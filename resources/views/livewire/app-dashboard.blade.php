<style>
/* ═══════════════════════════════════════════════════════
   HIFASTLINK APP — design tokens
═══════════════════════════════════════════════════════ */
:root {
    --bg:         #060a14;
    --sidebar-bg: #080d1a;
    --glass:      rgba(255,255,255,.032);
    --glass-2:    rgba(255,255,255,.06);
    --border:     rgba(255,255,255,.07);
    --border-2:   rgba(255,255,255,.12);
    --text:       #eaf0ff;
    --muted:      #68788e;
    --accent:     #0a84ff;
    --accent-dim: rgba(10,132,255,.14);
    --accent-glow:rgba(10,132,255,.5);
    --green:      #32d74b;
    --green-dim:  rgba(50,215,75,.14);
    --green-glow: rgba(50,215,75,.45);
    --amber:      #ff9f0a;
    --amber-dim:  rgba(255,159,10,.14);
    --red:        #ff453a;
    --tab-h:      64px;
    --sidebar-w:  340px;
    --radius-card:22px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

/* ─── Background ─────────────────────────────── */
.app-root {
    min-height: 100dvh;
    background: var(--bg);
    background-image:
        radial-gradient(ellipse 70% 50% at 20% 10%, rgba(10,132,255,.09), transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 80%, rgba(50,215,75,.05), transparent 60%);
    display: flex;
    flex-direction: column;
    position: relative;
}

/* ─── SIDEBAR ────────────────────────────────── */
.sidebar {
    width: 100%;
    display: contents; /* on mobile, children just flow in order */
}

/* ─── TOP BAR ────────────────────────────────── */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 10px;
    padding-top: calc(16px + env(safe-area-inset-top, 0px));
    flex-shrink: 0;
}
.topbar-left { display: flex; flex-direction: column; }
.topbar-label { font-size: 11px; color: var(--muted); letter-spacing: .05em; text-transform: uppercase; }
.topbar-name  { font-family: 'Sora', sans-serif; font-size: 19px; font-weight: 700; color: var(--text); line-height: 1.2; margin-top: 1px; }

.avatar {
    width: 42px; height: 42px; border-radius: 50%;
    background: linear-gradient(135deg, #0a2a5e 0%, #0660d4 100%);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Sora', sans-serif; font-weight: 700; font-size: 15px; color: #fff;
    box-shadow: 0 0 0 2px rgba(10,132,255,.35), 0 4px 12px rgba(0,0,0,.4);
    flex-shrink: 0;
}

/* Desktop logo (hidden on mobile) */
.sidebar-logo {
    display: none;
}

/* ─── CONNECTION CARD ────────────────────────── */
.conn-card {
    margin: 0 14px 12px;
    background: var(--glass);
    border: 1px solid var(--border);
    border-radius: var(--radius-card);
    padding: 22px 18px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    box-shadow: 0 0 0 1px rgba(255,255,255,.04) inset, 0 24px 64px rgba(0,0,0,.35);
}
/* subtle inner glow that shifts with state */
.conn-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(10,132,255,.07), transparent 70%);
    pointer-events: none;
}
.conn-card.state-connected::before {
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(50,215,75,.07), transparent 70%);
}
.conn-card.state-noplan::before {
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(255,159,10,.05), transparent 70%);
}

/* ─── VIZ ────────────────────────────────────── */
.viz-wrap {
    position: relative;
    width: 210px; height: 210px;
    flex-shrink: 0;
}
.viz-wrap svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }

