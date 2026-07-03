<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>TuStack — Coming soon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-gray-50 font-[Instrument_Sans] antialiased">
    <div class="flex h-full flex-col items-center justify-center px-6">

        <div class="w-full max-w-sm">
            {{-- Logo --}}
            <div class="mb-8 flex justify-center">
                <span class="text-2xl font-bold tracking-tight">
                    <span class="text-gray-900">Tu</span><span class="text-indigo-500">Stack</span>
                </span>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl border border-gray-200 bg-white px-8 py-10 shadow-sm text-center">
                <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50">
                    <svg class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <h1 class="text-lg font-semibold text-gray-900">We'll be right back</h1>
                <p class="mt-2 text-sm text-gray-500 leading-relaxed">
                    TuStack is temporarily unavailable.<br>We're working on it and will be back soon.
                </p>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-gray-400">
                © {{ date('Y') }} TuStack by
                <a href="https://prodelpe.com" target="_blank" rel="noopener" class="underline underline-offset-2 hover:text-gray-600 transition">prodelpe.com</a>
            </p>
        </div>

    </div>
</body>
</html>
