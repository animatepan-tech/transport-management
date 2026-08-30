@extends('layouts.app')

@section('title', 'Payments')
@section('page_heading', 'Payments')

@section('content')

{{-- =========================================================
PAGE HEADER
========================================================= --}}

<div class="page-header">


<div>

    <h1>Payments</h1>

    <p>
        View payment history and see how each payment was allocated
        against student fees.
    </p>

</div>

<div class="page-actions">

    <a href="{{ route('payments.create') }}"
       class="btn btn-primary">

        + Record Payment

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
VALIDATION ERRORS
========================================================= --}}

@if($errors->any())


<div class="alert alert-danger mb-3">

    <span>⚠</span>

    <div>

        <strong>
            Please correct the following:
        </strong>

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
PAYMENT SUMMARY
========================================================= --}}

@php


/*
|--------------------------------------------------------------------------
| Current page collection
|--------------------------------------------------------------------------
|
| PaymentController uses paginate(25), so these summary values
| represent the payments displayed on the current page.
|
*/

$collection = $payments->getCollection();


/*
|--------------------------------------------------------------------------
| Total payments
|--------------------------------------------------------------------------
*/

$totalPayments = $collection->sum(function ($payment) {

    return round(
        (float) $payment->amount,
        2
    );

});


/*
|--------------------------------------------------------------------------
| Total allocated
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Use the already-loaded payment allocations instead of
| calling $payment->allocated_amount repeatedly.
|
*/

$totalAllocated = $collection->sum(function ($payment) {

    return round(
        (float) $payment->allocations->sum('amount'),
        2
    );

});


/*
|--------------------------------------------------------------------------
| Total advance
|--------------------------------------------------------------------------
*/

$totalAdvance = $collection->sum(function ($payment) {

    $paymentAmount = round(
        (float) $payment->amount,
        2
    );

    $allocatedAmount = round(
        (float) $payment->allocations->sum('amount'),
        2
    );

    return max(
        0,
        round(
            $paymentAmount - $allocatedAmount,
            2
        )
    );

});


@endphp

<div class="stats-grid mb-3">


{{-- =====================================================
     TOTAL PAYMENTS
     ===================================================== --}}

<div class="stat-card">

    <div class="stat-label">
        Payments
    </div>

    <div class="stat-value">

        ₹{{ number_format(
            $totalPayments,
            2
        ) }}

    </div>

    <div class="stat-meta">
        Current page
    </div>

</div>


{{-- =====================================================
     ALLOCATED
     ===================================================== --}}

<div class="stat-card">

    <div class="stat-label">
        Allocated
    </div>

    <div class="stat-value amount-paid">

        ₹{{ number_format(
            $totalAllocated,
            2
        ) }}

    </div>

    <div class="stat-meta">
        Applied to generated fees
    </div>

</div>


{{-- =====================================================
     ADVANCE
     ===================================================== --}}

<div class="stat-card">

    <div class="stat-label">
        Advance
    </div>

    <div class="stat-value amount-profit">

        ₹{{ number_format(
            $totalAdvance,
            2
        ) }}

    </div>

    <div class="stat-meta">
        Unallocated payment credit
    </div>

</div>


{{-- =====================================================
     RECORD COUNT
     ===================================================== --}}

<div class="stat-card">

    <div class="stat-label">
        Records
    </div>

    <div class="stat-value">

        {{ $payments->total() }}

    </div>

    <div class="stat-meta">
        Total payment records
    </div>

</div>


</div>

{{-- =========================================================
PAYMENT INFORMATION
========================================================= --}}

<div class="alert alert-info mb-3">


<span>ⓘ</span>

<div>

    <strong>
        Automatic allocation
    </strong>

    <div style="margin-top:4px;">

        New payments are automatically applied to the student's
        oldest outstanding fee first.

        Any amount remaining after all outstanding fees are paid
        remains as advance credit.

    </div>

</div>


</div>

{{-- =========================================================
PAYMENT HISTORY
========================================================= --}}

<div class="card">


<div class="card-header">

    <div>

        <h2 class="card-title">
            Payment History
        </h2>

        <div class="text-muted"
             style="font-size:12px; margin-top:4px;">

            {{ $payments->total() }}
            payment record(s) found.

        </div>

    </div>


    <div class="page-actions">

        <a href="{{ route('payments.create') }}"
           class="btn btn-primary btn-sm">

            + Record Payment

        </a>

    </div>

</div>


