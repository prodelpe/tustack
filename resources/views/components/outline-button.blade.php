@props(['href' => null])

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg border border-gray-200 bg-white transition hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600']) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg border border-gray-200 bg-white transition hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600']) }}>
    {{ $slot }}
</button>
@endif
