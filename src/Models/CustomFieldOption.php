<?php

namespace Spiggle\DynamicFields\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spiggle\DynamicFields\Database\Factories\CustomFieldOptionFactory;

class CustomFieldOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_field_id',
        'label',
        'value',
        'color',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function getTable(): string
    {
        return config('dynamic-fields.tables.options', 'custom_field_options');
    }

    protected static function newFactory(): CustomFieldOptionFactory
    {
        return CustomFieldOptionFactory::new();
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $option): void {
            if (filled($option->color)) {
                return;
            }

            $field = $option->relationLoaded('customField')
                ? $option->customField
                : CustomField::query()->find($option->custom_field_id);

            if (! $field || $field->type !== 'tags') {
                return;
            }

            $index = (int) ($option->sort_order ?? 0);
            $option->color = \Spiggle\DynamicFields\Support\ThemeColors::forIndex($index);
        });
    }
}
