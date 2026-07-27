<div {{ $attributes->merge(['class' => 'flex items-center gap-0.5 rounded-full border border-gray-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-800']) }}>
    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
        <a
            href="{{ LaravelLocalization::getLocalizedURL($localeCode) }}"
            class="flex-1 rounded-full px-2 py-0.5 text-center text-xs font-medium transition {{ app()->getLocale() === $localeCode ? 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300' }}"
        >{{ strtoupper($localeCode) }}</a>
    @endforeach
</div>
