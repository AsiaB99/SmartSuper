@extends('layouts.app')

@section('title', __('productos.index.meta_title'))

@section('content')
    @php($esAdmin = auth()->user()?->isAdmin() ?? false)

    <section class="ss-header-gradient mb-8 flex flex-wrap items-center justify-between gap-6 rounded-lg border border-white/70 p-6 shadow-soft">
        <div>
            <p class="text-sm font-semibold uppercase text-brand-700">{{ $esAdmin ? __('productos.index.kicker_admin') : __('productos.index.kicker_catalog') }}</p>
            <h1 class="mt-2 font-display text-4xl text-ink-900">{{ __('productos.index.title') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600">{{ $esAdmin ? __('productos.index.subtitle_admin') : __('productos.index.subtitle_catalog') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a class="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-50" href="{{ route('precios.index') }}">{{ __('productos.index.view_prices') }}</a>
            @if ($esAdmin)
                <a class="inline-flex items-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700" href="{{ route('admin.productos.create') }}">{{ __('productos.index.new') }}</a>
            @endif
        </div>
    </section>

    <section class="grid gap-4 rounded-lg border border-white/70 bg-white/85 p-5 shadow-soft">
        @forelse ($productos as $producto)
            <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-brand-100 bg-mist/70 p-5">
                <div>
                    <h2 class="text-xl font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                    <p class="mt-2 text-sm text-ink-600">{{ __('productos.index.section') }} <strong class="text-ink-900">{{ $producto->seccion->nombre_seccion }}</strong></p>
                </div>
                @if ($esAdmin)
                    <div class="flex flex-wrap items-center gap-3">
                        <a class="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-50" href="{{ route('admin.productos.edit', $producto) }}">{{ __('common.edit') }}</a>
                        <form action="{{ route('admin.productos.destroy', $producto) }}" method="POST" onsubmit="return confirm('{{ __('productos.index.delete_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center rounded-full bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-rose-500" type="submit">{{ __('common.delete') }}</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-brand-200 bg-white p-6">
                <h2 class="text-xl font-semibold text-ink-900">{{ __('productos.index.empty.title') }}</h2>
                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('productos.index.empty.text') }}</p>
            </div>
        @endforelse
    </section>

    <div class="rounded-lg border border-white/70 bg-white/80 p-4 shadow-soft">
        {{ $productos->links() }}
    </div>
@endsection
