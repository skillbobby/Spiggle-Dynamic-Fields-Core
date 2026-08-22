<?php

namespace Spiggle\DynamicFields\Licensing;

final class AddonRegistration
{
    /**
     * @param  class-string  $licenseManagerClass
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $inactiveDescription,
        public readonly string $purchaseLabel,
        public readonly string $licenseManagerClass,
        public readonly ?string $permission = null,
        public readonly int $sort = 100,
    ) {}
}
