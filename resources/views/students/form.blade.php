@extends('layouts.app')

@section('title', isset($student) ? 'Edit Student' : 'Add Student')
@section('page_heading', isset($student) ? 'Edit Student' : 'Add Student')

@section('content')

<div class="page-header">


<div>
    <h1>
        {{ isset($student) ? 'Edit Student' : 'Add Student' }}
    </h1>

    <p>
        {{ isset($student)
            ? 'Update the student information and transport assignment.'
            : 'Add a student and assign their transport details.'
        }}
    </p>
</div>

<div class="page-actions">

    <a href="{{ route('students.index') }}"
       class="btn btn-secondary">

        ← Student List

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
STUDENT FORM
========================================================= --}}

<div class="card">


<div class="card-header">

    <div>

        <h2 class="card-title">
            Student Information
        </h2>

        <div class="text-muted"
             style="font-size:12px; margin-top:4px;">

            Enter the student's personal, transport and
            monthly fee information.

        </div>

    </div>

</div>


<div class="card-body">

    <form
        method="POST"
        action="{{ isset($student)
            ? route('students.update', $student)
            : route('students.store')
        }}"
    >

        @csrf

        @if(isset($student))

            @method('PUT')

        @endif


        {{-- =================================================
             STUDENT NAME
        ================================================== --}}

        <div class="form-group">

            <label for="student_name">

                Student Name
                <span class="text-danger">*</span>

            </label>

            <input
                type="text"
                name="student_name"
                id="student_name"
                value="{{ old(
                    'student_name',
                    $student->student_name ?? ''
                ) }}"
                maxlength="100"
                placeholder="Enter student name"
                required
                autofocus
            >

            @error('student_name')

                <div class="text-danger"
                     style="font-size:12px; margin-top:5px;">

                    {{ $message }}

                </div>

            @enderror

        </div>


        {{-- =================================================
             PARENT NAME
        ================================================== --}}

        <div class="form-group">

            <label for="parent_name">
                Parent / Guardian Name
            </label>

            <input
                type="text"
                name="parent_name"
                id="parent_name"
                value="{{ old(
                    'parent_name',
                    $student->parent_name ?? ''
                ) }}"
                maxlength="100"
                placeholder="Enter parent or guardian name"
            >

            @error('parent_name')

                <div class="text-danger"
                     style="font-size:12px; margin-top:5px;">

                    {{ $message }}

                </div>

            @enderror

        </div>


        {{-- =================================================
             WHATSAPP + PICKUP STOP
        ================================================== --}}

        <div class="grid grid-2">

            <div class="form-group">

                <label for="whatsapp_number">
                    WhatsApp Number
                </label>

                <input
                    type="text"
                    name="whatsapp_number"
                    id="whatsapp_number"
                    value="{{ old(
                        'whatsapp_number',
                        $student->whatsapp_number ?? ''
                    ) }}"
                    maxlength="30"
                    placeholder="Enter WhatsApp number"
                >

                @error('whatsapp_number')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            <div class="form-group">

                <label for="pickup_stop">
                    Pickup Stop
                </label>

                <input
                    type="text"
                    name="pickup_stop"
                    id="pickup_stop"
                    value="{{ old(
                        'pickup_stop',
                        $student->pickup_stop ?? ''
                    ) }}"
                    maxlength="150"
                    placeholder="Enter pickup stop"
                >

                @error('pickup_stop')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>

        </div>


        {{-- =================================================
             BUS + MONTHLY FEE
        ================================================== --}}

        <div class="grid grid-2">

            <div class="form-group">

                <label for="bus_id">
                    Bus
                </label>

                <select
                    name="bus_id"
                    id="bus_id"
                >

                    <option value="">
                        -- No Bus Assigned --
                    </option>

                    @foreach($buses as $bus)

                        <option
                            value="{{ $bus->id }}"
                            {{ (string) old(
                                'bus_id',
                                $student->bus_id ?? ''
                            ) === (string) $bus->id
                                ? 'selected'
                                : ''
                            }}
                        >

                            {{ $bus->bus_number }}

                            @if($bus->registration_number)

                                — {{ $bus->registration_number }}

                            @endif

                        </option>

                    @endforeach

                </select>

                @if($buses->isEmpty())

                    <div class="text-muted"
                         style="font-size:12px; margin-top:5px;">

                        No active buses are currently available.

                        You can add or activate a bus from the
                        Buses module.

                    </div>

                @endif

                @error('bus_id')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            <div class="form-group">

                <label for="monthly_fee">

                    Monthly Fee
                    <span class="text-danger">*</span>

                </label>

                <input
                    type="number"
                    name="monthly_fee"
                    id="monthly_fee"
                    step="0.01"
                    min="0"
                    value="{{ old(
                        'monthly_fee',
                        $student->monthly_fee ?? '0.00'
                    ) }}"
                    placeholder="0.00"
                    required
                >

                @error('monthly_fee')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>

        </div>


        {{-- =================================================
             START DATE + ACTIVE
        ================================================== --}}

        <div class="grid grid-2">

            <div class="form-group">

                <label for="start_date">
                    Start Date
                </label>

                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    value="{{ old(
                        'start_date',
                        isset($student) && $student->start_date
                            ? $student->start_date->format('Y-m-d')
                            : ''
                    ) }}"
                >

                @error('start_date')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            <div class="form-group">

                <label>
                    Student Status
                </label>

                <div style="margin-top:10px;">

                    <label
                        style="
                            display:flex;
                            align-items:center;
                            gap:8px;
                            cursor:pointer;
                        "
                    >

                        <input
                            type="checkbox"
                            name="active"
                            value="1"
                            {{ old(
                                'active',
                                isset($student)
                                    ? $student->active
                                    : true
                            ) ? 'checked' : '' }}
                        >

                        <span>
                            Active Student
                        </span>

                    </label>

                </div>

                <div class="text-muted"
                     style="font-size:12px; margin-top:5px;">

                    Inactive students remain in the system but
                    are not treated as active students.

                </div>

                @error('active')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>

        </div>


        {{-- =================================================
             NOTES
        ================================================== --}}

        <div class="form-group">

            <label for="notes">
                Notes
            </label>

            <textarea
                name="notes"
                id="notes"
                rows="5"
                placeholder="Optional notes about the student..."
            >{{ old(
                'notes',
                $student->notes ?? ''
            ) }}</textarea>

            @error('notes')

                <div class="text-danger"
                     style="font-size:12px; margin-top:5px;">

                    {{ $message }}

                </div>

            @enderror

        </div>


        {{-- =================================================
             INFORMATION
        ================================================== --}}

        <div class="alert alert-info mb-3">

            <span>ⓘ</span>

            <div>

                <strong>
                    Student Account
                </strong>

                <div style="margin-top:4px;">

                    Monthly fees are used when generating fee
                    records. Payments and fee allocations are
                    tracked separately from the student's basic
                    information.

                </div>

            </div>

        </div>


        {{-- =================================================
             ACTIONS
        ================================================== --}}

        <div class="form-actions">

            <a
                href="{{ route('students.index') }}"
                class="btn btn-secondary"
            >

                Cancel

            </a>


            <button
                type="submit"
                class="btn btn-primary"
            >

                {{ isset($student)
                    ? 'Update Student'
                    : 'Save Student'
                }}

            </button>

        </div>

    </form>

</div>


</div>

@endsection
