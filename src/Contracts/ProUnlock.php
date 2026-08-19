<?php

namespace Spiggle\DynamicFields\Contracts;

interface ProUnlock
{
    /**
     * True when the Pro package is installed and the license bind is valid
     * (or licensing is not enforced).
     */
    public function unlocked(): bool;
}
