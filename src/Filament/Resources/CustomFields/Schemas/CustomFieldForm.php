<?php

namespace Spiggle\DynamicFields\Filament\Resources\CustomFields\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spiggle\DynamicFields\Filament\Support\ProUpsell;
use Spiggle\DynamicFields\Services\ModelDiscoverer;
use Spiggle\DynamicFields\Support\FeatureCatalog;
use Spiggle\DynamicFields\Support\FieldTypes;
use Spiggle\DynamicFields\Support\ThemeColors;

class CustomFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Field Definition')
                    ->columns(2)
                    ->schema([
                        Select::make('target_model')
                            ->label('Target Model')
                            ->options(fn () => app(ModelDiscoverer::class)->discover())
                            ->searchable()
                            ->required()
                            ->helperText('Eloquent model that will receive this custom field.'),
                        TextInput::make('name')
                            ->label('Internal Name')
                            ->required()
                            ->alphaDash()
                            ->maxLength(100)
                            ->helperText('Snake_case key used in code and storage.'),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Field Type')
                            ->options(fn (): array => FeatureCatalog::managerTypeLabels())
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                ProUpsell::guardFieldType($state, $set);
                            }),
                        Toggle::make('is_required')
                            ->label('Required')
                            ->default(false)
                            ->inline(false),
                        Toggle::make('meta.show_in_table')
                            ->label('Show in table by default')
                            ->helperText('When enabled, this field\'s column is visible on list tables. All custom fields remain available in the column manager.')
                            ->default(false)
                            ->inline(false),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TagsInput::make('validation_rules')
                            ->label('Validation Rules')
                            ->placeholder('max:255')
                            ->helperText('Laravel validation rule strings (e.g. max:255, email). Stored as JSON.')
                            ->columnSpanFull()
                            ->nullable(),
                        TextInput::make('meta.hint')
                            ->label('Hint')
                            ->maxLength(255),
                        TextInput::make('meta.placeholder')
                            ->label('Placeholder')
                            ->maxLength(255),
                        TextInput::make('meta.group')
                            ->label('Group')
                            ->maxLength(100)
                            ->helperText('Optional grouping key for seeders / layout.'),
                        TextInput::make('meta.visible_when_field')
                            ->label('Visible when field')
                            ->helperText(fn (): string => FeatureCatalog::proUnlocked()
                                ? 'Internal name of another custom field.'
                                : 'PRO — Internal name of another custom field.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (mixed $state, callable $set): void {
                                ProUpsell::guardVisibility($state, $set, 'meta.visible_when_field');
                            }),
                        TextInput::make('meta.visible_when_value')
                            ->label('Visible when value')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (mixed $state, callable $set): void {
                                ProUpsell::guardVisibility($state, $set, 'meta.visible_when_value');
                            }),
                    ]),
                Section::make('Textarea options')
                    ->description('Plain textarea by default. Enable the built-in editor to use Filament RichEditor.')
                    ->visible(fn (Get $get): bool => $get('type') === 'textarea')
                    ->columns(2)
                    ->schema([
                        Toggle::make('meta.use_editor')
                            ->label(fn (): string => FeatureCatalog::proUnlocked()
                                ? 'Use built-in text editor'
                                : 'Use built-in text editor · PRO')
                            ->helperText('Filament RichEditor. Existing fields stay as a plain textarea unless this is enabled.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (mixed $state, callable $set): void {
                                ProUpsell::guardUseEditor($state, $set);
                            })
                            ->inline(false),
                        TextInput::make('meta.rows')
                            ->label('Rows')
                            ->numeric()
                            ->minValue(2)
                            ->default(3)
                            ->visible(fn (Get $get): bool => ! filter_var($get('meta.use_editor'), FILTER_VALIDATE_BOOLEAN)),
                    ]),
                Section::make('Selection limits')
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['multi_select', 'tags'], true))
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta.min_selections')
                            ->label('Min selections')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('meta.max_selections')
                            ->label('Max selections')
                            ->numeric()
                            ->minValue(0),
                    ]),
                Section::make('File upload')
                    ->description('Files are stored with Spatie Media Library on the host model. EAV stores collection name and media ids only — not file bytes. The host model must use HasMedia / HasCustomFieldMedia.')
                    ->visible(fn (Get $get): bool => $get('type') === 'file')
                    ->columns(2)
                    ->schema([
                        Toggle::make('meta.multiple')
                            ->label('Allow multiple files')
                            ->default(false)
                            ->live()
                            ->inline(false),
                        Select::make('meta.disk')
                            ->label('Storage disk')
                            ->options(fn (): array => self::diskOptions())
                            ->helperText('Default uses the package/Filament disk. S3 works when the disk is configured; credentials are not required for local tests.')
                            ->native(false)
                            ->nullable(),
                        TextInput::make('meta.collection')
                            ->label('Media collection')
                            ->helperText('Leave empty to auto-name as custom_field_{name}.'),
                        TextInput::make('meta.directory')
                            ->label('Directory hint')
                            ->helperText('Stored as a custom property on each media item. Spatie organizes files by media id.')
                            ->maxLength(255),
                        TagsInput::make('meta.accepted_mime_types')
                            ->label('Accepted MIME types')
                            ->placeholder('image/png')
                            ->columnSpanFull(),
                        TextInput::make('meta.max_size_kb')
                            ->label('Max size (KB)')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('meta.min_files')
                            ->label('Min files')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => (bool) $get('meta.multiple')),
                        TextInput::make('meta.max_files')
                            ->label('Max files')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => (bool) $get('meta.multiple')),
                        Toggle::make('meta.image_only')
                            ->label('Images only')
                            ->live()
                            ->inline(false),
                        Toggle::make('meta.image_editor')
                            ->label('Enable image editor')
                            ->helperText('Filament built-in image editor / manipulations.')
                            ->inline(false),
                        TextInput::make('meta.image_min_width')
                            ->label('Min width (px)')
                            ->numeric()
                            ->visible(fn (Get $get): bool => (bool) $get('meta.image_only')),
                        TextInput::make('meta.image_min_height')
                            ->label('Min height (px)')
                            ->numeric()
                            ->visible(fn (Get $get): bool => (bool) $get('meta.image_only')),
                        TextInput::make('meta.image_max_width')
                            ->label('Max width (px)')
                            ->numeric()
                            ->visible(fn (Get $get): bool => (bool) $get('meta.image_only')),
                        TextInput::make('meta.image_max_height')
                            ->label('Max height (px)')
                            ->numeric()
                            ->visible(fn (Get $get): bool => (bool) $get('meta.image_only')),
                        Toggle::make('meta.downloadable')
                            ->label('Downloadable')
                            ->default(true)
                            ->inline(false),
                        Toggle::make('meta.openable')
                            ->label('Openable')
                            ->default(true)
                            ->inline(false),
                        Toggle::make('meta.previewable')
                            ->label('Previewable')
                            ->default(true)
                            ->inline(false),
                        Toggle::make('meta.reorderable')
                            ->label('Reorderable')
                            ->default(true)
                            ->visible(fn (Get $get): bool => (bool) $get('meta.multiple'))
                            ->inline(false),
                        Toggle::make('meta.optimize_filenames')
                            ->label('Optimize file names')
                            ->helperText('Slug + unique suffix, e.g. my-resume-a1b2c3d4.pdf')
                            ->default(true)
                            ->inline(false),
                        Repeater::make('meta.conversions')
                            ->label('Image conversions')
                            ->schema([
                                TextInput::make('name')->required()->maxLength(50)->placeholder('thumb'),
                                TextInput::make('width')->numeric()->minValue(1),
                                TextInput::make('height')->numeric()->minValue(1),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->collapsed()
                            ->collapsible()
                            ->reorderable()
                            ->addActionLabel('Add conversion')
                            ->columnSpanFull()
                            ->helperText('Registered on the host model via HasCustomFieldMedia (e.g. thumb 150×150).'),
                    ]),
                Section::make('Options')
                    ->description('Used for Select, Radio, Multi-select, and Tags. Collapsed rows show the option label.')
                    ->visible(fn (Get $get): bool => FieldTypes::requiresOptions((string) $get('type')))
                    ->schema([
                        Repeater::make('options')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->schema([
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                TextInput::make('value')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                Select::make('color')
                                    ->label('Badge color')
                                    ->options(ThemeColors::selectOptions())
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Auto (theme)')
                                    ->helperText('Used for Tags badges. Leave empty to auto-assign from the Filament theme palette.'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->itemLabel(function (array $state): ?string {
                                $label = trim((string) ($state['label'] ?? ''));
                                if ($label !== '') {
                                    return $label;
                                }

                                $value = trim((string) ($state['value'] ?? ''));

                                return $value !== '' ? $value : null;
                            })
                            ->collapsed()
                            ->collapsible()
                            ->reorderable()
                            ->deletable()
                            ->addActionLabel('Add option'),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function diskOptions(): array
    {
        $disks = array_keys(config('filesystems.disks', []));
        $options = ['default' => 'Default (package / Filament)'];

        foreach ($disks as $disk) {
            $options[$disk] = $disk;
        }

        return $options;
    }
}
