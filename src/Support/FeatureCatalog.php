<?php

namespace Spiggle\DynamicFields\Support;

use Spiggle\DynamicFields\Contracts\ProUnlock;

class FeatureCatalog
{
    /**
     * Field types that ship in Core (unlicensed / public package).
     *
     * @var list<string>
     */
    public const CORE_TYPES = [
        'text',
        'email',
        'phone',
        'url',
        'number',
        'textarea',
        'select',
        'radio',
        'date',
        'datetime',
        'boolean',
        'toggle',
    ];

    /**
     * Field types that exist only when Pro drivers are bound.
     *
     * @var list<string>
     */
    public const PRO_TYPES = [
        'multi_select',
        'tags',
        'file',
    ];

    /**
     * @return list<string>
     */
    public static function coreTypes(): array
    {
        return self::CORE_TYPES;
    }

    /**
     * @return list<string>
     */
    public static function proTypes(): array
    {
        return self::PRO_TYPES;
    }

    public static function isProType(string $type): bool
    {
        return in_array($type, self::PRO_TYPES, true);
    }

    public static function isCoreType(string $type): bool
    {
        return in_array($type, self::CORE_TYPES, true);
    }

    /**
     * Pro drivers bind only when the Pro package registers ProUnlock and
     * reports an authorized license (or an explicit testing bypass).
     */
    public static function proUnlocked(): bool
    {
        if (! app()->bound(ProUnlock::class)) {
            return false;
        }

        return app(ProUnlock::class)->unlocked();
    }

    /**
     * Labels for the Field Manager type dropdown. Pro types stay visible
     * in Core so the upsell can intercept selection.
     *
     * @return array<string, string>
     */
    public static function managerTypeLabels(): array
    {
        $labels = FieldTypes::labels();

        if (self::proUnlocked()) {
            return $labels;
        }

        foreach (self::PRO_TYPES as $type) {
            if (isset($labels[$type])) {
                $labels[$type] = $labels[$type].' · PRO';
            }
        }

        return $labels;
    }

    public static function typeTitle(string $type): string
    {
        $labels = FieldTypes::labels();

        return $labels[$type] ?? str_replace('_', ' ', $type);
    }
}
