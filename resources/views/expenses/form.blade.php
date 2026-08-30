@extends('layouts.app')

@section('title', 'Add Expense')
@section('page_heading', 'Add Expense')

@section('content')

<div class="page-header">

    <div>

        <h1>Add Expense</h1>

        <p>
            Record a transport operating expense.
        </p>

    </div>


    <div class="page-actions">

        <a href="{{ route('expenses.index') }}"
           class="btn btn-secondary">

            ← Expense History

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
     EXPENSE FORM
     ========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Expense Details
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Enter the details of the expense below.

            </div>

        </div>

    </div>


    <div class="card-body">

        <form
            method="POST"
            action="{{ route('expenses.store') }}"
        >

            @csrf


            {{-- =================================================
                 BUS
                 ================================================= --}}

            <div class="form-group">

                <label for="bus_id">
                    Bus
                </label>

                <select
                    name="bus_id"
                    id="bus_id"
                >

                    <option value="">
                        All / General Expense
                    </option>


                    @foreach($buses as $bus)

                        <option
                            value="{{ $bus->id }}"
                            {{ old('bus_id') == $bus->id
                                ? 'selected'
                                : '' }}
                        >

                            {{ $bus->bus_number }}

                            @if($bus->registration_number)

                                —
                                {{ $bus->registration_number }}

                            @endif

                        </option>

                    @endforeach

                </select>


                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">

                    Leave blank for an expense that applies
                    to all buses or general transport operations.

                </div>


                @error('bus_id')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- =================================================
                 DATE + CATEGORY
                 ================================================= --}}

            <div class="grid grid-2">


                {{-- DATE --}}

                <div class="form-group">

                    <label for="expense_date">

                        Date

                        <span class="text-danger">
                            *
                        </span>

                    </label>

                    <input
                        type="date"
                        name="expense_date"
                        id="expense_date"
                        value="{{ old(
                            'expense_date',
                            now()->format('Y-m-d')
                        ) }}"
                        required
                    >


                    @error('expense_date')

                        <div class="text-danger"
                             style="font-size:12px; margin-top:5px;">

                            {{ $message }}

                        </div>

                    @enderror

                </div>


                {{-- CATEGORY --}}

                <div class="form-group">

                    <label for="category">

                        Category

                        <span class="text-danger">
                            *
                        </span>

                    </label>

                    <select
                        name="category"
                        id="category"
                        required
                    >

                        <option value="">
                            -- Select Category --
                        </option>


                        <option
                            value="Driver Salary"
                            {{ old('category') === 'Driver Salary'
                                ? 'selected'
                                : '' }}
                        >
                            Driver Salary
                        </option>


                        <option
                            value="Diesel/Petrol"
                            {{ old('category') === 'Diesel/Petrol'
                                ? 'selected'
                                : '' }}
                        >
                            Diesel / Petrol
                        </option>


                        <option
                            value="Repair"
                            {{ old('category') === 'Repair'
                                ? 'selected'
                                : '' }}
                        >
                            Repair
                        </option>


                        <option
                            value="Documentation"
                            {{ old('category') === 'Documentation'
                                ? 'selected'
                                : '' }}
                        >
                            Documentation
                        </option>


                        <option
                            value="Police Fine/Penalty"
                            {{ old('category') === 'Police Fine/Penalty'
                                ? 'selected'
                                : '' }}
                        >
                            Police Fine / Penalty
                        </option>


                        <option
                            value="Other"
                            {{ old('category') === 'Other'
                                ? 'selected'
                                : '' }}
                        >
                            Other
                        </option>

                    </select>


                    @error('category')

                        <div class="text-danger"
                             style="font-size:12px; margin-top:5px;">

                            {{ $message }}

                        </div>

                    @enderror

                </div>

            </div>


            {{-- =================================================
                 AMOUNT + VENDOR
                 ================================================= --}}

            <div class="grid grid-2">


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

                        <div class="text-danger"
                             style="font-size:12px; margin-top:5px;">

                            {{ $message }}

                        </div>

                    @enderror

                </div>


                {{-- VENDOR --}}

                <div class="form-group">

                    <label for="vendor">
                        Vendor
                    </label>

                    <input
                        type="text"
                        name="vendor"
                        id="vendor"
                        value="{{ old('vendor') }}"
                        maxlength="150"
                        placeholder="e.g. HP Fuel Station"
                    >


                    @error('vendor')

                        <div class="text-danger"
                             style="font-size:12px; margin-top:5px;">

                            {{ $message }}

                        </div>

                    @enderror

                </div>

            </div>


            {{-- =================================================
                 DESCRIPTION
                 ================================================= --}}

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    name="description"
                    id="description"
                    rows="4"
                    placeholder="Enter additional details about this expense..."
                >{{ old('description') }}</textarea>


                @error('description')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- =================================================
                 INFORMATION
                 ================================================= --}}

            <div class="alert alert-info mb-3">

                <span>ⓘ</span>

                <div>

                    <strong>
                        Expense Accounting
                    </strong>

                    <div style="margin-top:4px;">

                        Bus-specific expenses can be assigned to a
                        particular bus. Leave the bus as
                        <strong>All / General Expense</strong>
                        for expenses that are not bus-specific.

                    </div>

                </div>

            </div>


            {{-- =================================================
                 ACTIONS
                 ================================================= --}}

            <div class="form-actions">

                <a
                    href="{{ route('expenses.index') }}"
                    class="btn btn-secondary"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Save Expense

                </button>

            </div>

        </form>

    </div>

</div>

@endsection