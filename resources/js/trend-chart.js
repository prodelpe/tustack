const COLORS = [
    '#6366f1',
    '#8b5cf6',
    '#06b6d4',
    '#10b981',
    '#f59e0b',
    '#f43f5e',
    '#3b82f6',
    '#14b8a6',
]

function formatMonth(label) {
    const [year, month] = label.split('-')
    return new Date(year, month - 1).toLocaleDateString('en', { month: 'short', year: '2-digit' })
}

export function trendChart(Chart) {
    return (chartData) => ({
        chart: null,
        init() {
            const isDark = document.documentElement.classList.contains('dark')
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)'
            const textColor = isDark ? '#94a3b8' : '#9ca3af'

            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets.map((ds, i) => ({
                        label: ds.label,
                        data: ds.data,
                        borderColor: COLORS[i % COLORS.length],
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        tension: 0.3,
                        fill: false,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                usePointStyle: true,
                                pointStyleWidth: 8,
                                padding: 20,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                title: (items) => {
                                    const [year, month] = items[0].label.split('-')
                                    return new Date(year, month - 1).toLocaleDateString('en', { month: 'long', year: 'numeric' })
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                maxTicksLimit: 12,
                                callback(val) {
                                    return formatMonth(this.getLabelForValue(val))
                                },
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { color: textColor, precision: 0 },
                        },
                    },
                },
            })
        },
    })
}
