@php
    $locales = LaravelLocalization::getSupportedLocales();
    $regional = $locales[app()->getLocale()]['regional'] ?? app()->getLocale();
@endphp

<title>@yield('title', __('seo.default_title')) · TuStack</title>
<meta name="description" content="@yield('description', __('seo.default_description'))">
<link rel="canonical" href="{{ url()->current() }}">

@if(config('app.noindex'))
    <meta name="robots" content="noindex, nofollow">
@endif

@foreach($locales as $localeCode => $properties)
    <link rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
@endforeach

<meta property="og:site_name" content="TuStack">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="{{ $regional }}">
<meta property="og:title" content="@yield('title', __('seo.default_title'))">
<meta property="og:description" content="@yield('description', __('seo.default_description'))">
<meta property="og:image" content="{{ asset('images/og-default.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('title', __('seo.default_title'))">
<meta name="twitter:description" content="@yield('description', __('seo.default_description'))">
<meta name="twitter:image" content="{{ asset('images/og-default.png') }}">
