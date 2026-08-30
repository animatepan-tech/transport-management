@extends('layouts.app')

@section('title', 'Expenses')
@section('page_heading', 'Expenses')

@section('content')

<div class="page-header">

    <div>

        <h1>Expenses</h1>

        <p>
            Track transport-related operating expenses.
        </p>

    </div>


    <div class="page-actions">

        <a href="{{ route('expenses.create') }}"
           class="btn btn-primary">

            + Add Expense

        </a>

    </div>

</div>


{{-- =========================================================
     SUCCESS MESSAGE
     ========================================================= --}}

@if(session('success'))

    <div class="alert alert-success mb-3">

        <span>✓</span>

        <div>

            {{ session('success') }}

        </div>

    </div>

@endif


{{-- =========================================================
     ERROR MESSAGE
     ========================================================= --}}

@if(session('error'))

    <div class="alert alert-danger mb-3">

        <span>⚠</span>

        <div>

            {{ session('error') }}

        </div>

    </div>

@endif


{{-- =========================================================
     SUMMARY
     ========================================================= --}}

<div class="grid grid-4 mb-3">


    {{-- TOTAL EXPENSES --}}

    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Total Expenses
            </div>

            <div class="balance-value amount-due">

                ₹{{ number_format(
                    $totalExpenses,
                    2
                ) }}

            </div>

        </div>

    </div>


    {{-- THIS MONTH --}}

    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                This Month
            </div>

            <div class="balance-value">

                ₹{{ number_format(
                    $monthlyExpenses,
                    2
                ) }}

            </div>

        </div>

    </div>


    {{-- TODAY --}}

    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Today
            </div>

            <div class="balance-value">

                ₹{{ number_format(
                    $todayExpenses,
                    2
                ) }}

            </div>

        </div>

    </div>


    {{-- RECORD COUNT --}}

    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Expense Records
            </div>

            <div class="balance-value">

                {{ number_format($expenseCount) }}

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     EXPENSE HISTORY
     ========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Expense History
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Latest expenses are displayed first.

            </div>

        </div>

    </div>


    @if($expenses->count() > 0)

        <div class="table-wrapper">

            <table class="table">

                <thead>

                    <tr>

                        <th>
                            Date
                        </th>

                        <th>
                            Bus
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Vendor
                        </th>

                        <th>
                            Description
                        </th>

                        <th class="amount">
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($expenses as $expense)

                        <tr>

                            {{-- DATE --}}

                            <td>

                                <strong>

                                    {{ $expense->expense_date
                                        ? $expense->expense_date->format('d M Y')
                                        : '—'
                                    }}

                                </strong>

                            </td>


                            {{-- BUS --}}

                            <td>

                                @if($expense->bus)

                                    <strong>
                                        {{ $expense->bus->bus_number }}
                                    </strong>

                                    @if($expense->bus->registration_number)

                                        <div class="text-muted"
                                             style="font-size:12px; margin-top:3px;">

                                            {{ $expense->bus->registration_number }}

                                        </div>

                                    @endif

                                @else

                                    <span class="text-muted">
                                        General
                                    </span>

                                @endif

                            </td>


                            {{-- CATEGORY --}}

                            <td>

                                <span class="badge badge-info">

                                    {{ $expense->category }}

                                </span>

                            </td>


                            {{-- VENDOR --}}

                            <td>

                                @if($expense->vendor)

                                    {{ $expense->vendor }}

                                @else

                                    <span class="text-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- DESCRIPTION --}}

                            <td>

                                @if($expense->description)

                                    {{ $expense->description }}

                                @else

                                    <span class="text-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- AMOUNT --}}

                            <td class="amount amount-due">

                                <strong>

                                    ₹{{ number_format(
                                        (float) $expense->amount,
                                        2
                                    ) }}

                                </strong>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        {{-- =================================================
             PAGINATION
             ================================================= --}}

        <div style="padding:16px;">

            {{ $expenses->links() }}

        </div>

    @else

        <div class="card-body">

            <div class="alert alert-info">

                <span>ⓘ</span>

                <div>

                    <strong>
                        No expenses recorded.
                    </strong>

                    Add your first transport expense to begin
                    tracking operating costs.

                </div>

            </div>


            <a href="{{ route('expenses.create') }}"
               class="btn btn-primary">

                + Add Expense

            </a>

        </div>

    @endif

</div>

@endsection