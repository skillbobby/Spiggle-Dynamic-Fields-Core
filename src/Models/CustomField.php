<?php

namespace Spiggle\DynamicFields\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spiggle\DynamicFields\Database\Factories\CustomFieldFactory;
use Spiggle\DynamicFields\Events\CustomFieldCreated;
use Spiggle\DynamicFields\Events\CustomFieldDeleted;
use Spiggle\DynamicFields\Events\CustomFieldUpdated;
use Spiggle\DynamicFields\Services\FieldDefinitionCache;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_model',
        'name',
        'label',
        'type',
        'is_required',
        'validation_rules',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'meta' => 'array',
        'sort_order' => 'integer',
    ];

    protected $dispatchesEvents = [
        'created' => CustomFieldCreated::class,
        'updated' => CustomFieldUpdated::class,
        'deleted' => CustomFieldDeleted::class,
    ];

    public function getTable(): string
    {
        return config('dynamic-fields.tables.fields', 'custom_fields');
    }

    protected static function newFactory(): CustomFieldFactory
    {
        return CustomFieldFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(fn (self $field) => FieldDefinitionCache::forget($field->target_model));
        static::deleted(fn (self $field) => FieldDefinitionCache::forget($field->target_model));
    }

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class, 'custom_field_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
    }

    public function scopeForModel($query, string $modelClass)
    {
        return $query->where('target_model', $modelClass)->orderBy('sort_order')->orderBy('id');
    }

    public function optionMap(): array
    {
        if ($this->relationLoaded('options')) {
            return $this->getRelation('options')->pluck('label', 'value')->all();
        }

        return $this->options()->pluck('label', 'value')->all();
    }

    /**
     * @return array<string, string> value => color
     */
    public function optionColorMap(): array
    {
        $options = $this->relationLoaded('options')
            ? $this->getRelation('options')
            : $this->options()->get();

        $map = [];

        foreach ($options as $index => $option) {
            $value = (string) $option->value;
            $map[$value] = filled($option->color ?? null)
                ? (string) $option->color
                : \Spiggle\DynamicFields\Support\ThemeColors::forIndex((int) ($option->sort_order ?? $index));
        }

        return $map;
    }

    public function optionColor(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->optionColorMap()[$value] ?? null;
    }

    public function valueType(): string
    {
        return match ($this->type) {
            'boolean', 'toggle' => 'boolean',
            'date', 'datetime' => 'date',
            'number' => 'integer',
            'multi_select', 'tags', 'file' => 'json',
            default => 'string',
        };
    }

    public function cloneDefinition(?string $newName = null): self
    {
        $clone = $this->replicate(['id']);
        $clone->name = $newName ?? ($this->name.'_copy');
        $clone->label = $this->label.' (Copy)';
        $clone->save();

        foreach ($this->options as $option) {
            $clone->options()->create($option->only(['label', 'value', 'color', 'sort_order']));
        }

        FieldDefinitionCache::forget($clone->target_model);

        return $clone->fresh(['options']);
    }
}
