<?php

namespace App\Policies;

use App\Models\Billet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BilletPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function create(User $user)
    {
        return $user->role === 'saisie' || $user->role === 'admin';
    }

    public function update(User $user, Billet $billet)
    {
        return $user->role === 'admin';
    }

}
