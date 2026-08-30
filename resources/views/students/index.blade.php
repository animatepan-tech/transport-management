blade
@extends('layouts.app')

@section('title', 'Students')
@section('page_heading', 'Students')

@section('content')

<div class="page-header">

    <div>

        <h1>Students</h1>

        <p>
            Manage students, bus assignments, monthly fees
            and account status.
        </p>

    </div>


    <div>

        <a
            href="{{ route('students.create') }}"
            class="btn btn-primary"
        >
            + Add Student
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
   SUMMARY
========================================================= --}}

<div class="stats-grid mb-3">

    <div class="stat-card">

        <div class="stat-label">
            Total Students
        </div>

        <div class="stat-value">
            {{ $totalStudents }}
        </div>

        <div class="stat-meta">
            All student records
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Active Students
        </div>

        <div class="stat-value amount-paid">
            {{ $activeStudents }}
        </div>

        <div class="stat-meta">
            Currently active
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Students With Due
        </div>

        <div class="stat-value amount-due">
            {{ $studentsWithDue }}
        </div>

        <div class="stat-meta">
            Outstanding accounts
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Students With Advance
        </div>

        <div class="stat-value amount-profit">
            {{ $studentsWithAdvance }}
        </div>

        <div class="stat-meta">
            Advance credit
        </div>

    </div>

</div>


{{-- =========================================================
   FILTERS
========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Student Filters
            </h2>

            <div
                class="text-muted"
                style="font-size:12px; margin-top:4px;"
            >
                Search and filter student records.
            </div>

        </div>

    </div>


    <div class="card-body">

        <form
            method="GET"
            action="{{ route('students.index') }}"
        >

            <div class="grid grid-3">


                {{-- SEARCH --}}

                <div>

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Name, parent, WhatsApp or stop"
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
                                @selected(
                                    (string) $busId ===
                                    (string) $bus->id
                                )
                            >

                                {{ $bus->bus_number }}

                                @if($bus->registration_number)
                                    — {{ $bus->registration_number }}
                                @endif

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- STATUS --}}

                <div>

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-control"
                    >

                        <option
                            value="all"
                            @selected($status === 'all')
                        >
                            All Students
                        </option>

                        <option
                            value="active"
                            @selected($status === 'active')
                        >
                            Active Only
                        </option>

                        <option
                            value="inactive"
                            @selected($status === 'inactive')
                        >
                            Inactive Only
                        </option>

                    </select>

                </div>

            </div>


            <div
                style="
                    display:flex;
                    gap:10px;
                    margin-top:15px;
                    flex-wrap:wrap;
                "
            >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Apply Filters
                </button>


                <a
                    href="{{ route('students.index') }}"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
   STUDENT TABLE
========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Student Records
            </h2>

            <div
                class="text-muted"
                style="font-size:12px; margin-top:4px;"
            >
                {{ $students->total() }} student record(s)
            </div>

        </div>

    </div>


    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>
                        Student
                    </th>

                    <th>
                        Parent
                    </th>

                    <th>
                        Bus
                    </th>

                    <th>
                        Pickup Stop
                    </th>

                    <th>
                        Monthly Fee
                    </th>

                    <th>
                        Account
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($students as $student)

                    <tr>

                        {{-- STUDENT --}}

                        <td>

                            <strong>
                                {{ $student->student_name }}
                            </strong>

                            @if($student->whatsapp_number)

                                <div
                                    class="text-muted"
                                    style="
                                        font-size:11px;
                                        margin-top:3px;
                                    "
                                >
                                    {{ $student->whatsapp_number }}
                                </div>

                            @endif

                        </td>


                        {{-- PARENT --}}

                        <td>

                            {{ $student->parent_name ?: '—' }}

                        </td>


                        {{-- BUS --}}

                        <td>

                            @if($student->bus)

                                <strong>
                                    {{ $student->bus->bus_number }}
                                </strong>

                                @if($student->bus->registration_number)

                                    <div
                                        class="text-muted"
                                        style="
                                            font-size:11px;
                                            margin-top:3px;
                                        "
                                    >
                                        {{ $student->bus->registration_number }}
                                    </div>

                                @endif

                            @else

                                <span class="badge badge-neutral">
                                    No Bus
                                </span>

                            @endif

                        </td>


                        {{-- PICKUP --}}

                        <td>

                            {{ $student->pickup_stop ?: '—' }}

                        </td>


                        {{-- MONTHLY FEE --}}

                        <td>

                            <strong>

                                ₹{{ number_format(
                                    (float) $student->monthly_fee,
                                    2
                                ) }}

                            </strong>

                        </td>


                        {{-- ACCOUNT --}}

                        <td>

                            @if($student->has_due)

                                <span class="badge badge-danger">
                                    Due
                                </span>

                                <div
                                    class="amount-due"
                                    style="
                                        font-size:11px;
                                        margin-top:4px;
                                    "
                                >

                                    ₹{{ number_format(
                                        $student->due_amount,
                                        2
                                    ) }}

                                </div>

                            @elseif($student->has_advance)

                                <span class="badge badge-success">
                                    Advance
                                </span>

                                <div
                                    class="amount-profit"
                                    style="
                                        font-size:11px;
                                        margin-top:4px;
                                    "
                                >

                                    ₹{{ number_format(
                                        $student->advance_amount,
                                        2
                                    ) }}

                                </div>

                            @else

                                <span class="badge badge-neutral">
                                    Settled
                                </span>

                            @endif

                        </td>


                        {{-- STATUS --}}

                        <td>

                            @if($student->active)

                                <span class="badge badge-success">
                                    Active
                                </span>

                            @else

                                <span class="badge badge-neutral">
                                    Inactive
                                </span>

                            @endif

                        </td>


                        {{-- ACTIONS --}}

                        <td>

                            <div
                                style="
                                    display:flex;
                                    gap:6px;
                                    flex-wrap:wrap;
                                "
                            >

                                <a
                                    href="{{ route(
                                        'students.account',
                                        $student
                                    ) }}"
                                    class="btn btn-secondary btn-sm"
                                >
                                    Account
                                </a>


                                <a
                                    href="{{ route(
                                        'students.edit',
                                        $student
                                    ) }}"
                                    class="btn btn-secondary btn-sm"
                                >
                                    Edit
                                </a>


                                @if(
                                    !$student->fees()->exists()
                                    &&
                                    !$student->payments()->exists()
                                )

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'students.destroy',
                                            $student
                                        ) }}"
                                        onsubmit="return confirm(
                                            'Delete this student? This action cannot be undone.'
                                        );"
                                    >

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="8">

                            <div class="empty-state">

                                <div class="empty-state-icon">
                                    👨‍🎓
                                </div>

                                <p class="empty-state-title">
                                    No Students Found
                                </p>

                                <p class="empty-state-text">
                                    No students match the selected filters.
                                </p>

                                <div style="margin-top:12px;">

                                    <a
                                        href="{{ route(
                                            'students.create'
                                        ) }}"
                                        class="btn btn-primary"
                                    >
                                        + Add Student
                                    </a>

                                </div>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- PAGINATION --}}

    @if($students->hasPages())

        <div
            class="card-body"
            style="
                border-top:1px solid var(--border-color, #e5e7eb);
            "
        >

            {{ $students->links() }}

        </div>

    @endif

</div>

@endsection

