/**
 * lstm-dashboard.js
 * Wired to:
 *   GET  /api/lstm/stats
 *   GET  /api/lstm/employee-predictions
 *   GET  /api/lstm/employee-history/{id}
 *   POST /api/lstm/refresh-predictions
 */

(function () {
    'use strict';

    // ── State ────────────────────────────────────────
    let allEmployees  = [];
    let distChart     = null;
    let historyChart  = null;
    let tableExpanded = false;

    // ── Dept colour palette ───────────────────────────
    const DEPT_COLORS = [
        '#3b82f6','#22c55e','#a855f7','#f59e0b','#ef4444',
        '#06b6d4','#ec4899','#84cc16','#f97316','#6366f1',
    ];
    const deptColorMap = {};
    let colorIdx = 0;
    function deptColor(dept) {
        if (!deptColorMap[dept]) {
            deptColorMap[dept] = DEPT_COLORS[colorIdx++ % DEPT_COLORS.length];
        }
        return deptColorMap[dept];
    }

    // ── Avatar initials ───────────────────────────────
    function initials(name) {
        return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    }

    // ── Bar colour based on score ─────────────────────
    function barColor(score) {
        if (score >= 80) return '#22c55e';
        if (score >= 60) return '#3b82f6';
        if (score >= 40) return '#f59e0b';
        return '#ef4444';
    }

    // ── Risk label + pill class ───────────────────────
    function riskInfo(score) {
        if (score >= 80) return { label: 'Low',      cls: 'pill-ok'   };
        if (score >= 60) return { label: 'Medium',   cls: 'pill-med'  };
        if (score >= 40) return { label: 'High',     cls: 'pill-high' };
        return            { label: 'Critical', cls: 'pill-crit' };
    }

    function riskKey(score) {
        if (score >= 80) return 'low';
        if (score >= 60) return 'medium';
        if (score >= 40) return 'high';
        return 'critical';
    }

    // ── Trend HTML ────────────────────────────────────
    function trendHtml(trend) {
        const t = (trend || '').toLowerCase();
        if (t === 'up')   return '<span class="trend-up">▲ improving</span>';
        if (t === 'down') return '<span class="trend-down">▼ declining</span>';
        return '<span class="trend-flat">— stable</span>';
    }

    // ── Loading overlay ───────────────────────────────
    function showLoading()  { document.getElementById('loading-overlay').classList.remove('hidden'); }
    function hideLoading()  { document.getElementById('loading-overlay').classList.add('hidden');    }

    // ── CSRF helper ───────────────────────────────────
    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    // ═════════════════════════════════════════════════
    // FETCH helpers
    // ═════════════════════════════════════════════════
    async function fetchStats() {
        const res  = await fetch('/api/lstm/stats');
        return res.json();
    }

    async function fetchEmployees() {
        const res  = await fetch('/api/lstm/employee-predictions');
        return res.json();
    }

    async function fetchHistory(id) {
        const res  = await fetch(`/api/lstm/employee-history/${id}`);
        return res.json();
    }

    async function postRefresh() {
        const res = await fetch('/api/lstm/refresh-predictions', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' }
        });
        return res.json();
    }

    // ═════════════════════════════════════════════════
    // RENDER: metric strip
    // ═════════════════════════════════════════════════
    function renderStats(stats, employees) {
        // Last run
        document.getElementById('last-run').textContent =
            stats.lastRun ? new Date(stats.lastRun).toLocaleDateString('vi-VN') : '—';

        // Model accuracy
        document.getElementById('m-acc').textContent =
            stats.accuracy ? stats.accuracy.toFixed(1) + '%' : '—';

        // Compute from live employee data for accuracy
        if (employees.length) {
            const avg      = employees.reduce((s, e) => s + e.predictedScore, 0) / employees.length;
            const atRisk   = employees.filter(e => e.predictedScore < 60).length;
            const burnout  = employees.filter(e => e.trend === 'down').length;
            const high     = employees.filter(e => e.predictedScore >= 80).length;

            document.getElementById('m-avg').textContent     = avg.toFixed(1) + '%';
            document.getElementById('m-risk').textContent    = atRisk;
            document.getElementById('m-burnout').textContent = burnout;
            document.getElementById('m-high').textContent    = high;
        }
    }

    // ═════════════════════════════════════════════════
    // RENDER: attention table (employees predicted < 75%)
    // ═════════════════════════════════════════════════
    function renderAttention(employees) {
        const atRisk = employees
            .filter(e => e.predictedScore < 75)
            .sort((a, b) => a.predictedScore - b.predictedScore);

        document.getElementById('badge-atrisk').textContent =
            atRisk.length + ' employee' + (atRisk.length !== 1 ? 's' : '');

        const list = document.getElementById('attention-list');
        if (!atRisk.length) {
            list.innerHTML = '<div class="empty-state">No employees need attention right now.</div>';
            return;
        }

        list.innerHTML = atRisk.map(e => `
            <div class="emp-grid">
                <div>
                    <div class="emp-name">${e.name}</div>
                    <div class="emp-dept">${e.department}</div>
                </div>
                <div>
                    <div class="bar-bg"><div class="bar-fg" style="width:${Math.min(e.currentScore,100)}%;background:${barColor(e.currentScore)}"></div></div>
                    <div class="score-txt">${e.currentScore}%</div>
                </div>
                <div>
                    <div class="bar-bg"><div class="bar-fg" style="width:${Math.min(e.predictedScore,100)}%;background:${barColor(e.predictedScore)}"></div></div>
                    <div class="score-txt">${e.predictedScore}%</div>
                </div>
                <div>${trendHtml(e.trend)}</div>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════
    // RENDER: alerts panel
    // Generated dynamically from real employee data
    // ═════════════════════════════════════════════════
    function renderAlerts(employees) {
        const alerts = [];

        // Burnout: declining trend + predicted < 80
        const burnout = employees.filter(e => e.trend === 'down' && e.predictedScore < 80);
        burnout.slice(0, 2).forEach(e => {
            alerts.push({
                dot: 'dot-red', tag: 'tag-red', tagLabel: 'Burnout risk',
                name: `${e.name} — ${e.department}`,
                desc: `Productivity declining. Predicted ${e.predictedScore}%. Consider a 1-on-1 or workload review.`
            });
        });

        // Large gap: current vs predicted > 25pts
        const bigGap = employees
            .filter(e => e.trend !== 'down' && (e.predictedScore - e.currentScore) > 25)
            .sort((a, b) => (b.predictedScore - b.currentScore) - (a.predictedScore - a.currentScore));
        bigGap.slice(0, 2).forEach(e => {
            const gap = (e.predictedScore - e.currentScore).toFixed(0);
            alerts.push({
                dot: 'dot-amber', tag: 'tag-amber', tagLabel: 'Score gap',
                name: `${e.name} — ${e.department}`,
                desc: `Current ${e.currentScore}% vs predicted ${e.predictedScore}% (+${gap} pts). May lack recent task data.`
            });
        });

        // Very low current score
        const veryLow = employees
            .filter(e => e.currentScore < 30 && e.trend !== 'down')
            .sort((a, b) => a.currentScore - b.currentScore);
        veryLow.slice(0, 1).forEach(e => {
            alerts.push({
                dot: 'dot-amber', tag: 'tag-amber', tagLabel: 'Low current',
                name: `${e.name} — ${e.department}`,
                desc: `Current score ${e.currentScore}% is very low. May need task assignment or support.`
            });
        });

        // Excellent performers
        const top = employees.filter(e => e.predictedScore >= 88).slice(0, 1);
        top.forEach(e => {
            alerts.push({
                dot: 'dot-blue', tag: 'tag-blue', tagLabel: 'Top performer',
                name: `${e.name} — ${e.department}`,
                desc: `Predicted ${e.predictedScore}%. Consistently high — consider for mentoring or stretch projects.`
            });
        });

        const el = document.getElementById('alerts-list');
        if (!alerts.length) {
            el.innerHTML = '<div class="empty-state">No active alerts.</div>';
            return;
        }

        el.innerHTML = alerts.slice(0, 5).map(a => `
            <div class="alert-item">
                <div class="alert-dot ${a.dot}"></div>
                <div style="flex:1;min-width:0">
                    <div class="alert-name">${a.name}</div>
                    <div class="alert-desc">${a.desc}</div>
                </div>
                <span class="alert-tag ${a.tag}">${a.tagLabel}</span>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════
    // RENDER: department breakdown
    // ═════════════════════════════════════════════════
    function renderDepts(employees) {
        const deptMap = {};
        employees.forEach(e => {
            const d = e.department || 'Unknown';
            if (!deptMap[d]) deptMap[d] = { scores: [], count: 0 };
            deptMap[d].scores.push(e.predictedScore);
            deptMap[d].count++;
        });

        const depts = Object.entries(deptMap).map(([name, v]) => ({
            name,
            avg: v.scores.reduce((a, b) => a + b, 0) / v.scores.length,
            count: v.count,
            color: deptColor(name)
        })).sort((a, b) => b.avg - a.avg);

        const el = document.getElementById('dept-row');
        el.innerHTML = depts.map(d => `
            <div class="dept-card">
                <div class="dept-card-name">${d.name}</div>
                <div class="dept-card-score" style="color:${d.color}">${d.avg.toFixed(1)}%</div>
                <div class="dept-bar"><div class="dept-fill" style="width:${d.avg}%;background:${d.color}"></div></div>
                <div class="dept-meta">${d.count} employee${d.count !== 1 ? 's' : ''} · avg predicted</div>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════
    // RENDER: distribution chart
    // ═════════════════════════════════════════════════
    function renderDistChart(employees) {
        const buckets = [0, 0, 0, 0, 0, 0];
        employees.forEach(e => {
            const s = e.predictedScore;
            if (s < 50)       buckets[0]++;
            else if (s < 60)  buckets[1]++;
            else if (s < 70)  buckets[2]++;
            else if (s < 80)  buckets[3]++;
            else if (s < 90)  buckets[4]++;
            else              buckets[5]++;
        });

        if (distChart) distChart.destroy();
        distChart = new Chart(document.getElementById('dist-chart'), {
            type: 'bar',
            data: {
                labels: ['< 50%', '50–60%', '60–70%', '70–80%', '80–90%', '≥ 90%'],
                datasets: [{
                    data: buckets,
                    backgroundColor: ['#ef4444','#f97316','#f59e0b','#3b82f6','#22c55e','#16a34a'],
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} employee${ctx.parsed.y !== 1 ? 's' : ''}` } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#aaa' } },
                    y: {
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 11 }, color: '#aaa', stepSize: 1 },
                        title: { display: true, text: 'employees', font: { size: 11 }, color: '#bbb' }
                    }
                }
            }
        });
    }

    // ═════════════════════════════════════════════════
    // RENDER: insights
    // ═════════════════════════════════════════════════
    function renderInsights(employees) {
        const insights = [];

        // Best dept
        const deptMap = {};
        employees.forEach(e => {
            const d = e.department || 'Unknown';
            if (!deptMap[d]) deptMap[d] = [];
            deptMap[d].push(e.predictedScore);
        });
        const deptAvgs = Object.entries(deptMap)
            .map(([d, s]) => ({ dept: d, avg: s.reduce((a, b) => a + b, 0) / s.length }))
            .sort((a, b) => b.avg - a.avg);

        if (deptAvgs.length) {
            const best  = deptAvgs[0];
            const worst = deptAvgs[deptAvgs.length - 1];
            insights.push({ icon: '↑', cls: 'i-up',
                text: `<strong>${best.dept}</strong> leads with ${best.avg.toFixed(1)}% avg predicted productivity.` });
            if (deptAvgs.length > 1) {
                const lowCount = employees.filter(e => e.department === worst.dept && e.predictedScore < 75).length;
                insights.push({ icon: '↓', cls: 'i-down',
                    text: `<strong>${worst.dept}</strong> has the lowest avg at ${worst.avg.toFixed(1)}%. ${lowCount} employee${lowCount !== 1 ? 's' : ''} below 75%.` });
            }
        }

        // Burnout count
        const burnoutCount = employees.filter(e => e.trend === 'down').length;
        if (burnoutCount > 0) {
            insights.push({ icon: '!', cls: 'i-warn',
                text: `<strong>${burnoutCount} employee${burnoutCount !== 1 ? 's' : ''}</strong> show a declining trend — review in upcoming 1-on-1s.` });
        }

        // Gap insight
        const bigGap = employees.filter(e => (e.predictedScore - e.currentScore) > 25).length;
        if (bigGap > 0) {
            insights.push({ icon: '→', cls: 'i-info',
                text: `<strong>${bigGap} employee${bigGap !== 1 ? 's' : ''}</strong> have a large current–predicted gap. May need more task data coverage.` });
        }

        // High performers stable
        const stableHigh = employees.filter(e => e.predictedScore >= 85 && e.trend !== 'down').length;
        if (stableHigh > 0) {
            insights.push({ icon: '↑', cls: 'i-up',
                text: `<strong>${stableHigh} employee${stableHigh !== 1 ? 's' : ''}</strong> predicted above 85% with stable or improving trends.` });
        }

        const el = document.getElementById('insights-list');
        el.innerHTML = insights.slice(0, 5).map(i => `
            <div class="insight-item">
                <div class="insight-icon ${i.cls}">${i.icon}</div>
                <div class="insight-text">${i.text}</div>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════
    // RENDER: top performers list
    // ═════════════════════════════════════════════════
    function renderTop(employees) {
        const top = [...employees]
            .sort((a, b) => b.predictedScore - a.predictedScore)
            .slice(0, 6);

        document.getElementById('badge-top').textContent = top.length + ' employees';

        document.getElementById('top-list').innerHTML = top.map((e, i) => `
            <div class="top-item">
                <div class="top-rank">${i + 1}</div>
                <div class="top-avatar" style="background:${deptColor(e.department)}">${initials(e.name)}</div>
                <div class="top-info">
                    <div class="top-name" title="${e.name}">${e.name}</div>
                    <div class="top-dept">${e.department}</div>
                </div>
                <div class="top-score">${e.predictedScore}%</div>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════
    // RENDER: all-employees table
    // ═════════════════════════════════════════════════
    function renderTable(employees) {
        // Populate dept filter
        const depts = [...new Set(employees.map(e => e.department))].sort();
        const deptSel = document.getElementById('dept-filter');
        deptSel.innerHTML = '<option value="">All departments</option>' +
            depts.map(d => `<option value="${d}">${d}</option>`).join('');

        renderTableBody(employees);
    }

    function renderTableBody(employees) {
        const search     = (document.getElementById('search').value || '').toLowerCase();
        const deptFilter = document.getElementById('dept-filter').value;
        const riskFilter = document.getElementById('risk-filter').value;

        const filtered = employees.filter(e => {
            const matchSearch = !search ||
                e.name.toLowerCase().includes(search) ||
                (e.department || '').toLowerCase().includes(search);
            const matchDept = !deptFilter || e.department === deptFilter;
            const matchRisk = !riskFilter || riskKey(e.predictedScore) === riskFilter;
            return matchSearch && matchDept && matchRisk;
        });

        const tbody = document.getElementById('all-tbody');
        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#ccc;padding:2rem">No employees match these filters.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(e => {
            const risk = riskInfo(e.predictedScore);
            return `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:26px;height:26px;border-radius:50%;background:${deptColor(e.department)};
                            display:flex;align-items:center;justify-content:center;
                            font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0">${initials(e.name)}</div>
                        <span style="font-weight:600;color:#111">${e.name}</span>
                    </div>
                </td>
                <td style="color:#666">${e.department}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="width:50px;background:#f0f0f0;border-radius:3px;height:4px;overflow:hidden">
                            <div style="width:${Math.min(e.currentScore,100)}%;height:100%;background:${barColor(e.currentScore)};border-radius:3px"></div>
                        </div>
                        <span style="font-weight:600">${e.currentScore}%</span>
                    </div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="width:50px;background:#f0f0f0;border-radius:3px;height:4px;overflow:hidden">
                            <div style="width:${Math.min(e.predictedScore,100)}%;height:100%;background:${barColor(e.predictedScore)};border-radius:3px"></div>
                        </div>
                        <span style="font-weight:600">${e.predictedScore}%</span>
                    </div>
                </td>
                <td>${trendHtml(e.trend)}</td>
                <td><span class="risk-pill ${risk.cls}">${risk.label}</span></td>
                <td>
                    <button class="btn-chart" data-id="${e.id}" data-name="${e.name}">
                        Chart
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    // ═════════════════════════════════════════════════
    // RENDER: history chart modal
    // ═════════════════════════════════════════════════
    async function openChartModal(id, name) {
        document.getElementById('modal-name').textContent = name;
        document.getElementById('chart-modal').style.display = 'flex';

        if (historyChart) { historyChart.destroy(); historyChart = null; }

        let data;
        try {
            data = await fetchHistory(id);
        } catch {
            data = {
                labels:    ['Week -5','Week -4','Week -3','Week -2','Week -1','Current','Predicted'],
                history:   [null, null, null, null, null, 50, null],
                predicted: [null, null, null, null, null, 50, 75]
            };
        }

        const ctx = document.getElementById('history-chart').getContext('2d');
        historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Historical',
                        data: data.history,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,.08)',
                        borderWidth: 2.5,
                        tension: .35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#3b82f6',
                        spanGaps: false
                    },
                    {
                        label: 'LSTM Prediction',
                        data: data.predicted,
                        borderColor: '#22c55e',
                        borderDash: [6, 4],
                        borderWidth: 2.5,
                        pointBackgroundColor: '#22c55e',
                        pointRadius: 5,
                        fill: false,
                        spanGaps: false
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top',
                        labels: { font: { size: 11 }, boxWidth: 12, padding: 16 } },
                    tooltip: {
                        mode: 'index', intersect: false,
                        callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y != null ? ctx.parsed.y.toFixed(1) + '%' : '—'}` }
                    }
                },
                scales: {
                    y: {
                        min: 0, max: 100,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 11 }, color: '#aaa', callback: v => v + '%' },
                        title: { display: true, text: 'Productivity (%)', font: { size: 11 }, color: '#bbb' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#aaa' }
                    }
                }
            }
        });
    }

    // ═════════════════════════════════════════════════
    // MAIN INIT
    // ═════════════════════════════════════════════════
    async function init() {
        showLoading();

        try {
            const [stats, employees] = await Promise.all([fetchStats(), fetchEmployees()]);
            allEmployees = Array.isArray(employees) ? employees : [];

            renderStats(stats, allEmployees);
            renderAttention(allEmployees);
            renderAlerts(allEmployees);
            renderDepts(allEmployees);
            renderDistChart(allEmployees);
            renderInsights(allEmployees);
            renderTop(allEmployees);
            renderTable(allEmployees);

        } catch (err) {
            console.error('Dashboard init error:', err);
        }

        hideLoading();
    }

    // ═════════════════════════════════════════════════
    // EVENT BINDINGS
    // ═════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        init();

        // Refresh button
        document.getElementById('btn-refresh').addEventListener('click', async () => {
            const btn = document.getElementById('btn-refresh');
            btn.disabled = true;
            btn.classList.add('spinning');
            showLoading();

            try {
                await postRefresh();
                await init();
            } catch (err) {
                alert('Refresh failed: ' + err.message);
            }

            btn.disabled = false;
            btn.classList.remove('spinning');
            hideLoading();
        });

        // Toggle all-employees panel
        document.getElementById('btn-toggle-all').addEventListener('click', () => {
            tableExpanded = !tableExpanded;
            document.getElementById('panel-all').style.display = tableExpanded ? 'block' : 'none';
            document.getElementById('btn-toggle-all').textContent = tableExpanded ? 'Hide ▴' : 'Show all ▾';
        });

        // Table filters
        ['search','dept-filter','risk-filter'].forEach(id => {
            document.getElementById(id).addEventListener('input', () => renderTableBody(allEmployees));
            document.getElementById(id).addEventListener('change', () => renderTableBody(allEmployees));
        });

        // Chart button (delegated — works for both table and future cards)
        document.addEventListener('click', e => {
            const btn = e.target.closest('.btn-chart');
            if (btn) openChartModal(btn.dataset.id, btn.dataset.name);
        });

        // Close modal
        document.getElementById('btn-close-modal').addEventListener('click', () => {
            document.getElementById('chart-modal').style.display = 'none';
        });
        document.getElementById('chart-modal').addEventListener('click', e => {
            if (e.target === document.getElementById('chart-modal')) {
                document.getElementById('chart-modal').style.display = 'none';
            }
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') document.getElementById('chart-modal').style.display = 'none';
        });
    });

})();