<?php

namespace App\Relationships;

use App\Models\TenantUser;
use App\Models\User;
use Filament\Models\Contracts\HasTenants;

trait TenantHasUsers
{
    public function users()
    {
        return $this->belongsToMany(User::class, (new TenantUser)->getTable())
            ->using(TenantUser::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function userCanAccess(User $user)
    {
        if (! $user instanceof HasTenants) {
            return false;
        }

        return $user->canAccessTenant($this);
    }

    public function addUsers(array $users = [], ?string $role = null)
    {
        $attributes = (new TenantUser)->parseData(['role' => $role]);
        (new PivotHelper($this, 'users'))->addPivots($users, $attributes);
    }

    public function removeUsers(array $users = [])
    {
        (new PivotHelper($this, 'users'))->removePivots($users);
    }

    public function addUser(User $user, ?string $role = null)
    {
        $attributes = (new TenantUser)->parseData(['role' => $role]);
        (new PivotHelper($this, 'users'))->addPivot($user, $attributes);

        return $this;
    }

    public function updateUser(User $user, ?string $role = null)
    {
        $attributes = (new TenantUser)->parseData(['role' => $role]);
        (new PivotHelper($this, 'users'))->updatePivot($user, $attributes);

        return $this;
    }

    public function removeUser(User $user)
    {
        (new PivotHelper($this, 'users'))->removePivot($user);

        return $this;
    }

    public function syncUsers($users)
    {
        (new PivotHelper($this, 'users'))->syncPivots($users);

        return $this;
    }
}
