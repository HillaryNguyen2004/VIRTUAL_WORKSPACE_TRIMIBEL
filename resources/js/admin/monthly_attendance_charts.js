import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const payload = window.monthlyAttendanceChartData;

    if (!payload) return;

    // ---------------------------------------------------------
    // CHART 1: Workforce Health (Scatter Plot)
    // Replaces the "Top 10 variance" horizontal bar chart
    // ---------------------------------------------------------
    const hoursCanvas = document.getElementById('monthlyHoursChart');
    if (hoursCanvas) {
        // Zip the separated arrays into {x, y} coordinates for the scatter plot
        const scatterData = payload.labels.map((label, index) => ({
            x: payload.expected[index],
            y: payload.actual[index],
            name: label 
        }));

        // Calculate the maximum axis value to draw a perfect 45-degree target line
        const maxExpected = Math.max(...payload.expected, 0);
        const maxActual = Math.max(...payload.actual, 0);
        const maxAxis = Math.max(maxExpected, maxActual) + 20; // Added padding

        new Chart(hoursCanvas, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: 'Employees',
                        data: scatterData,
                        backgroundColor: '#5347CC',
                        borderColor: '#766CD6',
                        pointRadius: 6,
                        pointHoverRadius: 8,
                    },
                    {
                        type: 'line',
                        label: 'Target (100% On Track)',
                        // Draws a diagonal line from 0,0 to the top right
                        data: [{x: 0, y: 0}, {x: maxAxis, y: maxAxis}],
                        borderColor: 'rgba(200, 200, 200, 0.5)', // Subtle gray
                        borderWidth: 2,
                        borderDash: [5, 5], // Dashed line for the target
                        pointRadius: 0, // Hides the dots on the line itself
                        fill: false
                    }
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            // Custom tooltip to show the employee name and exact hours
                            label: (context) => {
                                const emp = context.raw;
                                return `${emp.name}: ${emp.y}h actual vs ${emp.x}h expected`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Expected Hours' },
                        grid: { display: false },
                        min: 0,
                        max: maxAxis
                    },
                    y: {
                        title: { display: true, text: 'Actual Hours' },
                        min: 0,
                        max: maxAxis
                    },
                },
            },
        });
    }

    // ---------------------------------------------------------
    // CHART 2: Variance Histogram (Intensity Bucket Chart)
    // Replaces the "Monthly status distribution" giant single bar
    // ---------------------------------------------------------
    const statusCanvas = document.getElementById('monthlyStatusChart');
    if (statusCanvas) {
        
        // Calculate the variance buckets directly on the client side
        let buckets = { 
            'Behind': 0, 
            'On Track': 0, 
            '1-20h Extra': 0, 
            '20h+ Extra': 0 
        };

        payload.labels.forEach((_, i) => {
            let variance = payload.actual[i] - payload.expected[i];
            
            if (variance < -2) {
                buckets['Behind']++;
            } else if (variance <= 2) {
                buckets['On Track']++;
            } else if (variance <= 20) {
                buckets['1-20h Extra']++;
            } else {
                buckets['20h+ Extra']++;
            }
        });

        new Chart(statusCanvas, {
            type: 'bar',
            data: {
                labels: Object.keys(buckets),
                datasets: [
                    {
                        label: 'Employees',
                        data: Object.values(buckets),
                        backgroundColor: [
                            '#34D399',
                            '#17C8C6',
                            '#4896FE',
                            '#5347CC'
                        ],
                        borderRadius: 8,
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
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.raw} Employees`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0, // Prevents decimals like 1.5 employees
                        },
                    },
                },
            },
        });
    }
});