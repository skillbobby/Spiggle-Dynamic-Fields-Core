<?php

namespace Spiggle\DynamicFields\Services;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Support\DynamicFieldRegistry;
use Spiggle\DynamicFields\Support\FieldTypes;
use Spiggle\DynamicFields\Support\ValueCaster;
use Throwable;

class CustomFieldMapper
{
    /**
     * Build Filament form components for a model class.
     *
     * @return array<int, mixed>
     */
    public function makeFormFields(string $modelClass): array
    {
        try {
            $registry = $this->registry();

            $stateKey = config('dynamic-fields.form_state_key', 'custom_fields');

            return $this->definitions($modelClass)
                ->map(fn (CustomField $field) => $this->makeFormComponent($field, $stateKey, $registry))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, mixed>
     */
    public function makeTableColumns(string $modelClass): array
    {
        try {
            $registry = $this->registry();

            return $this->definitions($modelClass)
                ->map(fn (CustomField $field) => $this->makeTableColumn($field, $registry))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, mixed>
     */
    public function makeInfolistEntries(string $modelClass): array
    {
        try {
            $registry = $this->registry();

            return $this->definitions($modelClass)
                ->map(fn (CustomField $field) => $this->makeInfolistEntry($field, $registry))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function registry(): DynamicFieldRegistry
    {
        $registry = app(DynamicFieldRegistry::class);
        $registry->syncProDrivers();

        return $registry;
    }

    /**
     * @return Collection<int, CustomField>
     */
    public function definitions(string $modelClass): Collection
    {
        return FieldDefinitionCache::forModel($modelClass);
    }

    protected function makeFormComponent(CustomField $field, string $stateKey, DynamicFieldRegistry $registry): mixed
    {
        if (! $registry->allows($field)) {
            return null;
        }

        $name = $stateKey.'.'.$field->name;
        $override = $registry->form($field->type);
        $component = $override
            ? $override($name, $field)
            : $this->makeCoreFormComponent($name, $field);

        if ($component === null) {
            return null;
        }

        $component
            ->label($field->label)
            ->required((bool) $field->is_required);

        $rules = $field->validation_rules ?? [];
        if (is_array($rules) && $rules !== []) {
            $component->rules($rules);
        }

        if ($hint = data_get($field->meta, 'hint')) {
            $component->helperText((string) $hint);
        }

        if ($placeholder = data_get($field->meta, 'placeholder')) {
            if (method_exists($component, 'placeholder')) {
                $component->placeholder((string) $placeholder);
            }
        }

        $registry->applyVisibility($component, $field, $stateKey);

        if ($field->type !== 'file') {
            $component->afterStateHydrated(function ($component, ?Model $record) use ($field): void {
                if (! $record || ! method_exists($record, 'getCustomFieldValue')) {
                    return;
                }

                $component->state($record->getCustomFieldValue($field->name));
            });
        }

        return $component;
    }

    protected function makeCoreFormComponent(string $name, CustomField $field): mixed
    {
        return match ($field->type) {
            'textarea' => Textarea::make($name)->rows((int) data_get($field->meta, 'rows', 3)),
            'select' => Select::make($name)->options($field->optionMap())->native(false),
            'radio' => Radio::make($name)->options($field->optionMap()),
            'date' => DatePicker::make($name)->native(false),
            'datetime' => DateTimePicker::make($name)->native(false),
            'boolean', 'toggle' => Toggle::make($name),
            'number' => TextInput::make($name)->numeric(),
            'email' => TextInput::make($name)->email(),
            'phone' => TextInput::make($name)->tel(),
            'url' => TextInput::make($name)->url(),
            default => TextInput::make($name),
        };
    }

    protected function makeTableColumn(CustomField $field, DynamicFieldRegistry $registry): mixed
    {
        if (! $registry->allows($field)) {
            return null;
        }

        $override = $registry->table($field->type);
        if ($override) {
            return $override($field);
        }

        $columnName = 'custom_fields.'.$field->name;

        if (FieldTypes::isBoolean($field->type)) {
            return IconColumn::make($columnName)
                ->label($field->label)
                ->boolean()
                ->getStateUsing(fn (Model $record) => (bool) (
                    method_exists($record, 'getCustomFieldValue')
                        ? $record->getCustomFieldValue($field->name)
                        : null
                ));
        }

        return TextColumn::make($columnName)
            ->label($field->label)
            ->toggleable()
            ->getStateUsing(function (Model $record) use ($field) {
                return $this->formatScalarState($record, $field);
            })
            ->searchable(query: function (Builder $query, string $search) use ($field): Builder {
                return $query->whereHas('customFieldValues', function (Builder $q) use ($field, $search): void {
                    $q->where('custom_field_id', $field->id)
                        ->where('value', 'like', '%'.$search.'%');
                });
            });
    }

    protected function makeInfolistEntry(CustomField $field, DynamicFieldRegistry $registry): mixed
    {
        if (! $registry->allows($field)) {
            return null;
        }

        $override = $registry->infolist($field->type);
        if ($override) {
            $entry = $override($field);
            if ($entry !== null) {
                return $entry;
            }
        }

        $name = 'custom_fields.'.$field->name;

        if (FieldTypes::isBoolean($field->type)) {
            return IconEntry::make($name)
                ->label($field->label)
                ->boolean()
                ->getStateUsing(fn (Model $record) => (bool) (
                    method_exists($record, 'getCustomFieldValue')
                        ? $record->getCustomFieldValue($field->name)
                        : null
                ));
        }

        return TextEntry::make($name)
            ->label($field->label)
            ->placeholder('—')
            ->getStateUsing(function (Model $record) use ($field) {
                return $this->formatScalarState($record, $field);
            });
    }

    protected function formatScalarState(Model $record, CustomField $field): ?string
    {
        if (! method_exists($record, 'getCustomFieldValue')) {
            return null;
        }

        $value = $record->getCustomFieldValue($field->name);

        if ($value === null) {
            return null;
        }

        if (FieldTypes::requiresOptions($field->type) && ! FieldTypes::storesArray($field->type)) {
            return $field->optionMap()[(string) $value] ?? (is_scalar($value) ? (string) $value : null);
        }

        if ($value instanceof \DateTimeInterface) {
            return $field->type === 'date'
                ? $value->format('Y-m-d')
                : $value->format('Y-m-d H:i');
        }

        return is_scalar($value) ? (string) $value : ValueCaster::serialize($value);
    }
}
