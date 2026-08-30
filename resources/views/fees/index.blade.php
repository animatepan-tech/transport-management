@extends('layouts.app')

@section('title', 'Fees')
@section('page_heading', 'Fees')

@section('content')

    {{-- =========================================================
         PAGE HEADER
         ========================================================= --}}

    <div class="page-header">

        <div>

            <h1>
                Fees
            </h1>

            <p>
                View generated transport fees, payment status,
                outstanding balances and fee periods for all students.
            </p>

        </div>


        <div class="page-actions">

            <a
                href="{{ route('fees.generate') }}"
                class="btn btn-primary"
            >
                + Generate Fees
            </a>

        </div>

    </div>


    {{-- =========================================================
         FLASH MESSAGES
         ========================================================= --}}

    @if(session('success'))

        <div class="alert alert-success mb-3">

            <span>✓</span>

            <div>
                {{ session('success') }}
            </div>

        </div>

    @endif


    @if(session('error'))

        <div class="alert alert-danger mb-3">

            <span>⚠</span>

            <div>
                {{ session('error') }}
            </div>

        </div>

    @endif


    @if(session('warning'))

        <div class="alert alert-warning mb-3">

            <span>!</span>

            <div>
                {{ session('warning') }}
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

                <ul style="margin:8px 0 0 18px;">

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
         SUMMARY CALCULATION
         ========================================================= --}}

    @php

        $collection = $fees->getCollection();


        /*
        |--------------------------------------------------------------------------
        | Total generated fees
        |--------------------------------------------------------------------------
        */

        $totalFeeAmount = $collection->sum(
            function ($fee) {
                return (float) $fee->amount;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Total late fees
        |--------------------------------------------------------------------------
        */

        $totalLateFee = $collection->sum(
            function ($fee) {
                return (float) $fee->late_fee;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Total paid
        |--------------------------------------------------------------------------
        */

        $totalPaid = $collection->sum(
            function ($fee) {
                return (float) $fee->paid_amount;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Total outstanding
        |--------------------------------------------------------------------------
        */

        $totalOutstanding = $collection->sum(
            function ($fee) {

                $required =
                    (float) $fee->amount
                    + (float) $fee->late_fee;

                $paid =
                    (float) $fee->paid_amount;

                return max(
                    0,
                    $required - $paid
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Paid records
        |--------------------------------------------------------------------------
        */

        $paidCount = $collection->filter(
            function ($fee) {

                return $fee->status === 'paid'
                    || (
                        (
                            (float) $fee->amount
                            + (float) $fee->late_fee
                        )
                        <= (float) $fee->paid_amount
                    );
            }
        )->count();


        /*
        |--------------------------------------------------------------------------
        | Outstanding records
        |--------------------------------------------------------------------------
        */

        $outstandingCount =
            $collection->count()
            - $paidCount;

    @endphp


    {{-- =========================================================
         SUMMARY CARDS
         ========================================================= --}}

    <div class="stats-grid mb-3">


        {{-- GENERATED FEES --}}

        <div class="stat-card">

            <div class="stat-label">
                Generated Fees
            </div>

            <div class="stat-value">

                ₹{{ number_format(
                    $totalFeeAmount,
                    2
                ) }}

            </div>

            <div class="stat-meta">
                Current page
            </div>

        </div>


        {{-- LATE FEES --}}

        <div class="stat-card">

            <div class="stat-label">
                Late Fees
            </div>

            <div class="stat-value amount-due">

                ₹{{ number_format(
                    $totalLateFee,
                    2
                ) }}

            </div>

            <div class="stat-meta">
                Additional charges
            </div>

        </div>


        {{-- PAID --}}

        <div class="stat-card">

            <div class="stat-label">
                Paid
            </div>

            <div class="stat-value amount-paid">

                ₹{{ number_format(
                    $totalPaid,
                    2
                ) }}

            </div>

            <div class="stat-meta">
                Amount paid against fees
            </div>

        </div>


        {{-- OUTSTANDING --}}

        <div class="stat-card">

            <div class="stat-label">
                Outstanding
            </div>

            <div class="stat-value amount-due">

                ₹{{ number_format(
                    $totalOutstanding,
                    2
                ) }}

            </div>

            <div class="stat-meta">
                Pending collection
            </div>

        </div>

    </div>


    {{-- =========================================================
         STATUS SUMMARY
         ========================================================= --}}

    <div class="card mb-3">

        <div class="card-body">

            <div
                style="
                    display:flex;
                    align-items:center;
                    flex-wrap:wrap;
                    gap:20px;
                "
            >

                <div>

                    <span class="badge badge-success">
                        Paid
                    </span>

                    <strong style="margin-left:6px;">
                        {{ $paidCount }}
                    </strong>

                </div>


                <div>

                    <span class="badge badge-danger">
                        Outstanding
                    </span>

                    <strong style="margin-left:6px;">
                        {{ $outstandingCount }}
                    </strong>

                </div>


                <div
                    class="text-muted"
                    style="font-size:12px;"
                >
                    {{ $fees->total() }}
                    fee record(s) found.
                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FEE RECORDS
         ========================================================= --}}

    <div class="card">

        <div class="card-header">

            <div>

                <h2 class="card-title">
                    Fee Records
                </h2>

                <div
                    class="text-muted"
                    style="
                        font-size:12px;
                        margin-top:4px;
                    "
                >
                    All generated transport fees are shown here.
                </div>

            </div>


            <div class="page-actions">

                <a
                    href="{{ route('fees.generate') }}"
                    class="btn btn-primary btn-sm"
                >
                    + Generate Fees
                </a>

            </div>

        </div>


        {{-- =====================================================
             TABLE
             ===================================================== --}}

        <div class="table-wrapper">

            <table class="table">

                <thead>

                    <tr>

                        <th>
                            Student
                        </th>

                        <th>
                            Bus
                        </th>

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
                            Late Fee
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

                    @forelse($fees as $fee)

                        @php

                            $feeAmount =
                                (float) $fee->amount;

                            $lateFee =
                                (float) $fee->late_fee;

                            $paidAmount =
                                (float) $fee->paid_amount;

                            $requiredAmount =
                                round(
                                    $feeAmount
                                    + $lateFee,
                                    2
                                );

                            $outstanding =
                                max(
                                    0,
                                    round(
                                        $requiredAmount
                                        - $paidAmount,
                                        2
                                    )
                                );

                        @endphp


                        <tr>


                            {{-- =================================================
                                 STUDENT
                                 ================================================= --}}

                            <td>

                                @if($fee->student)

                                    <div
                                        style="
                                            font-weight:650;
                                        "
                                    >
                                        {{ $fee->student->student_name }}
                                    </div>


                                    @if(
                                        $fee->student->parent_name
                                    )

                                        <div
                                            class="text-muted"
                                            style="
                                                font-size:12px;
                                                margin-top:3px;
                                            "
                                        >
                                            {{ $fee->student->parent_name }}
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

                                @if(
                                    $fee->student
                                    && $fee->student->bus
                                )

                                    <div
                                        style="
                                            font-weight:600;
                                        "
                                    >
                                        {{
                                            $fee->student
                                                ->bus
                                                ->bus_number
                                        }}
                                    </div>


                                    @if(
                                        $fee->student
                                            ->bus
                                            ->registration_number
                                    )

                                        <div
                                            class="text-muted"
                                            style="
                                                font-size:11px;
                                                margin-top:3px;
                                            "
                                        >
                                            {{
                                                $fee->student
                                                    ->bus
                                                    ->registration_number
                                            }}
                                        </div>

                                    @endif

                                @else

                                    <span
                                        class="badge badge-neutral"
                                    >
                                        No Bus
                                    </span>

                                @endif

                            </td>


                            {{-- =================================================
                                 PERIOD
                                 ================================================= --}}

                            <td>

                                <div
                                    style="
                                        font-weight:600;
                                    "
                                >

                                    {{
                                        $fee->period_start
                                            ? $fee->period_start
                                                ->format('d M Y')
                                            : '-'
                                    }}

                                </div>


                                <div
                                    class="text-muted"
                                    style="
                                        font-size:11px;
                                        margin-top:3px;
                                    "
                                >

                                    to

                                    {{
                                        $fee->period_end
                                            ? $fee->period_end
                                                ->format('d M Y')
                                            : '-'
                                    }}

                                </div>

                            </td>


                            {{-- =================================================
                                 BILLING
                                 ================================================= --}}

                            <td>

                                <span class="badge badge-neutral">

                                    {{
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $fee->billing_type
                                            )
                                        )
                                    }}

                                </span>

                            </td>


                            {{-- =================================================
                                 FEE
                                 ================================================= --}}

                            <td class="amount">

                                ₹{{ number_format(
                                    $feeAmount,
                                    2
                                ) }}

                            </td>


                            {{-- =================================================
                                 LATE FEE
                                 ================================================= --}}

                            <td class="amount">

                                @if($lateFee > 0)

                                    <span class="amount-due">

                                        ₹{{ number_format(
                                            $lateFee,
                                            2
                                        ) }}

                                    </span>

                                @else

                                    <span class="text-muted">
                                        ₹0.00
                                    </span>

                                @endif

                            </td>


                            {{-- =================================================
                                 PAID
                                 ================================================= --}}

                            <td class="amount">

                                @if($paidAmount > 0)

                                    <span class="amount-paid">

                                        ₹{{ number_format(
                                            $paidAmount,
                                            2
                                        ) }}

                                    </span>

                                @else

                                    <span class="text-muted">
                                        ₹0.00
                                    </span>

                                @endif

                            </td>


                            {{-- =================================================
                                 OUTSTANDING
                                 ================================================= --}}

                            <td class="amount">

                                @if($outstanding > 0)

                                    <strong class="amount-due">

                                        ₹{{ number_format(
                                            $outstanding,
                                            2
                                        ) }}

                                    </strong>

                                @else

                                    <strong class="amount-paid">
                                        ₹0.00
                                    </strong>

                                @endif

                            </td>


                            {{-- =================================================
                                 STATUS
                                 ================================================= --}}

                            <td>

                                @if($outstanding <= 0.01)

                                    <span class="badge badge-success">
                                        Paid
                                    </span>

                                @elseif($paidAmount > 0)

                                    <span class="badge badge-warning">
                                        Partial
                                    </span>

                                @elseif(
                                    $fee->status === 'carried_forward'
                                )

                                    <span class="badge badge-warning">
                                        Carried Forward
                                    </span>

                                @else

                                    <span class="badge badge-danger">
                                        Pending
                                    </span>

                                @endif

                            </td>


                        </tr>

                    @empty

                        {{-- =================================================
                             EMPTY STATE
                             ================================================= --}}

                        <tr>

                            <td colspan="9">

                                <div
                                    class="empty-state"
                                    style="
                                        padding:60px 25px;
                                        text-align:center;
                                    "
                                >

                                    <div
                                        class="empty-state-icon"
                                        style="
                                            font-size:42px;
                                            margin-bottom:12px;
                                        "
                                    >
                                        💰
                                    </div>


                                    <p
                                        class="empty-state-title"
                                        style="
                                            font-size:18px;
                                        "
                                    >
                                        No fee records found
                                    </p>


                                    <p
                                        class="empty-state-text"
                                    >
                                        No transport fees have been
                                        generated yet.
                                    </p>


                                    <div
                                        style="
                                            margin-top:18px;
                                        "
                                    >

                                        <a
                                            href="{{ route(
                                                'fees.generate'
                                            ) }}"
                                            class="btn btn-primary"
                                        >
                                            + Generate Fees
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

        @if($fees->hasPages())

            <div class="card-footer">

                {{ $fees->links() }}

            </div>

        @endif

    </div>


    {{-- =========================================================
         FEE STATUS GUIDE
         ========================================================= --}}

    <div class="card mt-3">

        <div class="card-header">

            <div>

                <h2 class="card-title">
                    Fee Status Guide
                </h2>

                <div
                    class="text-muted"
                    style="
                        font-size:12px;
                        margin-top:4px;
                    "
                >
                    Understand the payment status of each fee.
                </div>

            </div>

        </div>


        <div class="card-body">

            <div class="grid grid-3">


                {{-- PENDING --}}

                <div class="balance-box">

                    <div class="balance-label">
                        Pending
                    </div>

                    <div
                        class="balance-value amount-due"
                    >
                        Outstanding
                    </div>

                    <div
                        class="text-muted"
                        style="
                            font-size:12px;
                            margin-top:6px;
                            line-height:1.5;
                        "
                    >
                        Fee has been generated but
                        no payment has been fully allocated.
                    </div>

                </div>


                {{-- PARTIAL --}}

                <div class="balance-box">

                    <div class="balance-label">
                        Partial
                    </div>

                    <div class="balance-value">
                        Partially Paid
                    </div>

                    <div
                        class="text-muted"
                        style="
                            font-size:12px;
                            margin-top:6px;
                            line-height:1.5;
                        "
                    >
                        Payment has been received,
                        but some amount is still outstanding.
                    </div>

                </div>


                {{-- PAID --}}

                <div class="balance-box">

                    <div class="balance-label">
                        Paid
                    </div>

                    <div
                        class="balance-value amount-paid"
                    >
                        ₹0.00 Due
                    </div>

                    <div
                        class="text-muted"
                        style="
                            font-size:12px;
                            margin-top:6px;
                            line-height:1.5;
                        "
                    >
                        The fee and applicable late fee
                        have been completely paid.
                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection