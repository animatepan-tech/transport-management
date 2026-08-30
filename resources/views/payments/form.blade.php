@extends('layouts.app')

@section('title', 'Record Payment')
@section('page_heading', 'Record Payment')

@section('content')

@php


/*
|--------------------------------------------------------------------------
| Safe defaults
|--------------------------------------------------------------------------
*/

$students = $students ?? collect();

$account = $account ?? [
    'total_fees' => 0.00,
    'total_late_fees' => 0.00,
    'total_paid' => 0.00,
    'total_allocated' => 0.00,
    'advance_amount' => 0.00,
    'due_amount' => 0.00,
    'current_balance' => 0.00,
    'outstanding_fees' => collect(),
];

$selectedStudent = $selectedStudent ?? null;


@endphp

{{-- =========================================================
PAGE HEADER
========================================================= --}}

<div class="page-header">


<div>

    <h1>Record Payment</h1>

    <p>
        Record a student payment and automatically allocate it
        against the oldest outstanding fees.
    </p>

</div>

<div class="page-actions">

    <a href="{{ route('payments.index') }}"
       class="btn btn-secondary">

        ← Payment History

    </a>

</div>


</div>

{{-- =========================================================
VALIDATION ERRORS
========================================================= --}}

@if($errors->any())


<div class="alert alert-danger mb-3">

    <span>⚠</span>

    <div>

        <strong>
            Please correct the following errors:
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
SELECTED STUDENT ACCOUNT
========================================================= --}}

@if($selectedStudent)


<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">

                {{ $selectedStudent->student_name }}

            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                @if($selectedStudent->parent_name)

                    {{ $selectedStudent->parent_name }}

                @endif

                @if($selectedStudent->bus)

                    @if($selectedStudent->parent_name)
                        •
                    @endif

                    Bus {{ $selectedStudent->bus->bus_number }}

                @else

                    @if($selectedStudent->parent_name)
                        •
                    @endif

                    No bus assigned

                @endif

            </div>

        </div>


        {{-- ACCOUNT STATUS --}}

        @if($account['due_amount'] > 0)

            <span class="badge badge-danger">
                Payment Due
            </span>

        @elseif($account['advance_amount'] > 0)

            <span class="badge badge-success">
                Advance
            </span>

        @else

            <span class="badge badge-success">
                Settled
            </span>

        @endif

    </div>


    <div class="card-body">

        <div class="grid grid-3">


            {{-- GENERATED FEES --}}

            <div class="balance-box">

                <div class="balance-label">
                    Generated Fees
                </div>

                <div class="balance-value">

                    ₹{{ number_format(
                        (float) $account['total_fees'],
                        2
                    ) }}

                </div>

                @if((float) $account['total_late_fees'] > 0)

                    <div class="text-muted"
                         style="font-size:11px; margin-top:4px;">

                        + ₹{{ number_format(
                            (float) $account['total_late_fees'],
                            2
                        ) }}

                        late fees

                    </div>

                @endif

            </div>


            {{-- TOTAL PAID --}}

            <div class="balance-box">

                <div class="balance-label">
                    Total Paid
                </div>

                <div class="balance-value amount-paid">

                    ₹{{ number_format(
                        (float) $account['total_paid'],
                        2
                    ) }}

                </div>

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    Received payments

                </div>

            </div>


            {{-- OUTSTANDING --}}

            <div class="balance-box">

                <div class="balance-label">
                    Outstanding Due
                </div>

                <div class="balance-value
                    {{ (float) $account['due_amount'] > 0
                        ? 'amount-due'
                        : 'amount-profit' }}">

                    ₹{{ number_format(
                        (float) $account['due_amount'],
                        2
                    ) }}

                </div>

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    Remaining fee balance

                </div>

            </div>

        </div>


        {{-- ADVANCE CREDIT --}}

        @if((float) $account['advance_amount'] > 0)

            <div class="alert alert-success mt-2">

                <span>✓</span>

                <div>

                    <strong>
                        Advance Credit:
                    </strong>

                    ₹{{ number_format(
                        (float) $account['advance_amount'],
                        2
                    ) }}

                    is currently available.

                    @if($selectedStudent->monthly_fee > 0)

                        <div style="font-size:12px; margin-top:4px;">

                            Approximately
                            {{ number_format(
                                $account['advance_amount']
                                / (float) $selectedStudent->monthly_fee,
                                2
                            ) }}

                            month(s) of the current monthly fee.

                        </div>

                    @endif

                </div>

            </div>

        @endif


        {{-- CURRENT BALANCE --}}

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:15px;
            padding-top:15px;
            border-top:1px solid #eee;
        ">

            <div>

                <div style="font-weight:600;">
                    Current Balance
                </div>

                <div class="text-muted"
                     style="font-size:11px; margin-top:3px;">

                    Negative = Due • Positive = Advance

                </div>

            </div>


            @if((float) $account['current_balance'] < 0)

                <strong class="amount-due">

                    -₹{{ number_format(
                        abs((float) $account['current_balance']),
                        2
                    ) }}

                </strong>

            @elseif((float) $account['current_balance'] > 0)

                <strong class="amount-profit">

                    +₹{{ number_format(
                        (float) $account['current_balance'],
                        2
                    ) }}

                </strong>

            @else

                <strong class="amount-paid">

                    ₹0.00

                </strong>

            @endif

        </div>

    </div>

