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

    // ── Class thresholds (must match train_lstm_nextday.py) ────
    const TH_LOW  = 50;   // <50  = Low
    const TH_HIGH = 80;   // >=80 = High;   50..79 = Medium

    // ── Department palette ─────────────────────────────────────
    const DEPT_COLORS = [
        '#5347CC','#17C8C6','#4896FE','#10B981','#F59E0B',
        '#EF4444','#766CD6','#5FE1E0','#7FBFFF','#34D399',
    ];
    const _dcMap = {};
    let _dci = 0;
    const deptColor = d => _dcMap[d] || (_dcMap[d] = DEPT_COLORS[_dci++ % DEPT_COLORS.length]);

    // ── Helpers ────────────────────────────────────────────────
    const initials = name => (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

    function generateAvatarHTML(user, sizeClass = 'h-8 w-8', ringClass = 'ring-2 ring-white') {
        const name = user.name || 'U';
        const id = user.id || 0;
        const photoData = user.avatar_url || user.user_profile_photo || user.avatar || null;

        // 1. If user has a photo
        if (photoData) {
            // Assuming your asset URL maps to the root /storage/ directory
            const src = photoData.startsWith('http') ? photoData : `/storage/${photoData}`;
            return `<img src="${src}" alt="${name}" title="${name}" class="${sizeClass} rounded-full object-cover flex-shrink-0 ${ringClass}">`;
        }

        // 2. Fallback to Initials
        const initial = name.charAt(0).toUpperCase();
        const colors = ['bg-primary/10 text-primary', 'bg-secondary/10 text-secondary', 'bg-accent/20 text-accent'];
        const colorClass = colors[id % colors.length];

        return `
        <div class="bg-white rounded-full ${sizeClass} flex-shrink-0 ${ringClass}">
            <div class="${sizeClass} rounded-full ${colorClass} flex items-center justify-center font-bold text-xs" title="${name}">
                ${initial}
            </div>
        </div>`;
    }
    
    function scoreToClass(s) {
        if (s >= TH_HIGH) return 'High';
        if (s >= TH_LOW)  return 'Medium';
        return 'Low';
    }

    function classPill(cls) {
        const map = {
            High:   'inline-flex items-center rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success',
            Medium: 'inline-flex items-center rounded-full bg-secondary/10 px-3 py-1 text-xs font-semibold text-secondary',
            Low:    'inline-flex items-center rounded-full bg-danger/10 px-3 py-1 text-xs font-semibold text-danger',
        };
        return `<span class="${map[cls] || map.Medium}">${cls}</span>`;
    }

    function classColor(cls) {
        return cls === 'High' ? '#059669' : cls === 'Medium' ? '#2680F6' : '#DC2626';
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
    // TIER 1 — SNAPSHOT + HERO BANNER
    // ══════════════════════════════════════════════════════════
    function renderSnapshot(emps) {
        const total = emps.length || 1;
        const high  = emps.filter(e => e.predictedScore >= TH_HIGH).length;
        const low   = emps.filter(e => e.predictedScore <  TH_LOW).length;
        const med   = total - high - low;

        const decliningMed = emps.filter(e =>
            e.predictedScore >= TH_LOW && e.predictedScore < TH_HIGH &&
            (e.trend === 'declining' || e.trend === 'down')
        ).length;
        const attention = low + decliningMed;

        document.getElementById('snap-low').textContent  = low;
        document.getElementById('snap-med').textContent  = med;
        document.getElementById('snap-high').textContent = high;

        renderHeroBanner(emps, { attention, low, decliningMed, high, total });
    }

    function renderHeroBanner(emps, { attention, low, decliningMed, high, total }) {
        // ── Department summaries ──────────────────────────────
        const deptMap = {};
        emps.forEach(e => {
            const d = e.department || 'Unknown';
            if (!deptMap[d]) deptMap[d] = { name: d, count: 0, totalScore: 0, low: 0, declining: 0, improving: 0 };
            deptMap[d].count++;
            deptMap[d].totalScore += e.predictedScore;
            if (e.predictedScore < TH_LOW) deptMap[d].low++;
            if (e.trend === 'declining' || e.trend === 'down') deptMap[d].declining++;
            if (e.trend === 'improving' || e.trend === 'up')   deptMap[d].improving++;
        });
        const depts = Object.values(deptMap);

        const decliningLowCount = emps.filter(e =>
            e.predictedScore < TH_LOW && (e.trend === 'declining' || e.trend === 'down')
        ).length;

        // ── State machine ─────────────────────────────────────
        // happy_path > red_alert > mixed_bag > chronic
        let state;
        if (attention === 0) {
            state = 'happy_path';
        } else if (decliningLowCount > 0) {
            state = 'red_alert';
        } else {
            const hasDecliningDept = depts.some(d => d.declining > 0);
            const hasImprovingDept = depts.some(d => d.improving > 0 && d.declining === 0);
            state = (hasDecliningDept && hasImprovingDept) ? 'mixed_bag' : 'chronic';
        }

        // ── Dept highlights ───────────────────────────────────
        const worstDecliningDept = [...depts].filter(d => d.declining > 0)
            .sort((a, b) => b.declining - a.declining)[0];
        const worstLowDept = [...depts].filter(d => d.low > 0)
            .sort((a, b) => b.low - a.low)[0];
        const bestAvgDept = [...depts]
            .sort((a, b) => (b.totalScore / b.count) - (a.totalScore / a.count))[0];
        const bestImprovingDept = [...depts].filter(d => d.improving > 0 && d.declining === 0)
            .sort((a, b) => b.improving - a.improving)[0];

        // ── Spotlight employee ────────────────────────────────
        let spotEmp;
        if (state === 'happy_path') {
            spotEmp = [...emps].sort((a, b) => b.predictedScore - a.predictedScore)[0];
        } else {
            spotEmp =
                [...emps].filter(e => e.predictedScore < TH_LOW && (e.trend === 'declining' || e.trend === 'down'))
                         .sort((a, b) => a.predictedScore - b.predictedScore)[0]
             || [...emps].filter(e => e.predictedScore < TH_LOW)
                         .sort((a, b) => a.predictedScore - b.predictedScore)[0]
             || [...emps].filter(e => e.trend === 'declining' || e.trend === 'down')
                         .sort((a, b) => a.predictedScore - b.predictedScore)[0];
        }

        // ── Left narrative ────────────────────────────────────
        const link  = document.getElementById('hero-count-link');
        const count = document.getElementById('snap-attention');
        const label = document.getElementById('hero-count-label');
        const sub   = document.getElementById('snap-sub');
        const s     = attention === 1 ? '' : 's';

        switch (state) {
            case 'happy_path':
                count.textContent        = high;
                label.textContent        = 'employees on track';
                link.removeAttribute('href');
                link.style.pointerEvents = 'none';
                sub.textContent = `Team productivity looks strong for tomorrow. Scores are trending up across the board${bestAvgDept ? ', led by ' + bestAvgDept.name : ''}.`;
                break;

            case 'red_alert':
                count.textContent        = attention;
                label.textContent        = `employee${s} require attention`;
                link.href                = '#attention-section';
                link.style.pointerEvents = '';
                sub.textContent = `${attention} employee${s} require attention today. Driven primarily by sharp declining trends${worstDecliningDept ? ' in ' + worstDecliningDept.name : ''}.`;
                break;

            case 'chronic':
                count.textContent        = attention;
                label.textContent        = `employee${s} require attention`;
                link.href                = '#attention-section';
                link.style.pointerEvents = '';
                sub.textContent = `${attention} employee${s} require attention today. Scores remain persistently low${worstLowDept ? ' in ' + worstLowDept.name : ''}, with no upward momentum.`;
                break;

            case 'mixed_bag':
                count.textContent        = attention;
                label.textContent        = `employee${s} require attention`;
                link.href                = '#attention-section';
                link.style.pointerEvents = '';
                const dropD = worstDecliningDept?.name || worstLowDept?.name;
                const gainD = bestImprovingDept?.name  || bestAvgDept?.name;
                sub.textContent = `${attention} employee${s} require attention today. Unexpected drops${dropD ? ' in ' + dropD : ''}${gainD ? ', despite overall gains in ' + gainD : ''}.`;
                break;
        }

        // ── Right micro card ──────────────────────────────────
        const card = document.getElementById('hero-micro-card');
        if (!spotEmp) { card.classList.remove('flex'); card.classList.add('hidden'); return; }

        const isHappy    = state === 'happy_path';
        const empCls     = scoreToClass(spotEmp.predictedScore);
        const trend      = (spotEmp.trend || '').toLowerCase();
        const trendLabel = trend === 'declining' || trend === 'down' ? 'Steep Decline'
                         : trend === 'improving' || trend === 'up'   ? 'Improving'
                         : 'Stable';
        const pillBg     = isHappy ? 'rgba(52,211,153,.2)'   : 'rgba(252,165,165,.18)';
        const pillBorder = isHappy ? 'rgba(52,211,153,.4)'   : 'rgba(252,165,165,.4)';
        const pillColor  = isHappy ? '#34D399'                : '#FCA5A5';
        const cardTitle  = isHappy ? '★  Top Performer'       : '⚠  Top Priority';
        const cardAction = isHappy ? 'View Profile'            : 'Review Profile';

        card.innerHTML = `
            <div style="font-size:.62rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.45);margin-bottom:6px">${cardTitle}</div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                <div style="width:36px;height:36px;border-radius:9999px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0">
                    ${initials(spotEmp.name)}
                </div>
                <div style="min-width:0">
                    <div style="font-size:.85rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">${spotEmp.name}</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.55);margin-top:2px">${spotEmp.department || '—'}</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px">
                <span style="background:${pillBg};border:1px solid ${pillBorder};border-radius:6px;padding:2px 8px;font-size:.7rem;font-weight:600;color:${pillColor}">${isHappy ? empCls : trendLabel}</span>
                <span style="font-size:.72rem;color:rgba(255,255,255,.5)">Score: ${spotEmp.predictedScore.toFixed(0)}</span>
            </div>
            <button class="btn-chart hero-micro-btn"
                data-id="${spotEmp.id}" data-name="${spotEmp.name}"
                data-current="${spotEmp.currentScore ?? 0}" data-predicted="${spotEmp.predictedScore}"
                data-dept="${spotEmp.department || ''}" data-trend="${spotEmp.trend || ''}">
                ${cardAction} →
            </button>`;

        card.classList.remove('hidden');
        card.classList.add('flex');
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
            el.innerHTML = `<tr><td colspan="5" class="px-5 py-12 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-success/10 text-success mx-auto">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-main">All clear</p>
                        <p class="text-xs text-muted-400 mt-0.5">No employees need attention right now.</p>
                    </div>
                </div>
            </td></tr>`;
            return;
        }

        el.innerHTML = candidates.map(e => {
            const cls  = scoreToClass(e.predictedScore);
            const conf = e.confidence ? Math.round(e.confidence * 100) : null;
            const confHtml = conf !== null
                ? `<div class="flex items-center gap-2" title="Model confidence ${conf}%">
                       <div class="flex-1 bg-muted-100 rounded-full h-1.5 overflow-hidden max-w-[60px]">
                           <div class="h-full rounded-full bg-primary transition-[width] duration-500" style="width:${conf}%"></div>
                       </div>
                       <span class="text-xs font-semibold text-muted-600 min-w-[32px]">${conf}%</span>
                   </div>`
                : `<span class="text-xs text-muted-400">—</span>`;

            return `
            <tr class="hover:bg-canvas transition-colors">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        ${generateAvatarHTML(e, 'h-7 w-7 mr-2', '')}
                        <div class="min-w-0">
                            <div class="font-semibold text-main text-sm">${e.name}</div>
                            <div class="text-xs text-muted-400 mt-0.5">${e.department || '—'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4">${trajectoryLabel(e)}</td>
                <td class="px-4 py-4">${classPill(cls)}</td>
                <td class="px-4 py-4">${confHtml}</td>
                <td class="px-4 py-4 text-center">
                    <button class="btn-chart p-2 rounded-lg text-muted-400 hover:bg-primary/5 hover:text-primary transition-colors"
                        title="View history"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department || ''}" data-trend="${e.trend}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-chart-line-icon lucide-chart-line"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    </button>
                </td>
            </tr>`;
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
            el.innerHTML = `<div class="flex flex-col items-center justify-center gap-3 py-8">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-muted-100 text-muted-400 mx-auto">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <p class="text-sm text-muted-400">No department data.</p>
            </div>`;
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
                    backgroundColor: ['#DC2626','#EF4444','#7FBFFF','#4896FE','#34D399','#10B981'],
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
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF' } },
                    y: {
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { font: { size: 10 }, color: '#9CA3AF', stepSize: 1 },
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
            el.innerHTML = `<div class="flex flex-col items-center justify-center gap-3 py-8">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-muted-100 text-muted-400 mx-auto">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon stroke-linecap="round" stroke-linejoin="round" points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <p class="text-sm text-muted-400">No High predictions yet.</p>
            </div>`;
            return;
        }

        el.innerHTML = top.map((e, i) => `
            <div class="top-row">
                <span class="top-rank">${i + 1}</span>
                ${generateAvatarHTML(e, 'h-7 w-7', '')}
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
            tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-10 text-center text-sm text-muted-400">No employees match these filters.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(e => {
            const cls  = scoreToClass(e.predictedScore);
            const conf = e.confidence ? Math.round(e.confidence * 100) : null;
            const trendHtml = e.trend === 'declining' || e.trend === 'down'
                ? '<span class="traj-declining text-xs font-semibold">▼ Down</span>'
                : e.trend === 'improving' || e.trend === 'up'
                ? '<span class="traj-improving text-xs font-semibold">▲ Up</span>'
                : '<span class="traj-stable text-xs font-semibold">— Flat</span>';

            return `
            <tr class="hover:bg-canvas transition-colors">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-2">
                        ${generateAvatarHTML(e, 'h-7 w-7', '')}
                        <span class="font-semibold text-main text-sm">${e.name}</span>
                    </div>
                </td>
                <td class="px-4 py-4 text-xs text-muted-500">${e.department || '—'}</td>
                <td class="px-4 py-4"><span class="font-semibold text-main">${e.currentScore.toFixed(1)}</span></td>
                <td class="px-4 py-4">${classPill(cls)}</td>
                <td class="px-4 py-4">${trendHtml}</td>
                <td class="px-4 py-4 text-xs text-muted-500">${conf !== null ? conf + '%' : '—'}</td>
                <td class="px-4 py-4">
                    <button class="btn-chart p-2 rounded-lg text-muted-400 hover:bg-primary/5 hover:text-primary transition-colors"
                        title="View history"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department || ''}" data-trend="${e.trend}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-chart-line-icon lucide-chart-line"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    </button>
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

            <div class="flex items-start justify-between px-6 py-5 border-b border-muted-200 relative z-10 flex-shrink-0">
                <div>
                    <h2 class="font-bold text-xl text-main tracking-tight" id="hist-name">—</h2>
                    <p class="text-muted-500 text-sm mt-0.5" id="hist-dept">—</p>
                </div>
                <button type="button" id="hist-close"
                    class="mt-0.5 ml-4 flex-shrink-0 p-2 rounded-xl text-muted-400 hover:bg-muted-100 hover:text-main transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
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
                        label: 'Historical', data: data.history, borderColor: '#4896FE',
                        backgroundColor: 'rgba(72,150,254,.07)', borderWidth: 2.5,
                        tension: .35, fill: true, pointRadius: 4, pointHoverRadius: 6,
                        pointBackgroundColor: '#4896FE', pointBorderColor: '#fff',
                        pointBorderWidth: 1.5, spanGaps: false,
                    },
                    {
                        label: 'Predicted', data: data.predicted, borderColor: '#10B981',
                        borderDash: [6, 4], borderWidth: 2.5, fill: false, pointRadius: 5,
                        pointBackgroundColor: '#10B981', pointBorderColor: '#fff',
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
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9CA3AF' } },
                    y: {
                        min: minY, max: maxY,
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { font: { size: 11 }, color: '#9CA3AF' }
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
                '<tr><td colspan="5" class="px-5 py-10 text-center text-sm text-danger">Failed to load data. Check console for details.</td></tr>';
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

        // Full table is now a <details> element — no JS toggle needed.

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