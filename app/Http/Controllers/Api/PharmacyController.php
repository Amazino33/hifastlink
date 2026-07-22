<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\RadCheck;
use App\Models\RadReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    /**
     * Revoke the Wi-Fi access tied to a BasmelCare receipt.
     *
     * Called by BasmelCare when staff revoke a receipt (e.g. after a refund).
     * The invoice number is the RADIUS username, so deleting its RADIUS rows
     * means the next authentication attempt is rejected (passive revocation) —
     * the device can finish its current session but cannot reconnect.
     */
    public function revoke(Request $request): JsonResponse
    {
        // Shared secret — BasmelCare sends the same key HiFastLink stores for it.
        $expectedKey = AppSetting::get('basmelcare_api_key', '');

        if (! $expectedKey || $request->header('X-API-Key') !== $expectedKey) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate(['invoice_number' => 'required|string']);

        $username = strtoupper(preg_replace('/\s+/', '', trim($request->invoice_number)));

        $removed = RadCheck::where('username', $username)->delete();
        RadReply::where('username', $username)->delete();

        // Mark any device that connected on this receipt as disconnected.
        Device::where('meta->pharmacy_invoice', $username)
            ->update(['is_connected' => false]);

        return response()->json([
            'ok'             => true,
            'invoice_number' => $username,
            'revoked'        => $removed > 0,
        ]);
    }
}
