/**
 * LSTM Dashboard JavaScript
 * Handles data visualization and API calls for productivity predictions
 */

class LSTMDashboard {
    constructor() {
        this.baseURL = 'http://localhost:5001'; // LSTM API URL
        this.charts = {};
        this.refreshInterval = null;
        this.init();
    }

    async init() {
        try {
            await this.loadInitialData();
            this.initializeCharts();
            this.setupEventListeners();
            this.startAutoRefresh();
            console.log('LSTM Dashboard initialized successfully');
        } catch (error) {
            console.error('Error initializing LSTM Dashboard:', error);
            this.showError('Failed to initialize dashboard');
        }
    }

    async loadInitialData() {
        try {
            // Load dashboard statistics
            await Promise.all([
                this.updateDashboardStats(),
                this.loadProductivityTrends(),
                this.loadPerformanceDistribution(),
                this.loadEmployeePredictions()
            ]);
        } catch (error) {
            console.error('Error loading initial data:', error);
            throw error;
        }
    }

    async updateDashboardStats() {
        try {
            // Simulate API call - replace with your actual endpoint
            const response = await fetch('/api/lstm/stats');
            const data = await response.json();

            // Update stat cards
            document.getElementById('last-run-date').textContent =
                new Date(data.lastRun).toLocaleDateString();
            document.getElementById('high-performers').textContent = data.highPerformers;
            document.getElementById('at-risk-employees').textContent = data.atRiskEmployees;
            document.getElementById('model-accuracy').textContent = `${data.accuracy}%`;
        } catch (error) {
            console.error('Error updating dashboard stats:', error);
            // Set default values
            document.getElementById('last-run-date').textContent = 'Mar 30, 2026';
            document.getElementById('high-performers').textContent = '23';
            document.getElementById('at-risk-employees').textContent = '7';
            document.getElementById('model-accuracy').textContent = '87.3%';
        }
    }

    async loadProductivityTrends() {
        try {
            const timeframe = document.getElementById('timeframe-select').value;

            // Replace with your actual API endpoint
            const response = await fetch(`/api/lstm/trends?days=${timeframe}`);
            const data = await response.json();

            this.updateProductivityChart(data);
        } catch (error) {
            console.error('Error loading productivity trends:', error);
            // Use sample data
            this.updateProductivityChart(this.getSampleTrendData());
        }
    }

    async loadPerformanceDistribution() {
        try {
            const response = await fetch('/api/lstm/distribution');
            const data = await response.json();

            this.updateDistributionChart(data);
        } catch (error) {
            console.error('Error loading performance distribution:', error);
            // Use sample data
            this.updateDistributionChart({
                high: 35,
                medium: 42,
                low: 18,
                critical: 5
            });
        }
    }

    async loadEmployeePredictions() {
        try {
            const response = await fetch('/api/lstm/employee-predictions');
            const data = await response.json();

            this.updatePredictionsTable(data);
        } catch (error) {
            console.error('Error loading employee predictions:', error);
            // Use sample data
            this.updatePredictionsTable(this.getSampleEmployeeData());
        }
    }

