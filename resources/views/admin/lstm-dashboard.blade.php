@extends('layout_dashboard')

@section('content')
<div class="lstm-dash">

    {{-- HEADER --}}
    <div class="dash-header">
        <div>
            <h2 class="dash-title">Productivity Insights</h2>
            <p class="dash-subtitle">LSTM-powered predictions &middot; Last run <span id="last-run">—</span></p>
        </div>
        <div style="display:flex;gap:8px">
            <button id="btn-export" class="btn-secondary">Export Excel</button>
            <button id="btn-refresh" class="btn-primary">
                <svg id="refresh-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M1 4v6h6"/><path d="M23 20v-6h-6"/>
                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                </svg>
                Refresh predictions
            </button>
        </div>
    </div>

    {{-- METRIC STRIP --}}
    <div class="metric-strip">
        <div class="metric-tile">
            <span class="metric-num" id="m-avg">—</span>
            <span class="metric-lbl">Team avg predicted</span>
            <span class="metric-sub" id="m-avg-delta"></span>
        </div>
        <div class="metric-tile accent-danger">
            <span class="metric-num" id="m-risk">—</span>
            <span class="metric-lbl">Need attention (&lt;50%)</span>
            <span class="metric-sub" id="m-risk-sub"></span>
        </div>
        <div class="metric-tile accent-warn">
            <span class="metric-num" id="m-burnout">—</span>
            <span class="metric-lbl">Burnout signals</span>
            <span class="metric-sub" style="color:#92400e">High hrs, low score</span>
        </div>
        <div class="metric-tile accent-ok">
            <span class="metric-num" id="m-high">—</span>
            <span class="metric-lbl">High performers (≥80%)</span>
            <span class="metric-sub" id="m-high-sub"></span>
        </div>
        <div class="metric-tile">
            <span class="metric-num" id="m-acc">—</span>
            <span class="metric-lbl">Model accuracy</span>
            <span class="metric-sub" id="m-acc-sub"></span>
        </div>
    </div>

    {{-- MAIN GRID: attention table + alerts --}}
    <div class="main-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Needs attention</span>
                <span class="panel-badge danger" id="badge-atrisk">—</span>
            </div>
            <div class="emp-grid attn-header">
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

    {{-- ENGINEERED FEATURE BREAKDOWN --}}
    <div class="section-label">Engineered feature breakdown</div>
    <div class="feature-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">score_trend distribution</span>
                <span class="panel-badge neutral">avg_7d − avg_30d</span>
            </div>
            <div class="chart-wrap" style="height:120px"><canvas id="trend-chart"></canvas></div>
            <p class="chart-hint">Negative = declining momentum · Positive = improving</p>
        </div>
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Task signal coverage</span>
                <span class="panel-badge info">has_task_signal</span>
            </div>
            <div style="display:flex;align-items:center;gap:16px;margin-top:4px">
                <div style="position:relative;height:90px;width:90px;flex-shrink:0">
                    <canvas id="task-signal-chart"></canvas>
                </div>
                <div id="task-signal-legend" class="task-legend-text"></div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Burnout signal composite</span>
                <span class="panel-badge warn">hrs + trend</span>
            </div>
            <div class="burnout-grid" id="burnout-grid">
                <div class="burnout-stat">
                    <div class="bs-val" id="b-overwork">—</div>
                    <div class="bs-lbl">Overwork (&gt;9h/day)</div>
                    <div class="bs-bar"><div class="bs-fill" id="b-overwork-bar" style="background:#EF9F27"></div></div>
                </div>
                <div class="burnout-stat">
                    <div class="bs-val" id="b-neg-trend">—</div>
                    <div class="bs-lbl">Negative score_trend</div>
                    <div class="bs-bar"><div class="bs-fill" id="b-neg-trend-bar" style="background:#EF9F27"></div></div>
                </div>
                <div class="burnout-stat">
                    <div class="bs-val" id="b-combined">—</div>
                    <div class="bs-lbl">Both signals combined</div>
                    <div class="bs-bar"><div class="bs-fill" id="b-combined-bar" style="background:#E24B4A"></div></div>
                </div>
            </div>
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
                <span class="panel-title">7-day prediction horizon</span>
                <span class="panel-hint">actual vs LSTM predicted</span>
            </div>
            <div class="chart-wrap"><canvas id="horizon-chart"></canvas></div>
            <div class="horizon-legend">
                <span class="legend-dot" style="background:#378ADD"></span> Actual &nbsp;&nbsp;
                <span class="legend-dot legend-dashed" style="background:#1D9E75"></span> LSTM predicted
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><span class="panel-title">Score distribution</span><span class="panel-hint">predicted productivity</span></div>
            <div class="chart-wrap"><canvas id="dist-chart"></canvas></div>
        </div>

        <div class="panel">
            <div class="panel-head"><span class="panel-title">This week's insights</span></div>
            <div id="insights-list"></div>
        </div>
    </div>

    {{-- TOP PERFORMERS + MODEL TRANSPARENCY --}}
    <div class="transparency-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Top performers</span>
                <span class="panel-badge ok" id="badge-top">—</span>
            </div>
            <div id="top-list"><div class="empty-state">Loading…</div></div>
        </div>

        <div class="panel" style="grid-column: span 2">
            <div class="panel-head">
                <span class="panel-title">Model transparency</span>
                <span class="panel-badge neutral" id="model-version-badge">LSTM v1.0 · LOOKBACK=7</span>
            </div>
            <div class="model-inner">
                <div>
                    <div class="model-section-lbl">Model health</div>
                    <div class="model-stats-grid">
                        <div class="model-stat"><div class="ms-val" id="ms-loss">—</div><div class="ms-lbl">Val loss</div></div>
                        <div class="model-stat"><div class="ms-val" id="ms-mae">—</div><div class="ms-lbl">Best MAE</div></div>
                        <div class="model-stat"><div class="ms-val" id="ms-epochs">—</div><div class="ms-lbl">Epochs ran</div></div>
                        <div class="model-stat"><div class="ms-val" id="ms-conf">—</div><div class="ms-lbl">Confidence</div></div>
                    </div>
                </div>
                <div>
                    <div class="model-section-lbl">Feature importance (inferred from LSTM weights)</div>
                    <div id="feature-importance-list" class="fi-list">
                        <div class="empty-state">Loading…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALL EMPLOYEES TABLE --}}
    <div class="section-row">
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
                <option value="high">High performers (≥80%)</option>
                <option value="medium">Medium (60–79%)</option>
                <option value="low">Needs attention (&lt;60%)</option>
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
/* ─── Reset & base ──────────────────────────────────────── */
.lstm-dash *{box-sizing:border-box}
.lstm-dash{padding:1.5rem 0 3rem;font-family:'DM Sans',sans-serif;color:#111}

/* ─── Header ────────────────────────────────────────────── */
.dash-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem}
.dash-title{font-size:1.35rem;font-weight:600;letter-spacing:-.02em;margin:0 0 3px}
.dash-subtitle{font-size:.75rem;color:#888;margin:0}

.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:.4rem .95rem;font-size:.76rem;
  font-weight:600;border:1px solid #111;border-radius:8px;background:#111;color:#fff;
  cursor:pointer;transition:all .15s;white-space:nowrap;font-family:'DM Sans',sans-serif}
