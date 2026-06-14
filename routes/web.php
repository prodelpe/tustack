<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\SavedCompanyController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
Route::get('/companies/{company}/offers/{jobOffer}', [JobOfferController::class, 'show'])->name('companies.offers.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/dashboard/companies', [SavedCompanyController::class, 'index'])->name('dashboard.companies');
    Route::post('/companies/{company}/save', [SavedCompanyController::class, 'toggle'])->name('companies.save');
});

require __DIR__.'/auth.php';
