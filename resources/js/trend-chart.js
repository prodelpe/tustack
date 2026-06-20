import { COLORS, buildDatasets, getChartOptions } from './utils/chart'

export function trendChart(Chart) {
    return (chartData) => ({
        chart: null,
        techs: chartData.datasets.map((ds, i) => ({
            label: ds.label,
            color: COLORS[i % COLORS.length],
            active: true,
        })),
        toggle(label) {
            const tech = this.techs.find(t => t.label === label)
            if (!tech) return
            tech.active = !tech.active
            const idx = this.chart.data.datasets.findIndex(ds => ds.label === label)
            if (idx !== -1) {
                this.chart.data.datasets[idx].hidden = !tech.active
                this.chart.update()
            }
        },
        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: buildDatasets(chartData.datasets),
                },
                options: getChartOptions(),
            })
        },
    })
}
