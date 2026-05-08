@extends('layouts.app')

@section('title', 'Mi Despensa | SmartSuper')

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-5">
                <div>
                    <h1 class="ss-title text-left">Mi Despensa</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-ink-600">
                        Controla tus despensas y entra en el inventario para actualizar el stock de casa.
                    </p>
                </div>
                <a class="ss-btn-green" href="{{ route('despensas.create') }}">
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    <span>Nueva despensa</span>
                </a>
            </div>

            <section class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($despensas as $despensa)
                    <article class="flex flex-col rounded-[15px] bg-white p-6 shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-1">
                        <div class="mb-4 flex items-center gap-4">
                            <div class="flex h-[50px] w-[50px] items-center justify-center rounded-full border-2 border-[#eee] bg-brand-50 text-brand-500">
                                <x-ui.icon name="archive-box" class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-ink-900">{{ $despensa->nombre_despensa }}</h2>
                                <p class="text-xs text-ink-400">Creada: {{ optional($despensa->fecha_creacion)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                            </div>
                        </div>

                        <div class="my-3 h-[10px] overflow-hidden rounded-full bg-[#f1f2f6]">
                            <div class="h-full w-2/3 rounded-full bg-brand-500"></div>
                        </div>
                        <p class="text-sm font-bold text-brand-500">Inventario disponible</p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a class="ss-btn-outline flex-1" href="{{ route('despensas.stock', $despensa) }}">Stock</a>
                            @can('update', $despensa)
                                <a class="ss-btn-outline flex-1" href="{{ route('despensas.edit', $despensa) }}">Editar</a>
                            @endcan
                            @can('delete', $despensa)
                                <form class="w-full" action="{{ route('despensas.destroy', $despensa) }}" method="POST" onsubmit="return confirm('¿Eliminar esta despensa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="w-full rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500" type="submit">Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </article>
                @empty
                    <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6 md:col-span-2 lg:col-span-3">
                        <h2 class="text-xl font-semibold text-ink-900">No hay despensas todavía</h2>
                        <p class="mt-2 text-sm leading-7 text-ink-600">Crea la primera despensa para empezar a gestionar stock.</p>
                    </div>
                @endforelse
            </section>
        </div>
    </section>
@endsection
