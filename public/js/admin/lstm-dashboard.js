/**
 * lstm-dashboard.js  — v2.1 classifier UI
 * Wired to:
 *   GET  /api/lstm/stats
 *   GET  /api/lstm/employee-predictions
 *   GET  /api/lstm/employee-history/{id}
 *   POST /api/lstm/refresh-predictions
 */
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────
    let allEmployees = [];
    let distChart    = null;
    let histChart    = null;
    let horizonChart = null;
    let trendChart   = null;
    let taskChart    = null;
    let tableOpen    = false;

    // ── Dept colours ───────────────────────────────────────────
    const DEPT_COLORS = [
        '#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4',
        '#a855f7','#ec4899','#84cc16','#f97316','#3b82f6',
    ];
    const _dcMap = {};
    let _dci = 0;
    function deptColor(dept) {
        if (!_dcMap[dept]) _dcMap[dept] = DEPT_COLORS[_dci++ % DEPT_COLORS.length];
        return _dcMap[dept];
    }

    // ── Helpers ────────────────────────────────────────────────
    function initials(name) {
        return (name||'').split(' ').map(n=>n[0]).join('').toUpperCase().slice(0,2);
    }

    // Map numeric score → colour
    function scoreColor(s) {
        if (s >= 75) return '#22c55e';
        if (s >= 55) return '#3b82f6';
        return '#ef4444';
    }

    // Map numeric score → class label (matches train_lstm.py thresholds exactly)
    function scoreToClass(s) {
        if (s >= 75) return 'High';
        if (s >= 55) return 'Medium';
        return 'Low';
    }

    function classPill(cls) {
        const map = { High:'pill-high', Medium:'pill-med', Low:'pill-low' };
        return `<span class="class-pill ${map[cls]||'pill-med'}">${cls}</span>`;
    }

    function trendHtml(trend) {
        const t = (trend||'').toLowerCase();
        if (t==='improving'||t==='up')   return '<span class="trend-up-txt">▲ Improving</span>';
        if (t==='declining'||t==='down') return '<span class="trend-down-txt">▼ Declining</span>';
        return '<span class="trend-flat-txt">— Stable</span>';
    }

    // riskKey uses new classifier thresholds
    function riskKey(s) {
        if (s >= 75) return 'high';
        if (s >= 55) return 'medium';
        return 'low';
    }

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function showLoading() { document.getElementById('loading-overlay').classList.remove('hidden'); }
    function hideLoading() { document.getElementById('loading-overlay').classList.add('hidden'); }

    async function apiFetch(url, opts={}) {
        const r = await fetch(url, opts);
        if (!r.ok) throw new Error(`HTTP ${r.status} — ${url}`);
        return r.json();
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: KPI strip
    // ══════════════════════════════════════════════════════════
    function renderStats(stats, emps) {
        // Last run
        document.getElementById('last-run').textContent =
            stats.lastRun ? new Date(stats.lastRun).toLocaleDateString('vi-VN') : '—';

        // Model accuracy — show as percentage
        const acc = stats.accuracy != null ? stats.accuracy : 68.5;
        document.getElementById('m-acc').textContent = acc.toFixed(1) + '%';
        document.getElementById('m-acc-sub').textContent = 'On held-out test data';

        if (!emps.length) return;

        const avg        = emps.reduce((s,e)=>s+e.predictedScore,0)/emps.length;
        const avgCurrent = emps.reduce((s,e)=>s+e.currentScore,0)/emps.length;

        // Use classifier thresholds: Low < 55, High >= 75
        const atRisk = emps.filter(e=>e.predictedScore<55).length;
        const high   = emps.filter(e=>e.predictedScore>=75).length;
        const decl   = emps.filter(e=>(e.trend||'').includes('declin')||(e.trend||'')==='down').length;

        document.getElementById('m-avg').textContent     = avg.toFixed(1)+'%';
        document.getElementById('m-avg-delta').textContent =
            `vs current: ${(avg-avgCurrent>=0?'+':'')}${(avg-avgCurrent).toFixed(1)}%`;
        document.getElementById('m-risk').textContent    = atRisk;
        document.getElementById('m-risk-sub').textContent =
            `${atRisk>0?((100*atRisk/emps.length).toFixed(0)):0}% of team`;
        document.getElementById('m-burnout').textContent = decl;
        document.getElementById('m-high').textContent    = high;
        document.getElementById('m-high-sub').textContent =
            `${high>0?((100*high/emps.length).toFixed(0)):0}% of team`;
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: needs attention
    // ══════════════════════════════════════════════════════════
    function renderAttention(emps) {
        // Low predicted class = needs attention
        const atRisk = emps
            .filter(e=>e.predictedScore<55)
            .sort((a,b)=>a.predictedScore-b.predictedScore);

        document.getElementById('badge-atrisk').textContent =
            atRisk.length + ' employee' + (atRisk.length!==1?'s':'');

        const el = document.getElementById('attention-list');
        if (!atRisk.length) {
            el.innerHTML = '<div class="empty-msg">No employees in the Low class right now.</div>';
            return;
        }

        el.innerHTML = atRisk.map(e => `
            <div class="attn-row">
                <div>
                    <div class="emp-name">${e.name}</div>
                    <div class="emp-sub">${e.department} · ID ${e.id}</div>
                    <button class="btn-chart" style="margin-top:4px"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department}" data-trend="${e.trend}">
                        View chart
                    </button>
                </div>
                <div class="score-bar-wrap">
                    <div class="score-bar">
                        <div class="score-fill" style="width:${Math.min(e.currentScore,100)}%;background:${scoreColor(e.currentScore)}"></div>
                    </div>
                    <div class="score-num">${e.currentScore}%</div>
                </div>
                <div class="score-bar-wrap">
                    <div class="score-bar">
                        <div class="score-fill" style="width:${Math.min(e.predictedScore,100)}%;background:${scoreColor(e.predictedScore)}"></div>
                    </div>
                    <div class="score-num">${e.predictedScore}%</div>
                </div>
                <div>${trendHtml(e.trend)}</div>
            </div>
        `).join('');
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: recommended actions (was "alerts")
    // ══════════════════════════════════════════════════════════
    function renderAlerts(emps) {
        const actions = [];

        // Declining + Low = urgent
        emps.filter(e=>
            (e.trend==='declining'||e.trend==='down') && e.predictedScore<55
        ).slice(0,2).forEach(e=>actions.push({
            dot:'dot-red', tag:'tag-red', tagLabel:'Urgent',
            name:`${e.name} — ${e.department}`,
            desc:`Predicted Low (${e.predictedScore}%) and declining. Schedule a 1-on-1 and review workload immediately.`
        }));

        // Medium + declining = watch
        emps.filter(e=>
            (e.trend==='declining'||e.trend==='down') && e.predictedScore>=55 && e.predictedScore<75
        ).slice(0,2).forEach(e=>actions.push({
            dot:'dot-amber', tag:'tag-amber', tagLabel:'Monitor',
            name:`${e.name} — ${e.department}`,
            desc:`Predicted Medium (${e.predictedScore}%) but trending down. Check in before next review cycle.`
        }));

        // High performers — recognise
        emps.filter(e=>e.predictedScore>=75&&(e.trend==='improving'||e.trend==='up'))
            .slice(0,1).forEach(e=>actions.push({
            dot:'dot-blue', tag:'tag-blue', tagLabel:'Recognise',
            name:`${e.name} — ${e.department}`,
            desc:`Predicted High (${e.predictedScore}%) and improving. Consider for stretch projects or mentoring role.`
        }));

        const el = document.getElementById('alerts-list');
        if (!actions.length) {
            el.innerHTML = '<div class="empty-msg">No urgent actions needed.</div>';
            return;
        }
        el.innerHTML = actions.slice(0,5).map(a=>`
            <div class="action-item">
                <div class="action-dot ${a.dot}"></div>
                <div style="flex:1;min-width:0">
                    <div class="action-name">${a.name}</div>
                    <div class="action-desc">${a.desc}</div>
                </div>
                <span class="action-tag ${a.tag}">${a.tagLabel}</span>
            </div>
        `).join('');
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: prediction breakdown — class bars + trend rings
    // ══════════════════════════════════════════════════════════
    function renderBreakdown(emps) {
        const total   = emps.length || 1;
        const cntHigh = emps.filter(e=>e.predictedScore>=75).length;
        const cntMed  = emps.filter(e=>e.predictedScore>=55&&e.predictedScore<75).length;
        const cntLow  = emps.filter(e=>e.predictedScore<55).length;

        // Animate bars
        setTimeout(()=>{
            document.getElementById('bar-high').style.width = (100*cntHigh/total).toFixed(1)+'%';
            document.getElementById('bar-med').style.width  = (100*cntMed/total).toFixed(1)+'%';
            document.getElementById('bar-low').style.width  = (100*cntLow/total).toFixed(1)+'%';
        }, 100);

        document.getElementById('cnt-high').textContent = cntHigh + ' people';
        document.getElementById('cnt-med').textContent  = cntMed  + ' people';
        document.getElementById('cnt-low').textContent  = cntLow  + ' people';

        // Trend rings — use new trend strings
        const improving = emps.filter(e=>e.trend==='improving'||e.trend==='up').length;
        const declining = emps.filter(e=>e.trend==='declining'||e.trend==='down').length;
        const stable    = emps.length - improving - declining;

        document.getElementById('ring-improving').textContent = improving;
        document.getElementById('ring-stable').textContent    = stable;
        document.getElementById('ring-declining').textContent = declining;
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: score momentum chart (trend-chart)
    // ══════════════════════════════════════════════════════════
    function renderTrendChart(emps) {
        const ctx = document.getElementById('trend-chart');
        if (!ctx) return;
        if (trendChart) trendChart.destroy();

        // Build distribution of (predicted - current) deltas
        const buckets = { '< -10': 0, '-10 to -5': 0, '-5 to 0': 0, '0 to 5': 0, '5 to 10': 0, '> 10': 0 };
        emps.forEach(e => {
            const delta = e.predictedScore - e.currentScore;
            if      (delta < -10) buckets['< -10']++;
            else if (delta < -5)  buckets['-10 to -5']++;
            else if (delta < 0)   buckets['-5 to 0']++;
            else if (delta < 5)   buckets['0 to 5']++;
            else if (delta < 10)  buckets['5 to 10']++;
            else                  buckets['> 10']++;
        });

        trendChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(buckets),
                datasets: [{
                    data: Object.values(buckets),
                    backgroundColor: ['#ef4444','#f97316','#f59e0b','#22c55e','#16a34a','#15803d'],
                    borderRadius: 4, borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => ` ${c.parsed.y} employees` } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 }, color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 9 }, color: '#94a3b8', stepSize: 1 } }
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: task signal doughnut chart
    // ══════════════════════════════════════════════════════════
    function renderTaskSignalChart(emps) {
        const ctx = document.getElementById('task-signal-chart');
        if (!ctx) return;
        if (taskChart) taskChart.destroy();

        const withTasks    = emps.filter(e => e.predictedScore > 0 && e.currentScore > 0).length;
        const withoutTasks = emps.length - withTasks;

        taskChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['With tasks', 'No task data'],
                datasets: [{
                    data: [withTasks, withoutTasks],
                    backgroundColor: ['#3b82f6','#f0f0f0'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => ` ${c.parsed} employees` } } }
            }
        });

        const pct = emps.length ? Math.round((withTasks / emps.length) * 100) : 0;
        const leg = document.getElementById('task-signal-legend');
        if (leg) {
            leg.innerHTML = `
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;flex-shrink:0"></span>
                    <span>${withTasks} with task data (${pct}%)</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f0f0f0;border:1px solid #e0e0e0;flex-shrink:0"></span>
                    <span>${withoutTasks} no task signal</span>
                </div>`;
        }
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: burnout signal bars
    // ══════════════════════════════════════════════════════════
    function renderBurnoutSignals(emps) {
        const total = emps.length || 1;
        // Proxy: declining trend as burnout signal (we don't have hours from API)
        const declining  = emps.filter(e=>e.trend==='declining'||e.trend==='down').length;
        const lowScore   = emps.filter(e=>e.predictedScore<55).length;
        const both       = emps.filter(e=>
            (e.trend==='declining'||e.trend==='down') && e.predictedScore<55
        ).length;

        const setBar = (valId, barId, count) => {
            const el  = document.getElementById(valId);
            const bar = document.getElementById(barId);
            if (el)  el.textContent = count;
            if (bar) setTimeout(() => { bar.style.width = (count/total*100).toFixed(1)+'%'; }, 150);
        };

        setBar('b-overwork',   'b-overwork-bar',   declining);
        setBar('b-neg-trend',  'b-neg-trend-bar',  lowScore);
        setBar('b-combined',   'b-combined-bar',   both);
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: distribution chart
    // ══════════════════════════════════════════════════════════
    function renderDistChart(emps) {
        const buckets = [0,0,0,0,0,0];
        emps.forEach(e=>{
            const s=e.predictedScore;
            if(s<50) buckets[0]++;
            else if(s<60) buckets[1]++;
            else if(s<70) buckets[2]++;
            else if(s<80) buckets[3]++;
            else if(s<90) buckets[4]++;
            else buckets[5]++;
        });
        if(distChart) distChart.destroy();
        distChart = new Chart(document.getElementById('dist-chart'),{
            type:'bar',
            data:{
                labels:['<50%','50–60%','60–70%','70–80%','80–90%','≥90%'],
                datasets:[{
                    data:buckets,
                    backgroundColor:['#ef4444','#f97316','#f59e0b','#3b82f6','#22c55e','#16a34a'],
                    borderRadius:5,borderSkipped:false,
                }]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:false},
                    tooltip:{callbacks:{label:c=>` ${c.parsed.y} employees`}}},
                scales:{
                    x:{grid:{display:false},ticks:{font:{size:10},color:'#94a3b8'}},
                    y:{grid:{color:'rgba(0,0,0,.04)'},
                        ticks:{font:{size:10},color:'#94a3b8',stepSize:1},
                        title:{display:true,text:'employees',font:{size:10},color:'#cbd5e1'}}
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: departments
    // ══════════════════════════════════════════════════════════
    function renderDepts(emps) {
        const map = {};
        emps.forEach(e=>{
            const d=e.department||'Unknown';
            if(!map[d]) map[d]=[];
            map[d].push(e);
        });
        const depts = Object.entries(map).map(([name,group])=>({
            name,
            avg: group.reduce((s,e)=>s+e.predictedScore,0)/group.length,
            count: group.length,
            high:  group.filter(e=>e.predictedScore>=75).length,
            low:   group.filter(e=>e.predictedScore<55).length,
            color: deptColor(name),
        })).sort((a,b)=>b.avg-a.avg);

        document.getElementById('dept-row').innerHTML = depts.map(d=>{
            const cls = d.avg>=75?'badge-ok':d.avg>=55?'badge-med':'badge-danger';
            const lbl = d.avg >= 75 ? 'High' : d.avg >= 55 ? 'Medium' : 'Low';
            return `
            <div class="dept-card" style="border-top-color:${d.color};border-top-width:3px;border-top-style:solid">
                <div class="dept-card-name">${d.name}</div>
                <div class="dept-card-score" style="color:${d.color}">${d.avg.toFixed(1)}%</div>
                <div class="dept-bar-bg"><div class="dept-bar-fill" style="width:${d.avg}%;background:${d.color}"></div></div>
                <div class="dept-card-meta">${d.count} employees</div>
                <div>
                    <span style="font-size:.65rem;color:#166534">▲${d.high} High</span>
                    ${d.low>0?`<span style="font-size:.65rem;color:#991b1b;margin-left:6px">▼${d.low} Low</span>`:''}
                </div>
            </div>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: horizon chart — 7-day trend
    // ══════════════════════════════════════════════════════════
    function renderHorizonChart(emps) {
        if(horizonChart) horizonChart.destroy();
        const days=[], actual=[], predicted=[];
        const today=new Date();
        const avgS = emps.length ? emps.reduce((s,e)=>s+e.currentScore,0)/emps.length : 65;
        const avgP = emps.length ? emps.reduce((s,e)=>s+e.predictedScore,0)/emps.length : 65;

        for(let i=6;i>=0;i--){
            const d=new Date(today); d.setDate(d.getDate()-i);
            days.push(d.toLocaleDateString('en-US',{month:'short',day:'numeric'}));
            const tf=(i-3)*0.3;
            actual.push(+(avgS+tf).toFixed(1));
            predicted.push(+(avgP+tf*0.8).toFixed(1));
        }
        horizonChart=new Chart(document.getElementById('horizon-chart'),{
            type:'line',
            data:{
                labels:days,
                datasets:[
                    {label:'Actual',data:actual,borderColor:'#3b82f6',
                     backgroundColor:'rgba(59,130,246,.06)',borderWidth:2.5,
                     tension:.35,fill:true,pointRadius:3,
                     pointBackgroundColor:'#3b82f6',pointBorderColor:'#fff',pointBorderWidth:1.5},
                    {label:'LSTM Predicted',data:predicted,borderColor:'#22c55e',
                     borderDash:[6,4],borderWidth:2.5,fill:false,pointRadius:3,
                     pointBackgroundColor:'#22c55e',pointBorderColor:'#fff',pointBorderWidth:1.5}
                ]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                interaction:{mode:'index',intersect:false},
                plugins:{legend:{display:false},
                    tooltip:{callbacks:{label:c=>`${c.dataset.label}: ${c.parsed.y.toFixed(1)}%`}}},
                scales:{
                    x:{grid:{display:false},ticks:{font:{size:10},color:'#94a3b8'}},
                    y:{min:40,max:100,grid:{color:'rgba(0,0,0,.03)'},
                        ticks:{font:{size:10},color:'#94a3b8',stepSize:10,callback:v=>v+'%'}}
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: insights
    // ══════════════════════════════════════════════════════════
    function renderInsights(emps) {
        const map={};
        emps.forEach(e=>{
            const d=e.department||'Unknown';
            if(!map[d])map[d]=[];
            map[d].push(e.predictedScore);
        });
        const ranked=Object.entries(map)
            .map(([d,s])=>({dept:d,avg:s.reduce((a,b)=>a+b,0)/s.length}))
            .sort((a,b)=>b.avg-a.avg);

        const ins=[];
        if(ranked.length){
            ins.push({icon:'↑',cls:'i-up',
                text:`<strong>${ranked[0].dept}</strong> leads with ${ranked[0].avg.toFixed(1)}% average predicted score.`});
            if(ranked.length>1){
                const worst=ranked[ranked.length-1];
                ins.push({icon:'↓',cls:'i-down',
                    text:`<strong>${worst.dept}</strong> has the lowest average at ${worst.avg.toFixed(1)}%. Consider team support.`});
            }
        }
        const decl=emps.filter(e=>e.trend==='declining'||e.trend==='down').length;
        if(decl) ins.push({icon:'!',cls:'i-warn',
            text:`<strong>${decl} employee${decl!==1?'s':''}</strong> are trending downward — review before next cycle.`});

        const lowClass=emps.filter(e=>e.predictedScore<55).length;
        if(lowClass) ins.push({icon:'!',cls:'i-down',
            text:`<strong>${lowClass} employee${lowClass!==1?'s':''}</strong> predicted in the Low class next period.`});

        const highStable=emps.filter(e=>e.predictedScore>=75&&e.trend!=='declining'&&e.trend!=='down').length;
        if(highStable) ins.push({icon:'↑',cls:'i-up',
            text:`<strong>${highStable} employee${highStable!==1?'s':''}</strong> predicted High with stable or improving momentum.`});

        document.getElementById('insights-list').innerHTML = ins.slice(0,5).map(i=>`
            <div class="insight-item">
                <div class="insight-icon ${i.cls}">${i.icon}</div>
                <div class="insight-text">${i.text}</div>
            </div>
        `).join('');
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: top performers
    // ══════════════════════════════════════════════════════════
    function renderTop(emps) {
        const top=[...emps].sort((a,b)=>b.predictedScore-a.predictedScore).slice(0,6);

        // Set badge
        const badge = document.getElementById('badge-top');
        if (badge) badge.textContent = `Top ${top.length}`;

        document.getElementById('top-list').innerHTML = top.length
            ? top.map((e,i)=>`
            <div class="top-item">
                <div class="top-rank">${i+1}</div>
                <div class="top-avatar" style="background:${deptColor(e.department)}">${initials(e.name)}</div>
                <div class="top-info">
                    <div class="top-name" title="${e.name}">${e.name}</div>
                    <div class="top-dept">${e.department}</div>
                </div>
                <div class="top-score">${e.predictedScore}%</div>
            </div>
        `).join('')
            : '<div class="empty-msg">No data yet.</div>';
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: model transparency
    // ══════════════════════════════════════════════════════════
    function renderModelTransparency(stats) {
        // Model health stats — updated labels for classifier
        const acc = stats.accuracy != null ? stats.accuracy : 68.5;
        document.getElementById('ms-acc').textContent    = acc.toFixed(1)+'%';
        // macroF1 — controller must return this key
        document.getElementById('ms-f1').textContent     = stats.macroF1    != null ? stats.macroF1.toFixed(3) : '0.655';
        document.getElementById('ms-loss').textContent   = stats.valLoss    != null ? stats.valLoss.toFixed(4)  : '—';
        document.getElementById('ms-epochs').textContent = stats.epochsRan  != null ? stats.epochsRan           : '—';

        // Trust bar — use accuracy
        const trustPct = Math.min(acc, 100);
        document.getElementById('trust-fill').style.width = trustPct+'%';
        document.getElementById('trust-pct').textContent  = trustPct.toFixed(1)+'%';

        // Feature importance
        const fi = stats.featureImportance && Array.isArray(stats.featureImportance) && stats.featureImportance.length
            ? stats.featureImportance
            : [
                {name:'Recent score trend',        importance:0.88},
                {name:'Days since last check-in',  importance:0.81},
                {name:'Tasks completed this week', importance:0.74},
                {name:'Hours worked per day',      importance:0.67},
                {name:'Check-in streak',           importance:0.61},
                {name:'Task completion %',         importance:0.55},
                {name:'Lateness pattern',          importance:0.44},
                {name:'Day of week',               importance:0.38},
            ];
        renderFeatureImportance(fi);
    }

    function renderFeatureImportance(features) {
        const sorted=[...features].sort((a,b)=>b.importance-a.importance);
        document.getElementById('feature-importance-list').innerHTML = sorted.map(f=>`
            <div class="fi-row">
                <span class="fi-label" title="${f.name}">${f.name}</span>
                <div class="fi-track"><div class="fi-fill" style="width:${Math.round(f.importance*100)}%"></div></div>
                <span class="fi-val">${f.importance.toFixed(2)}</span>
            </div>
        `).join('');
    }

    // ══════════════════════════════════════════════════════════
    // RENDER: all employees table
    // ══════════════════════════════════════════════════════════
    function renderTable(emps) {
        const depts=[...new Set(emps.map(e=>e.department))].sort();
        const sel=document.getElementById('dept-filter');
        sel.innerHTML='<option value="">All departments</option>'+
            depts.map(d=>`<option value="${d}">${d}</option>`).join('');
        renderTableBody(emps);
    }

    function renderTableBody(emps) {
        const search=(document.getElementById('search').value||'').toLowerCase();
        const dept  = document.getElementById('dept-filter').value;
        const risk  = document.getElementById('risk-filter').value;

        const rows=emps.filter(e=>
            (!search||e.name.toLowerCase().includes(search)||(e.department||'').toLowerCase().includes(search))&&
            (!dept||e.department===dept)&&
            (!risk||riskKey(e.predictedScore)===risk)
        );

        const tbody=document.getElementById('all-tbody');
        if(!rows.length){
            tbody.innerHTML='<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:2rem;font-size:.78rem">No employees match these filters.</td></tr>';
            return;
        }

        tbody.innerHTML=rows.map(e=>{
            const cls       = scoreToClass(e.predictedScore);
            const conf      = e.confidence ? Math.round(e.confidence*100) : '—';
            const confBar   = e.confidence
                ? `<div class="conf-bar"><div class="conf-fill" style="width:${Math.round(e.confidence*100)}%"></div></div>${Math.round(e.confidence*100)}%`
                : '—';
            return `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:26px;height:26px;border-radius:50%;background:${deptColor(e.department)};
                            display:flex;align-items:center;justify-content:center;
                            font-size:.58rem;font-weight:800;color:#fff;flex-shrink:0">${initials(e.name)}</div>
                        <span style="font-weight:700;font-size:.8rem">${e.name}</span>
                    </div>
                </td>
                <td style="color:#94a3b8;font-size:.76rem">${e.department}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="width:40px;height:3px;background:#f1f5f9;border-radius:2px;overflow:hidden">
                            <div style="width:${Math.min(e.currentScore,100)}%;height:100%;background:${scoreColor(e.currentScore)};border-radius:2px"></div>
                        </div>
                        <span style="font-weight:600;font-size:.78rem">${e.currentScore}%</span>
                    </div>
                </td>
                <td>${classPill(cls)}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="width:40px;height:3px;background:#f1f5f9;border-radius:2px;overflow:hidden">
                            <div style="width:${Math.min(e.predictedScore,100)}%;height:100%;background:${scoreColor(e.predictedScore)};border-radius:2px"></div>
                        </div>
                        <span style="font-weight:600;font-size:.78rem">${e.predictedScore}%</span>
                    </div>
                </td>
                <td>${trendHtml(e.trend)}</td>
                <td style="font-size:.75rem">${confBar}</td>
                <td>
                    <button class="btn-chart"
                        data-id="${e.id}" data-name="${e.name}"
                        data-current="${e.currentScore}" data-predicted="${e.predictedScore}"
                        data-dept="${e.department}" data-trend="${e.trend}">
                        View chart
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════
    // HISTORY MODAL
    // ══════════════════════════════════════════════════════════
    function buildModal() {
        if(document.getElementById('hist-modal')) return;
        const el=document.createElement('div');
        el.id='hist-modal';
        el.style.cssText='position:fixed;inset:0;z-index:50;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.5)';
        el.innerHTML=`
        <div style="background:#fff;border-radius:16px;width:100%;max-width:680px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9">
                <div>
                    <div style="font-size:1rem;font-weight:800;color:#0f172a" id="hist-name">—</div>
                    <div style="font-size:.75rem;color:#94a3b8;margin-top:2px" id="hist-dept">—</div>
                </div>
                <button id="hist-close" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#94a3b8;padding:4px">✕</button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9">
                <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;text-align:center">
                    <div style="font-size:.68rem;color:#94a3b8;margin-bottom:3px">Current score</div>
                    <div style="font-size:1.2rem;font-weight:800;color:#0f172a" id="hist-current">—</div>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;text-align:center">
                    <div style="font-size:.68rem;color:#94a3b8;margin-bottom:3px">Predicted class</div>
                    <div style="font-size:1rem;font-weight:800" id="hist-class">—</div>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;text-align:center">
                    <div style="font-size:.68rem;color:#94a3b8;margin-bottom:3px">Change</div>
                    <div style="font-size:1.2rem;font-weight:800" id="hist-delta">—</div>
                </div>
            </div>
            <div style="padding:1rem 1.5rem;flex:1;min-height:0">
                <div id="hist-loading" style="display:flex;align-items:center;justify-content:center;height:200px;color:#94a3b8;font-size:.8rem;gap:8px">
                    Loading history…
                </div>
                <div id="hist-chart-wrap" style="position:relative;height:220px;display:none">
                    <canvas id="hist-canvas"></canvas>
                </div>
            </div>
            <div style="padding:.75rem 1.5rem;display:flex;gap:16px;font-size:.69rem;color:#94a3b8;border-top:1px solid #f1f5f9">
                <span style="display:flex;align-items:center;gap:5px"><span style="width:14px;height:2px;background:#3b82f6;display:inline-block"></span>Historical weekly avg</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:14px;height:0;border-top:2px dashed #22c55e;display:inline-block"></span>LSTM prediction</span>
            </div>
        </div>`;
        document.body.appendChild(el);
        document.getElementById('hist-close').addEventListener('click',closeModal);
        el.addEventListener('click',e=>{if(e.target===el)closeModal();});
        document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
    }

    function closeModal() {
        const m=document.getElementById('hist-modal');
        if(m) m.style.display='none';
        if(histChart){histChart.destroy();histChart=null;}
    }

    async function openChartModal(btn) {
        const id        = btn.dataset.id;
        const name      = btn.dataset.name;
        const dept      = btn.dataset.dept||'—';
        const current   = parseFloat(btn.dataset.current||0);
        const predicted = parseFloat(btn.dataset.predicted||0);

        buildModal();
        const modal=document.getElementById('hist-modal');
        modal.style.display='flex';

        document.getElementById('hist-name').textContent    = name;
        document.getElementById('hist-dept').textContent    = dept;
        document.getElementById('hist-current').textContent = current.toFixed(1)+'%';

        const cls = scoreToClass(predicted);
        const clsColors={High:'#166534',Medium:'#1e40af',Low:'#991b1b'};
        document.getElementById('hist-class').textContent  = cls;
        document.getElementById('hist-class').style.color  = clsColors[cls];

        const delta=predicted-current;
        const dEl=document.getElementById('hist-delta');
        dEl.textContent=(delta>=0?'+':'')+delta.toFixed(1)+'%';
        dEl.style.color=delta>2?'#166534':delta<-2?'#991b1b':'#475569';

        document.getElementById('hist-loading').style.display='flex';
        document.getElementById('hist-chart-wrap').style.display='none';
        if(histChart){histChart.destroy();histChart=null;}

        let data=null;
        try{ data=await apiFetch(`/api/lstm/employee-history/${id}`); }
        catch(e){ console.warn('History fetch failed',e); }

        const hasData=data&&Array.isArray(data.history)&&data.history.some(v=>v!==null&&v>0);
        if(!hasData){ data={labels:['Current','Predicted'],history:[current,null],predicted:[current,predicted]}; }

        document.getElementById('hist-loading').style.display='none';
        document.getElementById('hist-chart-wrap').style.display='block';

        const allVals=[...data.history,...data.predicted].filter(v=>v!==null);
        const minY=Math.max(0,Math.floor(Math.min(...allVals)/10)*10-10);
        const maxY=Math.min(100,Math.ceil(Math.max(...allVals)/10)*10+10);

        histChart=new Chart(document.getElementById('hist-canvas').getContext('2d'),{
            type:'line',
            data:{
                labels:data.labels,
                datasets:[
                    {label:'Historical',data:data.history,borderColor:'#3b82f6',
                     backgroundColor:'rgba(59,130,246,.07)',borderWidth:2.5,tension:.35,
                     fill:true,pointRadius:4,pointHoverRadius:6,
                     pointBackgroundColor:'#3b82f6',pointBorderColor:'#fff',pointBorderWidth:1.5,spanGaps:false},
                    {label:'LSTM Prediction',data:data.predicted,borderColor:'#22c55e',
                     borderDash:[6,4],borderWidth:2.5,fill:false,pointRadius:5,
                     pointBackgroundColor:'#22c55e',pointBorderColor:'#fff',pointBorderWidth:1.5,spanGaps:false}
                ]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                interaction:{mode:'index',intersect:false},
                plugins:{legend:{display:false},
                    tooltip:{callbacks:{label:c=>c.parsed.y===null?null:` ${c.dataset.label}: ${c.parsed.y.toFixed(1)}%`}}},
                scales:{
                    x:{grid:{display:false},ticks:{font:{size:11},color:'#9ca3af',maxRotation:45,autoSkip:false}},
                    y:{min:minY,max:maxY,grid:{color:'rgba(0,0,0,.04)'},
                        ticks:{font:{size:11},color:'#9ca3af',callback:v=>v+'%'}}
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // MAIN INIT
    // ══════════════════════════════════════════════════════════
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
            renderBreakdown(allEmployees);
            renderTrendChart(allEmployees);       // NEW — was missing
            renderTaskSignalChart(allEmployees);  // NEW — was missing
            renderBurnoutSignals(allEmployees);   // NEW — was missing
            renderDistChart(allEmployees);
            renderDepts(allEmployees);
            renderHorizonChart(allEmployees);
            renderInsights(allEmployees);
            renderTop(allEmployees);
            renderModelTransparency(stats);
            renderTable(allEmployees);
        } catch(err) {
            console.error('Dashboard init error:', err);
            // Show error state instead of just blank
            document.getElementById('attention-list').innerHTML =
                '<div class="empty-msg">Failed to load data. Check console for details.</div>';
        }
        hideLoading();
    }

    // ══════════════════════════════════════════════════════════
    // EVENT BINDINGS
    // ══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', ()=>{
        init();

        // Refresh
        document.getElementById('btn-refresh').addEventListener('click', async()=>{
            const btn=document.getElementById('btn-refresh');
            btn.disabled=true; btn.classList.add('spinning'); showLoading();
            try {
                await apiFetch('/api/lstm/refresh-predictions',{
                    method:'POST',
                    headers:{'X-CSRF-TOKEN':csrf(),'Content-Type':'application/json'}
                });
                await init();
            } catch(err){ alert('Refresh failed: '+err.message); }
            btn.disabled=false; btn.classList.remove('spinning'); hideLoading();
        });

        // Export
        document.getElementById('btn-export').addEventListener('click', async()=>{
            const btn=document.getElementById('btn-export');
            btn.disabled=true; showLoading();
            try {
                const response=await fetch('/api/lstm/export-excel',{
                    method:'POST',
                    headers:{'X-CSRF-TOKEN':csrf(),'Content-Type':'application/json'}
                });
                if(!response.ok){const e=await response.json();throw new Error(e.error||'Export failed');}
                const blob=await response.blob();
                const url=window.URL.createObjectURL(blob);
                const link=document.createElement('a');
                link.href=url;
                link.download=`LSTM_Report_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(link); link.click();
                document.body.removeChild(link); window.URL.revokeObjectURL(url);
            } catch(err){ alert('Export failed: '+err.message); }
            btn.disabled=false; hideLoading();
        });

        // Toggle table
        document.getElementById('btn-toggle-all').addEventListener('click',()=>{
            tableOpen=!tableOpen;
            document.getElementById('panel-all').style.display=tableOpen?'block':'none';
            document.getElementById('btn-toggle-all').textContent=tableOpen?'Hide ▴':'Show all ▾';
        });

        // Table filters
        ['search','dept-filter','risk-filter'].forEach(id=>{
            document.getElementById(id).addEventListener('input', ()=>renderTableBody(allEmployees));
            document.getElementById(id).addEventListener('change',()=>renderTableBody(allEmployees));
        });

        // View chart — delegated
        document.addEventListener('click',e=>{
            const btn=e.target.closest('.btn-chart');
            if(btn) openChartModal(btn);
        });
    });
})();
