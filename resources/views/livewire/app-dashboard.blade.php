<style>
/* ═══════════════════════════════════════════
   APP DASHBOARD — tokens & base
═══════════════════════════════════════════ */
:root {
    --bg:          #0a0e1a;
    --surface:     #111827;
    --surface-2:   #1a2235;
    --border:      rgba(255,255,255,.08);
    --text:        #e8eeff;
    --muted:       #7a8aaa;
    --accent:      #007afe;
    --accent-glow: rgba(0,122,254,.45);
    --green:       #30d158;
    --green-glow:  rgba(48,209,88,.35);
    --amber:       #ffcc00;
    --amber-glow:  rgba(255,204,0,.4);
    --tab-h:       68px;
}

/* ── Layout ─────────────────────────────── */
.app-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100dvh;
    background: var(--bg);
    max-width: 430px;
    margin: 0 auto;
    position: relative;
}

/* ── Top bar ────────────────────────────── */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 12px;
    padding-top: calc(16px + env(safe-area-inset-top, 0px));
}
.topbar-greeting { display: flex; flex-direction: column; }
.topbar-greeting .label { font-size: 12px; color: var(--muted); letter-spacing: .04em; }
.topbar-greeting .name  { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: var(--text); line-height: 1.2; }
.avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, #1a3a6e, #0055d4);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Sora', sans-serif; font-weight: 700; font-size: 15px;
    color: #fff; letter-spacing: .02em; flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(0,122,254,.3);
}

/* ── Connection card ────────────────────── */
.conn-card {
    margin: 0 16px 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 20px 16px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
}

/* ── Signal visualization ───────────────── */
.viz-wrap {
    position: relative;
    width: 200px; height: 200px;
    flex-shrink: 0;
}
.viz-wrap svg { position: absolute; inset: 0; width: 100%; height: 100%; }

