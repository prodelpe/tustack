@props(['company', 'isSaved' => false])

<div
    x-data="{
        saved: {{ $isSaved ? 'true' : 'false' }},
        async toggle() {
            const res = await fetch('{{ route('companies.save', $company) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            const data = await res.json()
            this.saved = data.saved
        }
    }"
>
    <x-outline-button
        @click="toggle"
        x-bind:title="saved ? 'Remove from My companies' : 'Save to My companies'"
        class="p-2"
    >
        <svg x-show="!saved" class="w-4 h-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
        <svg x-show="saved" class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
    </x-outline-button>
</div>
