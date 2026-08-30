@extends('layouts.app')

@section('title', 'WhatsApp Configuration')
@section('page_heading', 'WhatsApp Configuration')

@section('content')

<div class="page-header">

    <div>
        <h1>WhatsApp Configuration</h1>

        <p>
            Configure the WhatsApp Business number used to send transport
            communication.
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
     STATUS
     ========================================================= --}}

<div id="statusAlert"
     class="alert"
     style="display:none; margin-bottom:20px;">

    <span id="statusIcon"></span>

    <div id="statusMessage"></div>

</div>


{{-- =========================================================
     CONNECTION STATUS
     ========================================================= --}}

<div class="card mb-3">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                Connection Status
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Current WhatsApp Cloud API connection.

            </div>

        </div>

    </div>


    <div class="card-body">

        <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            flex-wrap:wrap;
        ">

            <div>

                <div class="balance-label">
                    Status
                </div>

                <div id="connectionStatus"
                     style="margin-top:6px;">

                    <span class="badge">
                        Not Configured
                    </span>

                </div>

            </div>


            <div>

                <div class="balance-label">
                    Last Connection Test
                </div>

                <div id="lastConnectionTest"
                     style="margin-top:6px;">

                    -

                </div>

            </div>


            <div>

                <button type="button"
                        id="testConnectionButton"
                        class="btn btn-secondary">

                    Test Connection

                </button>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     CONFIGURATION FORM
     ========================================================= --}}

<div class="card">

    <div class="card-header">

        <div>

            <h2 class="card-title">
                WhatsApp Business API
            </h2>

            <div class="text-muted"
                 style="font-size:12px; margin-top:4px;">

                Enter the credentials provided by Meta.

            </div>

        </div>

    </div>


    <div class="card-body">

        <form id="whatsappConfigForm">

            @csrf


            {{-- Business Account ID --}}

            <div class="form-group mb-3">

                <label for="business_account_id">
                    WhatsApp Business Account ID
                </label>

                <input
                    type="text"
                    id="business_account_id"
                    name="business_account_id"
                    class="form-control"
                    placeholder="Enter WhatsApp Business Account ID"
                    required
                >

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    Your Meta WhatsApp Business Account ID.

                </div>

            </div>


            {{-- Phone Number ID --}}

            <div class="form-group mb-3">

                <label for="phone_number_id">
                    Phone Number ID
                </label>

                <input
                    type="text"
                    id="phone_number_id"
                    name="phone_number_id"
                    class="form-control"
                    placeholder="Enter Phone Number ID"
                    required
                >

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    This is the Meta Phone Number ID, not the
                    display phone number.

                </div>

            </div>


            {{-- Display Phone Number --}}

            <div class="form-group mb-3">

                <label for="display_phone_number">
                    WhatsApp Number
                </label>

                <input
                    type="text"
                    id="display_phone_number"
                    name="display_phone_number"
                    class="form-control"
                    placeholder="+91 88101 01212"
                >

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    The phone number parents will see as the sender.

                </div>

            </div>


            {{-- API Version --}}

            <div class="form-group mb-3">

                <label for="api_version">
                    Meta Graph API Version
                </label>

                <input
                    type="text"
                    id="api_version"
                    name="api_version"
                    class="form-control"
                    value="v23.0"
                    required
                >

            </div>


            {{-- Access Token --}}

            <div class="form-group mb-3">

                <label for="access_token">
                    Access Token
                </label>

                <input
                    type="password"
                    id="access_token"
                    name="access_token"
                    class="form-control"
                    placeholder="Enter access token"
                    autocomplete="new-password"
                >

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    Leave blank when updating the configuration
                    to keep the existing token.

                </div>

            </div>


            {{-- Enable WhatsApp --}}

            <div class="form-group mb-3">

                <label style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                    cursor:pointer;
                ">

                    <input
                        type="checkbox"
                        id="is_enabled"
                        name="is_enabled"
                        value="1"
                    >

                    <span>
                        Enable WhatsApp Sending
                    </span>

                </label>

                <div class="text-muted"
                     style="font-size:11px; margin-top:4px;">

                    When disabled, the application will not send
                    WhatsApp communications.

                </div>

            </div>


            {{-- Buttons --}}

            <div style="
                display:flex;
                gap:10px;
                margin-top:25px;
            ">

                <button type="submit"
                        id="saveButton"
                        class="btn btn-primary">

                    Save Configuration

                </button>

                <button type="button"
                        id="testConnectionButtonBottom"
                        class="btn btn-secondary">

                    Test Connection

                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     LAST ERROR
     ========================================================= --}}

