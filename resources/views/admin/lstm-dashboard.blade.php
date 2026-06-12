@extends('layout_dashboard')

@section('title', 'Productivity Outlook')

@section('content')
<div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">Productivity Outlook</h1>
                <button id="btn-lstm-info" type="button"
                    class="inline-flex items-center px-2 py-1 gap-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wide hover:bg-primary/20 transition-all cursor-pointer"
                    title="About this model">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    LSTM
                </button>
            </div>
            <p class="text-muted-500 text-sm md:text-base mt-1"
               title="Dự đoán hạng năng suất ngày mai cho từng nhân viên dựa trên 14 ngày làm việc gần nhất.">
                Tomorrow's predicted productivity class · forecast based on the past 14 days
            </p>
        </div>
        <div class="flex items-center gap-4 flex-wrap justify-start lg:justify-end">
            <span class="text-sm text-muted-500">
                Last run: <span id="last-run" class="font-medium text-main">—</span>
            </span>
            <button id="btn-export"
                class="flex items-center justify-center gap-2 rounded-xl border border-muted-300 bg-white px-5 py-2.5 text-sm font-medium text-muted-600 hover:border-primary/40 hover:text-primary transition-all"
                title="Tải báo cáo Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export
            </button>
            <button id="btn-refresh"
                class="flex items-center justify-center gap-2 rounded-xl bg-primary hover:bg-primary-hover px-5 py-2.5 text-white text-sm font-semibold shadow-lg shadow-primary/20 transition-all active:scale-95"
                title="Chạy lại dự đoán">
                <svg id="refresh-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"/><path d="M23 20v-6h-6"/>
                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- CONTENT AREA --}}
    <div class="flex flex-col gap-6 max-w-[1200px] w-full mx-auto">
        <div class = "flex flex-col lg:flex-row gap-6 animate-fade-in-up">
            {{-- ── HERO SNAPSHOT ───────────────────────────────────── --}}
            <div class="w-full overflow-hidden p-6 md:p-8 bg-primary-gradient shadow-xl shadow-indigo-500/20 rounded-3xl">
                <div class="flex flex-row justify-between gap-6 h-full text-canvas">
                    <div class="flex flex-col justify-between gap-6 flex-1">
                        <p class="font-medium text-canvas/70 uppercase tracking-widest text-xs">
                            Tomorrow's Productivity Forecast
                        </p>

                        <div class="flex flex-col gap-2">
                            <a id="hero-count-link"
                            href="#attention-section"
                            class="group flex items-end gap-3 w-fit focus:outline-none transition-all"
                            title="See who needs attention">
                                <span id="snap-attention" class="text-6xl font-bold tracking-tight leading-none text-canvas transition-opacity">
                                    6
                                </span>
                                <span id="hero-count-label" class="text-2xl text-canvas/90 font-medium group-hover:text-canvas transition-colors">
                                    employees require attention
                                </span>
                                <svg class="h-5 w-5 text-canvas/60 -translate-y-1 group-hover:translate-y-0 group-hover:text-canvas transition-all flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                            </a>

                            {{-- Notice: The repeated "6 employees..." is removed. It just gets straight to the context. --}}
                            <p id="snap-sub" class="text-base text-canvas/80 leading-relaxed max-w-xl mt-1">
                                Driven primarily by sharp declining trends in <span class="font-semibold text-canvas">Engineering</span> and <span class="font-semibold text-canvas">Sales</span>.
                            </p>
                        </div>
                    </div>

                    {{-- micro card populated by renderHeroBanner() --}}
                    <div id="hero-micro-card"
                         class="flex-col justify-between self-end min-w-[180px] max-w-[220px] rounded-2xl p-4"
                         style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)">
                    </div>
                </div>

            </div>

            {{-- ── KPI CARDS ───────────────────────────────────────── --}}
            <div class="flex flex-col w-full lg:max-w-72 gap-4 animate-fade-in-up [animation-delay:50ms]">
                <x-white-card-container color="success/80" class="p-4 items-center gap-4 hover:border-success/80">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Predicted High</p>
                        <p class="text-2xl font-bold text-main leading-tight" id="snap-high">—</p>
                        <p class="text-xs text-muted-400 mt-0.5">Score ≥80 · on track</p>
                    </div>
                </x-white-card-container>

                <x-white-card-container color="secondary/80" class="p-4 items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Predicted Medium</p>
                        <p class="text-2xl font-bold text-main leading-tight" id="snap-med">—</p>
                        <p class="text-xs text-muted-400 mt-0.5">Score 50–79 · watch closely</p>
                    </div>
                </x-white-card-container>

                <x-white-card-container color="danger/50" class="p-4 items-center gap-4 hover:border-danger/50">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-danger/10 text-danger">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Predicted Low</p>
                        <p class="text-2xl font-bold text-main leading-tight" id="snap-low">—</p>
                        <p class="text-xs text-muted-400 mt-0.5">Score &lt;50 · needs attention</p>
                    </div>
                </x-white-card-container>

            </div>
        </div>

        {{-- ── ATTENTION LIST ──────────────────────────────────── --}}
        <div id="attention-section" class="scroll-mt-6 animate-fade-in-up [animation-delay:80ms]">
        <x-white-card-container color="primary/50" class="overflow-hidden flex-col animate-fade-in-up [animation-delay:100ms]">
            <div class="flex items-start justify-between border-b border-muted-200 px-5 py-4 flex-wrap gap-3">
                <div>
                    <h4 class="flex items-center gap-2 text-lg font-semibold text-main">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        Who needs attention
                    </h4>
                    <p class="text-sm text-muted-500 mt-1">Predicted Low · Medium trending down</p>
                </div>
                <select id="attn-filter"
                    class="appearance-none rounded-xl border border-muted-300 bg-white px-4 py-2 text-sm text-main hover:border-muted-400 focus:outline-none focus:border-primary transition-colors cursor-pointer">
                    <option value="all">All concerns</option>
                    <option value="low">Predicted Low only</option>
                    <option value="declining">Declining trend only</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="sticky top-0 z-10 border-b border-muted-200 bg-muted-50 text-xs uppercase tracking-wider text-muted-500">
                        <tr>
                            <th class="px-5 py-4 text-left font-semibold">Employee</th>
                            <th class="px-4 py-4 text-left font-semibold">Trajectory</th>
                            <th class="px-4 py-4 text-left font-semibold">Predicted</th>
                            <th class="px-4 py-4 text-left font-semibold">Confidence</th>
                            <th class="px-4 py-4 text-center font-semibold">Chart</th>
                        </tr>
                    </thead>
                    <tbody id="attention-list" class="divide-y divide-muted-100 text-sm">
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-muted-400">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </x-white-card-container>
        </div>

        {{-- ── CONTEXT ─────────────────────────────────────────── --}}
        <div class="animate-fade-in-up [animation-delay:120ms]">
            <p class="text-xs font-bold text-muted-400 uppercase tracking-widest mb-4">Context</p>
            <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_1.2fr_1fr] gap-4 animate-fade-in-up [animation-delay:150ms]">

                <x-white-card-container class="p-6 flex-col gap-4 hover:border-primary/50">
                    <h4 class="flex items-center gap-2 text-base font-semibold text-main border-b border-muted-100 pb-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        By department
                    </h4>
                    <div id="dept-list"><p class="text-sm text-muted-400 text-center py-6">Loading…</p></div>
                </x-white-card-container>

                <x-white-card-container class="p-6 flex-col gap-4 hover:border-secondary/50">
                    <h4 class="flex items-center gap-2 text-base font-semibold text-main border-b border-muted-100 pb-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary/10 text-secondary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        Score distribution
                    </h4>
                    <div class="relative w-full h-44"><canvas id="dist-chart"></canvas></div>
                </x-white-card-container>

                <x-white-card-container class="p-6 flex-col gap-4 hover:border-accent/50">
                    <h4 class="flex items-center gap-2 text-base font-semibold text-main border-b border-muted-100 pb-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent/10 text-accent">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        Top predicted
                    </h4>
                    <div id="top-list"><p class="text-sm text-muted-400 text-center py-6">Loading…</p></div>
                </x-white-card-container>
            </div>
        </div>

        {{-- ── ALL EMPLOYEES (collapsible like daily logs) ─────── --}}
        <x-white-card-container color="primary/50" class="overflow-hidden flex-col animate-fade-in-up [animation-delay:150ms]">
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between border-b border-muted-200 px-5 py-4 group-open:border-b">
                    <div>
                        <h4 class="text-lg font-semibold text-main">All employees</h4>
                        <p class="text-sm text-muted-500">Full prediction table with filters</p>
                    </div>
                    <div class="flex items-center gap-1 text-sm font-medium text-primary">
                        <span>Show details</span>
                        <svg class="h-4 w-4 transition-transform duration-200 -rotate-90 group-open:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </summary>

                <div>
                    <div class="border-b border-muted-200 bg-white p-5 flex flex-wrap gap-4">
                        <input type="text" id="search" placeholder="Search name or department…"
                            class="flex-1 min-w-[180px] rounded-xl border border-muted-300 bg-white px-3 py-2 text-sm placeholder-muted-400 hover:border-muted-400 focus:outline-none focus:border-primary transition-colors">
                        <select id="dept-filter"
                            class="rounded-xl border border-muted-300 bg-white px-3 py-2 text-sm hover:border-muted-400 focus:outline-none focus:border-primary transition-colors cursor-pointer">
                            <option value="">All departments</option>
                        </select>
                        <select id="risk-filter"
                            class="rounded-xl border border-muted-300 bg-white px-3 py-2 text-sm hover:border-muted-400 focus:outline-none focus:border-primary transition-colors cursor-pointer">
                            <option value="">All classes</option>
                            <option value="high">Predicted High</option>
                            <option value="medium">Predicted Medium</option>
                            <option value="low">Predicted Low</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-muted-50 text-xs uppercase tracking-wider text-muted-500 border-b border-muted-200">
                                <tr>
                                    <th class="px-5 py-4 text-left font-semibold">Employee</th>
                                    <th class="px-4 py-4 text-left font-semibold">Department</th>
                                    <th class="px-4 py-4 text-left font-semibold">Today</th>
                                    <th class="px-4 py-4 text-left font-semibold">Predicted</th>
                                    <th class="px-4 py-4 text-left font-semibold">Trend</th>
                                    <th class="px-4 py-4 text-left font-semibold">Confidence</th>
                                    <th class="px-4 py-4"></th>
                                </tr>
                            </thead>
                            <tbody id="all-tbody" class="divide-y divide-muted-100 text-sm"></tbody>
                        </table>
                    </div>
                </div>
            </details>
        </x-white-card-container>

    </div>

    {{-- LOADING OVERLAY --}}
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Loading predictions…</p>
    </div>
