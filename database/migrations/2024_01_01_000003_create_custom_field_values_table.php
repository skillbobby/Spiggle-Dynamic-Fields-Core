<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('dynamic-fields.tables.values', 'custom_field_values');
        $fieldsTable = config('dynamic-fields.tables.fields', 'custom_fields');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $blueprint) use ($fieldsTable): void {
                $blueprint->id();
                $blueprint->foreignId('custom_field_id')
                    ->constrained($fieldsTable)
                    ->cascadeOnDelete();
                $blueprint->string('model_type');
                $blueprint->unsignedBigInteger('model_id');
                $blueprint->longText('value')->nullable();
                $blueprint->string('value_type')->nullable();
                $blueprint->timestamps();

                $blueprint->index(['model_type', 'model_id']);
                $blueprint->index('custom_field_id');
                $blueprint->unique(['custom_field_id', 'model_type', 'model_id'], 'custom_field_values_unique_per_model');
            });
        } else {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $sm = Schema::getConnection()->getSchemaBuilder();
                $indexes = collect($sm->getIndexes($table))->pluck('name')->all();

                if (! in_array('custom_field_values_unique_per_model', $indexes, true)) {
                    $blueprint->unique(['custom_field_id', 'model_type', 'model_id'], 'custom_field_values_unique_per_model');
                }
            });
        }

        $this->ensureValueSearchIndex($table);
    }

    protected function ensureValueSearchIndex(string $table): void
    {
        $indexes = collect(Schema::getConnection()->getSchemaBuilder()->getIndexes($table))
            ->pluck('name')
            ->all();

        if (in_array('custom_field_values_field_value_index', $indexes, true)) {
            return;
        }

        // MySQL cannot index LONGTEXT without a prefix length. Spec asks for
        // index(custom_field_id, value) for search — use a safe prefix index
        // on MySQL/MariaDB; full composite index elsewhere.
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `custom_field_values_field_value_index` (`custom_field_id`, `value`(191))");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->index(['custom_field_id', 'value'], 'custom_field_values_field_value_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dynamic-fields.tables.values', 'custom_field_values'));
    }
};
