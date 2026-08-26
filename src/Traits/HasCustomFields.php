<?php

namespace Spiggle\DynamicFields\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spiggle\DynamicFields\Events\CustomFieldValueSaved;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Models\CustomFieldValue;
use Spiggle\DynamicFields\Services\FieldDefinitionCache;
use Spiggle\DynamicFields\Support\ValueCaster;

/**
 * @mixin Model
 */
trait HasCustomFields
{
    /** @var array<string, mixed> */
    protected array $pendingCustomFieldValues = [];

    /** @var array<string, CustomFieldValue>|null */
    protected ?array $customFieldValuesByName = null;

    public function initializeHasCustomFields(): void
    {
        if (config('dynamic-fields.append_to_json', true)) {
            $this->append('custom_fields');
        }
    }

    protected static function bootHasCustomFields(): void
    {
        static::saved(function (Model $model): void {
            /** @var Model&HasCustomFields $model */
            if ($model->pendingCustomFieldValues === []) {
                return;
            }

            foreach ($model->pendingCustomFieldValues as $name => $value) {
                if (self::pendingValueIsFileField($model, (string) $name)) {
                    continue;
                }

                $model->setCustomFieldValue((string) $name, $value);
            }

            $model->pendingCustomFieldValues = [];
        });
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'model');
    }

    public function scopeWithCustomFields(Builder $query): Builder
    {
        return $query->with(['customFieldValues.customField.options']);
    }

    public function getCustomFieldValue(string $fieldName): mixed
    {
        $field = FieldDefinitionCache::forModel(static::class)
            ->firstWhere('name', $fieldName);

            $synchronizer = \Spiggle\DynamicFields\Pro\Services\FileFieldSynchronizer::class;
            if ($field && $field->type === 'file' && class_exists($synchronizer) && method_exists($this, 'getMedia')) {
                return $synchronizer::payloadFromModel($this, $field);
            }

        $value = $this->findCustomFieldValueRecord($fieldName);

        if (! $value) {
            return null;
        }

        return $value->getCastedValue();
    }

    public function setCustomFieldValue(string $fieldName, mixed $value): CustomFieldValue
    {
        $field = FieldDefinitionCache::forModel(static::class)
            ->firstWhere('name', $fieldName);

        if (! $field) {
            $field = CustomField::query()
                ->forModel(static::class)
                ->where('name', $fieldName)
                ->firstOrFail();
        }

        /** @var CustomFieldValue $record */
        $record = $this->customFieldValues()
            ->firstOrNew(['custom_field_id' => $field->id]);

        $record->setCastedValue($value, $field->valueType());
        $record->model_type = $this->getMorphClass();
        $record->model_id = $this->getKey();
        $record->save();

        if ($this->relationLoaded('customFieldValues')) {
            $this->customFieldValuesByName = null;
            $this->unsetRelation('customFieldValues');
            $this->load('customFieldValues.customField');
        }

        event(new CustomFieldValueSaved($record, $this));

        return $record;
    }

    /**
     * Queue nested form values (`custom_fields => [...]`) for persistence on save.
     *
     * @param  array<string, mixed>|null  $values
     */
    public function setCustomFieldsAttribute(?array $values): void
    {
        $this->pendingCustomFieldValues = $values ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomFieldsAttribute(): array
    {
        $definitions = FieldDefinitionCache::forModel(static::class);

        if (! $this->relationLoaded('customFieldValues')) {
            $this->loadMissing(['customFieldValues.customField.options']);
        }

        return $this->buildCustomFieldsArray($definitions);
    }

    /**
     * Sync many custom field values at once (used by Filament / APIs).
     *
     * @param  array<string, mixed>  $values
     */
    public function syncCustomFields(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->setCustomFieldValue((string) $name, $value);
        }
    }

    protected function findCustomFieldValueRecord(string $fieldName): ?CustomFieldValue
    {
        if ($this->relationLoaded('customFieldValues')) {
            $indexed = $this->indexedCustomFieldValues();

            if (isset($indexed[$fieldName])) {
                return $indexed[$fieldName];
            }

            $fieldId = $this->resolveFieldId($fieldName);

            if ($fieldId === null) {
                return null;
            }

            return $this->customFieldValues->first(
                fn (CustomFieldValue $value) => (int) $value->custom_field_id === $fieldId
            );
        }

        return $this->customFieldValues()
            ->whereHas('customField', fn ($q) => $q->where('name', $fieldName))
            ->with('customField')
            ->first();
    }

    /**
     * @return array<string, CustomFieldValue>
     */
    protected function indexedCustomFieldValues(): array
    {
        if ($this->customFieldValuesByName !== null) {
            return $this->customFieldValuesByName;
        }

        $indexed = [];

        if ($this->relationLoaded('customFieldValues')) {
            foreach ($this->customFieldValues as $value) {
                $name = $value->customField?->name;

                if ($name !== null) {
                    $indexed[$name] = $value;
                }
            }
        }

        return $this->customFieldValuesByName = $indexed;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CustomField>  $definitions
     * @return array<string, mixed>
     */
    protected function buildCustomFieldsArray($definitions): array
    {
        $valuesByName = [];
        $valuesByFieldId = [];

        foreach ($this->customFieldValues as $value) {
            $valuesByFieldId[(int) $value->custom_field_id] = $value;

            $name = $value->customField?->name;
            if ($name !== null) {
                $valuesByName[$name] = $value;
            }
        }

        $result = [];

        foreach ($definitions as $field) {
            if ($field->type === 'file') {
                $fileValue = $this->resolveCustomFileFieldValue($field);
                if ($fileValue !== null) {
                    $result[$field->name] = $fileValue;

                    continue;
                }
            }

            $record = $valuesByName[$field->name] ?? $valuesByFieldId[(int) $field->id] ?? null;
            $result[$field->name] = $record?->getCastedValue();
        }

        foreach ($valuesByName as $name => $value) {
            if (! array_key_exists($name, $result)) {
                $result[$name] = $value->getCastedValue();
            }
        }

        return $result;
    }

    protected function resolveCustomFileFieldValue(CustomField $field): mixed
    {
        $synchronizer = \Spiggle\DynamicFields\Pro\Services\FileFieldSynchronizer::class;

        if ($field->type !== 'file' || ! class_exists($synchronizer) || ! method_exists($this, 'getMedia')) {
            return null;
        }

        return $synchronizer::payloadFromModel($this, $field);
    }

    protected function resolveFieldId(string $fieldName): ?int
    {
        $field = FieldDefinitionCache::forModel(static::class)->firstWhere('name', $fieldName);

        return $field?->id;
    }

    protected static function pendingValueIsFileField(Model $model, string $name): bool
    {
        $field = FieldDefinitionCache::forModel($model::class)->firstWhere('name', $name);

        return $field?->type === 'file';
    }
}
