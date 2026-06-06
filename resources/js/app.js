import './bootstrap';
import Alpine from 'alpinejs';

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

Alpine.data('homeFilters', ({ techOptions, provinceOptions }) => ({
    techOptions,
    provinceOptions,
    techSelected: [],
    techSearch: '',
    techOpen: false,
    provinceSelected: [],
    provinceSearch: '',
    provinceOpen: false,

    get filteredTechs() {
        return this.techOptions.filter(o => o.label.toLowerCase().includes(this.techSearch.toLowerCase()))
    },
    get filteredProvinces() {
        return this.provinceOptions.filter(o => o.label.toLowerCase().includes(this.provinceSearch.toLowerCase()))
    },
    isTechSelected(v) { return this.techSelected.includes(v) },
    isProvinceSelected(v) { return this.provinceSelected.includes(v) },
    toggleTech(v) {
        this.techSelected = this.isTechSelected(v)
            ? this.techSelected.filter(x => x !== v)
            : [...this.techSelected, v]
    },
    toggleProvince(v) {
        this.provinceSelected = this.isProvinceSelected(v)
            ? this.provinceSelected.filter(x => x !== v)
            : [...this.provinceSelected, v]
    },
    removeTech(v) { this.techSelected = this.techSelected.filter(x => x !== v) },
    removeProvince(v) { this.provinceSelected = this.provinceSelected.filter(x => x !== v) },
    labelFor(options, v) { return options.find(o => o.value === v)?.label ?? v },
}));

Alpine.data('multiselect', ({ options, selected = [] }) => ({
    options,
    selected,
    search: '',
    open: false,

    get filtered() {
        return this.options.filter(o =>
            o.label.toLowerCase().includes(this.search.toLowerCase())
        );
    },

    isSelected(value) {
        return this.selected.includes(value);
    },

    toggle(value) {
        if (this.isSelected(value)) {
            this.selected = this.selected.filter(v => v !== value);
        } else {
            this.selected.push(value);
        }
    },

    remove(value) {
        this.selected = this.selected.filter(v => v !== value);
    },

    labelFor(value) {
        return this.options.find(o => o.value === value)?.label ?? value;
    },
}));

Alpine.start();
