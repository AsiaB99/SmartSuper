<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Despensa;
use App\Models\User;

class DespensaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Despensa $despensa): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permiso = $user->permisoEnDespensa($despensa);

        return in_array($permiso, ['owner', 'editor', 'viewer'], true);
    }

    public function create(User $user): bool
    {
        return $user->id !== null;
    }

    public function update(User $user, Despensa $despensa): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permiso = $user->permisoEnDespensa($despensa);

        return in_array($permiso, ['owner', 'editor'], true);
    }

    public function delete(User $user, Despensa $despensa): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->permisoEnDespensa($despensa) === 'owner';
    }
}
