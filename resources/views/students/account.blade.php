@extends('layouts.app')

@section('title', 'Student Account')

@section('page_heading', 'Student Account')

@section('content')

<div class="page-header">

    <div>

        <h1>
            {{ $student->student_name }}
        </h1>

        <p>
            Student account and payment history
        </p>

    </div>

    <div class="page-actions">

        <a
            href="{{ route('students.index') }}"
            class="btn btn-secondary"
        >
            ← Students
        </a>

        <a
            href="{{ route('payments.create') }}"
            class="btn btn-primary"
        >
            + Record Payment
        </a>

    </div>

</div>


{{-- =========================================================
     STUDENT INFORMATION
     ========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <h2 class="card-title">
            Student Information
        </h2>

    </div>

    <div class="card-body">

        <div class="grid grid-3">

            <div>

                <div class="balance-label">
                    Student
                </div>

                <strong>
                    {{ $student->student_name }}
                </strong>

            </div>

            <div>

                <div class="balance-label">
                    Parent
                </div>

                <strong>
                    {{ $student->parent_name ?: '—' }}
                </strong>

            </div>

            <div>

                <div class="balance-label">
                    Bus
                </div>

                <strong>
                    {{ $student->bus->bus_number ?? '—' }}
                </strong>

            </div>

            <div>

                <div class="balance-label">
                    Pickup Stop
                </div>

                <strong>
                    {{ $student->pickup_stop ?: '—' }}
                </strong>

            </div>

            <div>

                <div class="balance-label">
                    Monthly Fee
                </div>

                <strong>
                    ₹{{ number_format($student->monthly_fee, 2) }}
                </strong>

            </div>

            <div>

                <div class="balance-label">
                    WhatsApp
                </div>

                <strong>
                    {{ $student->whatsapp_number ?: '—' }}
                </strong>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     ACCOUNT BALANCE
     ========================================================= --}}

<div class="stats-grid mb-3">

    <div class="stat-card">

        <div class="stat-label">
            Current Balance
        </div>

        @if($student->current_balance < 0)

            <div class="stat-value amount-loss">

                -₹{{ number_format(abs($student->current_balance), 2) }}

            </div>

            <div class="stat-meta">
                Amount due

            </div>

        @elseif($student->current_balance > 0)

            <div class="stat-value amount-paid">

                +₹{{ number_format($student->current_balance, 2) }}

            </div>

            <div class="stat-meta">
                Advance available
            </div>

        @else

            <div class="stat-value amount-profit">

                ₹0.00

            </div>

            <div class="stat-meta">
                Account fully settled
            </div>

        @endif

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Total Fees
        </div>

        <div class="stat-value">

            ₹{{ number_format(
                $student->total_fees +
                $student->total_late_fees,
                2
            ) }}

        </div>

        <div class="stat-meta">
            Including late fees
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Total Payments
        </div>

        <div class="stat-value amount-paid">

            ₹{{ number_format(
                $student->total_paid,
                2
            ) }}

        </div>

        <div class="stat-meta">
            Payments received
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Advance Coverage
        </div>

        <div class="stat-value">

            {{ number_format(
                $student->advance_months,
                2
            ) }}

            <span style="font-size:14px;">
                months
            </span>

        </div>

        <div class="stat-meta">
            Based on monthly fee
        </div>

    </div>

</div>


{{-- =========================================================
     ACCOUNT STATUS
     ========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <h2 class="card-title">
            Account Status
        </h2>

    </div>

    <div class="card-body">

        @if($student->has_due)

            <div class="alert alert-danger">

                <strong>
                    Payment Required
                </strong>

                <div>
                    ₹{{ number_format(
                        $student->due_amount,
                        2
                    ) }}
                    is currently outstanding.
                </div>

            </div>

        @elseif($student->has_advance)

            <div class="alert alert-success">

                <strong>
                    Advance Available
                </strong>

                <div>
                    ₹{{ number_format(
                        $student->advance_amount,
                        2
                    ) }}
                    is available as advance credit.
                </div>

            </div>

        @else

            <div class="alert alert-success">

                <strong>
                    Account Fully Settled
                </strong>

                <div>
                    No outstanding amount.
                </div>

            </div>

        @endif

    </div>

</div>


{{-- =========================================================
     FEE HISTORY
     ========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <h2 class="card-title">
            Fee History
        </h2>

    </div>

    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>
                        Period
                    </th>

                    <th>
                        Billing
                    </th>

                    <th>
                        Fee
                    </th>

                    <th>
                        Paid
                    </th>

                    <th>
                        Outstanding
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($student->fees as $fee)

                    <tr>

                        <td>

                            {{ $fee->period_start->format('d M Y') }}

                            <br>

                            <span class="text-muted">

                                to

                                {{ $fee->period_end->format('d M Y') }}

                            </span>

                        </td>


                        <td>

                            {{ ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $fee->billing_type
                                )
                            ) }}

                        </td>


                        <td class="amount">

                            ₹{{ number_format(
                                $fee->amount,
                                2
                            ) }}

                        </td>


                        <td class="amount amount-paid">

                            ₹{{ number_format(
                                $fee->paid_amount,
                                2
                            ) }}

                        </td>


                        <td class="amount">

                            @if($fee->outstanding_amount > 0)

                                <span class="amount-due">

                                    ₹{{ number_format(
                                        $fee->outstanding_amount,
                                        2
                                    ) }}

                                </span>

                            @else

                                <span class="amount-profit">
                                    ₹0.00
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($fee->status === 'paid')

                                <span class="badge badge-success">
                                    Paid
                                </span>

                            @elseif($fee->status === 'partial')

                                <span class="badge badge-warning">
                                    Partial
                                </span>

                            @else

                                <span class="badge badge-danger">
                                    Pending
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <p class="empty-state-title">
                                    No fees generated
                                </p>

                                <p class="empty-state-text">
                                    Fee records will appear here after fees are generated.
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
     PAYMENT HISTORY
     ========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <h2 class="card-title">
            Payment History
        </h2>

    </div>

    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>
                        Date
                    </th>

                    <th>
                        Amount
                    </th>

                    <th>
                        Mode
                    </th>

                    <th>
                        Reference
                    </th>

                    <th>
                        Allocated
                    </th>

                    <th>
                        Advance
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($student->payments as $payment)

                    <tr>

                        <td>

                            {{ $payment->payment_date
                                ->format('d M Y') }}

                        </td>


                        <td class="amount amount-paid">

                            ₹{{ number_format(
                                $payment->amount,
                                2
                            ) }}

                        </td>


                        <td>

                            {{ strtoupper(
                                $payment->payment_mode
                            ) }}

                        </td>


                        <td>

                            {{ $payment->reference ?: '—' }}

                        </td>


                        <td>

                            ₹{{ number_format(
                                $payment->allocated_amount,
                                2
                            ) }}

                        </td>


                        <td>

                            @if($payment->advance_amount > 0)

                                <span class="amount-paid">

                                    +₹{{ number_format(
                                        $payment->advance_amount,
                                        2
                                    ) }}

                                </span>

                            @else

                                ₹0.00

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <p class="empty-state-title">
                                    No payments recorded
                                </p>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


@endsection