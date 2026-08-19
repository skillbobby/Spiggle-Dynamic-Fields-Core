<?php

namespace Spiggle\DynamicFields\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Spiggle\DynamicFields\Filament\Resources\CustomFields\CustomFieldResource;

class DynamicFieldsPlugin implements Plugin
{
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
        return 'spiggle-dynamic-fields';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            CustomFieldResource::class,
        ]);

        $licensePage = 'Spiggle\\DynamicFields\\Pro\\Filament\\Pages\\ManageAddonLicense';
        if (class_exists($licensePage)) {
            $panel->pages([$licensePage]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
