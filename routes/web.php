<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConnectTelegramController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisableAlertsController;
use App\Http\Controllers\DisconnectTelegramController;
use App\Http\Controllers\EnableAlertsController;
use App\Http\Controllers\LegacyCompanyRedirectController;
use App\Support\LegalDocument;
use App\Support\SeoRoutePatterns;
use App\Http\Controllers\HumansController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SavedCompanyController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\SalaryInsightsController;
use App\Http\Controllers\TendenciesController;
use App\Http\Controllers\TendencyDataController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TrackSearchController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::get('/humans.txt', HumansController::class)->name('humans');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');
Route::post('/track-search', TrackSearchController::class)->middleware('throttle:30,1')->name('track-search');

Route::group([
    'prefix'     => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {
    Route::get('/', [SearchController::class, 'home'])->name('home');
    Route::get(LaravelLocalization::transRoute('routes.map'), [SearchController::class, 'map'])->name('map');
    // Legacy numeric urls kept alive: alerts sent before the slugs existed
    // still point here. Registered first so it wins over the English path.
    Route::get('/companies/{id}', LegacyCompanyRedirectController::class)->whereNumber('id');

    Route::get(LaravelLocalization::transRoute('routes.companies'), [CompanyController::class, 'show'])
        ->name('companies.show');

    // Two parameters first: otherwise empresas-laravel-madrid would match the
    // single-parameter route with the province glued to the technology.
    Route::get(LaravelLocalization::transRoute('routes.technology_province'), [LandingController::class, 'technologyInProvince'])
        ->where(['technology' => SeoRoutePatterns::technologies(), 'province' => SeoRoutePatterns::provinces()])
        ->name('landing.technology-province');

    Route::get(LaravelLocalization::transRoute('routes.technology'), [LandingController::class, 'technology'])
        ->where(['technology' => SeoRoutePatterns::technologies()])
        ->name('landing.technology');

    foreach (LegalDocument::DOCUMENTS as $document) {
        Route::get(LaravelLocalization::transRoute('routes.' . $document), [LegalController::class, 'show'])
            ->defaults('document', $document)
            ->name('legal.' . $document);
    }

    Route::get('/salary-insights', SalaryInsightsController::class)->name('salary-insights');
    Route::get(LaravelLocalization::transRoute('routes.tendencies'), TendenciesController::class)->name('tendencies');
    Route::get('/tendency-data', TendencyDataController::class)->name('tendency-data');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

    Route::get('/alerts/unsubscribe/{user}', [SavedSearchController::class, 'unsubscribe'])
        ->name('alerts.unsubscribe')
        ->middleware('signed');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/dashboard/companies', [SavedCompanyController::class, 'index'])->name('dashboard.companies');
        Route::post('/companies/{company}/save', [SavedCompanyController::class, 'toggle'])->name('companies.save');

        Route::post('/alerts/enable', EnableAlertsController::class)->name('alerts.enable');

        Route::post('/alerts/disable', DisableAlertsController::class)->name('alerts.disable');

        Route::post('/telegram/connect', ConnectTelegramController::class)->name('telegram.connect');

        Route::post('/telegram/disconnect', DisconnectTelegramController::class)->name('telegram.disconnect');

        Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
        Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
    });
});

require __DIR__.'/auth.php';
