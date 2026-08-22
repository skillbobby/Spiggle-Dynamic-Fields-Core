<?php

namespace Spiggle\DynamicFields;

use Illuminate\Support\ServiceProvider;
use Spiggle\DynamicFields\Console\ExportFieldsCommand;
use Spiggle\DynamicFields\Console\ImportFieldsCommand;
use Spiggle\DynamicFields\Console\SeedStandardUserFieldsCommand;
use Spiggle\DynamicFields\Console\VerifyFieldsCommand;
use Spiggle\DynamicFields\Services\CustomFieldMapper;
use Spiggle\DynamicFields\Services\FieldDefinitionCache;
use Spiggle\DynamicFields\Services\ModelDiscoverer;
use Spiggle\DynamicFields\Support\DynamicFieldRegistry;
use Spiggle\DynamicFields\Licensing\AddonLicenseRegistry;

class DynamicFieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dynamic-fields.php', 'dynamic-fields');
        $this->mergeConfigFrom(__DIR__.'/../config/spiggle-licensing.php', 'spiggle-licensing');

        $this->app->singleton(CustomFieldMapper::class);
        $this->app->singleton(ModelDiscoverer::class);
        $this->app->singleton(FieldDefinitionCache::class);
        $this->app->singleton(DynamicFieldRegistry::class);
        $this->app->singleton(AddonLicenseRegistry::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/dynamic-fields.php' => config_path('dynamic-fields.php'),
        ], 'dynamic-fields-config');

        $this->publishes([
            __DIR__.'/../config/spiggle-licensing.php' => config_path('spiggle-licensing.php'),
        ], 'spiggle-licensing-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'dynamic-fields-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedStandardUserFieldsCommand::class,
                ExportFieldsCommand::class,
                ImportFieldsCommand::class,
                VerifyFieldsCommand::class,
            ]);
        }
    }
}
