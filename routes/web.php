<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/dashboard/companies', fn () => view('dashboard.companies'))->name('dashboard.companies');
});

require __DIR__.'/auth.php';
