@extends('layout_dashboard')

@section('content')
<div class="lstm-dash">

    {{-- ══════════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════════ --}}
    <div class="dash-header">
        <div>
            <h2 class="dash-title">Productivity Outlook</h2>
            <p class="dash-subtitle"
               title="Dự đoán hạng năng suất ngày mai cho từng nhân viên dựa trên 14 ngày làm việc gần nhất.">
                Tomorrow's predicted productivity class · LSTM forecast based on past 14 days
            </p>
        </div>
        <div class="header-actions">
            <span class="last-run" title="Lần chạy mô hình gần nhất">
                Last updated <span id="last-run">—</span>
            </span>
            <button id="btn-export" class="btn-secondary" title="Tải báo cáo Excel">Export</button>
            <button id="btn-refresh" class="btn-primary" title="Chạy lại dự đoán">
                <svg id="refresh-icon" width="13" height="13" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M1 4v6h6"/><path d="M23 20v-6h-6"/>
                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TIER 1 — SNAPSHOT
         "What's the situation tomorrow?"
    ══════════════════════════════════════════════════════ --}}
    <div class="tier-1 panel">
        <div class="snapshot-row">
            <div class="snapshot-headline">
                <div class="snapshot-label" title="Tổng quan dự đoán cho ngày mai">Tomorrow's outlook</div>
                <div class="snapshot-main">
                    <span class="snapshot-num" id="snap-attention">—</span>
                    <span class="snapshot-text">need attention</span>
                </div>
                <div class="snapshot-sub" id="snap-sub">—</div>
            </div>
            <div class="snapshot-bars">
                <div class="snap-bar snap-bar-low"
                     title="Số nhân viên được dự đoán có năng suất Thấp ngày mai">
                    <span class="snap-bar-num" id="snap-low">—</span>
                    <span class="snap-bar-lbl">Predicted Low <span class="snap-bar-thresh">&lt;50</span></span>
                </div>
                <div class="snap-bar snap-bar-med"
                     title="Số nhân viên được dự đoán có năng suất Trung bình ngày mai">
                    <span class="snap-bar-num" id="snap-med">—</span>
                    <span class="snap-bar-lbl">Predicted Medium <span class="snap-bar-thresh">50–79</span></span>
                </div>
                <div class="snap-bar snap-bar-high"
                     title="Số nhân viên được dự đoán có năng suất Cao ngày mai">
                    <span class="snap-bar-num" id="snap-high">—</span>
                    <span class="snap-bar-lbl">Predicted High <span class="snap-bar-thresh">≥80</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TIER 2 — ACTION
         "Who do I need to talk to?"
    ══════════════════════════════════════════════════════ --}}
    <div class="tier-2">
        <div class="panel attention-panel">
            <div class="panel-head">
                <div>
                    <span class="panel-title" title="Nhân viên cần được quan tâm">Who needs attention</span>
                    <span class="panel-sub">Sorted by predicted risk · click a row to see history</span>
                </div>
                <div class="attention-controls">
                    <select id="attn-filter" class="tbl-select" title="Lọc theo dự đoán">
                        <option value="all">All concerns</option>
                        <option value="low">Predicted Low only</option>
                        <option value="declining">Declining trend only</option>
                    </select>
                </div>
            </div>

            <div class="attn-table-head">
                <span title="Tên nhân viên">Employee</span>
                <span title="Xu hướng 7 ngày qua">Recent trajectory</span>
                <span title="Hạng dự đoán cho ngày mai">Predicted</span>
                <span title="Độ tự tin của mô hình">Model confidence</span>
                <span></span>
            </div>
            <div id="attention-list">
                <div class="empty-state">Loading…</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TIER 3 — CONTEXT
         "How does this break down?"
    ══════════════════════════════════════════════════════ --}}
    <div class="section-label">Context</div>
    <div class="tier-3">
        {{-- Department breakdown --}}
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title" title="Trung bình theo phòng ban">By department</span>
                <span class="panel-hint">Predicted average for tomorrow</span>
            </div>
            <div id="dept-list">
                <div class="empty-state">Loading…</div>
            </div>
        </div>

        {{-- Predicted score distribution (REAL data) --}}
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title" title="Phân phối điểm dự đoán">Score distribution</span>
                <span class="panel-hint">Tomorrow's predicted scores</span>
            </div>
            <div class="chart-wrap"><canvas id="dist-chart"></canvas></div>
        </div>

        {{-- Top performers (compact) --}}
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title" title="Nhân viên có dự đoán cao nhất">Top predicted</span>
                <span class="panel-hint">Tomorrow</span>
            </div>
            <div id="top-list">
                <div class="empty-state">Loading…</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TIER 4 — TRUST
         "Can I trust these numbers?"
    ══════════════════════════════════════════════════════ --}}
    <div class="section-label">About this model</div>
    <div class="tier-4 panel">
        <div class="trust-grid">
            <div class="trust-cell"
                 title="Độ chính xác trên dữ liệu kiểm tra (chưa từng thấy trong quá trình huấn luyện)">
                <div class="trust-num" id="ms-acc">—</div>
                <div class="trust-lbl">Test accuracy</div>
                <div class="trust-sub">Held-out data, Feb 2026 onward</div>
            </div>
            <div class="trust-cell"
                 title="So với cách đoán đơn giản: 'ngày mai giống ngày hôm nay'">
                <div class="trust-num" id="ms-uplift">—</div>
                <div class="trust-lbl">Lift over naive baseline</div>
                <div class="trust-sub">vs. "tomorrow = today" guess</div>
            </div>
            <div class="trust-cell"
                 title="Macro F1 — trung bình cân bằng giữa các hạng">
                <div class="trust-num" id="ms-f1">—</div>
                <div class="trust-lbl">Macro F1</div>
                <div class="trust-sub">Balance across all classes</div>
            </div>
            <div class="trust-cell"
                 title="Số ngày dữ liệu được dùng làm đầu vào">
                <div class="trust-num" id="ms-lookback">14</div>
                <div class="trust-lbl">Lookback window</div>
                <div class="trust-sub">Days of history per prediction</div>
            </div>
        </div>

        <div class="trust-classes">
            <div class="trust-class-row">
                <span class="trust-class-lbl">High class</span>
                <span class="trust-class-bar"><span class="tcb-fill tcb-high" id="cls-f1-high-bar"></span></span>
                <span class="trust-class-val" id="cls-f1-high">—</span>
                <span class="trust-class-note">Strong predictions</span>
            </div>
            <div class="trust-class-row">
                <span class="trust-class-lbl">Medium class</span>
                <span class="trust-class-bar"><span class="tcb-fill tcb-med" id="cls-f1-med-bar"></span></span>
                <span class="trust-class-val" id="cls-f1-med">—</span>
                <span class="trust-class-note">Reasonably reliable</span>
            </div>
            <div class="trust-class-row">
                <span class="trust-class-lbl">Low class</span>
                <span class="trust-class-bar"><span class="tcb-fill tcb-low" id="cls-f1-low-bar"></span></span>
                <span class="trust-class-val" id="cls-f1-low">—</span>
                <span class="trust-class-note">Use as signal, not verdict</span>
            </div>
        </div>

        <div class="trust-disclaimer"
             title="Lưu ý quan trọng về cách diễn giải kết quả">
            <strong>How to read this:</strong> The model forecasts a class, not an exact score.
            Predictions for the Low class have lower reliability (F1 = 0.38) — when the model
            flags someone as Low, treat it as a prompt to check in, not a final judgement.
            High predictions are more dependable (F1 = 0.78). Class thresholds: Low &lt;50,
            Medium 50–79, High ≥80.
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         FULL TABLE (collapsed by default)
    ══════════════════════════════════════════════════════ --}}
    <div class="section-row">
        <div class="section-label" style="margin:0">All employees</div>
        <button class="btn-toggle" id="btn-toggle-all" title="Hiển thị/ẩn bảng đầy đủ">Show all ▾</button>
    </div>
    <div class="panel" id="panel-all" style="display:none">
        <div class="table-filters">
            <input type="text" id="search" placeholder="Search name or department…" class="tbl-search">
            <select id="dept-filter" class="tbl-select">
                <option value="">All departments</option>
            </select>
            <select id="risk-filter" class="tbl-select">
                <option value="">All classes</option>
                <option value="high">Predicted High</option>
                <option value="medium">Predicted Medium</option>
                <option value="low">Predicted Low</option>
            </select>
        </div>
        <div class="tbl-wrap">
            <table class="emp-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th title="Điểm hôm nay">Today</th>
                        <th title="Hạng dự đoán cho ngày mai">Predicted (tomorrow)</th>
                        <th title="Xu hướng 7 ngày">Trend</th>
                        <th title="Độ tự tin">Confidence</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="all-tbody"></tbody>
            </table>
        </div>
    </div>

    {{-- LOADING --}}
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Loading predictions…</p>
    </div>