/* Connect button — lives in center of the viz */
.connect-btn {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 80px; height: 80px;
    border-radius: 50%;
    background: radial-gradient(circle at 38% 30%, #5bbeff, #0055d4);
    box-shadow:
        0 0 0 3px rgba(0,122,254,.2),
        0 0 32px rgba(0,122,254,.6),
        0 8px 24px rgba(0,0,0,.55),
        inset 0 2px 0 rgba(255,255,255,.28),
        inset 0 -3px 6px rgba(0,0,0,.35);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    cursor: pointer;
    z-index: 3;
    border: none;
    transition: box-shadow .15s, transform .1s;
    -webkit-tap-highlight-color: transparent;
}
.connect-btn:active {
    transform: translate(-50%, -50%) scale(.95);
    box-shadow: 0 0 0 3px rgba(0,122,254,.25), 0 0 20px rgba(0,122,254,.5), 0 4px 12px rgba(0,0,0,.5), inset 0 2px 0 rgba(255,255,255,.22), inset 0 -2px 4px rgba(0,0,0,.3);
}
.connect-btn-icon { color: #fff; width: 26px; height: 26px; }
.connect-btn-icon svg { width: 100%; height: 100%; }
.connect-btn-lbl { color: rgba(255,255,255,.9); font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; font-family: 'Sora', sans-serif; }

/* Pulse rings behind the button */
.pr {
    position: absolute; top: 50%; left: 50%;
    border-radius: 50%;
    border: 1.5px solid rgba(0,122,254,.55);
    width: 80px; height: 80px;
    animation: pr-expand 2.4s ease-out infinite;
    pointer-events: none; z-index: 1;
}
.pr:nth-child(2) { animation-delay: .8s; }
.pr:nth-child(3) { animation-delay: 1.6s; }
@keyframes pr-expand {
    0%   { transform: translate(-50%,-50%) scale(1);   opacity: .55; }
    100% { transform: translate(-50%,-50%) scale(3.2); opacity: 0;   }
}

/* Connected dot in center */
.conn-center {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 70px; height: 70px;
    border-radius: 50%;
    background: radial-gradient(circle at 40% 35%, #6ff7a0, #1ab858);
    box-shadow: 0 0 28px rgba(48,209,88,.6), 0 6px 18px rgba(0,0,0,.45), inset 0 2px 0 rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    z-index: 3;
}
.conn-center svg { width: 28px; height: 28px; color: #fff; }

/* Green pulse rings */
.pr-green {
    position: absolute; top: 50%; left: 50%;
    border-radius: 50%;
    border: 1.5px solid rgba(48,209,88,.55);
    width: 70px; height: 70px;
    animation: pr-expand 2.4s ease-out infinite;
    pointer-events: none; z-index: 1;
}
.pr-green:nth-child(2) { animation-delay: .8s; }
.pr-green:nth-child(3) { animation-delay: 1.6s; }

/* ── Status badge ───────────────────────── */
.status-row {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
    letter-spacing: .05em; text-transform: uppercase; font-family: 'Sora', sans-serif;
}
.badge-noplan  { background: rgba(255,204,0,.12);  color: var(--amber);  border: 1px solid rgba(255,204,0,.2); }
.badge-active  { background: rgba(0,122,254,.12);  color: var(--accent); border: 1px solid rgba(0,122,254,.2); }
.badge-conn    { background: rgba(48,209,88,.12);  color: var(--green);  border: 1px solid rgba(48,209,88,.2); }
.badge-dot {
    width: 6px; height: 6px; border-radius: 50%;
    animation: dot-pulse 2s ease-in-out infinite;
}
.badge-noplan  .badge-dot { background: var(--amber); }
.badge-active  .badge-dot { background: var(--accent); }
.badge-conn    .badge-dot { background: var(--green); animation-play-state: running; }
@keyframes dot-pulse {
    0%,100% { opacity: 1; } 50% { opacity: .3; }
}
.status-sub { font-size: 12px; color: var(--muted); }

/* ── Hotspot detection strip ────────────── */
.hotspot-strip {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; border-radius: 12px;
    font-size: 12px; width: 100%;
}
.hs-ok  { background: rgba(48,209,88,.08);  color: #52e07a; border: 1px solid rgba(48,209,88,.15); }
.hs-off { background: rgba(255,204,0,.08);  color: var(--amber); border: 1px solid rgba(255,204,0,.15); }

/* ── Stats strip ────────────────────────── */
.stats-strip {
    display: grid;
    grid-template-columns: 1fr 1px 1fr 1px 1fr;
    align-items: center;
    gap: 0;
    padding: 0 4px;
    width: 100%;
}
.stats-strip .divider { height: 30px; background: var(--border); }
.stat-item { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 0 6px; }
.stat-val { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 500; color: var(--text); }
.stat-lbl { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }

/* ── Data arc bar ───────────────────────── */
.data-bar-wrap { width: 100%; }
.data-bar-labels { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); margin-bottom: 5px; }
.data-bar-track {
    height: 5px; border-radius: 999px;
    background: rgba(255,255,255,.07);
    overflow: hidden;
}
.data-bar-fill {
    height: 100%; border-radius: 999px;
    background: linear-gradient(90deg, #0055d4, #5bbeff);
    transition: width .6s ease;
}

/* ── Tab content area ───────────────────── */
.tab-content {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px;
    padding-bottom: calc(var(--tab-h) + 16px + env(safe-area-inset-bottom, 0px));
}

/* ── Section header ─────────────────────── */
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; padding-top: 4px;
}
.section-header h3 {
    font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: var(--text);
}
.section-header a { font-size: 12px; color: var(--accent); }

/* ── Plan cards ─────────────────────────── */
.plan-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.plan-card.featured {
    background: linear-gradient(135deg, #0c2a5e, #0c1e42);
    border-color: rgba(0,122,254,.25);
}
.plan-icon {
    width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
    background: rgba(0,122,254,.15);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent);
}
.plan-icon.feat { background: rgba(0,122,254,.25); }
.plan-info { flex: 1; min-width: 0; }
.plan-name { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.plan-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.plan-price { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--accent); flex-shrink: 0; }
.plan-price-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.btn-buy {
    display: inline-block; padding: 7px 16px;
    background: var(--accent); color: #fff;
    border: none; border-radius: 999px;
    font-size: 12px; font-weight: 600; letter-spacing: .02em;
    cursor: pointer; white-space: nowrap;
    font-family: 'Sora', sans-serif;
    -webkit-tap-highlight-color: transparent;
    transition: background .15s;
}
.btn-buy:hover { background: #0067d5; }

/* ── No-plan CTA ────────────────────────── */
.noplan-cta {
    background: var(--surface);
    border: 1px dashed rgba(0,122,254,.25);
    border-radius: 18px;
    padding: 24px 20px;
    text-align: center;
    margin-bottom: 16px;
}
.noplan-cta h4 { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.noplan-cta p  { font-size: 13px; color: var(--muted); line-height: 1.5; }

/* ── Voucher input ───────────────────────── */
.voucher-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 12px;
}
.voucher-card label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 8px; letter-spacing: .04em; text-transform: uppercase; }
.voucher-row { display: flex; gap: 8px; }
.voucher-input {
    flex: 1; background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 12px; padding: 10px 14px;
    color: var(--text); font-family: 'JetBrains Mono', monospace; font-size: 14px;
    letter-spacing: .1em; text-transform: uppercase;
    outline: none; transition: border-color .15s;
}
.voucher-input::placeholder { color: var(--muted); letter-spacing: .04em; text-transform: none; font-family: 'Outfit', sans-serif; }
.voucher-input:focus { border-color: var(--accent); }
.btn-redeem {
    background: var(--accent); color: #fff;
    border: none; border-radius: 12px; padding: 10px 18px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    font-family: 'Sora', sans-serif;
    white-space: nowrap;
    -webkit-tap-highlight-color: transparent;
}
.error-msg { font-size: 12px; color: #ff5f5f; margin-top: 6px; }

/* ── Session card ───────────────────────── */
.session-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 12px;
}
.session-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border); }
.session-row:last-child { border-bottom: none; padding-bottom: 0; }
.session-row .key  { font-size: 13px; color: var(--muted); }
.session-row .val  { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text); }

/* ── History list ───────────────────────── */
.tx-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid var(--border);
}
.tx-item:last-child { border-bottom: none; }
.tx-icon { width: 36px; height: 36px; border-radius: 12px; background: rgba(0,122,254,.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--accent); }
.tx-info { flex: 1; }
.tx-info .tx-name { font-size: 13px; font-weight: 500; color: var(--text); }
.tx-info .tx-date { font-size: 11px; color: var(--muted); margin-top: 2px; }
.tx-amount { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 500; color: var(--green); }

