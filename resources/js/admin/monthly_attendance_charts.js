import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const payload = window.monthlyAttendanceChartData;

    if (!payload) return;

    const hoursCanvas = document.getElementById('monthlyHoursChart');
    if (hoursCanvas) {
        new Chart(hoursCanvas, {
            type: 'bar',
            data: {
                labels: payload.labels,
                datasets: [
                    {
                        label: 'Expected Hours',
                        data: payload.expected,
                        borderRadius: 8,
                        maxBarThickness: 18,
                    },
                    {
                        label: 'Actual Hours',
                        data: payload.actual,
                        borderRadius: 8,
                        maxBarThickness: 18,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    }

    const statusCanvas = document.getElementById('monthlyStatusChart');
    if (statusCanvas) {
        new Chart(statusCanvas, {
            type: 'bar',
            data: {
                labels: ['Ahead', 'On Track', 'Behind'],
                datasets: [
                    {
                        label: 'Employees',
                        data: [
                            payload.statusCounts.ahead,
                            payload.statusCounts.on_track,
                            payload.statusCounts.behind,
                        ],
                        borderRadius: 10,
                        maxBarThickness: 56,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                },
            },
        });
    }
});
