@extends('layout_dashboard')

@section('content')
<div class="lstm-dash">

    {{-- HEADER --}}
    <div class="dash-header">
        <div>
            <h2 class="dash-title">Productivity Insights</h2>
            <p class="dash-subtitle">LSTM-powered predictions &middot; Last run <span id="last-run">—</span></p>
        </div>
        <button id="btn-refresh" class="btn-refresh">
            <svg id="refresh-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M1 4v6h6"/><path d="M23 20v-6h-6"/>
                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
            </svg>
            Refresh predictions
        </button>
    </div>

    {{-- METRIC STRIP --}}
    <div class="metric-strip">
        <div class="metric-tile">
            <span class="metric-num" id="m-avg">—</span>
            <span class="metric-lbl">Team avg predicted</span>
        </div>
        <div class="metric-tile accent-danger">
            <span class="metric-num" id="m-risk">—</span>
            <span class="metric-lbl">Need attention</span>
        </div>
        <div class="metric-tile accent-warn">
            <span class="metric-num" id="m-burnout">—</span>
            <span class="metric-lbl">Burnout signals</span>
        </div>
        <div class="metric-tile accent-ok">
            <span class="metric-num" id="m-high">—</span>
            <span class="metric-lbl">High performers</span>
        </div>
        <div class="metric-tile">
            <span class="metric-num" id="m-acc">—</span>
            <span class="metric-lbl">Model accuracy</span>
        </div>
    </div>

    {{-- MAIN GRID: attention table + alerts --}}
    <div class="main-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Needs attention</span>
                <span class="panel-badge danger" id="badge-atrisk">—</span>
            </div>
            <div class="attn-header emp-grid">
                <span>Employee</span>
                <span>Current</span>
                <span>Predicted</span>
                <span>Trend</span>
            </div>
            <div id="attention-list"><div class="empty-state">Loading…</div></div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Alerts &amp; patterns</span>
            </div>
            <div id="alerts-list"><div class="empty-state">Loading…</div></div>
        </div>
    </div>

    {{-- DEPARTMENT BREAKDOWN --}}
    <div class="section-label">By department</div>
    <div class="dept-row" id="dept-row">
        <div class="empty-state" style="grid-column:1/-1">Loading…</div>
    </div>

    {{-- BOTTOM GRID --}}
    <div class="bottom-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Score distribution</span>
                <span class="panel-hint">predicted productivity</span>
            </div>
            <div class="chart-wrap"><canvas id="dist-chart"></canvas></div>
        </div>

        <div class="panel">
            <div class="panel-head"><span class="panel-title">This week's insights</span></div>
            <div id="insights-list"></div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Top performers</span>
                <span class="panel-badge ok" id="badge-top">—</span>
            </div>
            <div id="top-list"><div class="empty-state">Loading…</div></div>
        </div>
    </div>

    {{-- ALL EMPLOYEES TABLE --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="section-label" style="margin:0">All employees</div>
        <button class="btn-toggle" id="btn-toggle-all">Show all ▾</button>
    </div>
    <div class="panel" id="panel-all" style="display:none">
        <div class="table-filters">
            <input type="text" id="search" placeholder="Search name or department…" class="tbl-search">
            <select id="dept-filter" class="tbl-select">
                <option value="">All departments</option>
            </select>
            <select id="risk-filter" class="tbl-select">
                <option value="">All risk levels</option>
                <option value="critical">Critical (&lt;40%)</option>
                <option value="high">High (40–60%)</option>
                <option value="medium">Medium (60–80%)</option>
                <option value="low">Low (&gt;80%)</option>
            </select>
        </div>
        <div class="tbl-wrap">
            <table class="emp-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Current</th>
                        <th>Predicted</th>
                        <th>Trend</th>
                        <th>Risk</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="all-tbody"></tbody>
            </table>
        </div>
    </div>

    {{-- LOADING OVERLAY --}}
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Loading predictions…</p>
    </div>
</div>

<style>
.lstm-dash{padding:1.5rem 0 3rem;font-family:'DM Sans',sans-serif}
.dash-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem}
.dash-title{font-size:1.4rem;font-weight:700;letter-spacing:-.02em;margin:0 0 2px;color:#111}
.dash-subtitle{font-size:.78rem;color:#888;margin:0}

.btn-refresh{display:flex;align-items:center;gap:7px;padding:.45rem 1rem;font-size:.78rem;
  font-weight:600;border:1.5px solid #d4d4d4;border-radius:8px;background:#fff;cursor:pointer;
  color:#333;transition:all .15s;white-space:nowrap}
.btn-refresh:hover{border-color:#333;background:#f8f8f8}
.btn-refresh:disabled{opacity:.5;cursor:not-allowed}
.btn-refresh.spinning #refresh-icon{animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

.metric-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:1.5rem}
.metric-tile{background:#fafafa;border:1px solid #ebebeb;border-radius:12px;padding:.85rem 1rem}
.metric-tile.accent-danger{border-left:3px solid #f05252}
.metric-tile.accent-warn  {border-left:3px solid #f59e0b}
.metric-tile.accent-ok    {border-left:3px solid #22c55e}
.metric-num{display:block;font-size:1.55rem;font-weight:700;letter-spacing:-.03em;
  line-height:1;margin-bottom:3px;color:#111}
.metric-lbl{font-size:.71rem;color:#888;font-weight:500;letter-spacing:.01em}

.main-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-bottom:1.5rem}

.panel{background:#fff;border:1px solid #ebebeb;border-radius:14px;padding:1rem 1.2rem;overflow:hidden}
.panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem}
.panel-title{font-size:.83rem;font-weight:700;color:#111;letter-spacing:-.01em}
.panel-hint{font-size:.71rem;color:#bbb}
.panel-badge{font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:20px;background:#f1f5f9;color:#64748b}
.panel-badge.ok    {background:#dcfce7;color:#166534}
.panel-badge.danger{background:#fee2e2;color:#991b1b}

.emp-grid{display:grid;grid-template-columns:1.8fr 1fr 1fr .8fr;gap:8px;align-items:center;
  padding:.42rem 0;border-bottom:1px solid #f2f2f2;font-size:.77rem}
.attn-header span{font-size:.68rem;font-weight:700;color:#bbb;text-transform:uppercase;letter-spacing:.06em}
.emp-name{font-size:.81rem;font-weight:700;color:#111}
.emp-dept{font-size:.69rem;color:#aaa;margin-top:1px}
.bar-bg{background:#f0f0f0;border-radius:4px;height:5px;margin-top:3px;overflow:hidden}
.bar-fg{height:100%;border-radius:4px;transition:width .5s ease}
.score-txt{font-size:.77rem;font-weight:600;color:#444;margin-top:1px}
.trend-up  {color:#16a34a;font-size:.73rem;font-weight:700}
.trend-down{color:#dc2626;font-size:.73rem;font-weight:700}
.trend-flat{color:#94a3b8;font-size:.73rem;font-weight:700}

.alert-item{display:flex;align-items:flex-start;gap:9px;padding:.55rem 0;border-bottom:1px solid #f5f5f5}
.alert-item:last-child{border-bottom:none}
.alert-dot{width:7px;height:7px;border-radius:50%;margin-top:5px;flex-shrink:0}
.dot-red  {background:#ef4444}.dot-amber{background:#f59e0b}.dot-blue{background:#3b82f6}
.alert-name{font-size:.81rem;font-weight:700;color:#111}
.alert-desc{font-size:.72rem;color:#666;margin-top:2px;line-height:1.4}
.alert-tag{margin-left:auto;flex-shrink:0;font-size:.67rem;font-weight:700;
  padding:2px 8px;border-radius:20px;white-space:nowrap}
.tag-red  {background:#fee2e2;color:#991b1b}
.tag-amber{background:#fef3c7;color:#92400e}
.tag-blue {background:#dbeafe;color:#1e40af}

.section-label{font-size:.7rem;font-weight:700;color:#bbb;letter-spacing:.08em;
  text-transform:uppercase;margin:0 0 .7rem}

.dept-row{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:1.5rem}
.dept-card{background:#fff;border:1px solid #ebebeb;border-radius:12px;padding:.85rem 1rem}
.dept-card-name{font-size:.76rem;font-weight:700;color:#555;margin-bottom:.4rem;letter-spacing:.01em}
.dept-card-score{font-size:1.35rem;font-weight:700;letter-spacing:-.03em;margin-bottom:.35rem}
.dept-bar{background:#f0f0f0;border-radius:4px;height:4px;overflow:hidden;margin-bottom:.35rem}
.dept-fill{height:100%;border-radius:4px}
.dept-meta{font-size:.69rem;color:#bbb}

.bottom-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:14px;margin-bottom:1.5rem}
.chart-wrap{position:relative;height:185px}

.insight-item{display:flex;gap:9px;padding:.48rem 0;border-bottom:1px solid #f5f5f5;align-items:flex-start}
.insight-item:last-child{border-bottom:none}
.insight-icon{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;flex-shrink:0;margin-top:2px;font-size:9px;font-weight:800}
.i-up  {background:#dcfce7;color:#166534}
.i-down{background:#fee2e2;color:#991b1b}
.i-warn{background:#fef3c7;color:#92400e}
.i-info{background:#dbeafe;color:#1e40af}
.insight-text{font-size:.74rem;color:#555;line-height:1.5}
.insight-text strong{color:#111;font-weight:700}

.top-item{display:flex;align-items:center;gap:9px;padding:.48rem 0;border-bottom:1px solid #f5f5f5}
.top-item:last-child{border-bottom:none}
.top-rank{font-size:.68rem;font-weight:700;color:#ccc;width:16px;text-align:center;flex-shrink:0}
.top-avatar{width:27px;height:27px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:.62rem;font-weight:800;flex-shrink:0;color:#fff}
.top-info{flex:1;min-width:0}
.top-name{font-size:.79rem;font-weight:700;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top-dept{font-size:.67rem;color:#aaa}
.top-score{font-size:.83rem;font-weight:700;color:#16a34a;white-space:nowrap}

.btn-toggle{font-size:.74rem;color:#999;background:none;border:none;cursor:pointer;padding:0;
  font-family:'DM Sans',sans-serif}
.btn-toggle:hover{color:#333}
.table-filters{display:flex;gap:8px;margin-bottom:.85rem;flex-wrap:wrap}
.tbl-search{flex:1;min-width:180px;padding:.38rem .7rem;font-size:.77rem;
  border:1px solid #e0e0e0;border-radius:8px;outline:none;font-family:'DM Sans',sans-serif}
.tbl-search:focus{border-color:#888}
.tbl-select{padding:.38rem .65rem;font-size:.77rem;border:1px solid #e0e0e0;border-radius:8px;
  outline:none;background:#fff;cursor:pointer;font-family:'DM Sans',sans-serif}
.tbl-wrap{overflow-x:auto}
.emp-table{width:100%;border-collapse:collapse;font-size:.77rem}
.emp-table th{font-size:.67rem;font-weight:700;color:#bbb;text-transform:uppercase;
  letter-spacing:.06em;padding:.5rem .6rem;border-bottom:1.5px solid #f0f0f0;text-align:left}
.emp-table td{padding:.55rem .6rem;border-bottom:1px solid #f7f7f7;vertical-align:middle;color:#333}
.emp-table tbody tr:hover td{background:#fafafa}
.risk-pill{font-size:.67rem;font-weight:700;padding:2px 9px;border-radius:20px;white-space:nowrap}
.pill-ok  {background:#dcfce7;color:#166534}
.pill-med {background:#dbeafe;color:#1e40af}
.pill-high{background:#fef3c7;color:#92400e}
.pill-crit{background:#fee2e2;color:#991b1b}
.btn-chart{font-size:.69rem;padding:3px 8px;border:1px solid #d0d0d0;border-radius:6px;
  background:#fff;cursor:pointer;color:#555;font-family:'DM Sans',sans-serif}
.btn-chart:hover{border-color:#555}

.loading-overlay{position:fixed;inset:0;background:rgba(255,255,255,.88);
  display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:9999;gap:12px}
.loading-overlay.hidden{display:none!important}
.spinner{width:30px;height:30px;border:3px solid #ebebeb;border-top-color:#333;
  border-radius:50%;animation:spin .7s linear infinite}
.loading-overlay p{font-size:.8rem;color:#777}

/* History modal is built dynamically in lstm-dashboard.js using Tailwind utility classes.
   No custom CSS needed here — all modal styling is inline Tailwind. */

.empty-state{font-size:.78rem;color:#ccc;text-align:center;padding:2rem 0}

@media(max-width:1100px){
  .metric-strip{grid-template-columns:repeat(3,1fr)}
  .main-grid{grid-template-columns:1fr}
  .dept-row{grid-template-columns:repeat(3,1fr)}
  .bottom-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:700px){
  .metric-strip{grid-template-columns:1fr 1fr}
  .dept-row{grid-template-columns:1fr 1fr}
  .bottom-grid{grid-template-columns:1fr}
}
</style>

@endsection

@push('scripts')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endpush