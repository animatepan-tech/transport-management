@extends('layouts.app')

@section('title', 'WhatsApp')
@section('page_heading', 'WhatsApp')

@section('content')

<div class="page-header">

    <div>

        <h1>WhatsApp</h1>

        <p>
            Manage transport fee reminders and WhatsApp communication history.
        </p>

    </div>

    <div class="page-actions">

    <a href="{{ route('whatsapp.configuration') }}"
       class="btn btn-secondary">

        ⚙ WhatsApp Configuration

    </a>

    <a href="{{ route('whatsapp.create') }}"
       class="btn btn-primary">

        + Send WhatsApp

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
     SUMMARY
     ========================================================= --}}

<div class="grid grid-4 mb-3">

    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Total Messages
            </div>

            <div class="balance-value">
                {{ number_format($totalLogs) }}
            </div>

        </div>

    </div>


    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Sent
            </div>

            <div class="balance-value amount-paid">
                {{ number_format($sentLogs) }}
            </div>

        </div>

    </div>


    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Queued
            </div>

            <div class="balance-value">
                {{ number_format($queuedLogs) }}
            </div>

        </div>

    </div>


    <div class="card">

        <div class="card-body">

            <div class="balance-label">
                Failed
            </div>

            <div class="balance-value amount-due">
                {{ number_format($failedLogs) }}
            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     LOG TABLE
     ========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                WhatsApp History
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Previously generated WhatsApp communications.

            </div>

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

                        <td>

                            {{ $log->created_at
                                ? $log->created_at->format('d M Y') 
                                : '-' }}

                            <div class="text-muted"
                                 style="font-size:11px;">

                                {{ $log->created_at
                                    ? $log->created_at->format('h:i A')
                                    : '' }}

                            </div>

                        </td>


                        <td>

                            <strong>
                                {{ $log->student->student_name ?? 'Unknown Student' }}
                            </strong>

                            @if($log->student && $log->student->bus)

                                <div class="text-muted"
                                     style="font-size:11px;">

                                    Bus {{ $log->student->bus->bus_number }}

                                </div>

                            @endif

                        </td>


                        <td>

                            {{ $log->phone ?? '-' }}

                        </td>


                        <td>

                            @php

                                $typeLabel = ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $log->message_type
                                    )
                                );

                            @endphp

                            <span class="badge badge-info">

                                {{ $typeLabel }}

                            </span>

                        </td>


                        <td class="amount">

                            ₹{{ number_format(
                                (float) $log->balance_at_send,
                                2
                            ) }}

                        </td>


                        <td>

                            @if($log->status === 'sent')

                                <span class="badge badge-success">
                                    Sent
                                </span>

                            @elseif($log->status === 'queued')

                                <span class="badge badge-warning">
                                    Queued
                                </span>

                            @elseif($log->status === 'failed')

                                <span class="badge badge-danger">
                                    Failed
                                </span>

                            @else

                                <span class="badge">
                                    {{ ucfirst($log->status) }}
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($log->sent_at)

                                {{ $log->sent_at->format('d M Y') }}

                                <div class="text-muted"
                                     style="font-size:11px;">

                                    {{ $log->sent_at->format('h:i A') }}

                                </div>

                            @else

                                -

                            @endif

                        </td>


                        <td>

                            <a href="{{ route(
                                'whatsapp.show',
                                $log
                            ) }}"
                               class="btn btn-secondary">

                                View

                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="8"
                            style="text-align:center; padding:30px;">

                            <strong>
                                No WhatsApp messages yet.
                            </strong>

                            <div class="text-muted"
                                 style="margin-top:5px;">

                                Start by sending a payment reminder.

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    @if($logs->hasPages())

        <div style="padding:15px;">

            {{ $logs->links() }}

        </div>

    @endif

</div>

@endsection