@extends('layouts.app')

@section('title', 'WhatsApp')
@section('page_heading', 'WhatsApp')

@section('content')

    {{-- =========================================================
         PAGE HEADER
         ========================================================= --}}

    <div class="page-header">

        <div>

            <h1>
                WhatsApp
            </h1>

            <p>
                Manage transport fee reminders and WhatsApp
                communication history.
            </p>

        </div>


        <div class="page-actions">

            <a
                href="{{ route('whatsapp.create') }}"
                class="btn btn-primary"
            >
                + Send WhatsApp
            </a>

        </div>

    </div>


    {{-- =========================================================
         SUCCESS MESSAGE
         ========================================================= --}}

    @if(session('success'))

        <div class="alert alert-success mb-3">

            <span>
                ✓
            </span>

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

            <span>
                ⚠
            </span>

            <div>
                {{ session('error') }}
            </div>

        </div>

    @endif


    {{-- =========================================================
         SUMMARY CARDS
         ========================================================= --}}

    <div class="grid grid-4 mb-3">


        {{-- TOTAL --}}

        <div class="card">

            <div class="card-body">

                <div class="balance-label">
                    Total Messages
                </div>

                <div class="balance-value">

                    {{ number_format(
                        (int) $totalLogs
                    ) }}

                </div>

            </div>

        </div>


        {{-- SENT --}}

        <div class="card">

            <div class="card-body">

                <div class="balance-label">
                    Sent
                </div>

                <div class="balance-value amount-paid">

                    {{ number_format(
                        (int) $sentLogs
                    ) }}

                </div>

            </div>

        </div>


        {{-- QUEUED --}}

        <div class="card">

            <div class="card-body">

                <div class="balance-label">
                    Queued
                </div>

                <div class="balance-value">

                    {{ number_format(
                        (int) $queuedLogs
                    ) }}

                </div>

            </div>

        </div>


        {{-- FAILED --}}

        <div class="card">

            <div class="card-body">

                <div class="balance-label">
                    Failed
                </div>

                <div class="balance-value amount-due">

                    {{ number_format(
                        (int) $failedLogs
                    ) }}

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         WHATSAPP HISTORY
         ========================================================= --}}

    <div class="card">

        <div class="card-header">

            <div>

                <h2 class="card-title">
                    WhatsApp History
                </h2>

                <div
                    class="text-muted"
                    style="
                        font-size:12px;
                        margin-top:4px;
                    "
                >
                    Previously generated WhatsApp communications.
                </div>

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
                            Date
                        </th>

                        <th>
                            Student
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Balance
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Sent
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($logs as $log)

                        <tr>


                            {{-- =================================================
                                 DATE
                                 ================================================= --}}

                            <td>

                                @if($log->created_at)

                                    {{ $log->created_at->format(
                                        'd M Y'
                                    ) }}

                                    <div
                                        class="text-muted"
                                        style="
                                            font-size:11px;
                                            margin-top:2px;
                                        "
                                    >
                                        {{ $log->created_at->format(
                                            'h:i A'
                                        ) }}
                                    </div>

                                @else

                                    -

                                @endif

                            </td>


                            {{-- =================================================
                                 STUDENT
                                 ================================================= --}}

                            <td>

                                <strong>

                                    {{ $log->student->student_name
                                        ?? 'Unknown Student'
                                    }}

                                </strong>


                                @if(
                                    $log->student
                                    && $log->student->bus
                                )

                                    <div
                                        class="text-muted"
                                        style="
                                            font-size:11px;
                                            margin-top:2px;
                                        "
                                    >
                                        Bus
                                        {{ $log->student->bus->bus_number }}
                                    </div>

                                @endif

                            </td>


                            {{-- =================================================
                                 PHONE
                                 ================================================= --}}

                            <td>

                                {{ $log->phone ?? '-' }}

                            </td>


                            {{-- =================================================
                                 MESSAGE TYPE
                                 ================================================= --}}

                            <td>

                                @php

                                    $typeLabel = ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) $log->message_type
                                        )
                                    );

                                @endphp


                                <span class="badge badge-info">

                                    {{ $typeLabel }}

                                </span>

                            </td>


                            {{-- =================================================
                                 BALANCE
                                 ================================================= --}}

                            <td class="amount">

                                ₹{{ number_format(
                                    (float) (
                                        $log->balance_at_send
                                        ?? 0
                                    ),
                                    2
                                ) }}

                            </td>


                            {{-- =================================================
                                 STATUS
                                 ================================================= --}}

                            <td>

                                @if($log->status === 'sent')

                                    <span
                                        class="badge badge-success"
                                    >
                                        Sent
                                    </span>

                                @elseif($log->status === 'queued')

                                    <span
                                        class="badge badge-warning"
                                    >
                                        Queued
                                    </span>

                                @elseif($log->status === 'failed')

                                    <span
                                        class="badge badge-danger"
                                    >
                                        Failed
                                    </span>

                                @else

                                    <span class="badge">

                                        {{ ucfirst(
                                            (string) $log->status
                                        ) }}

                                    </span>

                                @endif

                            </td>


                            {{-- =================================================
                                 SENT TIME
                                 ================================================= --}}

                            <td>

                                @if($log->sent_at)

                                    {{ $log->sent_at->format(
                                        'd M Y'
                                    ) }}

                                    <div
                                        class="text-muted"
                                        style="
                                            font-size:11px;
                                            margin-top:2px;
                                        "
                                    >
                                        {{ $log->sent_at->format(
                                            'h:i A'
                                        ) }}
                                    </div>

                                @else

                                    -

                                @endif

                            </td>


                            {{-- =================================================
                                 ACTION
                                 ================================================= --}}

                            <td>

                                <a
                                    href="{{ route(
                                        'whatsapp.show',
                                        $log
                                    ) }}"
                                    class="btn btn-secondary"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                style="
                                    text-align:center;
                                    padding:40px 20px;
                                "
                            >

                                <strong>
                                    No WhatsApp messages yet.
                                </strong>

                                <div
                                    class="text-muted"
                                    style="
                                        margin-top:6px;
                                    "
                                >
                                    Start by sending a WhatsApp message.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
             PAGINATION
             ===================================================== --}}

        @if($logs->hasPages())

            <div
                style="
                    padding:15px;
                    border-top:1px solid #e5e7eb;
                "
            >

                {{ $logs->links() }}

            </div>

        @endif

    </div>

@endsection