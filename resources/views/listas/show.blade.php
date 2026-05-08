@extends('layouts.app')

@section('title', 'Ver lista | SmartSuper')

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="mb-8 flex flex-wrap items-start justify-between gap-5 rounded-[24px] bg-white px-6 py-6 shadow-[0_14px_35px_rgba(0,0,0,0.06)] sm:px-8">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Vista de lista</p>
                    <h1 class="mt-2 truncate text-4xl font-semibold leading-tight text-ink-900">{{ $lista->nombre_lista }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600">Consulta los detalles de tu lista.</p>
                </div>
                <a class="ss-btn-outline self-center" href="{{ route('listas.index') }}">Volver</a>
            </div>

            <div class="grid gap-10 lg:grid-cols-[1fr_330px]">
                <section class="rounded-[15px] bg-white shadow-[0_4px_10px_rgba(0,0,0,0.03)]">
                    @forelse ($lista->productos as $producto)
                        <article class="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--color-borde-suave)] p-4 last:border-b-0 sm:p-5">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                                <p class="text-sm text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: 'Producto de lista' }}</p>
                            </div>
                            <div class="rounded-full border border-[var(--color-borde-suave)] px-5 py-2 text-sm font-semibold text-ink-700">
                                {{ (int) $producto->pivot->cantidad }}
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                            <h2 class="text-xl font-semibold text-ink-900">Sin productos</h2>
                            <p class="mt-2 text-sm leading-7 text-ink-600">Esta lista todavía no tiene productos añadidos.</p>
                        </div>
                    @endforelse
                </section>

                <x-listas.resumen-aside class="p-10">
                    <h2 class="text-2xl font-semibold text-ink-900">Resumen</h2>
                    <p class="mt-8 text-sm text-ink-600">Estado:</p>
                    <p class="mt-2 text-xl font-semibold text-ink-900">{{ ucfirst($lista->estado) }}</p>
                    <p class="mt-6 text-sm text-ink-600">Productos en lista:</p>
                    <p class="mt-2 text-3xl font-bold text-ink-900">{{ $lista->productos->count() }}</p>
                    @if ($lista->supermercadoElegido)
                        <p class="mt-6 text-sm text-ink-600">Supermercado elegido:</p>
                        <p class="mt-2 text-base font-semibold text-ink-900">{{ $lista->supermercadoElegido->nombre_super }}</p>
                    @endif
                    <p class="mt-6 text-sm text-ink-600">Participan en esta lista:</p>
                    <div class="mt-3 grid gap-3">
                        @foreach ($lista->usuarios as $usuarioParticipante)
                            @php($permiso = $usuarioParticipante->pivot->permiso_lista)
                            <article class="rounded-[12px] border border-[var(--color-borde-suave)] bg-white/70 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-ink-900">
                                            {{ $usuarioParticipante->nombre_usuario ?: $usuarioParticipante->name }}
                                        </p>
                                        @if ($usuarioParticipante->nombre_usuario)
                                            <p class="text-xs text-ink-500">{{ $usuarioParticipante->name }}</p>
                                        @endif
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] {{ $permiso === 'owner' ? 'bg-brand-50 text-brand-700' : ($permiso === 'editor' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $permiso === 'owner' ? 'Owner' : ($permiso === 'editor' ? 'Editor' : 'Viewer') }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </x-listas.resumen-aside>
            </div>
        </div>
    </section>
@endsection
