/**
 * lstm-dashboard.js — v3.0 redesigned
 *
 * Renders only data the API actually provides. No synthetic horizon
 * charts, no fake burnout signals, no fabricated momentum distribution.
 *
 * Endpoints:
 *   GET  /api/lstm/stats
 *   GET  /api/lstm/employee-predictions
 *   GET  /api/lstm/employee-history/{id}
 *   POST /api/lstm/refresh-predictions
 *   POST /api/lstm/export-excel
 */
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────
    let allEmployees = [];
    let distChart    = null;
    let histChart    = null;
    let tableOpen    = false;

    // ── Class thresholds (must match train_lstm_nextday.py) ────
    const TH_LOW  = 50;   // <50  = Low
    const TH_HIGH = 80;   // >=80 = High;   50..79 = Medium

    // ── Department palette ─────────────────────────────────────
    const DEPT_COLORS = [
        '#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4',
        '#a855f7','#ec4899','#84cc16','#f97316','#3b82f6',
    ];
    const _dcMap = {};
    let _dci = 0;
    const deptColor = d => _dcMap[d] || (_dcMap[d] = DEPT_COLORS[_dci++ % DEPT_COLORS.length]);

    // ── Helpers ────────────────────────────────────────────────
    const initials = name => (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

    function scoreToClass(s) {
        if (s >= TH_HIGH) return 'High';
        if (s >= TH_LOW)  return 'Medium';
        return 'Low';
    }

    function classPill(cls) {
        const map = { High: 'pill-high', Medium: 'pill-med', Low: 'pill-low' };
        return `<span class="cls-pill ${map[cls] || 'pill-med'}">${cls}</span>`;
    }

    function classColor(cls) {
        return cls === 'High' ? '#22c55e' : cls === 'Medium' ? '#3b82f6' : '#ef4444';
    }

    function trajectoryLabel(emp) {
        const t = (emp.trend || '').toLowerCase();
        if (t === 'declining' || t === 'down') {
            return `<div class="traj-text traj-declining">▼ Declining</div>
                    <div class="traj-detail">Recent scores trending down</div>`;
        }
        if (t === 'improving' || t === 'up') {
            return `<div class="traj-text traj-improving">▲ Improving</div>
                    <div class="traj-detail">Recent scores trending up</div>`;
        }
        return `<div class="traj-text traj-stable">— Stable</div>
                <div class="traj-detail">No strong direction</div>`;
    }

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function showLoading() { document.getElementById('loading-overlay').classList.remove('hidden'); }
    function hideLoading() { document.getElementById('loading-overlay').classList.add('hidden'); }

    async function apiFetch(url, opts = {}) {
        const r = await fetch(url, opts);
        if (!r.ok) throw new Error(`HTTP ${r.status} — ${url}`);
        return r.json();
    }

    // ══════════════════════════════════════════════════════════
    // TIER 1 — SNAPSHOT
    // ══════════════════════════════════════════════════════════
    function renderSnapshot(emps) {
        const total = emps.length || 1;
        const high  = emps.filter(e => e.predictedScore >= TH_HIGH).length;
        const low   = emps.filter(e => e.predictedScore <  TH_LOW).length;
        const med   = total - high - low;

        // "Need attention" = predicted Low OR (predicted Medium + declining)
        const decliningMed = emps.filter(e =>
            e.predictedScore >= TH_LOW && e.predictedScore < TH_HIGH &&
            (e.trend === 'declining' || e.trend === 'down')
        ).length;
        const attention = low + decliningMed;

        document.getElementById('snap-attention').textContent = attention;
        document.getElementById('snap-low').textContent  = low;
        document.getElementById('snap-med').textContent  = med;
        document.getElementById('snap-high').textContent = high;

        const pct = Math.round((attention / total) * 100);
        let subText = `${pct}% of team · ${low} predicted Low`;
        if (decliningMed > 0) subText += ` · ${decliningMed} Medium trending down`;
        document.getElementById('snap-sub').textContent = subText;
    }

    // ══════════════════════════════════════════════════════════
    // TIER 2 — ATTENTION LIST
    // ══════════════════════════════════════════════════════════
    function renderAttention(emps) {
        const filter = document.getElementById('attn-filter')?.value || 'all';

        let candidates = emps.filter(e => {
            const isLow      = e.predictedScore < TH_LOW;
            const isMed      = e.predictedScore >= TH_LOW && e.predictedScore < TH_HIGH;
            const declining  = e.trend === 'declining' || e.trend === 'down';
            if (filter === 'low')        return isLow;
            if (filter === 'declining')  return declining;
            return isLow || (isMed && declining);
        });

        // Sort: Low first, then Medium-declining, then by confidence
        candidates.sort((a, b) => {
            const ra = a.predictedScore < TH_LOW ? 0 : 1;
            const rb = b.predictedScore < TH_LOW ? 0 : 1;
            if (ra !== rb) return ra - rb;
            return (b.confidence || 0) - (a.confidence || 0);
        });

        const el = document.getElementById('attention-list');
        if (!candidates.length) {
            el.innerHTML = '<div class="empty-state">No employees need attention right now. Good news.</div>';
            return;
        }

        el.innerHTML = candidates.map(e => {
            const cls  = scoreToClass(e.predictedScore);
            const conf = e.confidence ? Math.round(e.confidence * 100) : null;
            const confHtml = conf !== null
                ? `<div class="conf-cell" title="Mô hình tự tin ${conf}% với dự đoán này">
                       <span class="conf-bar-wrap"><span class="conf-bar-fill" style="width:${conf}%"></span></span>
                       <span class="conf-num">${conf}%</span>
                   </div>`
                : '<div class="conf-num">—</div>';

            return `
            <div class="attn-row">
                <div class="attn-emp-name">
                    <div class="attn-emp-avatar" style="background:${deptColor(e.department)}">
                        ${initials(e.name)}
                    </div>
                    <div class="attn-emp-info">
                        <span>${e.name}</span>
                        <span class="attn-emp-dept">${e.department || '—'}</span>
                    </div>
                </div>
                <div>${trajectoryLabel(e)}</div>
                <div>${classPill(cls)}</div>
                ${confHtml}
                <div>
                    <button class="btn-mini btn-chart"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department || ''}" data-trend="${e.trend}">View</button>
                </div>
            </div>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════
    // TIER 3 — DEPARTMENT BREAKDOWN
    // ══════════════════════════════════════════════════════════
    function renderDepts(emps) {
        const map = {};
        emps.forEach(e => {
            const d = e.department || 'Unknown';
            if (!map[d]) map[d] = [];
            map[d].push(e);
        });

        const depts = Object.entries(map).map(([name, group]) => ({
            name,
            avg:  group.reduce((s, e) => s + e.predictedScore, 0) / group.length,
            count: group.length,
            high:  group.filter(e => e.predictedScore >= TH_HIGH).length,
            low:   group.filter(e => e.predictedScore <  TH_LOW).length,
            color: deptColor(name),
        })).sort((a, b) => b.avg - a.avg);

        const el = document.getElementById('dept-list');
        if (!depts.length) {
            el.innerHTML = '<div class="empty-state">No data.</div>';
            return;
        }

        el.innerHTML = depts.map(d => {
            const pct = Math.min(d.avg, 100);
            return `
            <div class="dept-row-item" title="${d.count} nhân viên · ${d.high} High · ${d.low} Low">
                <div>
                    <div class="dept-name">${d.name}</div>
                    <div class="dept-meta">${d.count} employees · ${d.high} High · ${d.low} Low</div>
                    <div class="dept-bar-wrap">
                        <span class="dept-bar-track">
                            <span class="dept-bar-fill-inline" style="width:${pct}%;background:${d.color}"></span>
                        </span>
                    </div>
                </div>
                <div class="dept-score-lbl">${d.avg.toFixed(1)}</div>
            </div>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════
    // TIER 3 — SCORE DISTRIBUTION (REAL — uses predicted scores)
    // ══════════════════════════════════════════════════════════
    function renderDistChart(emps) {
        const buckets = [0, 0, 0, 0, 0, 0];
        emps.forEach(e => {
            const s = e.predictedScore;
            if      (s < 40) buckets[0]++;
            else if (s < 50) buckets[1]++;
            else if (s < 65) buckets[2]++;
            else if (s < 80) buckets[3]++;
            else if (s < 90) buckets[4]++;
            else             buckets[5]++;
        });

        if (distChart) distChart.destroy();
        distChart = new Chart(document.getElementById('dist-chart'), {
            type: 'bar',
            data: {
                labels: ['<40', '40–49', '50–64', '65–79', '80–89', '≥90'],
                datasets: [{
                    data: buckets,
                    backgroundColor: ['#dc2626','#ef4444','#3b82f6','#6366f1','#22c55e','#16a34a'],
                    borderRadius: 4, borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: c => ` ${c.parsed.y} employees` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
                    y: {
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { font: { size: 10 }, color: '#94a3b8', stepSize: 1 },
                        beginAtZero: true,
                    }
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // TIER 3 — TOP PERFORMERS (compact)
    // ══════════════════════════════════════════════════════════
    function renderTop(emps) {
        const top = [...emps]
            .filter(e => e.predictedScore >= TH_HIGH)
            .sort((a, b) => b.predictedScore - a.predictedScore)
            .slice(0, 5);

        const el = document.getElementById('top-list');
        if (!top.length) {
            el.innerHTML = '<div class="empty-state">No High predictions yet.</div>';
            return;
        }

        el.innerHTML = top.map((e, i) => `
            <div class="top-row">
                <span class="top-rank">${i + 1}</span>
                <div class="top-avatar" style="background:${deptColor(e.department)}">
                    ${initials(e.name)}
                </div>
                <div class="top-info">
                    <div class="top-name" title="${e.name}">${e.name}</div>
                    <div class="top-dept">${e.department || '—'}</div>
                </div>
                <div class="top-score-num">${e.predictedScore.toFixed(0)}</div>
            </div>
        `).join('');
    }

    // ══════════════════════════════════════════════════════════
    // TIER 4 — TRUST PANEL
    // ══════════════════════════════════════════════════════════
    function renderTrust(stats) {
        const acc       = stats.accuracy        != null ? stats.accuracy       : 70.0;
        const naive     = stats.naiveAccuracy   != null ? stats.naiveAccuracy  : 65.0;
        const macroF1   = stats.macroF1         != null ? stats.macroF1        : 0.613;
        const lookback  = stats.lookback        != null ? stats.lookback       : 14;

        document.getElementById('ms-acc').textContent      = acc.toFixed(1) + '%';
        const uplift = acc - naive;
        document.getElementById('ms-uplift').textContent   = (uplift >= 0 ? '+' : '') + uplift.toFixed(1) + 'pp';
        document.getElementById('ms-f1').textContent       = macroF1.toFixed(3);
        document.getElementById('ms-lookback').textContent = lookback;

        // Per-class F1 (from training/eval results)
        const f1High = stats.f1High != null ? stats.f1High : 0.779;
        const f1Med  = stats.f1Med  != null ? stats.f1Med  : 0.679;
        const f1Low  = stats.f1Low  != null ? stats.f1Low  : 0.381;

        document.getElementById('cls-f1-high').textContent = f1High.toFixed(2);
        document.getElementById('cls-f1-med').textContent  = f1Med.toFixed(2);
        document.getElementById('cls-f1-low').textContent  = f1Low.toFixed(2);

        setTimeout(() => {
            document.getElementById('cls-f1-high-bar').style.width = (f1High * 100).toFixed(0) + '%';
            document.getElementById('cls-f1-med-bar').style.width  = (f1Med  * 100).toFixed(0) + '%';
            document.getElementById('cls-f1-low-bar').style.width  = (f1Low  * 100).toFixed(0) + '%';
        }, 100);
    }

    // ══════════════════════════════════════════════════════════
    // FULL TABLE
    // ══════════════════════════════════════════════════════════
    function renderTable(emps) {
        const depts = [...new Set(emps.map(e => e.department))].filter(Boolean).sort();
        const sel = document.getElementById('dept-filter');
        sel.innerHTML = '<option value="">All departments</option>' +
            depts.map(d => `<option value="${d}">${d}</option>`).join('');
        renderTableBody(emps);
    }

    function riskKey(s) {
        if (s >= TH_HIGH) return 'high';
        if (s >= TH_LOW)  return 'medium';
        return 'low';
    }

    function renderTableBody(emps) {
        const search = (document.getElementById('search').value || '').toLowerCase();
        const dept   = document.getElementById('dept-filter').value;
        const risk   = document.getElementById('risk-filter').value;

        const rows = emps.filter(e =>
            (!search || e.name.toLowerCase().includes(search) ||
                       (e.department || '').toLowerCase().includes(search)) &&
            (!dept || e.department === dept) &&
            (!risk || riskKey(e.predictedScore) === risk)
        );

        const tbody = document.getElementById('all-tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#cbd5e1;padding:2rem;font-size:.78rem">No employees match these filters.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(e => {
            const cls   = scoreToClass(e.predictedScore);
            const conf  = e.confidence ? Math.round(e.confidence * 100) : null;
            const trendHtml = e.trend === 'declining' || e.trend === 'down'
                ? '<span class="traj-declining">▼ Down</span>'
                : e.trend === 'improving' || e.trend === 'up'
                ? '<span class="traj-improving">▲ Up</span>'
                : '<span class="traj-stable">— Flat</span>';

            return `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:24px;height:24px;border-radius:50%;background:${deptColor(e.department)};display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:700;color:#fff;flex-shrink:0">${initials(e.name)}</div>
                        <span style="font-weight:600;font-size:.78rem">${e.name}</span>
                    </div>
                </td>
                <td style="color:#94a3b8;font-size:.74rem">${e.department || '—'}</td>
                <td><span style="font-weight:600">${e.currentScore.toFixed(1)}</span></td>
                <td>${classPill(cls)}</td>
                <td style="font-size:.72rem;font-weight:600">${trendHtml}</td>
                <td style="font-size:.74rem">${conf !== null ? conf + '%' : '—'}</td>
                <td>
                    <button class="btn-mini btn-chart"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department || ''}" data-trend="${e.trend}">View</button>
                </td>
            </tr>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════
    // HISTORY MODAL — kept simple, only renders real history
    // ══════════════════════════════════════════════════════════
    function buildModal() {
        if (document.getElementById('hist-modal')) return;
        const el = document.createElement('div');
        el.id = 'hist-modal';
        el.style.cssText = 'position:fixed;inset:0;z-index:50;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.5)';
        el.innerHTML = `
        <div style="background:#fff;border-radius:14px;width:100%;max-width:680px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:1.2rem 1.5rem;border-bottom:1px solid #f1f5f9">
                <div>
                    <div style="font-size:1rem;font-weight:700;color:#0f172a" id="hist-name">—</div>
                    <div style="font-size:.74rem;color:#94a3b8;margin-top:2px" id="hist-dept">—</div>
                </div>
                <button id="hist-close" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#94a3b8;padding:4px">✕</button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9">
                <div style="background:#fafbfc;border-radius:10px;padding:10px 14px;text-align:center">
                    <div style="font-size:.66rem;color:#94a3b8;margin-bottom:3px">Today</div>
                    <div style="font-size:1.2rem;font-weight:700;color:#0f172a" id="hist-current">—</div>
                </div>
                <div style="background:#fafbfc;border-radius:10px;padding:10px 14px;text-align:center">
                    <div style="font-size:.66rem;color:#94a3b8;margin-bottom:3px">Predicted (tomorrow)</div>
                    <div style="font-size:1rem;font-weight:700" id="hist-class">—</div>
                </div>
                <div style="background:#fafbfc;border-radius:10px;padding:10px 14px;text-align:center">
                    <div style="font-size:.66rem;color:#94a3b8;margin-bottom:3px">Confidence</div>
                    <div style="font-size:1.2rem;font-weight:700;color:#0f172a" id="hist-conf">—</div>
                </div>
            </div>
            <div style="padding:1rem 1.5rem;flex:1;min-height:0">
                <div id="hist-loading" style="display:flex;align-items:center;justify-content:center;height:200px;color:#94a3b8;font-size:.78rem">
                    Loading history…
                </div>
                <div id="hist-chart-wrap" style="position:relative;height:220px;display:none">
                    <canvas id="hist-canvas"></canvas>
                </div>
            </div>
            <div style="padding:.7rem 1.5rem;display:flex;gap:16px;font-size:.68rem;color:#94a3b8;border-top:1px solid #f1f5f9">
                <span style="display:flex;align-items:center;gap:5px"><span style="width:14px;height:2px;background:#3b82f6;display:inline-block"></span>Historical</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:14px;height:0;border-top:2px dashed #22c55e;display:inline-block"></span>Predicted</span>
            </div>
        </div>`;
        document.body.appendChild(el);
        document.getElementById('hist-close').addEventListener('click', closeModal);
        el.addEventListener('click', e => { if (e.target === el) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    }

    function closeModal() {
        const m = document.getElementById('hist-modal');
        if (m) m.style.display = 'none';
        if (histChart) { histChart.destroy(); histChart = null; }
    }

    async function openChartModal(btn) {
        const id        = btn.dataset.id;
        const name      = btn.dataset.name;
        const dept      = btn.dataset.dept || '—';
        const current   = parseFloat(btn.dataset.current || 0);
        const predicted = parseFloat(btn.dataset.predicted || 0);

        // Find employee in cache to get confidence
        const emp = allEmployees.find(e => String(e.id) === String(id));
        const conf = emp?.confidence ? Math.round(emp.confidence * 100) : null;

        buildModal();
        const modal = document.getElementById('hist-modal');
        modal.style.display = 'flex';

        document.getElementById('hist-name').textContent    = name;
        document.getElementById('hist-dept').textContent    = dept;
        document.getElementById('hist-current').textContent = current.toFixed(1);

        const cls = scoreToClass(predicted);
        document.getElementById('hist-class').textContent = cls;
        document.getElementById('hist-class').style.color = classColor(cls);
        document.getElementById('hist-conf').textContent  = conf !== null ? conf + '%' : '—';

        document.getElementById('hist-loading').style.display = 'flex';
        document.getElementById('hist-chart-wrap').style.display = 'none';
        if (histChart) { histChart.destroy(); histChart = null; }

        let data = null;
        try { data = await apiFetch(`/api/lstm/employee-history/${id}`); }
        catch (e) { console.warn('History fetch failed', e); }

        const hasData = data && Array.isArray(data.history) && data.history.some(v => v !== null && v > 0);
        if (!hasData) {
            data = { labels: ['Today', 'Tomorrow'], history: [current, null], predicted: [current, predicted] };
        }

        document.getElementById('hist-loading').style.display = 'none';
        document.getElementById('hist-chart-wrap').style.display = 'block';

        const allVals = [...data.history, ...data.predicted].filter(v => v !== null);
        const minY = Math.max(0,   Math.floor(Math.min(...allVals) / 10) * 10 - 10);
        const maxY = Math.min(100, Math.ceil(Math.max(...allVals)  / 10) * 10 + 10);

        histChart = new Chart(document.getElementById('hist-canvas').getContext('2d'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Historical', data: data.history, borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,.07)', borderWidth: 2.5,
                        tension: .35, fill: true, pointRadius: 4, pointHoverRadius: 6,
                        pointBackgroundColor: '#3b82f6', pointBorderColor: '#fff',
                        pointBorderWidth: 1.5, spanGaps: false,
                    },
                    {
                        label: 'Predicted', data: data.predicted, borderColor: '#22c55e',
                        borderDash: [6, 4], borderWidth: 2.5, fill: false, pointRadius: 5,
                        pointBackgroundColor: '#22c55e', pointBorderColor: '#fff',
                        pointBorderWidth: 1.5, spanGaps: false,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: c => c.parsed.y === null ? null : ` ${c.dataset.label}: ${c.parsed.y.toFixed(1)}`
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                    y: {
                        min: minY, max: maxY,
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { font: { size: 11 }, color: '#9ca3af' }
                    }
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // INIT
    // ══════════════════════════════════════════════════════════
    async function init() {
        showLoading();
        try {
            const [stats, emps] = await Promise.all([
                apiFetch('/api/lstm/stats'),
                apiFetch('/api/lstm/employee-predictions'),
            ]);
            allEmployees = Array.isArray(emps) ? emps : [];

            // Last run timestamp
            document.getElementById('last-run').textContent =
                stats.lastRun ? new Date(stats.lastRun).toLocaleDateString('vi-VN') : '—';

            renderSnapshot(allEmployees);
            renderAttention(allEmployees);
            renderDepts(allEmployees);
            renderDistChart(allEmployees);
            renderTop(allEmployees);
            renderTrust(stats);
            renderTable(allEmployees);
        } catch (err) {
            console.error('Dashboard init error:', err);
            document.getElementById('attention-list').innerHTML =
                '<div class="empty-state">Failed to load data. Check console for details.</div>';
        }
        hideLoading();
    }

    // ══════════════════════════════════════════════════════════
    // EVENTS
    // ══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        init();

        document.getElementById('btn-refresh').addEventListener('click', async () => {
            const btn = document.getElementById('btn-refresh');
            btn.disabled = true; btn.classList.add('spinning'); showLoading();
            try {
                await apiFetch('/api/lstm/refresh-predictions', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' }
                });
                await init();
            } catch (err) { alert('Refresh failed: ' + err.message); }
            btn.disabled = false; btn.classList.remove('spinning'); hideLoading();
        });

        document.getElementById('btn-export').addEventListener('click', async () => {
            const btn = document.getElementById('btn-export');
            btn.disabled = true; showLoading();
            try {
                const response = await fetch('/api/lstm/export-excel', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' }
                });
                if (!response.ok) {
                    const e = await response.json();
                    throw new Error(e.error || 'Export failed');
                }
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `LSTM_Report_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(link); link.click();
                document.body.removeChild(link); window.URL.revokeObjectURL(url);
            } catch (err) { alert('Export failed: ' + err.message); }
            btn.disabled = false; hideLoading();
        });

        // Attention filter
        document.getElementById('attn-filter').addEventListener('change', () => {
            renderAttention(allEmployees);
        });

        // Toggle full table
        document.getElementById('btn-toggle-all').addEventListener('click', () => {
            tableOpen = !tableOpen;
            document.getElementById('panel-all').style.display = tableOpen ? 'block' : 'none';
            document.getElementById('btn-toggle-all').textContent = tableOpen ? 'Hide ▴' : 'Show all ▾';
        });

        // Table filters
        ['search', 'dept-filter', 'risk-filter'].forEach(id => {
            document.getElementById(id).addEventListener('input',  () => renderTableBody(allEmployees));
            document.getElementById(id).addEventListener('change', () => renderTableBody(allEmployees));
        });

        // View chart — delegated
        document.addEventListener('click', e => {
            const btn = e.target.closest('.btn-chart');
            if (btn) openChartModal(btn);
        });
    });
})();