/* ── Account tab ────────────────────────── */
.account-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 16px; background: var(--surface);
    border: 1px solid var(--border); border-radius: 16px;
    margin-bottom: 10px; cursor: pointer;
    transition: background .15s;
    -webkit-tap-highlight-color: transparent;
    text-decoration: none;
    color: var(--text);
}
.account-row:hover { background: var(--surface-2); }
.account-row-icon { width: 40px; height: 40px; border-radius: 12px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--muted); }
.account-row-label { flex: 1; font-size: 14px; font-weight: 500; }
.account-row-arrow { color: var(--muted); }

/* ── Warning modal ───────────────────────── */
.overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.75);
    display: flex; align-items: flex-end; justify-content: center;
    z-index: 99; padding: 0 16px 32px;
    padding-bottom: calc(32px + env(safe-area-inset-bottom, 0px));
}
.modal-sheet {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 28px 28px 20px 20px;
    padding: 24px 20px 20px;
    width: 100%; max-width: 398px;
    text-align: center;
}
.modal-icon { font-size: 40px; margin-bottom: 12px; }
.modal-title { font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.modal-body  { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 20px; }
.btn-modal-ok {
    display: block; width: 100%; padding: 14px;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 14px; color: var(--text);
    font-size: 14px; font-weight: 600; cursor: pointer;
    font-family: 'Sora', sans-serif;
}

/* ── Toast notification ─────────────────── */
.toast-wrap {
    position: fixed; top: 60px; left: 50%; transform: translateX(-50%);
    z-index: 100; pointer-events: none; width: calc(100% - 32px); max-width: 398px;
}
.toast {
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 13px; font-weight: 500;
    color: var(--text);
    box-shadow: 0 8px 32px rgba(0,0,0,.5);
}
.toast.success { border-color: rgba(48,209,88,.3); color: #52e07a; }

/* ── Bottom tab bar ─────────────────────── */
.tab-bar {
    position: fixed;
    bottom: 0; left: 50%; transform: translateX(-50%);
    width: 100%; max-width: 430px;
    height: var(--tab-h);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    background: rgba(10,14,26,.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid var(--border);
    display: flex;
    z-index: 50;
}
.tab-btn {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 4px;
    background: none; border: none; cursor: pointer;
    color: var(--muted); transition: color .15s;
    -webkit-tap-highlight-color: transparent;
    font-size: 10px; font-family: 'Outfit', sans-serif; font-weight: 500;
    letter-spacing: .03em;
}
.tab-btn svg { width: 22px; height: 22px; transition: transform .15s; }
.tab-btn.active { color: var(--accent); }
.tab-btn.active svg { transform: scale(1.1); }

/* ── SVG ring animations ─────────────────── */
@keyframes scan-arc {
    from { stroke-dashoffset: 0; }
    to   { stroke-dashoffset: -346; }
}
@keyframes ring-pulse {
    0%,100% { opacity: .4; r: 39; }
    50%      { opacity: 1;  r: 41; }
}
</style>

<div
    class="app-wrap"
    x-data="{
        tab: 'home',
        toast: null,
        showToast(msg, type) { this.toast = { msg, type }; setTimeout(() => this.toast = null, 3200); }
    }"
    x-on:toast.window="showToast($event.detail.message, $event.detail.type ?? 'info')"
    wire:poll.15000ms="pollConnection"
>

    {{-- ══ TOP BAR ══════════════════════════════════════ --}}
    <div class="topbar safe-top">
        <div class="topbar-greeting">
            <span class="label">Welcome back</span>
            <span class="name">{{ $user->display_name }}</span>
        </div>
        <div class="avatar">{{ $user->initials }}</div>
    </div>

    {{-- ══ TOAST ════════════════════════════════════════ --}}
    <div class="toast-wrap" x-show="toast" x-transition.opacity style="display:none">
        <div class="toast" :class="toast?.type === 'success' ? 'success' : ''" x-text="toast?.msg"></div>
    </div>

    {{-- ══ CONNECTION CARD ══════════════════════════════ --}}
    <div class="conn-card">

        {{-- Signal visualization --}}
        <div class="viz-wrap">

            {{-- SVG rings --}}
            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                {{-- ── NO PLAN STATE: amber scanning rings ── --}}
                @if($connectionState === 'no-plan')
                    <circle cx="100" cy="100" r="87" stroke="rgba(255,204,0,.10)" stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="71" stroke="rgba(255,204,0,.14)" stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="55" stroke="rgba(255,204,0,.20)" stroke-width="2"/>
                    <circle cx="100" cy="100" r="39" stroke="rgba(255,204,0,.30)" stroke-width="2"/>
                    {{-- scanning arc --}}
                    <circle cx="100" cy="100" r="87" stroke="rgba(255,204,0,.65)" stroke-width="2"
                        stroke-dasharray="60 286" stroke-linecap="round"
                        transform="rotate(-90 100 100)"
                        style="animation: scan-arc 2s linear infinite"/>
                    <circle cx="100" cy="100" r="71" stroke="rgba(255,204,0,.4)" stroke-width="1.5"
                        stroke-dasharray="40 207" stroke-linecap="round"
                        transform="rotate(-90 100 100)"
                        style="animation: scan-arc 2s linear infinite reverse"/>
                    {{-- center dot --}}
                    <circle cx="100" cy="100" r="10" fill="rgba(255,204,0,.2)" stroke="rgba(255,204,0,.5)" stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="5" fill="#ffcc00"/>

                {{-- ── PLAN ACTIVE STATE: blue data arc ── --}}
                @elseif($connectionState === 'plan-active')
                    <circle cx="100" cy="100" r="87" stroke="rgba(0,122,254,.08)"  stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="71" stroke="rgba(0,122,254,.12)"  stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="55" stroke="rgba(0,122,254,.18)"  stroke-width="2"/>
                    {{-- data arc track --}}
                    <circle cx="100" cy="100" r="39" stroke="rgba(0,122,254,.10)"  stroke-width="5"
                        transform="rotate(-90 100 100)"/>
                    {{-- data arc fill --}}
                    @php
                        $circumference = 2 * M_PI * 39; // ~245
                        $filled = $circumference * ($dataUsedPct / 100);
                        $empty  = $circumference - $filled;
                    @endphp
                    <circle cx="100" cy="100" r="39"
                        stroke="url(#blueGrad)" stroke-width="5"
                        stroke-dasharray="{{ number_format($filled, 1) }} {{ number_format($empty, 1) }}"
                        stroke-linecap="round"
                        transform="rotate(-90 100 100)"/>
                    <defs>
                        <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%"   stop-color="#0055d4"/>
                            <stop offset="100%" stop-color="#5bbeff"/>
                        </linearGradient>
                    </defs>

                {{-- ── CONNECTED STATE: green pulsing rings ── --}}
                @else
                    <circle cx="100" cy="100" r="87" stroke="rgba(48,209,88,.08)"  stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="71" stroke="rgba(48,209,88,.14)"  stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="55" stroke="rgba(48,209,88,.22)"  stroke-width="2"/>
                    <circle cx="100" cy="100" r="39" stroke="rgba(48,209,88,.35)"  stroke-width="2"/>
                @endif
            </svg>

            {{-- ── Pulse rings (plan-active behind button) ── --}}
            @if($connectionState === 'plan-active')
                <div class="pr"></div>
                <div class="pr"></div>
                <div class="pr"></div>
            @endif

            {{-- ── Green pulse rings (connected) ── --}}
            @if($connectionState === 'connected')
                <div class="pr-green"></div>
                <div class="pr-green"></div>
                <div class="pr-green"></div>
            @endif

            {{-- ── CONNECT BUTTON (plan-active only) ── --}}
            @if($connectionState === 'plan-active')
                <button class="connect-btn" wire:click="connect" wire:loading.attr="disabled">
                    <span class="connect-btn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="2" x2="12" y2="12"/>
                            <path d="M8.5 4.8A8 8 0 1 0 15.5 4.8"/>
                        </svg>
                    </span>
                    <span class="connect-btn-lbl" wire:loading.remove>Connect</span>
                    <span class="connect-btn-lbl" wire:loading>...</span>
                </button>
            @endif

            {{-- ── CONNECTED center dot ── --}}
            @if($connectionState === 'connected')
                <div class="conn-center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
            @endif
        </div>

        {{-- Status badge --}}
        <div class="status-row">
            @if($connectionState === 'no-plan')
                <div class="status-badge badge-noplan">
                    <span class="badge-dot"></span> No Active Plan
                </div>
                <span class="status-sub">Subscribe to get online</span>
            @elseif($connectionState === 'plan-active')
                <div class="status-badge badge-active">
                    <span class="badge-dot"></span> Plan Active
                </div>
                <span class="status-sub">Tap Connect to get online</span>
            @else
                <div class="status-badge badge-conn">
                    <span class="badge-dot"></span> Connected
                </div>
                <span class="status-sub">Session active · {{ $uptime ?? '—' }}</span>
            @endif
        </div>

        {{-- Hotspot detection strip --}}
        <div class="hotspot-strip {{ $isOnHotspot ? 'hs-ok' : 'hs-off' }}">
            @if($isOnHotspot)
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                HiFastLink hotspot detected
            @else
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Not on HiFastLink WiFi — connect first
            @endif
        </div>

        {{-- Data usage bar (plan-active / connected) --}}
        @if($connectionState !== 'no-plan' && $dataUsedPct > 0)
            <div class="data-bar-wrap">
                <div class="data-bar-labels">
                    <span>{{ $dataRemaining ?? '—' }} left</span>
                    <span>{{ $dataUsedPct }}% used</span>
                </div>
                <div class="data-bar-track">
                    <div class="data-bar-fill" style="width: {{ $dataUsedPct }}%"></div>
                </div>
            </div>
        @endif

        {{-- Stats strip (connected) --}}
        @if($connectionState === 'connected' && $activeSession)
            <div class="stats-strip">
                <div class="stat-item">
                    <span class="stat-val">{{ $sessionDownload ?? '—' }}</span>
                    <span class="stat-lbl">Down</span>
                </div>
                <div class="divider"></div>
                <div class="stat-item">
                    <span class="stat-val">{{ $sessionUpload ?? '—' }}</span>
                    <span class="stat-lbl">Up</span>
                </div>
                <div class="divider"></div>
                <div class="stat-item">
                    <span class="stat-val">{{ $expiryHuman ?? '—' }}</span>
                    <span class="stat-lbl">Expiry</span>
                </div>
            </div>
        @elseif($connectionState === 'plan-active' && $expiryHuman)
            <div class="stats-strip">
                <div class="stat-item">
                    <span class="stat-val">{{ $dataRemaining ?? '—' }}</span>
                    <span class="stat-lbl">Remaining</span>
                </div>
                <div class="divider"></div>
                <div class="stat-item" style="grid-column: 3/5">
                    <span class="stat-val">{{ $expiryHuman }}</span>
                    <span class="stat-lbl">Expiry</span>
                </div>
            </div>
        @endif
    </div>

    {{-- ══ TAB CONTENT ══════════════════════════════════ --}}
    <div class="tab-content">

        {{-- ─── HOME TAB ─────────────────────────────── --}}
        <div x-show="tab === 'home'">

            {{-- No-plan CTA --}}
            @if($connectionState === 'no-plan')
                <div class="noplan-cta">
                    <h4>Get Connected</h4>
                    <p>Purchase a data plan or redeem a voucher to access HiFastLink hotspots anywhere.</p>
                </div>
            @endif

            {{-- Voucher redemption --}}
            <div class="voucher-card">
                <label>Redeem Voucher / Invoice Code</label>
                <div class="voucher-row">
                    <input
                        type="text"
                        class="voucher-input"
                        placeholder="Enter code"
                        wire:model="voucherCode"
                        wire:keydown.enter="redeemVoucher"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        maxlength="20"
                    >
                    <button class="btn-redeem" wire:click="redeemVoucher" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="redeemVoucher">Apply</span>
                        <span wire:loading wire:target="redeemVoucher">...</span>
                    </button>
                </div>
                @error('voucherCode')
                    <p class="error-msg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Active session info --}}
            @if($connectionState === 'connected' && $activeSession)
                <div class="section-header">
                    <h3>Session Details</h3>
                </div>
                <div class="session-card">
                    <div class="session-row">
                        <span class="key">IP Address</span>
                        <span class="val">{{ $activeSession->framedipaddress ?? 'N/A' }}</span>
                    </div>
                    <div class="session-row">
                        <span class="key">Download</span>
                        <span class="val">{{ $sessionDownload ?? '—' }}</span>
                    </div>
                    <div class="session-row">
                        <span class="key">Upload</span>
                        <span class="val">{{ $sessionUpload ?? '—' }}</span>
                    </div>
                    <div class="session-row">
                        <span class="key">Duration</span>
                        <span class="val">{{ $uptime ?? '—' }}</span>
                    </div>
                </div>
            @endif

            {{-- Recent transactions --}}
            @if($recentTransactions->isNotEmpty())
                <div class="section-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="session-card">
                    @foreach($recentTransactions as $tx)
                        <div class="tx-item">
                            <div class="tx-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                                </svg>
                            </div>
                            <div class="tx-info">
                                <div class="tx-name">{{ $tx->plan?->name ?? 'Voucher' }}</div>
                                <div class="tx-date">{{ $tx->paid_at?->format('d M Y') ?? '—' }}</div>
                            </div>
                            <div class="tx-amount">₦{{ number_format($tx->amount, 0) }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── PLANS TAB ────────────────────────────── --}}
        <div x-show="tab === 'plans'" style="display:none">
            <div class="section-header">
                <h3>Available Plans</h3>
            </div>

            @forelse($plans as $plan)
                <div class="plan-card {{ $plan->is_featured ? 'featured' : '' }}">
                    <div class="plan-icon {{ $plan->is_featured ? 'feat' : '' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                        </svg>
                    </div>
                    <div class="plan-info">
                        <div class="plan-name">{{ $plan->name }}</div>
                        <div class="plan-meta">
                            {{ $plan->data_limit_human }} &middot; {{ $plan->validity_days }} day{{ $plan->validity_days == 1 ? '' : 's' }}
                            @if($plan->speed_limit_download)
                                &middot; {{ $plan->speed_limit_download }}k down
                            @endif
                        </div>
                    </div>
                    <div class="plan-price-wrap">
                        <span class="plan-price">₦{{ number_format($plan->price, 0) }}</span>
                        <form method="POST" action="{{ route('pay') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn-buy">Buy</button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="text-align:center; padding: 40px 0; color: var(--muted); font-size: 13px;">
                    No plans available for your area yet.
                </div>
            @endforelse
        </div>

        {{-- ─── DEVICES TAB ──────────────────────────── --}}
        <div x-show="tab === 'devices'" style="display:none">
            <div class="section-header">
                <h3>My Devices</h3>
            </div>
            <div style="text-align:center; padding: 40px 0; color: var(--muted); font-size: 13px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="opacity:.4; margin: 0 auto 12px; display: block;">
                    <rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
                </svg>
                Device management coming soon.<br>
                <a href="{{ route('dashboard') }}" style="color: var(--accent); font-size: 12px; margin-top: 8px; display: inline-block;">
                    View full dashboard →
                </a>
            </div>
        </div>

        {{-- ─── ACCOUNT TAB ──────────────────────────── --}}
        <div x-show="tab === 'account'" style="display:none">

            {{-- Profile header --}}
            <div style="text-align: center; padding: 20px 0 24px;">
                <div style="width:72px; height:72px; border-radius:50%; background: linear-gradient(135deg,#1a3a6e,#0055d4); display:flex; align-items:center; justify-content:center; margin: 0 auto 10px; font-family:'Sora',sans-serif; font-size:24px; font-weight:700; color:#fff; box-shadow: 0 0 0 3px rgba(0,122,254,.25);">
                    {{ $user->initials }}
                </div>
                <div style="font-family:'Sora',sans-serif; font-size:17px; font-weight:700; color: var(--text);">{{ $user->display_name }}</div>
                <div style="font-size:12px; color: var(--muted); margin-top: 3px;">{{ $user->username ?? $user->email }}</div>
            </div>

            <a href="{{ route('profile.edit') }}" class="account-row">
                <div class="account-row-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <span class="account-row-label">Edit Profile</span>
                <span class="account-row-arrow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            </a>

            <a href="{{ route('dashboard') }}" class="account-row">
                <div class="account-row-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <span class="account-row-label">Full Dashboard</span>
                <span class="account-row-arrow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            </a>

            <a href="{{ route('request-custom-plans') }}" class="account-row">
                <div class="account-row-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                </div>
                <span class="account-row-label">Request Custom Plan</span>
                <span class="account-row-arrow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            </a>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                @csrf
                <button type="submit" class="account-row" style="width:100%; text-align:left; color: #ff5f5f;">
                    <div class="account-row-icon" style="background: rgba(255,95,95,.1); color: #ff5f5f;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </div>
                    <span class="account-row-label" style="color: #ff5f5f;">Sign Out</span>
                </button>
            </form>
        </div>

    </div>{{-- end tab-content --}}

    {{-- ══ HOTSPOT WARNING MODAL ═══════════════════════ --}}
    @if($showWarning)
        <div class="overlay" wire:click.self="dismissWarning">
            <div class="modal-sheet">
                <div class="modal-icon">📶</div>
                <div class="modal-title">Not on HiFastLink WiFi</div>
                <div class="modal-body">
                    You need to connect to a HiFastLink WiFi network first, then tap Connect to get online.<br><br>
                    Look for networks like <strong>HiFastLink</strong>, <strong>BasmelCare</strong>, or check with your host.
                </div>
                <button class="btn-modal-ok" wire:click="dismissWarning">Got it</button>
            </div>
        </div>
    @endif

    {{-- ══ BOTTOM TAB BAR ══════════════════════════════ --}}
    <nav class="tab-bar safe-bottom">

        <button class="tab-btn" :class="tab==='home' ? 'active':''" @click="tab='home'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Home
        </button>

        <button class="tab-btn" :class="tab==='plans' ? 'active':''" @click="tab='plans'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
            Plans
        </button>

        <button class="tab-btn" :class="tab==='devices' ? 'active':''" @click="tab='devices'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
            </svg>
            Devices
        </button>

        <button class="tab-btn" :class="tab==='account' ? 'active':''" @click="tab='account'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            Account
        </button>

    </nav>

</div>
