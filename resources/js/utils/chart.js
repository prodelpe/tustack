export const COLORS = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#f43f5e', '#3b82f6', '#14b8a6']

export function formatMonth(label) {
    const [year, month] = label.split('-')
    return new Date(year, month - 1).toLocaleDateString('en', { month: 'short', year: '2-digit' })
}

function getThemeColors() {
    const isDark = document.documentElement.classList.contains('dark')
    return {
        gridColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
        textColor: isDark ? '#94a3b8' : '#9ca3af',
    }
}

export function buildDatasets(rawDatasets) {
    return rawDatasets.map((ds, i) => ({
        label: ds.label,
        data: ds.data,
        borderColor: COLORS[i % COLORS.length],
        borderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 4,
        tension: 0.3,
        fill: false,
    }))
}

export function getChartOptions() {
    const { gridColor, textColor } = getThemeColors()
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
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
                    callback(val) { return formatMonth(this.getLabelForValue(val)) },
                },
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: { color: textColor, precision: 0 },
            },
        },
    }
}
