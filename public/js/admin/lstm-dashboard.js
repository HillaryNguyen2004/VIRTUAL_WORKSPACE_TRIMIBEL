class LSTMDashboard {
    constructor() {
        this.employees = [];
        this.filteredEmployees = [];
        this.currentView = 'cards';
        this.currentPage = 1;
        this.itemsPerPage = 12;
        this.init();
    }

    async init() {
        this.showLoading();
        await this.loadAll();
        this.bindEvents();
        this.hideLoading();
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
            this.employees = data || [];
            this.filteredEmployees = [...this.employees];
            this.populateFilters();
            this.renderEmployees();

        } catch (e) {
            console.error('Employee load error:', e);
            this.employees = [];
            this.filteredEmployees = [];
            this.renderEmployees();
        }
    }

    populateFilters() {
        // Populate department filter
        const departments = [...new Set(this.employees.map(emp => emp.department))];
        const deptFilter = document.getElementById('department-filter');
        deptFilter.innerHTML = '<option value="">All Departments</option>';
        departments.forEach(dept => {
            deptFilter.innerHTML += `<option value="${dept}">${dept}</option>`;
        });
    }

    applyFilters() {
        const deptFilter = document.getElementById('department-filter').value;
        const riskFilter = document.getElementById('risk-filter').value;
        const searchTerm = document.getElementById('employee-search').value.toLowerCase();

        this.filteredEmployees = this.employees.filter(emp => {
            const matchesDept = !deptFilter || emp.department === deptFilter;
            const matchesRisk = !riskFilter || this.getRisk(emp.predictedScore).level === riskFilter;
            const matchesSearch = !searchTerm ||
                emp.name.toLowerCase().includes(searchTerm) ||
                emp.department.toLowerCase().includes(searchTerm);

            return matchesDept && matchesRisk && matchesSearch;
        });

        this.currentPage = 1; // Reset to first page when filtering
        this.renderEmployees();
    }

    renderEmployees() {
        this.updateEmployeeCount();

        if (this.currentView === 'cards') {
            this.renderCards();
            document.getElementById('employees-cards').style.display = '';
            document.getElementById('employees-table').style.display = 'none';
            document.getElementById('pagination-cards').style.display = this.filteredEmployees.length > this.itemsPerPage ? 'flex' : 'none';
            document.getElementById('pagination-table').style.display = 'none';
        } else {
            this.renderTable();
            document.getElementById('employees-cards').style.display = 'none';
            document.getElementById('employees-table').style.display = 'block';
            document.getElementById('pagination-cards').style.display = 'none';
            document.getElementById('pagination-table').style.display = this.filteredEmployees.length > this.itemsPerPage ? 'flex' : 'none';
        }

        this.renderPagination();
    }

    getPaginatedEmployees() {
        if (this.itemsPerPage === 'all') {
            return this.filteredEmployees;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        return this.filteredEmployees.slice(startIndex, endIndex);
    }

    renderCards() {
        const container = document.getElementById('employees-cards');
        const paginatedEmployees = this.getPaginatedEmployees();

        if (!Array.isArray(paginatedEmployees) || paginatedEmployees.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No employees found</h5>
                    <p class="text-muted">Try adjusting your filters or search terms</p>
                </div>
            `;
            container.className = 'row';
            return;
        }

        container.innerHTML = '';
        container.className = 'row';

        paginatedEmployees.forEach((emp, index) => {
            const risk = this.getRisk(emp.predictedScore);
            const trend = this.getTrend(emp.trend);
            const initials = this.getInitials(emp.name);

            const cardDiv = document.createElement('div');
            cardDiv.className = 'col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3';

            cardDiv.innerHTML = `
                <div class="employee-card p-3 h-100 fade-in">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="employee-avatar">${initials}</div>
                        <span class="badge bg-${risk.color} risk-badge">${risk.level}</span>
                    </div>

                    <div class="mb-2">
                        <h6 class="mb-1 text-truncate" title="${emp.name}">${emp.name}</h6>
                        <small class="text-muted text-truncate d-block" title="${emp.department}">${emp.department}</small>
                    </div>

                    <div class="row mb-2 text-center">
                        <div class="col-6">
                            <div class="score-display text-primary">${emp.currentScore}%</div>
                            <small class="text-muted">Current</small>
                        </div>
                        <div class="col-6">
                            <div class="score-display text-${risk.colorClass}">${emp.predictedScore}%</div>
                            <small class="text-muted">Predicted</small>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="mini-chart" style="--progress: ${emp.predictedScore}%; --chart-color: ${this.getScoreColor(emp.predictedScore)}"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="trend-indicator ${trend.class}">
                            <i class="${trend.icon}"></i>
                            <span class="d-none d-lg-inline">${this.getTrendText(emp.trend)}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary refresh-one" data-id="${emp.id}" title="Refresh prediction">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>

                    <div class="mt-1">
                        <small class="text-muted">${new Date(emp.lastUpdated).toLocaleDateString()}</small>
                    </div>
                </div>
            `;

            // Add stagger animation
            setTimeout(() => cardDiv.style.animationDelay = `${index * 50}ms`, 0);
            container.appendChild(cardDiv);
        });
    }

    renderTable() {
        const tbody = document.getElementById('predictions-table-body');
        const paginatedEmployees = this.getPaginatedEmployees();
        tbody.innerHTML = '';

        if (!Array.isArray(paginatedEmployees) || paginatedEmployees.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4">No employees found</td></tr>`;
            return;
        }

        paginatedEmployees.forEach(emp => {
            const risk = this.getRisk(emp.predictedScore);
            const trend = this.getTrend(emp.trend);
            const initials = this.getInitials(emp.name);

            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="min-width: 0;">
                            <div class="employee-avatar me-2" style="width: 30px; height: 30px; font-size: 0.7rem;">${initials}</div>
                            <strong class="employee-name-cell" title="${emp.name}">${emp.name}</strong>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark department-cell" title="${emp.department}">${emp.department}</span></td>
                    <td><span class="badge bg-primary">${emp.currentScore}%</span></td>
                    <td><span class="badge bg-${risk.color}">${emp.predictedScore}%</span></td>
                    <td><span class="${trend.class} trend-cell"><i class="${trend.icon}"></i> ${emp.trend}</span></td>
                    <td><span class="badge bg-${risk.color}">${risk.level}</span></td>
                    <td><small>${new Date(emp.lastUpdated).toLocaleDateString()}</small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary refresh-one" data-id="${emp.id}">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    renderPagination() {
        if (this.itemsPerPage === 'all') {
            document.getElementById('pagination-cards').style.display = 'none';
            document.getElementById('pagination-table').style.display = 'none';
            return;
        }

        const totalPages = Math.ceil(this.filteredEmployees.length / this.itemsPerPage);
        const currentPagination = this.currentView === 'cards' ? 'pagination-cards' : 'pagination-table';
        const container = document.querySelector(`#${currentPagination} .pagination`);

        if (totalPages <= 1) {
            document.getElementById(currentPagination).style.display = 'none';
            return;
        }

        let paginationHtml = '';

        // Previous button
        paginationHtml += `
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                paginationHtml += `
                    <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next button
        paginationHtml += `
            <li class="page-item ${this.currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        container.innerHTML = paginationHtml;
    }

    getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    }

    getTrendText(trend) {
        if (trend.length > 8) return trend.slice(0, 5) + '...';
        return trend;
    }

    getRisk(score) {
        if (score >= 80) return { level: 'Low', color: 'success', colorClass: 'success' };
        if (score >= 60) return { level: 'Medium', color: 'primary', colorClass: 'primary' };
        if (score >= 40) return { level: 'High', color: 'warning', colorClass: 'warning' };
        return { level: 'Critical', color: 'danger', colorClass: 'danger' };
    }

    getTrend(trendText) {
        const trend = trendText?.toLowerCase() || '';
        if (trend.includes('improv') || trend.includes('up') || trend.includes('increas')) {
            return { icon: 'fas fa-arrow-up', class: 'trend-up' };
        } else if (trend.includes('declin') || trend.includes('down') || trend.includes('decreas')) {
            return { icon: 'fas fa-arrow-down', class: 'trend-down' };
        } else {
            return { icon: 'fas fa-minus', class: 'trend-stable' };
        }
    }

    getScoreColor(score) {
        if (score >= 80) return '#28a745';
        if (score >= 60) return '#007bff';
        if (score >= 40) return '#ffc107';
        return '#dc3545';
    }

    updateEmployeeCount() {
        const count = this.filteredEmployees.length;
        const total = this.employees.length;
        document.getElementById('employee-count').textContent =
            `${count} of ${total} employee${total !== 1 ? 's' : ''}`;
    }

    showLoading() {
        document.getElementById('loading-spinner').style.display = 'block';
        document.getElementById('employees-cards').style.display = 'none';
        document.getElementById('employees-table').style.display = 'none';
    }

    hideLoading() {
        document.getElementById('loading-spinner').style.display = 'none';
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
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            this.showLoading();

            try {
                const res = await fetch('/api/lstm/refresh-predictions', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const refreshData = await res.json();

                if (!res.ok) {
                    throw new Error(refreshData.error || 'Refresh failed');
                }

                await new Promise(resolve => setTimeout(resolve, 1000));
                await this.loadEmployees();

                alert(`✅ All predictions refreshed\n${refreshData.message || ''}`);

            } catch (err) {
                console.error('Refresh error:', err);
                alert('❌ Refresh failed: ' + err.message);
            }

            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh All Predictions';
            this.hideLoading();
        });

        // ✅ REFRESH ONE
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.refresh-one');
            if (!btn) return;

            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
            btn.innerHTML = originalContent;
        });

        // PAGINATION CLICKS
        document.addEventListener('click', (e) => {
            if (e.target.closest('.page-link') && e.target.closest('.pagination')) {
                e.preventDefault();
                const page = parseInt(e.target.closest('.page-link').dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.renderEmployees();
                }
            }
        });

        // FILTERS
        document.getElementById('department-filter').addEventListener('change', () => this.applyFilters());
        document.getElementById('risk-filter').addEventListener('change', () => this.applyFilters());
        document.getElementById('employee-search').addEventListener('input', () => this.applyFilters());

        // ITEMS PER PAGE
        document.getElementById('items-per-page').addEventListener('change', (e) => {
            this.itemsPerPage = e.target.value === 'all' ? 'all' : parseInt(e.target.value);
            this.currentPage = 1;
            this.renderEmployees();
        });

        // VIEW TOGGLE
        document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.currentView = e.target.value;
                    this.renderEmployees();
                }
            });
        });
    }
}

// ✅ INIT
document.addEventListener('DOMContentLoaded', () => {
    new LSTMDashboard();
});