</div>


{{-- =====================================================
     OUTSTANDING FEES
     ===================================================== --}}

@if($account['outstanding_fees']->count() > 0)

    <div class="card mb-3">

        <div class="card-header">

            <div>

                <h2 class="card-title">
                    Outstanding Fees
                </h2>

                <div class="text-muted"
                     style="font-size:12px; margin-top:4px;">

                    Payments are automatically allocated
                    from the oldest fee period first.

                </div>

            </div>

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

                    @foreach(
                        $account['outstanding_fees']
                        as $fee
                    )

                        @php

                            $feeAmount =
                                (float) $fee->amount;

                            $lateFee =
                                (float) $fee->late_fee;

                            $paidAmount =
                                (float) $fee->paid_amount;

                            $totalRequired =
                                $feeAmount +
                                $lateFee;

                            $outstanding = max(
                                0,
                                round(
                                    $totalRequired
                                    - $paidAmount,
                                    2
                                )
                            );

                        @endphp


                        <tr>


                            {{-- PERIOD --}}

                            <td>

                                <div style="font-weight:600;">

                                    {{ $fee->period_start
                                        ? $fee->period_start->format('d M Y')
                                        : '-'
                                    }}

                                </div>

                                <div class="text-muted"
                                     style="
                                        font-size:11px;
                                        margin-top:3px;
                                     ">

                                    to

                                    {{ $fee->period_end
                                        ? $fee->period_end->format('d M Y')
                                        : '-'
                                    }}

                                </div>

                            </td>


                            {{-- BILLING TYPE --}}

                            <td>

                                <span class="badge badge-neutral">

                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $fee->billing_type
                                        )
                                    ) }}

                                </span>

                            </td>


                            {{-- FEE --}}

                            <td class="amount">

                                ₹{{ number_format(
                                    $feeAmount,
                                    2
                                ) }}

                            </td>


                            {{-- LATE FEE --}}

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


                            {{-- PAID --}}

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


                            {{-- OUTSTANDING --}}

                            <td class="amount">

                                <strong class="amount-due">

                                    ₹{{ number_format(
                                        $outstanding,
                                        2
                                    ) }}

                                </strong>

                            </td>


                            {{-- STATUS --}}

                            <td>

                                @if($fee->status === 'partial')

                                    <span class="badge badge-warning">

                                        Partial

                                    </span>

                                @elseif($fee->status === 'paid')

                                    <span class="badge badge-success">

                                        Paid

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

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

@else

    <div class="alert alert-success mb-3">

        <span>✓</span>

        <div>

            <strong>
                No outstanding fees.
            </strong>

            This student currently has no generated fees
            requiring payment.

        </div>

    </div>

@endif


@endif

{{-- =========================================================
PAYMENT FORM
========================================================= --}}

<div class="card">


