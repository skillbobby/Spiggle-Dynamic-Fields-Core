<?php

namespace Spiggle\DynamicFields\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Services\FieldDefinitionCache;

class ExportFieldsCommand extends Command
{
    protected $signature = 'dynamic-fields:export
                            {--model= : Limit export to a target model FQCN}
                            {--path= : Output JSON path (default: storage/app/custom-fields-export.json)}';

    protected $description = 'Export custom field definitions (and options) to JSON';

    public function handle(): int
    {
        $query = CustomField::query()->with('options')->orderBy('target_model')->orderBy('sort_order');

        if ($model = $this->option('model')) {
            $query->where('target_model', $model);
        }

        $payload = $query->get()->map(function (CustomField $field) {
            return [
                'target_model' => $field->target_model,
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'is_required' => $field->is_required,
                'validation_rules' => $field->validation_rules,
                'meta' => $field->meta,
                'sort_order' => $field->sort_order,
                'options' => $field->options->map(fn ($o) => [
                    'label' => $o->label,
                    'value' => $o->value,
                    'color' => $o->color,
                    'sort_order' => $o->sort_order,
                ])->values()->all(),
            ];
        })->values()->all();

        $path = $this->option('path') ?: storage_path('app/custom-fields-export.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Exported '.count($payload)." field definition(s) to {$path}");

        return self::SUCCESS;
    }
}
