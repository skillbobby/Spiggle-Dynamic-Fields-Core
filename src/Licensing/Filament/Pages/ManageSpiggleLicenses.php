<?php

namespace Spiggle\DynamicFields\Licensing\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spiggle\DynamicFields\Licensing\AddonLicenseRegistry;
use Spiggle\DynamicFields\Licensing\AddonRegistration;
use UnitEnum;

class ManageSpiggleLicenses extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $slug = 'spiggle-licenses';

    protected static ?string $title = 'Spiggle Licenses';

    protected static ?string $navigationLabel = 'Spiggle Licenses';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('spiggle-licensing.navigation.group', 'System');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('spiggle-licensing.navigation.sort', 90);
    }

    public static function getNavigationLabel(): string
    {
        return (string) config('spiggle-licensing.navigation.label', 'Spiggle Licenses');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! app()->bound(AddonLicenseRegistry::class)) {
            return false;
        }

        return app(AddonLicenseRegistry::class)->hasAddons();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! app()->bound(AddonLicenseRegistry::class)) {
            return false;
        }

        foreach (app(AddonLicenseRegistry::class)->all() as $addon) {
            if (self::userCanManageAddon($user, $addon)) {
                return true;
            }
        }

        return false;
    }

    public function mount(): void
    {
        $this->data = [
            'license_keys' => [],
        ];
    }

    public function content(Schema $schema): Schema
    {
        $registry = app(AddonLicenseRegistry::class);
        $sections = [];

        foreach ($registry->all() as $addon) {
            $sections[] = $this->addonSection($registry, $addon);
        }

        return $schema
            ->statePath('data')
            ->components($sections);
    }

    protected function addonSection(AddonLicenseRegistry $registry, AddonRegistration $addon): Section
    {
        $manager = $registry->manager($addon);
        $status = $manager->status();
        $authorized = (bool) ($status['authorized'] ?? false) && (bool) ($status['activated'] ?? false);
        $enforced = (bool) ($status['enforced'] ?? true);
        $checkout = trim((string) ($status['checkout_url'] ?? ''));

        return Section::make($authorized ? $addon->name.' · Activated' : 'Activate '.$addon->name)
            ->description($authorized
                ? 'This server is bound to a Lemon Squeezy license instance. The key is stored encrypted and shown masked only.'
                : $addon->inactiveDescription)
            ->schema([
                Text::make(fn (): string => ! $enforced
                    ? 'Licensing disabled (dev)'
                    : ($authorized ? 'Activated' : 'Not activated'))
                    ->badge()
                    ->color(! $enforced ? 'warning' : ($authorized ? 'success' : 'danger')),
                Text::make(fn (): string => $addon->installedVersionsLabel())
                    ->color('gray')
                    ->visible(fn (): bool => $addon->installedVersionsLabel() !== ''),
                Text::make(fn () => $authorized
                    ? 'Key '.$status['masked_key'].' · instance '.substr((string) ($status['instance_id'] ?? ''), 0, 8).'…'
                    : $addon->inactiveDescription)
                    ->visible(true),
                TextInput::make('license_keys.'.$addon->id)
                    ->label('License key')
                    ->password()
                    ->revealable()
                    ->required()
                    ->visible(! $authorized)
                    ->autocomplete('off')
                    ->dehydrated(false),
                Actions::make([
                    Action::make('activate_'.$addon->id)
                        ->label('Activate')
                        ->color('primary')
                        ->visible(! $authorized)
                        ->action(fn () => $this->activateLicense($addon->id)),
                    Action::make('deactivate_'.$addon->id)
                        ->label('Deactivate')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible($authorized)
                        ->action(fn () => $this->deactivateLicense($addon->id)),
                    Action::make('purchase_'.$addon->id)
                        ->label($addon->purchaseLabel)
                        ->url($checkout !== '' ? $checkout : null)
                        ->openUrlInNewTab()
                        ->color('gray')
                        ->visible(! $authorized && $checkout !== ''),
                ]),
            ]);
    }

    public function activateLicense(string $addonId): void
    {
        $registry = app(AddonLicenseRegistry::class);
        $addon = $registry->find($addonId);

        if ($addon === null) {
            return;
        }

        $key = trim((string) data_get($this->data, 'license_keys.'.$addonId, ''));
        $result = $registry->manager($addon)->activate($key);

        data_set($this->data, 'license_keys.'.$addonId, '');

        $this->notifyResult($result);
    }

    public function deactivateLicense(string $addonId): void
    {
        $registry = app(AddonLicenseRegistry::class);
        $addon = $registry->find($addonId);

        if ($addon === null) {
            return;
        }

        $result = $registry->manager($addon)->deactivate();

        $this->notifyResult($result);
    }

    protected function notifyResult(object $result): void
    {
        $notification = Notification::make()->title((string) ($result->message ?? 'Done'));

        if ((bool) ($result->ok ?? false)) {
            $notification->success()->send();

            return;
        }

        $notification->danger()->send();
    }

    protected static function userCanManageAddon(object $user, AddonRegistration $addon): bool
    {
        $permission = $addon->permission;

        if ($permission === null || $permission === '') {
            return true;
        }

        if (method_exists($user, 'can') && method_exists($user, 'hasRole')) {
            try {
                if ($user->can($permission) || $user->hasRole('super_admin')) {
                    return true;
                }

                if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
                    return true;
                }

                $exists = \Spatie\Permission\Models\Permission::query()
                    ->where('name', $permission)
                    ->exists();

                return ! $exists;
            } catch (\Throwable) {
                return true;
            }
        }

        return true;
    }
}
