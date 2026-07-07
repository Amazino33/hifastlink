<x-filament-panels::page>
<style>
:root {
    --bg: #ffffff; --bg2: #f9fafb; --bg3: #f3f4f6;
    --border: #e5e7eb;
    --text: #111827; --text2: #374151; --text3: #6b7280;
    --accent: #3b82f6; --accent-h: #2563eb;
    --red: #dc2626; --orange: #d97706; --blue: #2563eb; --green: #059669;
}
.dark {
    --bg: #1f2937; --bg2: #111827; --bg3: #1a2535;
    --border: #374151;
    --text: #f9fafb; --text2: #e5e7eb; --text3: #d1d5db;
    --accent: #3b82f6; --accent-h: #2563eb;
    --red: #f87171; --orange: #fbbf24; --blue: #60a5fa; --green: #34d399;
}
.s-card { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:16px; }
.stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:640px){ .stat-grid{ grid-template-columns:repeat(2,1fr); } }
.stat-box { background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:16px; text-align:center; }
.stat-num  { font-size:28px; font-weight:700; color:var(--text); line-height:1; }
.stat-lbl  { font-size:12px; color:var(--text3); margin-top:4px; }
.toolbar { display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.s-input {
    padding:8px 12px; border:1px solid var(--border); border-radius:8px;
    background:var(--bg2); color:var(--text); font-size:14px; outline:none;
    transition:border-color .15s; font-family:inherit; flex:1; min-width:0;
}
.s-input:focus { border-color:var(--accent); }
.s-select { padding:8px 12px; border:1px solid var(--border); border-radius:8px; background:var(--bg2); color:var(--text); font-size:14px; outline:none; cursor:pointer; font-family:inherit; }
.s-btn { padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:background .15s; font-family:inherit; white-space:nowrap; }
.s-btn-danger    { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.s-btn-danger:hover { background:#fecaca; }
.s-btn-secondary { background:var(--bg2); color:var(--text2); border:1px solid var(--border); }
.s-btn-secondary:hover { background:var(--bg3); }

/* Log entries */
.log-entry { border:1px solid var(--border); border-radius:10px; margin-bottom:10px; overflow:hidden; }
.log-header {
    display:flex; align-items:flex-start; gap:12px; padding:12px 16px;
    background:var(--bg); cursor:pointer; user-select:none;
}
.log-header:hover { background:var(--bg2); }
.level-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 9px; border-radius:100px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; flex-shrink:0; margin-top:1px;
}
.lvl-error   { background:#fee2e2; color:#991b1b; }
.lvl-warning { background:#fef3c7; color:#92400e; }
.lvl-info    { background:#dbeafe; color:#1e40af; }
.lvl-debug   { background:#f3f4f6; color:#374151; }
.dark .lvl-error   { background:#450a0a; color:#fca5a5; }
.dark .lvl-warning { background:#451a03; color:#fcd34d; }
.dark .lvl-info    { background:#1e3a5f; color:#93c5fd; }
.dark .lvl-debug   { background:#1f2937; color:#9ca3af; }
.log-main { flex:1; min-width:0; }
.log-type    { font-size:11px; color:var(--text3); font-family:monospace; margin-bottom:2px; }
.log-message { font-size:13px; color:var(--text); font-weight:500; word-break:break-word; }
.log-meta { font-size:11px; color:var(--text3); margin-top:4px; display:flex; flex-wrap:wrap; gap:8px; }
.log-meta span { display:inline-flex; align-items:center; gap:3px; }
.log-expand-icon { color:var(--text3); transition:transform .2s; flex-shrink:0; margin-top:2px; }
.log-context {
    border-top:1px solid var(--border); background:var(--bg2);
    padding:14px 16px; display:none;
}
.log-context.open { display:block; }
.log-context pre {
    font-size:11px; color:var(--text2); font-family:monospace;
    white-space:pre-wrap; word-break:break-all; margin:0;
    max-height:320px; overflow-y:auto;
}
.empty-state { text-align:center; padding:48px 0; color:var(--text3); }
.empty-state svg { width:40px; height:40px; margin:0 auto 12px; opacity:.4; }
.empty-state p { font-size:14px; margin:0; }
</style>

{{-- Stats row --}}
<div class="stat-grid">
    <div class="stat-box">
        <div class="stat-num">{{ number_format($this->stats['total']) }}</div>
        <div class="stat-lbl">Total Logs</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:var(--red)">{{ number_format($this->stats['errors']) }}</div>
        <div class="stat-lbl">Errors</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:var(--blue)">{{ number_format($this->stats['last24h']) }}</div>
        <div class="stat-lbl">Last 24 Hours</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:var(--green)">{{ number_format($this->stats['today']) }}</div>
        <div class="stat-lbl">Today</div>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <input type="text" class="s-input" placeholder="Search message, exception type, URL…"
           wire:model.live.debounce.400ms="search">

    <select class="s-select" wire:model.live="levelFilter">
        <option value="">All Levels</option>
        <option value="error">Error</option>
        <option value="warning">Warning</option>
        <option value="info">Info</option>
    </select>

    <button class="s-btn s-btn-secondary" wire:click="clearOld"
            wire:confirm="Delete all logs older than 30 days?">
        Clear &gt;30 days
    </button>
    <button class="s-btn s-btn-danger" wire:click="clearAll"
            wire:confirm="Permanently delete ALL system logs? This cannot be undone.">
        Clear All
    </button>
</div>

{{-- Log list --}}
@if($this->logs->isEmpty())
<div class="empty-state">
    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
    </svg>
    <p>No log entries found{{ $search || $levelFilter ? ' matching your filters' : '' }}.</p>
</div>
@else
@foreach($this->logs as $log)
<div class="log-entry" x-data="{ open: false }">
    <div class="log-header" @click="open = !open">
        <span class="level-badge lvl-{{ $log->level }}">{{ $log->level }}</span>
        <div class="log-main">
            <div class="log-type">{{ $log->shortType() }}</div>
            <div class="log-message">{{ Str::limit($log->message, 200) }}</div>
            <div class="log-meta">
                @if($log->url)
                <span>
                    <svg style="width:11px;height:11px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    {{ $log->method }} {{ Str::limit($log->url, 80) }}
                </span>
                @endif
                @if($log->user_id)
                <span>
                    <svg style="width:11px;height:11px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    User #{{ $log->user_id }}
                </span>
                @endif
                @if($log->ip)
                <span>
                    <svg style="width:11px;height:11px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253"/></svg>
                    {{ $log->ip }}
                </span>
                @endif
                <span title="{{ $log->occurred_at->format('Y-m-d H:i:s') }}">
                    <svg style="width:11px;height:11px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $log->occurred_at->diffForHumans() }}
                </span>
            </div>
        </div>
        <svg class="log-expand-icon" :style="open ? 'transform:rotate(180deg)' : ''"
             style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </div>
    <div class="log-context" :class="open ? 'open' : ''">
        <pre>{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
@endforeach

<div style="margin-top:16px">
    {{ $this->logs->links() }}
</div>
@endif

</x-filament-panels::page>
