<x-filament-panels::page>
<style>
:root {
    --bg: #ffffff; --bg2: #f9fafb; --bg3: #f3f4f6;
    --border: #e5e7eb;
    --text: #111827; --text2: #374151; --text3: #6b7280;
    --accent: #3b82f6; --accent-h: #2563eb;
    --flw: #fb923c; --paystack: #059669;
    --green: #059669; --red: #dc2626;
}
.dark {
    --bg: #1f2937; --bg2: #111827; --bg3: #1a2535;
    --border: #374151;
    --text: #f9fafb; --text2: #e5e7eb; --text3: #d1d5db;
    --accent: #3b82f6; --accent-h: #2563eb;
    --flw: #f97316; --paystack: #10b981;
    --green: #34d399; --red: #f87171;
}
.s-card {
    background:var(--bg); border:1px solid var(--border);
    border-radius:12px; padding:24px; margin-bottom:20px;
}
.s-card-title { font-size:16px; font-weight:600; color:var(--text); margin-bottom:4px; display:flex; align-items:center; gap:8px; }
.s-card-desc  { font-size:13px; color:var(--text3); margin-bottom:20px; }
.s-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:640px){ .s-row{ grid-template-columns:1fr; } }
.s-label { display:block; font-size:13px; font-weight:500; color:var(--text2); margin-bottom:6px; }
.s-input {
    width:100%; padding:9px 12px; border:1px solid var(--border);
    border-radius:8px; background:var(--bg2); color:var(--text);
    font-size:13.5px; outline:none; box-sizing:border-box; transition:border-color .15s; font-family:inherit;
}
.s-input:focus { border-color:var(--accent); }
.s-hint { font-size:12px; color:var(--text3); margin-top:5px; }
.s-err  { font-size:12px; color:var(--red); margin-top:4px; }

