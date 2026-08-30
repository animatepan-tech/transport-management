<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display expense history.
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Expense history
        |--------------------------------------------------------------------------
        */

        $expenses = Expense::query()
            ->with('bus')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(25);


        /*
        |--------------------------------------------------------------------------
        | Total expenses
        |--------------------------------------------------------------------------
        */

        $totalExpenses = round(
            (float) Expense::query()->sum('amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Current month expenses
        |--------------------------------------------------------------------------
        */

        $monthStart = now()
            ->startOfMonth()
            ->toDateString();

        $monthEnd = now()
            ->endOfMonth()
            ->toDateString();


        $monthlyExpenses = round(
            (float) Expense::query()
                ->whereDate(
                    'expense_date',
                    '>=',
                    $monthStart
                )
                ->whereDate(
                    'expense_date',
                    '<=',
                    $monthEnd
                )
                ->sum('amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Today's expenses
        |--------------------------------------------------------------------------
        */

        $todayExpenses = round(
            (float) Expense::query()
                ->whereDate(
                    'expense_date',
                    now()->toDateString()
                )
                ->sum('amount'),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Number of expense records
        |--------------------------------------------------------------------------
        */

        $expenseCount = Expense::query()->count();


        return view(
            'expenses.index',
            compact(
                'expenses',
                'totalExpenses',
                'monthlyExpenses',
                'todayExpenses',
                'expenseCount'
            )
        );
    }


    /**
     * Show the expense creation form.
     */
    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | Active buses
        |--------------------------------------------------------------------------
        |
        | Only active buses are offered for new expenses.
        | General expenses can still be recorded by leaving
        | the bus field empty.
        |
        */

        $buses = Bus::query()
            ->where('active', true)
            ->orderBy('bus_number')
            ->get();


        return view(
            'expenses.form',
            compact('buses')
        );
    }


    /**
     * Store a new expense.
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'bus_id' => [
                'nullable',
                'integer',
                'exists:buses,id',
            ],

            'expense_date' => [
                'required',
                'date',
            ],

            'category' => [
                'required',
                'string',
                'max:100',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'vendor' => [
                'nullable',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Normalize amount
        |--------------------------------------------------------------------------
        */

        $data['amount'] = round(
            (float) $data['amount'],
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Create expense
        |--------------------------------------------------------------------------
        */

        Expense::create($data);


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense recorded successfully.'
            );
    }
}