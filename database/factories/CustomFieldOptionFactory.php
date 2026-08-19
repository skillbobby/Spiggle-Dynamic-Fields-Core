<?php

namespace Spiggle\DynamicFields\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Models\CustomFieldOption;

/**
 * @extends Factory<CustomFieldOption>
 */
class CustomFieldOptionFactory extends Factory
{
    protected $model = CustomFieldOption::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'custom_field_id' => CustomField::factory(),
            'label' => ucfirst($label),
            'value' => str_replace(' ', '_', strtolower($label)),
            'sort_order' => fake()->numberBetween(0, 20),
            'color' => null,
        ];
    }
}