<div class="card-header">

    <div>

        <h2 class="card-title">
            Payment Details
        </h2>

        <div class="text-muted"
             style="font-size:12px; margin-top:4px;">

            Payment will automatically be allocated
            to outstanding fees.

        </div>

    </div>

</div>


<div class="card-body">

    <form
        method="POST"
        action="{{ route('payments.store') }}"
    >

        @csrf


        {{-- =================================================
             STUDENT
             ================================================= --}}

        <div class="form-group">

            <label for="student_id">

                Student

                <span class="text-danger">
                    *
                </span>

            </label>


            <select
                name="student_id"
                id="student_id"
                required
                onchange="loadStudentAccount(this.value)"
            >

                <option value="">
                    -- Select Student --
                </option>


                @foreach($students as $student)

                    <option
                        value="{{ $student->id }}"

                        {{ (string) old(
                            'student_id',
                            $selectedStudent?->id
                        ) === (string) $student->id
                            ? 'selected'
                            : '' }}
                    >

                        {{ $student->student_name }}

                        —

                        {{ $student->bus->bus_number ?? 'No Bus' }}

                    </option>

                @endforeach

            </select>


            @error('student_id')

                <div
                    class="text-danger"
                    style="
                        font-size:12px;
                        margin-top:5px;
                    "
                >

                    {{ $message }}

                </div>

            @enderror

        </div>


        {{-- =================================================
             PAYMENT DATE + AMOUNT
             ================================================= --}}

        <div class="grid grid-2">


            {{-- DATE --}}

            <div class="form-group">

                <label for="payment_date">

                    Payment Date

                    <span class="text-danger">
                        *
                    </span>

                </label>


                <input
                    type="date"
                    name="payment_date"
                    id="payment_date"
                    value="{{ old(
                        'payment_date',
                        now()->format('Y-m-d')
                    ) }}"
                    required
                >


                @error('payment_date')

                    <div
                        class="text-danger"
                        style="
                            font-size:12px;
                            margin-top:5px;
                        "
                    >

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- AMOUNT --}}

            <div class="form-group">

                <label for="amount">

                    Amount

                    <span class="text-danger">
                        *
                    </span>

                </label>


                <input
                    type="number"
                    name="amount"
                    id="amount"
                    step="0.01"
                    min="0.01"
                    value="{{ old('amount') }}"
                    placeholder="0.00"
                    required
                >


                @error('amount')

                    <div
                        class="text-danger"
                        style="
                            font-size:12px;
                            margin-top:5px;
                        "
                    >

                        {{ $message }}

                    </div>

                @enderror

            </div>

        </div>


        {{-- =================================================
             PAYMENT MODE + REFERENCE
             ================================================= --}}

        <div class="grid grid-2">


            {{-- PAYMENT MODE --}}

            <div class="form-group">

                <label for="payment_mode">

                    Payment Mode

                    <span class="text-danger">
                        *
                    </span>

                </label>


                <select
                    name="payment_mode"
                    id="payment_mode"
                    required
                >

                    <option
                        value="cash"
                        {{ old(
                            'payment_mode',
                            'cash'
                        ) === 'cash'
                            ? 'selected'
                            : '' }}
                    >

                        Cash

                    </option>


                    <option
                        value="upi"
                        {{ old('payment_mode') === 'upi'
                            ? 'selected'
                            : '' }}
                    >

                        UPI

                    </option>


                    <option
                        value="bank"
                        {{ old('payment_mode') === 'bank'
                            ? 'selected'
                            : '' }}
                    >

                        Bank Transfer

                    </option>


                    <option
                        value="cheque"
                        {{ old('payment_mode') === 'cheque'
                            ? 'selected'
                            : '' }}
                    >

                        Cheque

                    </option>

                </select>


                @error('payment_mode')

                    <div
                        class="text-danger"
                        style="
                            font-size:12px;
                            margin-top:5px;
                        "
                    >

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- REFERENCE --}}

            <div class="form-group">

                <label for="reference">

                    Reference

                </label>


                <input
                    type="text"
                    name="reference"
                    id="reference"
                    value="{{ old('reference') }}"
                    maxlength="100"
                    placeholder="UPI ID / cheque no. / transaction ID"
                >


                @error('reference')

                    <div
                        class="text-danger"
                        style="
                            font-size:12px;
                            margin-top:5px;
                        "
                    >

                        {{ $message }}

                    </div>

                @enderror

            </div>

        </div>


        {{-- =================================================
             NOTES
             ================================================= --}}

        <div class="form-group">

            <label for="notes">
                Notes
            </label>


            <textarea
                name="notes"
                id="notes"
                rows="4"
                placeholder="Optional payment notes..."
            >{{ old('notes') }}</textarea>


            @error('notes')

                <div
                    class="text-danger"
                    style="
                        font-size:12px;
                        margin-top:5px;
                    "
                >

                    {{ $message }}

                </div>

            @enderror

        </div>


        {{-- =================================================
             AUTOMATIC ALLOCATION INFORMATION
             ================================================= --}}

        <div class="alert alert-info mb-3">

            <span>ⓘ</span>

            <div>

                <strong>
                    Automatic Allocation
                </strong>

                <div style="margin-top:4px;">

                    The system will automatically apply this
                    payment to the student's oldest outstanding
                    fee first.

                    If the payment is larger than the outstanding
                    amount, the remaining amount will stay as
                    advance credit.

                </div>

            </div>

        </div>


        {{-- =================================================
             PAYMENT PREVIEW
             ================================================= --}}

        @if($selectedStudent)

            <div
                id="paymentPreview"
                class="balance-box mb-3"
            >

                <div class="balance-label">
                    Payment Preview
                </div>

                <div
                    id="previewText"
                    class="text-muted"
                    style="
                        font-size:13px;
                        margin-top:6px;
                    "
                >

                    Enter a payment amount to see
                    the expected allocation.

                </div>

            </div>

        @endif


        {{-- =================================================
             ACTIONS
             ================================================= --}}

        <div class="form-actions">

            <a
                href="{{ route('payments.index') }}"
                class="btn btn-secondary"
            >

                Cancel

            </a>


            <button
                type="submit"
                class="btn btn-primary"
            >

                Save Payment

            </button>

        </div>

    </form>

