<?php

namespace App\Http\Controllers;

use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackTechnologySearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! app()->isProduction()) {
            return response()->noContent();
        }

        if (auth()->check() && auth()->user()->is_admin) {
            return response()->noContent();
        }

        $data = $request->validate([
            'technologies'   => ['required', 'array', 'max:20'],
            'technologies.*' => ['required', 'string', 'max:100'],
        ]);

        Technology::whereIn('name', $data['technologies'])->increment('searches_count');

        return response()->noContent();
    }
}
