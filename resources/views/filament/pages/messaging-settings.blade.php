<x-filament-panels::page>
<style>
:root {
    --bg: #ffffff; --bg2: #f9fafb; --bg3: #f3f4f6;
    --border: #e5e7eb;
    --text: #111827; --text2: #374151; --text3: #6b7280;
    --accent: #3b82f6; --accent-h: #2563eb;
    --green: #059669; --red: #dc2626;
}
.dark {
    --bg: #1f2937; --bg2: #111827; --bg3: #1a2535;
    --border: #374151;
    --text: #f9fafb; --text2: #e5e7eb; --text3: #d1d5db;
    --accent: #3b82f6; --accent-h: #2563eb;
    --green: #34d399; --red: #f87171;
}
.s-card {
    background:var(--bg); border:1px solid var(--border);
    border-radius:12px; padding:24px; margin-bottom:20px;
}
.s-card-title { font-size:15px; font-weight:600; color:var(--text); margin-bottom:4px; }
.s-card-desc  { font-size:13px; color:var(--text3); margin-bottom:20px; }
.s-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:640px){ .s-row{ grid-template-columns:1fr; } }
.s-label { display:block; font-size:13px; font-weight:500; color:var(--text2); margin-bottom:6px; }
.s-input {
    width:100%; padding:8px 12px; border:1px solid var(--border);
    border-radius:8px; background:var(--bg2); color:var(--text);
    font-size:14px; outline:none; box-sizing:border-box; transition:border-color .15s; font-family:inherit;
}
.s-input:focus { border-color:var(--accent); }
.s-hint { font-size:12px; color:var(--text3); margin-top:4px; }
.s-err  { font-size:12px; color:var(--red); margin-top:4px; }
.s-toggle-row {
    display:flex; align-items:center; gap:12px;
    padding:14px 0; border-top:1px solid var(--border); margin-bottom:20px;
}
.s-toggle-row:first-of-type { border-top:none; padding-top:0; }
.toggle-switch { position:relative; width:44px; height:24px; flex-shrink:0; cursor:pointer; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-track {
    position:absolute; inset:0; background:#d1d5db; border-radius:24px; transition:background .2s;
}
.toggle-switch input:checked ~ .toggle-track { background:var(--accent); }
.toggle-thumb {
    position:absolute; top:3px; left:3px; width:18px; height:18px;
    background:#fff; border-radius:50%; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.toggle-switch input:checked ~ .toggle-track .toggle-thumb { transform:translateX(20px); }
.s-toggle-label { font-size:14px; font-weight:500; color:var(--text2); }
.s-toggle-desc  { font-size:12px; color:var(--text3); }
.status-badge {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 10px; border-radius:100px; font-size:12px; font-weight:600;
}
.status-on  { background:#dcfce7; color:#166534; }
.status-off { background:#f3f4f6; color:#6b7280; }
.dark .status-on  { background:#14532d; color:#86efac; }
.dark .status-off { background:#374151; color:#9ca3af; }
.s-btn-row { display:flex; gap:10px; justify-content:flex-end; margin-top:8px; }
.s-btn {
    padding:9px 22px; border-radius:8px; font-size:13px; font-weight:600;
    cursor:pointer; border:none; transition:background .15s; font-family:inherit;
}
.s-btn-primary   { background:var(--accent); color:#fff; }
.s-btn-primary:hover { background:var(--accent-h); }
.s-btn-secondary { background:var(--bg2); color:var(--text2); border:1px solid var(--border); }
.s-btn-secondary:hover { background:var(--bg3); }
.s-btn:disabled { opacity:.5; cursor:not-allowed; }
</style>

{{-- WhatsApp credentials --}}
<div class="s-card">
    <div class="s-card-title">WAWP — WhatsApp</div>
    <div class="s-card-desc">
        Your WAWP instance ID and access token from
        <a href="https://app.wawp.net" target="_blank" style="color:var(--accent)">app.wawp.net</a>.
    </div>

    <div class="s-row">
        <div>
            <label class="s-label">Instance ID</label>
            <input type="text" wire:model="instance_id" class="s-input" placeholder="e.g. 0F8B7C26C87E" autocomplete="off">
            @error('instance_id')<p class="s-err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="s-label">Access Token</label>
            <input type="password" wire:model="access_token" class="s-input" placeholder="Paste your access token" autocomplete="new-password">
            @error('access_token')<p class="s-err">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="s-toggle-row">
        <label class="toggle-switch">
            <input type="checkbox" wire:model="enabled">
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
        <div>
            <div class="s-toggle-label">Enable WhatsApp Notifications</div>
            <div class="s-toggle-desc">When off, messages are logged locally instead of being sent.</div>
        </div>
        <div style="margin-left:auto">
            <span class="status-badge {{ $enabled ? 'status-on' : 'status-off' }}">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                {{ $enabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>
    </div>
</div>

{{-- SMS Fallback --}}
<div class="s-card">
    <div class="s-card-title">SMS Fallback</div>
    <div class="s-card-desc">When WhatsApp delivery fails, messages are retried via SMS.</div>

    <div class="s-toggle-row">
        <label class="toggle-switch">
            <input type="checkbox" wire:model="sms_enabled">
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
        <div>
            <div class="s-toggle-label">Enable SMS Fallback</div>
            <div class="s-toggle-desc">Sends SMS when WhatsApp cannot deliver (e.g. number not on WhatsApp).</div>
        </div>
        <div style="margin-left:auto">
            <span class="status-badge {{ $sms_enabled ? 'status-on' : 'status-off' }}">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                {{ $sms_enabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>
    </div>

    <div class="s-row">
        <div>
            <label class="s-label">SMS Provider</label>
            <select wire:model="sms_provider" class="s-input" style="cursor:pointer;">
                <option value="termii">Termii</option>
                <option value="bulksms">BulkSMS Nigeria</option>
                <option value="kudisms">KudiSMS</option>
            </select>
        </div>
        <div>
            <label class="s-label">Sender ID / Name</label>
            <input type="text" wire:model="sms_sender_id" class="s-input" placeholder="HiFastLink" maxlength="11">
            @error('sms_sender_id')<p class="s-err">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="s-row">
        <div>
            <label class="s-label">API Key</label>
            <input type="password" wire:model="sms_api_key" class="s-input" placeholder="Paste API key" autocomplete="new-password">
            @error('sms_api_key')<p class="s-err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="s-label">API Secret <span style="font-size:11px;color:var(--text3)">(BulkSMS only)</span></label>
            <input type="password" wire:model="sms_api_secret" class="s-input" placeholder="Only required for BulkSMS" autocomplete="new-password">
            @error('sms_api_secret')<p class="s-err">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- OTP Rate Limiting --}}
<div class="s-card">
    <div class="s-card-title">OTP Rate Limiting</div>
    <div class="s-card-desc">Prevent spam by limiting how many login codes can be sent per number within a time window.</div>

    <div class="s-row">
        <div>
            <label class="s-label">Time Window (minutes)</label>
            <input type="number" min="1" max="60" wire:model="otp_window_minutes" class="s-input">
            @error('otp_window_minutes')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">How long the window is tracked. Default: 10 min.</p>
        </div>
        <div>
            <label class="s-label">Max Requests per Window</label>
            <input type="number" min="1" max="10" wire:model="otp_max_attempts" class="s-input">
            @error('otp_max_attempts')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">Block requests beyond this count. Default: 3.</p>
        </div>
    </div>
</div>

{{-- Save --}}
<div class="s-btn-row" style="margin-bottom:20px;">
    <button class="s-btn s-btn-primary" wire:click="saveMessaging" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="saveMessaging">Save Messaging Settings</span>
        <span wire:loading wire:target="saveMessaging">Saving...</span>
    </button>
</div>

{{-- Test message --}}
<div class="s-card">
    <div class="s-card-title">Send Test Message</div>
    <div class="s-card-desc">
        Verify your configuration by sending a test WhatsApp/SMS to any number (include country code, e.g.
        <code style="background:var(--bg3);padding:1px 5px;border-radius:4px;font-size:12px;">2348012345678</code>).
    </div>
    <div style="display:flex;gap:12px;align-items:flex-start;">
        <div style="flex:1;">
            <input type="text" wire:model="test_number" class="s-input" placeholder="2348012345678" style="max-width:320px;">
            @error('test_number')<p class="s-err">{{ $message }}</p>@enderror
        </div>
        <button class="s-btn s-btn-secondary" wire:click="sendTest" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="sendTest">Send Test</span>
            <span wire:loading wire:target="sendTest">Sending...</span>
        </button>
    </div>
</div>

{{-- What gets sent --}}
<div class="s-card" style="background:var(--bg2);">
    <div class="s-card-title" style="margin-bottom:10px;">What gets sent via WhatsApp?</div>
    <ul style="margin:0;padding-left:20px;color:var(--text2);font-size:13px;line-height:1.9;">
        <li>Login OTP codes (new user registration & new device login)</li>
        <li>Plan purchase confirmations</li>
        <li>Plan expiry reminders</li>
    </ul>
</div>

</x-filament-panels::page>