</div>


</div>

{{-- =========================================================
JAVASCRIPT
========================================================= --}}

<script>

function loadStudentAccount(studentId)
{
    if (!studentId) {

        window.location.href =
            "{{ route('payments.create') }}";

        return;
    }

    window.location.href =
        "{{ route('payments.create') }}" +
        "?student_id=" +
        encodeURIComponent(studentId);
}


document.addEventListener('DOMContentLoaded', function () {

    const amountInput =
        document.getElementById('amount');

    const previewText =
        document.getElementById('previewText');

    if (!amountInput || !previewText) {
        return;
    }


    const outstanding =
        {{ (float) ($account['due_amount'] ?? 0) }};

    const advance =
        {{ (float) ($account['advance_amount'] ?? 0) }};


    function updatePreview()
    {
        const amount =
            parseFloat(amountInput.value) || 0;


        if (amount <= 0) {

            previewText.textContent =
                'Enter a payment amount to see the expected allocation.';

            return;
        }


        if (outstanding <= 0) {

            previewText.innerHTML =
                'This student has no outstanding fees. ' +
                '<strong>' +
                '₹' +
                amount.toFixed(2) +
                '</strong> ' +
                'will remain as advance credit.';

            return;
        }


        const allocation =
            Math.min(
                amount,
                outstanding
            );


        const remaining =
            Math.max(
                0,
                amount - outstanding
            );


        let html =
            '<strong>₹' +
            allocation.toFixed(2) +
            '</strong> ' +
            'will be allocated to outstanding fees.';


        if (remaining > 0) {

            html +=
                '<br><span class="amount-profit">' +
                '₹' +
                remaining.toFixed(2) +
                ' will remain as advance credit.' +
                '</span>';

        }


        previewText.innerHTML = html;
    }


    amountInput.addEventListener(
        'input',
        updatePreview
    );


    updatePreview();

});

</script>

@endsection
