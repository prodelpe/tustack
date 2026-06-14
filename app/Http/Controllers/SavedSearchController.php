<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'technologies' => ['array'],
            'provinces'    => ['array'],
            'query'        => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();

        $filters = [
            'technologies' => $validated['technologies'] ?? [],
            'provinces'    => $validated['provinces'] ?? [],
            'query'        => $validated['query'] ?? '',
        ];

        $hash = md5(json_encode($filters));

        $alreadySaved = $user->savedSearches()
            ->where('filters_hash', $hash)
            ->exists();

        if ($alreadySaved) {
            return response()->json(['saved' => false, 'duplicate' => true]);
        }

        $user->savedSearches()->create(['filters' => $filters, 'filters_hash' => $hash]);

        if (! $user->alerts_enabled) {
            $user->update(['alerts_enabled' => true]);
        }

        return response()->json(['saved' => true]);
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless(auth()->id() === $savedSearch->user_id, 403);

        $savedSearch->delete();

        return back();
    }

    public function unsubscribe(Request $request, User $user): View
    {
        $user->update(['alerts_enabled' => false]);

        return view('alerts.unsubscribed');
    }
}
