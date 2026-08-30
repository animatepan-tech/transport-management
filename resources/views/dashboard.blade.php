@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_heading', 'Dashboard')

@section('content')

<div class="page-header">

    <div>
        <h1>Dashboard</h1>

        <p>
            Overview of students, buses, fees, collections,
            outstanding balances and expenses.
        </p>
    </div>

</div>


{{-- =========================================================
   PERIOD
========================================================= --}}

<div class="alert alert-info mb-3">

    <span>ⓘ</span>

    <div>

        <strong>Current Month</strong>

        <div style="margin-top:4px;">
            {{ $monthStart->format('d M Y') }}
            —
            {{ $monthEnd->format('d M Y') }}
        </div>

    </div>

</div>


{{-- =========================================================
   PRIMARY SUMMARY
========================================================= --}}

<div class="stats-grid mb-3">

    <div class="stat-card">

        <div class="stat-label">
            Students
        </div>

        <div class="stat-value">
            {{ $totalStudents }}
        </div>

        <div class="stat-meta">
            {{ $activeStudents }} active
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Buses
        </div>

        <div class="stat-value">
            {{ $totalBuses }}
        </div>

        <div class="stat-meta">
            Registered buses
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Monthly Collection
        </div>

        <div class="stat-value amount-paid">
            ₹{{ number_format($monthlyPayments, 2) }}
        </div>

        <div class="stat-meta">
            {{ $monthlyPaymentCount }} payment record(s)
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Monthly Outstanding
        </div>

        <div class="stat-value amount-due">
            ₹{{ number_format($monthlyOutstanding, 2) }}
        </div>

        <div class="stat-meta">
            Fees still due
        </div>

    </div>

</div>


{{-- =========================================================
   FINANCIAL SUMMARY
========================================================= --}}

<div class="stats-grid mb-3">

    <div class="stat-card">

        <div class="stat-label">
            Fees Required
        </div>

        <div class="stat-value">
            ₹{{ number_format($monthlyRequired, 2) }}
        </div>

        <div class="stat-meta">
            Includes late fees
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Allocated
        </div>

        <div class="stat-value amount-paid">
            ₹{{ number_format($monthlyAllocated, 2) }}
        </div>

        <div class="stat-meta">
            Applied to fees
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Advance Credit
        </div>

        <div class="stat-value amount-profit">
            ₹{{ number_format($monthlyAdvance, 2) }}
        </div>

        <div class="stat-meta">
            Unallocated payments
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Expenses
        </div>

        <div class="stat-value amount-due">
            ₹{{ number_format($monthlyExpenses, 2) }}
        </div>

        <div class="stat-meta">
            Current month
        </div>

    </div>

</div>


