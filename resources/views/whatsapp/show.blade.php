@extends('layouts.app')

@section('title', 'Send WhatsApp')
@section('page_heading', 'Send WhatsApp')

@section('content')

<div class="page-header">

    <div>

        <h1>Send WhatsApp</h1>

        <p>
            Send a transport fee message to a student's WhatsApp number.
        </p>

    </div>

    <div class="page-actions">

        <a href="{{ route('whatsapp.index') }}"
           class="btn btn-secondary">

            ← WhatsApp History

        </a>

    </div>

</div>


{{-- =========================================================
     ERROR
     ========================================================= --}}

@if(session('error'))

    <div class="alert alert-danger mb-3">

        <span>⚠</span>

        <div>
            {{ session('error') }}
        </div>

    </div>

@endif


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
     STUDENT LIST
     ========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Students
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Select a student to generate a WhatsApp message.

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
                        WhatsApp
                    </th>

                    <th>
                        Account
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($students as $student)

                    @php

                        $due = round(
                            (float) $student->due_amount,
                            2
                        );

                        $advance = round(
                            (float) $student->advance_amount,
                            2
                        );

                    @endphp


                    <tr>

                        <td>

                            <strong>
                                {{ $student->student_name }}
                            </strong>

                        </td>


                        <td>

                            {{ $student->parent_name ?? '-' }}

                        </td>


                        <td>

                            @if($student->bus)

                                {{ $student->bus->bus_number }}

                            @else

                                <span class="text-muted">
                                    No Bus
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($student->whatsapp_number)

                                {{ $student->whatsapp_number }}

                            @else

                                <span class="badge badge-danger">
                                    Missing
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($due > 0.01)

                                <span class="badge badge-danger">

                                    Due ₹{{ number_format(
                                        $due,
                                        2
                                    ) }}

                                </span>

                            @elseif($advance > 0.01)

                                <span class="badge badge-success">

                                    Advance ₹{{ number_format(
                                        $advance,
                                        2
                                    ) }}

                                </span>

                            @else

                                <span class="badge badge-success">
                                    Settled
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($student->whatsapp_number)

                                <form method="POST"
                                      action="{{ route(
                                          'whatsapp.send',
                                          $student
                                      ) }}"
                                      style="display:flex; gap:6px; align-items:center;">

                                    @csrf

                                    <select name="message_type"
                                            style="min-width:120px;">

                                        <option value="due">
                                            Due Reminder
                                        </option>

                                        <option value="reminder">
                                            General Reminder
                                        </option>

                                        <option value="general">
                                            General Message
                                        </option>

                                    </select>


                                    <button type="submit"
                                            class="btn btn-primary">

                                        WhatsApp

                                    </button>

                                </form>

                            @else

                                <span class="text-muted">

                                    Add WhatsApp number in student profile.

                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6"
                            style="text-align:center; padding:30px;">

                            No active students found.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- =========================================================
     INFORMATION
     ========================================================= --}}

<div class="alert alert-info mt-3">

    <span>ⓘ</span>

    <div>

        <strong>
            How this works
        </strong>

        <div style="margin-top:5px;">

            The system calculates the student's current outstanding
            balance using the existing fee and payment-allocation
            accounting system.

            Clicking <strong>WhatsApp</strong> creates a communication
            log and opens WhatsApp with the generated message.

        </div>

    </div>

</div>

@endsection