</div>

<style>
/* ── Smooth scroll ───────────────────────────────────────── */
html { scroll-behavior: smooth }

/* ── Attention section highlight on :target ──────────────── */
#attention-section:target > * {
    animation: attn-pulse 1.4s ease forwards;
}
@keyframes attn-pulse {
    0%   { box-shadow: 0 0 0 0   rgba(83,71,204,0) }
    25%  { box-shadow: 0 0 0 4px rgba(83,71,204,.35) }
    100% { box-shadow: 0 0 0 0   rgba(83,71,204,0) }
}

/* ── Spinner ─────────────────────────────────────────────── */
#btn-refresh.spinning #refresh-icon { animation: lstm-spin .7s linear infinite }
@keyframes lstm-spin { to { transform: rotate(360deg) } }

/* ── Loading overlay ─────────────────────────────────────── */
.loading-overlay {
    position: fixed; inset: 0;
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(2px);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    z-index: 9999; gap: 14px;
}
.loading-overlay.hidden { display: none !important }
.spinner {
    width: 30px; height: 30px;
    border: 2.5px solid #E5E7EB;
    border-top-color: #5347CC;
    border-radius: 50%;
    animation: lstm-spin .7s linear infinite;
}
.loading-overlay p { font-size: .82rem; color: #6B7280 }

/* ── Trajectory ──────────────────────────────────────────── */
.traj-text   { font-size: .78rem; font-weight: 600 }
.traj-declining { color: #EF4444 }
.traj-improving { color: #10B981 }
.traj-stable    { color: #6B7280 }
.traj-detail    { font-size: .68rem; color: #9CA3AF; margin-top: 2px }

/* ── Department list ─────────────────────────────────────── */
.dept-row-item {
    display: grid; grid-template-columns: 1fr auto;
    gap: 10px; align-items: center;
    padding: .6rem 0; border-bottom: 1px solid #F3F4F6;
}
.dept-row-item:last-child { border-bottom: none }
.dept-name { font-size: .85rem; font-weight: 600; color: #070416 }
.dept-meta { font-size: .7rem; color: #9CA3AF; margin-top: 2px }
.dept-bar-wrap { display: flex; align-items: center; gap: 8px; width: 100%; margin-top: 6px }
.dept-bar-track { flex: 1; background: #F3F4F6; border-radius: 9999px; height: 5px; overflow: hidden }
.dept-bar-fill-inline { height: 100%; border-radius: 9999px; transition: width .6s ease }
.dept-score-lbl { font-size: .85rem; font-weight: 700; color: #070416; white-space: nowrap }

/* ── Top performers ──────────────────────────────────────── */
.top-row { display: flex; align-items: center; gap: 10px; padding: .55rem 0; border-bottom: 1px solid #F3F4F6 }
.top-row:last-child { border-bottom: none }
.top-rank { font-size: .72rem; font-weight: 700; color: #9CA3AF; width: 16px; text-align: center; flex-shrink: 0 }
.top-avatar { width: 28px; height: 28px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: .6rem; font-weight: 700; flex-shrink: 0; color: #fff }
.top-info { flex: 1; min-width: 0 }
.top-name { font-size: .82rem; font-weight: 600; color: #070416; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.top-dept { font-size: .7rem; color: #9CA3AF; margin-top: 1px }
.top-score-num { font-size: .85rem; font-weight: 700; color: #059669; white-space: nowrap }

/* ── Trust class bars ────────────────────────────────────── */
.trust-class-row { display: grid; grid-template-columns: 70px 1fr 50px 1fr; gap: 14px; align-items: center }
.trust-class-bar { background: #F3F4F6; border-radius: 9999px; height: 6px; overflow: hidden; display: block; width: 100% }
.tcb-fill { display: block; height: 100%; border-radius: 9999px; transition: width .6s ease }
.tcb-high { background: #10B981 }
.tcb-med  { background: #4896FE }
.tcb-low  { background: #EF4444 }

/* ── Hero micro card ─────────────────────────────────────── */
#hero-micro-card { display: none }
@media (min-width: 1024px) {
    #hero-micro-card.flex { display: flex }
}
.hero-micro-btn {
    width: 100%; text-align: center;
    font-size: .72rem; font-weight: 600;
    padding: 6px 12px; border-radius: 8px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff; cursor: pointer;
    transition: background .15s;
    font-family: inherit;
}
.hero-micro-btn:hover { background: rgba(255,255,255,.28) }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 860px) {
    .trust-class-row { grid-template-columns: 60px 1fr 45px }
    .trust-class-row > :last-child { display: none }
}
</style>

{{-- ── ABOUT THIS MODEL MODAL ──────────────────────────────── --}}
<div id="lstm-info-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">

    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl flex flex-col animate-fade-in-up">

        {{-- Header --}}
        <div class="flex items-start justify-between px-6 py-5 border-b border-muted-200 relative z-10 flex-shrink-0">
            <div>
                <h2 class="font-bold text-xl text-main tracking-tight">About this model</h2>
                <p class="text-muted-500 text-sm mt-0.5">LSTM · Long Short-Term Memory neural network</p>
            </div>
            <button type="button" id="lstm-info-close"
                class="mt-0.5 ml-4 flex-shrink-0 p-2 rounded-xl text-muted-400 hover:bg-muted-100 hover:text-main transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Modal body --}}
        <div class="p-6 flex flex-col gap-6">

            {{-- 4 KPI cards --}}
            <div class="grid grid-cols-2 gap-3">
                <x-white-card-container color="primary/50" class="p-4 flex-col gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Test accuracy</p>
                        <p id="ms-acc" class="text-2xl font-bold text-main leading-tight">—</p>
                        <p class="text-xs text-muted-400 mt-0.5">Held-out data, Feb 2026+</p>
                    </div>
                </x-white-card-container>

                <x-white-card-container color="accent/50" class="p-4 flex-col gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent/10 text-accent">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Lift over baseline</p>
                        <p id="ms-uplift" class="text-2xl font-bold text-main leading-tight">—</p>
                        <p class="text-xs text-muted-400 mt-0.5">vs. "tomorrow = today"</p>
                    </div>
                </x-white-card-container>

                <x-white-card-container color="secondary/50" class="p-4 flex-col gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Macro F1</p>
                        <p id="ms-f1" class="text-2xl font-bold text-main leading-tight">—</p>
                        <p class="text-xs text-muted-400 mt-0.5">Balance across classes</p>
                    </div>
                </x-white-card-container>

                <x-white-card-container color="primary/50" class="p-4 flex-col gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-400">Lookback window</p>
                        <p id="ms-lookback" class="text-2xl font-bold text-main leading-tight">14</p>
                        <p class="text-xs text-muted-400 mt-0.5">Days of history per prediction</p>
                    </div>
                </x-white-card-container>
            </div>

            {{-- Per-class F1 breakdown --}}
            <x-white-card-container color="primary/50" class="p-5 flex-col gap-4">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-main border-b border-muted-200 pb-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    Per-class F1 score
                </h4>
                <div class="flex flex-col gap-3">
                    <div class="trust-class-row">
                        <span class="text-sm font-semibold text-main">High</span>
                        <span class="trust-class-bar"><span class="tcb-fill tcb-high" id="cls-f1-high-bar"></span></span>
                        <span class="text-sm font-bold text-main text-right" id="cls-f1-high">—</span>
                        <span class="text-xs text-muted-500 hidden sm:block">Strong predictions</span>
                    </div>
                    <div class="trust-class-row">
                        <span class="text-sm font-semibold text-main">Medium</span>
                        <span class="trust-class-bar"><span class="tcb-fill tcb-med" id="cls-f1-med-bar"></span></span>
                        <span class="text-sm font-bold text-main text-right" id="cls-f1-med">—</span>
                        <span class="text-xs text-muted-500 hidden sm:block">Reasonably reliable</span>
                    </div>
                    <div class="trust-class-row">
                        <span class="text-sm font-semibold text-main">Low</span>
                        <span class="trust-class-bar"><span class="tcb-fill tcb-low" id="cls-f1-low-bar"></span></span>
                        <span class="text-sm font-bold text-main text-right" id="cls-f1-low">—</span>
                        <span class="text-xs text-muted-500 hidden sm:block">Use as signal, not verdict</span>
                    </div>
                </div>
                <div class="text-sm text-muted-600 leading-relaxed border-t border-muted-200 pt-3 rounded-r-xl">
                    <strong class="text-main font-semibold">How to read this:</strong>
                    The model predicts a class (Low / Medium / High), not an exact score. Low predictions
                    have lower reliability — treat them as a prompt to check in, not a final verdict.
                    Thresholds: Low &lt;50, Medium 50–79, High ≥80.
                </div>
            </x-white-card-container>
        </div>
    </div>
</div>

<script>
(function () {
    const modal  = document.getElementById('lstm-info-modal');
    const opener = document.getElementById('btn-lstm-info');
    const closer = document.getElementById('lstm-info-close');

    function openModal()  { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.add('hidden');    modal.classList.remove('flex'); document.body.style.overflow = ''; }

    opener.addEventListener('click', openModal);
    closer.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
})();
</script>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endpush
