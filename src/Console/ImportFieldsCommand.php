<?php

namespace Spiggle\DynamicFields\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Services\FieldDefinitionCache;

class ImportFieldsCommand extends Command
{
    protected $signature = 'dynamic-fields:import
                            {path : Path to a JSON export file}
                            {--fresh : Delete matching target models\' fields before import}';

    protected $description = 'Import custom field definitions from JSON';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode(File::get($path), true);

        if (! is_array($payload)) {
            $this->error('Invalid JSON payload.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $models = collect($payload)->pluck('target_model')->unique()->filter();
            foreach ($models as $model) {
                CustomField::query()->where('target_model', $model)->each->delete();
                FieldDefinitionCache::forget($model);
            }
        }

        $created = 0;
        $updated = 0;

        foreach ($payload as $definition) {
            if (! is_array($definition) || empty($definition['target_model']) || empty($definition['name'])) {
                continue;
            }

            $options = $definition['options'] ?? [];
            unset($definition['options']);

            $field = CustomField::query()->updateOrCreate(
                [
                    'target_model' => $definition['target_model'],
                    'name' => $definition['name'],
                ],
                collect($definition)->only([
                    'label', 'type', 'is_required', 'validation_rules', 'meta', 'sort_order',
                ])->all()
            );

            $field->wasRecentlyCreated ? $created++ : $updated++;

            $field->options()->delete();
            foreach (array_values($options) as $index => $option) {
                if (! is_array($option)) {
                    continue;
                }
                $field->options()->create([
                    'label' => $option['label'] ?? $option['value'] ?? 'Option',
                    'value' => $option['value'] ?? (string) $index,
                    'color' => $option['color'] ?? null,
                    'sort_order' => $option['sort_order'] ?? $index,
                ]);
            }

            FieldDefinitionCache::forget($field->target_model);
        }

        $this->info("Import complete: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }
}
