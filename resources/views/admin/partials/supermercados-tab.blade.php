<section id="admin-supermercados-tab" class="grid items-stretch gap-6 xl:grid-cols-[0.95fr_1.6fr]">
    <div class="flex h-full flex-col rounded-lg border border-white/70 bg-white/90 p-5 shadow-soft">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase text-brand-700">{{ __('admin.supermarkets.kicker') }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-ink-900">{{ __('admin.supermarkets.chains_title') }}</h2>
            </div>
        </div>

        <form id="admin-supermercados-search-form" method="GET" action="{{ route('admin.index') }}" class="mt-5">
            <input type="hidden" name="tab" value="supermercados">
            <label for="supermercados_busqueda" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('common.search') }}</label>
            <div class="flex flex-wrap gap-3">
                <input id="supermercados_busqueda" name="supermercados_busqueda" type="search" value="{{ $supermercadosBusqueda }}" class="ss-input min-w-[220px] flex-1" placeholder="{{ __('admin.supermarkets.search_placeholder') }}">
                <button type="submit" class="ss-btn-green">{{ __('common.search') }}</button>
            </div>
        </form>

        <div class="mt-6 space-y-4">
            @forelse ($cadenas as $cadena)
                <article class="rounded-lg border border-brand-100 bg-mist/70 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-ink-900">{{ $cadena->nombre }}</h3>
                            <p class="mt-2 text-sm text-ink-600">{{ __('admin.supermarkets.chain_summary', ['active' => $cadena->supermercados_activos_count, 'total' => $cadena->supermercados_count]) }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.cadenas-supermercados.toggle', $cadena) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="supermercados_busqueda" value="{{ $supermercadosBusqueda }}">
                            <input type="hidden" name="supermercados_page" value="{{ request('supermercados_page', 1) }}">
                            <input type="hidden" name="chain_action" value="{{ $cadena->supermercados_activos_count > 0 ? 'deactivate' : 'activate' }}">
                            <button type="submit" class="inline-flex items-center rounded-full {{ $cadena->supermercados_activos_count > 0 ? 'bg-rose-600 hover:bg-rose-500' : 'bg-brand-600 hover:bg-brand-700' }} px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition">
                                {{ $cadena->supermercados_activos_count > 0 ? __('admin.supermarkets.deactivate_chain') : __('admin.supermarkets.activate_chain') }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-brand-200 bg-white p-5 text-sm text-ink-600">
                    {{ __('admin.supermarkets.empty_chains') }}
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $cadenas->links() }}
        </div>
    </div>

    <div class="flex h-full flex-col rounded-lg border border-white/70 bg-white/90 p-5 shadow-soft">
        <div>
            <p class="text-sm font-semibold uppercase text-brand-700">{{ __('admin.supermarkets.kicker') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-ink-900">{{ __('admin.supermarkets.stores_title') }}</h2>
            <p class="mt-2 text-sm text-ink-600">{{ __('admin.supermarkets.stores_text') }}</p>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($supermercados as $supermercado)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-brand-100 bg-white p-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-lg font-semibold text-ink-900">{{ $supermercado->nombre_super }}</h3>
                            <span class="rounded-full {{ $supermercado->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }} px-3 py-1 text-xs font-semibold">
                                {{ $supermercado->activo ? __('admin.supermarkets.state_active') : __('admin.supermarkets.state_inactive') }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-ink-600">{{ $supermercado->cadena?->nombre ?? __('admin.supermarkets.no_chain') }}</p>
                        @if ($supermercado->direccion)
                            <p class="mt-1 text-sm text-ink-500">{{ $supermercado->direccion }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.supermercados.toggle', $supermercado) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="supermercados_busqueda" value="{{ $supermercadosBusqueda }}">
                        <input type="hidden" name="supermercados_page" value="{{ $supermercados->currentPage() }}">
                        <button type="submit" class="inline-flex items-center rounded-full {{ $supermercado->activo ? 'bg-rose-600 hover:bg-rose-500' : 'bg-brand-600 hover:bg-brand-700' }} px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition">
                            {{ $supermercado->activo ? __('admin.supermarkets.deactivate_store') : __('admin.supermarkets.activate_store') }}
                        </button>
                    </form>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-brand-200 bg-white p-5 text-sm text-ink-600">
                    {{ __('admin.supermarkets.empty_stores') }}
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $supermercados->links() }}
        </div>
    </div>
</section>
