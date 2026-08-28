@if (App\Support\Analytics::shouldLoadUmami())
    <script defer
            src="{{ config('analytics.umami.script_url') }}"
            data-website-id="{{ config('analytics.umami.website_id') }}"></script>
@endif
