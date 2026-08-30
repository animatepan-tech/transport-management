@extends('layouts.app')

@section('title', 'WhatsApp Log')
@section('page_heading', 'WhatsApp Log')

@section('content')

<div class="page-header">

    <div>

        <h1>WhatsApp Log</h1>

        <p>
            View details of a previously generated WhatsApp communication.
        </p>

    </div>

    <div class="page-actions">

        <a href="{{ route('whatsapp.index') }}"
           class="btn btn-secondary">

            ← WhatsApp History

        </a>

    </div>

</div>


<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">

                {{ $whatsappLog->student->student_name
                    ?? 'Unknown Student' }}

            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                WhatsApp communication record

            </div>

        </div>


        @if($whatsappLog->status === 'sent')

            <span class="badge badge-success">
                Sent
            </span>

        @elseif($whatsappLog->status === 'queued')

            <span class="badge badge-warning">
                Queued
            </span>

        @elseif($whatsappLog->status === 'failed')

            <span class="badge badge-danger">
                Failed
            </span>

        @else

            <span class="badge">
                {{ ucfirst($whatsappLog->status) }}
            </span>

        @endif

    </div>


    <div class="card-body">

        <div class="grid grid-2">

            <div>

                <div class="balance-label">
                    Student
                </div>

                <strong>
                    {{ $whatsappLog->student->student_name
                        ?? '-' }}
                </strong>

            </div>


            <div>

                <div class="balance-label">
                    Parent
                </div>

                <strong>
                    {{ $whatsappLog->student->parent_name
                        ?? '-' }}
                </strong>

            </div>


            <div>

                <div class="balance-label">
                    WhatsApp Number
                </div>

                <strong>
                    {{ $whatsappLog->phone ?? '-' }}
                </strong>

            </div>


            <div>

                <div class="balance-label">
                    Message Type
                </div>

                <strong>

                    {{ ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $whatsappLog->message_type
                        )
                    ) }}

                </strong>

            </div>


            <div>

                <div class="balance-label">
                    Template
                </div>

                <strong>
                    {{ $whatsappLog->template_name ?? '-' }}
                </strong>

            </div>


            <div>

                <div class="balance-label">
                    Balance At Send
                </div>

                <strong>

                    ₹{{ number_format(
                        (float) $whatsappLog->balance_at_send,
                        2
                    ) }}

                </strong>

            </div>


            <div>

                <div class="balance-label">
                    Fee
                </div>

                @if($whatsappLog->fee)

                    <strong>

                        {{ $whatsappLog->fee->period_start
                            ? $whatsappLog->fee->period_start->format('d M Y')
                            : '-' }}

                        –

                        {{ $whatsappLog->fee->period_end
                            ? $whatsappLog->fee->period_end->format('d M Y')
                            : '-' }}

                    </strong>

                @else

                    <span class="text-muted">
                        No specific fee
                    </span>

                @endif

            </div>


            <div>

                <div class="balance-label">
                    Created
                </div>

                <strong>

                    {{ $whatsappLog->created_at
                        ? $whatsappLog->created_at->format('d M Y h:i A')
                        : '-' }}

                </strong>

            </div>


            <div>

                <div class="balance-label">
                    Sent At
                </div>

                <strong>

                    {{ $whatsappLog->sent_at
                        ? $whatsappLog->sent_at->format('d M Y h:i A')
                        : '-' }}

                </strong>

            </div>


            @if($whatsappLog->provider_message_id)

                <div>

                    <div class="balance-label">
                        Provider Message ID
                    </div>

                    <strong>
                        {{ $whatsappLog->provider_message_id }}
                    </strong>

                </div>

            @endif


            @if($whatsappLog->error_message)

                <div>

                    <div class="balance-label">
                        Error
                    </div>

                    <strong class="text-danger">
                        {{ $whatsappLog->error_message }}
                    </strong>

                </div>

            @endif

        </div>


        <div class="form-actions mt-3">

            <a href="{{ route('whatsapp.index') }}"
               class="btn btn-secondary">

                Back

            </a>


            <form method="POST"
                  action="{{ route(
                      'whatsapp.destroy',
                      $whatsappLog
                  ) }}"
                  onsubmit="return confirm('Delete this WhatsApp log?');">

                @csrf

                @method('DELETE')

                <button type="submit"
                        class="btn btn-danger">

                    Delete Log

                </button>

            </form>

        </div>

    </div>

</div>

@endsection