/* Gateway Selector Grid */
.gateway-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}
@media(max-width:640px){ .gateway-grid { grid-template-columns:1fr; } }
.gateway-option {
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 16px 20px;
    background: var(--bg);
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: 14px;
}
.gateway-option:hover {
    border-color: #cbd5e1;
}
.gateway-option.selected {
    border-color: var(--accent);
    background: rgba(59, 130, 246, 0.04);
}
.gateway-radio {
    accent-color: var(--accent);
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.gateway-info h4 {
    margin: 0 0 2px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
}
.gateway-info p {
    margin: 0;
    font-size: 12px;
    color: var(--text3);
}

.status-badge {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 10px; border-radius:100px; font-size:12px; font-weight:600;
}
.status-on  { background:#dcfce7; color:#166534; }
.status-off { background:#f3f4f6; color:#6b7280; }
.dark .status-on  { background:#14532d; color:#86efac; }
.dark .status-off { background:#374151; color:#9ca3af; }

.s-btn-row { display:flex; gap:12px; justify-content:space-between; align-items:center; margin-top:16px; }
.s-btn {
    padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600;
    cursor:pointer; border:none; transition:all .15s; font-family:inherit;
    display: inline-flex; align-items: center; gap: 6px;
}
.s-btn-primary   { background:var(--accent); color:#fff; }
.s-btn-primary:hover { background:var(--accent-h); }
.s-btn-secondary { background:var(--bg2); color:var(--text2); border:1px solid var(--border); }
.s-btn-secondary:hover { background:var(--bg3); }
.s-btn:disabled { opacity:.5; cursor:not-allowed; }

.copy-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--bg2);
    border: 1px solid var(--border);
    padding: 6px 12px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 13px;
    color: var(--text);
}
.copy-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--accent);
    font-weight: 600;
    font-size: 12px;
    padding: 0;
}
.copy-btn:hover { text-decoration: underline; }
</style>

{{-- ── 1. Active Gateway Selector ────────────────────────────────────── --}}
<div class="s-card">
    <div class="s-card-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Default Active Gateway
    </div>
    <div class="s-card-desc">
        Select which payment gateway is used by default when customers purchase a subscription or top up their prepaid wallet.
    </div>

    <div class="gateway-grid">
        <label class="gateway-option {{ $payment_gateway === 'flutterwave' ? 'selected' : '' }}">
            <input type="radio" wire:model.live="payment_gateway" value="flutterwave" class="gateway-radio">
            <div class="gateway-info">
                <h4>Flutterwave (Cards, Bank Transfer, USSD)</h4>
                <p>Accepts cards, bank accounts, direct bank transfers, and Barter.</p>
            </div>
            @if($payment_gateway === 'flutterwave')
                <span class="status-badge status-on" style="margin-left:auto">Active</span>
            @endif
        </label>

        <label class="gateway-option {{ $payment_gateway === 'paystack' ? 'selected' : '' }}">
            <input type="radio" wire:model.live="payment_gateway" value="paystack" class="gateway-radio">
            <div class="gateway-info">
                <h4>Paystack (Cards, Bank Transfer, USSD)</h4>
                <p>Accepts cards, bank transfers, USSD, and Apple Pay.</p>
            </div>
            @if($payment_gateway === 'paystack')
                <span class="status-badge status-on" style="margin-left:auto">Active</span>
            @endif
        </label>
    </div>
</div>

{{-- ── 2. Flutterwave Configuration ──────────────────────────────────── --}}
<div class="s-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
        <div class="s-card-title" style="margin-bottom:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Flutterwave Credentials
        </div>
        <span class="status-badge {{ !empty($flw_secret_key) ? 'status-on' : 'status-off' }}">
            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
            {{ !empty($flw_secret_key) ? 'Configured' : 'Missing Keys' }}
        </span>
    </div>
    <div class="s-card-desc">
        Obtain your API keys from your <a href="https://dashboard.flutterwave.com" target="_blank" style="color:var(--accent); text-decoration:underline;">Flutterwave Dashboard</a> &rarr; Settings &rarr; API Keys.
    </div>

    <div class="s-row">
        <div>
            <label class="s-label">Public Key</label>
            <input type="text" wire:model="flw_public_key" class="s-input" placeholder="Enter Flutterwave public key" autocomplete="off">
            @error('flw_public_key')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Your Flutterwave public key (Live or Test).</p>
        </div>
        <div>
            <label class="s-label">Secret Key</label>
            <input type="password" wire:model="flw_secret_key" class="s-input" placeholder="Enter Flutterwave secret key" autocomplete="new-password">
            @error('flw_secret_key')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Used for server-side verification and payment initialization.</p>
        </div>
    </div>

    <div class="s-row">
        <div>
            <label class="s-label">Webhook Secret Hash</label>
            <input type="text" wire:model="flw_secret_hash" class="s-input" placeholder="e.g. hifastlink_secure_flw_hash" autocomplete="off">
            @error('flw_secret_hash')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Set the exact same secret hash in Flutterwave Dashboard &rarr; Settings &rarr; Webhooks.</p>
        </div>
        <div>
            <label class="s-label">API Base URL</label>
            <input type="text" wire:model="flw_base_url" class="s-input" placeholder="https://api.flutterwave.com/v3">
            @error('flw_base_url')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Standard Flutterwave v3 API endpoint.</p>
        </div>
    </div>

    <div class="s-btn-row">
        <div>
            <button type="button" wire:click="testFlutterwave" wire:loading.attr="disabled" class="s-btn s-btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span wire:loading.remove wire:target="testFlutterwave">Test Flutterwave Connection</span>
                <span wire:loading wire:target="testFlutterwave">Testing API...</span>
            </button>
        </div>
    </div>
</div>

{{-- ── 3. Paystack Configuration ────────────────────────────────────── --}}
<div class="s-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
        <div class="s-card-title" style="margin-bottom:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            Paystack Credentials
        </div>
        <span class="status-badge {{ !empty($paystack_secret_key) ? 'status-on' : 'status-off' }}">
            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
            {{ !empty($paystack_secret_key) ? 'Configured' : 'Missing Keys' }}
        </span>
    </div>
    <div class="s-card-desc">
        Obtain your API keys from your <a href="https://dashboard.paystack.co" target="_blank" style="color:var(--accent); text-decoration:underline;">Paystack Dashboard</a> &rarr; Settings &rarr; API Keys & Webhooks.
    </div>

    <div class="s-row">
        <div>
            <label class="s-label">Public Key</label>
            <input type="text" wire:model="paystack_public_key" class="s-input" placeholder="Enter Paystack public key" autocomplete="off">
            @error('paystack_public_key')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Your Paystack public key (Live or Test).</p>
        </div>
        <div>
            <label class="s-label">Secret Key</label>
            <input type="password" wire:model="paystack_secret_key" class="s-input" placeholder="Enter Paystack secret key" autocomplete="new-password">
            @error('paystack_secret_key')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Your Paystack secret key (Live or Test).</p>
        </div>
    </div>

    <div class="s-row">
        <div style="grid-column: 1 / -1;">
            <label class="s-label">Payment API URL</label>
            <input type="text" wire:model="paystack_payment_url" class="s-input" placeholder="https://api.paystack.co">
            @error('paystack_payment_url')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Standard Paystack API endpoint.</p>
        </div>
    </div>

    <div class="s-btn-row">
        <div>
            <button type="button" wire:click="testPaystack" wire:loading.attr="disabled" class="s-btn s-btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span wire:loading.remove wire:target="testPaystack">Test Paystack Connection</span>
                <span wire:loading wire:target="testPaystack">Testing API...</span>
            </button>
        </div>
    </div>
</div>

{{-- ── 4. Webhook Setup Information ──────────────────────────────────── --}}
<div class="s-card">
    <div class="s-card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Instant Webhook Endpoint
    </div>
    <div class="s-card-desc">
        Webhooks ensure subscriptions and wallet top-ups activate instantly even if a customer closes their browser before redirecting back.
    </div>

    <div x-data="{ copied: false, url: '{{ url('/payment/webhook') }}' }" style="margin-bottom:14px;">
        <label class="s-label">Universal Webhook URL (Copy into Flutterwave and Paystack Dashboards)</label>
        <div class="copy-pill">
            <span x-text="url"></span>
            <button type="button" class="copy-btn" @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000);">
                <span x-show="!copied">Copy URL</span>
                <span x-show="copied" style="color:var(--green);">Copied! &#10003;</span>
            </button>
        </div>
    </div>

    <div style="font-size:12.5px; color:var(--text3); line-height:1.6;">
        &bull; <strong>In Flutterwave</strong>: Go to <em>Settings &rarr; Webhooks</em> &rarr; Paste the Webhook URL above, and enter your <em>Webhook Secret Hash</em>.<br>
        &bull; <strong>In Paystack</strong>: Go to <em>Settings &rarr; Preferences &rarr; API & Webhooks</em> &rarr; Paste the Webhook URL in the <em>Live / Test Webhook URL</em> field.
    </div>
</div>

{{-- ── 5. Save Button ────────────────────────────────────────────────── --}}
<div style="display:flex; justify-content:flex-end; margin-bottom:40px;">
    <button type="button" wire:click="saveSettings" wire:loading.attr="disabled" class="s-btn s-btn-primary" style="padding:11px 32px; font-size:14px;">
        <span wire:loading.remove wire:target="saveSettings">Save Payment Settings</span>
        <span wire:loading wire:target="saveSettings">Saving Settings...</span>
    </button>
</div>
</x-filament-panels::page>
