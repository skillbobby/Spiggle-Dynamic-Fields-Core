<?php

namespace Spiggle\DynamicFields\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Models\CustomFieldValue;

/**
 * @extends Factory<CustomFieldValue>
 */
class CustomFieldValueFactory extends Factory
{
    protected $model = CustomFieldValue::class;

    public function definition(): array
    {
        return [
            'custom_field_id' => CustomField::factory(),
            'model_type' => \App\Models\User::class,
            'model_id' => 1,
            'value' => fake()->sentence(3),
            'value_type' => 'string',
        ];
    }
}
