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
import { connectRefinementList } from 'instantsearch.js/es/connectors'

const INDEX_NAME = 'devstack_companies'
const DEFAULT_SORT = `${INDEX_NAME}:last_offer_at:desc`
const VISIBLE_TECHNOLOGIES = 5
const POPULAR_TECHNOLOGIES = 6

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
                return {
                    q: index.query || undefined,
                    tech: techs?.length ? techs : undefined,
                    prov: provs?.length ? provs : undefined,
                    page: index.page > 1 ? index.page : undefined,
                    sort: index.sortBy !== DEFAULT_SORT ? index.sortBy : undefined,
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
                        sortBy: routeState.sort || DEFAULT_SORT,
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

const facetSelect = connectRefinementList(renderFacetSelect)
const popularTechnologies = connectRefinementList(renderPopularTechnologies)

function renderFacetSelect({ items, widgetParams }, isFirstRender) {
    const { container, attribute, allLabel } = widgetParams
    const element = document.querySelector(container)

    if (!element) {
        return
    }

    if (isFirstRender) {
        const select = document.createElement('select')
        select.className = 'hero__select'
        select.setAttribute('aria-label', allLabel)
        select.addEventListener('change', event => selectOnly(attribute, event.target.value))
        element.appendChild(select)
    }

    const select = element.querySelector('select')
    const selected = items.filter(item => item.isRefined)
    const options = [`<option value="">${escapeHtml(allLabel)}</option>`]

    if (selected.length > 1) {
        options.push(`<option value="__several__" disabled>${window.__I18N__.severalSelected.replace(':count', selected.length)}</option>`)
    }

    items.forEach(item => {
        options.push(`<option value="${escapeHtml(item.value)}">${escapeHtml(item.label)} (${item.count})</option>`)
    })

    select.innerHTML = options.join('')
    select.value = selected.length === 1 ? selected[0].value : (selected.length > 1 ? '__several__' : '')
}

function renderPopularTechnologies({ items }) {
    const element = document.querySelector('#hero-popular')

    if (!element) {
        return
    }

    if (items.length === 0) {
        element.innerHTML = ''

        return
    }

    const chips = items.map(item => {
        const modifier = item.isRefined ? ' hero__chip--active' : ''

        return `<button type="button" class="hero__chip${modifier}" data-value="${escapeHtml(item.value)}">${escapeHtml(item.label)}</button>`
    })

    element.innerHTML = `<span class="hero__popular-label">${window.__I18N__.popularTechnologies}</span>${chips.join('')}`
}

document.querySelector('#hero-popular')?.addEventListener('click', event => {
    const chip = event.target.closest('[data-value]')

    if (!chip) {
        return
    }

    const { value } = chip.dataset
    const selected = selectedValues('technology_names')
    const isOnlySelection = selected.length === 1 && selected[0] === value

    selectOnly('technology_names', isOnlySelection ? '' : value)
})

search.addWidgets([
    configure({
        hitsPerPage: 12,
    }),

    sortBy({
        container: '#sort-by',
        items: [
            { label: window.__I18N__.sortRecentActivity, value: DEFAULT_SORT },
            { label: window.__I18N__.sortMostOffers,     value: `${INDEX_NAME}:active_offers_count:desc` },
            { label: window.__I18N__.sortNameAZ,         value: `${INDEX_NAME}:name:asc` },
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

    facetSelect({
        container: '#hero-technology',
        attribute: 'technology_names',
        allLabel: window.__I18N__.anyTechnology,
        limit: 100,
        sortBy: ['count:desc', 'name:asc'],
    }),

    facetSelect({
        container: '#hero-province',
        attribute: 'province_name',
        allLabel: window.__I18N__.anyProvince,
        limit: 100,
        sortBy: ['name:asc'],
    }),

    popularTechnologies({
        attribute: 'technology_names',
        limit: POPULAR_TECHNOLOGIES,
        sortBy: ['count:desc', 'name:asc'],
    }),

    hits({
        container: '#hits',
        templates: {
            item: hit => companyCard(hit),
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

function companyCard(hit) {
    return `
        <a href="${companyUrl(hit.slug)}" class="hit-card">
            <h2 class="hit-card__name">
                ${escapeHtml(hit.name)}
                ${hit.is_consultancy ? `<span class="hit-card__type-badge hit-card__type-badge--consultancy">${window.__I18N__.consultoria}</span>` : ''}
                ${hit.is_recruitment ? `<span class="hit-card__type-badge hit-card__type-badge--recruitment">${window.__I18N__.recruitment}</span>` : ''}
            </h2>
            ${locationLabel(hit) ? `<p class="hit-card__location">${escapeHtml(locationLabel(hit))}</p>` : ''}
            <p class="hit-card__activity">${activityLabel(hit)}</p>
            <div class="hit-card__techs">${technologyBadges(hit.technology_names ?? [])}</div>
        </a>
    `
}

function locationLabel({ city, province_name: province }) {
    if (!city) {
        return province ?? ''
    }

    if (!province || city === province) {
        return city
    }

    return `${city}, ${province}`
}

function activityLabel(hit) {
    const activeOffers = hit.active_offers_count ?? 0

    if (activeOffers === 0) {
        return window.__I18N__.noRecentOffers
    }

    if (activeOffers === 1) {
        return window.__I18N__.offerLastYear
    }

    return window.__I18N__.offersLastYear.replace(':count', activeOffers)
}

function technologyBadges(technologyNames) {
    const selected = selectedValues('technology_names')
    const matches = technologyNames.filter(name => selected.includes(name))
    const others = technologyNames.filter(name => !selected.includes(name))
    const visible = [...matches, ...others].slice(0, Math.max(VISIBLE_TECHNOLOGIES, matches.length))
    const hiddenCount = technologyNames.length - visible.length

    const badges = visible.map(name => {
        const modifier = selected.includes(name) ? ' hit-card__tech--match' : ''

        return `<span class="hit-card__tech${modifier}">${escapeHtml(name)}</span>`
    })

    if (hiddenCount > 0) {
        badges.push(`<span class="hit-card__tech hit-card__tech--more">${window.__I18N__.moreTechnologies.replace(':count', hiddenCount)}</span>`)
    }

    return badges.join('')
}

function selectedValues(attribute) {
    return search.getUiState()[INDEX_NAME]?.refinementList?.[attribute] ?? []
}

function selectOnly(attribute, value) {
    search.setUiState(uiState => {
        const indexState = uiState[INDEX_NAME] ?? {}

        return {
            ...uiState,
            [INDEX_NAME]: {
                ...indexState,
                page: 1,
                refinementList: {
                    ...indexState.refinementList,
                    [attribute]: value ? [value] : [],
                },
            },
        }
    })
}

function escapeHtml(text) {
    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }

    return String(text ?? '').replace(/[&<>"']/g, character => entities[character])
}

let trackedTechs = new Set()
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

function trackNewTechs(techs, province) {
    if (window.__IS_ADMIN__) return

    const newTechs = techs.filter(t => !trackedTechs.has(t))
    if (!newTechs.length) return

    newTechs.forEach(t => trackedTechs.add(t))

    fetch(window.__TRACK_SEARCH_URL__, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ technologies: newTechs, province: province || null }),
        keepalive: true,
    }).catch(() => {})
}

search.on('render', () => {
    const state = search.getUiState()['devstack_companies'] || {}
    const techs = state.refinementList?.technology_names || []
    const provs  = state.refinementList?.province_name || []

    trackNewTechs(techs, provs[0])

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
