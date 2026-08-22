<?php

namespace Spiggle\DynamicFields\Licensing\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Spiggle\DynamicFields\Licensing\AddonLicenseRegistry;
use Spiggle\DynamicFields\Licensing\Filament\Pages\ManageSpiggleLicenses;

class SpiggleLicensingPlugin implements Plugin
{
    protected static bool $registered = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'spiggle-licensing';
    }

    public function register(Panel $panel): void
    {
        if (static::$registered || ! class_exists(ManageSpiggleLicenses::class)) {
            return;
        }

        static::$registered = true;

        $panel->pages([
            ManageSpiggleLicenses::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
