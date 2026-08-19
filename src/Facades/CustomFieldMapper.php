<?php

namespace Spiggle\DynamicFields\Facades;

use Illuminate\Support\Facades\Facade;
use Spiggle\DynamicFields\Services\CustomFieldMapper as CustomFieldMapperService;

/**
 * @method static array makeFormFields(string $modelClass)
 * @method static array makeTableColumns(string $modelClass)
 * @method static array makeInfolistEntries(string $modelClass)
 * @method static \Illuminate\Support\Collection definitions(string $modelClass)
 *
 * @see CustomFieldMapperService
 */
class CustomFieldMapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CustomFieldMapperService::class;
    }
}
