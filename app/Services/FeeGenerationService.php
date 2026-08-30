<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeeGenerationService
{
    /**
     * Generate fees for all active students.
     *
     * Existing advance payments are automatically
     * allocated to newly generated fees.
     *
     * Allocation order:
     *
     * 1. Oldest payment first
     * 2. Within the new fee, payment is applied until
     *    the fee is fully paid
     * 3. Any unused payment remains as advance
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
            'monthly' => 1,
            'quarterly' => 3,
            'half_yearly' => 6,
            'yearly' => 12,
            default => throw new \InvalidArgumentException(
                'Invalid billing type.'
            ),
        };

        $end = $start
            ->copy()
            ->addMonths($months - 1)
            ->endOfMonth();

        $created = 0;
        $skipped = 0;
        $skippedBeforeStartDate = 0;
        $skippedZeroAmount = 0;

        Student::query()
            ->where('active', true)
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
                                |
                                | If the student's transport/service start date
                                | is after the entire billing period, do not
                                | generate a fee.
                                |
                                */

                                if (
                                    $student->start_date &&
                                    $student->start_date->gt($end)
                                ) {

                                    $skippedBeforeStartDate++;

                                    return;
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | Prevent overlapping fee periods
                                |--------------------------------------------------------------------------
                                */

                                $overlapExists = Fee::query()
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
                                        ($end->year - $start->year)
                                        * 12
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
                                    'student_id' => $student->id,

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
                                | Apply existing advance payments
                                |--------------------------------------------------------------------------
                                */

                                $this->applyAdvanceToFee(
                                    $student,
                                    $fee
                                );
                            }
                        );
                    }
                }
            );

        return [
            'created' => $created,
            'skipped' => $skipped,
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

    /**
     * Apply available advance payments to a fee.
     *
     * Payments are consumed oldest-first.
     */
    protected function applyAdvanceToFee(
        Student $student,
        Fee $fee
    ): void {

        $totalRequired = round(
            (float) $fee->amount
            + (float) $fee->late_fee,
            2
        );

        $remainingFee = round(
            $totalRequired
            - (float) $fee->paid_amount,
            2
        );

        if ($remainingFee <= 0) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get student's payments oldest-first
        |--------------------------------------------------------------------------
        */

        $payments = Payment::query()
            ->where(
                'student_id',
                $student->id
            )
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($payments as $payment) {

            if ($remainingFee <= 0) {
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Amount already allocated from this payment
            |--------------------------------------------------------------------------
            */

            $allocatedAmount = (float) $payment
                ->allocations()
                ->sum('amount');

            /*
            |--------------------------------------------------------------------------
            | Remaining advance on this payment
            |--------------------------------------------------------------------------
            */

            $advanceAmount = round(
                (float) $payment->amount
                - $allocatedAmount,
                2
            );

            if ($advanceAmount <= 0) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Allocate payment
            |--------------------------------------------------------------------------
            */

            $allocationAmount = round(
                min(
                    $advanceAmount,
                    $remainingFee
                ),
                2
            );

            if ($allocationAmount <= 0) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create allocation
            |--------------------------------------------------------------------------
            */

            $payment->allocations()->create([
                'fee_id' =>
                    $fee->id,

                'amount' =>
                    $allocationAmount,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update fee paid amount
            |--------------------------------------------------------------------------
            */

            $fee->paid_amount = round(
                (float) $fee->paid_amount
                + $allocationAmount,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Prevent overpayment on fee
            |--------------------------------------------------------------------------
            */

            if (
                $fee->paid_amount
                >=
                $totalRequired
            ) {

                $fee->paid_amount =
                    $totalRequired;

                $fee->status =
                    'paid';

                $remainingFee = 0;

            } else {

                $fee->status =
                    'partial';

                $remainingFee = round(
                    $totalRequired
                    - (float) $fee->paid_amount,
                    2
                );
            }

            $fee->save();
        }
    }
}