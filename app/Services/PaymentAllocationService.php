<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentAllocationService
{
    /**
     * Record a payment and automatically allocate it
     * against the student's oldest outstanding fees.
     *
     * Allocation order:
     *
     * 1. Oldest period_start
     * 2. Lowest fee ID when periods are identical
     *
     * Any amount remaining after all outstanding fees
     * are paid remains as advance credit.
     *
     * payment_allocations is the authoritative source
     * for determining how much of a payment has been used.
     */
    public function recordPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Validate payment amount
            |--------------------------------------------------------------------------
            */

            $amount = round(
                (float) ($data['amount'] ?? 0),
                2
            );

            if ($amount <= 0) {
                throw new RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Validate student ID
            |--------------------------------------------------------------------------
            */

            $studentId = (int) ($data['student_id'] ?? 0);

            if ($studentId <= 0) {
                throw new RuntimeException(
                    'A valid student is required.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Lock and verify student
            |--------------------------------------------------------------------------
            |
            | Locking the student row helps prevent two simultaneous payment
            | transactions from allocating against the same fee set at the
            | same time.
            |
            */

            $student = Student::query()
                ->where('id', $studentId)
                ->lockForUpdate()
                ->first();

            if (!$student) {
                throw new RuntimeException(
                    'The selected student does not exist.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Only active students can receive new payments
            |--------------------------------------------------------------------------
            |
            | This is enforced here as well as in the controller so that
            | the service remains safe if it is called from another place.
            |
            */

            if (!$student->active) {
                throw new RuntimeException(
                    'The selected student is not active and cannot receive a new payment.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Validate payment date
            |--------------------------------------------------------------------------
            */

            if (empty($data['payment_date'])) {
                throw new RuntimeException(
                    'Payment date is required.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Validate payment mode
            |--------------------------------------------------------------------------
            */

            $allowedPaymentModes = [
                'cash',
                'upi',
                'bank',
                'cheque',
            ];

            $paymentMode = $data['payment_mode'] ?? null;

            if (!in_array($paymentMode, $allowedPaymentModes, true)) {
                throw new RuntimeException(
                    'Invalid payment mode.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Create payment
            |--------------------------------------------------------------------------
            |
            | fee_id intentionally remains NULL.
            |
            | One payment can be allocated against multiple fees, therefore
            | payment_allocations is the authoritative relationship.
            |
            */

            $payment = Payment::create([
                'student_id' => $studentId,

                'payment_date' => $data['payment_date'],

                'amount' => $amount,

                'payment_mode' => $paymentMode,

                'reference' =>
                    !empty($data['reference'])
                        ? trim($data['reference'])
                        : null,

                'notes' =>
                    !empty($data['notes'])
                        ? trim($data['notes'])
                        : null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Remaining payment amount
            |--------------------------------------------------------------------------
            */

            $remaining = $amount;


            /*
            |--------------------------------------------------------------------------
            | Find outstanding fees
            |--------------------------------------------------------------------------
            |
            | Oldest outstanding fee is paid first.
            |
            | Outstanding condition:
            |
            | amount + late_fee > paid_amount
            |
            */

            $fees = Fee::query()
                ->where('student_id', $studentId)

                ->whereRaw(
                    '(amount + late_fee) > paid_amount'
                )

                ->orderBy('period_start')
                ->orderBy('id')

                ->lockForUpdate()

                ->get();


            /*
            |--------------------------------------------------------------------------
            | Allocate payment against fees
            |--------------------------------------------------------------------------
            */

            foreach ($fees as $fee) {

                /*
                |--------------------------------------------------------------------------
                | Stop when payment is completely allocated
                |--------------------------------------------------------------------------
                */

                if ($remaining <= 0.00) {
                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Current fee amounts
                |--------------------------------------------------------------------------
                */

                $feeAmount = round(
                    (float) $fee->amount,
                    2
                );

                $lateFee = round(
                    (float) $fee->late_fee,
                    2
                );

                $alreadyPaid = round(
                    (float) $fee->paid_amount,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Total amount required for this fee
                |--------------------------------------------------------------------------
                */

                $totalRequired = round(
                    $feeAmount + $lateFee,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Protect against invalid zero/negative fee
                |--------------------------------------------------------------------------
                */

                if ($totalRequired <= 0.00) {

                    $fee->paid_amount = 0.00;
                    $fee->status = 'paid';
                    $fee->save();

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Calculate outstanding amount
                |--------------------------------------------------------------------------
                */

                $outstanding = round(
                    $totalRequired - $alreadyPaid,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Fee already fully paid
                |--------------------------------------------------------------------------
                */

                if ($outstanding <= 0.00) {

                    /*
                    | Normalize paid amount and status.
                    */

                    $fee->paid_amount = $totalRequired;
                    $fee->status = 'paid';
                    $fee->save();

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Determine allocation amount
                |--------------------------------------------------------------------------
                */

                $allocationAmount = round(
                    min(
                        $remaining,
                        $outstanding
                    ),
                    2
                );


                if ($allocationAmount <= 0.00) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Calculate new paid amount
                |--------------------------------------------------------------------------
                */

                $newPaidAmount = round(
                    $alreadyPaid + $allocationAmount,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Update fee status
                |--------------------------------------------------------------------------
                */

                if ($newPaidAmount >= $totalRequired) {

                    /*
                    | Never allow paid_amount to exceed the fee requirement.
                    */

                    $newPaidAmount = $totalRequired;

                    $fee->status = 'paid';

                } else {

                    $fee->status = 'partial';
                }


                /*
                |--------------------------------------------------------------------------
                | Save updated fee
                |--------------------------------------------------------------------------
                */

                $fee->paid_amount = $newPaidAmount;

                $fee->save();


                /*
                |--------------------------------------------------------------------------
                | Create allocation record
                |--------------------------------------------------------------------------
                */

                $payment->allocations()->create([
                    'fee_id' => $fee->id,
                    'amount' => $allocationAmount,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Reduce remaining payment
                |--------------------------------------------------------------------------
                */

                $remaining = round(
                    $remaining - $allocationAmount,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Protect against floating-point residue
                |--------------------------------------------------------------------------
                */

                if (abs($remaining) < 0.01) {

                    $remaining = 0.00;

                    break;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Advance payment
            |--------------------------------------------------------------------------
            |
            | Any remaining amount is NOT inserted into a fee.
            |
            | Example:
            |
            | Payment       = ₹5,000
            | Outstanding   = ₹3,000
            |
            | Allocated     = ₹3,000
            | Advance       = ₹2,000
            |
            | The ₹2,000 advance is represented by:
            |
            | payment.amount
            | -
            | total payment_allocations.amount
            |
            |
            | This is exactly what:
            |
            | Payment::allocated_amount
            | Payment::advance_amount
            |
            | calculate.
            |
            */


            /*
            |--------------------------------------------------------------------------
            | Return fresh payment with relationships
            |--------------------------------------------------------------------------
            */

            return $payment->fresh([
                'student',
                'student.bus',
                'allocations',
                'allocations.fee',
            ]);
        });
    }
}