/* Connect button */
.connect-btn {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 88px; height: 88px;
    border-radius: 50%;
    background: radial-gradient(circle at 38% 28%, #5ab8ff 0%, #0060e6 55%, #003fa8 100%);
    box-shadow:
        0 0 0 4px rgba(10,132,255,.18),
        0 0 36px rgba(10,132,255,.65),
        0 10px 28px rgba(0,0,0,.6),
        inset 0 2px 0 rgba(255,255,255,.32),
        inset 0 -4px 8px rgba(0,0,0,.4);
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
    cursor: pointer; z-index: 3; border: none;
    transition: box-shadow .15s, transform .1s;
    -webkit-tap-highlight-color: transparent;
}
.connect-btn:active {
    transform: translate(-50%,-50%) scale(.93);
}
.connect-btn-icon { color: #fff; width: 28px; height: 28px; filter: drop-shadow(0 1px 3px rgba(0,0,0,.5)); }
.connect-btn-icon svg { width: 100%; height: 100%; }
.connect-btn-lbl  { color: rgba(255,255,255,.88); font-size: 9px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; font-family: 'Sora', sans-serif; }

/* Pulse rings */
.pr {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    border-radius: 50%;
    border: 1.5px solid rgba(10,132,255,.5);
    width: 88px; height: 88px;
    animation: pr-expand 2.6s ease-out infinite;
    animation-fill-mode: backwards;
    pointer-events: none; z-index: 1;
}
.pr:nth-child(2) { animation-delay: .88s; }
.pr:nth-child(3) { animation-delay: 1.76s; }
@keyframes pr-expand {
    0%   { transform: translate(-50%,-50%) scale(1);   opacity: .55; }
    100% { transform: translate(-50%,-50%) scale(3.4); opacity: 0;   }
}

.pr-green {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    border-radius: 50%;
    border: 1.5px solid rgba(50,215,75,.5);
    width: 78px; height: 78px;
    animation: pr-expand 2.6s ease-out infinite;
    animation-fill-mode: backwards;
    pointer-events: none; z-index: 1;
}
.pr-green:nth-child(2) { animation-delay: .88s; }
.pr-green:nth-child(3) { animation-delay: 1.76s; }

/* Connected center */
.conn-center {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    width: 78px; height: 78px; border-radius: 50%;
    background: radial-gradient(circle at 38% 28%, #7fffa6 0%, #1fcc46 55%, #0d8c2e 100%);
    box-shadow:
        0 0 0 4px rgba(50,215,75,.18),
        0 0 32px rgba(50,215,75,.6),
        0 8px 20px rgba(0,0,0,.5),
        inset 0 2px 0 rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center; z-index: 3;
}
.conn-center svg { width: 30px; height: 30px; color: #fff; filter: drop-shadow(0 1px 3px rgba(0,0,0,.4)); }

/* ─── Status badge ───────────────────────────── */
.status-row { display: flex; flex-direction: column; align-items: center; gap: 5px; }
.status-badge {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 5px 16px; border-radius: 999px;
    font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    font-family: 'Sora', sans-serif;
}
.badge-noplan { background: var(--amber-dim);  color: var(--amber); border: 1px solid rgba(255,159,10,.22); }
.badge-active  { background: var(--accent-dim); color: var(--accent); border: 1px solid rgba(10,132,255,.22); }
.badge-conn    { background: var(--green-dim);  color: var(--green);  border: 1px solid rgba(50,215,75,.22); }
.badge-dot {
    width: 6px; height: 6px; border-radius: 50%;
    animation: dot-blink 2s ease-in-out infinite;
}
.badge-noplan .badge-dot { background: var(--amber); }
.badge-active  .badge-dot { background: var(--accent); }
.badge-conn    .badge-dot { background: var(--green); }
@keyframes dot-blink { 0%,100%{opacity:1} 50%{opacity:.2} }
.status-sub { font-size: 12px; color: var(--muted); }

/* ─── Hotspot strip ──────────────────────────── */
.hotspot-strip {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 14px; border-radius: 12px;
    font-size: 12px; font-weight: 500; width: 100%;
}
.hs-ok  { background: var(--green-dim); color: #52e87a; border: 1px solid rgba(50,215,75,.18); }
.hs-off { background: var(--amber-dim); color: var(--amber); border: 1px solid rgba(255,159,10,.18); }

/* ─── Data bar ───────────────────────────────── */
.data-bar-wrap { width: 100%; }
.data-bar-labels { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); margin-bottom: 6px; }
.data-bar-track  { height: 4px; border-radius: 999px; background: rgba(255,255,255,.06); overflow: hidden; }
.data-bar-fill   { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #0046c0, #0a84ff, #5bbeff); transition: width .6s ease; }

/* ─── Stats strip ────────────────────────────── */
.stats-strip { display: flex; width: 100%; align-items: center; }
.stat-divider { width: 1px; height: 28px; background: var(--border); flex-shrink: 0; }
.stat-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; }
.stat-val { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 500; color: var(--text); }
.stat-lbl { font-size: 9px; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; }

/* ─── MAIN AREA ──────────────────────────────── */
.main-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* ─── TAB CONTENT ────────────────────────────── */
.tab-content {
    flex: 1;
    overflow-y: auto;
    padding: 6px 14px 0;
    padding-bottom: calc(var(--tab-h) + 16px + env(safe-area-inset-bottom, 0px));
}

/* ─── Section header ─────────────────────────── */
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; padding-top: 6px;
}
.section-header h3 {
    font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text); letter-spacing: .01em;
}
.section-header a { font-size: 12px; color: var(--accent); }

/* ─── Glass card base ────────────────────────── */
.glass-card {
    background: var(--glass);
    border: 1px solid var(--border);
    border-radius: var(--radius-card);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/* ─── No-plan CTA ────────────────────────────── */
.noplan-cta {
    border: 1px dashed rgba(10,132,255,.2);
    border-radius: var(--radius-card);
    padding: 28px 20px;
    text-align: center;
    margin-bottom: 14px;
    background: rgba(10,132,255,.03);
}
.noplan-cta h4 { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.noplan-cta p  { font-size: 13px; color: var(--muted); line-height: 1.6; }

/* ─── Voucher card ───────────────────────────── */
.voucher-card { padding: 16px; margin-bottom: 12px; }
.voucher-label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 9px; letter-spacing: .05em; text-transform: uppercase; }
.voucher-row { display: flex; gap: 8px; }
.voucher-input {
    flex: 1; background: rgba(255,255,255,.04); border: 1px solid var(--border);
    border-radius: 12px; padding: 11px 14px;
    color: var(--text); font-family: 'JetBrains Mono', monospace; font-size: 14px;
    letter-spacing: .1em; text-transform: uppercase; outline: none; transition: border-color .15s;
}
.voucher-input::placeholder { color: var(--muted); letter-spacing: .03em; text-transform: none; font-family: 'Outfit', sans-serif; }
.voucher-input:focus { border-color: var(--accent); background: rgba(10,132,255,.05); }
.btn-redeem {
    background: var(--accent); color: #fff; border: none; border-radius: 12px; padding: 11px 20px;
    font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Sora', sans-serif;
    white-space: nowrap; -webkit-tap-highlight-color: transparent; transition: background .15s;
    box-shadow: 0 4px 16px rgba(10,132,255,.4);
}
.btn-redeem:hover { background: #0070e0; }
.error-msg { font-size: 12px; color: var(--red); margin-top: 6px; }

/* ─── Session card ───────────────────────────── */
.session-card { padding: 4px 16px; margin-bottom: 12px; }
.session-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px solid var(--border); }
.session-row:last-child { border-bottom: none; padding-bottom: 3px; }
.session-row .key { font-size: 13px; color: var(--muted); }
.session-row .val { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text); }

/* ─── Activity list ──────────────────────────── */
.activity-card { padding: 4px 16px; margin-bottom: 12px; }
.tx-item { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border); }
.tx-item:last-child { border-bottom: none; padding-bottom: 3px; }
.tx-icon { width: 38px; height: 38px; border-radius: 12px; background: var(--accent-dim); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--accent); }
.tx-info { flex: 1; }
.tx-name { font-size: 13px; font-weight: 500; color: var(--text); }
.tx-date { font-size: 11px; color: var(--muted); margin-top: 2px; }
.tx-amount { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 600; color: var(--green); }

/* ─── Plan groups ────────────────────────────── */
.plan-group { margin-bottom: 22px; }
.plan-group-header {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 12px; padding: 0 2px;
}
.plan-group-icon {
    width: 28px; height: 28px; border-radius: 9px; flex-shrink: 0;
    background: var(--accent-dim); display: flex; align-items: center; justify-content: center;
    color: var(--accent);
}
.plan-group-icon.universal { background: rgba(50,215,75,.12); color: var(--green); }
.plan-group-icon.other     { background: var(--amber-dim); color: var(--amber); }
.plan-group-title { font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700; color: var(--text); flex: 1; }
.plan-group-count { font-size: 11px; color: var(--muted); background: var(--glass-2); padding: 2px 9px; border-radius: 999px; }

