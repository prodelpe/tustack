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
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/dashboard/companies', [SavedCompanyController::class, 'index'])->name('dashboard.companies');
    Route::post('/companies/{company}/save', [SavedCompanyController::class, 'toggle'])->name('companies.save');

    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');

    Route::patch('/profile/alerts', [ProfileController::class, 'updateAlerts'])->name('profile.alerts');
});

require __DIR__.'/auth.php';
