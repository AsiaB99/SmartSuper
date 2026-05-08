@extends('layouts.app')

@section('title', 'Mi Lista | SmartSuper')

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="mb-8">
                <div>
                    <h1 class="ss-title text-left">Mi Lista</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-ink-600">
                        Organiza tus compras, revisa productos y calcula la mejor recomendación de supermercado.
                    </p>
                </div>
            </div>

            <div class="grid gap-10 lg:grid-cols-[1fr_330px]">
                <section class="grid gap-5">
                    @forelse ($listas as $lista)
                        @php($estaComprada = $lista->estado === 'comprada')
                        <article class="flex flex-wrap items-center justify-between gap-5 rounded-[15px] bg-white p-5 shadow-[0_4px_10px_rgba(0,0,0,0.03)] transition duration-300 hover:translate-x-1 hover:shadow-[0_8px_20px_rgba(0,0,0,0.08)]">
                            <div class="flex items-center gap-5">
                                <div class="flex h-[70px] w-[70px] items-center justify-center rounded-[10px] bg-brand-50 text-brand-500">
                                    <x-ui.icon name="list-bullet" class="h-8 w-8" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold text-ink-900">{{ $lista->nombre_lista }}</h2>
                                    <p class="mt-1 text-sm text-ink-500">Creada: {{ optional($lista->fecha_creacion)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $estaComprada ? 'bg-[var(--color-exito-suave)] text-brand-600' : 'bg-accent-100 text-accent-800' }}">
                                        {{ ucfirst($lista->estado) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <a class="ss-btn-outline" href="{{ route('listas.productos', $lista) }}">Productos</a>
                                <a class="ss-btn-outline" href="{{ route('listas.recomendacion', $lista) }}">Recomendar super</a>
                                @can('update', $lista)
                                    <a class="ss-btn-green" href="{{ route('listas.finalizar.confirmar', $lista) }}">Finalizar</a>
                                    <a class="ss-btn-outline" href="{{ route('listas.edit', $lista) }}">Editar</a>
                                @endcan
                                @can('delete', $lista)
                                    <form action="{{ route('listas.destroy', $lista) }}" method="POST" onsubmit="return confirm('¿Eliminar esta lista?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500" type="submit">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                            <h2 class="text-xl font-semibold text-ink-900">No hay listas todavía</h2>
                            <p class="mt-2 text-sm leading-7 text-ink-600">Crea la primera lista para empezar a estructurar el flujo de compra.</p>
                        </div>
                    @endforelse
                </section>

                <aside class="h-fit rounded-[15px] border-t-[5px] border-accent-500 bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.08)] lg:sticky lg:top-24">
                    <h2 class="text-2xl font-semibold text-ink-900">Resumen</h2>
                    <p class="mt-4 text-sm text-ink-600">Listas guardadas:</p>
                    <p class="text-5xl font-bold text-ink-900">{{ $listas->count() }}</p>
                    <div class="my-5 rounded-[10px] bg-[var(--color-info-suave)] p-3 text-sm font-semibold text-brand-600">
                        <x-ui.icon name="shopping-cart" class="mr-1 inline h-4 w-4" />
                        Entra en una lista para ajustar cantidades.
                    </div>
                    <a class="ss-btn-green w-full" href="{{ route('listas.create') }}">Crear lista</a>
                </aside>
            </div>
        </div>
    </section>
@endsection

