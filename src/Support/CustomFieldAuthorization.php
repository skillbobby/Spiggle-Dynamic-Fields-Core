<?php

declare(strict_types=1);

namespace Spiggle\DynamicFields\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class CustomFieldAuthorization
{
    public static function userCanManage(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $permission = config('dynamic-fields.permissions.manage', 'manage_custom_fields');

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
