<?php

namespace Spiggle\DynamicFields\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class StandardUserFieldsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('dynamic-fields:seed-user-fields');
    }
}
