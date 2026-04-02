class LSTMDashboard {
    constructor() {
        this.init();
    }

    async init() {
        await this.loadAll();
        this.bindEvents();
    }

    async loadAll() {
        await Promise.all([
            this.loadStats(),
            this.loadEmployees()
        ]);
    }

    // ✅ LOAD STATS
    async loadStats() {
        try {
            const res = await fetch('/api/lstm/stats');
            const data = await res.json();

            document.getElementById('last-run-date').textContent =
                new Date(data.lastRun).toLocaleDateString();

            document.getElementById('high-performers').textContent = data.highPerformers;
            document.getElementById('at-risk-employees').textContent = data.atRiskEmployees;
            document.getElementById('model-accuracy').textContent = data.accuracy + '%';

        } catch (e) {
            console.error('Stats error:', e);
        }
    }

    // ✅ LOAD EMPLOYEES
    async loadEmployees() {
        try {
            const res = await fetch('/api/lstm/employee-predictions');
            const data = await res.json();

            console.log('Employees:', data); // DEBUG
            this.renderTable(data);

        } catch (e) {
            console.error('Employee load error:', e);
        }
    }

    renderTable(data) {
        const tbody = document.getElementById('predictions-table-body');
        tbody.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">No data found</td></tr>`;
            return;
        }

        data.forEach(emp => {
            const risk = this.getRisk(emp.predictedScore);

            tbody.innerHTML += `
                <tr>
                    <td>${emp.name}</td>
                    <td>${emp.department}</td>
                    <td><span class="badge bg-primary">${emp.currentScore}%</span></td>
                    <td><span class="badge bg-${risk.color}">${emp.predictedScore}%</span></td>
                    <td>${emp.trend}</td>
                    <td><span class="badge bg-${risk.color}">${risk.level}</span></td>
                    <td>${new Date(emp.lastUpdated).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-warning refresh-one" data-id="${emp.id}">
                            🔄
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    getRisk(score) {
        if (score >= 80) return { level: 'Low', color: 'success' };
        if (score >= 60) return { level: 'Medium', color: 'primary' };
        if (score >= 40) return { level: 'High', color: 'warning' };
        return { level: 'Critical', color: 'danger' };
    }

    bindEvents() {
        const refreshBtn = document.getElementById('refresh-predictions');

        if (!refreshBtn) {
            console.error('Refresh button not found!');
            return;
        }

        // ✅ REFRESH ALL
        refreshBtn.addEventListener('click', async () => {

            refreshBtn.disabled = true;
            refreshBtn.innerText = 'Refreshing...';

            try {
                console.log('Starting refresh...');
                const res = await fetch('/api/lstm/refresh-predictions', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                console.log('Refresh response status:', res.status);
                const refreshData = await res.json();
                console.log('Refresh result:', refreshData);

                if (!res.ok) {
                    throw new Error(refreshData.error || 'Refresh failed');
                }

                // Wait a moment for DB to be updated
                await new Promise(resolve => setTimeout(resolve, 1000));

                console.log('Loading employees after refresh...');
                await this.loadEmployees();

                alert(`✅ All predictions refreshed\n${refreshData.message || ''}`);

            } catch (err) {
                console.error('Refresh error:', err);
                alert('❌ Refresh failed: ' + err.message);
            }

            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh All Predictions';
        });

        // ✅ REFRESH ONE
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.refresh-one');
            if (!btn) return;

            btn.disabled = true;
            btn.innerText = '...';

            try {
                await fetch('/api/lstm/refresh-predictions', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                await this.loadEmployees();

            } catch {
                alert('Failed');
            }

            btn.disabled = false;
            btn.innerText = '🔄';
        });
    }
}

// ✅ INIT
document.addEventListener('DOMContentLoaded', () => {
    new LSTMDashboard();
});
