<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SavedCompanyController extends Controller
{
    public function toggle(Company $company): JsonResponse
    {
        $user = Auth::user();
        $user->savedCompanies()->toggle($company->id);
        $saved = $user->savedCompanies()->where('company_id', $company->id)->exists();

        return response()->json(['saved' => $saved]);
    }

    public function index()
    {
        $companies = Auth::user()
            ->savedCompanies()
            ->with(['province', 'jobOffers.technologies'])
            ->latest('company_user.created_at')
            ->get();

        return view('dashboard.companies', compact('companies'));
    }
}
