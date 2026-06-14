import { instantMeiliSearch } from '@meilisearch/instant-meilisearch'
import instantsearch from 'instantsearch.js'
import { history } from 'instantsearch.js/es/lib/routers'
import {
    searchBox,
    refinementList,
    clearRefinements,
    hits,
    stats,
    pagination,
    configure,
} from 'instantsearch.js/es/widgets'

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
                const provs = index.refinementList?.province_name
                return {
                    q: index.query || undefined,
                    technologies: techs?.length ? techs.join(',') : undefined,
                    provinces: provs?.length ? provs.join(',') : undefined,
                    page: index.page > 1 ? index.page : undefined,
                }
            },
            routeToState(routeState) {
                const toArray = v => !v ? [] : Array.isArray(v) ? v : v.split(',')
                return {
                    'devstack_companies': {
                        query: routeState.q || '',
                        page: routeState.page || 1,
                        refinementList: {
                            technology_names: toArray(routeState.technologies),
                            province_name: toArray(routeState.provinces),
                        },
                    },
                }
            },
        },
    },
})

search.addWidgets([
    configure({
        hitsPerPage: 12,
        sort: ['job_offers_count:desc'],
    }),

    searchBox({
        container: '#searchbox',
        placeholder: 'Search companies…',
        showReset: true,
    }),

    stats({
        container: '#stats',
        templates: {
            text: ({ nbHits }) => `${nbHits} companies found`,
        },
    }),

    refinementList({
        container: '#filter-technologies',
        attribute: 'technology_names',
        searchable: true,
        searchablePlaceholder: 'Search…',
        limit: 10,
        showMore: true,
        sortBy: ['count:desc', 'name:asc'],
    }),

    refinementList({
        container: '#filter-provinces',
        attribute: 'province_name',
        searchable: true,
        searchablePlaceholder: 'Search…',
        limit: 10,
        showMore: true,
        sortBy: ['count:desc', 'name:asc'],
    }),

    hits({
        container: '#hits',
        templates: {
            item: (hit) => `
                <a href="/companies/${hit.id}" class="hit-card">
                    <div class="hit-card__header">
                        <div>
                            <h2 class="hit-card__name">${hit.name}</h2>
                            ${hit.city || hit.province_name ? `
                                <p class="hit-card__location">
                                    ${[hit.city, hit.province_name].filter(Boolean).join(', ')}
                                </p>
                            ` : ''}
                        </div>
                        ${hit.job_offers_count ? `
                            <span class="hit-card__offers-count">${hit.job_offers_count} ${hit.job_offers_count === 1 ? 'offer' : 'offers'} tracked</span>
                        ` : ''}
                    </div>
                    <div class="hit-card__techs">
                        ${(hit.technology_names || []).map(t => `<span class="hit-card__tech">${t}</span>`).join('')}
                    </div>
                </a>
            `,
            empty: '<p class="hits-empty">No companies found for the selected filters.</p>',
        },
    }),

    clearRefinements({
        container: '#clear-filters',
        templates: {
            resetLabel: 'Clear filters',
        },
    }),

    pagination({
        container: '#pagination',
    }),
])

search.start()
