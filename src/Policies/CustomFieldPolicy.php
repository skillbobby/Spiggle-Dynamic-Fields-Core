<?php

declare(strict_types=1);

namespace Spiggle\DynamicFields\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spiggle\DynamicFields\Models\CustomField;
use Spiggle\DynamicFields\Support\CustomFieldAuthorization;

class CustomFieldPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function view(AuthUser $authUser, CustomField $customField): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function update(AuthUser $authUser, CustomField $customField): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function delete(AuthUser $authUser, CustomField $customField): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function restore(AuthUser $authUser, CustomField $customField): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function forceDelete(AuthUser $authUser, CustomField $customField): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function replicate(AuthUser $authUser, CustomField $customField): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return CustomFieldAuthorization::userCanManage($authUser);
    }
}
