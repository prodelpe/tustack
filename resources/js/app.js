import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { trendChart } from './trend-chart';

Alpine.data('trendChart', trendChart(Chart));

// Counted so that closing one panel does not unlock scrolling while another
// one is still open.
let scrollLocks = 0

function lockScroll(locked) {
    scrollLocks = Math.max(0, scrollLocks + (locked ? 1 : -1))
    document.body.classList.toggle('overflow-hidden', scrollLocks > 0)
}

Alpine.data('filterPanel', () => ({
    open: false,
    count: 0,
    init() {
        // The search widgets already broadcast the active refinements, so the
        // badge on the trigger stays in sync without touching search logic.
        window.addEventListener('search-updated', (event) => {
            const { technologies = [], provinces = [], query = '' } = event.detail || {}
            this.count = technologies.length + provinces.length + (query ? 1 : 0)
        })

        this.$watch('open', lockScroll)
    },
    toggle() {
        this.open = !this.open
    },
    close() {
        this.open = false
    },
}));

Alpine.data('navMenu', () => ({
    open: false,
    init() {
        this.$watch('open', lockScroll)
    },
    toggle() {
        this.open = !this.open
    },
    close() {
        this.open = false
    },
}));

Alpine.data('themeToggle', () => ({
    theme: localStorage.getItem('theme') ?? 'system',
    init() {
        this.$watch('theme', () => this.apply())
        this.apply()
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (this.theme === 'system') this.apply()
        })
    },
    setTheme(val) {
        this.theme = val
        localStorage.setItem('theme', val)
    },
    apply() {
        const isDark = this.theme === 'dark' ||
            (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
        document.documentElement.classList.toggle('dark', isDark)
    },
}));

Alpine.start();
