<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryInsightsController extends Controller
{
    public function __invoke(Request $request)
    {
        $techNames = array_filter((array) $request->input('tech', []));

        if (empty($techNames)) {
            return response()->json([]);
        }

        $results = DB::table('technologies')
            ->join('job_offer_technology', 'technologies.id', '=', 'job_offer_technology.technology_id')
            ->join('job_offers', 'job_offers.id', '=', 'job_offer_technology.job_offer_id')
            ->whereIn('technologies.name', $techNames)
            ->where('job_offers.salary_min', '>=', 10000)
            ->select([
                'technologies.name',
                DB::raw('MIN(job_offers.salary_min) as range_min'),
                DB::raw('MAX(job_offers.salary_max) as range_max'),
                DB::raw('COUNT(DISTINCT job_offers.id) as offers_count'),
            ])
            ->groupBy('technologies.id', 'technologies.name')
            ->having('offers_count', '>=', 3)
            ->get();

        return response()->json($results);
    }
}
