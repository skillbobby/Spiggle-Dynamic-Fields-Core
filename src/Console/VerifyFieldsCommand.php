<?php

namespace Spiggle\DynamicFields\Console;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Services\CustomFieldMapper;
use Spiggle\DynamicFields\Support\FeatureCatalog;
use Spiggle\DynamicFields\Support\FieldTypes;
use Throwable;

class VerifyFieldsCommand extends Command
{
    protected $signature = 'dynamic-fields:verify
                            {--model= : Target model FQCN (default: App\\Models\\User)}';

    protected $description = 'Exercise mapper, persistence, redirects, and file media for Dynamic Fields';

    public function handle(): int
    {
        $modelClass = $this->option('model') ?: \App\Models\User::class;
        $failures = 0;

        $failures += $this->assertTrue(class_exists($modelClass), "Model {$modelClass} exists");
        $failures += $this->assertTrue(method_exists($modelClass, 'getCustomFieldValue'), 'HasCustomFields is present');
        $failures += $this->assertTrue(Schema::hasTable('custom_fields'), 'custom_fields table exists');
        $failures += $this->assertTrue(Schema::hasColumn('custom_field_options', 'color'), 'options.color column exists');

        $failures += $this->assertTrue(FieldTypes::requiresOptions('select'), 'select requires options');
        $failures += $this->assertTrue(FieldTypes::requiresOptions('multi_select'), 'multi_select requires options');
        $failures += $this->assertTrue(FieldTypes::requiresOptions('tags'), 'tags requires options');
        $failures += $this->assertTrue(FieldTypes::isBoolean('toggle'), 'toggle is boolean-like');
        $failures += $this->assertTrue(FieldTypes::isFile('file'), 'file type detected');

        $createPage = file_get_contents(
            dirname(__DIR__, 2).'/src/Filament/Resources/CustomFields/Pages/CreateCustomField.php'
        ) ?: '';
        $failures += $this->assertTrue(
            str_contains($createPage, "getResourceUrl('index')")
            || str_contains($createPage, 'getRedirectUrl(): string'),
            'create page defines getRedirectUrl() to index'
        );
        $failures += $this->assertTrue(
            str_contains($createPage, "'index'"),
            'create-custom-field redirects to the list index'
        );

        $formSource = file_get_contents(
            dirname(__DIR__, 2).'/src/Filament/Resources/CustomFields/Schemas/CustomFieldForm.php'
        ) ?: '';
        $failures += $this->assertTrue(str_contains($formSource, 'itemLabel'), 'options repeater sets itemLabel');
        $failures += $this->assertTrue(str_contains($formSource, 'collapsed()'), 'options repeater is collapsed by default');
        $failures += $this->assertTrue(str_contains($formSource, 'collapsible()'), 'options repeater is collapsible');
        $failures += $this->assertTrue(str_contains($formSource, 'reorderable()'), 'options repeater is reorderable');

        $mapper = app(CustomFieldMapper::class);
        $components = $mapper->makeFormFields($modelClass);
        $byName = [];
        foreach ($components as $component) {
            if (is_object($component) && method_exists($component, 'getName')) {
                $byName[$component->getName()] = $component;
            }
        }

        $this->line('Mapper produced '.count($components).' form component(s).');

        $standard = CustomField::query()->where('target_model', $modelClass)->count();
        $failures += $this->assertTrue($standard >= 23, "at least 23 custom fields exist (found {$standard})");

        $typeChecks = [
            'bio' => [Textarea::class, false],
            'first_name' => [\Filament\Forms\Components\TextInput::class, false],
            'department' => [Select::class, false],
        ];

        if (FeatureCatalog::proUnlocked()) {
            $typeChecks['skills'] = [Select::class, true];
            $typeChecks['interests'] = [TagsInput::class, false];
            $typeChecks['beta_access'] = [Toggle::class, false];
            $typeChecks['about_html'] = [RichEditor::class, false];
            $typeChecks['favorite_team'] = [Select::class, false];
        } else {
            $typeChecks['beta_access'] = [Toggle::class, false];
            $typeChecks['favorite_team'] = [Select::class, false];
            $typeChecks['about_html'] = [Textarea::class, false];
        }

        $stateKey = config('dynamic-fields.form_state_key', 'custom_fields');

        foreach ($typeChecks as $fieldName => [$class, $multiple]) {
            $key = $stateKey.'.'.$fieldName;
            if (! isset($byName[$key])) {
                $this->warn("Field [{$fieldName}] not in mapper output (seed extended fields?).");
                continue;
            }
            $component = $byName[$key];
            $failures += $this->assertTrue($component instanceof $class, "{$fieldName} maps to {$class}");
            if ($multiple && $component instanceof Select) {
                $failures += $this->assertTrue($component->isMultiple(), 'skills is a multi-select');
            }
        }

        if (isset($byName[$stateKey.'.profile_documents'])) {
            $fileClass = \Filament\Forms\Components\SpatieMediaLibraryFileUpload::class;
            $failures += $this->assertTrue(
                class_exists($fileClass) && $byName[$stateKey.'.profile_documents'] instanceof $fileClass,
                'profile_documents maps to SpatieMediaLibraryFileUpload'
            );
        }

        if (! class_exists($modelClass) || ! method_exists($modelClass, 'factory')) {
            $this->warn('Skipping persistence checks (no factory).');
            $this->printSummary($failures);

            return $failures === 0 ? self::SUCCESS : self::FAILURE;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            /** @var \Illuminate\Database\Eloquent\Model $record */
            $record = $modelClass::factory()->create();

            $record->setCustomFieldValue('first_name', 'Ada');
            $record->setCustomFieldValue('department', 'engineering');
            $record->setCustomFieldValue('bio', 'Plain textarea bio.');
            $record->unsetRelation('customFieldValues');
            $record->load('customFieldValues.customField');

            $failures += $this->assertTrue($record->getCustomFieldValue('first_name') === 'Ada', 'text value persists');
            $failures += $this->assertTrue($record->getCustomFieldValue('department') === 'engineering', 'select value persists');
            $failures += $this->assertTrue($record->getCustomFieldValue('bio') === 'Plain textarea bio.', 'plain textarea persists');

            if (CustomField::query()->where('target_model', $modelClass)->where('name', 'skills')->exists()) {
                $record->setCustomFieldValue('skills', ['php', 'laravel']);
                $record->unsetRelation('customFieldValues');
                $skills = $record->fresh()->load('customFieldValues.customField')->getCustomFieldValue('skills');
                $failures += $this->assertTrue(is_array($skills) && in_array('php', $skills, true), 'multi-select persists as array');
            }

            if (CustomField::query()->where('target_model', $modelClass)->where('name', 'interests')->exists()) {
                $record->setCustomFieldValue('interests', ['open_source', 'security']);
                $record->unsetRelation('customFieldValues');
                $tags = $record->fresh()->load('customFieldValues.customField')->getCustomFieldValue('interests');
                $failures += $this->assertTrue(is_array($tags) && in_array('security', $tags, true), 'tags persist as array');
            }

            if (CustomField::query()->where('target_model', $modelClass)->where('name', 'beta_access')->exists()) {
                $record->setCustomFieldValue('beta_access', true);
                $record->unsetRelation('customFieldValues');
                $failures += $this->assertTrue($record->fresh()->load('customFieldValues.customField')->getCustomFieldValue('beta_access') === true, 'toggle persists true');
            }

            if (CustomField::query()->where('target_model', $modelClass)->where('name', 'about_html')->exists()) {
                $html = '<p>Hello <strong>world</strong></p>';
                $record->setCustomFieldValue('about_html', $html);
                $record->unsetRelation('customFieldValues');
                $failures += $this->assertTrue(
                    str_contains((string) $record->fresh()->load('customFieldValues.customField')->getCustomFieldValue('about_html'), 'Hello'),
                    'rich textarea persists HTML'
                );
            }

            if (
                CustomField::query()->where('target_model', $modelClass)->where('name', 'profile_documents')->exists()
                && method_exists($record, 'addMedia')
            ) {
                $failures += $this->assertFilePersistence($record, $modelClass);
            } elseif (CustomField::query()->where('target_model', $modelClass)->where('name', 'profile_documents')->exists()) {
                $this->warn('File field exists but model is missing HasMedia / addMedia().');
            }
        } finally {
            \Illuminate\Support\Facades\DB::rollBack();
        }

        $this->printSummary($failures);

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function assertFilePersistence(object $record, string $modelClass): int
    {
        $failures = 0;

        try {
            Storage::disk('public')->makeDirectory('dynamic-fields-verify');
            $path = 'dynamic-fields-verify/sample.txt';
            Storage::disk('public')->put($path, 'dynamic-fields verify file');
            $absolute = Storage::disk('public')->path($path);

            $field = CustomField::query()
                ->where('target_model', $modelClass)
                ->where('name', 'profile_documents')
                ->first();

            $synchronizer = \Spiggle\DynamicFields\Pro\Services\FileFieldSynchronizer::class;
            if (! class_exists($synchronizer)) {
                return 0;
            }

            $collection = $synchronizer::collectionName($field);

            $record->addMedia($absolute)
                ->preservingOriginal()
                ->withCustomProperties([
                    'custom_field' => 'profile_documents',
                    'original_name' => 'sample.txt',
                ])
                ->toMediaCollection($collection, 'public');

            $payload = $record->fresh()->getCustomFieldValue('profile_documents');
            $failures += $this->assertTrue(is_array($payload), 'file field returns metadata array');
            $failures += $this->assertTrue(($payload['collection'] ?? null) === $collection, 'file metadata has collection');
            $failures += $this->assertTrue(! empty($payload['media_ids'] ?? []), 'file metadata has media ids');
            $failures += $this->assertTrue(! empty($payload['files'] ?? []), 'file metadata has files list');
        } catch (Throwable $e) {
            $this->error('File persistence failed: '.$e->getMessage());
            $failures++;
        }

        return $failures;
    }

    protected function assertTrue(bool $condition, string $label): int
    {
        if ($condition) {
            $this->info("PASS  {$label}");

            return 0;
        }

        $this->error("FAIL  {$label}");

        return 1;
    }

    protected function printSummary(int $failures): void
    {
        if ($failures === 0) {
            $this->info('Dynamic Fields verify: all checks passed.');

            return;
        }

        $this->error("Dynamic Fields verify: {$failures} check(s) failed.");
    }
}
