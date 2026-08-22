<?php

namespace Spiggle\DynamicFields\Licensing\Contracts;

interface AddonLicenseManager
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array;

    public function activate(string $licenseKey): object;

    public function deactivate(): object;
}
