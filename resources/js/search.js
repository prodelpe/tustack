import { instantMeiliSearch } from '@meilisearch/instant-meilisearch'
import { buildLinkUrl, companyUrl } from './utils/url'
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
    sortBy,
} from 'instantsearch.js/es/widgets'

const { searchClient } = instantMeiliSearch(
    window.__MEILISEARCH_HOST__,
    window.__MEILISEARCH_KEY__,
)

let excludeConsultancies = new URLSearchParams(window.location.search).get('excl_cons') === '1'
let excludeRecruitment   = new URLSearchParams(window.location.search).get('excl_rec') === '1'

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
                const defaultSort = 'devstack_companies:job_offers_count:desc'
                return {
                    q: index.query || undefined,
                    tech: techs?.length ? techs : undefined,
                    prov: provs?.length ? provs : undefined,
                    page: index.page > 1 ? index.page : undefined,
                    sort: index.sortBy !== defaultSort ? index.sortBy : undefined,
                    excl_cons: excludeConsultancies ? '1' : undefined,
                    excl_rec:  excludeRecruitment   ? '1' : undefined,
                }
            },
            routeToState(routeState) {
                const toArray = v => !v ? [] : Array.isArray(v) ? v : [v]
                return {
                    'devstack_companies': {
                        query: routeState.q || '',
                        page: routeState.page || 1,
                        sortBy: routeState.sort || 'devstack_companies:job_offers_count:desc',
                        refinementList: {
                            technology_names: toArray(routeState.tech),
                            province_name: toArray(routeState.prov),
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
    }),

    sortBy({
        container: '#sort-by',
        items: [
            { label: window.__I18N__.sortMostOffers,     value: 'devstack_companies:job_offers_count:desc' },
            { label: window.__I18N__.sortRecentActivity, value: 'devstack_companies:last_offer_at:desc' },
            { label: window.__I18N__.sortNameAZ,         value: 'devstack_companies:name:asc' },
        ],
    }),

    searchBox({
        container: '#searchbox',
        placeholder: window.__I18N__.searchPlaceholder,
        showReset: true,
    }),

    stats({
        container: '#stats',
        templates: {
            text: ({ nbHits }) => window.__I18N__.companiesFound.replace(':count', nbHits),
        },
    }),

    refinementList({
        container: '#filter-technologies',
        attribute: 'technology_names',
        searchable: true,
        searchablePlaceholder: 'Search…',
        limit: 10,
        showMore: true,
        showMoreLimit: 100,
        sortBy: ['count:desc', 'name:asc'],
        templates: {
            showMoreText: ({ isShowingMore }) => isShowingMore ? window.__I18N__.showLess : window.__I18N__.showMore,
        },
    }),

    refinementList({
        container: '#filter-provinces',
        attribute: 'province_name',
        searchable: true,
        searchablePlaceholder: 'Search…',
        limit: 10,
        showMore: true,
        showMoreLimit: 100,
        sortBy: ['count:desc', 'name:asc'],
        templates: {
            showMoreText: ({ isShowingMore }) => isShowingMore ? window.__I18N__.showLess : window.__I18N__.showMore,
        },
    }),

    hits({
        container: '#hits',
        templates: {
            item: (hit) => `
                <a href="${companyUrl(hit.slug)}" class="hit-card">
                    <div class="hit-card__header">
                        <div>
                            <h2 class="hit-card__name">
                                ${hit.name}
                                ${hit.is_consultancy ? `<span class="hit-card__type-badge hit-card__type-badge--consultancy">${window.__I18N__.consultoria}</span>` : ''}
                                ${hit.is_recruitment ? `<span class="hit-card__type-badge hit-card__type-badge--recruitment">${window.__I18N__.recruitment}</span>` : ''}
                            </h2>
                            ${hit.city || hit.province_name ? `
                                <p class="hit-card__location">
                                    ${[hit.city, hit.province_name].filter(Boolean).join(', ')}
                                </p>
                            ` : ''}
                        </div>
                        ${hit.job_offers_count ? `
                            <span class="hit-card__offers-count">${hit.job_offers_count} ${hit.job_offers_count === 1 ? window.__I18N__.offer : window.__I18N__.offers}</span>
                        ` : ''}
                    </div>
                    <div class="hit-card__techs">
                        ${(hit.technology_names || []).map(t => `<span class="hit-card__tech">${t}</span>`).join('')}
                    </div>
                </a>
            `,
            empty: `<p class="hits-empty">${window.__I18N__.noCompaniesFound}</p>`,
        },
    }),

    clearRefinements({
        container: '#clear-filters',
        templates: {
            resetLabel: window.__I18N__.clearFilters,
        },
    }),

    pagination({
        container: '#pagination',
    }),
])

let trackedTechs = new Set()
let initialRenderDone = false
let salaryFetchTimer = null
let lastSalaryTechs = ''

function formatSalary(value) {
    return value ? Math.round(value / 1000) + 'k' : null
}

