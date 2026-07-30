<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Models\Technology;
use App\Support\Analytics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackSearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! Analytics::isEnabled()) {
            return response()->noContent();
        }

        $data = $request->validate([
            'technologies'   => ['required', 'array', 'max:20'],
            'technologies.*' => ['required', 'string', 'max:100'],
            'province'       => ['nullable', 'string', 'max:100'],
        ]);

        $technologies = Technology::whereIn('name', $data['technologies'])->pluck('name');

        if ($technologies->isEmpty()) {
            return response()->noContent();
        }

        Technology::whereIn('name', $technologies)->increment('searches_count');

        SearchLog::create([
            'technologies' => $technologies->all(),
            'province'     => $data['province'] ?? null,
        ]);

        return response()->noContent();
    }
}
