<?php

namespace Spiggle\DynamicFields\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spiggle\DynamicFields\Models\CustomField;

class CustomFieldUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public CustomField $customField) {}
}
