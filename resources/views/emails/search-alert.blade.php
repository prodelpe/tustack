@component('mail::message')
# {{ $companies->count() }} new {{ Str::plural('company', $companies->count()) }} match your search

Hi {{ $user->name }},

We found **{{ $companies->count() }}** new {{ Str::plural('company', $companies->count()) }} matching **{{ $savedSearch->describe() }}**:

@foreach ($companies as $company)
**[{{ $company->display_name }}]({{ route('companies.show', $company) }})**
{{ collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ') }}

@endforeach

@component('mail::button', ['url' => $searchUrl])
View all results
@endcomponent

---

You're receiving this because you saved a search alert on TuStack.
[Unsubscribe from alerts]({{ $unsubscribeUrl }})
@endcomponent
