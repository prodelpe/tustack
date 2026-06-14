<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedCompanyController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

Route::get('/alerts/unsubscribe/{user}', [SavedSearchController::class, 'unsubscribe'])
    ->name('alerts.unsubscribe')
    ->middleware('signed');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        return view('dashboard', [
            'savedSearches'  => $user->savedSearches()->latest()->get(),
            'alertsEnabled'  => $user->alerts_enabled,
        ]);
    })->name('dashboard');
    Route::get('/dashboard/companies', [SavedCompanyController::class, 'index'])->name('dashboard.companies');
    Route::post('/companies/{company}/save', [SavedCompanyController::class, 'toggle'])->name('companies.save');


    Route::post('/alerts/enable', function () {
        auth()->user()->update(['alerts_enabled' => true]);
        return redirect()->route('dashboard');
    })->name('alerts.enable');

    Route::post('/alerts/disable', function () {
        auth()->user()->update(['alerts_enabled' => false]);
        return redirect()->route('dashboard');
    })->name('alerts.disable');

    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
});

require __DIR__.'/auth.php';
