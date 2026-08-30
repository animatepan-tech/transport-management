<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FeeAllocationService
{
    /**
     * Allocate a payment against the student's oldest outstanding fees.
     *
     * This is the single authoritative service for payment-to-fee allocation.
     *
     * Rules:
     *
     * 1. Oldest fee period first.
     * 2. Lowest fee ID when periods are identical.
     * 3. Partial payments are supported.
     * 4. Fully paid fees become "paid".
     * 5. Any amount not allocated remains advance credit.
     *
     * Returns the amount allocated from the payment.
     */
    public function allocatePaymentToOutstandingFees(
        Payment $payment
    ): float {
        $paymentAmount = round(
            (float) $payment->amount,
            2
        );

        if ($paymentAmount <= 0) {
            throw new RuntimeException(
                'Payment amount must be greater than zero.'
            );
        }

        $studentId = (int) $payment->student_id;

        if ($studentId <= 0) {
            throw new RuntimeException(
                'Payment does not have a valid student.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate any amount already allocated from this payment
        |--------------------------------------------------------------------------
        |
        | This makes the method safe even if called on a payment that already
        | has one or more payment allocation records.
        |
        */

        $alreadyAllocated = round(
            (float) $payment
                ->allocations()
                ->sum('amount'),
            2
        );

        $remaining = max(
            0,
            round(
                $paymentAmount - $alreadyAllocated,
                2
            )
        );

        if ($remaining <= 0.00) {
            return 0.00;
        }

        /*
        |--------------------------------------------------------------------------
        | Find oldest outstanding fees
        |--------------------------------------------------------------------------
        */

        $fees = Fee::query()
            ->where(
                'student_id',
                $studentId
            )
            ->whereRaw(
                '(amount + late_fee) > paid_amount'
            )
            ->orderBy('period_start')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $allocatedTotal = 0.00;

        /*
        |--------------------------------------------------------------------------
        | Allocate payment oldest-first
        |--------------------------------------------------------------------------
        */

        foreach ($fees as $fee) {

            if ($remaining <= 0.00) {
                break;
            }

            $allocationAmount =
                $this->allocateAmountToFee(
                    $fee,
                    $remaining,
                    $payment
                );

            if ($allocationAmount <= 0.00) {
                continue;
            }

            $remaining = round(
                $remaining - $allocationAmount,
                2
            );

            if (abs($remaining) < 0.01) {
                $remaining = 0.00;
            }

            $allocatedTotal = round(
                $allocatedTotal + $allocationAmount,
                2
            );
        }

        return $allocatedTotal;
    }

    /**
     * Allocate existing unallocated payment balances to a newly generated fee.
     *
     * Payments are consumed oldest-first.
     *
     * Returns the amount allocated to the fee.
     */
    public function allocateAdvanceToFee(
        Fee $fee
    ): float {
        $totalRequired = $this->feeRequiredAmount(
            $fee
        );

        $alreadyPaid = round(
            (float) $fee->paid_amount,
            2
        );

        $remainingFee = max(
            0,
            round(
                $totalRequired - $alreadyPaid,
                2
            )
        );

        if ($remainingFee <= 0.00) {
            return 0.00;
        }

        /*
        |--------------------------------------------------------------------------
        | Get student's payments oldest-first
        |--------------------------------------------------------------------------
        */

        $payments = Payment::query()
            ->where(
                'student_id',
                $fee->student_id
            )
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $allocatedTotal = 0.00;

        /*
        |--------------------------------------------------------------------------
        | Consume available advance balances
        |--------------------------------------------------------------------------
        */

        foreach ($payments as $payment) {

            if ($remainingFee <= 0.00) {
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Amount already consumed from this payment
            |--------------------------------------------------------------------------
            */

            $alreadyAllocated = round(
                (float) $payment
                    ->allocations()
                    ->sum('amount'),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Remaining advance on payment
            |--------------------------------------------------------------------------
            */

            $paymentAdvance = max(
                0,
                round(
                    (float) $payment->amount
                    - $alreadyAllocated,
                    2
                )
            );

            if ($paymentAdvance <= 0.00) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Determine how much can be applied to this fee
            |--------------------------------------------------------------------------
            */

            $allocationAmount = round(
                min(
                    $paymentAdvance,
                    $remainingFee
                ),
                2
            );

            if ($allocationAmount <= 0.00) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create allocation + update fee
            |--------------------------------------------------------------------------
            */

            $this->applyAllocation(
                $fee,
                $payment,
                $allocationAmount
            );

            /*
            |--------------------------------------------------------------------------
            | Update running totals
            |--------------------------------------------------------------------------
            */

            $remainingFee = round(
                $remainingFee - $allocationAmount,
                2
            );

            if (abs($remainingFee) < 0.01) {
                $remainingFee = 0.00;
            }

            $allocatedTotal = round(
                $allocatedTotal + $allocationAmount,
                2
            );
        }

        return $allocatedTotal;
    }

    /**
     * Allocate a specific amount of a payment to a fee.
     *
     * This method contains the common fee-update/allocation logic used
     * by both payment allocation and advance allocation.
     */
    protected function allocateAmountToFee(
        Fee $fee,
        float $availableAmount,
        Payment $payment
    ): float {
        $totalRequired = $this->feeRequiredAmount(
            $fee
        );

        $alreadyPaid = round(
            (float) $fee->paid_amount,
            2
        );

        $outstanding = max(
            0,
            round(
                $totalRequired - $alreadyPaid,
                2
            )
        );

        if ($outstanding <= 0.00) {

            $fee->paid_amount =
                $totalRequired;

            $fee->status =
                'paid';

            $fee->save();

            return 0.00;
        }

        $allocationAmount = round(
            min(
                $availableAmount,
                $outstanding
            ),
            2
        );

        if ($allocationAmount <= 0.00) {
            return 0.00;
        }

        $this->applyAllocation(
            $fee,
            $payment,
            $allocationAmount
        );

        return $allocationAmount;
    }

    /**
     * Create payment allocation and update fee state.
     */
    protected function applyAllocation(
        Fee $fee,
        Payment $payment,
        float $allocationAmount
    ): void {
        $totalRequired = $this->feeRequiredAmount(
            $fee
        );

        $newPaidAmount = round(
            (float) $fee->paid_amount
            + $allocationAmount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Never overpay the fee
        |--------------------------------------------------------------------------
        */

        if ($newPaidAmount >= $totalRequired) {

            $newPaidAmount =
                $totalRequired;

            $fee->status =
                'paid';

        } else {

            $fee->status =
                'partial';
        }

        /*
        |--------------------------------------------------------------------------
        | Update fee
        |--------------------------------------------------------------------------
        */

        $fee->paid_amount =
            $newPaidAmount;

        $fee->save();

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
    }

    /**
     * Calculate the total amount required for a fee.
     *
     * Fee amount + late fee.
     */
    protected function feeRequiredAmount(
        Fee $fee
    ): float {
        return round(
            (float) $fee->amount
            + (float) $fee->late_fee,
            2
        );
    }
}