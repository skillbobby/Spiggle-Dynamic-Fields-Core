<?php

namespace Spiggle\DynamicFields\Console;

use Illuminate\Console\Command;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Services\FieldDefinitionCache;

class SeedStandardUserFieldsCommand extends Command
{
    protected $signature = 'dynamic-fields:seed-user-fields
                            {--model= : Target model FQCN (default: App\\Models\\User)}
                            {--fresh : Delete existing fields for the target model before seeding}';

    protected $description = 'Seed standard profile-style custom fields for the User model';

    public function handle(): int
    {
        $model = $this->option('model') ?: \App\Models\User::class;

        if (! class_exists($model)) {
            $this->error("Model [{$model}] does not exist.");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            CustomField::query()->where('target_model', $model)->each(function (CustomField $field): void {
                $field->delete();
            });
            $this->warn("Removed existing custom fields for {$model}.");
        }

        $definitions = $this->definitions();
        $created = 0;
        $skipped = 0;

        foreach ($definitions as $index => $definition) {
            $options = $definition['options'] ?? [];
            unset($definition['options']);

            $existing = CustomField::query()
                ->where('target_model', $model)
                ->where('name', $definition['name'])
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $field = CustomField::query()->create(array_merge($definition, [
                'target_model' => $model,
                'sort_order' => $definition['sort_order'] ?? ($index + 1) * 10,
            ]));

            foreach ($options as $optionIndex => $option) {
                $field->options()->create([
                    'label' => $option['label'],
                    'value' => $option['value'],
                    'color' => $option['color'] ?? null,
                    'sort_order' => $option['sort_order'] ?? $optionIndex,
                ]);
            }

            $created++;
        }

        FieldDefinitionCache::forget($model);

        $this->info("Seeded custom fields for {$model}: {$created} created, {$skipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function definitions(): array
    {
        return [
            [
                'name' => 'first_name',
                'label' => 'First Name',
                'type' => 'text',
                'is_required' => true,
                'validation_rules' => ['max:100'],
                'meta' => ['placeholder' => 'Jane', 'group' => 'identity'],
            ],
            [
                'name' => 'last_name',
                'label' => 'Last Name',
                'type' => 'text',
                'is_required' => true,
                'validation_rules' => ['max:100'],
                'meta' => ['placeholder' => 'Doe', 'group' => 'identity'],
            ],
            [
                'name' => 'display_name',
                'label' => 'Display Name',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:150'],
                'meta' => ['hint' => 'Shown publicly instead of the login name when set.', 'group' => 'identity'],
            ],
            [
                'name' => 'phone',
                'label' => 'Phone',
                'type' => 'phone',
                'is_required' => false,
                'validation_rules' => ['max:30'],
                'meta' => ['placeholder' => '+1 555 0100', 'group' => 'contact'],
            ],
            [
                'name' => 'mobile',
                'label' => 'Mobile',
                'type' => 'phone',
                'is_required' => false,
                'validation_rules' => ['max:30'],
                'meta' => ['group' => 'contact'],
            ],
            [
                'name' => 'alternate_email',
                'label' => 'Alternate Email',
                'type' => 'email',
                'is_required' => false,
                'validation_rules' => ['email', 'max:255'],
                'meta' => ['group' => 'contact'],
            ],
            [
                'name' => 'date_of_birth',
                'label' => 'Date of Birth',
                'type' => 'date',
                'is_required' => false,
                'validation_rules' => ['date'],
                'meta' => ['group' => 'personal'],
            ],
            [
                'name' => 'gender',
                'label' => 'Gender',
                'type' => 'select',
                'is_required' => false,
                'meta' => ['group' => 'personal'],
                'options' => [
                    ['label' => 'Prefer not to say', 'value' => 'unspecified'],
                    ['label' => 'Female', 'value' => 'female'],
                    ['label' => 'Male', 'value' => 'male'],
                    ['label' => 'Non-binary', 'value' => 'non_binary'],
                    ['label' => 'Other', 'value' => 'other'],
                ],
            ],
            [
                'name' => 'job_title',
                'label' => 'Job Title',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:150'],
                'meta' => ['group' => 'work'],
            ],
            [
                'name' => 'company',
                'label' => 'Company',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:150'],
                'meta' => ['group' => 'work'],
            ],
            [
                'name' => 'department',
                'label' => 'Department',
                'type' => 'select',
                'is_required' => false,
                'meta' => ['group' => 'work'],
                'options' => [
                    ['label' => 'Executive', 'value' => 'executive'],
                    ['label' => 'Engineering', 'value' => 'engineering'],
                    ['label' => 'Product', 'value' => 'product'],
                    ['label' => 'Design', 'value' => 'design'],
                    ['label' => 'Marketing', 'value' => 'marketing'],
                    ['label' => 'Sales', 'value' => 'sales'],
                    ['label' => 'Human Resources', 'value' => 'hr'],
                    ['label' => 'Finance', 'value' => 'finance'],
                    ['label' => 'Support', 'value' => 'support'],
                    ['label' => 'Other', 'value' => 'other'],
                ],
            ],
            [
                'name' => 'address_line_1',
                'label' => 'Address Line 1',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:255'],
                'meta' => ['group' => 'address'],
            ],
            [
                'name' => 'address_line_2',
                'label' => 'Address Line 2',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:255'],
                'meta' => ['group' => 'address'],
            ],
            [
                'name' => 'city',
                'label' => 'City',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:100'],
                'meta' => ['group' => 'address'],
            ],
            [
                'name' => 'state',
                'label' => 'State / Province',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:100'],
                'meta' => ['group' => 'address'],
            ],
            [
                'name' => 'postal_code',
                'label' => 'Postal Code',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:30'],
                'meta' => ['group' => 'address'],
            ],
            [
                'name' => 'country',
                'label' => 'Country',
                'type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max:100'],
                'meta' => ['group' => 'address', 'placeholder' => 'United States'],
            ],
            [
                'name' => 'website',
                'label' => 'Website',
                'type' => 'url',
                'is_required' => false,
                'validation_rules' => ['url', 'max:255'],
                'meta' => ['group' => 'social'],
            ],
            [
                'name' => 'linkedin',
                'label' => 'LinkedIn URL',
                'type' => 'url',
                'is_required' => false,
                'validation_rules' => ['url', 'max:255'],
                'meta' => ['group' => 'social'],
            ],
            [
                'name' => 'timezone',
                'label' => 'Timezone',
                'type' => 'select',
                'is_required' => false,
                'meta' => ['group' => 'preferences'],
                'options' => [
                    ['label' => 'UTC', 'value' => 'UTC'],
                    ['label' => 'America/New_York', 'value' => 'America/New_York'],
                    ['label' => 'America/Chicago', 'value' => 'America/Chicago'],
                    ['label' => 'America/Denver', 'value' => 'America/Denver'],
                    ['label' => 'America/Los_Angeles', 'value' => 'America/Los_Angeles'],
                    ['label' => 'Europe/London', 'value' => 'Europe/London'],
                    ['label' => 'Europe/Berlin', 'value' => 'Europe/Berlin'],
                    ['label' => 'Asia/Tokyo', 'value' => 'Asia/Tokyo'],
                    ['label' => 'Australia/Sydney', 'value' => 'Australia/Sydney'],
                ],
            ],
            [
                'name' => 'preferred_language',
                'label' => 'Preferred Language',
                'type' => 'select',
                'is_required' => false,
                'meta' => ['group' => 'preferences'],
                'options' => [
                    ['label' => 'English', 'value' => 'en'],
                    ['label' => 'Spanish', 'value' => 'es'],
                    ['label' => 'French', 'value' => 'fr'],
                    ['label' => 'German', 'value' => 'de'],
                    ['label' => 'Portuguese', 'value' => 'pt'],
                ],
            ],
            [
                'name' => 'bio',
                'label' => 'Bio',
                'type' => 'textarea',
                'is_required' => false,
                'validation_rules' => ['max:2000'],
                'meta' => ['rows' => 4, 'group' => 'personal'],
            ],
            [
                'name' => 'marketing_opt_in',
                'label' => 'Marketing Opt-In',
                'type' => 'boolean',
                'is_required' => false,
                'meta' => ['hint' => 'User agrees to receive product updates and marketing.', 'group' => 'preferences'],
            ],
        ];
    }
}
