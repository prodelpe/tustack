@props([
    'label',
    'active',
    'enableRoute',
    'disableRoute',
    'color' => 'green',
    'openBlank' => false,
])

@php
    $colors = [
        'green' => ['border' => 'border-green-200 bg-green-50 dark:border-green-900/40 dark:bg-green-950/20', 'dot' => 'bg-green-500', 'text' => 'text-green-700 dark:text-green-400'],
        'blue'  => ['border' => 'border-blue-200 bg-blue-50 dark:border-blue-900/40 dark:bg-blue-950/20',   'dot' => 'bg-blue-500',  'text' => 'text-blue-700 dark:text-blue-400'],
    ];

    $borderClass = $active ? $colors[$color]['border'] : 'border-gray-200 bg-gray-50 dark:border-slate-800 dark:bg-slate-900';
    $dotClass    = $active ? $colors[$color]['dot']    : 'bg-gray-400 dark:bg-slate-600';
    $textClass   = $active ? $colors[$color]['text']   : 'text-gray-500 dark:text-slate-400';
@endphp

<div class="flex items-center justify-between rounded-xl border px-5 py-4 {{ $borderClass }}">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
            <span class="text-xs font-medium text-gray-500 dark:text-slate-400">{{ $label }}</span>
        </div>
        <span class="text-sm {{ $textClass }}">{{ $slot }}</span>
    </div>

    @if ($active)
        <form method="POST" action="{{ $disableRoute }}" class="flex items-center">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-slate-200 transition">
                {{ $active ? 'Disable' : 'Enable' }}
            </button>
        </form>
    @else
        <form method="POST" action="{{ $enableRoute }}" class="flex items-center" @if($openBlank) target="_blank" @endif>
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-slate-200 transition">
                {{ $active ? 'Disable' : 'Enable' }}
            </button>
        </form>
    @endif
</div>
