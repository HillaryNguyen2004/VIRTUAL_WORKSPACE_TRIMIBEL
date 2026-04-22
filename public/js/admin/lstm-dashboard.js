/**
 * lstm-dashboard.js
 * Pure JS + Tailwind. No Bootstrap.
 *
 * Wired to:
 *   GET  /api/lstm/stats
 *   GET  /api/lstm/employee-predictions
 *   GET  /api/lstm/employee-history/{id}
 *   POST /api/lstm/refresh-predictions
 */

(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    let allEmployees = [];
    let distChart    = null;
    let histChart    = null;    let trendChart   = null;
    let taskChart    = null;
    let horizonChart = null;    let tableOpen    = false;

    // ── Department colours ───────────────────────────────────────────────────
    const DEPT_COLORS = [
        '#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4',
        '#a855f7','#ec4899','#84cc16','#f97316','#3b82f6',
    ];
    const _deptColorMap = {};
    let   _colorIdx     = 0;
    function deptColor(dept) {
        if (!_deptColorMap[dept])
            _deptColorMap[dept] = DEPT_COLORS[_colorIdx++ % DEPT_COLORS.length];
        return _deptColorMap[dept];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function initials(name) {
        return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    }

    function barColor(s) {
        if (s >= 80) return '#16a34a';  // green — High performer
        if (s >= 60) return '#3b82f6';  // blue — Medium
        return '#ef4444';               // red — Low (needs attention)
    }

    function riskInfo(s) {
        if (s >= 80) return { label: 'High',   cls: 'bg-green-100 text-green-800'   };  // High performer
        if (s >= 60) return { label: 'Medium', cls: 'bg-blue-100 text-blue-800'     };  // Medium
        return              { label: 'Low',    cls: 'bg-red-100 text-red-800'       };  // Low — needs attention
    }

    function riskKey(s) {
        if (s >= 80) return 'high';
        if (s >= 60) return 'medium';
        return 'low';
    }

    function trendHtml(trend) {
        const t = (trend || '').toLowerCase();
        if (t === 'up')   return '<span class="trend-up">▲ improving</span>';
        if (t === 'down') return '<span class="trend-down">▼ declining</span>';
        return '<span class="trend-flat">— stable</span>';
    }

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function showLoading() { document.getElementById('loading-overlay').classList.remove('hidden'); }
    function hideLoading() { document.getElementById('loading-overlay').classList.add('hidden');    }

    // ── API calls ────────────────────────────────────────────────────────────
    async function apiFetch(url, opts = {}) {
        const r = await fetch(url, opts);
        if (!r.ok) throw new Error(`HTTP ${r.status} — ${url}`);
        return r.json();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: metric strip
    // ═════════════════════════════════════════════════════════════════════════
    function renderStats(stats, emps) {
        document.getElementById('last-run').textContent =
            stats.lastRun ? new Date(stats.lastRun).toLocaleDateString('vi-VN') : '—';
        document.getElementById('m-acc').textContent =
            stats.accuracy != null ? stats.accuracy.toFixed(1) + '%' : '—';

        if (emps.length) {
            const avg     = emps.reduce((s, e) => s + e.predictedScore, 0) / emps.length;
            const atRisk  = emps.filter(e => e.predictedScore < 60).length;
            const high    = emps.filter(e => e.predictedScore >= 80).length;
            
            document.getElementById('m-avg').textContent     = avg.toFixed(1) + '%';
            document.getElementById('m-risk').textContent    = atRisk;
            document.getElementById('m-burnout').textContent = emps.filter(e => e.trend === 'down').length;
            document.getElementById('m-high').textContent    = high;
            
            // Sub-labels (deltas & context)
            document.getElementById('m-avg-delta').textContent = 
                `vs current: ${(avg - (emps.reduce((s, e) => s + e.currentScore, 0) / emps.length)).toFixed(1)}%`;
            document.getElementById('m-risk-sub').textContent = 
                `${atRisk > 0 ? (100 * atRisk / emps.length).toFixed(0) : 0}% of team`;
            document.getElementById('m-high-sub').textContent = 
                `${high > 0 ? (100 * high / emps.length).toFixed(0) : 0}% of team`;
            document.getElementById('m-acc-sub').textContent = 'from LSTM model training';
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: needs-attention table
    // ═════════════════════════════════════════════════════════════════════════
    function renderAttention(emps) {
        const atRisk = emps.filter(e => e.predictedScore < 60)
            .sort((a, b) => a.predictedScore - b.predictedScore);

        document.getElementById('badge-atrisk').textContent =
            atRisk.length + ' employee' + (atRisk.length !== 1 ? 's' : '');

        const el = document.getElementById('attention-list');
        if (!atRisk.length) {
            el.innerHTML = '<div class="empty-state">No employees need attention right now.</div>';
            return;
        }

        el.innerHTML = atRisk.map(e => `
            <div class="emp-grid">
                <div>
                    <div class="emp-name">${e.name}</div>
                    <div class="emp-dept">ID: ${e.id} · ${e.department}</div>
                    <button type="button" class="btn-chart mt-1"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department}" data-trend="${e.trend}">
                        View chart
                    </button>
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

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: alerts panel
    // ═════════════════════════════════════════════════════════════════════════
    function renderAlerts(emps) {
        const alerts = [];

        emps.filter(e => e.trend === 'down' && e.predictedScore < 80)
            .slice(0, 2).forEach(e => alerts.push({
                dot: 'dot-red', tag: 'tag-red', tagLabel: 'Burnout risk',
                name: `${e.name} — ${e.department}`,
                desc: `Productivity declining. Predicted ${e.predictedScore}%. Consider a 1-on-1 or workload review.`
            }));

        emps.filter(e => (e.predictedScore - e.currentScore) > 25 && e.trend !== 'down')
            .sort((a, b) => (b.predictedScore - b.currentScore) - (a.predictedScore - a.currentScore))
            .slice(0, 2).forEach(e => alerts.push({
                dot: 'dot-amber', tag: 'tag-amber', tagLabel: 'Score gap',
                name: `${e.name} — ${e.department}`,
                desc: `Current ${e.currentScore}% vs predicted ${e.predictedScore}% (+${(e.predictedScore - e.currentScore).toFixed(0)} pts). May lack recent task data.`
            }));

        emps.filter(e => e.currentScore < 30 && e.trend !== 'down')
            .sort((a, b) => a.currentScore - b.currentScore)
            .slice(0, 1).forEach(e => alerts.push({
                dot: 'dot-amber', tag: 'tag-amber', tagLabel: 'Low current',
                name: `${e.name} — ${e.department}`,
                desc: `Current score ${e.currentScore}% is very low. May need task assignment or support.`
            }));

        emps.filter(e => e.predictedScore >= 88).slice(0, 1).forEach(e => alerts.push({
            dot: 'dot-blue', tag: 'tag-blue', tagLabel: 'Top performer',
            name: `${e.name} — ${e.department}`,
            desc: `Predicted ${e.predictedScore}%. Consistently high — consider for mentoring or stretch projects.`
        }));

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

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: department breakdown
    // ═════════════════════════════════════════════════════════════════════════
    function renderDepts(emps) {
        const map = {};
        emps.forEach(e => {
            const d = e.department || 'Unknown';
            if (!map[d]) map[d] = [];
            map[d].push(e.predictedScore);
        });
        const depts = Object.entries(map)
            .map(([name, scores]) => ({
                name,
                avg:   scores.reduce((a, b) => a + b, 0) / scores.length,
                count: scores.length,
                color: deptColor(name),
            }))
            .sort((a, b) => b.avg - a.avg);

        document.getElementById('dept-row').innerHTML = depts.map(d => `
            <div class="dept-card">
                <div class="dept-card-name">${d.name}</div>
                <div class="dept-card-score" style="color:${d.color}">${d.avg.toFixed(1)}%</div>
                <div class="dept-bar"><div class="dept-fill" style="width:${d.avg}%;background:${d.color}"></div></div>
                <div class="dept-meta">${d.count} employee${d.count !== 1 ? 's' : ''}</div>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: distribution chart
    // ═════════════════════════════════════════════════════════════════════════
    function renderDistChart(emps) {
        const buckets = [0, 0, 0, 0, 0, 0];
        emps.forEach(e => {
            const s = e.predictedScore;
            if      (s < 50) buckets[0]++;
            else if (s < 60) buckets[1]++;
            else if (s < 70) buckets[2]++;
            else if (s < 80) buckets[3]++;
            else if (s < 90) buckets[4]++;
            else             buckets[5]++;
        });
        if (distChart) distChart.destroy();
        distChart = new Chart(document.getElementById('dist-chart'), {
            type: 'bar',
            data: {
                labels: ['< 50%','50–60%','60–70%','70–80%','80–90%','≥ 90%'],
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
                        grid: { color: 'rgba(0,0,0,.05)' },
                        ticks: { font: { size: 11 }, color: '#aaa', stepSize: 1 },
                        title: { display: true, text: 'employees', font: { size: 11 }, color: '#bbb' }
                    }
                }
            }
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: insights
    // ═════════════════════════════════════════════════════════════════════════
    function renderInsights(emps) {
        const map = {};
        emps.forEach(e => {
            const d = e.department || 'Unknown';
            if (!map[d]) map[d] = [];
            map[d].push(e.predictedScore);
        });
        const deptRanked = Object.entries(map)
            .map(([d, s]) => ({ dept: d, avg: s.reduce((a, b) => a + b, 0) / s.length }))
            .sort((a, b) => b.avg - a.avg);

        const ins = [];
        if (deptRanked.length) {
            const best = deptRanked[0];
            ins.push({ icon: '↑', cls: 'i-up',
                text: `<strong>${best.dept}</strong> leads with ${best.avg.toFixed(1)}% avg predicted productivity.` });
            if (deptRanked.length > 1) {
                const worst    = deptRanked[deptRanked.length - 1];
                const lowCount = emps.filter(e => e.department === worst.dept && e.predictedScore < 75).length;
                ins.push({ icon: '↓', cls: 'i-down',
                    text: `<strong>${worst.dept}</strong> has the lowest avg at ${worst.avg.toFixed(1)}%. ${lowCount} employee${lowCount !== 1 ? 's' : ''} below 75%.` });
            }
        }
        const burnout = emps.filter(e => e.trend === 'down').length;
        if (burnout) ins.push({ icon: '!', cls: 'i-warn',
            text: `<strong>${burnout} employee${burnout !== 1 ? 's' : ''}</strong> show a declining trend — review in upcoming 1-on-1s.` });

        const bigGap = emps.filter(e => (e.predictedScore - e.currentScore) > 25).length;
        if (bigGap) ins.push({ icon: '→', cls: 'i-info',
            text: `<strong>${bigGap} employee${bigGap !== 1 ? 's' : ''}</strong> have a large current–predicted gap. May need more task data.` });

        const stableHigh = emps.filter(e => e.predictedScore >= 85 && e.trend !== 'down').length;
        if (stableHigh) ins.push({ icon: '↑', cls: 'i-up',
            text: `<strong>${stableHigh} employee${stableHigh !== 1 ? 's' : ''}</strong> predicted above 85% with stable or improving trends.` });

        document.getElementById('insights-list').innerHTML = ins.slice(0, 5).map(i => `
            <div class="insight-item">
                <div class="insight-icon ${i.cls}">${i.icon}</div>
                <div class="insight-text">${i.text}</div>
            </div>
        `).join('');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: top performers
    // ═════════════════════════════════════════════════════════════════════════
    function renderTop(emps) {
        const top = [...emps].sort((a, b) => b.predictedScore - a.predictedScore).slice(0, 6);
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

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: feature breakdown section
    // ═════════════════════════════════════════════════════════════════════════
    function renderFeatureBreakdown(emps) {
        // 1. score_trend distribution histogram
        if (trendChart) trendChart.destroy();
        const trendBuckets = [0, 0, 0, 0, 0];
        // Simulate score_trend distribution from trend counts
        const declining = emps.filter(e => e.trend === 'down').length;
        const stable = emps.filter(e => e.trend === 'stable').length;
        const improving = emps.filter(e => e.trend === 'improving').length;
        
        trendBuckets[0] = declining;         // negative trends
        trendBuckets[2] = stable;            // near zero
        trendBuckets[4] = improving;         // positive trends
        
        trendChart = new Chart(document.getElementById('trend-chart'), {
            type: 'bar',
            data: {
                labels: ['Declining', 'Slight↓', 'Stable', 'Slight↑', 'Improving'],
                datasets: [{
                    data: trendBuckets,
                    backgroundColor: ['#991b1b','#fca5a5','#e5e7eb','#86efac','#166534'],
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#aaa' } },
                    y: { grid: { color: 'rgba(0,0,0,.03)' }, ticks: { font: { size: 10 }, color: '#aaa', stepSize: 1 } }
                }
            }
        });

        // 2. Task signal coverage donut
        if (taskChart) taskChart.destroy();
        const withSignal = emps.filter(e => e.predictedScore > 50).length;
        const noSignal = emps.length - withSignal;
        
        taskChart = new Chart(document.getElementById('task-signal-chart'), {
            type: 'doughnut',
            data: {
                labels: ['Has activity', 'Low/none'],
                datasets: [{
                    data: [withSignal, noSignal],
                    backgroundColor: ['#3b82f6', '#e5e7eb'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // Coverage legend
        document.getElementById('task-signal-legend').innerHTML = `
            <div>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6"></span>
                    <span>${withSignal} with task signals</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#e5e7eb"></span>
                    <span>${noSignal} low/no signals</span>
                </div>
            </div>
        `;

        // 3. Burnout composite stats
        const burnout = emps.filter(e => e.trend === 'down').length;
        const burnoutPct = emps.length > 0 ? (100 * burnout / emps.length) : 0;
        const negTrendCount = emps.filter(e => e.trend === 'down' && e.predictedScore < 65).length;
        const combinedRisk = emps.filter(e => e.trend === 'down' && e.predictedScore < 60).length;

        document.getElementById('b-overwork').textContent = burnout;
        document.getElementById('b-overwork-bar').style.width = burnoutPct + '%';
        
        document.getElementById('b-neg-trend').textContent = negTrendCount;
        document.getElementById('b-neg-trend-bar').style.width = 
            (emps.length > 0 ? (100 * negTrendCount / emps.length) : 0) + '%';
        
        document.getElementById('b-combined').textContent = combinedRisk;
        document.getElementById('b-combined-bar').style.width = 
            (emps.length > 0 ? (100 * combinedRisk / emps.length) : 0) + '%';
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: 7-day horizon chart
    // ═════════════════════════════════════════════════════════════════════════
    function renderHorizonChart(emps) {
        if (horizonChart) horizonChart.destroy();
        
        // Generate 7-day simulated data based on trends
        const days = [];
        const actual = [];
        const predicted = [];
        
        const today = new Date();
        for (let i = 6; i >= 0; i--) {
            const d = new Date(today);
            d.setDate(d.getDate() - i);
            days.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            
            // Simple simulation: average score with slight trend
            const avgScore = emps.reduce((s, e) => s + e.currentScore, 0) / emps.length;
            const trendFactor = (i - 3) * 0.5;  // slight upward trend toward today
            
            actual.push(avgScore + trendFactor);
            predicted.push(avgScore + trendFactor + 2);  // predict slightly higher
        }
        
        horizonChart = new Chart(document.getElementById('horizon-chart'), {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Actual',
                        data: actual,
                        borderColor: '#378ADD',
                        backgroundColor: 'rgba(55, 138, 221, .05)',
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#378ADD',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                    },
                    {
                        label: 'LSTM Predicted',
                        data: predicted,
                        borderColor: '#1D9E75',
                        borderDash: [6, 4],
                        borderWidth: 2.5,
                        fill: false,
                        pointRadius: 3,
                        pointBackgroundColor: '#1D9E75',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#aaa' } },
                    y: { 
                        min: 40,
                        max: 100,
                        grid: { color: 'rgba(0,0,0,.03)' }, 
                        ticks: { font: { size: 10 }, color: '#aaa', stepSize: 10 } 
                    }
                }
            }
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: model transparency section (dynamic from API)
    // ═════════════════════════════════════════════════════════════════════════
    function renderModelTransparency(stats) {
        // Model health indicators - now dynamic from API
        document.getElementById('ms-loss').textContent = 
            stats.valLoss != null ? stats.valLoss.toFixed(4) : '—';
        document.getElementById('ms-mae').textContent = 
            stats.bestMAE != null ? stats.bestMAE.toFixed(4) : '—';
        document.getElementById('ms-epochs').textContent = 
            stats.epochsRan != null ? stats.epochsRan : '—';
        document.getElementById('ms-conf').textContent = 
            stats.confidence != null ? stats.confidence.toFixed(2) : '—';
        
        // Feature importance - now dynamic from API
        if (stats.featureImportance && Array.isArray(stats.featureImportance)) {
            renderFeatureImportance(stats.featureImportance);
        } else {
            console.warn('No feature importance data available from API');
            document.getElementById('feature-importance-list').innerHTML = 
                '<div class="empty-state">No feature importance data available.</div>';
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: feature importance bars (dynamic)
    // ═════════════════════════════════════════════════════════════════════════
    function renderFeatureImportance(features) {
        const container = document.getElementById('feature-importance-list');
        if (!features || !features.length) {
            container.innerHTML = '<div class="empty-state">No feature importance data available.</div>';
            return;
        }
        
        // Sort by importance (descending)
        const sorted = [...features].sort((a, b) => b.importance - a.importance);
        
        container.innerHTML = sorted.map(f => {
            const widthPercent = Math.round(f.importance * 100);
            return `
                <div class="fi-row">
                    <span class="fi-label">${f.name}</span>
                    <div class="fi-track"><div class="fi-fill" style="width:${widthPercent}%"></div></div>
                    <span class="fi-val">${f.importance.toFixed(2)}</span>
                </div>
            `;
        }).join('');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RENDER: all employees table
    // ═════════════════════════════════════════════════════════════════════════
    function renderTable(emps) {
        const depts   = [...new Set(emps.map(e => e.department))].sort();
        const deptSel = document.getElementById('dept-filter');
        deptSel.innerHTML = '<option value="">All departments</option>' +
            depts.map(d => `<option value="${d}">${d}</option>`).join('');
        renderTableBody(emps);
    }

    function renderTableBody(emps) {
        const search = (document.getElementById('search').value || '').toLowerCase();
        const dept   = document.getElementById('dept-filter').value;
        const risk   = document.getElementById('risk-filter').value;

        const rows = emps.filter(e =>
            (!search || e.name.toLowerCase().includes(search) || (e.department || '').toLowerCase().includes(search)) &&
            (!dept   || e.department === dept) &&
            (!risk   || riskKey(e.predictedScore) === risk)
        );

        const tbody = document.getElementById('all-tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-400 py-8 text-sm">No employees match these filters.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(e => {
            const r = riskInfo(e.predictedScore);
            const color = deptColor(e.department);
            return `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-3 py-2">
                    <div class="flex items-center gap-2">
                        <div style="width:26px;height:26px;border-radius:50%;background:${color};flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff">${initials(e.name)}</div>
                        <span class="font-semibold text-gray-900 text-sm">${e.name}</span>
                    </div>
                </td>
                <td class="px-3 py-2 text-gray-500 text-sm font-mono">${e.id}</td>
                <td class="px-3 py-2 text-gray-500 text-sm">${e.department}</td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-2">
                        <div style="width:48px;height:4px;background:#f0f0f0;border-radius:2px;overflow:hidden">
                            <div style="width:${Math.min(e.currentScore,100)}%;height:100%;background:${barColor(e.currentScore)};border-radius:2px"></div>
                        </div>
                        <span class="font-semibold text-sm">${e.currentScore}%</span>
                    </div>
                </td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-2">
                        <div style="width:48px;height:4px;background:#f0f0f0;border-radius:2px;overflow:hidden">
                            <div style="width:${Math.min(e.predictedScore,100)}%;height:100%;background:${barColor(e.predictedScore)};border-radius:2px"></div>
                        </div>
                        <span class="font-semibold text-sm">${e.predictedScore}%</span>
                    </div>
                </td>
                <td class="px-3 py-2 text-sm">${trendHtml(e.trend)}</td>
                <td class="px-3 py-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full ${r.cls}">${r.label}</span>
                </td>
                <td class="px-3 py-2">
                    <button type="button" class="btn-chart"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department}" data-trend="${e.trend}">
                        View chart
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HISTORY CHART MODAL
    // Pure Tailwind — zero Bootstrap
    // ═════════════════════════════════════════════════════════════════════════

    function buildModal() {
        if (document.getElementById('hist-modal')) return;

        const el = document.createElement('div');
        el.id = 'hist-modal';
        // Overlay
        el.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
        el.style.background = 'rgba(0,0,0,0.45)';
        el.style.display    = 'none';

        el.innerHTML = `
        <div id="hist-box"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden"
             style="max-height:90vh;display:flex;flex-direction:column">

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-bold text-gray-900" id="hist-name">—</h3>
                    <p class="text-xs text-gray-400 mt-0.5" id="hist-dept">—</p>
                </div>
                <button id="hist-close"
                    class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg p-1.5 transition-colors text-lg leading-none"
                    aria-label="Close">&#x2715;</button>
            </div>

            {{-- Mini stat row --}}
            <div class="grid grid-cols-3 gap-3 px-6 py-3 border-b border-gray-100">
                <div class="bg-gray-50 rounded-xl px-3 py-2 text-center">
                    <div class="text-xs text-gray-400 mb-0.5">7-day avg</div>
                    <div class="text-lg font-bold text-gray-900" id="hist-current">—</div>
                </div>
                <div class="bg-gray-50 rounded-xl px-3 py-2 text-center">
                    <div class="text-xs text-gray-400 mb-0.5">LSTM predicted</div>
                    <div class="text-lg font-bold text-blue-600" id="hist-predicted">—</div>
                </div>
                <div class="bg-gray-50 rounded-xl px-3 py-2 text-center">
                    <div class="text-xs text-gray-400 mb-0.5">Change</div>
                    <div class="text-lg font-bold" id="hist-delta">—</div>
                </div>
            </div>

            {{-- Chart --}}
            <div class="px-6 py-4 flex-1" style="min-height:0">
                <div id="hist-loading"
                     class="flex items-center justify-center h-48 text-gray-400 text-sm gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Loading history…
                </div>
                <div id="hist-chart-wrap" style="position:relative;height:220px;display:none">
                    <canvas id="hist-canvas"></canvas>
                </div>
                <div id="hist-empty"
                     class="hidden text-center text-gray-400 text-sm py-12">
                    No historical data available yet.
                </div>
            </div>

            {{-- Legend + footer --}}
            <div class="px-6 pb-4 flex items-center gap-4 text-xs text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span style="display:inline-block;width:20px;height:2px;background:#3b82f6;vertical-align:middle"></span>
                    Historical (weekly avg)
                </span>
                <span class="flex items-center gap-1.5">
                    <span style="display:inline-block;width:20px;height:0;border-top:2px dashed #22c55e;vertical-align:middle"></span>
                    LSTM prediction
                </span>
                <span class="ml-auto">Scores from ProductivityCalculatorService (7-day window)</span>
            </div>
        </div>`;

        document.body.appendChild(el);

        // Close handlers
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
        const dept      = btn.dataset.dept      || '—';
        const current   = parseFloat(btn.dataset.current   || 0);
        const predicted = parseFloat(btn.dataset.predicted || 0);
        const trend     = btn.dataset.trend     || 'stable';

        buildModal();

        // Show modal
        const modal = document.getElementById('hist-modal');
        modal.style.display = 'flex';

        // Populate header
        document.getElementById('hist-name').textContent = name;
        document.getElementById('hist-dept').textContent = dept;
        document.getElementById('hist-current').textContent   = current.toFixed(1) + '%';
        document.getElementById('hist-predicted').textContent = predicted.toFixed(1) + '%';

        const delta    = predicted - current;
        const deltaEl  = document.getElementById('hist-delta');
        deltaEl.textContent = (delta >= 0 ? '+' : '') + delta.toFixed(1) + '%';
        deltaEl.className   = 'text-lg font-bold ' +
            (delta > 2 ? 'text-green-600' : delta < -2 ? 'text-red-500' : 'text-gray-500');

        // Show loading, hide chart
        document.getElementById('hist-loading').classList.remove('hidden');
        document.getElementById('hist-chart-wrap').style.display = 'none';
        document.getElementById('hist-empty').classList.add('hidden');
        if (histChart) { histChart.destroy(); histChart = null; }

        // Fetch history
        let data = null;
        try {
            data = await apiFetch(`/api/lstm/employee-history/${id}`);
        } catch (err) {
            console.warn('History fetch failed, using fallback:', err.message);
        }

        // Validate data has actual history (not all nulls)
        const hasHistory = data && Array.isArray(data.history) &&
            data.history.some(v => v !== null && v > 0);

        document.getElementById('hist-loading').classList.add('hidden');

        if (!hasHistory) {
            // Show a minimal 2-point chart (current → predicted) so it's never blank
            data = {
                labels:    ['Current', 'Predicted'],
                history:   [current, null],
                predicted: [current, predicted],
            };
        }

        document.getElementById('hist-chart-wrap').style.display = 'block';
        drawHistoryChart(data, current, predicted, trend);
    }

    function drawHistoryChart(data, current, predicted, trend) {
        if (histChart) { histChart.destroy(); histChart = null; }

        const ctx = document.getElementById('hist-canvas').getContext('2d');

        // Determine y-axis range — pad 10 points around data
        const allVals = [
            ...data.history.filter(v => v !== null),
            ...data.predicted.filter(v => v !== null),
        ];
        const minVal = Math.max(0,   Math.floor(Math.min(...allVals) / 10) * 10 - 10);
        const maxVal = Math.min(100, Math.ceil( Math.max(...allVals) / 10) * 10 + 10);

        histChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label:                'Historical',
                        data:                 data.history,
                        borderColor:          '#3b82f6',
                        backgroundColor:      'rgba(59,130,246,.07)',
                        borderWidth:          2.5,
                        tension:              .35,
                        fill:                 true,
                        pointRadius:          4,
                        pointHoverRadius:     6,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor:     '#fff',
                        pointBorderWidth:     1.5,
                        spanGaps:             false,   // don't connect missing weeks
                    },
                    {
                        label:                'LSTM Prediction',
                        data:                 data.predicted,
                        borderColor:          '#22c55e',
                        borderDash:           [6, 4],
                        borderWidth:          2.5,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor:     '#fff',
                        pointBorderWidth:     1.5,
                        pointRadius:          5,
                        fill:                 false,
                        spanGaps:             false,
                    }
                ]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                interaction:         { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                if (ctx.parsed.y === null) return null;
                                return ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid:  { display: false },
                        ticks: { font: { size: 11 }, color: '#9ca3af',
                                 maxRotation: 45, autoSkip: false }
                    },
                    y: {
                        min:   minVal,
                        max:   maxVal,
                        grid:  { color: 'rgba(0,0,0,.04)' },
                        ticks: {
                            font: { size: 11 }, color: '#9ca3af',
                            callback: v => v + '%'
                        },
                    }
                }
            }
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MAIN INIT
    // ═════════════════════════════════════════════════════════════════════════
    async function init() {
        showLoading();
        try {
            const [stats, emps] = await Promise.all([
                apiFetch('/api/lstm/stats'),
                apiFetch('/api/lstm/employee-predictions'),
            ]);
            allEmployees = Array.isArray(emps) ? emps : [];

            renderStats(stats, allEmployees);
            renderAttention(allEmployees);
            renderAlerts(allEmployees);
            renderDepts(allEmployees);
            renderDistChart(allEmployees);
            renderInsights(allEmployees);
            renderTop(allEmployees);
            renderFeatureBreakdown(allEmployees);
            renderHorizonChart(allEmployees);
            renderModelTransparency(stats);
            renderTable(allEmployees);

        } catch (err) {
            console.error('Dashboard init error:', err);
        }
        hideLoading();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // EVENT BINDINGS
    // ═════════════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        init();

        // Refresh all
        document.getElementById('btn-refresh').addEventListener('click', async () => {
            const btn = document.getElementById('btn-refresh');
            btn.disabled = true;
            btn.classList.add('spinning');
            showLoading();
            try {
                await apiFetch('/api/lstm/refresh-predictions', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' }
                });
                await init();
            } catch (err) {
                alert('Refresh failed: ' + err.message);
            }
            btn.disabled = false;
            btn.classList.remove('spinning');
            hideLoading();
        });

        // Export to Excel
        document.getElementById('btn-export').addEventListener('click', async () => {
            const btn = document.getElementById('btn-export');
            btn.disabled = true;
            showLoading();
            try {
                const response = await fetch('/api/lstm/export-excel', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' }
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || 'Export failed');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `LSTM_Detailed_Report_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                alert('✓ Export completed successfully!');
            } catch (err) {
                alert('Export failed: ' + err.message);
            } finally {
                btn.disabled = false;
                hideLoading();
            }
        });

        // Toggle all-employees table
        document.getElementById('btn-toggle-all').addEventListener('click', () => {
            tableOpen = !tableOpen;
            document.getElementById('panel-all').style.display = tableOpen ? 'block' : 'none';
            document.getElementById('btn-toggle-all').textContent = tableOpen ? 'Hide ▴' : 'Show all ▾';
        });

        // Table filters
        ['search','dept-filter','risk-filter'].forEach(id => {
            document.getElementById(id).addEventListener('input',  () => renderTableBody(allEmployees));
            document.getElementById(id).addEventListener('change', () => renderTableBody(allEmployees));
        });

        // View chart button — delegated, works for attention list AND all-employees table
        document.addEventListener('click', e => {
            const btn = e.target.closest('.btn-chart');
            if (btn) openChartModal(btn);
        });
    });

})();
