<?php

namespace Spiggle\DynamicFields\Filament\Resources\CustomFields\Pages;

use Filament\Resources\Pages\CreateRecord;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\CustomFieldResource;

class CreateCustomField extends CreateRecord
{
    protected static string $resource = CustomFieldResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }
}
