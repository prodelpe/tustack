<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TendenciesController extends Controller
{
    public function __invoke()
    {
        $topTechIds = DB::table('job_offer_technology as jot')
            ->join('job_offers as jo', 'jo.id', '=', 'jot.job_offer_id')
            ->whereNotNull('jo.published_at')
            ->where('jo.published_at', '>=', now()->subYears(2))
            ->groupBy('jot.technology_id')
            ->orderByDesc(DB::raw('COUNT(DISTINCT jo.id)'))
            ->limit(8)
            ->pluck('jot.technology_id');

        $rows = DB::table('technologies as t')
            ->join('job_offer_technology as jot', 'jot.technology_id', '=', 't.id')
            ->join('job_offers as jo', 'jo.id', '=', 'jot.job_offer_id')
            ->whereIn('t.id', $topTechIds)
            ->whereNotNull('jo.published_at')
            ->where('jo.published_at', '>=', now()->subYears(2))
            ->selectRaw("t.name, DATE_FORMAT(jo.published_at, '%Y-%m') as month, COUNT(DISTINCT jo.id) as count")
            ->groupBy('t.id', 't.name', 'month')
            ->orderBy('month')
            ->get();

        $months = collect();
        $cursor = now()->subYears(2)->startOfMonth();
        while ($cursor->lte(now()->startOfMonth())) {
            $months->push($cursor->format('Y-m'));
            $cursor->addMonth();
        }

        $datasets = $rows->groupBy('name')
            ->map(function ($techRows, $name) use ($months) {
                $byMonth = $techRows->keyBy('month');
                return [
                    'label' => $name,
                    'data'  => $months->map(fn($m) => (int) ($byMonth->get($m)?->count ?? 0))->values()->all(),
                ];
            })
            ->values()
            ->all();

        $chartData = [
            'labels'   => $months->values()->all(),
            'datasets' => $datasets,
        ];

        return view('tendencies', compact('chartData'));
    }
}
