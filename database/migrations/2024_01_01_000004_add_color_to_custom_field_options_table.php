<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('dynamic-fields.tables.options', 'custom_field_options');

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'color')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->string('color')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        $table = config('dynamic-fields.tables.options', 'custom_field_options');

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'color')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropColumn('color');
        });
    }
};
