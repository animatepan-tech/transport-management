<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentAllocationService
{
    public function __construct(
        private readonly FeeAllocationService $feeAllocation
    ) {
    }

    /**
     * Record a payment and automatically allocate it
     * against the student's oldest outstanding fees.
     *
     * Payment creation belongs here.
     *
     * Fee allocation belongs to FeeAllocationService.
     */
    public function recordPayment(
        array $data
    ): Payment {
        return DB::transaction(
            function () use ($data) {

                /*
                |--------------------------------------------------------------------------
                | Validate payment amount
                |--------------------------------------------------------------------------
                */

                $amount = round(
                    (float) (
                        $data['amount']
                        ?? 0
                    ),
                    2
                );

                if ($amount <= 0) {
                    throw new RuntimeException(
                        'Payment amount must be greater than zero.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validate student
                |--------------------------------------------------------------------------
                */

                $studentId = (int) (
                    $data['student_id']
                    ?? 0
                );

                if ($studentId <= 0) {
                    throw new RuntimeException(
                        'A valid student is required.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Lock and verify student
                |--------------------------------------------------------------------------
                */

                $student = Student::query()
                    ->where(
                        'id',
                        $studentId
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$student) {
                    throw new RuntimeException(
                        'The selected student does not exist.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Only active students may receive payments
                |--------------------------------------------------------------------------
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

                if (
                    empty(
                        $data['payment_date']
                    )
                ) {
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

                $paymentMode =
                    $data['payment_mode']
                    ?? null;

                if (
                    !in_array(
                        $paymentMode,
                        $allowedPaymentModes,
                        true
                    )
                ) {
                    throw new RuntimeException(
                        'Invalid payment mode.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Create payment
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                |
                | payment does not contain fee_id.
                |
                | The relationship is:
                |
                | payment
                |     ↓
                | payment_allocations
                |     ↓
                | fee
                |
                */

                $payment = Payment::create([
                    'student_id' =>
                        $studentId,

                    'payment_date' =>
                        $data['payment_date'],

                    'amount' =>
                        $amount,

                    'payment_mode' =>
                        $paymentMode,

                    'reference' =>
                        !empty(
                            $data['reference']
                        )
                            ? trim(
                                $data['reference']
                            )
                            : null,

                    'notes' =>
                        !empty(
                            $data['notes']
                        )
                            ? trim(
                                $data['notes']
                            )
                            : null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Centralized allocation
                |--------------------------------------------------------------------------
                */

                $this->feeAllocation
                    ->allocatePaymentToOutstandingFees(
                        $payment
                    );

                /*
                |--------------------------------------------------------------------------
                | Return fresh payment
                |--------------------------------------------------------------------------
                */

                return $payment->fresh([
                    'student',
                    'student.bus',
                    'allocations',
                    'allocations.fee',
                ]);
            }
        );
    }
}