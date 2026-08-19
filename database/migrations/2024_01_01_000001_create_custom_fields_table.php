<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('dynamic-fields.tables.fields', 'custom_fields');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('target_model');
            $blueprint->string('name');
            $blueprint->string('label');
            $blueprint->string('type');
            $blueprint->boolean('is_required')->default(false);
            $blueprint->json('validation_rules')->nullable();
            $blueprint->json('meta')->nullable();
            $blueprint->unsignedInteger('sort_order')->default(0);
            $blueprint->timestamps();

            $blueprint->unique(['target_model', 'name']);
            $blueprint->index('target_model');
            $blueprint->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dynamic-fields.tables.fields', 'custom_fields'));
    }
};
