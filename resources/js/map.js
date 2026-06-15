import { instantMeiliSearch } from '@meilisearch/instant-meilisearch'
import instantsearch from 'instantsearch.js'
import { history } from 'instantsearch.js/es/lib/routers'
import {
    refinementList,
    clearRefinements,
    configure,
    stats,
} from 'instantsearch.js/es/widgets'
import { connectHits } from 'instantsearch.js/es/connectors'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet default marker icons with Vite
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({ iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow })

// Init map centered on Spain
const map = L.map('map').setView([40.4, -3.7], 6)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
}).addTo(map)

const markers = L.layerGroup().addTo(map)

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
                return {
                    technologies: index.refinementList?.technology_names || [],
                    provinces:    index.refinementList?.province_name || [],
                }
            },
            routeToState(routeState) {
                const toArray = v => !v ? [] : Array.isArray(v) ? v : [v]
                return {
                    'devstack_companies': {
                        refinementList: {
                            technology_names: toArray(routeState.technologies),
                            province_name:    toArray(routeState.provinces),
                        },
                    },
                }
            },
        },
    },
})

search.addWidgets([
    configure({
        hitsPerPage: 1000,
    }),

    stats({
        container: '#stats',
        templates: {
            text: ({ nbHits }) => `${nbHits} companies on map`,
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

    clearRefinements({
        container: '#clear-filters',
        templates: { resetLabel: 'Clear filters' },
    }),

    connectHits(({ hits }) => {
        markers.clearLayers()

        hits.forEach(hit => {
            if (!hit._geo) return

            const techs = (hit.technology_names || []).slice(0, 6).map(t => `<span class="map-popup__tech">${t}</span>`).join('')
            const location = [hit.city, hit.province_name].filter(Boolean).join(', ')

            const popup = `
                <div class="map-popup">
                    <a href="/companies/${hit.id}" class="map-popup__name">${hit.name}</a>
                    ${location ? `<p class="map-popup__location">${location}</p>` : ''}
                    <div class="map-popup__techs">${techs}</div>
                </div>
            `

            L.marker([hit._geo.lat, hit._geo.lng])
                .bindPopup(popup)
                .addTo(markers)
        })
    })({}),
])

search.start()

search.on('render', () => {
    const listLink = document.getElementById('list-link')
    if (listLink && window.__HOME_URL__) {
        const state = search.getUiState()['devstack_companies'] || {}
        const techs = state.refinementList?.technology_names || []
        const provs  = state.refinementList?.province_name || []
        const params = new URLSearchParams()
        techs.forEach(t => params.append('technologies', t))
        provs.forEach(p => params.append('provinces', p))
        const query = params.toString()
        listLink.href = window.__HOME_URL__ + (query ? '?' + query : '')
    }
})

document.getElementById('exclude-consultancies')?.addEventListener('change', (e) => {
    const filter = e.target.checked ? 'is_consultancy = false' : ''
    search.helper.setQueryParameter('filters', filter).search()
})