<div id="errorCard"
     class="card"
     style="display:none; margin-top:20px;">

    <div class="card-header">

        <h2 class="card-title">
            Last Connection Error
        </h2>

    </div>

    <div class="card-body">

        <div id="lastError"
             style="
                color:#b42318;
                white-space:pre-wrap;
             ">
        </div>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById(
        'whatsappConfigForm'
    );

    const saveButton = document.getElementById(
        'saveButton'
    );

    const testButton = document.getElementById(
        'testConnectionButton'
    );

    const testButtonBottom = document.getElementById(
        'testConnectionButtonBottom'
    );

    const statusAlert = document.getElementById(
        'statusAlert'
    );

    const statusIcon = document.getElementById(
        'statusIcon'
    );

    const statusMessage = document.getElementById(
        'statusMessage'
    );

    const connectionStatus = document.getElementById(
        'connectionStatus'
    );

    const lastConnectionTest = document.getElementById(
        'lastConnectionTest'
    );

    const errorCard = document.getElementById(
        'errorCard'
    );

    const lastError = document.getElementById(
        'lastError'
    );


    function showAlert(type, message) {

        statusAlert.style.display = 'flex';

        statusAlert.className =
            'alert ' +
            (type === 'success'
                ? 'alert-success'
                : 'alert-danger');

        statusIcon.textContent =
            type === 'success' ? '✓' : '⚠';

        statusMessage.textContent = message;
    }


    function updateStatus(status) {

        let html = '';

        switch (status) {

            case 'CONNECTED':

                html =
                    '<span class="badge badge-success">' +
                    'Connected' +
                    '</span>';

                break;

            case 'ERROR':

                html =
                    '<span class="badge badge-danger">' +
                    'Connection Error' +
                    '</span>';

                break;

            case 'DISCONNECTED':

                html =
                    '<span class="badge badge-warning">' +
                    'Disconnected' +
                    '</span>';

                break;

            default:

                html =
                    '<span class="badge">' +
                    'Not Configured' +
                    '</span>';
        }

        connectionStatus.innerHTML = html;
    }


    async function loadConfiguration() {

        try {

            const response = await fetch(
                '{{ route("whatsapp.configuration.data") }}',
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const result = await response.json();

            if (!result.configured) {

                updateStatus(null);

                return;
            }

            const config = result.config;

            document.getElementById(
                'business_account_id'
            ).value =
                config.business_account_id || '';

            document.getElementById(
                'phone_number_id'
            ).value =
                config.phone_number_id || '';

            document.getElementById(
                'display_phone_number'
            ).value =
                config.display_phone_number || '';

            document.getElementById(
                'api_version'
            ).value =
                config.api_version || 'v23.0';

            document.getElementById(
                'is_enabled'
            ).checked =
                Boolean(config.is_enabled);

            updateStatus(
                config.connection_status
            );

            lastConnectionTest.textContent =
                config.last_connection_test_at || '-';

            if (config.last_connection_error) {

                errorCard.style.display = 'block';

                lastError.textContent =
                    config.last_connection_error;

            }

        } catch (error) {

            showAlert(
                'error',
                'Unable to load WhatsApp configuration.'
            );
        }
    }


    async function testConnection() {

        testButton.disabled = true;

        testButtonBottom.disabled = true;

        testButton.textContent =
            'Testing...';

        try {

            const response = await fetch(
                '{{ route("whatsapp.configuration.test") }}',
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'input[name="_token"]'
                            ).value,

                        'Accept':
                            'application/json'
                    }
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {

                throw new Error(
                    result.message ||
                    'WhatsApp connection failed.'
                );
            }

            showAlert(
                'success',
                result.message ||
                'WhatsApp connection successful.'
            );

            await loadConfiguration();

        } catch (error) {

            showAlert(
                'error',
                error.message
            );

            await loadConfiguration();

        } finally {

            testButton.disabled = false;

            testButtonBottom.disabled = false;

            testButton.textContent =
                'Test Connection';
        }
    }


    form.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            saveButton.disabled = true;

            saveButton.textContent =
                'Saving...';

            const formData =
                new FormData(form);

            const payload = {

                business_account_id:
                    formData.get(
                        'business_account_id'
                    ),

                phone_number_id:
                    formData.get(
                        'phone_number_id'
                    ),

                display_phone_number:
                    formData.get(
                        'display_phone_number'
                    ),

                access_token:
                    formData.get(
                        'access_token'
                    ),

                api_version:
                    formData.get(
                        'api_version'
                    ),

                is_enabled:
                    formData.get(
                        'is_enabled'
                    ) === '1'
            };

            try {

                const response = await fetch(
                    '{{ route("whatsapp.configuration.store") }}',
                    {
                        method: 'POST',

                        headers: {

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                document.querySelector(
                                    'input[name="_token"]'
                                ).value,

                            'Accept':
                                'application/json'
                        },

                        body:
                            JSON.stringify(payload)
                    }
                );

                const result =
                    await response.json();

                if (!response.ok) {

                    const firstError =
                        result.errors
                            ? Object.values(
                                result.errors
                            )[0][0]
                            : result.message;

                    throw new Error(
                        firstError ||
                        'Unable to save configuration.'
                    );
                }

                showAlert(
                    'success',
                    result.message ||
                    'WhatsApp configuration saved successfully.'
                );

                document.getElementById(
                    'access_token'
                ).value = '';

                await loadConfiguration();

            } catch (error) {

                showAlert(
                    'error',
                    error.message
                );

            } finally {

                saveButton.disabled = false;

                saveButton.textContent =
                    'Save Configuration';
            }
        }
    );


    testButton.addEventListener(
        'click',
        testConnection
    );

    testButtonBottom.addEventListener(
        'click',
        testConnection
    );


    loadConfiguration();

});

</script>

@endsection