<?php

namespace App\Policies;

use App\Models\SuperUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class SuperUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(Authenticatable $auth)
    {
        return $auth instanceof SuperUser;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(Authenticatable $auth, SuperUser $superUser)
    {
        return $auth instanceof SuperUser;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(Authenticatable $auth)
    {
        return $auth instanceof SuperUser;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(Authenticatable $auth, SuperUser $superUser)
    {
        return $auth instanceof SuperUser;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(Authenticatable $auth, SuperUser $superUser)
    {
        return $auth instanceof SuperUser;
    }
}
