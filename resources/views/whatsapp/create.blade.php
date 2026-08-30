@extends('layouts.app')

@section('title', 'Send WhatsApp Message')
@section('page_heading', 'Send WhatsApp Message')

@section('content')

<div class="page-header">

    <div>

        <h1>Send WhatsApp Message</h1>

        <p>
            Select a student, prepare the message, and open WhatsApp.
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
     FORM
     ========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Message Details
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                WhatsApp Web or the WhatsApp application will open
                with the message prepared.

            </div>

        </div>

    </div>


    <div class="card-body">

        <form method="POST"
              action="{{ route('whatsapp.store') }}">

            @csrf


            {{-- =================================================
                 STUDENT
                 ================================================= --}}

            <div class="form-group">

                <label for="student_id">

                    Student
                    <span class="text-danger">*</span>

                </label>


                <select
                    name="student_id"
                    id="student_id"
                    required
                >

                    <option value="">
                        -- Select Student --
                    </option>


                    @foreach($students as $student)

                        <option
                            value="{{ $student->id }}"
                            data-phone="{{ $student->whatsapp_number }}"
                            data-name="{{ $student->student_name }}"
                            {{ old(
                                'student_id',
                                $selectedStudent?->id
                            ) == $student->id
                                ? 'selected'
                                : '' }}
                        >

                            {{ $student->student_name }}

                            —

                            {{ $student->bus->bus_number ?? 'No Bus' }}

                            @if($student->whatsapp_number)

                                — {{ $student->whatsapp_number }}

                            @endif

                        </option>

                    @endforeach

                </select>


                @error('student_id')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            <div class="grid grid-2">


                {{-- =================================================
                     WHATSAPP NUMBER
                     ================================================= --}}

                <div class="form-group">

                    <label for="whatsapp_number">

                        WhatsApp Number
                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        name="whatsapp_number"
                        id="whatsapp_number"
                        value="{{ old(
                            'whatsapp_number',
                            $selectedStudent?->whatsapp_number
                        ) }}"
                        maxlength="30"
                        placeholder="9876543210"
                        required
                    >


                    <div class="text-muted"
                         style="font-size:11px; margin-top:5px;">

                        Enter a 10-digit Indian mobile number
                        or a number with country code.

                    </div>


                    @error('whatsapp_number')

                        <div class="text-danger"
                             style="font-size:12px; margin-top:5px;">

                            {{ $message }}

                        </div>

                    @enderror

                </div>


                {{-- =================================================
                     MESSAGE TYPE
                     ================================================= --}}

                <div class="form-group">

                    <label for="message_type">

                        Message Type
                        <span class="text-danger">*</span>

                    </label>


                    <select
                        name="message_type"
                        id="message_type"
                        required
                    >

                        <option
                            value="general"
                            {{ old(
                                'message_type',
                                'general'
                            ) === 'general'
                                ? 'selected'
                                : '' }}
                        >
                            General
                        </option>

                        <option
                            value="fee_reminder"
                            {{ old(
                                'message_type'
                            ) === 'fee_reminder'
                                ? 'selected'
                                : '' }}
                        >
                            Fee Reminder
                        </option>

                        <option
                            value="payment_confirmation"
                            {{ old(
                                'message_type'
                            ) === 'payment_confirmation'
                                ? 'selected'
                                : '' }}
                        >
                            Payment Confirmation
                        </option>

                        <option
                            value="notice"
                            {{ old(
                                'message_type'
                            ) === 'notice'
                                ? 'selected'
                                : '' }}
                        >
                            Notice
                        </option>

                    </select>


                    @error('message_type')

                        <div class="text-danger"
                             style="font-size:12px; margin-top:5px;">

                            {{ $message }}

                        </div>

                    @enderror

                </div>

            </div>


            {{-- =================================================
                 MESSAGE
                 ================================================= --}}

            <div class="form-group">

                <label for="message">

                    Message
                    <span class="text-danger">*</span>

                </label>


                <textarea
                    name="message"
                    id="message"
                    rows="8"
                    maxlength="5000"
                    placeholder="Enter your WhatsApp message..."
                    required
                >{{ old('message') }}</textarea>


                <div class="text-muted"
                     style="font-size:11px; margin-top:5px;">

                    Maximum 5000 characters.

                </div>


                @error('message')

                    <div class="text-danger"
                         style="font-size:12px; margin-top:5px;">

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- =================================================
                 MESSAGE TEMPLATES
                 ================================================= --}}

            <div class="card mb-3">

                <div class="card-header">

                    <div>

                        <h3 class="card-title">
                            Quick Templates
                        </h3>

                    </div>

                </div>


                <div class="card-body">

                    <div class="grid grid-2">

                        <button
                            type="button"
                            class="btn btn-secondary template-btn"
                            data-template="fee"
                        >

                            Fee Reminder

                        </button>


                        <button
                            type="button"
                            class="btn btn-secondary template-btn"
                            data-template="payment"
                        >

                            Payment Confirmation

                        </button>


                        <button
                            type="button"
                            class="btn btn-secondary template-btn"
                            data-template="general"
                        >

                            General Message

                        </button>


                        <button
                            type="button"
                            class="btn btn-secondary template-btn"
                            data-template="notice"
                        >

                            Transport Notice

                        </button>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 INFORMATION
                 ================================================= --}}

            <div class="alert alert-info mb-3">

                <span>ⓘ</span>

                <div>

                    <strong>
                        How this works
                    </strong>

                    <div style="margin-top:5px;">

                        1. Select a student.

                        <br>

                        2. Confirm the WhatsApp number.

                        <br>

                        3. Enter or select a message.

                        <br>

                        4. Click "Open WhatsApp".

                        <br>

                        5. WhatsApp will open with the message
                        ready to send.

                    </div>

                    <div style="margin-top:8px;">

                        <strong>Important:</strong>
                        This module does not claim delivery confirmation.
                        The actual sending is completed through WhatsApp.

                    </div>

                </div>

            </div>


            {{-- =================================================
                 ACTIONS
                 ================================================= --}}

            <div class="form-actions">

                <a href="{{ route('whatsapp.index') }}"
                   class="btn btn-secondary">

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Open WhatsApp

                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     JAVASCRIPT
     ========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const studentSelect =
        document.getElementById('student_id');

    const phoneInput =
        document.getElementById('whatsapp_number');

    const messageInput =
        document.getElementById('message');

    const typeSelect =
        document.getElementById('message_type');


    /*
    |--------------------------------------------------------------------------
    | Student selection
    |--------------------------------------------------------------------------
    */

    studentSelect.addEventListener('change', function () {

        const option =
            this.options[this.selectedIndex];

        if (!option) {
            return;
        }

        const phone =
            option.getAttribute('data-phone') || '';

        phoneInput.value = phone;

    });


    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.template-btn')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const selectedOption =
                    studentSelect.options[
                        studentSelect.selectedIndex
                    ];

                const studentName =
                    selectedOption
                        ? (
                            selectedOption
                                .getAttribute('data-name')
                            || 'Parent'
                        )
                        : 'Parent';


                const template =
                    this.getAttribute('data-template');


                if (template === 'fee') {

                    typeSelect.value =
                        'fee_reminder';

                    messageInput.value =
`Dear Parent,

This is a friendly reminder regarding the pending transport fee for ${studentName}.

Please arrange the payment at your earliest convenience.

Thank you.
Transport Management`;

                }


                if (template === 'payment') {

                    typeSelect.value =
                        'payment_confirmation';

                    messageInput.value =
`Dear Parent,

We have received your transport fee payment for ${studentName}.

Thank you for your payment.

Transport Management`;

                }


                if (template === 'general') {

                    typeSelect.value =
                        'general';

                    messageInput.value =
`Dear Parent,

This is a message regarding ${studentName}'s transport service.

Thank you.
Transport Management`;

                }


                if (template === 'notice') {

                    typeSelect.value =
                        'notice';

                    messageInput.value =
`Dear Parent,

Please note the following transport notice regarding ${studentName}.

Thank you for your cooperation.
Transport Management`;

                }

            });

        });

});

</script>

@endsection