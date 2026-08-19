<?php

namespace Spiggle\DynamicFields\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spiggle\DynamicFields\Models\CustomField;

/**
 * @extends Factory<CustomField>
 */
class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'target_model' => \App\Models\User::class,
            'name' => str_replace('-', '_', $name),
            'label' => fake()->words(2, true),
            'type' => fake()->randomElement(['text', 'textarea', 'date', 'boolean', 'number', 'email']),
            'is_required' => fake()->boolean(20),
            'validation_rules' => ['max:255'],
            'meta' => [],
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function select(): static
    {
        return $this->state(fn () => ['type' => 'select']);
    }

    public function forModel(string $modelClass): static
    {
        return $this->state(fn () => ['target_model' => $modelClass]);
    }
}