    initializeCharts() {
        // Initialize Productivity Trend Chart
        const trendCtx = document.getElementById('productivityTrendChart').getContext('2d');
        this.charts.productivity = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Average Productivity Score',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Predicted Score',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Initialize Distribution Chart
        const distCtx = document.getElementById('distributionChart').getContext('2d');
        this.charts.distribution = new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: ['High (80-100%)', 'Medium (60-80%)', 'Low (40-60%)', 'Critical (<40%)'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#28a745',
                        '#007bff',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const value = context.parsed;
                                return `${label}: ${value}%`;
                            }
                        }
                    }
                }
            }
        });
    }

    updateProductivityChart(data) {
        if (!this.charts.productivity) return;

        this.charts.productivity.data.labels = data.labels;
        this.charts.productivity.data.datasets[0].data = data.actual;
        this.charts.productivity.data.datasets[1].data = data.predicted;
        this.charts.productivity.update();
    }

    updateDistributionChart(data) {
        if (!this.charts.distribution) return;

        this.charts.distribution.data.datasets[0].data = [
            data.high,
            data.medium,
            data.low,
            data.critical
        ];
        this.charts.distribution.update();
    }

    updatePredictionsTable(employees) {
        const tbody = document.getElementById('predictions-table-body');
        tbody.innerHTML = '';

        employees.forEach(emp => {
            const row = document.createElement('tr');

            // Determine risk level and color
            const riskInfo = this.getRiskLevel(emp.predictedScore);

            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${emp.avatar || '/images/default-avatar.png'}"
                             class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                        <span class="font-weight-bold">${emp.name}</span>
                    </div>
                </td>
                <td>${emp.department}</td>
                <td>
                    <span class="badge bg-primary">${emp.currentScore}%</span>
                </td>
                <td>
                    <span class="badge bg-${riskInfo.color}">${emp.predictedScore}%</span>
                </td>
                <td>
                    <i class="fas fa-arrow-${emp.trend === 'up' ? 'up text-success' : emp.trend === 'down' ? 'down text-danger' : 'right text-warning'}"></i>
                    <span class="ms-1">${emp.trendValue}%</span>
                </td>
                <td>
                    <span class="badge bg-${riskInfo.color}">${riskInfo.level}</span>
                </td>
                <td>${new Date(emp.lastUpdated).toLocaleDateString()}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-sm" onclick="dashboard.viewEmployeeDetails(${emp.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="dashboard.sendAlert(${emp.id})">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </div>
                </td>
            `;

            tbody.appendChild(row);
        });
    }

    getRiskLevel(score) {
        if (score >= 80) return { level: 'Low', color: 'success' };
        if (score >= 60) return { level: 'Medium', color: 'primary' };
        if (score >= 40) return { level: 'High', color: 'warning' };
        return { level: 'Critical', color: 'danger' };
    }

    setupEventListeners() {
        // Timeframe selector
        document.getElementById('timeframe-select').addEventListener('change', () => {
            this.loadProductivityTrends();
        });

        // Refresh button
        document.getElementById('refresh-predictions').addEventListener('click', async () => {
            const button = document.getElementById('refresh-predictions');
            const originalHTML = button.innerHTML;

            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            button.disabled = true;

            try {
                await this.refreshAllPredictions();
            } finally {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        });
    }

    async refreshAllPredictions() {
        try {
            // Call your LSTM API to generate fresh predictions
            const response = await fetch(`${this.baseURL}/predict/all`, {
                method: 'POST'
            });

            if (!response.ok) {
                throw new Error('Failed to refresh predictions');
            }

            // Reload all data
            await this.loadInitialData();
            this.showSuccess('Predictions refreshed successfully');
        } catch (error) {
            console.error('Error refreshing predictions:', error);
            this.showError('Failed to refresh predictions');
        }
    }

    startAutoRefresh() {
        // Refresh data every 5 minutes
        this.refreshInterval = setInterval(() => {
            this.loadInitialData();
        }, 5 * 60 * 1000);
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    // Action methods
    viewEmployeeDetails(employeeId) {
        // Redirect to employee detail page or open modal
        window.location.href = `/admin/employees/${employeeId}/productivity-details`;
    }

    async sendAlert(employeeId) {
        try {
            const response = await fetch('/api/alerts/productivity-concern', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ employeeId })
            });

            if (response.ok) {
                this.showSuccess('Alert sent successfully');
            } else {
                throw new Error('Failed to send alert');
            }
        } catch (error) {
            console.error('Error sending alert:', error);
            this.showError('Failed to send alert');
        }
    }

    // Utility methods
    showSuccess(message) {
        // Use your existing notification system
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(message);
        }
    }

    showError(message) {
        // Use your existing notification system
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        } else {
            alert(message);
        }
    }

    // Sample data methods (remove when you have real API endpoints)
    getSampleTrendData() {
        const days = parseInt(document.getElementById('timeframe-select').value);
        const labels = [];
        const actual = [];
        const predicted = [];

        for (let i = days - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));

            // Generate sample data
            const baseScore = 75 + Math.sin(i / 7) * 10;
            actual.push(Math.round(baseScore + (Math.random() - 0.5) * 10));
            predicted.push(Math.round(baseScore + (Math.random() - 0.5) * 8));
        }

        return { labels, actual, predicted };
    }

    getSampleEmployeeData() {
        const names = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'Tom Brown'];
        const departments = ['Engineering', 'Marketing', 'Sales', 'HR', 'Finance'];

        return names.map((name, index) => ({
            id: index + 1,
            name,
            department: departments[index],
            currentScore: Math.round(60 + Math.random() * 35),
            predictedScore: Math.round(65 + Math.random() * 30),
            trend: ['up', 'down', 'stable'][Math.floor(Math.random() * 3)],
            trendValue: Math.round(Math.random() * 10),
            lastUpdated: new Date().toISOString(),
            avatar: null
        }));
    }

    // Cleanup
    destroy() {
        this.stopAutoRefresh();

        // Destroy charts
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });

        this.charts = {};
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.dashboard = new LSTMDashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.dashboard) {
        window.dashboard.destroy();
    }
});