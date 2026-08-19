<?php

namespace Spiggle\DynamicFields\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Spiggle\DynamicFields\Models\CustomField;

class DynamicFieldRegistry
{
    /** @var array<string, Closure> */
    protected array $formDrivers = [];

    /** @var array<string, Closure> */
    protected array $tableDrivers = [];

    /** @var array<string, Closure> */
    protected array $infolistDrivers = [];

    protected ?Closure $visibilityApplier = null;

    protected ?Closure $badgeRenderer = null;

    protected ?Closure $proSync = null;

    /**
     * Pro package registers a callback that binds drivers after a license check.
     */
    public function onSync(?Closure $callback): void
    {
        $this->proSync = $callback;
    }

    /**
     * Drop previous Pro drivers, then let Pro re-bind when authorized.
     */
    public function syncProDrivers(): void
    {
        $this->clearPro();

        if ($this->proSync instanceof Closure) {
            ($this->proSync)($this);
        }
    }

    /**
     * @param  array{form?: Closure, table?: Closure, infolist?: Closure}  $drivers
     */
    public function register(string $type, array $drivers): void
    {
        if (($drivers['form'] ?? null) instanceof Closure) {
            $this->formDrivers[$type] = $drivers['form'];
        }

        if (($drivers['table'] ?? null) instanceof Closure) {
            $this->tableDrivers[$type] = $drivers['table'];
        }

        if (($drivers['infolist'] ?? null) instanceof Closure) {
            $this->infolistDrivers[$type] = $drivers['infolist'];
        }
    }

    public function registerVisibility(Closure $applier): void
    {
        $this->visibilityApplier = $applier;
    }

    public function registerBadgeRenderer(Closure $renderer): void
    {
        $this->badgeRenderer = $renderer;
    }

    public function hasForm(string $type): bool
    {
        return isset($this->formDrivers[$type]);
    }

    public function form(string $type): ?Closure
    {
        return $this->formDrivers[$type] ?? null;
    }

    public function table(string $type): ?Closure
    {
        return $this->tableDrivers[$type] ?? null;
    }

    public function infolist(string $type): ?Closure
    {
        return $this->infolistDrivers[$type] ?? null;
    }

    public function allows(CustomField $field): bool
    {
        if (FeatureCatalog::isProType($field->type)) {
            return $this->hasForm($field->type);
        }

        return true;
    }

    public function applyVisibility(mixed $component, CustomField $field, string $stateKey): void
    {
        if ($this->visibilityApplier instanceof Closure) {
            ($this->visibilityApplier)($component, $field, $stateKey);
        }
    }

    public function badgeHtml(Model $record, CustomField $field): ?string
    {
        if ($this->badgeRenderer instanceof Closure) {
            $html = ($this->badgeRenderer)($record, $field);

            return is_string($html) && $html !== '' ? $html : null;
        }

        return null;
    }

    public function clearPro(): void
    {
        foreach (array_merge(FeatureCatalog::proTypes(), ['textarea']) as $type) {
            unset($this->formDrivers[$type], $this->tableDrivers[$type], $this->infolistDrivers[$type]);
        }

        $this->visibilityApplier = null;
        $this->badgeRenderer = null;
    }
}
