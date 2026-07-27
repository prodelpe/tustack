@props(['trail' => []])

@if(count($trail) > 1)
    <nav aria-label="{{ __('nav.breadcrumb') }}" class="mb-5 text-sm text-gray-500 dark:text-slate-400">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
            @foreach($trail as $index => $crumb)
                <li class="flex items-center gap-x-2">
                    @if($index > 0)
                        <span aria-hidden="true" class="text-gray-300 dark:text-slate-600">/</span>
                    @endif

                    @if($loop->last)
                        <span class="text-gray-700 dark:text-slate-300" aria-current="page">{{ $crumb['label'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">{{ $crumb['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
