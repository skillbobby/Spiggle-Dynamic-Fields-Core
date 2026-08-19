<?php

namespace Spiggle\DynamicFields\Filament\Resources\CustomFields;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\Pages\CreateCustomField;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\Pages\EditCustomField;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\Pages\ListCustomFields;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\Schemas\CustomFieldForm;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\Tables\CustomFieldsTable;
use Spiggle\DynamicFields\Models\CustomField;
use UnitEnum;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('dynamic-fields.navigation.group', 'System');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('dynamic-fields.navigation.sort', 90);
    }

    public static function getNavigationLabel(): string
    {
        return 'Custom Fields';
    }

    public static function getModelLabel(): string
    {
        return 'Custom Field';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Custom Fields';
    }

    public static function form(Schema $schema): Schema
    {
        return CustomFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomFieldsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFields::route('/'),
            'create' => CreateCustomField::route('/create'),
            'edit' => EditCustomField::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $permission = config('dynamic-fields.permissions.manage', 'manage_custom_fields');

        if (method_exists($user, 'can') && method_exists($user, 'hasRole')) {
            try {
                if ($user->can($permission) || $user->hasRole('super_admin')) {
                    return true;
                }

                // If Shield permissions have not been generated yet, allow
                // authenticated panel access so the package remains usable.
                if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
                    return true;
                }

                $exists = \Spatie\Permission\Models\Permission::query()
                    ->where('name', $permission)
                    ->exists();

                return ! $exists;
            } catch (\Throwable) {
                return true;
            }
        }

        return true;
    }
}
