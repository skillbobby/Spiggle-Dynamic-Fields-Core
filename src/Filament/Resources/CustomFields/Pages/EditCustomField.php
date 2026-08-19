<?php

namespace Spiggle\DynamicFields\Filament\Resources\CustomFields\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\CustomFieldResource;

class EditCustomField extends EditRecord
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
