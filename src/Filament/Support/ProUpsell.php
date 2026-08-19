<?php

namespace Spiggle\DynamicFields\Filament\Support;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Spiggle\DynamicFields\Support\FeatureCatalog;

class ProUpsell
{
    public static function checkoutUrl(): string
    {
        $url = trim((string) (
            config('dynamic-fields.licensing.checkout_url')
            ?: config('dynamic-fields.upsell.checkout_url', '')
        ));
        $domain = (string) config('app.url');

        if ($url === '') {
            $page = 'Spiggle\\DynamicFields\\Pro\\Filament\\Pages\\ManageAddonLicense';
            if (class_exists($page)) {
                try {
                    return $page::getUrl();
                } catch (\Throwable) {
                    return '';
                }
            }

            return '';
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'checkout[custom][domain]='.urlencode($domain);
    }

    public static function notify(string $feature): void
    {
        $checkout = self::checkoutUrl();

        $notification = Notification::make()
            ->warning()
            ->persistent()
            ->title('Pro Feature Required')
            ->body($feature.' requires a Dynamic Fields Pro license.');

        if ($checkout !== '') {
            $notification->actions([
                Action::make('buy')
                    ->button()
                    ->label('Buy Pro License')
                    ->url($checkout)
                    ->openUrlInNewTab(),
            ]);
        }

        $notification->send();
    }

    public static function guardFieldType(?string $state, callable $set): void
    {
        if ($state === null || $state === '' || FeatureCatalog::proUnlocked()) {
            return;
        }

        if (! FeatureCatalog::isProType($state)) {
            return;
        }

        self::notify(FeatureCatalog::typeTitle($state));
        $set('type', 'text');
    }

    public static function guardUseEditor(mixed $state, callable $set): void
    {
        if (! filter_var($state, FILTER_VALIDATE_BOOLEAN) || FeatureCatalog::proUnlocked()) {
            return;
        }

        self::notify('The built-in text editor');
        $set('meta.use_editor', false);
    }

    public static function guardVisibility(mixed $state, callable $set, string $metaKey): void
    {
        if ($state === null || $state === '' || FeatureCatalog::proUnlocked()) {
            return;
        }

        self::notify('Conditional visibility');
        $set($metaKey, null);
    }
}
