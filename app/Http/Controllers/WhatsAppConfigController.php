<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class WhatsAppConfigController extends Controller
{
    /**
     * Show WhatsApp configuration.
     */
    public function show(): JsonResponse
    {
        $config = WhatsAppConfig::latest('id')->first();

        if (!$config) {
            return response()->json([
                'configured' => false,
                'config' => null,
            ]);
        }

        return response()->json([
            'configured' => true,
            'config' => [
                'id' => $config->id,
                'business_account_id' => $config->business_account_id,
                'phone_number_id' => $config->phone_number_id,
                'display_phone_number' => $config->display_phone_number,
                'api_version' => $config->api_version,
                'is_enabled' => $config->is_enabled,
                'connection_status' => $config->connection_status,
                'last_connection_test_at' =>
                    $config->last_connection_test_at?->format('Y-m-d H:i:s'),
                'last_connection_error' =>
                    $config->last_connection_error,
                'has_access_token' => !empty($config->access_token),
            ],
        ]);
    }

    /**
     * Save WhatsApp configuration.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_account_id' => [
                'required',
                'string',
                'max:255',
            ],

            'phone_number_id' => [
                'required',
                'string',
                'max:255',
            ],

            'display_phone_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'access_token' => [
                'nullable',
                'string',
            ],

            'api_version' => [
                'required',
                'string',
                'max:20',
            ],

            'is_enabled' => [
                'boolean',
            ],
        ]);

        $config = WhatsAppConfig::latest('id')->first();

        /*
         * If editing an existing configuration and no new token
         * was supplied, preserve the existing encrypted token.
         */
        if ($config) {
            $updateData = [
                'business_account_id' =>
                    $validated['business_account_id'],

                'phone_number_id' =>
                    $validated['phone_number_id'],

                'display_phone_number' =>
                    $validated['display_phone_number'] ?? null,

                'api_version' =>
                    $validated['api_version'],

                'is_enabled' =>
                    $validated['is_enabled'] ?? true,

                'connection_status' =>
                    'DISCONNECTED',

                'last_connection_error' =>
                    null,
            ];

            if (!empty($validated['access_token'])) {
                $updateData['access_token'] =
                    $validated['access_token'];
            }

            $config->update($updateData);
        } else {
            if (empty($validated['access_token'])) {
                throw ValidationException::withMessages([
                    'access_token' =>
                        'Access token is required for the first configuration.',
                ]);
            }

            $config = WhatsAppConfig::create([
                'business_account_id' =>
                    $validated['business_account_id'],

                'phone_number_id' =>
                    $validated['phone_number_id'],

                'display_phone_number' =>
                    $validated['display_phone_number'] ?? null,

                'access_token' =>
                    $validated['access_token'],

                'api_version' =>
                    $validated['api_version'],

                'is_enabled' =>
                    $validated['is_enabled'] ?? true,

                'connection_status' =>
                    'DISCONNECTED',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp configuration saved successfully.',
        ]);
    }

    /**
     * Test Meta WhatsApp connection.
     */
    public function test(WhatsAppService $whatsapp): JsonResponse
    {
        $result = $whatsapp->testConnection();

        if (!$result['success']) {
            return response()->json(
                $result,
                422
            );
        }

        return response()->json($result);
    }

    /**
     * Enable or disable WhatsApp sending.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'is_enabled' => [
                'required',
                'boolean',
            ],
        ]);

        $config = WhatsAppConfig::latest('id')->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp is not configured.',
            ], 422);
        }

        $config->update([
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return response()->json([
            'success' => true,
            'is_enabled' => $config->is_enabled,
            'message' => $config->is_enabled
                ? 'WhatsApp sending enabled.'
                : 'WhatsApp sending disabled.',
        ]);
    }
    public function page()
{
    return view('whatsapp.config');
}
}