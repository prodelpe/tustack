@props(['title'])

<main class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex gap-10">
        <x-dashboard-aside />
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">{{ $title }}</h1>
            {{ $slot }}
        </div>
    </div>
</main>
