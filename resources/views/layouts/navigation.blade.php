<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white shadow-[0_2px_10px_rgba(0,0,0,0.10)]">
    <div class="ss-container">
        <div class="flex min-h-[70px] items-center justify-between gap-4">
            <div class="flex items-center gap-8">
                <div class="shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-baseline text-ink-900 transition duration-300 hover:scale-105 hover:text-brand-500">
                        <span class="font-display text-[1.75rem] font-semibold leading-none">Smart</span>
                        <span class="font-display text-[1.75rem] font-semibold leading-none text-brand-500 underline">Super</span>
                        <span class="sr-only">Inicio</span>
                    </a>
                </div>

                <div class="hidden items-center gap-1 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->is('/')">
                        Inicio
                    </x-nav-link>
                    <x-nav-link :href="route('listas.index')" :active="request()->routeIs('listas.*')">
                        Mi Lista
                    </x-nav-link>
                    <x-nav-link :href="route('precios.index')" :active="request()->routeIs('precios.*') || request()->routeIs('admin.precios.*')">
                        Comparador
                    </x-nav-link>
                    <x-nav-link :href="route('supermercados.index')" :active="request()->routeIs('supermercados.*') || request()->routeIs('admin.supermercados.*')">
                        Supermercados
                    </x-nav-link>
                    <x-nav-link :href="route('despensas.index')" :active="request()->routeIs('despensas.*')">
                        Mi Despensa
                    </x-nav-link>
                    @auth
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*') || request()->routeIs('admin.productos.*')">
                            Productos
                        </x-nav-link>
                    @endif
                    @endauth
                </div>
            </div>

            @auth
            <div class="hidden items-center gap-4 sm:flex">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full border border-brand-200 bg-white px-3 py-2 text-sm font-medium text-ink-700 transition hover:border-brand-300 hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-300">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="text-left">
                                <span class="block">{{ Auth::user()->name }}</span>
                                <span class="block text-xs text-ink-500">{{ Auth::user()->email }}</span>
                            </span>

                            <div>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesion
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            @else
                <div class="hidden items-center gap-3 sm:flex">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-brand-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <x-ui.icon name="arrow-right-end-on-rectangle" class="h-4 w-4" />
                        <span>Login</span>
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-ink-900 transition hover:text-brand-600">
                            <x-ui.icon name="user-plus" class="h-4 w-4" />
                            <span>Registrarse</span>
                        </a>
                    @endif
                </div>
            @endauth

            <div class="flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full border border-brand-200 bg-white p-2 text-brand-700 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-300" aria-label="Abrir navegación">
                    <x-ui.icon name="bars-3" x-show="! open" class="h-6 w-6" />
                    <x-ui.icon name="x-mark" x-show="open" class="h-6 w-6" />
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-brand-100 lg:hidden">
        <div class="space-y-1 px-2 pb-3 pt-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->is('/')">
                Inicio
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('listas.index')" :active="request()->routeIs('listas.*')">
                Mi Lista
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('precios.index')" :active="request()->routeIs('precios.*') || request()->routeIs('admin.precios.*')">
                Comparador
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('supermercados.index')" :active="request()->routeIs('supermercados.*') || request()->routeIs('admin.supermercados.*')">
                Supermercados
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('despensas.index')" :active="request()->routeIs('despensas.*')">
                Mi Despensa
            </x-responsive-nav-link>
            @auth
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*') || request()->routeIs('admin.productos.*')">
                    Productos
                </x-responsive-nav-link>
            @endif
            @endauth
        </div>

        @auth
        <div class="border-t border-brand-100 px-4 pb-4 pt-4">
            <div class="px-4">
                <div class="font-medium text-base text-ink-900">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-ink-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Perfil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesion
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
            <div class="flex flex-wrap gap-3 border-t border-brand-100 px-4 pb-4 pt-4">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-900 shadow-soft transition hover:border-brand-300 hover:text-brand-800">
                    <x-ui.icon name="arrow-right-end-on-rectangle" class="h-4 w-4" />
                    <span>Iniciar sesión</span>
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-full bg-accent-400 px-4 py-2.5 text-sm font-semibold text-ink-900 transition hover:bg-accent-300">
                        <x-ui.icon name="user-plus" class="h-4 w-4" />
                        <span>Registrarse</span>
                    </a>
                @endif
            </div>
        @endauth
    </div>
</nav>
