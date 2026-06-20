import { instantMeiliSearch } from '@meilisearch/instant-meilisearch'
import instantsearch from 'instantsearch.js'
import { history } from 'instantsearch.js/es/lib/routers'
import { refinementList, clearRefinements, configure } from 'instantsearch.js/es/widgets'
import Chart from 'chart.js/auto'

const COLORS = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#f43f5e', '#3b82f6', '#14b8a6']

function formatMonth(label) {
    const [year, month] = label.split('-')
    return new Date(year, month - 1).toLocaleDateString('en', { month: 'short', year: '2-digit' })
}

// — InstantSearch —

const { searchClient } = instantMeiliSearch(
    window.__MEILISEARCH_HOST__,
    window.__MEILISEARCH_KEY__,
)

const search = instantsearch({
    indexName: 'devstack_companies',
    searchClient,
    routing: {
        router: history({
            createURL({ qsModule, routeState, location }) {
                const query = qsModule.stringify(routeState, { encode: false })
                return `${location.pathname}${query ? '?' + query : ''}`
            },
        }),
        stateMapping: {
            stateToRoute(uiState) {
                const index = uiState['devstack_companies'] || {}
                const techs = index.refinementList?.technology_names
                return {
                    tech: techs?.length ? techs : undefined,
                }
            },
            routeToState(routeState) {
                const toArray = v => !v ? [] : Array.isArray(v) ? v : [v]
                return {
                    'devstack_companies': {
                        refinementList: {
                            technology_names: toArray(routeState.tech),
                        },
                    },
                }
            },
        },
    },
})

search.addWidgets([
    configure({ hitsPerPage: 0 }),

    refinementList({
        container: '#filter-technologies',
        attribute: 'technology_names',
        searchable: true,
        searchablePlaceholder: 'Search…',
        limit: 10,
        showMore: true,
        sortBy: ['count:desc', 'name:asc'],
    }),

    clearRefinements({
        container: '#clear-filters',
        templates: { resetLabel: 'Clear filters' },
    }),
])

// — Chart.js —

const canvas = document.getElementById('trends-chart')
const isDark  = document.documentElement.classList.contains('dark')
const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)'
const textColor = isDark ? '#94a3b8' : '#9ca3af'

const chart = new Chart(canvas, {
    type: 'line',
    data: { labels: [], datasets: [] },
    options: {
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
    },
})

// — Sync —

let lastTechKey = null

function updateChart(techs) {
    const key = [...techs].sort().join(',')
    if (key === lastTechKey) return
    lastTechKey = key

    const params = new URLSearchParams()
    techs.forEach(t => params.append('tech[]', t))

    fetch(`${window.__TENDENCY_DATA_URL__}?${params}`)
        .then(r => r.json())
        .then(data => {
            chart.data.labels = data.labels
            chart.data.datasets = data.datasets.map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                borderColor: COLORS[i % COLORS.length],
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4,
                tension: 0.3,
                fill: false,
            }))
            chart.update()
        })
        .catch(() => {})
}

search.on('render', () => {
    const techs = search.getUiState()['devstack_companies']?.refinementList?.technology_names || []

    updateChart(techs)

    const listLink = document.getElementById('list-link')
    if (listLink && window.__HOME_URL__) {
        const params = new URLSearchParams()
        techs.forEach((t, i) => params.set(`tech[${i}]`, t))
        const query = params.toString().replaceAll('%5B', '[').replaceAll('%5D', ']')
        listLink.href = window.__HOME_URL__ + (query ? '?' + query : '')
    }
})

search.start()
