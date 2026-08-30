<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Expense;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     *
     * Dashboard figures are calculated from the existing
     * students, buses, fees, payments, payment_allocations
     * and expenses tables.
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Current period
        |--------------------------------------------------------------------------
        */

        $today = now()->startOfDay();

        $monthStart = now()->startOfMonth()->startOfDay();
        $monthEnd = now()->endOfMonth()->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */

        $totalStudents = Student::query()
            ->count();

        $activeStudents = Student::query()
            ->where('active', true)
            ->count();

        $inactiveStudents = Student::query()
            ->where('active', false)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Buses
        |--------------------------------------------------------------------------
        */

        $totalBuses = Bus::query()
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Current month fees
        |--------------------------------------------------------------------------
        */

        $feesQuery = Fee::query()
            ->whereDate(
                'period_start',
                '>=',
                $monthStart->toDateString()
            )
            ->whereDate(
                'period_start',
                '<=',
                $monthEnd->toDateString()
            );


        $monthlyFees = round(
            (float) $feesQuery->sum('amount'),
            2
        );


        $monthlyLateFees = round(
            (float) $feesQuery->sum('late_fee'),
            2
        );


        $monthlyRequired = round(
            $monthlyFees + $monthlyLateFees,
            2
        );


        $monthlyPaid = round(
            (float) $feesQuery->sum('paid_amount'),
            2
        );


        $monthlyOutstanding = max(
            0,
            round(
                $monthlyRequired - $monthlyPaid,
                2
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Current month payments
        |--------------------------------------------------------------------------
        */

        $paymentsQuery = Payment::query()
            ->whereDate(
                'payment_date',
                '>=',
                $monthStart->toDateString()
            )
            ->whereDate(
                'payment_date',
                '<=',
                $monthEnd->toDateString()
            );


        $monthlyPayments = round(
            (float) $paymentsQuery->sum('amount'),
            2
        );


        $monthlyPaymentCount = $paymentsQuery->count();


        /*
        |--------------------------------------------------------------------------
        | Current month allocations
        |--------------------------------------------------------------------------
        */

        $monthlyAllocated = round(
            (float) DB::table('payment_allocations')
                ->join(
                    'payments',
                    'payments.id',
                    '=',
                    'payment_allocations.payment_id'
                )
                ->whereDate(
                    'payments.payment_date',
                    '>=',
                    $monthStart->toDateString()
                )
                ->whereDate(
                    'payments.payment_date',
                    '<=',
                    $monthEnd->toDateString()
                )
                ->sum('payment_allocations.amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Current month advance
        |--------------------------------------------------------------------------
        */

        $monthlyAdvance = max(
            0,
            round(
                $monthlyPayments - $monthlyAllocated,
                2
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Current month expenses
        |--------------------------------------------------------------------------
        */

        $monthlyExpenses = round(
            (float) Expense::query()
                ->whereDate(
                    'expense_date',
                    '>=',
                    $monthStart->toDateString()
                )
                ->whereDate(
                    'expense_date',
                    '<=',
                    $monthEnd->toDateString()
                )
                ->sum('amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Net cash
        |--------------------------------------------------------------------------
        */

        $monthlyNetCash = round(
            $monthlyPayments - $monthlyExpenses,
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Fee status
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
        | Students with outstanding balances
        |--------------------------------------------------------------------------
        */

        $outstandingStudents = Student::query()
            ->whereHas('fees', function ($query) use (
                $monthStart,
                $monthEnd
            ) {
                $query
                    ->whereDate(
                        'period_start',
                        '>=',
                        $monthStart->toDateString()
                    )
                    ->whereDate(
                        'period_start',
                        '<=',
                        $monthEnd->toDateString()
                    )
                    ->whereRaw(
                        '(amount + late_fee) > paid_amount'
                    );
            })
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Recent payments
        |--------------------------------------------------------------------------
        */

        $recentPayments = Payment::query()
            ->with([
                'student',
                'allocations',
            ])
            ->latest('payment_date')
            ->latest('id')
            ->limit(10)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Recent expenses
        |--------------------------------------------------------------------------
        */

        $recentExpenses = Expense::query()
            ->with('bus')
            ->latest('expense_date')
            ->latest('id')
            ->limit(10)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Bus summary
        |--------------------------------------------------------------------------
        */

        $busSummary = Bus::query()
            ->withCount([
                'students',
            ])
            ->orderBy('bus_number')
            ->get()
            ->map(function ($bus) use (
                $monthStart,
                $monthEnd
            ) {

                $studentIds = Student::query()
                    ->where(
                        'bus_id',
                        $bus->id
                    )
                    ->pluck('id');


                $required = 0;

                $paid = 0;


                if ($studentIds->isNotEmpty()) {

                    $fees = Fee::query()
                        ->whereIn(
                            'student_id',
                            $studentIds
                        )
                        ->whereDate(
                            'period_start',
                            '>=',
                            $monthStart->toDateString()
                        )
                        ->whereDate(
                            'period_start',
                            '<=',
                            $monthEnd->toDateString()
                        );


                    $required = round(
                        (float) $fees->sum('amount')
                        +
                        (float) $fees->sum('late_fee'),
                        2
                    );


                    $paid = round(
                        (float) $fees->sum('paid_amount'),
                        2
                    );
                }


                return [
                    'bus' => $bus,

                    'students' =>
                        $bus->students_count,

                    'required' =>
                        $required,

                    'paid' =>
                        $paid,

                    'outstanding' =>
                        max(
                            0,
                            round(
                                $required - $paid,
                                2
                            )
                        ),
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | Render dashboard
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard',
            compact(
                'today',
                'monthStart',
                'monthEnd',
                'totalStudents',
                'activeStudents',
                'inactiveStudents',
                'totalBuses',
                'monthlyFees',
                'monthlyLateFees',
                'monthlyRequired',
                'monthlyPaid',
                'monthlyOutstanding',
                'monthlyPayments',
                'monthlyPaymentCount',
                'monthlyAllocated',
                'monthlyAdvance',
                'monthlyExpenses',
                'monthlyNetCash',
                'paidFees',
                'partialFees',
                'pendingFees',
                'carriedForwardFees',
                'outstandingStudents',
                'recentPayments',
                'recentExpenses',
                'busSummary'
            )
        );
    }
}