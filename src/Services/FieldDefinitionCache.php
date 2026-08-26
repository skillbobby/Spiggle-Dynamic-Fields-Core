<?php

namespace Spiggle\DynamicFields\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spiggle\DynamicFields\Models\CustomField;

class FieldDefinitionCache
{
    /** @var array<string, Collection<int, CustomField>> */
    protected static array $memory = [];

    /**
     * @return Collection<int, CustomField>
     */
    public static function forModel(string $modelClass): Collection
    {
        if (isset(self::$memory[$modelClass])) {
            return self::$memory[$modelClass];
        }

        if (! config('dynamic-fields.cache.enabled', true)) {
            return self::$memory[$modelClass] = self::query($modelClass);
        }

        $key = self::key($modelClass);

        /** @var array<int, array<string, mixed>> $payload */
        $payload = Cache::remember(
            $key,
            (int) config('dynamic-fields.cache.ttl', 3600),
            function () use ($modelClass) {
                return self::query($modelClass)->map(function (CustomField $field) {
                    $data = $field->attributesToArray();
                    $data['options'] = $field->options->map->attributesToArray()->all();

                    return $data;
                })->all();
            }
        );

        return self::$memory[$modelClass] = collect($payload)->map(function (array $attributes) {
            $options = $attributes['options'] ?? [];
            unset($attributes['options']);

            $field = new CustomField;
            $field->forceFill($attributes);
            $field->exists = true;
            $field->syncOriginal();

            $field->setRelation(
                'options',
                collect(is_array($options) ? $options : [])->map(function (array $option) {
                    $model = new \Spiggle\DynamicFields\Models\CustomFieldOption;
                    $model->forceFill($option);
                    $model->exists = true;
                    $model->syncOriginal();

                    return $model;
                })
            );

            return $field;
        });
    }

    public static function forget(string $modelClass): void
    {
        unset(self::$memory[$modelClass]);
        Cache::forget(self::key($modelClass));
    }

    public static function flush(): void
    {
        // Best-effort: forget known models from DB.
        CustomField::query()
            ->distinct()
            ->pluck('target_model')
            ->each(fn (string $model) => self::forget($model));
    }

    /**
     * @return Collection<int, CustomField>
     */
    protected static function query(string $modelClass): Collection
    {
        return CustomField::query()
            ->forModel($modelClass)
            ->with('options')
            ->get();
    }

    protected static function key(string $modelClass): string
    {
        $prefix = config('dynamic-fields.cache.prefix', 'spiggle_dynamic_fields');

        return $prefix.':definitions:'.md5($modelClass);
    }
}