</div>

<style>
/* ─── Reset & base ──────────────────────────────────────── */
.lstm-dash *{box-sizing:border-box}
.lstm-dash{padding:1.5rem 0 3rem;font-family:'DM Sans',sans-serif;color:#1a1a1a}

/* ─── Header ────────────────────────────────────────────── */
.dash-header{display:flex;justify-content:space-between;align-items:flex-start;
  margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap}
.dash-title{font-size:1.4rem;font-weight:600;letter-spacing:-.02em;margin:0 0 4px;color:#0f172a}
.dash-subtitle{font-size:.78rem;color:#64748b;margin:0;line-height:1.5;cursor:help}
.header-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.last-run{font-size:.72rem;color:#94a3b8;cursor:help;margin-right:4px}

.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:.45rem 1rem;
  font-size:.76rem;font-weight:600;border:1px solid #0f172a;border-radius:8px;
  background:#0f172a;color:#fff;cursor:pointer;transition:all .15s;
  white-space:nowrap;font-family:inherit}
.btn-primary:hover{background:#1e293b}
.btn-primary:disabled{opacity:.5;cursor:not-allowed}
.btn-primary.spinning #refresh-icon{animation:spin .7s linear infinite}
.btn-secondary{display:inline-flex;align-items:center;gap:6px;padding:.45rem 1rem;
  font-size:.76rem;font-weight:600;border:1px solid #cbd5e1;border-radius:8px;
  background:#fff;color:#334155;cursor:pointer;transition:all .15s;font-family:inherit}
.btn-secondary:hover{border-color:#94a3b8;background:#f8fafc}
@keyframes spin{to{transform:rotate(360deg)}}

/* ─── Panels ─────────────────────────────────────────────── */
.panel{background:#fff;border:1px solid #ebebeb;border-radius:14px;padding:1.1rem 1.25rem}
.panel-head{display:flex;align-items:flex-start;justify-content:space-between;
  margin-bottom:.85rem;gap:8px}
.panel-title{font-size:.85rem;font-weight:700;color:#0f172a;letter-spacing:-.01em;
  cursor:help;display:block}
.panel-sub{font-size:.7rem;color:#94a3b8;display:block;margin-top:2px;font-weight:500}
.panel-hint{font-size:.7rem;color:#94a3b8;font-weight:500}

/* ─── Section label ─────────────────────────────────────── */
.section-label{font-size:.68rem;font-weight:700;color:#94a3b8;letter-spacing:.1em;
  text-transform:uppercase;margin:1.5rem 0 .85rem}
.section-row{display:flex;align-items:center;justify-content:space-between;
  margin:1.5rem 0 .85rem}

/* ═══════════════════════════════════════════════════════════
   TIER 1 — SNAPSHOT
═══════════════════════════════════════════════════════════ */
.tier-1{margin-bottom:14px;padding:1.4rem 1.5rem}
.snapshot-row{display:grid;grid-template-columns:1fr 1.4fr;gap:1.5rem;align-items:center}
.snapshot-label{font-size:.72rem;color:#64748b;margin-bottom:6px;cursor:help}
.snapshot-main{display:flex;align-items:baseline;gap:10px;margin-bottom:4px}
.snapshot-num{font-size:2.3rem;font-weight:600;letter-spacing:-.04em;line-height:1;color:#0f172a}
.snapshot-text{font-size:.95rem;color:#475569;font-weight:500}
.snapshot-sub{font-size:.74rem;color:#94a3b8}

.snapshot-bars{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.snap-bar{padding:.85rem 1rem;border-radius:10px;cursor:help;
  display:flex;flex-direction:column;gap:3px}
.snap-bar-low {background:#fef2f2;color:#991b1b}
.snap-bar-med {background:#f1f5f9;color:#334155}
.snap-bar-high{background:#f0fdf4;color:#166534}
.snap-bar-num{font-size:1.6rem;font-weight:600;letter-spacing:-.03em;line-height:1}
.snap-bar-lbl{font-size:.7rem;font-weight:500;line-height:1.3}
.snap-bar-thresh{opacity:.65;font-weight:400;margin-left:2px}

/* ═══════════════════════════════════════════════════════════
   TIER 2 — ACTION
═══════════════════════════════════════════════════════════ */
.tier-2{margin-bottom:1.5rem}
.attention-panel{padding:1.1rem 0 .5rem}
.attention-panel .panel-head{padding:0 1.25rem;margin-bottom:.85rem}
.attention-controls{display:flex;gap:8px}

.attn-table-head{display:grid;grid-template-columns:1.6fr 1.2fr .7fr 1fr 80px;
  gap:10px;padding:.5rem 1.25rem;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;
  background:#fafbfc}
.attn-table-head span{font-size:.66rem;font-weight:700;color:#94a3b8;
  text-transform:uppercase;letter-spacing:.06em;cursor:help}

.attn-row{display:grid;grid-template-columns:1.6fr 1.2fr .7fr 1fr 80px;
  gap:10px;align-items:center;padding:.7rem 1.25rem;border-bottom:1px solid #f5f5f5;
  font-size:.78rem;transition:background .15s}
.attn-row:hover{background:#fafbfc}
.attn-row:last-child{border-bottom:none}

.attn-emp-name{font-weight:700;font-size:.84rem;color:#0f172a;
  display:flex;align-items:center;gap:8px}
.attn-emp-avatar{width:28px;height:28px;border-radius:50%;display:flex;
  align-items:center;justify-content:center;font-size:.62rem;font-weight:700;
  color:#fff;flex-shrink:0}
.attn-emp-info{display:flex;flex-direction:column;min-width:0}
.attn-emp-dept{font-size:.68rem;color:#94a3b8;font-weight:500;margin-top:1px}

.traj-text{font-size:.74rem;font-weight:600}
.traj-declining{color:#991b1b}
.traj-improving{color:#166534}
.traj-stable{color:#64748b}
.traj-detail{font-size:.66rem;color:#94a3b8;font-weight:500;margin-top:1px}

.cls-pill{font-size:.66rem;font-weight:700;padding:3px 10px;border-radius:20px;
  white-space:nowrap;display:inline-block}
.pill-high{background:#dcfce7;color:#166534}
.pill-med {background:#e0e7ff;color:#3730a3}
.pill-low {background:#fee2e2;color:#991b1b}

.conf-cell{display:flex;align-items:center;gap:8px;cursor:help}
.conf-bar-wrap{flex:1;background:#f1f5f9;border-radius:3px;height:5px;overflow:hidden;max-width:60px}
.conf-bar-fill{height:100%;border-radius:3px;background:#3b82f6;transition:width .5s}
.conf-num{font-size:.72rem;font-weight:600;color:#475569;min-width:32px}

.btn-mini{font-size:.68rem;padding:4px 10px;border:1px solid #e2e8f0;border-radius:6px;
  background:#fff;cursor:pointer;color:#64748b;font-family:inherit;transition:all .15s}
.btn-mini:hover{border-color:#0f172a;color:#0f172a}

.empty-state{font-size:.78rem;color:#cbd5e1;text-align:center;padding:2.5rem 1.25rem}

/* ═══════════════════════════════════════════════════════════
   TIER 3 — CONTEXT
═══════════════════════════════════════════════════════════ */
.tier-3{display:grid;grid-template-columns:1.2fr 1.2fr 1fr;gap:14px;margin-bottom:.5rem}

/* Department list */
.dept-row-item{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;
  padding:.55rem 0;border-bottom:1px solid #f5f5f5}
.dept-row-item:last-child{border-bottom:none}
.dept-name{font-size:.78rem;font-weight:600;color:#0f172a}
.dept-meta{font-size:.66rem;color:#94a3b8;font-weight:500;margin-top:2px}
.dept-bar-wrap{display:flex;align-items:center;gap:8px;width:100%;margin-top:6px}
.dept-bar-track{flex:1;background:#f1f5f9;border-radius:3px;height:5px;overflow:hidden}
.dept-bar-fill-inline{height:100%;border-radius:3px;transition:width .6s ease}
.dept-score-lbl{font-size:.78rem;font-weight:700;color:#0f172a;white-space:nowrap}

.chart-wrap{position:relative;height:160px}

/* Top performers compact */
.top-row{display:flex;align-items:center;gap:9px;padding:.5rem 0;
  border-bottom:1px solid #f5f5f5}
.top-row:last-child{border-bottom:none}
.top-rank{font-size:.7rem;font-weight:700;color:#cbd5e1;width:14px;text-align:center;flex-shrink:0}
.top-avatar{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:.55rem;font-weight:700;flex-shrink:0;color:#fff}
.top-info{flex:1;min-width:0}
.top-name{font-size:.76rem;font-weight:600;color:#0f172a;white-space:nowrap;
  overflow:hidden;text-overflow:ellipsis}
.top-dept{font-size:.64rem;color:#94a3b8}
.top-score-num{font-size:.78rem;font-weight:700;color:#166534;white-space:nowrap}

/* ═══════════════════════════════════════════════════════════
   TIER 4 — TRUST
═══════════════════════════════════════════════════════════ */
.tier-4{margin-bottom:1.5rem;padding:1.3rem 1.5rem}
.trust-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:1.5rem;
  padding-bottom:1.2rem;border-bottom:1px solid #f1f5f9}
.trust-cell{cursor:help}
.trust-num{font-size:1.7rem;font-weight:600;letter-spacing:-.03em;line-height:1;
  color:#0f172a;margin-bottom:4px}
.trust-lbl{font-size:.78rem;font-weight:600;color:#475569;margin-bottom:2px}
.trust-sub{font-size:.68rem;color:#94a3b8}

.trust-classes{display:flex;flex-direction:column;gap:8px;margin-bottom:1rem}
.trust-class-row{display:grid;grid-template-columns:90px 1fr 50px 1.1fr;gap:12px;
  align-items:center;font-size:.74rem}
.trust-class-lbl{font-weight:600;color:#0f172a}
.trust-class-bar{background:#f1f5f9;border-radius:3px;height:6px;overflow:hidden;width:100%}
.tcb-fill{display:block;height:100%;border-radius:3px;transition:width .6s ease}
.tcb-high{background:#22c55e}
.tcb-med {background:#3b82f6}
.tcb-low {background:#ef4444}
.trust-class-val{font-weight:700;color:#0f172a;text-align:right}
.trust-class-note{color:#64748b;font-size:.72rem}

.trust-disclaimer{font-size:.74rem;color:#475569;line-height:1.6;
  background:#fafbfc;border-left:3px solid #cbd5e1;padding:.75rem 1rem;border-radius:0 8px 8px 0;
  cursor:help}
.trust-disclaimer strong{color:#0f172a;font-weight:700}

/* ─── Table ───────────────────────────────────────────────── */
.btn-toggle{font-size:.74rem;color:#64748b;background:none;border:none;cursor:pointer;
  padding:0;font-family:inherit;font-weight:500}
.btn-toggle:hover{color:#0f172a}
.table-filters{display:flex;gap:8px;margin-bottom:.85rem;flex-wrap:wrap}
.tbl-search{flex:1;min-width:180px;padding:.42rem .75rem;font-size:.78rem;
  border:1px solid #e2e8f0;border-radius:8px;outline:none;font-family:inherit}
.tbl-search:focus{border-color:#0f172a}
.tbl-select{padding:.42rem .7rem;font-size:.78rem;border:1px solid #e2e8f0;
  border-radius:8px;outline:none;background:#fff;cursor:pointer;font-family:inherit}
.tbl-wrap{overflow-x:auto}
.emp-table{width:100%;border-collapse:collapse;font-size:.78rem}
.emp-table th{font-size:.66rem;font-weight:700;color:#94a3b8;text-transform:uppercase;
  letter-spacing:.06em;padding:.55rem .65rem;border-bottom:1.5px solid #f0f0f0;
  text-align:left;cursor:help}
.emp-table td{padding:.6rem .65rem;border-bottom:1px solid #f7f7f7;vertical-align:middle;color:#334155}
.emp-table tbody tr:hover td{background:#fafbfc}

/* ─── Loading ──────────────────────────────────────────────── */
.loading-overlay{position:fixed;inset:0;background:rgba(255,255,255,.92);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  z-index:9999;gap:14px}
.loading-overlay.hidden{display:none!important}
.spinner{width:30px;height:30px;border:2.5px solid #e2e8f0;border-top-color:#0f172a;
  border-radius:50%;animation:spin .7s linear infinite}
.loading-overlay p{font-size:.78rem;color:#64748b}

/* ─── Responsive ─────────────────────────────────────────── */
@media(max-width:1100px){
  .tier-3{grid-template-columns:1fr 1fr}
  .tier-3>.panel:last-child{grid-column:1/-1}
  .snapshot-row{grid-template-columns:1fr}
  .trust-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:700px){
  .snapshot-bars{grid-template-columns:1fr}
  .tier-3{grid-template-columns:1fr}
  .attn-table-head,.attn-row{grid-template-columns:1.5fr .8fr 80px}
  .attn-table-head span:nth-child(2),
  .attn-table-head span:nth-child(4),
  .attn-row>*:nth-child(2),
  .attn-row>*:nth-child(4){display:none}
  .trust-grid{grid-template-columns:1fr 1fr}
  .trust-class-row{grid-template-columns:80px 1fr 40px}
  .trust-class-note{display:none}
}
</style>

@endsection

@push('scripts')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endpush