<?php

namespace Spiggle\DynamicFields\Support;

class FieldTypes
{
    public static function all(): array
    {
        return config('dynamic-fields.field_types', [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'select' => 'Select',
            'multi_select' => 'Multi-select',
            'tags' => 'Tags',
            'radio' => 'Radio',
            'date' => 'Date',
            'datetime' => 'Date & Time',
            'boolean' => 'Boolean',
            'toggle' => 'Toggle',
            'number' => 'Number',
            'email' => 'Email',
            'phone' => 'Phone',
            'url' => 'URL',
            'file' => 'File',
        ]);
    }

    public static function labels(): array
    {
        return self::all();
    }

    public static function requiresOptions(string $type): bool
    {
        return in_array($type, ['select', 'radio', 'multi_select', 'tags'], true);
    }

    public static function storesArray(string $type): bool
    {
        return in_array($type, ['multi_select', 'tags', 'file'], true);
    }

    public static function isBoolean(string $type): bool
    {
        return in_array($type, ['boolean', 'toggle'], true);
    }

    public static function isFile(string $type): bool
    {
        return $type === 'file';
    }

    public static function hasColoredOptions(string $type): bool
    {
        return $type === 'tags';
    }
}
