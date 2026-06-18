<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Companies', Company::count()),
            Stat::make('Job Offers', JobOffer::count())
                ->description(JobOffer::where('created_at', '>=', now()->subDays(7))->count() . ' this week'),
            Stat::make('Technologies', Technology::count()),
            Stat::make('Users', User::count())
                ->description(User::whereNotNull('telegram_chat_id')->count() . ' with Telegram'),
        ];
    }
}