function renderSalaryInsights(insights) {
    const container = document.getElementById('salary-insights')
    if (!container) return

    if (!insights.length) {
        container.classList.add('hidden')
        container.innerHTML = ''
        return
    }

    const items = insights.map(({ name, range_min, range_max, offers_count }) => {
        const min = formatSalary(range_min)
        const max = formatSalary(range_max)
        const range = [min, max].filter(Boolean).join('–')
        return `<span class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 dark:border-green-800/40 dark:bg-green-950/30">
            <span class="text-xs font-medium text-gray-700 dark:text-slate-300">${name}</span>
            <span class="text-xs font-semibold text-green-700 dark:text-green-400">${range} €</span>
            <span class="text-xs text-gray-400 dark:text-slate-500">${offers_count} offers</span>
        </span>`
    }).join('')

    container.innerHTML = `<div class="flex flex-wrap items-center gap-2">${items}</div>`
    container.classList.remove('hidden')
}

function renderSalarySkeleton(count) {
    const container = document.getElementById('salary-insights')
    if (!container) return

    const skeletons = Array.from({ length: count }, () =>
        `<span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 dark:border-slate-700 dark:bg-slate-800 animate-pulse">
            <span class="h-3 w-16 rounded bg-gray-300 dark:bg-slate-600"></span>
            <span class="h-3 w-14 rounded bg-gray-200 dark:bg-slate-700"></span>
            <span class="h-3 w-12 rounded bg-gray-200 dark:bg-slate-700"></span>
        </span>`
    ).join('')

    container.innerHTML = `<div class="flex flex-wrap items-center gap-2">${skeletons}</div>`
    container.classList.remove('hidden')
}

function fetchSalaryInsights(techs) {
    if (!window.__SALARY_INSIGHTS_URL__ || !techs.length) {
        renderSalaryInsights([])
        return
    }

    const key = techs.slice().sort().join(',')
    if (key === lastSalaryTechs) return
    lastSalaryTechs = key

    renderSalarySkeleton(techs.length)

    clearTimeout(salaryFetchTimer)
    salaryFetchTimer = setTimeout(() => {
        const params = new URLSearchParams()
        techs.forEach(t => params.append('tech[]', t))
        fetch(`${window.__SALARY_INSIGHTS_URL__}?${params}`)
            .then(r => r.json())
            .then(renderSalaryInsights)
            .catch(() => {})
    }, 300)
}

function trackNewTechs(techs) {
    if (window.__IS_ADMIN__) return

    const newTechs = techs.filter(t => !trackedTechs.has(t))
    if (!newTechs.length) return

    newTechs.forEach(t => trackedTechs.add(t))

    fetch(window.__TRACK_SEARCH_URL__, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ technologies: newTechs }),
        keepalive: true,
    }).catch(() => {})
}

search.on('render', () => {
    const state = search.getUiState()['devstack_companies'] || {}
    const techs = state.refinementList?.technology_names || []
    const provs  = state.refinementList?.province_name || []

    if (!initialRenderDone) {
        initialRenderDone = true
        trackNewTechs(techs)
    }

    fetchSalaryInsights(techs)

    window.dispatchEvent(new CustomEvent('search-updated', {
        detail: {
            technologies: techs,
            provinces: provs,
            query: state.query || '',
        }
    }))

    const nbHits = search.helper?.lastResults?.nbHits ?? 0
    const pagination = document.getElementById('pagination')
    if (pagination) pagination.style.display = nbHits === 0 ? 'none' : ''

    const mapLink = document.getElementById('map-link')
    if (mapLink && window.__MAP_URL__) {
        mapLink.href = buildLinkUrl(window.__MAP_URL__, { techs, provs, excludeConsultancies, excludeRecruitment })
    }

    const trendsLink = document.getElementById('trends-link')
    if (trendsLink && window.__TENDENCIES_URL__) {
        trendsLink.href = buildLinkUrl(window.__TENDENCIES_URL__, { techs })
    }
})

search.start()

search.helper.on('change', (event) => {
    const techs = event.state.disjunctiveFacetsRefinements?.technology_names
        ?? event.state.facetsRefinements?.technology_names
        ?? []
    trackNewTechs(techs)
})

function buildFilters() {
    const parts = []
    if (excludeConsultancies) parts.push('is_consultancy = false')
    if (excludeRecruitment)   parts.push('is_recruitment = false')
    return parts.join(' AND ')
}

const excludeConsultanciesCheckbox = document.getElementById('exclude-consultancies')
const excludeRecruitmentCheckbox   = document.getElementById('exclude-recruitment')

if (excludeConsultanciesCheckbox) {
    if (excludeConsultancies) {
        excludeConsultanciesCheckbox.checked = true
        search.once('render', () => {
            search.helper.setQueryParameter('filters', buildFilters()).search()
        })
    }

    excludeConsultanciesCheckbox.addEventListener('change', (e) => {
        excludeConsultancies = e.target.checked
        search.helper.setQueryParameter('filters', buildFilters()).search()
    })
}

if (excludeRecruitmentCheckbox) {
    if (excludeRecruitment) {
        excludeRecruitmentCheckbox.checked = true
        search.once('render', () => {
            search.helper.setQueryParameter('filters', buildFilters()).search()
        })
    }

    excludeRecruitmentCheckbox.addEventListener('change', (e) => {
        excludeRecruitment = e.target.checked
        search.helper.setQueryParameter('filters', buildFilters()).search()
    })
}
