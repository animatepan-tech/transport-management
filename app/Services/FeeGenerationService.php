<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class FeeGenerationService
{
    public function __construct(
        private readonly FeeAllocationService $feeAllocation,
        private readonly WhatsAppFeeNotificationService $whatsappFeeNotification
    ) {
    }

    /**
     * Generate fees for all active students.
     *
     * Supported billing types:
     *
     * monthly      = 1 month
     * quarterly    = 3 months
     * half_yearly  = 6 months
     * yearly       = 12 months
     *
     * Existing unallocated payment balances are automatically
     * allocated to newly generated fees.
     *
     * After successful fee creation/allocation, the application
     * sends the WhatsApp fee notification.
     */
    public function generate(
        string $month,
        string $billingType
    ): array {
        $start = Carbon::createFromFormat(
            'Y-m',
            $month
        )->startOfMonth();

        $months = match ($billingType) {

            'monthly' =>
                1,

            'quarterly' =>
                3,

            'half_yearly' =>
                6,

            'yearly' =>
                12,

            default =>
                throw new \InvalidArgumentException(
                    'Invalid billing type.'
                ),
        };

        $end = $start
            ->copy()
            ->addMonths(
                $months - 1
            )
            ->endOfMonth();

        $created = 0;

        $skipped = 0;

        $skippedBeforeStartDate = 0;

        $skippedZeroAmount = 0;

        $createdFeeIds = [];

        /*
        |--------------------------------------------------------------------------
        | Generate fees for active students
        |--------------------------------------------------------------------------
        */

        Student::query()
            ->where(
                'active',
                true
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($students) use (
                    $start,
                    $end,
                    $billingType,
                    &$created,
                    &$skipped,
                    &$skippedBeforeStartDate,
                    &$skippedZeroAmount,
                    &$createdFeeIds
                ) {

                    foreach ($students as $student) {

                        $createdFeeId = DB::transaction(
                            function () use (
                                $student,
                                $start,
                                $end,
                                $billingType,
                                &$created,
                                &$skipped,
                                &$skippedBeforeStartDate,
                                &$skippedZeroAmount
                            ): ?int {

                                /*
                                |--------------------------------------------------------------------------
                                | Student start-date protection
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $student->start_date
                                    && $student->start_date->gt(
                                        $end
                                    )
                                ) {

                                    $skippedBeforeStartDate++;

                                    return null;
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | Prevent overlapping fee periods
                                |--------------------------------------------------------------------------
                                */

                                $overlapExists =
                                    Fee::query()
                                        ->where(
                                            'student_id',
                                            $student->id
                                        )
                                        ->whereDate(
                                            'period_start',
                                            '<=',
                                            $end->toDateString()
                                        )
                                        ->whereDate(
                                            'period_end',
                                            '>=',
                                            $start->toDateString()
                                        )
                                        ->lockForUpdate()
                                        ->exists();

                                if ($overlapExists) {

                                    $skipped++;

                                    return null;
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | Number of months
                                |--------------------------------------------------------------------------
                                */

                                $numberOfMonths =
                                    (
                                        (
                                            $end->year
                                            - $start->year
                                        ) * 12
                                    )
                                    + $end->month
                                    - $start->month
                                    + 1;

                                /*
                                |--------------------------------------------------------------------------
                                | Calculate fee
                                |--------------------------------------------------------------------------
                                */

                                $amount = round(
                                    (float) $student->monthly_fee
                                    * $numberOfMonths,
                                    2
                                );

                                /*
                                |--------------------------------------------------------------------------
                                | Do not create zero fees
                                |--------------------------------------------------------------------------
                                */

                                if ($amount <= 0.00) {

                                    $skippedZeroAmount++;

                                    return null;
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | Create fee
                                |--------------------------------------------------------------------------
                                */

                                $fee = Fee::create([
                                    'student_id' =>
                                        $student->id,

                                    'period_start' =>
                                        $start->toDateString(),

                                    'period_end' =>
                                        $end->toDateString(),

                                    'billing_type' =>
                                        $billingType,

                                    'amount' =>
                                        $amount,

                                    'paid_amount' =>
                                        0,

                                    'late_fee' =>
                                        0,

                                    'status' =>
                                        'pending',

                                    'last_reminder_at' =>
                                        null,

                                    'reminder_count' =>
                                        0,
                                ]);

                                /*
                                |--------------------------------------------------------------------------
                                | Apply existing advance
                                |--------------------------------------------------------------------------
                                */

                                $this->feeAllocation
                                    ->allocateAdvanceToFee(
                                        $fee
                                    );

                                $created++;

                                return $fee->id;
                            }
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Remember created fee
                        |--------------------------------------------------------------------------
                        |
                        | The WhatsApp API is intentionally NOT called from
                        | inside the transaction.
                        |
                        */

                        if ($createdFeeId !== null) {
                            $createdFeeIds[] =
                                $createdFeeId;
                        }
                    }
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Send WhatsApp notifications AFTER financial transactions
        |--------------------------------------------------------------------------
        */

        $whatsappSent = 0;

        $whatsappSkipped = 0;

        $whatsappFailed = 0;

        foreach ($createdFeeIds as $feeId) {

            try {

                $fee = Fee::with('student')
                    ->find($feeId);

                if (!$fee) {
                    continue;
                }

                $notification =
                    $this->whatsappFeeNotification
                        ->sendNewFeeNotification(
                            $fee
                        );

                $status =
                    $notification['status']
                    ?? 'failed';

                if (
                    $status === 'accepted'
                    || $status === 'sent'
                ) {

                    $whatsappSent++;

                } elseif (
                    $status === 'not_required'
                    || $status === 'already_sent'
                ) {

                    $whatsappSkipped++;

                } else {

                    $whatsappFailed++;
                }

            } catch (Throwable $e) {

                $whatsappFailed++;
            }
        }

        return [
            'created' =>
                $created,

            'skipped' =>
                $skipped,

            'skipped_before_start_date' =>
                $skippedBeforeStartDate,

            'skipped_zero_amount' =>
                $skippedZeroAmount,

            'whatsapp_sent' =>
                $whatsappSent,

            'whatsapp_skipped' =>
                $whatsappSkipped,

            'whatsapp_failed' =>
                $whatsappFailed,

            'period_start' =>
                $start->toDateString(),

            'period_end' =>
                $end->toDateString(),
        ];
    }
}