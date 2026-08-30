<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;

class FeeAllocationService
{
    /**
     * Allocate any existing unused payment balance
     * against a newly generated fee.
     *
     * Allocation order:
     *
     * 1. Oldest payment date
     * 2. Oldest payment ID
     *
     * The payment's unused amount is:
     *
     * payment.amount
     * -
     * SUM(payment_allocations.amount)
     *
     * The allocation will never exceed the fee's
     * outstanding amount.
     */
    public function allocateAdvanceToFee(Fee $fee): float
    {
        $allocatedTotal = 0.00;

        /*
        |--------------------------------------------------------------------------
        | Get payments belonging to this student
        |--------------------------------------------------------------------------
        |
        | Lock the payment rows because another payment operation may
        | be happening at the same time.
        |
        */

        $payments = Payment::query()
            ->where('student_id', $fee->student_id)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Current fee balance
        |--------------------------------------------------------------------------
        */

        $totalRequired = round(
            (float) $fee->amount
            + (float) $fee->late_fee,
            2
        );

        $alreadyPaid = round(
            (float) $fee->paid_amount,
            2
        );

        $outstanding = round(
            $totalRequired - $alreadyPaid,
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Nothing to allocate
        |--------------------------------------------------------------------------
        */

        if ($outstanding <= 0) {

            return 0.00;
        }


        /*
        |--------------------------------------------------------------------------
        | Process oldest payment first
        |--------------------------------------------------------------------------
        */

        foreach ($payments as $payment) {

            if ($outstanding <= 0) {
                break;
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate amount already allocated from this payment
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
            | Calculate unused payment balance
            |--------------------------------------------------------------------------
            */

            $advance = round(
                (float) $payment->amount
                - $alreadyAllocated,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Ignore payments with no remaining balance
            |--------------------------------------------------------------------------
            */

            if ($advance <= 0) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Determine allocation amount
            |--------------------------------------------------------------------------
            */

            $allocationAmount = round(
                min(
                    $advance,
                    $outstanding
                ),
                2
            );


            if ($allocationAmount <= 0) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Update fee paid amount
            |--------------------------------------------------------------------------
            */

            $newPaidAmount = round(
                $alreadyPaid + $allocationAmount,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Never allow paid amount to exceed fee requirement
            |--------------------------------------------------------------------------
            */

            if ($newPaidAmount >= $totalRequired) {

                $newPaidAmount = $totalRequired;

                $fee->status = 'paid';

            } else {

                $fee->status = 'partial';
            }


            $fee->paid_amount = $newPaidAmount;

            $fee->save();


            /*
            |--------------------------------------------------------------------------
            | Create payment allocation
            |--------------------------------------------------------------------------
            */

            $payment->allocations()->create([
                'fee_id' => $fee->id,
                'amount' => $allocationAmount,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Update running totals
            |--------------------------------------------------------------------------
            */

            $allocatedTotal = round(
                $allocatedTotal + $allocationAmount,
                2
            );

            $alreadyPaid = $newPaidAmount;

            $outstanding = round(
                $totalRequired - $alreadyPaid,
                2
            );
        }


        return round(
            $allocatedTotal,
            2
        );
    }
}
