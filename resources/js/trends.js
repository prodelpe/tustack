import { instantMeiliSearch } from '@meilisearch/instant-meilisearch'
import instantsearch from 'instantsearch.js'
import { history } from 'instantsearch.js/es/lib/routers'
import { refinementList, clearRefinements, configure } from 'instantsearch.js/es/widgets'
import Chart from 'chart.js/auto'
import { buildDatasets, getChartOptions } from './utils/chart'
import { buildLinkUrl } from './utils/url'

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

const chart = new Chart(document.getElementById('trends-chart'), {
    type: 'line',
    data: { labels: [], datasets: [] },
    options: getChartOptions(),
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
            chart.data.datasets = buildDatasets(data.datasets)
            chart.update()
        })
        .catch(() => {})
}

search.on('render', () => {
    const techs = search.getUiState()['devstack_companies']?.refinementList?.technology_names || []

    updateChart(techs)

    const listLink = document.getElementById('list-link')
    if (listLink && window.__HOME_URL__) {
        listLink.href = buildLinkUrl(window.__HOME_URL__, { techs })
    }
})

search.start()
