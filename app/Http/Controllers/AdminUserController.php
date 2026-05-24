<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->string('name')->toString(),
            'nombre_usuario' => $request->string('nombre_usuario')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'rol' => 'cliente',
        ]);

        return redirect()
            ->route('admin.index', $this->redirectQuery($request))
            ->with('status', __('flash.admin.users.created'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if ($admin->is($user)) {
            return redirect()
                ->route('admin.index', $this->redirectQuery($request))
                ->withErrors(['usuarios' => __('admin.users.errors.self_delete')]);
        }

        if ($user->isAdmin() && User::query()->where('rol', 'admin')->count() <= 1) {
            return redirect()
                ->route('admin.index', $this->redirectQuery($request))
                ->withErrors(['usuarios' => __('admin.users.errors.last_admin')]);
        }

        $user->delete();

        return redirect()
            ->route('admin.index', $this->redirectQuery($request))
            ->with('status', __('flash.admin.users.deleted'));
    }

    private function redirectQuery(Request $request): array
    {
        return array_filter([
            'tab' => 'usuarios',
            'usuarios_busqueda' => trim((string) $request->query('usuarios_busqueda', '')),
            'usuarios_page' => $request->query('usuarios_page'),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }
}
