@extends('layouts.app')

@section('title', __('admin.panel.meta_title'))

@section('content')
    @php
        $tabLinks = [
            'supermercados' => ['label' => __('admin.tabs.supermarkets'), 'query' => ['tab' => 'supermercados']],
            'productos' => ['label' => __('admin.tabs.products'), 'query' => ['tab' => 'productos']],
            'usuarios' => ['label' => __('admin.tabs.users'), 'query' => ['tab' => 'usuarios']],
        ];
    @endphp

    <section class="ss-section">
        <div class="ss-container space-y-8">
            <section class="ss-header-gradient mb-8 rounded-lg border border-white/70 p-6 text-center shadow-soft">
                <div class="mx-auto max-w-4xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('common.admin') }}</p>
                    <h1 class="mt-2 font-display text-4xl font-semibold leading-tight text-ink-900 sm:text-5xl">{{ __('admin.panel.title') }}</h1>
                    <p class="mx-auto mt-3 max-w-3xl text-sm leading-7 text-ink-600">{{ __('admin.panel.subtitle') }}</p>
                </div>
            </section>

            <nav class="flex flex-wrap justify-center gap-3" aria-label="{{ __('admin.panel.navigation') }}">
                @foreach ($tabLinks as $tabKey => $tabLink)
                    <a
                        href="{{ route('admin.index', $tabLink['query']) }}"
                        class="{{ $tab === $tabKey ? 'bg-brand-600 text-white shadow-soft' : 'border border-brand-200 bg-white text-brand-800 hover:bg-brand-50' }} inline-flex items-center rounded-full px-5 py-3 text-sm font-semibold transition"
                    >
                        {{ $tabLink['label'] }}
                    </a>
                @endforeach
            </nav>

            @if ($errors->has('usuarios'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('usuarios') }}
                </div>
            @endif

            @if ($tab === 'supermercados')
                @include('admin.partials.supermercados-tab')
                @push('scripts')
                    @vite('resources/js/admin-supermercados-page.js')
                @endpush
            @endif

            @if ($tab === 'productos')
                <section class="rounded-lg border border-white/70 bg-white/90 p-5 shadow-soft">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase text-brand-700">{{ __('admin.products.kicker') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold text-ink-900">{{ __('admin.products.title') }}</h2>
                        </div>
                        <a class="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 shadow-soft transition hover:bg-brand-50" href="{{ route('admin.productos-externos.index') }}">{{ __('admin.products.external_mapping') }}</a>
                    </div>

                    <form method="GET" action="{{ route('admin.index') }}" class="mt-6">
                        <input type="hidden" name="tab" value="productos">
                        <label for="productos_busqueda" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('common.search') }}</label>
                        <div class="flex flex-wrap gap-3">
                            <input id="productos_busqueda" name="productos_busqueda" type="search" value="{{ $productosBusqueda }}" class="ss-input min-w-[220px] flex-1" placeholder="{{ __('admin.products.search_placeholder') }}">
                            <button type="submit" class="ss-btn-green">{{ __('common.search') }}</button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-4">
                        @forelse ($productos as $producto)
                            <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-brand-100 bg-mist/70 p-5">
                                <div>
                                    <h3 class="text-xl font-semibold text-ink-900">{{ $producto->nombre_producto }}</h3>
                                    <p class="mt-2 text-sm text-ink-600">{{ __('productos.index.section') }} <strong class="text-ink-900">{{ $producto->seccion->nombre_seccion }}</strong></p>
                                    @if ($producto->marca || $producto->formato)
                                        <p class="mt-1 text-sm text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') }}</p>
                                    @endif
                                </div>
                                <form class="js-confirm-delete" data-confirm="{{ __('admin.products.delete_confirm') }}" action="{{ route('admin.productos.destroy', $producto) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="productos_busqueda" value="{{ $productosBusqueda }}">
                                    <input type="hidden" name="productos_page" value="{{ $productos->currentPage() }}">
                                    <button class="inline-flex items-center rounded-full bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-rose-500" type="submit">{{ __('common.delete') }}</button>
                                </form>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-brand-200 bg-white p-6">
                                <h3 class="text-xl font-semibold text-ink-900">{{ __('productos.index.empty.title') }}</h3>
                                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('productos.index.empty.text') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $productos->links() }}
                    </div>
                </section>
            @endif

            @if ($tab === 'usuarios')
                <section class="grid gap-6 xl:grid-cols-[1fr_1.2fr]">
                    <div class="rounded-lg border border-white/70 bg-white/90 p-5 shadow-soft">
                        <p class="text-sm font-semibold uppercase text-brand-700">{{ __('admin.users.kicker') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-ink-900">{{ __('admin.users.create_title') }}</h2>
                        <p class="mt-2 text-sm text-ink-600">{{ __('admin.users.create_text') }}</p>

                        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="mt-6 grid gap-4">
                            @csrf
                            <div>
                                <label for="admin_user_name" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('Name') }}</label>
                                <input id="admin_user_name" name="name" type="text" value="{{ old('name') }}" class="ss-input w-full">
                                @error('name')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="admin_user_username" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('auth.username') }}</label>
                                <input id="admin_user_username" name="nombre_usuario" type="text" value="{{ old('nombre_usuario') }}" class="ss-input w-full">
                                @error('nombre_usuario')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="admin_user_email" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('auth.email') }}</label>
                                <input id="admin_user_email" name="email" type="email" value="{{ old('email') }}" class="ss-input w-full">
                                @error('email')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="admin_user_password" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('auth.password') }}</label>
                                <input id="admin_user_password" name="password" type="password" class="ss-input w-full">
                                @error('password')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="admin_user_password_confirmation" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('auth.password_confirmation') }}</label>
                                <input id="admin_user_password_confirmation" name="password_confirmation" type="password" class="ss-input w-full">
                            </div>
                            <button type="submit" class="ss-btn-green justify-center">{{ __('admin.users.create_submit') }}</button>
                        </form>
                    </div>

                    <div class="rounded-lg border border-white/70 bg-white/90 p-5 shadow-soft">
                        <div>
                            <p class="text-sm font-semibold uppercase text-brand-700">{{ __('admin.users.kicker') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold text-ink-900">{{ __('admin.users.list_title') }}</h2>
                            <p class="mt-2 text-sm text-ink-600">{{ __('admin.users.list_text') }}</p>
                        </div>

                        <form method="GET" action="{{ route('admin.index') }}" class="mt-6">
                            <input type="hidden" name="tab" value="usuarios">
                            <label for="usuarios_busqueda" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('common.search') }}</label>
                            <div class="flex flex-wrap gap-3">
                                <input id="usuarios_busqueda" name="usuarios_busqueda" type="search" value="{{ $usuariosBusqueda }}" class="ss-input min-w-[220px] flex-1" placeholder="{{ __('admin.users.search_placeholder') }}">
                                <button type="submit" class="ss-btn-green">{{ __('common.search') }}</button>
                            </div>
                        </form>

                        <div class="mt-6 space-y-4">
                            @forelse ($usuarios as $usuario)
                                <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-brand-100 bg-white p-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="text-lg font-semibold text-ink-900">{{ $usuario->name }}</h3>
                                            <span class="rounded-full {{ $usuario->isAdmin() ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs font-semibold">
                                                {{ $usuario->isAdmin() ? __('admin.users.role_admin') : __('admin.users.role_client') }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-ink-600">{{ '@'.$usuario->nombre_usuario }}</p>
                                        <p class="mt-1 text-sm text-ink-500">{{ $usuario->email }}</p>
                                    </div>
                                    <form class="js-confirm-delete" data-confirm="{{ __('admin.users.delete_confirm') }}" method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="usuarios_busqueda" value="{{ $usuariosBusqueda }}">
                                        <input type="hidden" name="usuarios_page" value="{{ $usuarios->currentPage() }}">
                                        <button type="submit" class="inline-flex items-center rounded-full bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-rose-500">{{ __('common.delete') }}</button>
                                    </form>
                                </article>
                            @empty
                                <div class="rounded-lg border border-dashed border-brand-200 bg-white p-5 text-sm text-ink-600">
                                    {{ __('admin.users.empty') }}
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-6">
                            {{ $usuarios->links() }}
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
