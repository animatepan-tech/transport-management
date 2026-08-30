@extends('layouts.app')

@section('title', 'Generate Fees')
@section('page_heading', 'Generate Fees')

@section('content')

    {{-- =========================================================
         PAGE HEADER
         ========================================================= --}}

    <div class="page-header">

        <div>

            <h1>
                Generate Fees
            </h1>

            <p>
                Generate monthly, quarterly, half-yearly or yearly
                transport fees for active students.
            </p>

        </div>


        <div class="page-actions">

            <a
                href="{{ route('fees.index') }}"
                class="btn btn-secondary"
            >
                ← Fees
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
         WARNING MESSAGE
         ========================================================= --}}

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
         GENERATION CARD
         ========================================================= --}}

    <div
        class="card"
        style="
            max-width:1000px;
        "
    >

        {{-- =====================================================
             CARD HEADER
             ===================================================== --}}

        <div class="card-header">

            <div>

                <h2 class="card-title">
                    Fee Generation
                </h2>

                <div
                    class="text-muted"
                    style="
                        font-size:13px;
                        margin-top:4px;
                    "
                >
                    Only active students will receive a new fee record.
                </div>

            </div>

        </div>


        {{-- =====================================================
             CARD BODY
             ===================================================== --}}

        <div class="card-body">

            <form
                method="POST"
                action="{{ route('fees.generate.store') }}"
                id="feeGenerationForm"
            >

                @csrf


                {{-- =================================================
                     GENERATION SETTINGS
                     ================================================= --}}

                <div class="grid grid-2">


                    {{-- =================================================
                         STARTING MONTH
                         ================================================= --}}

                    <div class="form-group">

                        <label for="month">

                            Starting Month

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="month"
                            id="month"
                            name="month"
                            value="{{ old(
                                'month',
                                now()->format('Y-m')
                            ) }}"
                            required
                        >


                        <div
                            class="text-muted"
                            style="
                                font-size:12px;
                                margin-top:6px;
                                line-height:1.5;
                            "
                        >
                            Select the first month of the fee period.
                            Example: August 2026
                        </div>


                        @error('month')

                            <div
                                class="text-danger"
                                style="
                                    font-size:12px;
                                    margin-top:6px;
                                "
                            >
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- =================================================
                         BILLING PERIOD
                         ================================================= --}}

                    <div class="form-group">

                        <label for="billing_type">

                            Billing Period

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <select
                            id="billing_type"
                            name="billing_type"
                            required
                        >

                            <option
                                value="monthly"
                                {{ old(
                                    'billing_type',
                                    'monthly'
                                ) === 'monthly'
                                    ? 'selected'
                                    : ''
                                }}
                            >
                                Monthly — 1 Month
                            </option>


                            <option
                                value="quarterly"
                                {{ old(
                                    'billing_type'
                                ) === 'quarterly'
                                    ? 'selected'
                                    : ''
                                }}
                            >
                                Quarterly — 3 Months
                            </option>


                            <option
                                value="half_yearly"
                                {{ old(
                                    'billing_type'
                                ) === 'half_yearly'
                                    ? 'selected'
                                    : ''
                                }}
                            >
                                Half Yearly — 6 Months
                            </option>


                            <option
                                value="yearly"
                                {{ old(
                                    'billing_type'
                                ) === 'yearly'
                                    ? 'selected'
                                    : ''
                                }}
                            >
                                Yearly — 12 Months
                            </option>

                        </select>


                        <div
                            class="text-muted"
                            style="
                                font-size:12px;
                                margin-top:6px;
                                line-height:1.5;
                            "
                        >
                            The student's monthly fee is multiplied by
                            the number of months in the selected period.
                        </div>


                        @error('billing_type')

                            <div
                                class="text-danger"
                                style="
                                    font-size:12px;
                                    margin-top:6px;
                                "
                            >
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                </div>


                {{-- =================================================
                     PERIOD PREVIEW
                     ================================================= --}}

                <div
                    class="card"
                    style="
                        margin-top:20px;
                        border:1px solid #e2e8f0;
                        background:#f8fafc;
                    "
                >

                    <div class="card-body">

                        <div
                            style="
                                display:grid;
                                grid-template-columns:
                                    minmax(0, 1fr)
                                    minmax(180px, 240px);
                                gap:20px;
                                align-items:center;
                            "
                        >


                            {{-- SELECTED PERIOD --}}

                            <div>

                                <div
                                    class="text-muted"
                                    style="
                                        font-size:12px;
                                        font-weight:600;
                                        text-transform:uppercase;
                                        letter-spacing:.3px;
                                    "
                                >
                                    Selected Billing Period
                                </div>


                                <div
                                    id="periodPreview"
                                    style="
                                        margin-top:6px;
                                        font-size:20px;
                                        line-height:1.3;
                                        font-weight:700;
                                        color:#1e293b;
                                    "
                                >
                                    —
                                </div>


                                <div
                                    id="billingLabelPreview"
                                    class="text-muted"
                                    style="
                                        margin-top:4px;
                                        font-size:12px;
                                    "
                                >
                                    Monthly
                                </div>

                            </div>


                            {{-- DURATION --}}

                            <div
                                style="
                                    padding-left:20px;
                                    border-left:
                                        1px solid #e2e8f0;
                                "
                            >

                                <div
                                    class="text-muted"
                                    style="
                                        font-size:12px;
                                        font-weight:600;
                                        text-transform:uppercase;
                                        letter-spacing:.3px;
                                    "
                                >
                                    Duration
                                </div>


                                <div
                                    id="monthCountPreview"
                                    style="
                                        margin-top:6px;
                                        font-size:20px;
                                        line-height:1.3;
                                        font-weight:700;
                                        color:#2563eb;
                                    "
                                >
                                    1 month
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     INFORMATION
                     ================================================= --}}

                <div
                    class="alert alert-info"
                    style="
                        margin-top:20px;
                    "
                >

                    <span>
                        ⓘ
                    </span>

                    <div>

                        <strong>
                            How fee generation works
                        </strong>


                        <div
                            style="
                                margin-top:7px;
                                line-height:1.7;
                            "
                        >

                            <div>
                                • Only active students are included.
                            </div>

                            <div>
                                • Each student's current monthly fee is used.
                            </div>

                            <div>
                                • Monthly billing creates a 1-month fee.
                            </div>

                            <div>
                                • Quarterly billing creates a 3-month fee.
                            </div>

                            <div>
                                • Half-yearly billing creates a 6-month fee.
                            </div>

                            <div>
                                • Yearly billing creates a 12-month fee.
                            </div>

                            <div>
                                • Existing overlapping fee periods are skipped.
                            </div>

                            <div>
                                • Duplicate or overlapping periods are not
                                  created for the same student.
                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     WARNING
                     ================================================= --}}

                <div
                    class="alert alert-warning"
                    style="
                        margin-top:14px;
                    "
                >

                    <span>
                        ⚠
                    </span>

                    <div>

                        <strong>
                            Before generating fees
                        </strong>

                        <div
                            style="
                                margin-top:5px;
                                line-height:1.6;
                            "
                        >
                            Make sure the starting month and billing
                            period are correct. Existing overlapping
                            fee periods will be skipped automatically.
                        </div>

                    </div>

                </div>


                {{-- =================================================
                     ACTIONS
                     ================================================= --}}

                <div
                    class="form-actions"
                    style="
                        margin-top:24px;
                    "
                >

                    <a
                        href="{{ route('fees.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="generateFeesButton"
                    >
                        Generate Fees
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- =========================================================
         JAVASCRIPT
         ========================================================= --}}

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const monthInput =
                    document.getElementById('month');

                const billingType =
                    document.getElementById('billing_type');

                const periodPreview =
                    document.getElementById('periodPreview');

                const billingLabelPreview =
                    document.getElementById(
                        'billingLabelPreview'
                    );

                const monthCountPreview =
                    document.getElementById(
                        'monthCountPreview'
                    );

                const form =
                    document.getElementById(
                        'feeGenerationForm'
                    );

                const generateButton =
                    document.getElementById(
                        'generateFeesButton'
                    );


                /*
                |--------------------------------------------------------------------------
                | Billing configuration
                |--------------------------------------------------------------------------
                */

                const billingOptions = {

                    monthly: {
                        label: 'Monthly',
                        months: 1
                    },

                    quarterly: {
                        label: 'Quarterly',
                        months: 3
                    },

                    half_yearly: {
                        label: 'Half Yearly',
                        months: 6
                    },

                    yearly: {
                        label: 'Yearly',
                        months: 12
                    }

                };


                /*
                |--------------------------------------------------------------------------
                | Get selected billing option
                |--------------------------------------------------------------------------
                */

                function getBillingOption()
                {
                    return (
                        billingOptions[
                            billingType.value
                        ]
                        || billingOptions.monthly
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Format month/year
                |--------------------------------------------------------------------------
                */

                function formatMonth(
                    date
                ) {
                    return new Intl.DateTimeFormat(
                        'en-IN',
                        {
                            month: 'long',
                            year: 'numeric'
                        }
                    ).format(date);
                }


                /*
                |--------------------------------------------------------------------------
                | Update period preview
                |--------------------------------------------------------------------------
                */

                function updatePreview()
                {
                    const selected =
                        getBillingOption();


                    billingLabelPreview.textContent =
                        selected.label;


                    monthCountPreview.textContent =
                        selected.months === 1
                            ? '1 month'
                            : selected.months + ' months';


                    const monthValue =
                        monthInput.value;


                    if (!monthValue) {

                        periodPreview.textContent =
                            'Select a starting month';

                        return;
                    }


                    const parts =
                        monthValue.split('-');


                    if (parts.length !== 2) {

                        periodPreview.textContent =
                            'Select a valid month';

                        return;
                    }


                    const year =
                        parseInt(
                            parts[0],
                            10
                        );


                    const month =
                        parseInt(
                            parts[1],
                            10
                        );


                    if (
                        !Number.isInteger(year)
                        || !Number.isInteger(month)
                        || month < 1
                        || month > 12
                    ) {

                        periodPreview.textContent =
                            'Select a valid month';

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | JavaScript month indexes are zero-based.
                    |--------------------------------------------------------------------------
                    */

                    const startDate =
                        new Date(
                            year,
                            month - 1,
                            1
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Last day of selected billing period.
                    |--------------------------------------------------------------------------
                    |
                    | month - 1 + numberOfMonths
                    | gives the following month,
                    | day 0 gives the last day of the
                    | previous month.
                    |--------------------------------------------------------------------------
                    */

                    const endDate =
                        new Date(
                            year,
                            month - 1
                                + selected.months,
                            0
                        );


                    periodPreview.textContent =
                        formatMonth(startDate)
                        + ' – '
                        + formatMonth(endDate);
                }


                /*
                |--------------------------------------------------------------------------
                | Update on changes
                |--------------------------------------------------------------------------
                */

                monthInput.addEventListener(
                    'change',
                    updatePreview
                );


                billingType.addEventListener(
                    'change',
                    updatePreview
                );


                /*
                |--------------------------------------------------------------------------
                | Prevent accidental duplicate submission
                |--------------------------------------------------------------------------
                */

                form.addEventListener(
                    'submit',
                    function (event) {

                        const selected =
                            getBillingOption();


                        const monthValue =
                            monthInput.value;


                        if (!monthValue) {
                            return;
                        }


                        const confirmed =
                            window.confirm(
                                'Generate '
                                + selected.months
                                + (
                                    selected.months === 1
                                        ? ' month'
                                        : ' months'
                                )
                                + ' of transport fees for all active students?'
                            );


                        if (!confirmed) {

                            event.preventDefault();

                            return;
                        }


                        generateButton.disabled =
                            true;

                        generateButton.textContent =
                            'Generating Fees...';

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Initial preview
                |--------------------------------------------------------------------------
                */

                updatePreview();

            }
        );

    </script>

@endsection