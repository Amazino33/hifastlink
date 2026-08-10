<?php

namespace App\Http\Livewire;

use App\Models\Device;
use App\Models\PartnerIntegration;
use App\Models\RadAcct;
use App\Models\RadCheck;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class VoucherPortal extends Component
{
    public string $step = 'code'; // code | success

    public string $slug        = '';
    public string $code        = '';
    public string $error       = '';
    public ?string $expiresAt  = null;

    public ?string $linkLogin  = null;
    public ?string $mac        = null;
    public ?string $ip         = null;
    public ?string $router     = null;

    private ?PartnerIntegration $integration = null;

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        $this->linkLogin = request()->query('link-login')
            ?? request()->query('link-login-only')
            ?? request()->query('link_login');

        $this->mac    = request()->query('mac');
        $this->ip     = request()->query('ip');
        $this->router = request()->query('router');
    }

    private function loadIntegration(): ?PartnerIntegration
    {
        if (! $this->integration) {
            $this->integration = PartnerIntegration::findBySlug($this->slug);
        }
        return $this->integration;
    }

    public function validateCode(): void
    {
        $this->validate(['code' => 'required|string|max:100']);

        $integration = $this->loadIntegration();

        if (! $integration) {
            $this->error = 'This integration is not available.';
            return;
        }

        $code = strtoupper(preg_replace('/\s+/', '', trim($this->code)));

        // Reconnect without re-calling the partner API if RADIUS entry exists and is valid.
        $existing = RadCheck::where('username', $code)->where('attribute', 'Cleartext-Password')->first();
        if ($existing) {
            $expiry    = RadCheck::where('username', $code)->where('attribute', 'Expiration')->first();
            $isExpired = $expiry && Carbon::createFromFormat('d M Y H:i', $expiry->value)->isPast();

            if (! $isExpired) {
                if ($this->mac) {
                    $bound = Device::where('meta->partner_code', $code)->first();
                    if ($bound && strtoupper($this->mac) !== strtoupper($bound->mac)) {
                        $this->error = 'This code has already been used on a different device.';
                        return;
                    }
                }
                $this->code = $code;
                $this->activate($integration);
                return;
            }
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $integration->api_key])
                ->post($integration->api_url, [$integration->code_field => $code]);

            if (! $response->successful() || ! $response->json('valid')) {
                $this->error = $response->json('message', 'Invalid or expired code. Check your receipt and try again.');
                return;
            }

            // Use whatever identifier the partner returns as the RADIUS username.
            $returnedCode    = $response->json($integration->code_field) ?? $response->json('code') ?? $code;
            $this->code      = strtoupper($returnedCode);
            $this->expiresAt = $response->json('expires_at');
            $this->error     = '';

            $this->activate($integration);
        } catch (\Throwable $e) {
            Log::error("[VoucherPortal:{$this->slug}] API call failed: " . $e->getMessage());
            $this->error = 'Could not reach the service. Please try again.';
        }
    }

    private function activate(PartnerIntegration $integration): void
    {
        $radUsername = $this->code;

        $existing    = RadCheck::where('username', $radUsername)->where('attribute', 'Cleartext-Password')->first();
        $radPassword = $existing?->value ?? Str::random(12);

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radPassword]
        );

        $staleQuery = RadAcct::forUser($radUsername)->whereNull('acctstoptime');
        if ($this->router) {
            $staleQuery->where('nas_identifier', '!=', $this->router);
        }
        $staleQuery->update(['acctstoptime' => now(), 'acctterminatecause' => 'Admin-Reset']);

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => '1']
        );

        if ($this->expiresAt) {
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => Carbon::parse($this->expiresAt)->format('d M Y H:i')]
            );
        }

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
                    'meta'         => ['partner_code' => $radUsername, 'integration' => $this->slug],
                ]
            );
        }

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

        $this->step  = 'success';
        $this->error = '';
    }

    public function goBack(): void
    {
        $this->step  = 'code';
        $this->code  = '';
        $this->error = '';
    }

    public function render()
    {
        $integration = $this->loadIntegration();
        return view('livewire.voucher-portal', compact('integration'));
    }
}