{{-- =========================================================
   NET CASH
========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Cash Position
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Payments received minus expenses during the
                current month.

            </div>

        </div>

    </div>


    <div class="card-body">

        <div class="balance-box">

            <div class="balance-label">
                Net Cash
            </div>

            <div class="balance-value
                {{ $monthlyNetCash >= 0
                    ? 'amount-profit'
                    : 'amount-due'
                }}">

                ₹{{ number_format($monthlyNetCash, 2) }}

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
   FEE STATUS
========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Fee Status
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Current-month fee records by payment status.

            </div>

        </div>

    </div>


    <div class="card-body">

        <div class="grid grid-4">

            <div class="balance-box">

                <div class="balance-label">
                    Paid
                </div>

                <div class="balance-value amount-paid">
                    {{ $paidFees }}
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">
                    Fully paid
                </div>

            </div>


            <div class="balance-box">

                <div class="balance-label">
                    Partial
                </div>

                <div class="balance-value">
                    {{ $partialFees }}
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">
                    Partially paid
                </div>

            </div>


            <div class="balance-box">

                <div class="balance-label">
                    Pending
                </div>

                <div class="balance-value amount-due">
                    {{ $pendingFees }}
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">
                    No payment applied
                </div>

            </div>


            <div class="balance-box">

                <div class="balance-label">
                    Outstanding Students
                </div>

                <div class="balance-value amount-due">
                    {{ $outstandingStudents }}
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">
                    Students with due amounts
                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
   BUS SUMMARY
========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Bus Summary
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Current-month fee position by bus.

            </div>

        </div>

    </div>


    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>Bus</th>
                    <th>Students</th>
                    <th>Required</th>
                    <th>Paid</th>
                    <th>Outstanding</th>

                </tr>

            </thead>


            <tbody>

                @forelse($busSummary as $row)

                    <tr>

                        <td>

                            <strong>
                                {{ $row['bus']->bus_number }}
                            </strong>

                            @if($row['bus']->registration_number)

                                <div class="text-muted"
                                     style="font-size:11px; margin-top:3px;">

                                    {{ $row['bus']->registration_number }}

                                </div>

                            @endif

                        </td>


                        <td>
                            {{ $row['students'] }}
                        </td>


                        <td>
                            ₹{{ number_format($row['required'], 2) }}
                        </td>


                        <td>

                            <strong class="amount-paid">
                                ₹{{ number_format($row['paid'], 2) }}
                            </strong>

                        </td>


                        <td>

                            @if($row['outstanding'] > 0)

                                <strong class="amount-due">
                                    ₹{{ number_format($row['outstanding'], 2) }}
                                </strong>

                            @else

                                <span class="text-muted">
                                    ₹0.00
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5">

                            <div class="empty-state">

                                <div class="empty-state-icon">
                                    🚌
                                </div>

                                <p class="empty-state-title">
                                    No buses found
                                </p>

                                <p class="empty-state-text">
                                    No bus records are available.
                                </p>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- =========================================================
   RECENT PAYMENTS
========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Recent Payments
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Latest payment records.

            </div>

        </div>

    </div>


    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>Date</th>
                    <th>Student</th>
                    <th>Mode</th>
                    <th>Amount</th>
                    <th>Allocated</th>
                    <th>Advance</th>

                </tr>

            </thead>


            <tbody>

                @forelse($recentPayments as $payment)

                    @php

                        $allocated = round(
                            (float) $payment->allocations->sum('amount'),
                            2
                        );

                        $advance = max(
                            0,
                            round(
                                (float) $payment->amount - $allocated,
                                2
                            )
                        );

                    @endphp


                    <tr>

                        <td>

                            {{ $payment->payment_date
                                ? $payment->payment_date->format('d M Y')
                                : '—'
                            }}

                        </td>


                        <td>

                            @if($payment->student)

                                <strong>
                                    {{ $payment->student->student_name }}
                                </strong>

                            @else

                                <span class="text-muted">
                                    Unknown Student
                                </span>

                            @endif

                        </td>


                        <td>

                            <span class="badge badge-neutral">

                                {{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $payment->payment_mode
                                    )
                                ) }}

                            </span>

                        </td>


                        <td>

                            <strong class="amount-paid">
                                ₹{{ number_format($payment->amount, 2) }}
                            </strong>

                        </td>


                        <td>
                            ₹{{ number_format($allocated, 2) }}
                        </td>


                        <td>

                            @if($advance > 0)

                                <strong class="amount-profit">
                                    ₹{{ number_format($advance, 2) }}
                                </strong>

                            @else

                                <span class="text-muted">
                                    ₹0.00
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <span class="text-muted">
                                No payments found.
                            </span>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- =========================================================
   RECENT EXPENSES
========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Recent Expenses
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Latest recorded expenses.

            </div>

        </div>

    </div>


    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>Date</th>
                    <th>Category</th>
                    <th>Bus</th>
                    <th>Vendor</th>
                    <th>Amount</th>

                </tr>

            </thead>


            <tbody>

                @forelse($recentExpenses as $expense)

                    <tr>

                        <td>

                            {{ $expense->expense_date
                                ? $expense->expense_date->format('d M Y')
                                : '—'
                            }}

                        </td>


                        <td>

                            <strong>
                                {{ $expense->category }}
                            </strong>

                        </td>


                        <td>

                            @if($expense->bus)

                                {{ $expense->bus->bus_number }}

                            @else

                                <span class="text-muted">
                                    General
                                </span>

                            @endif

                        </td>


                        <td>
                            {{ $expense->vendor ?: '—' }}
                        </td>


                        <td>

                            <strong>
                                ₹{{ number_format($expense->amount, 2) }}
                            </strong>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5">

                            <span class="text-muted">
                                No expenses found.
                            </span>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- =========================================================
   DASHBOARD INFORMATION
========================================================= --}}

<div class="card">

    <div class="card-header">

        <h2 class="card-title">
            Dashboard Information
        </h2>

    </div>


    <div class="card-body">

        <div class="grid grid-3">

            <div class="balance-box">

                <div class="balance-label">
                    Payment Accounting
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">

                    Payments are recorded independently from
                    fee allocations. One payment may cover
                    multiple fee records.

                </div>

            </div>


            <div class="balance-box">

                <div class="balance-label">
                    Advance Credit
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">

                    Advance is the portion of a payment that
                    remains unallocated after outstanding fees
                    have been covered.

                </div>

            </div>


            <div class="balance-box">

                <div class="balance-label">
                    Current Date
                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">

                    {{ $today->format('d M Y') }}

                </div>

            </div>

        </div>

    </div>

</div>

@endsection