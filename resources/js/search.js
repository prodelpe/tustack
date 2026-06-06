import { instantMeiliSearch } from '@meilisearch/instant-meilisearch'
import instantsearch from 'instantsearch.js'
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
    routing: true,
})

search.addWidgets([
    configure({
        hitsPerPage: 12,
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
    }),

    refinementList({
        container: '#filter-provinces',
        attribute: 'province_name',
        searchable: true,
        searchablePlaceholder: 'Search…',
        limit: 10,
        showMore: true,
    }),

    hits({
        container: '#hits',
        templates: {
            item: (hit) => `
                <a href="/companies/${hit.id}" class="hit-card">
                    <div class="hit-card__header">
                        <h2 class="hit-card__name">${hit.name}</h2>
                        ${hit.city || hit.province_name ? `
                            <p class="hit-card__location">
                                ${[hit.city, hit.province_name].filter(Boolean).join(', ')}
                            </p>
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
