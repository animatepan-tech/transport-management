<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeeGenerationService
{
    public function __construct(
        private readonly FeeAllocationService $feeAllocation
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
     * allocated to the newly created fee.
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

        /*
        |--------------------------------------------------------------------------
        | Generate for active students
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
                    &$skippedZeroAmount
                ) {

                    foreach ($students as $student) {

                        DB::transaction(
                            function () use (
                                $student,
                                $start,
                                $end,
                                $billingType,
                                &$created,
                                &$skipped,
                                &$skippedBeforeStartDate,
                                &$skippedZeroAmount
                            ) {

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

                                    return;
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

                                    return;
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
                                | Calculate fee amount
                                |--------------------------------------------------------------------------
                                */

                                $amount = round(
                                    (float) $student->monthly_fee
                                    * $numberOfMonths,
                                    2
                                );

                                /*
                                |--------------------------------------------------------------------------
                                | Do not create zero-value fees
                                |--------------------------------------------------------------------------
                                */

                                if ($amount <= 0) {

                                    $skippedZeroAmount++;

                                    return;
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

                                $created++;

                                /*
                                |--------------------------------------------------------------------------
                                | CENTRALIZED ADVANCE ALLOCATION
                                |--------------------------------------------------------------------------
                                |
                                | FeeGenerationService no longer implements
                                | payment allocation itself.
                                |
                                */

                                $this->feeAllocation
                                    ->allocateAdvanceToFee(
                                        $fee
                                    );
                            }
                        );
                    }
                }
            );

        return [
            'created' =>
                $created,

            'skipped' =>
                $skipped,

            'skipped_before_start_date' =>
                $skippedBeforeStartDate,

            'skipped_zero_amount' =>
                $skippedZeroAmount,

            'period_start' =>
                $start->toDateString(),

            'period_end' =>
                $end->toDateString(),
        ];
    }
}