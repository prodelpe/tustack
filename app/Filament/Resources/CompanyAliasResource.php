<?php

namespace App\Filament\Resources;

use App\Actions\MergeCompaniesAction;
use App\Filament\Resources\CompanyAliasResource\Pages;
use App\Models\CompanyAlias;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyAliasResource extends Resource
{
    protected static ?string $model = CompanyAlias::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-pointing-in';

    protected static ?string $modelLabel = 'company alias';

    protected static ?string $navigationLabel = 'Company aliases';

    public static function getNavigationBadge(): ?string
    {
        $pending = CompanyAlias::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Kept')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Same as')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        CompanyAlias::APPROVED => 'success',
                        CompanyAlias::REJECTED => 'gray',
                        default                => 'warning',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        CompanyAlias::PENDING  => 'Pending',
                        CompanyAlias::APPROVED => 'Approved',
                        CompanyAlias::REJECTED => 'Rejected',
                    ])
                    ->default(CompanyAlias::PENDING),
            ])
            ->actions([
                Tables\Actions\Action::make('merge')
                    ->label('Same company')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('The offers move to the name that is kept and the other company disappears. This can be undone.')
                    ->visible(fn (CompanyAlias $record) => $record->status === CompanyAlias::PENDING)
                    ->action(function (CompanyAlias $record) {
                        $absorbed = $record->absorbedCompany();

                        if (! $absorbed || ! $record->company) {
                            Notification::make()->danger()->title('One of the two companies no longer exists.')->send();

                            return;
                        }

                        app(MergeCompaniesAction::class)->handle($absorbed, $record->company);

                        Notification::make()->success()->title('Merged into ' . $record->company->name)->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Different')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (CompanyAlias $record) => $record->status === CompanyAlias::PENDING)
                    ->action(function (CompanyAlias $record) {
                        $record->update(['status' => CompanyAlias::REJECTED, 'source' => 'admin']);

                        Notification::make()->success()->title('Noted. This pair will not be asked about again.')->send();
                    }),

                Tables\Actions\Action::make('undo')
                    ->label('Undo merge')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CompanyAlias $record) => $record->status === CompanyAlias::APPROVED)
                    ->action(function (CompanyAlias $record) {
                        $company = app(MergeCompaniesAction::class)->undo($record);

                        Notification::make()->success()->title($company->name . ' is a company of its own again.')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyAliases::route('/'),
        ];
    }
}
