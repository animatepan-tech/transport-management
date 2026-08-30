<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Expense;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     *
     * Report accounting rules:
     *
     * 1. Fees are generated according to fee.period_start.
     * 2. Payments are counted according to payment.payment_date.
     * 3. Payment allocations are counted according to the
     *    payment's payment_date.
     * 4. Outstanding balances are based on the selected fees'
     *    current paid_amount.
     * 5. Advance credit is the unallocated portion of payments
     *    received during the selected period.
     * 6. Bus-wise payment allocation uses payment_allocations,
     *    not Fee.paid_amount, so the bus report reconciles with
     *    period payments.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate filters
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],

            'bus_id' => [
                'nullable',
                'integer',
                'exists:buses,id',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Report date range
        |--------------------------------------------------------------------------
        |
        | Default = current month.
        |
        */

        $from = !empty($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()
            : now()->startOfMonth();

        $to = !empty($data['to'])
            ? Carbon::parse($data['to'])->endOfDay()
            : now()->endOfDay();


        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();


        /*
        |--------------------------------------------------------------------------
        | Selected bus
        |--------------------------------------------------------------------------
        */

        $busId = $data['bus_id'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */

        $totalStudents = Student::query()
            ->when(
                $busId,
                fn ($query) =>
                    $query->where('bus_id', $busId)
            )
            ->count();


        $activeStudents = Student::query()
            ->where('active', true)
            ->when(
                $busId,
                fn ($query) =>
                    $query->where('bus_id', $busId)
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Fees generated during selected period
        |--------------------------------------------------------------------------
        */

        $feesQuery = Fee::query()
            ->whereDate('period_start', '>=', $fromDate)
            ->whereDate('period_start', '<=', $toDate)
            ->when(
                $busId,
                function ($query) use ($busId) {

                    $query->whereHas(
                        'student',
                        fn ($studentQuery) =>
                            $studentQuery->where(
                                'bus_id',
                                $busId
                            )
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Fee totals
        |--------------------------------------------------------------------------
        */

        $totalFees = round(
            (float) $feesQuery->sum('amount'),
            2
        );


        $totalLateFees = round(
            (float) $feesQuery->sum('late_fee'),
            2
        );


        $totalRequired = round(
            $totalFees + $totalLateFees,
            2
        );


        $feeRecordCount = $feesQuery->count();


        /*
        |--------------------------------------------------------------------------
        | Fee status counts
        |--------------------------------------------------------------------------
        */

        $paidFees = (clone $feesQuery)
            ->where('status', 'paid')
            ->count();


        $partialFees = (clone $feesQuery)
            ->where('status', 'partial')
            ->count();


        $pendingFees = (clone $feesQuery)
            ->where('status', 'pending')
            ->count();


        $carriedForwardFees = (clone $feesQuery)
            ->where('status', 'carried_forward')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Outstanding fee amount
        |--------------------------------------------------------------------------
        |
        | Calculate this fee-by-fee so an individual stale or
        | overpaid record cannot distort the entire report.
        |
        */

        $totalOutstanding = round(
            (float) (clone $feesQuery)
                ->get()
                ->sum(function ($fee) {

                    $required = round(
                        (float) $fee->amount
                        + (float) $fee->late_fee,
                        2
                    );

                    $paid = round(
                        (float) $fee->paid_amount,
                        2
                    );

                    return max(
                        0,
                        round(
                            $required - $paid,
                            2
                        )
                    );
                }),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Payments received during selected period
        |--------------------------------------------------------------------------
        */

        $paymentsQuery = Payment::query()
            ->whereDate('payment_date', '>=', $fromDate)
            ->whereDate('payment_date', '<=', $toDate)
            ->when(
                $busId,
                function ($query) use ($busId) {

                    $query->whereHas(
                        'student',
                        fn ($studentQuery) =>
                            $studentQuery->where(
                                'bus_id',
                                $busId
                            )
                    );
                }
            );


        $totalPayments = round(
            (float) $paymentsQuery->sum('amount'),
            2
        );


        $paymentCount = $paymentsQuery->count();


        /*
        |--------------------------------------------------------------------------
        | Allocations created from payments during selected period
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We use payment_allocations joined to payments.
        |
        | This means the amount represents allocations belonging
        | to payments received during this report period.
        |
        */

        $paymentAllocationsQuery = DB::table(
            'payment_allocations'
        )
            ->join(
                'payments',
                'payments.id',
                '=',
                'payment_allocations.payment_id'
            )
            ->join(
                'students',
                'students.id',
                '=',
                'payments.student_id'
            )
            ->whereDate(
                'payments.payment_date',
                '>=',
                $fromDate
            )
            ->whereDate(
                'payments.payment_date',
                '<=',
                $toDate
            )
            ->when(
                $busId,
                fn ($query) =>
                    $query->where(
                        'students.bus_id',
                        $busId
                    )
            );


        $totalPaymentAllocated = round(
            (float) $paymentAllocationsQuery
                ->sum('payment_allocations.amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Advance credit
        |--------------------------------------------------------------------------
        |
        | Advance = payment received during period
        |           minus allocations belonging to those payments.
        |
        */

        $totalAdvance = round(
            max(
                0,
                $totalPayments - $totalPaymentAllocated
            ),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */

        $expensesQuery = Expense::query()
            ->whereDate('expense_date', '>=', $fromDate)
            ->whereDate('expense_date', '<=', $toDate)
            ->when(
                $busId,
                fn ($query) =>
                    $query->where(
                        'bus_id',
                        $busId
                    )
            );


        $totalExpenses = round(
            (float) $expensesQuery->sum('amount'),
            2
        );


        $expenseCount = $expensesQuery->count();


        /*
        |--------------------------------------------------------------------------
        | Net cash
        |--------------------------------------------------------------------------
        */

        $netCash = round(
            $totalPayments - $totalExpenses,
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Payment mode summary
        |--------------------------------------------------------------------------
        */

        $paymentModes = (clone $paymentsQuery)
            ->select(
                'payment_mode',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('payment_mode')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Expense category summary
        |--------------------------------------------------------------------------
        */

        $expenseCategories = (clone $expensesQuery)
            ->select(
                'category',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Bus-wise financial report
        |--------------------------------------------------------------------------
        */

        $busReports = Bus::query()
            ->withCount('students')
            ->when(
                $busId,
                fn ($query) =>
                    $query->where('id', $busId)
            )
            ->orderBy('bus_number')
            ->get()
            ->map(
                function ($bus) use (
                    $fromDate,
                    $toDate
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Student IDs belonging to this bus
                    |--------------------------------------------------------------------------
                    */

                    $studentIds = Student::query()
                        ->where('bus_id', $bus->id)
                        ->pluck('id');


                    /*
                    |--------------------------------------------------------------------------
                    | Fees generated during selected period
                    |--------------------------------------------------------------------------
                    */

                    $fees = Fee::query()
                        ->whereIn(
                            'student_id',
                            $studentIds
                        )
                        ->whereDate(
                            'period_start',
                            '>=',
                            $fromDate
                        )
                        ->whereDate(
                            'period_start',
                            '<=',
                            $toDate
                        );


                    $feesAmount = round(
                        (float) $fees->sum('amount'),
                        2
                    );


                    $lateFees = round(
                        (float) $fees->sum('late_fee'),
                        2
                    );


                    $required = round(
                        $feesAmount + $lateFees,
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Current outstanding for these fees
                    |--------------------------------------------------------------------------
                    */

                    $outstanding = round(
                        (float) (clone $fees)
                            ->get()
                            ->sum(function ($fee) {

                                $required = round(
                                    (float) $fee->amount
                                    + (float) $fee->late_fee,
                                    2
                                );

                                $paid = round(
                                    (float) $fee->paid_amount,
                                    2
                                );

                                return max(
                                    0,
                                    round(
                                        $required - $paid,
                                        2
                                    )
                                );
                            }),
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Payments received by this bus during period
                    |--------------------------------------------------------------------------
                    */

                    $paymentsAmount = round(
                        (float) Payment::query()
                            ->whereIn(
                                'student_id',
                                $studentIds
                            )
                            ->whereDate(
                                'payment_date',
                                '>=',
                                $fromDate
                            )
                            ->whereDate(
                                'payment_date',
                                '<=',
                                $toDate
                            )
                            ->sum('amount'),
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Allocations from this bus's payments
                    |--------------------------------------------------------------------------
                    */

                    $allocated = round(
                        (float) DB::table(
                            'payment_allocations'
                        )
                            ->join(
                                'payments',
                                'payments.id',
                                '=',
                                'payment_allocations.payment_id'
                            )
                            ->whereIn(
                                'payments.student_id',
                                $studentIds
                            )
                            ->whereDate(
                                'payments.payment_date',
                                '>=',
                                $fromDate
                            )
                            ->whereDate(
                                'payments.payment_date',
                                '<=',
                                $toDate
                            )
                            ->sum(
                                'payment_allocations.amount'
                            ),
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Expenses assigned to this bus
                    |--------------------------------------------------------------------------
                    */

                    $expensesAmount = round(
                        (float) Expense::query()
                            ->where(
                                'bus_id',
                                $bus->id
                            )
                            ->whereDate(
                                'expense_date',
                                '>=',
                                $fromDate
                            )
                            ->whereDate(
                                'expense_date',
                                '<=',
                                $toDate
                            )
                            ->sum('amount'),
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Net cash
                    |--------------------------------------------------------------------------
                    */

                    $net = round(
                        $paymentsAmount
                        - $expensesAmount,
                        2
                    );


                    return [
                        'bus' =>
                            $bus,

                        'students' =>
                            $bus->students_count,

                        'fees' =>
                            $feesAmount,

                        'late_fees' =>
                            $lateFees,

                        'required' =>
                            $required,

                        'allocated' =>
                            $allocated,

                        'outstanding' =>
                            $outstanding,

                        'payments' =>
                            $paymentsAmount,

                        'expenses' =>
                            $expensesAmount,

                        'net' =>
                            $net,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Outstanding student accounts
        |--------------------------------------------------------------------------
        |
        | These are current balances for fees generated inside
        | the selected report period.
        |
        */

        $students = Student::query()
            ->with('bus')
            ->when(
                $busId,
                fn ($query) =>
                    $query->where(
                        'bus_id',
                        $busId
                    )
            )
            ->orderBy('student_name')
            ->get()
            ->map(
                function ($student) use (
                    $fromDate,
                    $toDate
                ) {

                    $fees = Fee::query()
                        ->where(
                            'student_id',
                            $student->id
                        )
                        ->whereDate(
                            'period_start',
                            '>=',
                            $fromDate
                        )
                        ->whereDate(
                            'period_start',
                            '<=',
                            $toDate
                        )
                        ->get();


                    $required = round(
                        (float) $fees->sum(
                            fn ($fee) =>
                                (float) $fee->amount
                                + (float) $fee->late_fee
                        ),
                        2
                    );


                    $paid = round(
                        (float) $fees->sum(
                            fn ($fee) =>
                                (float) $fee->paid_amount
                        ),
                        2
                    );


                    $due = round(
                        (float) $fees->sum(
                            function ($fee) {

                                $required = round(
                                    (float) $fee->amount
                                    + (float) $fee->late_fee,
                                    2
                                );

                                $paid = round(
                                    (float) $fee->paid_amount,
                                    2
                                );

                                return max(
                                    0,
                                    round(
                                        $required - $paid,
                                        2
                                    )
                                );
                            }
                        ),
                        2
                    );


                    return [
                        'student' =>
                            $student,

                        'required' =>
                            $required,

                        'paid' =>
                            $paid,

                        'due' =>
                            $due,
                    ];
                }
            )
            ->filter(
                fn ($row) =>
                    $row['due'] > 0.01
            )
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Buses for filter
        |--------------------------------------------------------------------------
        */

        $buses = Bus::query()
            ->orderBy('bus_number')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Render
        |--------------------------------------------------------------------------
        */

        return view(
            'reports.index',
            compact(
                'from',
                'to',
                'busId',
                'buses',

                'totalStudents',
                'activeStudents',

                'totalFees',
                'totalLateFees',
                'totalRequired',
                'feeRecordCount',

                'paidFees',
                'partialFees',
                'pendingFees',
                'carriedForwardFees',

                'totalOutstanding',

                'totalPayments',
                'paymentCount',

                'totalPaymentAllocated',
                'totalAdvance',

                'totalExpenses',
                'expenseCount',

                'netCash',

                'paymentModes',
                'expenseCategories',

                'busReports',
                'students'
            )
        );
    }
}