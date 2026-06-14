<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Search alerts</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">
            Receive an email when new companies matching your saved searches appear.
        </p>
    </header>

    <form method="POST" action="{{ route('profile.alerts') }}" class="mt-6 space-y-6">
        @csrf
        @method('PATCH')

        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                name="alerts_enabled"
                value="1"
                {{ auth()->user()->alerts_enabled ? 'checked' : '' }}
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900"
            >
            <span class="text-sm text-gray-700 dark:text-slate-300">Enable email alerts for saved searches</span>
        </label>

        <div>
            <x-primary-button>Save preference</x-primary-button>
        </div>

        @if (session('status') === 'alerts-updated')
            <p class="text-sm text-green-600 dark:text-green-400">Saved.</p>
        @endif
    </form>

    @if ($savedSearches->isNotEmpty())
        <div class="mt-8">
            <p class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-3">Saved searches</p>
            <ul class="space-y-2">
                @foreach ($savedSearches as $search)
                    <li class="flex items-center justify-between text-sm text-gray-600 dark:text-slate-400">
                        <span>{{ $search->describe() }}</span>
                        <form method="POST" action="{{ route('saved-searches.destroy', $search) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
