<?php

namespace Spiggle\DynamicFields\Licensing;

use Illuminate\Contracts\Container\Container;
use Spiggle\DynamicFields\Licensing\Contracts\AddonLicenseManager;
use Spiggle\DynamicFields\Licensing\Filament\Pages\ManageSpiggleLicenses;

class AddonLicenseRegistry
{
    /** @var array<string, AddonRegistration> */
    protected array $addons = [];

    public function __construct(protected Container $app) {}

    public function register(AddonRegistration $addon): void
    {
        if (! is_subclass_of($addon->licenseManagerClass, AddonLicenseManager::class)) {
            throw new \InvalidArgumentException(
                "{$addon->licenseManagerClass} must implement ".AddonLicenseManager::class
            );
        }

        $this->addons[$addon->id] = $addon;
    }

    public function hasAddons(): bool
    {
        return $this->addons !== [];
    }

    /**
     * @return list<AddonRegistration>
     */
    public function all(): array
    {
        $addons = array_values($this->addons);

        usort($addons, fn (AddonRegistration $a, AddonRegistration $b): int => $a->sort <=> $b->sort);

        return $addons;
    }

    public function find(string $id): ?AddonRegistration
    {
        return $this->addons[$id] ?? null;
    }

    public function manager(AddonRegistration $addon): AddonLicenseManager
    {
        return $this->app->make($addon->licenseManagerClass);
    }

    public static function licensePageUrl(): string
    {
        if (! class_exists(ManageSpiggleLicenses::class)) {
            return '';
        }

        try {
            return ManageSpiggleLicenses::getUrl();
        } catch (\Throwable) {
            return '';
        }
    }
}
