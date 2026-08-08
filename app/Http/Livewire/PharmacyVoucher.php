<?php

namespace App\Http\Livewire;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\RadAcct;
use App\Models\RadCheck;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class PharmacyVoucher extends Component
{
    public string $step = 'invoice'; // invoice | success

    public string $invoiceNumber = '';
    public string $error         = '';

    public ?string $expiresAt     = null;
    public ?string $validityHours = null;

    // Captive portal context (passed from MikroTik redirect)
    public ?string $linkLogin = null;
    public ?string $mac       = null;
    public ?string $ip        = null;
    public ?string $router    = null;

    public function mount(): void
    {
        $this->linkLogin = request()->query('link-login')
            ?? request()->query('link-login-only')
            ?? request()->query('link_login');

        $this->mac    = request()->query('mac');
        $this->ip     = request()->query('ip');
        $this->router = request()->query('router');
    }

    // ── Step: invoice ────────────────────────────────────────────

    public function validateInvoice(): void
    {
        $this->validate(['invoiceNumber' => 'required|string|max:100']);

        // Receipts are uppercase; normalise so casing/spacing off the printed
        // receipt does not cause a false rejection, and so the same string is
        // used consistently as the RADIUS identity.
        $invoice = strtoupper(preg_replace('/\s+/', '', trim($this->invoiceNumber)));

        // If we already have an active, non-expired radcheck for this receipt,
        // skip the BasmelCare API call entirely. This allows the same receipt to
        // reconnect across multiple routers at the same location — the API marks
        // the receipt as "used" after the first call and would reject subsequent ones.
        $existing = RadCheck::where('username', $invoice)
            ->where('attribute', 'Cleartext-Password')
            ->first();

        if ($existing) {
            $expiry = RadCheck::where('username', $invoice)
                ->where('attribute', 'Expiration')
                ->first();

            $isExpired = $expiry && Carbon::createFromFormat('d M Y H:i', $expiry->value)->isPast();

            if (! $isExpired) {
                // MAC binding: only the original device can reconnect without re-validating.
                // Since all routers share the same SSID, the device presents the same MAC
                // on every router — so a different MAC means a different device (possible sharing).
                if ($this->mac) {
                    $boundDevice = Device::where('meta->pharmacy_invoice', $invoice)->first();
                    if ($boundDevice && strtoupper($this->mac) !== strtoupper($boundDevice->mac)) {
                        $this->error = 'This receipt has already been used on a different device.';
                        return;
                    }
                }

                $this->invoiceNumber = $invoice;
                $this->activate();
                return;
            }
        }

        $apiUrl = AppSetting::get('basmelcare_api_url', '');
        $apiKey = AppSetting::get('basmelcare_api_key', '');

        if (! $apiUrl || ! $apiKey) {
            $this->error = 'Pharmacy integration is not configured. Please contact support.';
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->post($apiUrl, ['invoice_number' => $invoice]);

            if (! $response->successful() || ! $response->json('valid')) {
                $this->error = $response->json('message', 'Invalid or already used receipt number.');
                return;
            }

            // Use wifi_code as RADIUS username when present (new sales).
            // Fall back to invoice_number for old sales that predate the wifi_code column.
            $wifiCode = $response->json('wifi_code');
            $this->invoiceNumber = strtoupper($wifiCode ?? $response->json('invoice_number', $invoice));
            $this->expiresAt     = $response->json('expires_at');
            $this->validityHours = $response->json('validity_hours', 24);
            $this->error         = '';

            // No OTP — the receipt itself is the access token. Connect straight away.
            $this->activate();
        } catch (\Throwable $e) {
            Log::error('[PharmacyVoucher] API call failed: ' . $e->getMessage());
            $this->error = 'Could not reach BasmelCare. Please try again.';
        }
    }

    // ── Activate internet access ─────────────────────────────────

    private function activate(): void
    {
        // The invoice number is the RADIUS username (anonymous — no user account
        // is created for walk-in pharmacy customers). Revocation later targets
        // exactly this username.
        $radUsername = $this->invoiceNumber;

        // Reuse the existing password on reconnect so the same receipt keeps a
        // stable credential across the 24h window.
        $existing = RadCheck::where('username', $radUsername)
            ->where('attribute', 'Cleartext-Password')
            ->first();
        $radPassword = $existing?->value ?? Str::random(12);

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radPassword]
        );

        // Close any open radacct sessions on other routers so FreeRADIUS does not
        // count them against Simultaneous-Use when the device switches routers.
        // MikroTik does not always send Accounting-Stop when a device walks away,
        // leaving ghost sessions that block re-authentication elsewhere.
        $staleQuery = RadAcct::forUser($radUsername)->whereNull('acctstoptime');
        if ($this->router) {
            $staleQuery->where('nas_identifier', '!=', $this->router);
        }
        $staleQuery->update([
            'acctstoptime'       => now(),
            'acctterminatecause' => 'Admin-Reset',
        ]);

        // One active session at a time — stale sessions above are cleared first.
        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => '1']
        );

        // Expiration comes from BasmelCare (measured from first redemption, so it
        // is not extended by reconnecting).
        if ($this->expiresAt) {
            $expiry = Carbon::parse($this->expiresAt);
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $expiry->format('d M Y H:i')]
            );
        }

        // Track the device (no user_id — this is an anonymous pharmacy session).
        if ($this->mac) {
            Device::updateOrCreate(
                ['mac' => strtoupper($this->mac)],
                [
                    'user_id'      => null,
                    'ip'           => $this->ip ?? request()->ip(),
                    'user_agent'   => request()->userAgent(),
                    'first_seen'   => now(),
                    'last_seen'    => now(),
                    'is_connected' => true,
                    'meta'         => ['pharmacy_invoice' => $radUsername],
                ]
            );
        }

        // Bridge to MikroTik if we arrived via the captive portal.
        if ($this->linkLogin) {
            session([
                'bridge_username'   => $radUsername,
                'bridge_password'   => $radPassword,
                'bridge_link_login' => $this->linkLogin,
                'bridge_link_orig'  => route('captive.connected'),
                'bridge_mac'        => $this->mac,
                'bridge_ip'         => $this->ip,
                'bridge_router'     => $this->router,
                'bridge_completed'  => true,
            ]);

            $this->redirect(route('captive.bridge'));
            return;
        }

        // Direct access (not on captive portal) — show success.
        $this->step  = 'success';
        $this->error = '';
    }

    public function goBack(): void
    {
        $this->step          = 'invoice';
        $this->invoiceNumber = '';
        $this->error         = '';
    }

    public function render()
    {
        return view('livewire.pharmacy-voucher');
    }
}
