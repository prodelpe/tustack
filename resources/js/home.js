import TomSelect from 'tom-select'
import 'tom-select/dist/css/tom-select.css'

const commonConfig = {
    plugins: ['remove_button'],
    maxOptions: null,
    closeAfterSelect: false,
    onInitialize() {
        this.wrapper.classList.add('ts-initialized')
    },
}

new TomSelect('#select-technologies', {
    ...commonConfig,
    placeholder: 'Any stack',
})

new TomSelect('#select-provinces', {
    ...commonConfig,
    placeholder: 'Anywhere',
})

document.getElementById('home-form').addEventListener('submit', function (e) {
    e.preventDefault()

    const techs = Array.from(this.querySelectorAll('[name="technologies[]"] option:checked, [data-tech]'))
    const url = new URL('/search', window.location.origin)

    const techSelect = this.querySelector('#select-technologies')
    const provSelect = this.querySelector('#select-provinces')

    const techInstance = techSelect?.tomselect
    const provInstance = provSelect?.tomselect

    const selectedTechs = techInstance ? techInstance.getValue() : []
    const selectedProvs = provInstance ? provInstance.getValue() : []

    if (selectedTechs.length) url.searchParams.set('technologies', selectedTechs.join(','))
    if (selectedProvs.length) url.searchParams.set('provinces', selectedProvs.join(','))

    window.location.href = url.toString()
})
