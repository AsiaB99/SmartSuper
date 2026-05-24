<?php

namespace App\Http\Controllers;

use App\Models\CadenaSupermercado;
use App\Models\Producto;
use App\Models\Supermercado;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
    private const TAB_SUPERMERCADOS = 'supermercados';
    private const TAB_PRODUCTOS = 'productos';
    private const TAB_USUARIOS = 'usuarios';

    public function index(Request $request): View
    {
        $tab = $this->resolveTab((string) $request->query('tab', self::TAB_SUPERMERCADOS));
        $supermercadosBusqueda = trim((string) $request->string('supermercados_busqueda'));
        $productosBusqueda = trim((string) $request->string('productos_busqueda'));
        $usuariosBusqueda = trim((string) $request->string('usuarios_busqueda'));

        $cadenas = CadenaSupermercado::query()
            ->withCount('supermercados')
            ->withCount([
                'supermercados as supermercados_activos_count' => fn (Builder $query): Builder => $query->where('activo', true),
            ])
            ->when($supermercadosBusqueda !== '', function (Builder $query) use ($supermercadosBusqueda): Builder {
                return $query->where('nombre', 'like', "%{$supermercadosBusqueda}%");
            })
            ->orderBy('nombre')
            ->paginate(10, ['*'], 'cadenas_page')
            ->withQueryString();

        $supermercados = Supermercado::query()
            ->with('cadena')
            ->when($supermercadosBusqueda !== '', function (Builder $query) use ($supermercadosBusqueda): Builder {
                return $query->where(function (Builder $nestedQuery) use ($supermercadosBusqueda): void {
                    $nestedQuery->where('nombre_super', 'like', "%{$supermercadosBusqueda}%")
                        ->orWhere('direccion', 'like', "%{$supermercadosBusqueda}%")
                        ->orWhereHas('cadena', function (Builder $cadenaQuery) use ($supermercadosBusqueda): void {
                            $cadenaQuery->where('nombre', 'like', "%{$supermercadosBusqueda}%");
                        });
                });
            })
            ->orderByDesc('activo')
            ->orderBy('nombre_super')
            ->paginate(8, ['*'], 'supermercados_page')
            ->withQueryString();

        $productos = Producto::query()
            ->with('seccion')
            ->when($productosBusqueda !== '', function (Builder $query) use ($productosBusqueda): Builder {
                return $query->where(function (Builder $nestedQuery) use ($productosBusqueda): void {
                    $nestedQuery->where('nombre_producto', 'like', "%{$productosBusqueda}%")
                        ->orWhere('marca', 'like', "%{$productosBusqueda}%")
                        ->orWhere('formato', 'like', "%{$productosBusqueda}%");
                });
            })
            ->orderBy('nombre_producto')
            ->paginate(15, ['*'], 'productos_page')
            ->withQueryString();

        $usuarios = User::query()
            ->where('rol', '!=', 'admin')
            ->when($usuariosBusqueda !== '', function (Builder $query) use ($usuariosBusqueda): Builder {
                return $query->where(function (Builder $nestedQuery) use ($usuariosBusqueda): void {
                    $nestedQuery->where('name', 'like', "%{$usuariosBusqueda}%")
                        ->orWhere('nombre_usuario', 'like', "%{$usuariosBusqueda}%")
                        ->orWhere('email', 'like', "%{$usuariosBusqueda}%");
                });
            })
            ->orderBy('name')
            ->paginate(15, ['*'], 'usuarios_page')
            ->withQueryString();

        $viewData = [
            'tab' => $tab,
            'supermercadosBusqueda' => $supermercadosBusqueda,
            'productosBusqueda' => $productosBusqueda,
            'usuariosBusqueda' => $usuariosBusqueda,
            'cadenas' => $cadenas,
            'supermercados' => $supermercados,
            'productos' => $productos,
            'usuarios' => $usuarios,
        ];

        if ($tab === self::TAB_SUPERMERCADOS && $request->ajax()) {
            return response()->view('admin.partials.supermercados-tab', $viewData);
        }

        return view('admin.index', $viewData);
    }

    public function redirectToSupermercadosTab(): RedirectResponse
    {
        return $this->redirectToTab(self::TAB_SUPERMERCADOS);
    }

    public function redirectToProductosTab(): RedirectResponse
    {
        return $this->redirectToTab(self::TAB_PRODUCTOS);
    }

    public function redirectToUsuariosTab(): RedirectResponse
    {
        return $this->redirectToTab(self::TAB_USUARIOS);
    }

    private function redirectToTab(string $tab): RedirectResponse
    {
        return redirect()->route('admin.index', ['tab' => $tab]);
    }

    private function resolveTab(string $tab): string
    {
        return in_array($tab, [
            self::TAB_SUPERMERCADOS,
            self::TAB_PRODUCTOS,
            self::TAB_USUARIOS,
        ], true) ? $tab : self::TAB_SUPERMERCADOS;
    }
}
