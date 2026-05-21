<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarProductoExternoMapeoRequest;
use App\Http\Requests\CrearProductoDesdeExternoRequest;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use App\Services\MapeoProductosExternosService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductoExternoController extends Controller
{
    public function __construct(
        private readonly MapeoProductosExternosService $mapeoService,
    ) {
    }

    public function index(Request $request): View
    {
        $filtroFuente = trim((string) $request->string('fuente'));
        $filtroEstado = trim((string) $request->string('estado'));
        $busqueda = trim((string) $request->string('busqueda'));
        $busquedaProducto = trim((string) $request->string('busqueda_producto'));
        $externoBuscado = $request->integer('externo');

        $query = ProductoExterno::query()
            ->with('producto.seccion')
            ->when($filtroFuente !== '', function ($builder) use ($filtroFuente): void {
                $builder->where('fuente', $filtroFuente);
            })
            ->when($filtroEstado !== '', function ($builder) use ($filtroEstado): void {
                $builder->where('mapeo_estado', $filtroEstado);
            }, function ($builder): void {
                $builder->whereIn('mapeo_estado', [
                    ProductoExterno::ESTADO_PENDIENTE,
                    ProductoExterno::ESTADO_SUGERIDO,
                ]);
            })
            ->when($busqueda !== '', function ($builder) use ($busqueda): void {
                $builder->where(function ($nested) use ($busqueda): void {
                    $nested->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('marca', 'like', "%{$busqueda}%")
                        ->orWhere('formato', 'like', "%{$busqueda}%")
                        ->orWhere('external_id', 'like', "%{$busqueda}%");
                });
            })
            ->orderByRaw("
                case mapeo_estado
                    when 'sugerido' then 0
                    when 'pendiente' then 1
                    when 'mapeado' then 2
                    when 'descartado' then 3
                    else 4
                end
            ")
            ->orderByDesc('fecha_importacion');

        $productosExternos = $query->paginate(12)->withQueryString();

        $candidatos = $productosExternos->getCollection()->mapWithKeys(function (ProductoExterno $productoExterno) use ($busquedaProducto, $externoBuscado): array {
            $aplicarBusqueda = $externoBuscado === $productoExterno->id ? $busquedaProducto : null;

            return [
                $productoExterno->id => $this->mapeoService
                    ->buscarCandidatosManuales($productoExterno, $aplicarBusqueda),
            ];
        });

        return view('productos-externos.index', [
            'busqueda' => $busqueda,
            'busquedaProducto' => $busquedaProducto,
            'candidatos' => $candidatos,
            'estadosDisponibles' => [
                ProductoExterno::ESTADO_PENDIENTE,
                ProductoExterno::ESTADO_SUGERIDO,
                ProductoExterno::ESTADO_MAPEADO,
                ProductoExterno::ESTADO_DESCARTADO,
            ],
            'externoBuscado' => $externoBuscado,
            'filtroEstado' => $filtroEstado,
            'filtroFuente' => $filtroFuente,
            'fuentesDisponibles' => ProductoExterno::query()
                ->select('fuente')
                ->distinct()
                ->orderBy('fuente')
                ->pluck('fuente'),
            'productosExternos' => $productosExternos,
            'resumenEstados' => $this->resumenEstados(),
            'secciones' => Seccion::query()->orderBy('nombre_seccion')->get(['id', 'nombre_seccion']),
        ]);
    }

    public function confirmar(
        ConfirmarProductoExternoMapeoRequest $request,
        ProductoExterno $productoExterno,
    ): RedirectResponse {
        $producto = \App\Models\Producto::query()->findOrFail($request->integer('producto_id'));

        $this->mapeoService->confirmarMapeo($productoExterno, $producto);

        return redirect()
            ->route('admin.productos-externos.index', $this->buildRedirectFilters($request))
            ->with('status', __('flash.productos_externos.mapped'));
    }

    public function store(
        CrearProductoDesdeExternoRequest $request,
        ProductoExterno $productoExterno,
    ): RedirectResponse {
        $this->mapeoService->crearYMapearProducto($productoExterno, $request->validated());

        return redirect()
            ->route('admin.productos-externos.index', $this->buildRedirectFilters($request))
            ->with('status', __('flash.productos_externos.created_and_mapped'));
    }

    public function descartar(Request $request, ProductoExterno $productoExterno): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $this->mapeoService->descartar($productoExterno);

        return redirect()
            ->route('admin.productos-externos.index', $this->buildRedirectFilters($request))
            ->with('status', __('flash.productos_externos.discarded'));
    }

    /**
     * @return array<string, int>
     */
    private function resumenEstados(): array
    {
        return [
            ProductoExterno::ESTADO_PENDIENTE => ProductoExterno::query()->where('mapeo_estado', ProductoExterno::ESTADO_PENDIENTE)->count(),
            ProductoExterno::ESTADO_SUGERIDO => ProductoExterno::query()->where('mapeo_estado', ProductoExterno::ESTADO_SUGERIDO)->count(),
            ProductoExterno::ESTADO_MAPEADO => ProductoExterno::query()->where('mapeo_estado', ProductoExterno::ESTADO_MAPEADO)->count(),
            ProductoExterno::ESTADO_DESCARTADO => ProductoExterno::query()->where('mapeo_estado', ProductoExterno::ESTADO_DESCARTADO)->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildRedirectFilters(Request $request): array
    {
        return collect(['fuente', 'estado', 'busqueda', 'busqueda_producto', 'externo'])
            ->mapWithKeys(function (string $key) use ($request): array {
                $value = trim((string) $request->input($key, ''));

                return $value === '' ? [] : [$key => $value];
            })
            ->all();
    }
}
