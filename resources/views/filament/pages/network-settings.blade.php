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
.s-btn-danger    { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.s-btn-danger:hover { background:#fecaca; }
.s-btn:disabled { opacity:.5; cursor:not-allowed; }
.rate-preview {
    display:inline-flex; align-items:center; gap:8px;
    background:var(--bg3); border:1px solid var(--border);
    border-radius:8px; padding:8px 14px; font-size:13px; color:var(--text2); margin-top:16px;
}
.rate-preview strong { color:var(--text); font-family:monospace; }
.info-box {
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
    padding:14px 16px; font-size:13px; color:#1e40af; line-height:1.6;
}
.dark .info-box { background:#1e3a5f; border-color:#3b82f6; color:#93c5fd; }
.info-box ul { margin:6px 0 0; padding-left:18px; }
</style>

{{-- Global Bandwidth Cap --}}
<div class="s-card">
    <div class="s-card-title">Global Bandwidth Cap</div>
    <div class="s-card-desc">
        Sets a default speed ceiling for every non-admin user. Prevents one person from saturating the
        connection. Per-plan speed limits still take priority — this only kicks in when a plan has none.
    </div>

    <div class="s-toggle-row" style="border-top:none;padding-top:0;margin-bottom:20px;">
        <label class="toggle-switch">
            <input type="checkbox" wire:model.live="global_speed_enabled">
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
        <div>
            <div class="s-toggle-label">Enable Global Speed Cap</div>
            <div class="s-toggle-desc">When disabled, users with no per-plan speed run at full line speed.</div>
        </div>
        <div style="margin-left:auto">
            <span class="status-badge {{ $global_speed_enabled ? 'status-on' : 'status-off' }}">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                {{ $global_speed_enabled ? 'Active' : 'Off' }}
            </span>
        </div>
    </div>

    <div class="s-row">
        <div>
            <label class="s-label">Upload Limit (Kbps)</label>
            <input type="number" min="0" max="1000000" wire:model.live="global_speed_upload" class="s-input"
                   placeholder="e.g. 1024" {{ ! $global_speed_enabled ? 'disabled' : '' }}>
            @error('global_speed_upload')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">1024 = 1 Mbps &nbsp;·&nbsp; 5120 = 5 Mbps &nbsp;·&nbsp; 0 = no upload limit</p>
        </div>
        <div>
            <label class="s-label">Download Limit (Kbps)</label>
            <input type="number" min="0" max="1000000" wire:model.live="global_speed_download" class="s-input"
                   placeholder="e.g. 2048" {{ ! $global_speed_enabled ? 'disabled' : '' }}>
            @error('global_speed_download')<p class="s-err">{{ $message }}</p>@enderror
            <p class="s-hint">2048 = 2 Mbps &nbsp;·&nbsp; 10240 = 10 Mbps &nbsp;·&nbsp; 0 = no download limit</p>
        </div>
    </div>

    @if($global_speed_enabled && ($global_speed_upload || $global_speed_download))
    <div class="rate-preview">
        <svg style="width:15px;height:15px;flex-shrink:0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        MikroTik RADIUS attribute:&nbsp;
        <strong>Mikrotik-Rate-Limit = {{ $global_speed_upload }}k/{{ $global_speed_download }}k</strong>
        &nbsp;·&nbsp;
        <span>↑ {{ number_format($global_speed_upload / 1024, 1) }} Mbps &nbsp; ↓ {{ number_format($global_speed_download / 1024, 1) }} Mbps</span>
    </div>
    @endif
</div>

{{-- Who it applies to --}}
<div class="s-card" style="background:var(--bg2);">
    <div class="s-card-title" style="margin-bottom:10px;">Who does this apply to?</div>
    <div class="info-box">
        <ul>
            <li><strong>Regular subscribers</strong> — applies when their plan has no speed limit configured.</li>
            <li><strong>Voucher-access users</strong> — custom vouchers with no attached plan always get the global cap.</li>
            <li><strong>Free-pass users</strong> — family/staff accounts without a plan get the global cap.</li>
            <li><strong>Admins</strong> — always exempt, never capped regardless of this setting.</li>
            <li><strong>Per-plan speed limits always win</strong> — if a plan sets its own speed, this global cap is ignored for that plan's users.</li>
        </ul>
    </div>
</div>

{{-- Save + Apply --}}
<div class="s-btn-row" style="margin-bottom:20px;">
    <button class="s-btn s-btn-secondary" wire:click="applyGlobally" wire:loading.attr="disabled"
            wire:confirm="This will re-sync RADIUS for all active non-admin users. Continue?">
        <span wire:loading.remove wire:target="applyGlobally">Apply to All Users Now</span>
        <span wire:loading wire:target="applyGlobally">Applying...</span>
    </button>
    <button class="s-btn s-btn-primary" wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="save">Save Settings</span>
        <span wire:loading wire:target="save">Saving...</span>
    </button>
</div>

<p style="font-size:12px;color:var(--text3);text-align:right;margin-top:-12px;">
    "Save" persists the settings. "Apply to All Users Now" also pushes the new rate limit to RADIUS immediately for all active connections.
</p>

</x-filament-panels::page>
