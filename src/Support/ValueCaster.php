<?php

namespace Spiggle\DynamicFields\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Throwable;

class ValueCaster
{
    public static function cast(mixed $value, ?string $valueType): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($valueType) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'integer' => is_numeric($value) ? (int) $value : null,
            'float', 'double' => is_numeric($value) ? (float) $value : null,
            'date', 'datetime' => self::toCarbon($value),
            'json', 'array' => self::toArray($value),
            default => (string) $value,
        };
    }

    public static function serialize(mixed $value, ?string $valueType = 'string'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($valueType) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'integer' => (string) (int) $value,
            'float', 'double' => (string) (float) $value,
            'date' => self::toCarbon($value)?->toDateString(),
            'datetime' => self::toCarbon($value)?->toDateTimeString(),
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            default => is_scalar($value) ? (string) $value : json_encode($value),
        };
    }

    protected static function toCarbon(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    protected static function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
