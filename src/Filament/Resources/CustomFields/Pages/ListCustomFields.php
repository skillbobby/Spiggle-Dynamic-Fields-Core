<?php

namespace Spiggle\DynamicFields\Filament\Resources\CustomFields\Pages;

use Filament\Resources\Pages\ListRecords;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\CustomFieldResource;

class ListCustomFields extends ListRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->modal(false)
                ->url(fn (): string => static::getResource()::getUrl('create')),
        ];
    }
}
