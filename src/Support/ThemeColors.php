<?php

namespace Spiggle\DynamicFields\Support;

class ThemeColors
{
    /**
     * Filament / theme semantic colors plus common palette names.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        $configured = config('dynamic-fields.tags.colors');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_filter($configured, fn ($color) => is_string($color) && $color !== ''));
        }

        return [
            'primary',
            'success',
            'warning',
            'danger',
            'info',
            'gray',
            'blue',
            'indigo',
            'purple',
            'pink',
            'rose',
            'red',
            'orange',
            'amber',
            'yellow',
            'lime',
            'green',
            'emerald',
            'teal',
            'cyan',
            'sky',
            'violet',
            'fuchsia',
            'slate',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::names() as $name) {
            $options[$name] = ucfirst($name);
        }

        return $options;
    }

    public static function forIndex(int $index): string
    {
        $names = self::names();

        if ($names === []) {
            return 'gray';
        }

        return $names[abs($index) % count($names)];
    }

    public static function isNamed(?string $color): bool
    {
        if ($color === null || $color === '') {
            return false;
        }

        return in_array(strtolower($color), self::names(), true);
    }

    public static function isHex(?string $color): bool
    {
        if ($color === null || $color === '') {
            return false;
        }

        return (bool) preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color);
    }

    public static function normalize(?string $color, int $fallbackIndex = 0): string
    {
        if (self::isNamed($color) || self::isHex($color)) {
            return (string) $color;
        }

        return self::forIndex($fallbackIndex);
    }

}