/* ─── Plan cards ─────────────────────────────── */
.plans-list { display: flex; flex-direction: column; gap: 10px; }
.plan-card {
    background: var(--glass);
    border: 1px solid var(--border);
    border-radius: var(--radius-card);
    padding: 16px;
    display: flex; align-items: center; gap: 14px;
    transition: border-color .2s;
}
.plan-card.featured {
    background: linear-gradient(135deg, rgba(10,132,255,.12) 0%, rgba(10,132,255,.04) 100%);
    border-color: rgba(10,132,255,.28);
}
.plan-card.other-location {
    opacity: .75;
    border-style: dashed;
}
.plan-icon {
    width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
    background: var(--accent-dim); display: flex; align-items: center; justify-content: center; color: var(--accent);
}
.plan-icon.feat { background: rgba(10,132,255,.22); }
.plan-info { flex: 1; min-width: 0; }
.plan-name { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.plan-meta { font-size: 12px; color: var(--muted); margin-top: 3px; }
.plan-loc-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 600; letter-spacing: .03em;
    padding: 2px 8px; border-radius: 999px; margin-top: 5px;
}
.loc-here    { background: var(--green-dim);  color: var(--green);  }
.loc-any     { background: rgba(50,215,75,.08); color: #52c56e;    }
.loc-other   { background: var(--amber-dim);  color: var(--amber);  }
.plan-price-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
.plan-price { font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 700; color: var(--accent); }
.btn-buy {
    display: inline-block; padding: 7px 18px;
    background: var(--accent); color: #fff; border: none; border-radius: 999px;
    font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;
    font-family: 'Sora', sans-serif; -webkit-tap-highlight-color: transparent;
    box-shadow: 0 4px 14px rgba(10,132,255,.35); transition: background .15s;
}
.btn-buy:hover { background: #0070e0; }
.btn-buy.dimmed { background: rgba(10,132,255,.45); box-shadow: none; }

/* ─── Account tab ────────────────────────────── */
.profile-header { text-align: center; padding: 24px 0 20px; }
.profile-avatar-lg {
    width: 76px; height: 76px; border-radius: 50%;
    background: linear-gradient(135deg, #0a2a5e, #0660d4);
    display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;
    font-family: 'Sora', sans-serif; font-size: 26px; font-weight: 700; color: #fff;
    box-shadow: 0 0 0 3px rgba(10,132,255,.28), 0 8px 24px rgba(0,0,0,.4);
}
.profile-name { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: var(--text); }
.profile-sub  { font-size: 12px; color: var(--muted); margin-top: 4px; }

.account-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 16px; background: var(--glass);
    border: 1px solid var(--border); border-radius: 16px;
    margin-bottom: 10px; cursor: pointer; transition: background .15s, border-color .15s;
    -webkit-tap-highlight-color: transparent; text-decoration: none; color: var(--text);
}
.account-row:hover { background: var(--glass-2); border-color: var(--border-2); }
.account-row-icon { width: 40px; height: 40px; border-radius: 12px; background: var(--glass-2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--muted); }
.account-row-label { flex: 1; font-size: 14px; font-weight: 500; }
.account-row-arrow { color: var(--muted); }

/* ─── Warning modal ──────────────────────────── */
.overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.7);
    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    display: flex; align-items: flex-end; justify-content: center;
    z-index: 99; padding: 0 16px;
    padding-bottom: calc(32px + env(safe-area-inset-bottom, 0px));
}
.modal-sheet {
    background: #0e1728; border: 1px solid var(--border-2);
    border-radius: 28px 28px 22px 22px; padding: 28px 22px 22px;
    width: 100%; max-width: 400px; text-align: center;
    box-shadow: 0 -24px 64px rgba(0,0,0,.5);
}
.modal-icon  { margin-bottom: 14px; display: flex; justify-content: center; }
.modal-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.modal-body  { font-size: 13px; color: var(--muted); line-height: 1.65; margin-bottom: 22px; }
.btn-modal-ok {
    display: block; width: 100%; padding: 14px;
    background: var(--glass-2); border: 1px solid var(--border-2);
    border-radius: 14px; color: var(--text); font-size: 14px; font-weight: 600;
    cursor: pointer; font-family: 'Sora', sans-serif; transition: background .15s;
}
.btn-modal-ok:hover { background: rgba(255,255,255,.09); }

/* ─── Toast ──────────────────────────────────── */
.toast-wrap {
    position: fixed; top: calc(env(safe-area-inset-top, 0px) + 16px);
    left: 50%; transform: translateX(-50%);
    z-index: 100; pointer-events: none; width: calc(100% - 32px); max-width: 400px;
}
.toast {
    background: #0e1728; border: 1px solid var(--border-2);
    border-radius: 14px; padding: 12px 18px; font-size: 13px; font-weight: 500;
    color: var(--text); box-shadow: 0 8px 32px rgba(0,0,0,.5);
}
.toast.success { border-color: rgba(50,215,75,.3); color: #52e87a; }
.toast.error   { border-color: rgba(255,69,58,.3); color: var(--red); }

/* ─── BOTTOM TAB BAR (mobile) ────────────────── */
.tab-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: var(--tab-h);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    background: rgba(6,10,20,.88);
    backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border-top: 1px solid var(--border);
    display: flex; z-index: 50;
}
.tab-btn {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
    background: none; border: none; cursor: pointer; color: var(--muted);
    transition: color .2s; -webkit-tap-highlight-color: transparent;
    font-size: 10px; font-family: 'Outfit', sans-serif; font-weight: 500; letter-spacing: .03em;
    position: relative;
}
.tab-btn svg { width: 22px; height: 22px; transition: transform .2s; }
.tab-btn.active { color: var(--accent); }
.tab-btn.active svg { transform: scale(1.12); }
.tab-btn.active::before {
    content: ''; position: absolute; top: 8px;
    width: 28px; height: 3px; border-radius: 999px;
    background: var(--accent); opacity: .4;
    top: -1px;
}

