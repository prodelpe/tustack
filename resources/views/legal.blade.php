@extends('layouts.app')

@section('title', $seoTitle)
@section('description', $seoDescription)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <x-breadcrumbs :trail="$breadcrumbs" />

    <article class="legal-prose">
        {!! $body !!}
    </article>

    <p class="mt-10 border-t border-gray-200 pt-6 text-sm text-gray-500 dark:border-slate-800 dark:text-slate-400">
        {{ __('legal.updated_at', ['date' => $updatedAt->isoFormat('LL')]) }}
    </p>
</div>
@endsection
