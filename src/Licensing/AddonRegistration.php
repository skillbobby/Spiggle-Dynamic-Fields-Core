<?php

namespace Spiggle\DynamicFields\Licensing;

final class AddonRegistration
{
    /**
     * @param  class-string  $licenseManagerClass
     * @param  array<string, string>  $packages  composer package => short label (e.g. 'spiggle/foo-core' => 'Core')
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $inactiveDescription,
        public readonly string $purchaseLabel,
        public readonly string $licenseManagerClass,
        public readonly ?string $permission = null,
        public readonly int $sort = 100,
        public readonly array $packages = [],
    ) {}

    public function installedVersionsLabel(): string
    {
        if ($this->packages === []) {
            return '';
        }

        if (class_exists(InstalledPackageVersions::class)) {
            return InstalledPackageVersions::summary($this->packages);
        }

        if (class_exists(\Spiggle\FormBuilder\Support\InstalledPackageVersions::class)) {
            return \Spiggle\FormBuilder\Support\InstalledPackageVersions::summary($this->packages);
        }

        return '';
    }
}
