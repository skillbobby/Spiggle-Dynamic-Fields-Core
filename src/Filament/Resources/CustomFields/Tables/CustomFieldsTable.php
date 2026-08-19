<?php

namespace Spiggle\DynamicFields\Filament\Resources\CustomFields\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spiggle\DynamicFields\Filament\Support\ProUpsell;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Services\ModelDiscoverer;
use Spiggle\DynamicFields\Support\FeatureCatalog;
use Spiggle\DynamicFields\Support\FieldTypes;

class CustomFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FieldTypes::labels()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('target_model')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->tooltip(fn (CustomField $record): string => $record->target_model)
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('Options'),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('target_model')
                    ->label('Target Model')
                    ->options(fn () => app(ModelDiscoverer::class)->discover()),
                SelectFilter::make('type')
                    ->options(FieldTypes::labels()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('clone')
                    ->label(fn (): string => FeatureCatalog::proUnlocked() ? 'Clone' : 'Clone · PRO')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation(fn (): bool => FeatureCatalog::proUnlocked())
                    ->action(function (CustomField $record): void {
                        if (! FeatureCatalog::proUnlocked()) {
                            ProUpsell::notify('Clone field');

                            return;
                        }

                        $clone = $record->cloneDefinition();

                        Notification::make()
                            ->title('Field cloned')
                            ->body("Created {$clone->label} ({$clone->name}).")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
