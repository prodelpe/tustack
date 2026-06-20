@props(['type'])

@php
    $styles = match($type) {
        'consultancy' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
        'recruitment' => 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-500/20 dark:bg-teal-500/10 dark:text-teal-300',
        default       => '',
    };

    $label = match($type) {
        'consultancy' => 'Consultoria',
        'recruitment' => 'Recruitment',
        default       => '',
    };
@endphp

@if($label)
    <span {{ $attributes->merge(['class' => "rounded-full border px-2.5 py-0.5 text-xs font-medium $styles"]) }}>
        {{ $label }}
    </span>
@endif
