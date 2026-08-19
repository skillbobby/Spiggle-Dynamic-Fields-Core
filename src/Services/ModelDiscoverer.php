<?php

namespace Spiggle\DynamicFields\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

class ModelDiscoverer
{
    /**
     * Discover Eloquent model FQCNs for the Field Manager target dropdown.
     *
     * @return array<string, string> class => label
     */
    public function discover(): array
    {
        $paths = config('dynamic-fields.model_discovery.paths', ['app/Models']);
        $exclude = config('dynamic-fields.model_discovery.exclude', []);
        $found = [];

        foreach ($paths as $relative) {
            $absolute = base_path($relative);

            if (! File::isDirectory($absolute)) {
                continue;
            }

            foreach (File::allFiles($absolute) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = $this->classFromFile($file->getPathname(), $absolute, $relative);

                if (! $class || in_array($class, $exclude, true)) {
                    continue;
                }

                if (! $this->isEloquentModel($class)) {
                    continue;
                }

                $found[$class] = class_basename($class).' ('.$class.')';
            }
        }

        ksort($found);

        return $found;
    }

    protected function classFromFile(string $path, string $baseDir, string $relativeBase): ?string
    {
        $relative = Str::after($path, $baseDir.DIRECTORY_SEPARATOR);
        $relative = str_replace(['/', '\\'], '\\', $relative);
        $classPath = Str::beforeLast($relative, '.php');

        $namespaceRoot = str_replace('/', '\\', trim($relativeBase, '/\\'));
        // app/Models => App\Models
        $namespaceRoot = collect(explode('\\', $namespaceRoot))
            ->map(fn (string $part) => Str::studly($part))
            ->implode('\\');

        return $namespaceRoot.'\\'.$classPath;
    }

    protected function isEloquentModel(string $class): bool
    {
        try {
            if (! class_exists($class)) {
                return false;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                return false;
            }

            return $reflection->isSubclassOf(Model::class);
        } catch (Throwable) {
            return false;
        }
    }
}
