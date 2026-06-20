<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TendencyDataController extends Controller
{
    public function __invoke(Request $request)
    {
        $techNames = array_filter((array) $request->input('tech', []));
        $techIds   = $this->resolveTechIds($techNames);

        if ($techIds->isEmpty()) {
            return response()->json(['labels' => [], 'datasets' => []]);
        }

        $months   = $this->buildMonthRange();
        $rows     = $this->fetchMonthlyOfferCounts($techIds);
        $datasets = $this->buildDatasets($rows, $months, $techNames);

        return response()->json([
            'labels'   => $months->values()->all(),
            'datasets' => $datasets,
        ]);
    }

    private function resolveTechIds(array $techNames): Collection
    {
        if (!empty($techNames)) {
            return DB::table('technologies')
                ->whereIn('name', $techNames)
                ->pluck('id');
        }

        return DB::table('job_offer_technology as jot')
            ->join('job_offers as jo', 'jo.id', '=', 'jot.job_offer_id')
            ->whereNotNull('jo.published_at')
            ->where('jo.published_at', '>=', now()->subYear())
            ->groupBy('jot.technology_id')
            ->orderByDesc(DB::raw('COUNT(DISTINCT jo.id)'))
            ->limit(8)
            ->pluck('jot.technology_id');
    }

    private function buildMonthRange(): Collection
    {
        $months = collect();
        $cursor = now()->subYear()->startOfMonth();

        while ($cursor->lte(now()->startOfMonth())) {
            $months->push($cursor->format('Y-m'));
            $cursor->addMonth();
        }

        return $months;
    }

    private function fetchMonthlyOfferCounts(Collection $techIds): Collection
    {
        return DB::table('technologies as t')
            ->join('job_offer_technology as jot', 'jot.technology_id', '=', 't.id')
            ->join('job_offers as jo', 'jo.id', '=', 'jot.job_offer_id')
            ->whereIn('t.id', $techIds)
            ->whereNotNull('jo.published_at')
            ->where('jo.published_at', '>=', now()->subYear())
            ->selectRaw("t.name, DATE_FORMAT(jo.published_at, '%Y-%m') as month, COUNT(DISTINCT jo.id) as count")
            ->groupBy('t.id', 't.name', 'month')
            ->orderBy('month')
            ->get();
    }

    private function buildDatasets(Collection $rows, Collection $months, array $techNames): array
    {
        $grouped = $rows->groupBy('name');

        $order = !empty($techNames)
            ? collect($techNames)->filter(fn($n) => $grouped->has($n))
            : $grouped->keys();

        return $order->map(fn($name) => [
            'label' => $name,
            'data'  => $this->fillMonthGaps($grouped[$name], $months),
        ])->values()->all();
    }

    private function fillMonthGaps(Collection $techRows, Collection $months): array
    {
        $byMonth = $techRows->keyBy('month');

        return $months
            ->map(fn($m) => (int) ($byMonth->get($m)?->count ?? 0))
            ->values()
            ->all();
    }
}