.btn-primary:hover{background:#333;border-color:#333}
.btn-primary:disabled{opacity:.45;cursor:not-allowed}
.btn-primary.spinning #refresh-icon{animation:spin .7s linear infinite}
.btn-secondary{display:inline-flex;align-items:center;gap:6px;padding:.4rem .95rem;font-size:.76rem;
  font-weight:600;border:1px solid #d4d4d4;border-radius:8px;background:#fff;color:#333;
  cursor:pointer;transition:all .15s;font-family:'DM Sans',sans-serif}
.btn-secondary:hover{border-color:#888;background:#fafafa}
@keyframes spin{to{transform:rotate(360deg)}}

/* ─── Metric strip ───────────────────────────────────────── */
.metric-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:1.5rem}
.metric-tile{background:#fafafa;border:1px solid #ebebeb;border-radius:12px;padding:.85rem 1rem}
.metric-tile.accent-danger{border-left:3px solid #E24B4A;border-radius:0 12px 12px 0}
.metric-tile.accent-warn  {border-left:3px solid #EF9F27;border-radius:0 12px 12px 0}
.metric-tile.accent-ok    {border-left:3px solid #639922;border-radius:0 12px 12px 0}
.metric-num{display:block;font-size:1.5rem;font-weight:600;letter-spacing:-.03em;line-height:1;margin-bottom:3px}
.metric-lbl{font-size:.7rem;color:#888;font-weight:500}
.metric-sub{display:block;font-size:.69rem;margin-top:3px;color:#999}

/* ─── Section label ─────────────────────────────────────── */
.section-label{font-size:.68rem;font-weight:700;color:#bbb;letter-spacing:.08em;
  text-transform:uppercase;margin:0 0 .75rem}
.section-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem}

/* ─── Panels ─────────────────────────────────────────────── */
.panel{background:#fff;border:1px solid #ebebeb;border-radius:14px;padding:1rem 1.2rem;overflow:hidden}
.panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem}
.panel-title{font-size:.82rem;font-weight:700;color:#111;letter-spacing:-.01em}
.panel-hint{font-size:.7rem;color:#ccc}
.panel-badge{font-size:.67rem;font-weight:700;padding:2px 9px;border-radius:20px}
.panel-badge.neutral{background:#f1f5f9;color:#64748b}
.panel-badge.ok     {background:#dcfce7;color:#166534}
.panel-badge.danger {background:#fee2e2;color:#991b1b}
.panel-badge.info   {background:#dbeafe;color:#1e40af}
.panel-badge.warn   {background:#fef3c7;color:#92400e}

/* ─── Main grid ───────────────────────────────────────────── */
.main-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-bottom:1.5rem}

/* ─── Employee attention grid ────────────────────────────── */
.emp-grid{display:grid;grid-template-columns:1.8fr 1fr 1fr .8fr;gap:8px;align-items:center;
  padding:.42rem 0;border-bottom:1px solid #f2f2f2;font-size:.77rem}
.attn-header span{font-size:.67rem;font-weight:700;color:#bbb;text-transform:uppercase;letter-spacing:.06em}
.emp-name{font-size:.8rem;font-weight:700;color:#111}
.emp-dept{font-size:.68rem;color:#aaa;margin-top:1px}
.bar-bg{background:#f0f0f0;border-radius:4px;height:4px;margin-top:4px;overflow:hidden}
.bar-fg{height:100%;border-radius:4px;transition:width .5s ease}
.score-txt{font-size:.77rem;font-weight:600;color:#555;margin-top:2px}
.trend-up  {color:#166534;font-size:.72rem;font-weight:700}
.trend-down{color:#991b1b;font-size:.72rem;font-weight:700}
.trend-flat{color:#94a3b8;font-size:.72rem;font-weight:700}

/* ─── Alert items ─────────────────────────────────────────── */
.alert-item{display:flex;align-items:flex-start;gap:9px;padding:.55rem 0;border-bottom:1px solid #f5f5f5}
.alert-item:last-child{border-bottom:none}
.alert-dot{width:6px;height:6px;border-radius:50%;margin-top:5px;flex-shrink:0}
.dot-red  {background:#E24B4A}.dot-amber{background:#EF9F27}.dot-blue{background:#378ADD}
.alert-name{font-size:.8rem;font-weight:700;color:#111}
.alert-desc{font-size:.71rem;color:#666;margin-top:2px;line-height:1.45}
.alert-tag{margin-left:auto;flex-shrink:0;font-size:.66rem;font-weight:700;
  padding:2px 8px;border-radius:20px;white-space:nowrap;align-self:flex-start}
.tag-red  {background:#fee2e2;color:#991b1b}
.tag-amber{background:#fef3c7;color:#92400e}
.tag-blue {background:#dbeafe;color:#1e40af}

/* ─── Feature breakdown grid ─────────────────────────────── */
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:1.5rem}
.chart-hint{font-size:.68rem;color:#bbb;margin-top:6px}
.task-legend-text{font-size:.75rem;color:#666;line-height:1.7}
.burnout-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:4px}
.burnout-stat{background:#fafafa;border-radius:8px;padding:10px 12px}
.bs-val{font-size:1.2rem;font-weight:600;letter-spacing:-.02em;margin-bottom:2px}
.bs-lbl{font-size:.69rem;color:#888}
.bs-bar{background:#ebebeb;border-radius:3px;height:3px;margin-top:6px;overflow:hidden}
.bs-fill{height:100%;border-radius:3px;transition:width .5s}

/* ─── Department row ─────────────────────────────────────── */
.dept-row{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:1.5rem}
.dept-card{background:#fff;border:1px solid #ebebeb;border-radius:12px;padding:.85rem 1rem}
.dept-card-name{font-size:.74rem;font-weight:700;color:#555;margin-bottom:.4rem;letter-spacing:.01em}
.dept-card-score{font-size:1.3rem;font-weight:600;letter-spacing:-.03em;margin-bottom:.35rem}
.dept-bar{background:#f0f0f0;border-radius:4px;height:3px;overflow:hidden;margin-bottom:.35rem}
.dept-fill{height:100%;border-radius:4px}
.dept-meta{font-size:.68rem;color:#bbb}

/* ─── Bottom grid ─────────────────────────────────────────── */
.bottom-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:14px;margin-bottom:1.5rem}
.chart-wrap{position:relative;height:160px}
.horizon-legend{font-size:.7rem;color:#999;margin-top:8px;display:flex;align-items:center;gap:4px}
.legend-dot{display:inline-block;width:8px;height:8px;border-radius:50%;vertical-align:middle}
.legend-dashed{border-radius:0;height:2px;width:14px;vertical-align:middle}

/* ─── Insights ────────────────────────────────────────────── */
.insight-item{display:flex;gap:9px;padding:.45rem 0;border-bottom:1px solid #f5f5f5;align-items:flex-start}
.insight-item:last-child{border-bottom:none}
.insight-icon{width:17px;height:17px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;flex-shrink:0;margin-top:2px;font-size:8px;font-weight:800}
.i-up  {background:#dcfce7;color:#166534}
.i-down{background:#fee2e2;color:#991b1b}
.i-warn{background:#fef3c7;color:#92400e}
.i-info{background:#dbeafe;color:#1e40af}
.insight-text{font-size:.73rem;color:#555;line-height:1.5}
.insight-text strong{color:#111;font-weight:700}

/* ─── Transparency + top performers grid ─────────────────── */
.transparency-grid{display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:1.5rem}

/* ─── Top performers ─────────────────────────────────────── */
.top-item{display:flex;align-items:center;gap:9px;padding:.45rem 0;border-bottom:1px solid #f5f5f5}
.top-item:last-child{border-bottom:none}
.top-rank{font-size:.67rem;font-weight:700;color:#ccc;width:14px;text-align:center;flex-shrink:0}
.top-avatar{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:.6rem;font-weight:700;flex-shrink:0;color:#fff}
.top-info{flex:1;min-width:0}
.top-name{font-size:.78rem;font-weight:700;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top-dept{font-size:.66rem;color:#aaa}
.top-score{font-size:.82rem;font-weight:700;color:#166534;white-space:nowrap}

/* ─── Model transparency ─────────────────────────────────── */
.model-inner{display:grid;grid-template-columns:1fr 1.6fr;gap:20px}
.model-section-lbl{font-size:.7rem;color:#999;margin-bottom:8px;font-weight:600}
.model-stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.model-stat{background:#fafafa;border-radius:8px;padding:10px 12px}
.ms-val{font-size:1.15rem;font-weight:600;letter-spacing:-.02em;margin-bottom:2px}
.ms-val.green{color:#166534}.ms-val.red{color:#991b1b}
.ms-lbl{font-size:.69rem;color:#999}
.fi-list{margin-top:2px}
.fi-row{display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.75rem}
.fi-label{width:140px;flex-shrink:0;color:#666}
.fi-track{flex:1;background:#f0f0f0;border-radius:3px;height:5px;overflow:hidden}
.fi-fill{height:100%;border-radius:3px;background:#378ADD}
.fi-val{width:30px;text-align:right;color:#bbb;flex-shrink:0;font-size:.7rem}

/* ─── Table ───────────────────────────────────────────────── */
.btn-toggle{font-size:.73rem;color:#999;background:none;border:none;cursor:pointer;padding:0;
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
.risk-pill{font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:20px;white-space:nowrap}
.pill-ok  {background:#dcfce7;color:#166534}
.pill-med {background:#dbeafe;color:#1e40af}
.pill-high{background:#fef3c7;color:#92400e}
.pill-crit{background:#fee2e2;color:#991b1b}
.btn-chart{font-size:.68rem;padding:3px 8px;border:1px solid #d0d0d0;border-radius:6px;
  background:#fff;cursor:pointer;color:#555;font-family:'DM Sans',sans-serif}
.btn-chart:hover{border-color:#555}

/* ─── Loading overlay ─────────────────────────────────────── */
.loading-overlay{position:fixed;inset:0;background:rgba(255,255,255,.9);
  display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:9999;gap:12px}
.loading-overlay.hidden{display:none!important}
.spinner{width:28px;height:28px;border:2.5px solid #ebebeb;border-top-color:#333;
  border-radius:50%;animation:spin .7s linear infinite}
.loading-overlay p{font-size:.78rem;color:#888}
.empty-state{font-size:.77rem;color:#ccc;text-align:center;padding:2rem 0}

/* ─── Responsive ─────────────────────────────────────────── */
@media(max-width:1200px){
  .metric-strip{grid-template-columns:repeat(3,1fr)}
  .main-grid{grid-template-columns:1fr}
  .feature-grid{grid-template-columns:1fr 1fr}
  .dept-row{grid-template-columns:repeat(3,1fr)}
  .bottom-grid{grid-template-columns:1fr 1fr}
  .transparency-grid{grid-template-columns:1fr}
  .model-inner{grid-template-columns:1fr}
}
@media(max-width:700px){
  .metric-strip{grid-template-columns:1fr 1fr}
  .feature-grid{grid-template-columns:1fr}
  .dept-row{grid-template-columns:1fr 1fr}
  .bottom-grid{grid-template-columns:1fr}
  .burnout-grid{grid-template-columns:1fr}
}
</style>

@endsection

@push('scripts')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endpush