@extends('layouts.app')

@section('title', 'Productos de lista | SmartSuper')

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-5">
                <div>
                    <h1 class="ss-title text-left">{{ $lista->nombre_lista }}</h1>
                    <p class="mt-2 text-sm text-ink-600">Lista semanal con cantidades y productos marcados.</p>
                </div>
                <a class="ss-btn-outline" href="{{ route('listas.index') }}">Volver</a>
            </div>

            <form class="mb-6 flex flex-wrap items-end gap-3 rounded-[15px] bg-white p-5 shadow-[0_5px_15px_rgba(0,0,0,0.05)]" action="{{ route('listas.productos', $lista) }}" method="GET">
                <label class="grid min-w-[260px] flex-1 gap-2">
                    <span class="text-sm font-semibold text-ink-700">Buscar producto</span>
                    <input class="ss-input" type="search" name="q" value="{{ $busqueda }}" placeholder="Ej: arroz, pasta, leche">
                </label>
                <button class="ss-btn-outline" type="submit">Buscar</button>
                @if ($busqueda !== '')
                    <a class="ss-btn-outline" href="{{ route('listas.productos', $lista) }}">Limpiar</a>
                @endif
            </form>

            @if ($puedeEditar)
                <section class="mb-8 rounded-[15px] bg-white p-6 shadow-[0_5px_15px_rgba(0,0,0,0.05)]">
                    <h2 class="text-2xl font-semibold text-ink-900">Agregar a lista</h2>
                    <form class="mt-5 grid gap-5 md:grid-cols-[1fr_180px_auto]" action="{{ route('listas.productos.agregar', $lista) }}" method="POST">
                        @csrf
                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-ink-700">Producto</span>
                            <select class="ss-input" name="id_producto" required>
                                <option value="">Selecciona producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}" @selected((int) old('id_producto') === $producto->id)>
                                        {{ $producto->nombre_producto }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_producto')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-ink-700">Cantidad</span>
                            <input class="ss-input" type="number" name="cantidad" min="1" step="1" value="{{ old('cantidad', 1) }}" required>
                            @error('cantidad')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                        </label>
                        <button class="ss-btn-green self-end" type="submit">Añadir</button>
                    </form>
                </section>
            @endif

            <div class="grid gap-10 lg:grid-cols-[1fr_330px]">
                <section class="grid gap-5">
                    @forelse ($lista->productos as $producto)
                        <article class="flex flex-wrap items-center justify-between gap-5 rounded-[15px] bg-white p-5 shadow-[0_4px_10px_rgba(0,0,0,0.03)] transition duration-300 hover:translate-x-1 hover:shadow-[0_8px_20px_rgba(0,0,0,0.08)]">
                            <div class="flex items-center gap-5">
                                <div class="flex h-[70px] w-[70px] items-center justify-center rounded-[10px] bg-brand-50 text-brand-500">
                                    <x-ui.icon name="shopping-bag" class="h-8 w-8" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                                    <p class="text-sm text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: 'Producto de lista' }}</p>
                                </div>
                            </div>

                            @if ($puedeEditar)
                                <div class="flex flex-wrap items-center gap-3">
                                    <form action="{{ route('listas.productos.actualizar', [$lista, $producto]) }}" method="POST" class="flex flex-wrap items-center gap-3">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-center gap-3 rounded-full border border-[var(--color-borde-suave)] bg-white p-1">
                                            <input class="w-20 border-0 bg-transparent px-3 py-2 text-center font-semibold focus:ring-0" type="number" name="cantidad" min="1" step="1" value="{{ (int) $producto->pivot->cantidad }}" required>
                                        </div>
                                        <label class="inline-flex items-center gap-2 text-sm font-medium text-ink-700">
                                            <input type="checkbox" name="marcado" value="1" @checked($producto->pivot->marcado)>
                                            Marcado
                                        </label>
                                        <button class="ss-btn-outline" type="submit">Guardar</button>
                                    </form>

                                    <form action="{{ route('listas.productos.quitar', [$lista, $producto]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="p-3 text-rose-600 transition hover:scale-110" type="submit" aria-label="Quitar {{ $producto->nombre_producto }}">
                                            <x-ui.icon name="trash" class="h-5 w-5" />
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="rounded-full border border-[var(--color-borde-suave)] px-5 py-2 font-semibold">
                                    {{ (int) $producto->pivot->cantidad }}
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                            <h2 class="text-xl font-semibold text-ink-900">Sin productos</h2>
                            <p class="mt-2 text-sm leading-7 text-ink-600">Añade productos para empezar la lista de compra.</p>
                        </div>
                    @endforelse
                </section>

                <aside class="h-fit rounded-[15px] border-t-[5px] border-accent-500 bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.08)] lg:sticky lg:top-24">
                    <h2 class="text-2xl font-semibold text-ink-900">Resumen</h2>
                    <p class="mt-4 text-sm text-ink-600">Productos en lista:</p>
                    <p class="text-5xl font-bold text-ink-900">{{ $lista->productos->count() }}</p>
                    <div class="my-5 rounded-[10px] bg-[var(--color-info-suave)] p-3 text-sm font-semibold text-brand-600">
                        <x-ui.icon name="shopping-cart" class="mr-1 inline h-4 w-4" />
                        Listo para calcular recomendación.
                    </div>
                    <a class="ss-btn-green w-full text-center {{ ! $puedeEditar ? 'pointer-events-none opacity-60' : '' }}"
                       href="{{ $puedeEditar ? route('listas.finalizar.confirmar', $lista) : '#' }}">
                        Finalizar Compra
                    </a>
                </aside>
            </div>
        </div>
    </section>
@endsection