/* ─── Plan card top row (icon + info side by side) ── */
.plan-card-top { display: flex; align-items: center; gap: 12px; width: 100%; }

/* ─── Account rows (mobile: column stack) ─────── */
.account-rows { display: flex; flex-direction: column; gap: 10px; }

/* ─── Desktop nav hidden on mobile ──────────── */
.desktop-nav { display: none; }

/* ─── SVG ring animations ────────────────────── */
@keyframes scan-arc {
    from { stroke-dashoffset: 0; }
    to   { stroke-dashoffset: -376; }
}

/* ═══════════════════════════════════════════════════════
   DESKTOP — 768px+
═══════════════════════════════════════════════════════ */
@media (min-width: 768px) {

    .app-root {
        flex-direction: row;
        align-items: stretch;
        background-image:
            radial-gradient(ellipse 55% 70% at 14% 30%, rgba(10,132,255,.1), transparent 65%),
            radial-gradient(ellipse 40% 50% at 85% 75%, rgba(50,215,75,.06), transparent 65%);
    }

    /* Sidebar becomes a real column */
    .sidebar {
        display: flex;
        flex-direction: column;
        width: var(--sidebar-w);
        min-width: var(--sidebar-w);
        background: rgba(8,13,26,.7);
        border-right: 1px solid var(--border);
        height: 100dvh;
        position: sticky;
        top: 0;
        overflow-y: auto;
        gap: 0;
        flex-shrink: 0;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 22px 20px 10px;
        font-family: 'Sora', sans-serif;
        font-size: 17px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: .02em;
    }
    .sidebar-logo-icon {
        width: 32px; height: 32px; border-radius: 10px;
        background: linear-gradient(135deg, #0046c0, #0a84ff);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(10,132,255,.4);
    }
    .sidebar-logo-icon svg { width: 18px; height: 18px; color: #fff; }

    .topbar { padding: 12px 20px 8px; }
    .topbar-name { font-size: 17px; }

    .conn-card {
        margin: 0 14px 14px;
        border-radius: 20px;
    }

    .viz-wrap { width: 220px; height: 220px; }

    /* Main area fills remaining space */
    .main-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 100dvh;
    }

    /* Desktop top nav */
    .desktop-nav {
        display: flex;
        align-items: center;
        gap: 2px;
        padding: 18px 24px 0;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .desktop-nav-btn {
        padding: 10px 20px 12px;
        background: none; border: none; cursor: pointer;
        color: var(--muted); font-size: 13px; font-weight: 600;
        font-family: 'Sora', sans-serif; letter-spacing: .01em;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        transition: color .2s, border-color .2s;
        white-space: nowrap;
    }
    .desktop-nav-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
    .desktop-nav-btn:hover:not(.active) { color: var(--text); }

    /* Tab content on desktop */
    .tab-content {
        padding: 24px 28px 28px;
        padding-bottom: 28px; /* no tab bar */
        overflow-y: auto;
        flex: 1;
    }

    /* Hide mobile tab bar */
    .tab-bar { display: none; }

    /* Conn-card always visible in sidebar regardless of active tab */
    .conn-card { display: flex !important; }

    /* Plans in a grid on desktop */
    .plans-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
    }
    .plan-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .plan-card-top { display: flex; align-items: flex-start; gap: 12px; width: 100%; }
    .plan-price-wrap { display: flex; align-items: center; justify-content: space-between; width: 100%; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }

    /* Account: two-column layout on desktop */
    .account-rows { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

    /* Toast position for desktop */
    .toast-wrap { left: calc(var(--sidebar-w) + 24px); transform: none; max-width: 380px; }
}

/* ── Profile edit form ── */
.edit-profile-hdr {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    gap: 12px;
}
.edit-back-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    background: none;
    border: none;
    color: var(--accent);
    font-size: 14px;
    font-weight: 500;
    padding: 0;
    cursor: pointer;
    font-family: inherit;
}
.edit-profile-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
}
.prof-form {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.prof-field { display: flex; flex-direction: column; gap: 5px; }
.prof-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.prof-input {
    background: rgba(255,255,255,0.06);
    border: 1.5px solid rgba(255,255,255,0.18);
    border-radius: 12px;
    padding: 11px 14px;
    font-size: 15px;
    color: var(--text);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    font-family: inherit;
    width: 100%;
}
.prof-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.prof-input::placeholder { color: var(--muted); }
.prof-error { font-size: 12px; color: var(--red); margin-top: 2px; }
.prof-save-btn {
    display: block;
    width: calc(100% - 40px);
    margin: 2px 20px 20px;
    padding: 13px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: opacity 0.15s;
}
.prof-save-btn:active { opacity: 0.8; }
.prof-pw-section { border-top: 1px solid var(--border); margin-top: 4px; }
.prof-pw-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    background: none;
    border: none;
    padding: 16px 20px;
    color: var(--text);
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
}
.prof-pw-toggle svg { transition: transform 0.2s; }
.prof-pw-btn {
    background: rgba(255,255,255,0.06);
    color: var(--text);
    border: 1.5px solid rgba(255,255,255,0.13);
    margin-top: 4px;
}

