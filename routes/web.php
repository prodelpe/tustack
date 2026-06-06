<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/search', [SearchController::class, 'results'])->name('search');
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