<div class="table-wrapper">

    <table class="table">

        <thead>

            <tr>

                <th>
                    Date
                </th>

                <th>
                    Student
                </th>

                <th>
                    Bus
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

                <th>
                    Allocation Details
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($payments as $payment)

                @php

                    /*
                    |--------------------------------------------------------------------------
                    | Payment amount
                    |--------------------------------------------------------------------------
                    */

                    $paymentAmount = round(
                        (float) $payment->amount,
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Allocated amount
                    |--------------------------------------------------------------------------
                    |
                    | Use the already eager-loaded allocations collection.
                    |
                    */

                    $allocatedAmount = round(
                        (float) $payment->allocations->sum('amount'),
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Advance amount
                    |--------------------------------------------------------------------------
                    */

                    $advanceAmount = max(
                        0,
                        round(
                            $paymentAmount - $allocatedAmount,
                            2
                        )
                    );

                @endphp


                <tr>


                    {{-- =================================================
                         DATE
                         ================================================= --}}

                    <td>

                        @if($payment->payment_date)

                            <strong>

                                {{ $payment->payment_date->format('d-m-Y') }}

                            </strong>

                        @else

                            <span class="text-muted">
                                —
                            </span>

                        @endif

                    </td>


                    {{-- =================================================
                         STUDENT
                         ================================================= --}}

                    <td>

                        @if($payment->student)

                            <div style="font-weight:600;">

                                {{ $payment->student->student_name }}

                            </div>


                            @if($payment->student->parent_name)

                                <div class="text-muted"
                                     style="
                                        font-size:12px;
                                        margin-top:3px;
                                     ">

                                    {{ $payment->student->parent_name }}

                                </div>

                            @endif


                            @if($payment->student->whatsapp_number)

                                <div class="text-muted"
                                     style="
                                        font-size:11px;
                                        margin-top:3px;
                                     ">

                                    {{ $payment->student->whatsapp_number }}

                                </div>

                            @endif

                        @else

                            <span class="text-muted">

                                Student Deleted

                            </span>

                        @endif

                    </td>


                    {{-- =================================================
                         BUS
                         ================================================= --}}

                    <td>

                        @if($payment->student?->bus)

                            <div style="font-weight:600;">

                                {{ $payment->student->bus->bus_number }}

                            </div>


                            @if($payment->student->bus->registration_number)

                                <div class="text-muted"
                                     style="
                                        font-size:11px;
                                        margin-top:3px;
                                     ">

                                    {{ $payment->student->bus->registration_number }}

                                </div>

                            @endif

                        @else

                            <span class="badge badge-neutral">

                                No Bus

                            </span>

                        @endif

                    </td>


                    {{-- =================================================
                         PAYMENT AMOUNT
                         ================================================= --}}

                    <td>

                        <strong class="amount-paid">

                            ₹{{ number_format(
                                $paymentAmount,
                                2
                            ) }}

                        </strong>

                    </td>


                    {{-- =================================================
                         PAYMENT MODE
                         ================================================= --}}

                    <td>

                        @php

                            $modeLabels = [

                                'cash' => 'Cash',

                                'upi' => 'UPI',

                                'bank' => 'Bank Transfer',

                                'cheque' => 'Cheque',

                            ];

                        @endphp


                        <span class="badge badge-neutral">

                            {{ $modeLabels[$payment->payment_mode]
                                ?? ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $payment->payment_mode
                                    )
                                )
                            }}

                        </span>

                    </td>


                    {{-- =================================================
                         REFERENCE
                         ================================================= --}}

                    <td>

                        @if($payment->reference)

                            <span>
                                {{ $payment->reference }}
                            </span>

                        @else

                            <span class="text-muted">
                                —
                            </span>

                        @endif

                    </td>


                    {{-- =================================================
                         ALLOCATED
                         ================================================= --}}

                    <td>

                        @if($allocatedAmount > 0)

                            <strong class="amount-paid">

                                ₹{{ number_format(
                                    $allocatedAmount,
                                    2
                                ) }}

                            </strong>


                            <div class="text-muted"
                                 style="
                                    font-size:11px;
                                    margin-top:3px;
                                 ">

                                Applied to fees

                            </div>

                        @else

                            <span class="text-muted">

                                ₹0.00

                            </span>

                        @endif

                    </td>


                    {{-- =================================================
                         ADVANCE
                         ================================================= --}}

                    <td>

                        @if($advanceAmount > 0)

                            <strong class="amount-profit">

                                +₹{{ number_format(
                                    $advanceAmount,
                                    2
                                ) }}

                            </strong>


                            <div style="
                                font-size:11px;
                                margin-top:3px;
                            ">

                                <span class="amount-profit">

                                    Advance Credit

                                </span>

                            </div>

                        @else

                            <span class="text-muted">

                                ₹0.00

                            </span>

                        @endif

                    </td>


                    {{-- =================================================
                         ALLOCATION DETAILS
                         ================================================= --}}

                    <td>

                        @if($payment->allocations->count() > 0)

                            @foreach($payment->allocations as $allocation)

                                @php

                                    $fee = $allocation->fee;

                                    $allocationAmount = round(
                                        (float) $allocation->amount,
                                        2
                                    );

                                @endphp


                                @if($fee)

                                    <div style="
                                        margin-bottom:8px;
                                        padding-bottom:8px;
                                        border-bottom:1px solid #eee;
                                    ">


                                        {{-- ALLOCATED AMOUNT --}}

                                        <div style="
                                            font-weight:600;
                                        ">

                                            ₹{{ number_format(
                                                $allocationAmount,
                                                2
                                            ) }}

                                        </div>


                                        {{-- FEE PERIOD --}}

                                        <div class="text-muted"
                                             style="
                                                font-size:11px;
                                                margin-top:3px;
                                             ">

                                            Fee period:

                                            {{ $fee->period_start
                                                ? $fee->period_start->format('d M Y')
                                                : '—'
                                            }}

                                            –

                                            {{ $fee->period_end
                                                ? $fee->period_end->format('d M Y')
                                                : '—'
                                            }}

                                        </div>


                                        {{-- BILLING TYPE --}}

                                        @if($fee->billing_type)

                                            <div class="text-muted"
                                                 style="
                                                    font-size:11px;
                                                    margin-top:3px;
                                                 ">

                                                Billing:

                                                {{ ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $fee->billing_type
                                                    )
                                                ) }}

                                            </div>

                                        @endif


                                        {{-- FEE STATUS --}}

                                        <div style="
                                            font-size:11px;
                                            margin-top:4px;
                                        ">

                                            @if($fee->status === 'paid')

                                                <span class="badge badge-success">

                                                    Paid

                                                </span>


                                            @elseif($fee->status === 'partial')

                                                <span class="badge badge-warning">

                                                    Partial

                                                </span>


                                            @elseif(
                                                $fee->status ===
                                                'carried_forward'
                                            )

                                                <span class="badge badge-warning">

                                                    Carried Forward

                                                </span>


                                            @else

                                                <span class="badge badge-danger">

                                                    Pending

                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                @else

                                    <div class="text-muted"
                                         style="
                                            margin-bottom:8px;
                                            padding-bottom:8px;
                                            border-bottom:1px solid #eee;
                                         ">

                                        Allocation:

                                        ₹{{ number_format(
                                            $allocationAmount,
                                            2
                                        ) }}

                                        <div style="
                                            font-size:11px;
                                            margin-top:3px;
                                        ">

                                            Fee record unavailable

                                        </div>

                                    </div>

                                @endif

                            @endforeach


                            {{-- =================================================
                                 REMAINING ADVANCE
                                 ================================================= --}}

                            @if($advanceAmount > 0)

                                <div style="margin-top:6px;">

                                    <span class="badge badge-success">

                                        ₹{{ number_format(
                                            $advanceAmount,
                                            2
                                        ) }}

                                        remains as advance

                                    </span>

                                </div>

                            @endif


                        @else

                            {{-- =================================================
                                 PAYMENT WITH NO ALLOCATIONS
                                 ================================================= --}}

                            @if($advanceAmount > 0)

                                <span class="badge badge-success">

                                    Advance Credit

                                </span>

                            @else

                                <span class="text-muted">

                                    No allocation

                                </span>

                            @endif

                        @endif

                    </td>

                </tr>


            @empty

                {{-- =================================================
                     EMPTY STATE
                     ================================================= --}}

                <tr>

                    <td colspan="9">

                        <div class="empty-state">

                            <div class="empty-state-icon">
                                💰
                            </div>


                            <p class="empty-state-title">

                                No payments found

                            </p>


                            <p class="empty-state-text">

                                No student payments have been
                                recorded yet.

                            </p>


                            <div style="margin-top:15px;">

                                <a href="{{ route('payments.create') }}"
                                   class="btn btn-primary">

                                    + Record First Payment

                                </a>

                            </div>

                        </div>

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- =========================================================
     PAGINATION
     ========================================================= --}}

