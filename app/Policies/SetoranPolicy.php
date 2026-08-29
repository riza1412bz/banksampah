<?php

namespace App\Policies;

use App\Models\Setoran;
use App\Models\User;

class SetoranPolicy
{
    /** Nasabah hanya boleh melihat setoran miliknya sendiri. Admin boleh semua. */
    public function view(User $user, Setoran $setoran): bool
    {
        return $user->isAdmin() || $user->id === $setoran->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
