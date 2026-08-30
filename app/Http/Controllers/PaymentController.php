<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Display payment history.
     */
    public function index(): View
    {
        $payments = Payment::query()
            ->with([
                'student.bus',
                'allocations.fee',
            ])
            ->latest('payment_date')
            ->latest('id')
            ->paginate(25);

        return view(
            'payments.index',
            compact('payments')
        );
    }


    /**
     * Show the record-payment form.
     *
     * Optional URL:
     *
     * /payments/create?student_id=1
     */
    public function create(
        Request $request
    ): View {
        /*
        |--------------------------------------------------------------------------
        | Load all active students
        |--------------------------------------------------------------------------
        |
        | Your database currently has:
        |
        | Rahul         -> active = 1
        | Test Student  -> active = 1
        |
        | Therefore both must be loaded here.
        |
        */

        $students = Student::query()
            ->where('active', 1)
            ->with('bus')
            ->orderBy('student_name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Safe default values
        |--------------------------------------------------------------------------
        */

        $selectedStudent = null;

        $account = [
            'total_fees' => 0.00,
            'total_late_fees' => 0.00,
            'total_paid' => 0.00,
            'total_allocated' => 0.00,
            'advance_amount' => 0.00,
            'due_amount' => 0.00,
            'current_balance' => 0.00,
            'outstanding_fees' => collect(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Selected student
        |--------------------------------------------------------------------------
        */

        $studentId = $request->input('student_id');


        if (
            $studentId !== null
            && $studentId !== ''
        ) {

            /*
            |--------------------------------------------------------------------------
            | Only active students can be selected
            |--------------------------------------------------------------------------
            */

            $selectedStudent = Student::query()
                ->where('active', 1)
                ->with('bus')
                ->find((int) $studentId);


            if ($selectedStudent) {

                /*
                |--------------------------------------------------------------------------
                | Total generated fees
                |--------------------------------------------------------------------------
                */

                $totalFees = round(
                    (float) $selectedStudent
                        ->fees()
                        ->sum('amount'),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Total late fees
                |--------------------------------------------------------------------------
                */

                $totalLateFees = round(
                    (float) $selectedStudent
                        ->fees()
                        ->sum('late_fee'),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Total payments received
                |--------------------------------------------------------------------------
                */

                $totalPaid = round(
                    (float) $selectedStudent
                        ->payments()
                        ->sum('amount'),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Total allocated amount
                |--------------------------------------------------------------------------
                |
                | Payment allocations are the accounting source of truth.
                |
                */

                $totalAllocated = round(
                    (float) $selectedStudent
                        ->fees()
                        ->withSum(
                            'allocations',
                            'amount'
                        )
                        ->get()
                        ->sum(
                            fn ($fee) =>
                                (float) (
                                    $fee->allocations_sum_amount
                                    ?? 0
                                )
                        ),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Advance credit
                |--------------------------------------------------------------------------
                |
                | Amount received but not allocated to fees.
                |
                */

                $advanceAmount = max(
                    0,
                    round(
                        $totalPaid
                        - $totalAllocated,
                        2
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | Total amount required
                |--------------------------------------------------------------------------
                */

                $totalRequired = round(
                    $totalFees
                    + $totalLateFees,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Outstanding amount
                |--------------------------------------------------------------------------
                */

                $dueAmount = max(
                    0,
                    round(
                        $totalRequired
                        - $totalAllocated,
                        2
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | Current account balance
                |--------------------------------------------------------------------------
                |
                | Negative = amount due
                | Zero     = settled
                | Positive = advance
                |
                */

                $currentBalance = round(
                    $advanceAmount
                    - $dueAmount,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Outstanding fee records
                |--------------------------------------------------------------------------
                |
                | Use allocation totals rather than relying only on
                | paid_amount so the display follows the same accounting
                | source of truth as payment allocation.
                |
                */

                $feeRecords = $selectedStudent
                    ->fees()
                    ->withSum(
                        'allocations',
                        'amount'
                    )
                    ->orderBy('period_start')
                    ->orderBy('id')
                    ->get();


                $outstandingFees = $feeRecords
                    ->filter(
                        function ($fee) {

                            $required = round(
                                (float) $fee->amount
                                + (float) $fee->late_fee,
                                2
                            );

                            $allocated = round(
                                (float) (
                                    $fee->allocations_sum_amount
                                    ?? 0
                                ),
                                2
                            );

                            return round(
                                $required - $allocated,
                                2
                            ) > 0.01;
                        }
                    )
                    ->values();


                /*
                |--------------------------------------------------------------------------
                | Prepare account data for Blade
                |--------------------------------------------------------------------------
                */

                $account = [
                    'total_fees' =>
                        $totalFees,

                    'total_late_fees' =>
                        $totalLateFees,

                    'total_paid' =>
                        $totalPaid,

                    'total_allocated' =>
                        $totalAllocated,

                    'advance_amount' =>
                        $advanceAmount,

                    'due_amount' =>
                        $dueAmount,

                    'current_balance' =>
                        $currentBalance,

                    'outstanding_fees' =>
                        $outstandingFees,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Return payment form
        |--------------------------------------------------------------------------
        */

        return view(
            'payments.form',
            [
                'students' =>
                    $students,

                'selectedStudent' =>
                    $selectedStudent,

                'account' =>
                    $account,
            ]
        );
    }


    /**
     * Store a new payment.
     *
     * PaymentAllocationService handles:
     *
     * - payment creation
     * - oldest-fee-first allocation
     * - partial payments
     * - fully paid fees
     * - advance payments
     */
    public function store(
        Request $request,
        PaymentAllocationService $paymentService
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([

            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_mode' => [
                'required',
                'in:cash,upi,bank,cheque',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Make sure selected student is active
        |--------------------------------------------------------------------------
        */

        $student = Student::query()
            ->where('active', 1)
            ->find(
                (int) $data['student_id']
            );


        if (!$student) {

            return back()
                ->withInput()
                ->withErrors([
                    'student_id' =>
                        'The selected student is not active and cannot receive a new payment.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Record and allocate payment
        |--------------------------------------------------------------------------
        */

        try {

            $payment =
                $paymentService->recordPayment(
                    $data
                );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Service exception
            |--------------------------------------------------------------------------
            */

            return back()
                ->withInput()
                ->withErrors([
                    'amount' =>
                        $e->getMessage(),
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Reload allocations
        |--------------------------------------------------------------------------
        |
        | Makes sure the returned payment contains its latest allocations.
        |
        */

        $payment->load(
            'allocations'
        );


        /*
        |--------------------------------------------------------------------------
        | Calculate allocated amount
        |--------------------------------------------------------------------------
        */

        $allocatedAmount = round(
            (float) $payment
                ->allocations
                ->sum('amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Calculate advance amount
        |--------------------------------------------------------------------------
        */

        $advanceAmount = max(
            0,
            round(
                (float) $payment->amount
                - $allocatedAmount,
                2
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Build success message
        |--------------------------------------------------------------------------
        */

        if ($advanceAmount > 0.01) {

            $message =
                'Payment of ₹'
                . number_format(
                    (float) $payment->amount,
                    2
                )
                . ' recorded successfully. ₹'
                . number_format(
                    $allocatedAmount,
                    2
                )
                . ' was allocated to outstanding fees and ₹'
                . number_format(
                    $advanceAmount,
                    2
                )
                . ' remains as advance credit.';

        } else {

            $message =
                'Payment of ₹'
                . number_format(
                    (float) $payment->amount,
                    2
                )
                . ' recorded successfully and automatically allocated to outstanding fees.';
        }


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('payments.index')
            ->with(
                'success',
                $message
            );
    }
}