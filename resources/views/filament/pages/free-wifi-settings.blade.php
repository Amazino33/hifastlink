<x-filament-panels::page>
<style>
:root {
    --bg: #ffffff; --bg2: #f9fafb; --bg3: #f3f4f6;
    --border: #e5e7eb;
    --text: #111827; --text2: #374151; --text3: #6b7280;
    --accent: #3b82f6; --accent-h: #2563eb;
    --green: #059669; --red: #dc2626; --orange: #d97706;
}
.dark {
    --bg: #1f2937; --bg2: #111827; --bg3: #1a2535;
    --border: #374151;
    --text: #f9fafb; --text2: #e5e7eb; --text3: #d1d5db;
    --accent: #3b82f6; --accent-h: #2563eb;
    --green: #34d399; --red: #f87171; --orange: #fbbf24;
}
.s-card { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:24px; margin-bottom:20px; }
.s-card-title { font-size:15px; font-weight:600; color:var(--text); margin-bottom:4px; }
.s-card-desc  { font-size:13px; color:var(--text3); margin-bottom:20px; line-height:1.6; }
.s-label { display:block; font-size:13px; font-weight:500; color:var(--text2); margin-bottom:6px; }
.s-input, .s-select {
    width:100%; padding:8px 12px; border:1px solid var(--border);
    border-radius:8px; background:var(--bg2); color:var(--text);
    font-size:14px; outline:none; box-sizing:border-box;
    transition:border-color .15s; font-family:inherit;
}
.s-input:focus, .s-select:focus { border-color:var(--accent); }
.s-hint { font-size:12px; color:var(--text3); margin-top:4px; }
.s-err  { font-size:12px; color:var(--red); margin-top:4px; }
.s-toggle-row {
    display:flex; align-items:center; gap:12px;
    padding:14px 0; border-top:1px solid var(--border); margin-bottom:20px;
}
.s-toggle-row:first-of-type { border-top:none; padding-top:0; }
.toggle-switch { position:relative; width:44px; height:24px; flex-shrink:0; cursor:pointer; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-track { position:absolute; inset:0; background:#d1d5db; border-radius:24px; transition:background .2s; }
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
.s-btn-primary { background:var(--accent); color:#fff; }
.s-btn-primary:hover { background:var(--accent-h); }
.s-btn:disabled { opacity:.5; cursor:not-allowed; }
.info-box {
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
    padding:14px 16px; font-size:13px; color:#1e40af; line-height:1.6;
}
.dark .info-box { background:#1e3a5f; border-color:#3b82f6; color:#93c5fd; }
.info-box ul { margin:6px 0 0; padding-left:18px; }
.plan-preview {
    display:flex; align-items:center; gap:14px;
    background:var(--bg3); border:1px solid var(--border);
    border-radius:8px; padding:14px 16px; margin-top:16px; font-size:13px;
}
.plan-preview-stat { text-align:center; }
.plan-preview-stat strong { display:block; font-size:20px; font-weight:800; color:var(--text); }
.plan-preview-stat span  { font-size:11px; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
.plan-preview-divider { width:1px; background:var(--border); align-self:stretch; }
.warn-box {
    background:#fffbeb; border:1px solid #fde68a; border-radius:8px;
    padding:14px 16px; font-size:13px; color:#92400e; line-height:1.6; margin-bottom:16px;
}
.dark .warn-box { background:#422006; border-color:#d97706; color:#fcd34d; }
.url-example {
    background:var(--bg2); border:1px solid var(--border); border-radius:8px;
    padding:12px 14px; font-family:monospace; font-size:12px; color:var(--text2);
    margin-top:10px; word-break:break-all;
}
</style>

{{-- On/Off toggle --}}
<div class="s-card">
    <div class="s-card-title">Free WiFi Trial Offer</div>
    <div class="s-card-desc">
        When enabled, a public registration page is available at
        <strong>/free-wifi/{router}</strong> for each router. People scan a QR code
        on a poster, enter their name and phone number, and receive the trial plan below.
        One claim per phone number — abuse prevention is always enforced regardless of this toggle.
    </div>

    <div class="s-toggle-row" style="border-top:none;padding-top:0;margin-bottom:0;">
        <label class="toggle-switch">
            <input type="checkbox" wire:model.live="free_wifi_enabled">
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
        <div>
            <div class="s-toggle-label">Enable Free WiFi Trial</div>
            <div class="s-toggle-desc">Turn off to temporarily pause the offer — registration pages will show "unavailable".</div>
        </div>
        <div style="margin-left:auto">
            <span class="status-badge {{ $free_wifi_enabled ? 'status-on' : 'status-off' }}">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                {{ $free_wifi_enabled ? 'Active' : 'Paused' }}
            </span>
        </div>
    </div>
</div>

{{-- Plan picker --}}
<div class="s-card">
    <div class="s-card-title">Trial Plan</div>
    <div class="s-card-desc">
        The plan assigned to every new registration. The plan's own <strong>validity days</strong>
        and <strong>data limit</strong> control what the user gets — change them in the
        <a href="{{ route('filament.admin.resources.plans.index') }}" style="color:var(--accent)">Plans resource</a>
        and it takes effect here automatically.
    </div>

    @if(! $free_wifi_plan_id)
    <div class="warn-box">
        ⚠️ No plan selected. The registration page will show "temporarily unavailable" until you pick a plan and save.
    </div>
    @endif

    <div>
        <label class="s-label">Select Plan</label>
        <select wire:model.live="free_wifi_plan_id" class="s-select">
            <option value="">— Choose a plan —</option>
            @foreach($this->plans as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('free_wifi_plan_id')<p class="s-err">{{ $message }}</p>@enderror
        <p class="s-hint">
            Only active plans are listed. Mark a plan <strong>Admin Only</strong> in Plans → Edit
            to hide it from the public shop while still using it here.
        </p>
    </div>

    @if($this->selectedPlan)
    <div class="plan-preview">
        <div class="plan-preview-stat">
            <strong>{{ $this->selectedPlan->validity_days }}</strong>
            <span>Days</span>
        </div>
        <div class="plan-preview-divider"></div>
        <div class="plan-preview-stat">
            <strong>{{ $this->selectedPlan->data_limit ? \Illuminate\Support\Number::fileSize($this->selectedPlan->data_limit * 1048576) : '∞' }}</strong>
            <span>Data</span>
        </div>
        <div class="plan-preview-divider"></div>
        <div class="plan-preview-stat">
            <strong style="font-size:15px">{{ $this->selectedPlan->name }}</strong>
            <span>Plan name</span>
        </div>
    </div>
    @endif
</div>

{{-- How to use --}}
<div class="s-card" style="background:var(--bg2);">
    <div class="s-card-title" style="margin-bottom:10px;">QR Code URL Format</div>
    <div class="info-box">
        Generate one QR code per router. Use the router's <strong>NAS Identifier</strong>
        (found on the router's edit page) as the URL slug.
        <ul>
            <li>Add <code>/free-wifi/*</code> to MikroTik's <strong>walled garden</strong> so the page loads before authentication.</li>
            <li>One phone number can only claim the trial once — ever.</li>
            <li>If the user already has an active paid plan, registration just logs them in without touching their plan.</li>
        </ul>
        <div class="url-example">{{ config('app.url') }}/free-wifi/<strong>{NAS_IDENTIFIER}</strong></div>
    </div>
</div>

{{-- Save --}}
<div class="s-btn-row">
    <button class="s-btn s-btn-primary" wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="save">Save Settings</span>
        <span wire:loading wire:target="save">Saving…</span>
    </button>
</div>

</x-filament-panels::page>
