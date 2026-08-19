<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('dynamic-fields.tables.options', 'custom_field_options');
        $fieldsTable = config('dynamic-fields.tables.fields', 'custom_fields');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $blueprint) use ($fieldsTable): void {
            $blueprint->id();
            $blueprint->foreignId('custom_field_id')
                ->constrained($fieldsTable)
                ->cascadeOnDelete();
            $blueprint->string('label');
            $blueprint->string('value');
            $blueprint->unsignedInteger('sort_order')->default(0);
            $blueprint->timestamps();

            $blueprint->unique(['custom_field_id', 'value']);
            $blueprint->index('custom_field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dynamic-fields.tables.options', 'custom_field_options'));
    }
};
