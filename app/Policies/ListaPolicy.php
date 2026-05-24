<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lista;
use App\Models\User;

class ListaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lista $lista): bool
    {
        $permiso = $user->permisoEnLista($lista);

        return in_array($permiso, ['owner', 'editor', 'viewer'], true);
    }

    public function create(User $user): bool
    {
        return $user->id !== null;
    }

    public function update(User $user, Lista $lista): bool
    {
        $permiso = $user->permisoEnLista($lista);

        return in_array($permiso, ['owner', 'editor'], true);
    }

    public function delete(User $user, Lista $lista): bool
    {
        return $user->permisoEnLista($lista) === 'owner';
    }
}
