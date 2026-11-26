<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;

class LegacyCompanyRedirectController extends Controller
{
    public function __invoke(string $id): RedirectResponse
    {
        return redirect()->route('companies.show', Company::findOrFail($id), 301);
    }
}
