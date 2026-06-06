import './bootstrap';
import Alpine from 'alpinejs';

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
