<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Any authenticated user may list users.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a user.
     */
    public function view(User $user, User $model): bool
    {
        return true;
    }

    /**
     * Any authenticated user may create users.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Users may only edit their own account.
     */
    public function update(User $user, User $model): bool
    {
        return $user->is($model);
    }

    /**
     * Any authenticated user may delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        return true;
    }
}
