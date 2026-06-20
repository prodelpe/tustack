import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { trendChart } from './trend-chart';

Alpine.data('trendChart', trendChart(Chart));

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
