<?php

namespace Spiggle\DynamicFields\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spiggle\DynamicFields\Database\Factories\CustomFieldValueFactory;
use Spiggle\DynamicFields\Support\ValueCaster;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_field_id',
        'model_type',
        'model_id',
        'value',
        'value_type',
    ];

    public function getTable(): string
    {
        return config('dynamic-fields.tables.values', 'custom_field_values');
    }

    protected static function newFactory(): CustomFieldValueFactory
    {
        return CustomFieldValueFactory::new();
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCastedValue(): mixed
    {
        return ValueCaster::cast($this->value, $this->value_type);
    }

    public function setCastedValue(mixed $value, ?string $valueType = null): void
    {
        $type = $valueType ?? $this->value_type ?? 'string';
        $this->value_type = $type;
        $this->value = ValueCaster::serialize($value, $type);
    }
}