@if($payments->hasPages())

    <div class="card-footer">

        {{ $payments->links() }}

    </div>

@endif


</div>

{{-- =========================================================
PAYMENT STATUS GUIDE
========================================================= --}}

<div class="card mt-3">


<div class="card-header">

    <h2 class="card-title">
        Payment Status Guide
    </h2>

</div>


<div class="card-body">

    <div class="grid grid-3">


        {{-- =====================================================
             ALLOCATED
             ===================================================== --}}

        <div class="balance-box">

            <div class="balance-label">
                Allocated
            </div>

            <div class="balance-value amount-paid">
                Payment Applied
            </div>

            <div class="text-muted"
                 style="
                    font-size:12px;
                    margin-top:5px;
                 ">

                The payment has been applied against
                one or more outstanding student fees.

            </div>

        </div>


        {{-- =====================================================
             ADVANCE
             ===================================================== --}}

        <div class="balance-box">

            <div class="balance-label">
                Advance Credit
            </div>

            <div class="balance-value amount-profit">
                Payment Remaining
            </div>

            <div class="text-muted"
                 style="
                    font-size:12px;
                    margin-top:5px;
                 ">

                The payment exceeded the current
                outstanding fees and the remaining
                amount is held as advance credit.

            </div>

        </div>


        {{-- =====================================================
             AUTOMATIC ALLOCATION
             ===================================================== --}}

        <div class="balance-box">

            <div class="balance-label">
                Allocation Order
            </div>

            <div class="balance-value">
                Oldest First
            </div>

            <div class="text-muted"
                 style="
                    font-size:12px;
                    margin-top:5px;
                 ">

                Payments are allocated to the oldest
                outstanding fee period first.

            </div>

        </div>


    </div>

</div>


</div>

@endsection
