@extends('layouts.app')

@section('title', 'Reports')
@section('page_heading', 'Reports')

@section('content')

<div class="page-header">


<div>

    <h1>Reports</h1>

    <p>
        View fee collection, outstanding balances,
        advance payments, expenses and bus-wise financial performance.
    </p>

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
VALIDATION ERRORS
========================================================= --}}

@if($errors->any())


<div class="alert alert-danger mb-3">

    <span>⚠</span>

    <div>

        <strong>Please correct the following:</strong>

        <ul style="margin:6px 0 0 18px;">

            @foreach($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

    </div>

</div>


@endif

{{-- =========================================================
REPORT FILTERS
========================================================= --}}

<div class="card mb-3">


<div class="card-header">

    <div>

        <h2 class="card-title">
            Report Filters
        </h2>

        <div class="text-muted"
             style="font-size:12px; margin-top:4px;">

            Select a date range and optionally filter the report
            by bus.

        </div>

    </div>

</div>


<div class="card-body">

    <form method="GET"
          action="{{ route('reports') }}">

        <div class="grid grid-3">

            {{-- FROM DATE --}}

            <div>

                <label for="from">
                    From Date
                </label>

                <input
                    type="date"
                    id="from"
                    name="from"
                    value="{{ $from->format('Y-m-d') }}"
                    class="form-control"
                >

            </div>


            {{-- TO DATE --}}

            <div>

                <label for="to">
                    To Date
                </label>

                <input
                    type="date"
                    id="to"
                    name="to"
                    value="{{ $to->format('Y-m-d') }}"
                    class="form-control"
                >

            </div>


            {{-- BUS --}}

            <div>

                <label for="bus_id">
                    Bus
                </label>

                <select
                    id="bus_id"
                    name="bus_id"
                    class="form-control"
                >

                    <option value="">
                        All Buses
                    </option>

                    @foreach($buses as $bus)

                        <option
                            value="{{ $bus->id }}"
                            @selected((string) $busId === (string) $bus->id)
                        >

                            {{ $bus->bus_number }}

                            @if($bus->registration_number)
                                — {{ $bus->registration_number }}
                            @endif

                        </option>

                    @endforeach

                </select>

            </div>

        </div>


        <div style="
            display:flex;
            gap:10px;
            margin-top:15px;
            flex-wrap:wrap;
        ">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Apply Filters
            </button>


            <a
                href="{{ route('reports') }}"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </div>

    </form>

</div>


</div>

{{-- =========================================================
REPORT PERIOD
========================================================= --}}

<div class="alert alert-info mb-3">


<span>ⓘ</span>

<div>

    <strong>Report Period</strong>

    <div style="margin-top:4px;">

        {{ $from->format('d M Y') }}
        —
        {{ $to->format('d M Y') }}

        @if($busId)

            @php
                $selectedBus = $buses->firstWhere('id', $busId);
            @endphp

            @if($selectedBus)

                <span style="margin-left:8px;">

                    • Bus:

                    <strong>
                        {{ $selectedBus->bus_number }}
                    </strong>

                </span>

            @endif

        @endif

    </div>

</div>


</div>

{{-- =========================================================
SUMMARY
========================================================= --}}

<div class="stats-grid mb-3">


{{-- PAYMENTS RECEIVED --}}

<div class="stat-card">

    <div class="stat-label">
        Payments Received
    </div>

    <div class="stat-value amount-paid">

        ₹{{ number_format(
            (float) ($totalPayments ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">

        {{ $paymentCount ?? 0 }}
        payment record(s)

    </div>

</div>


{{-- FEES GENERATED --}}

<div class="stat-card">

    <div class="stat-label">
        Fees Generated
    </div>

    <div class="stat-value">

        ₹{{ number_format(
            (float) ($totalRequired ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">

        {{ $feeRecordCount ?? 0 }}
        fee record(s)

    </div>

</div>


{{-- OUTSTANDING --}}

<div class="stat-card">

    <div class="stat-label">
        Outstanding
    </div>

    <div class="stat-value amount-due">

        ₹{{ number_format(
            (float) ($totalOutstanding ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">
        Fees still due
    </div>

</div>


{{-- ADVANCE --}}

<div class="stat-card">

    <div class="stat-label">
        Advance Credit
    </div>

    <div class="stat-value amount-profit">

        ₹{{ number_format(
            (float) ($totalAdvance ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">
        Unallocated payments
    </div>

</div>


</div>

{{-- =========================================================
FINANCIAL SUMMARY
========================================================= --}}

<div class="stats-grid mb-3">


{{-- ALLOCATED --}}

<div class="stat-card">

    <div class="stat-label">
        Allocated to Fees
    </div>

    <div class="stat-value amount-paid">

        ₹{{ number_format(
            (float) ($totalPaymentAllocated ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">
        Payment allocation total
    </div>

</div>


{{-- EXPENSES --}}

<div class="stat-card">

    <div class="stat-label">
        Expenses
    </div>

    <div class="stat-value amount-due">

        ₹{{ number_format(
            (float) ($totalExpenses ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">

        {{ $expenseCount ?? 0 }}
        expense record(s)

    </div>

</div>


{{-- NET CASH --}}

<div class="stat-card">

    <div class="stat-label">
        Net Cash
    </div>

    <div class="stat-value
        {{ ($netCash ?? 0) >= 0
            ? 'amount-profit'
            : 'amount-due'
        }}">

        ₹{{ number_format(
            (float) ($netCash ?? 0),
            2
        ) }}

    </div>

    <div class="stat-meta">

        Payments received minus expenses

    </div>

</div>


{{-- STUDENTS --}}

<div class="stat-card">

    <div class="stat-label">
        Students
    </div>

    <div class="stat-value">

        {{ $totalStudents ?? 0 }}

    </div>

    <div class="stat-meta">

        {{ $activeStudents ?? 0 }}
        active student(s)

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

            Breakdown of generated fee records.

        </div>

    </div>

</div>


<div class="card-body">

    <div class="grid grid-4">


        {{-- PAID --}}

        <div class="balance-box">

            <div class="balance-label">
                Paid
            </div>

            <div class="balance-value amount-paid">

                {{ $paidFees ?? 0 }}

            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                Fully paid fees

            </div>

        </div>


        {{-- PARTIAL --}}

        <div class="balance-box">

            <div class="balance-label">
                Partial
            </div>

            <div class="balance-value">

                {{ $partialFees ?? 0 }}

            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                Partially paid fees

            </div>

        </div>


        {{-- PENDING --}}

        <div class="balance-box">

            <div class="balance-label">
                Pending
            </div>

            <div class="balance-value amount-due">

                {{ $pendingFees ?? 0 }}

            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                No payment applied

            </div>

        </div>


        {{-- CARRIED FORWARD --}}

        <div class="balance-box">

            <div class="balance-label">
                Carried Forward
            </div>

            <div class="balance-value">

                {{ $carriedForwardFees ?? 0 }}

            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                Unpaid fees carried from an earlier period

            </div>

        </div>


    </div>

</div>


</div>

{{-- =========================================================
BUS-WISE REPORT
========================================================= --}}

<div class="card mb-3">


<div class="card-header">

    <div>

        <h2 class="card-title">
            Bus-wise Financial Report
        </h2>

        <div class="text-muted"
             style="font-size:12px; margin-top:4px;">

            Fees, collections and expenses for each bus.

        </div>

    </div>

</div>


<div class="table-wrapper">

    <table class="table">

        <thead>

            <tr>

                <th>Bus</th>
                <th>Students</th>
                <th>Fees</th>
                <th>Late Fees</th>
                <th>Required</th>
                <th>Allocated</th>
                <th>Outstanding</th>
                <th>Payments</th>
                <th>Expenses</th>
                <th>Net</th>

            </tr>

        </thead>


        <tbody>

            @forelse($busReports ?? [] as $report)

                <tr>

                    {{-- BUS --}}

                    <td>

                        <strong>
                            {{ $report['bus']->bus_number }}
                        </strong>

                        @if($report['bus']->registration_number)

                            <div class="text-muted"
                                 style="font-size:11px; margin-top:3px;">

                                {{ $report['bus']->registration_number }}

                            </div>

                        @endif

                    </td>


                    {{-- STUDENTS --}}

                    <td>
                        {{ $report['students'] }}
                    </td>


                    {{-- FEES --}}

                    <td>

                        ₹{{ number_format(
                            (float) $report['fees'],
                            2
                        ) }}

                    </td>


                    {{-- LATE FEES --}}

                    <td>

                        ₹{{ number_format(
                            (float) $report['late_fees'],
                            2
                        ) }}

                    </td>


                    {{-- REQUIRED --}}

                    <td>

                        <strong>

                            ₹{{ number_format(
                                (float) $report['required'],
                                2
                            ) }}

                        </strong>

                    </td>


                    {{-- ALLOCATED --}}

                    <td>

                        <strong class="amount-paid">

                            ₹{{ number_format(
                                (float) $report['allocated'],
                                2
                            ) }}

                        </strong>

                    </td>


                    {{-- OUTSTANDING --}}

                    <td>

                        @if((float) $report['outstanding'] > 0)

                            <strong class="amount-due">

                                ₹{{ number_format(
                                    (float) $report['outstanding'],
                                    2
                                ) }}

                            </strong>

                        @else

                            <span class="text-muted">
                                ₹0.00
                            </span>

                        @endif

                    </td>


                    {{-- PAYMENTS --}}

                    <td>

                        <strong class="amount-paid">

                            ₹{{ number_format(
                                (float) $report['payments'],
                                2
                            ) }}

                        </strong>

                    </td>


                    {{-- EXPENSES --}}

                    <td>

                        <strong>

                            ₹{{ number_format(
                                (float) $report['expenses'],
                                2
                            ) }}

                        </strong>

                    </td>


                    {{-- NET --}}

                    <td>

                        <strong
                            class="{{ (float) $report['net'] >= 0
                                ? 'amount-profit'
                                : 'amount-due'
                            }}"
                        >

                            ₹{{ number_format(
                                (float) $report['net'],
                                2
                            ) }}

                        </strong>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="10">

                        <div class="empty-state">

                            <div class="empty-state-icon">
                                🚌
                            </div>

                            <p class="empty-state-title">
                                No bus data found
                            </p>

                            <p class="empty-state-text">
                                No buses match the selected filter.
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
PAYMENT MODES + EXPENSE CATEGORIES
========================================================= --}}

<div class="grid grid-2 mb-3">


{{-- PAYMENT MODES --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Payment Modes
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Collection by payment method.

            </div>

        </div>

    </div>


    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>Mode</th>
                    <th>Records</th>
                    <th>Amount</th>

                </tr>

            </thead>


            <tbody>

                @forelse($paymentModes ?? [] as $mode)

                    @php

                        $modeLabels = [
                            'cash' => 'Cash',
                            'upi' => 'UPI',
                            'bank' => 'Bank',
                            'cheque' => 'Cheque',
                        ];

                        $label =
                            $modeLabels[$mode->payment_mode]
                            ?? ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $mode->payment_mode
                                )
                            );

                    @endphp


                    <tr>

                        <td>

                            <span class="badge badge-neutral">
                                {{ $label }}
                            </span>

                        </td>

                        <td>
                            {{ $mode->count }}
                        </td>

                        <td>

                            <strong class="amount-paid">

                                ₹{{ number_format(
                                    (float) $mode->total,
                                    2
                                ) }}

                            </strong>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3">

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


{{-- EXPENSE CATEGORIES --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Expense Categories
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Expenses grouped by category.

            </div>

        </div>

    </div>


    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>Category</th>
                    <th>Records</th>
                    <th>Amount</th>

                </tr>

            </thead>


            <tbody>

                @forelse($expenseCategories ?? [] as $category)

                    <tr>

                        <td>

                            <strong>
                                {{ $category->category }}
                            </strong>

                        </td>

                        <td>
                            {{ $category->count }}
                        </td>

                        <td>

                            <strong>

                                ₹{{ number_format(
                                    (float) $category->total,
                                    2
                                ) }}

                            </strong>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3">

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


</div>

{{-- =========================================================
OUTSTANDING STUDENTS
========================================================= --}}

<div class="card mb-3">


<div class="card-header">

    <div>

        <h2 class="card-title">
            Outstanding Student Accounts
        </h2>

        <div class="text-muted"
             style="font-size:12px; margin-top:4px;">

            Students with unpaid fee amounts during the
            selected report period.

        </div>

    </div>

</div>


<div class="table-wrapper">

    <table class="table">

        <thead>

            <tr>

                <th>Student</th>
                <th>Bus</th>
                <th>Required</th>
                <th>Allocated</th>
                <th>Outstanding</th>

            </tr>

        </thead>


        <tbody>

            @forelse($students ?? [] as $row)

                @php
                    $student = $row['student'];
                @endphp


                <tr>

                    {{-- STUDENT --}}

                    <td>

                        <strong>
                            {{ $student->student_name }}
                        </strong>

                        @if($student->parent_name)

                            <div class="text-muted"
                                 style="font-size:11px; margin-top:3px;">

                                {{ $student->parent_name }}

                            </div>

                        @endif

                    </td>


                    {{-- BUS --}}

                    <td>

                        @if($student->bus)

                            <strong>
                                {{ $student->bus->bus_number }}
                            </strong>

                        @else

                            <span class="badge badge-neutral">
                                No Bus
                            </span>

                        @endif

                    </td>


                    {{-- REQUIRED --}}

                    <td>

                        ₹{{ number_format(
                            (float) $row['required'],
                            2
                        ) }}

                    </td>


                    {{-- ALLOCATED --}}

                    <td>

                        <strong class="amount-paid">

                            ₹{{ number_format(
                                (float) $row['paid'],
                                2
                            ) }}

                        </strong>

                    </td>


                    {{-- OUTSTANDING --}}

                    <td>

                        <strong class="amount-due">

                            ₹{{ number_format(
                                (float) $row['due'],
                                2
                            ) }}

                        </strong>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="5">

                        <div class="empty-state">

                            <div class="empty-state-icon">
                                ✓
                            </div>

                            <p class="empty-state-title">
                                No Outstanding Students
                            </p>

                            <p class="empty-state-text">

                                All student accounts are settled
                                for the selected report period.

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
REPORT NOTES
========================================================= --}}

<div class="card">


<div class="card-header">

    <h2 class="card-title">
        Report Information
    </h2>

</div>


<div class="card-body">

    <div class="grid grid-3">


        {{-- PAYMENT ACCOUNTING --}}

        <div class="balance-box">

            <div class="balance-label">
                Payment Accounting
            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                Payments are recorded independently from
                fee allocations. One payment may be allocated
                across multiple fee periods.

            </div>

        </div>


        {{-- ADVANCE CREDIT --}}

        <div class="balance-box">

            <div class="balance-label">
                Advance Credit
            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                Advance is the portion of recorded payments
                that has not yet been allocated to a fee.

            </div>

        </div>


        {{-- NET CASH --}}

        <div class="balance-box">

            <div class="balance-label">
                Net Cash
            </div>

            <div class="text-muted"
                 style="font-size:12px; margin-top:5px;">

                Net cash is calculated as payments received
                during the selected period minus recorded
                expenses during the same period.

            </div>

        </div>


    </div>

</div>


</div>

@endsection