/* ── Transaction history ── */
.txn-list { padding: 4px 20px 8px; display: flex; flex-direction: column; }
.txn-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}
.txn-row:last-child { border-bottom: none; }
.txn-icon {
    width: 38px; height: 38px;
    border-radius: 11px;
    background: var(--glass-2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: var(--accent);
}
.txn-info { flex: 1; min-width: 0; }
.txn-title { font-size: 14px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.txn-sub   { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
.txn-right { text-align: right; flex-shrink: 0; }
.txn-amount { font-size: 14px; font-weight: 600; color: var(--text); }
.txn-badge { display: inline-block; margin-top: 3px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; border-radius: 5px; padding: 1px 6px; }
.txn-badge.success { background: var(--green-dim);  color: var(--green); }
.txn-badge.pending { background: var(--amber-dim);  color: var(--amber); }
.txn-badge.failed  { background: rgba(255,69,58,.14); color: var(--red); }
.txn-empty { text-align: center; padding: 50px 20px; color: var(--muted); font-size: 14px; }
.txn-pages {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px 20px;
    border-top: 1px solid var(--border);
}
.txn-page-btn {
    background: var(--glass-2);
    border: 1px solid var(--border-2);
    border-radius: 10px;
    padding: 8px 18px;
    color: var(--text);
    font-size: 13px; font-weight: 500;
    cursor: pointer; font-family: inherit;
    transition: opacity 0.15s;
}
.txn-page-btn:disabled { opacity: 0.3; cursor: default; }
.txn-page-info { font-size: 13px; color: var(--muted); }

/* ── Plan queue (Up Next) ── */
.queue-item {
    background: var(--glass-2);
    border: 1px solid var(--border-2);
    border-radius: 16px;
    padding: 13px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}
.queue-pos {
    width: 24px; height: 24px;
    border-radius: 50%;
    background: var(--glass-2);
    border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--muted);
    flex-shrink: 0;
}
.queue-info { flex: 1; min-width: 0; }
.queue-name { font-size: 14px; font-weight: 600; color: var(--text); }
.queue-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.queue-start-btn {
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 7px 13px;
    font-size: 12px; font-weight: 700;
    cursor: pointer; font-family: inherit;
    flex-shrink: 0;
    transition: opacity 0.15s;
    white-space: nowrap;
}
.queue-start-btn:active { opacity: 0.8; }
</style>

{{-- ════════════════════════════════════════════════════════
     APP ROOT
════════════════════════════════════════════════════════ --}}
<div
    class="app-root"
    x-data="{
        tab: 'home',
        toast: null,
        showToast(msg, type) { this.toast = { msg, type }; setTimeout(() => this.toast = null, 3400); }
    }"
    x-on:toast.window="showToast($event.detail.message, $event.detail.type ?? 'info')"
    wire:poll.15000ms="pollConnection"
>

    {{-- ══ TOAST ════════════════════════════════════════════ --}}
    <div class="toast-wrap" x-show="toast" x-transition.opacity style="display:none">
        <div class="toast" :class="toast?.type === 'success' ? 'success' : (toast?.type === 'error' ? 'error' : '')" x-text="toast?.msg"></div>
    </div>

    {{-- ══ SIDEBAR ══════════════════════════════════════════ --}}
    <aside class="sidebar">

        {{-- Logo (desktop only) --}}
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
            </div>
            HiFastLink
        </div>

        {{-- Top bar --}}
        <div class="topbar safe-top">
            <div class="topbar-left">
                <span class="topbar-label">Welcome back</span>
                <span class="topbar-name">{{ $user->display_name }}</span>
            </div>
            <div class="avatar">{{ $user->initials }}</div>
        </div>

        {{-- Connection card: mobile = Home tab only; desktop = always visible in sidebar --}}
        <div class="conn-card {{ $connectionState === 'connected' ? 'state-connected' : ($connectionState === 'no-plan' ? 'state-noplan' : '') }}"
             x-show="tab === 'home'"
             x-cloak>

            {{-- Visualization --}}
            <div class="viz-wrap">

                <svg viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <filter id="ring-glow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="3" result="blur"/>
                            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#0046c0"/><stop offset="100%" stop-color="#5bbeff"/>
                        </linearGradient>
                    </defs>

                    @if($connectionState === 'no-plan')
                        {{-- Amber scanning rings --}}
                        <circle cx="110" cy="110" r="97" stroke="rgba(255,159,10,.07)" stroke-width="1"/>
                        <circle cx="110" cy="110" r="80" stroke="rgba(255,159,10,.10)" stroke-width="1.5"/>
                        <circle cx="110" cy="110" r="62" stroke="rgba(255,159,10,.15)" stroke-width="1.5"/>
                        <circle cx="110" cy="110" r="44" stroke="rgba(255,159,10,.22)" stroke-width="2"/>
                        <circle cx="110" cy="110" r="97" stroke="rgba(255,159,10,.6)" stroke-width="2"
                            stroke-dasharray="65 545" stroke-linecap="round"
                            transform="rotate(-90 110 110)"
                            style="animation: scan-arc 2.2s linear infinite"/>
                        <circle cx="110" cy="110" r="62" stroke="rgba(255,159,10,.35)" stroke-width="1.5"
                            stroke-dasharray="40 350" stroke-linecap="round"
                            transform="rotate(-90 110 110)"
                            style="animation: scan-arc 2.2s linear infinite reverse"/>
                        <circle cx="110" cy="110" r="12" fill="rgba(255,159,10,.15)" stroke="rgba(255,159,10,.4)" stroke-width="1.5"/>
                        <circle cx="110" cy="110" r="5" fill="#ff9f0a"/>

                    @elseif($connectionState === 'plan-active')
                        {{-- Blue data arc rings --}}
                        <circle cx="110" cy="110" r="97" stroke="rgba(10,132,255,.06)"  stroke-width="1"/>
                        <circle cx="110" cy="110" r="80" stroke="rgba(10,132,255,.09)"  stroke-width="1.5"/>
                        <circle cx="110" cy="110" r="62" stroke="rgba(10,132,255,.14)"  stroke-width="1.5"/>
                        {{-- Data arc track --}}
                        <circle cx="110" cy="110" r="44" stroke="rgba(10,132,255,.08)" stroke-width="6"
                            transform="rotate(-90 110 110)"/>
                        {{-- Data arc fill --}}
                        @php
                            $circ  = 2 * M_PI * 44;
                            $fill  = $circ * ($dataUsedPct / 100);
                            $empty = $circ - $fill;
                        @endphp
                        <circle cx="110" cy="110" r="44"
                            stroke="url(#blueGrad)" stroke-width="6"
                            stroke-dasharray="{{ number_format($fill,1) }} {{ number_format($empty,1) }}"
                            stroke-linecap="round"
                            transform="rotate(-90 110 110)"
                            filter="url(#ring-glow)"/>

                    @else
                        {{-- Green connected rings --}}
                        <circle cx="110" cy="110" r="97" stroke="rgba(50,215,75,.06)"  stroke-width="1"/>
                        <circle cx="110" cy="110" r="80" stroke="rgba(50,215,75,.10)"  stroke-width="1.5"/>
                        <circle cx="110" cy="110" r="62" stroke="rgba(50,215,75,.16)"  stroke-width="2"/>
                        <circle cx="110" cy="110" r="44" stroke="rgba(50,215,75,.26)"  stroke-width="2" filter="url(#ring-glow)"/>
                    @endif
                </svg>

                {{-- Pulse rings --}}
                @if($connectionState === 'plan-active')
                    <div class="pr"></div><div class="pr"></div><div class="pr"></div>
                @endif
                @if($connectionState === 'connected')
                    <div class="pr-green"></div><div class="pr-green"></div><div class="pr-green"></div>
                @endif

                {{-- Connect button --}}
                @if($connectionState === 'plan-active')
                    <button class="connect-btn" id="app-connect-btn" onclick="appConnect(this)">
                        <span class="connect-btn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <line x1="12" y1="2" x2="12" y2="12"/><path d="M8.5 4.8A8 8 0 1 0 15.5 4.8"/>
                            </svg>
                        </span>
                        <span class="connect-btn-lbl">Connect</span>
                    </button>
                @endif

                {{-- Connected checkmark --}}
                @if($connectionState === 'connected')
                    <div class="conn-center">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                @endif

                {{-- No-plan amber dot center --}}
                @if($connectionState === 'no-plan')
                    {{-- center dot is drawn in SVG --}}
                @endif
            </div>

            {{-- Status badge --}}
            <div class="status-row">
                @if($connectionState === 'no-plan')
                    <div class="status-badge badge-noplan"><span class="badge-dot"></span> No Active Plan</div>
                    <span class="status-sub">Subscribe to get online</span>
                @elseif($connectionState === 'plan-active')
                    <div class="status-badge badge-active"><span class="badge-dot"></span> Plan Active</div>
                    <span class="status-sub">Tap Connect to get online</span>
                @else
                    <div class="status-badge badge-conn"><span class="badge-dot"></span> Connected</div>
                    <span class="status-sub">Session active · {{ $uptime ?? '—' }}</span>
                @endif
            </div>

            {{-- Hotspot strip --}}
            <div class="hotspot-strip {{ $isOnHotspot ? 'hs-ok' : 'hs-off' }}">
                @if($isOnHotspot)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    On HiFastLink WiFi
                @else
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Not on HiFastLink WiFi — connect first
                @endif
            </div>

            {{-- Data bar --}}
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

            {{-- Stats strip --}}
            @if($connectionState === 'connected' && $activeSession)
                <div class="stats-strip">
                    <div class="stat-item">
                        <span class="stat-val">{{ $sessionDownload ?? '—' }}</span>
                        <span class="stat-lbl">Down</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-val">{{ $sessionUpload ?? '—' }}</span>
                        <span class="stat-lbl">Up</span>
                    </div>
                    <div class="stat-divider"></div>
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
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-val">{{ $expiryHuman }}</span>
                        <span class="stat-lbl">Expiry</span>
                    </div>
                </div>
            @endif

        </div>{{-- end conn-card --}}
    </aside>{{-- end sidebar --}}

    {{-- ══ MAIN AREA ════════════════════════════════════════ --}}
    <div class="main-area">

        {{-- Desktop top nav (hidden on mobile via CSS, shown at 768px+) --}}
        <nav class="desktop-nav">
            <button class="desktop-nav-btn" :class="tab==='home'    ? 'active':''" @click="tab='home'">Home</button>
            <button class="desktop-nav-btn" :class="tab==='plans'   ? 'active':''" @click="tab='plans'">Plans</button>
            <button class="desktop-nav-btn" :class="tab==='devices' ? 'active':''" @click="tab='devices'">Devices</button>
            <button class="desktop-nav-btn" :class="tab==='account' ? 'active':''" @click="tab='account'">Account</button>
        </nav>

        {{-- Tab content --}}
        <div class="tab-content">

            {{-- ─── HOME TAB ─────────────────────────────── --}}
            <div x-show="tab === 'home'">

                @if($connectionState === 'no-plan')
                    <div class="noplan-cta">
                        <h4>Get Connected</h4>
                        <p>Purchase a data plan below or redeem a voucher code to start browsing on any HiFastLink hotspot.</p>
                    </div>
                @endif

                {{-- Voucher --}}
                <div class="voucher-card glass-card">
                    <label class="voucher-label">Redeem Voucher / Invoice Code</label>
                    <div class="voucher-row">
                        <input type="text" class="voucher-input" placeholder="Enter code"
                            wire:model="voucherCode" wire:keydown.enter="redeemVoucher"
                            autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="20">
                        <button class="btn-redeem" wire:click="redeemVoucher" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="redeemVoucher">Apply</span>
                            <span wire:loading wire:target="redeemVoucher">···</span>
                        </button>
                    </div>
                    @error('voucherCode')<p class="error-msg">{{ $message }}</p>@enderror
                </div>

                {{-- Session details --}}
                @if($connectionState === 'connected' && $activeSession)
                    <div class="section-header"><h3>Session Details</h3></div>
                    <div class="session-card glass-card">
                        <div class="session-row"><span class="key">IP Address</span><span class="val">{{ $activeSession->framedipaddress ?? 'N/A' }}</span></div>
                        <div class="session-row"><span class="key">Download</span><span class="val">{{ $sessionDownload ?? '—' }}</span></div>
                        <div class="session-row"><span class="key">Upload</span><span class="val">{{ $sessionUpload ?? '—' }}</span></div>
                        <div class="session-row"><span class="key">Duration</span><span class="val">{{ $uptime ?? '—' }}</span></div>
                    </div>
                @endif

                {{-- Plan queue: Up Next --}}
                @if($pendingSubscriptions->isNotEmpty())
                    <div class="section-header"><h3>Up Next</h3></div>
                    @foreach($pendingSubscriptions as $sub)
                        <div class="queue-item">
                            <div class="queue-pos">{{ $loop->iteration }}</div>
                            <div class="queue-info">
                                <div class="queue-name">{{ $sub->plan->name }}</div>
                                <div class="queue-meta">{{ $sub->plan->data_limit_human }} &middot; {{ $sub->plan->validity_days }} day{{ $sub->plan->validity_days == 1 ? '' : 's' }}</div>
                            </div>
                            @if($loop->first)
                                <button class="queue-start-btn"
                                    wire:click="forceActivate({{ $sub->id }})"
                                    wire:confirm="Start this plan now? Your current plan will stop."
                                    wire:loading.attr="disabled"
                                    wire:target="forceActivate">
                                    Start Now
                                </button>
                            @endif
                        </div>
                    @endforeach
                @endif

                {{-- Recent activity --}}
                @if($recentTransactions->isNotEmpty())
                    <div class="section-header"><h3>Recent Activity</h3></div>
                    <div class="activity-card glass-card">
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

                @if($groupedPlans->isEmpty())
                    <div style="text-align:center; padding: 56px 0; color: var(--muted); font-size: 13px;">
                        No plans available for your area yet.
                    </div>
                @else
                    {{-- Grouped by duration: Daily / Weekly / Monthly / Family / Long-term --}}
                    @foreach($groupedPlans as $durationLabel => $durationPlans)
                        @php
                            $groupIcons = [
                                'Daily'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                                'Weekly'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
                                'Monthly'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="8" y2="14"/><line x1="12" y1="14" x2="12" y2="14"/><line x1="16" y1="14" x2="16" y2="14"/>',
                                'Family'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                                'Long-term' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                            ];
                            $icon = $groupIcons[$durationLabel] ?? $groupIcons['Daily'];
                        @endphp
                        <div class="plan-group">
                            <div class="plan-group-header">
                                <div class="plan-group-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                                </div>
                                <span class="plan-group-title">{{ $durationLabel }}</span>
                                <span class="plan-group-count">{{ $durationPlans->count() }}</span>
                            </div>

                            <div class="plans-list">
                                @foreach($durationPlans as $plan)
                                    @php
                                        $isOtherLocation = $plan->router_id && $plan->router_id !== $userRouterId;
                                        $locLabel = $plan->router_id
                                            ? ($plan->router?->brand_name ?? $plan->router?->name ?? 'Specific location')
                                            : 'Any hotspot';
                                    @endphp
                                    <div class="plan-card {{ $plan->is_featured ? 'featured' : '' }} {{ $isOtherLocation ? 'other-location' : '' }}">
                                        <div class="plan-card-top">
                                            <div class="plan-icon {{ $plan->is_featured ? 'feat' : '' }}">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                                                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                                                </svg>
                                            </div>
                                            <div class="plan-info">
                                                <div class="plan-name">{{ $plan->name }}</div>
                                                <div class="plan-meta">
                                                    {{ $plan->data_limit_human }} &middot; {{ $plan->validity_days }}d
                                                    @if($plan->speed_limit_download) &middot; {{ $plan->speed_limit_download }}k↓ @endif
                                                </div>
                                                <span class="plan-loc-badge {{ $isOtherLocation ? 'loc-other' : 'loc-any' }}">
                                                    @if($isOtherLocation)
                                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                                        {{ $locLabel }} only
                                                    @else
                                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/></svg>
                                                        {{ $locLabel }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="plan-price-wrap">
                                            <span class="plan-price">₦{{ number_format($plan->price, 0) }}</span>
                                            <form method="POST" action="{{ route('pay') }}">
                                                @csrf
                                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                <button type="submit" class="btn-buy {{ $isOtherLocation ? 'dimmed' : '' }}">Buy</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- ─── DEVICES TAB ──────────────────────────── --}}
            <div x-show="tab === 'devices'" style="display:none">
                <div class="section-header"><h3>My Devices</h3></div>
                <div style="text-align:center; padding: 56px 0; color: var(--muted); font-size: 13px;">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="opacity:.3; margin: 0 auto 14px; display: block;">
                        <rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                    Device management coming soon.
                    <br>
                    <a href="{{ route('dashboard') }}" style="color: var(--accent); font-size: 12px; margin-top: 10px; display: inline-block;">
                        View full dashboard →
                    </a>
                </div>
            </div>

            {{-- ─── ACCOUNT TAB ──────────────────────────── --}}
            <div x-show="tab === 'account'"
                 x-data="{ editMode: false, pwOpen: false }"
                 @profile-saved.window="editMode = false; pwOpen = false"
                 style="display:none">

                @if($historyMode)
                {{-- ── TRANSACTION HISTORY (Livewire-controlled) ── --}}
                <div>
                    <div class="edit-profile-hdr">
                        <button type="button" wire:click="exitHistory" class="edit-back-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Back
                        </button>
                        <span class="edit-profile-title">Transactions</span>
                    </div>

                    <div class="txn-list">
                        @forelse($allTransactions as $txn)
                            <div class="txn-row">
                                <div class="txn-icon">
                                    @if($txn->gateway === 'voucher')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    @else
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    @endif
                                </div>
                                <div class="txn-info">
                                    <div class="txn-title">{{ $txn->plan?->name ?? ucwords(str_replace('-', ' ', $txn->gateway ?? 'Payment')) }}</div>
                                    <div class="txn-sub">{{ ($txn->paid_at ?? $txn->created_at)?->format('d M Y, g:i a') }}</div>
                                </div>
                                <div class="txn-right">
                                    <div class="txn-amount">₦{{ number_format($txn->amount) }}</div>
                                    <span class="txn-badge {{ $txn->status }}">{{ $txn->status }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="txn-empty">No transactions yet.</div>
                        @endforelse
                    </div>

                    @if($allTransactions->hasPages())
                        <div class="txn-pages">
                            <button wire:click="previousPage" @if($allTransactions->onFirstPage()) disabled @endif class="txn-page-btn">← Prev</button>
                            <span class="txn-page-info">{{ $allTransactions->currentPage() }} / {{ $allTransactions->lastPage() }}</span>
                            <button wire:click="nextPage" @if(!$allTransactions->hasMorePages()) disabled @endif class="txn-page-btn">Next →</button>
                        </div>
                    @endif
                </div>
                @else
                {{-- ── VIEW MODE ── --}}
                <div x-show="!editMode">
                    <div class="profile-header">
                        <div class="profile-avatar-lg">{{ $user->initials }}</div>
                        <div class="profile-name">{{ $user->display_name }}</div>
                        <div class="profile-sub">{{ $user->username ?? $user->email ?? 'No username' }}</div>
                    </div>

                    <div class="account-rows">
                        <button type="button" @click="editMode = true" class="account-row" style="width:100%;text-align:left;">
                            <div class="account-row-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <span class="account-row-label">Edit Profile</span>
                            <span class="account-row-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </button>

                        <button type="button" wire:click="enterHistory" class="account-row" style="width:100%;text-align:left;">
                            <div class="account-row-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </div>
                            <span class="account-row-label">Transaction History</span>
                            <span class="account-row-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </button>

                        <a href="{{ route('dashboard') }}" class="account-row">
                            <div class="account-row-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            </div>
                            <span class="account-row-label">Full Dashboard</span>
                            <span class="account-row-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </a>

                        <a href="{{ route('request-custom-plans') }}" class="account-row">
                            <div class="account-row-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            </div>
                            <span class="account-row-label">Custom Plan</span>
                            <span class="account-row-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="account-row" style="width:100%;text-align:left;">
                                <div class="account-row-icon" style="background:rgba(255,69,58,.1);color:var(--red);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                </div>
                                <span class="account-row-label" style="color:var(--red);">Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ── EDIT MODE ── --}}
                <div x-show="editMode" x-cloak>

                    {{-- Back header --}}
                    <div class="edit-profile-hdr">
                        <button type="button" @click="editMode = false; pwOpen = false" class="edit-back-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Back
                        </button>
                        <span class="edit-profile-title">Edit Profile</span>
                    </div>

                    {{-- Avatar --}}
                    <div class="profile-header" style="padding-bottom:4px">
                        <div class="profile-avatar-lg">{{ $user->initials }}</div>
                    </div>

                    {{-- Name / Phone / Email --}}
                    <div class="prof-form">
                        <div class="prof-field">
                            <label class="prof-label">Full Name</label>
                            <input type="text" wire:model="profileName" class="prof-input" placeholder="Your name">
                            @error('profileName') <span class="prof-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="prof-field">
                            <label class="prof-label">Phone Number</label>
                            <input type="tel" wire:model="profilePhone" class="prof-input" placeholder="e.g. 07012345678">
                            @error('profilePhone') <span class="prof-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="prof-field">
                            <label class="prof-label">Email</label>
                            <input type="email" wire:model="profileEmail" class="prof-input" placeholder="email@example.com">
                            @error('profileEmail') <span class="prof-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button type="button" wire:click="saveProfile" class="prof-save-btn">
                        <span wire:loading.remove wire:target="saveProfile">Save Changes</span>
                        <span wire:loading wire:target="saveProfile">Saving…</span>
                    </button>

                    {{-- Change Password (collapsible) --}}
                    <div class="prof-pw-section">
                        <button type="button" @click="pwOpen = !pwOpen" class="prof-pw-toggle">
                            <span>Change Password</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :style="pwOpen ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>

                        <div x-show="pwOpen" x-cloak>
                            <div class="prof-form" style="padding-top:4px">
                                <div class="prof-field">
                                    <label class="prof-label">Current Password</label>
                                    <input type="password" wire:model="currentPassword" class="prof-input" placeholder="Current password">
                                    @error('currentPassword') <span class="prof-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="prof-field">
                                    <label class="prof-label">New Password</label>
                                    <input type="password" wire:model="newPassword" class="prof-input" placeholder="Min 4 characters">
                                    @error('newPassword') <span class="prof-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="prof-field">
                                    <label class="prof-label">Confirm New Password</label>
                                    <input type="password" wire:model="newPasswordConfirmation" class="prof-input" placeholder="Repeat new password">
                                </div>
                            </div>
                            <button type="button" wire:click="changePassword" class="prof-save-btn prof-pw-btn">
                                <span wire:loading.remove wire:target="changePassword">Update Password</span>
                                <span wire:loading wire:target="changePassword">Updating…</span>
                            </button>
                        </div>
                    </div>

                </div>{{-- end edit mode --}}
                @endif{{-- end historyMode/else --}}
            </div>

        </div>{{-- end tab-content --}}

    </div>{{-- end main-area --}}

    {{-- ══ HOTSPOT WARNING MODAL ════════════════════════════ --}}
    @if($showWarning)
        <div class="overlay" wire:click.self="dismissWarning">
            <div class="modal-sheet">
                <div class="modal-icon">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                    </svg>
                </div>
                <div class="modal-title">Not on HiFastLink WiFi</div>
                <div class="modal-body">
                    Connect to a HiFastLink WiFi network first, then tap Connect to get online.<br><br>
                    Look for networks like <strong style="color:var(--text)">HiFastLink</strong> or <strong style="color:var(--text)">BasmelCare</strong>.
                </div>
                <button class="btn-modal-ok" wire:click="dismissWarning">Got it</button>
            </div>
        </div>
    @endif

    {{-- ══ MOBILE BOTTOM TAB BAR ══════════════════════════ --}}
    <nav class="tab-bar">
        <button class="tab-btn" :class="tab==='home'    ? 'active':''" @click="tab='home'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </button>
        <button class="tab-btn" :class="tab==='plans'   ? 'active':''" @click="tab='plans'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
            Plans
        </button>
        <button class="tab-btn" :class="tab==='devices' ? 'active':''" @click="tab='devices'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            Devices
        </button>
        <button class="tab-btn" :class="tab==='account' ? 'active':''" @click="tab='account'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Account
        </button>
    </nav>

</div>

@script
<script>
async function appConnect(btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    btn.querySelector('.connect-btn-lbl').textContent = '···';
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const resp = await fetch('/dashboard/connect', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({}),
        });
        const data = await resp.json().catch(() => ({}));
        if (resp.ok && data.redirect_url) {
            window.location.href = data.redirect_url;
            return;
        }
        btn.disabled = false;
        btn.querySelector('.connect-btn-lbl').textContent = 'Connect';
        alert(data.message || 'Could not connect. Please try again.');
    } catch (err) {
        btn.disabled = false;
        btn.querySelector('.connect-btn-lbl').textContent = 'Connect';
        alert('Network error. Make sure you are connected to WiFi.');
    }
}
</script>
@endscript
