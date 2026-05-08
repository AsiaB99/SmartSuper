@extends('layouts.app')

@section('title', __('despensas.index.meta_title'))

@section('content')
    @php($tieneAccionesEdicion = $despensas->contains(fn ($despensa) => auth()->user()?->can('update', $despensa)))
    @php($tieneAccionesEliminacion = $despensas->contains(fn ($despensa) => auth()->user()?->can('delete', $despensa)))

    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mb-12 rounded-[20px] bg-white p-10 text-center shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <h1 class="text-4xl font-semibold text-ink-900">{{ __('despensas.index.title') }}</h1>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-7 text-ink-600">
                    {{ __('despensas.index.subtitle') }}
                </p>
            </section>

            <div class="grid gap-10 lg:grid-cols-[1fr_330px]">
                <section class="grid gap-8 md:grid-cols-2">
                    @forelse ($despensas as $despensa)
                        <article class="flex flex-col rounded-[15px] bg-white p-6 shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-1">
                            <div class="mb-4 flex items-center gap-4">
                                <div class="flex h-[50px] w-[50px] items-center justify-center rounded-full border-2 border-[var(--color-borde-suave)] bg-brand-50 text-brand-500">
                                    <x-ui.icon name="archive-box" class="h-6 w-6" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-ink-900">{{ $despensa->nombre_despensa }}</h2>
                                    <p class="text-xs text-ink-400">{{ __('common.created_at') }} {{ optional($despensa->fecha_creacion)->format('d/m/Y H:i') ?? __('common.no_date') }}</p>
                                </div>
                            </div>

                            <div class="my-3 h-[10px] overflow-hidden rounded-full bg-[var(--color-barra-suave)]">
                                <div class="h-full w-2/3 rounded-full bg-brand-500"></div>
                            </div>
                            <p class="text-sm font-bold text-brand-500">{{ __('despensas.index.available_inventory') }}</p>

                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                <a class="ss-btn-outline flex-1" href="{{ route('despensas.stock', $despensa) }}">{{ __('despensas.index.stock') }}</a>
                                @can('update', $despensa)
                                    <button
                                        class="ss-btn-outline inline-flex items-center justify-center"
                                        type="button"
                                        data-edit-despensa
                                        data-edit-url="{{ route('despensas.update', $despensa) }}"
                                        data-edit-data-url="{{ route('despensas.edit', $despensa) }}"
                                        data-despensa-nombre="{{ $despensa->nombre_despensa }}"
                                        aria-label="{{ __('common.edit') }} {{ $despensa->nombre_despensa }}"
                                        title="{{ __('common.edit') }}"
                                    >
                                        <x-ui.icon name="pencil-square" class="h-5 w-5" />
                                        <span class="sr-only">{{ __('common.edit') }}</span>
                                    </button>
                                @endcan
                                @can('delete', $despensa)
                                    <form action="{{ route('despensas.destroy', $despensa) }}" method="POST" class="js-delete-despensa-form">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="inline-flex items-center justify-center rounded-[10px] bg-rose-600 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500"
                                            type="button"
                                            data-delete-despensa
                                            data-despensa-nombre="{{ $despensa->nombre_despensa }}"
                                            aria-label="{{ __('common.delete') }} {{ $despensa->nombre_despensa }}"
                                            title="{{ __('common.delete') }}"
                                        >
                                            <x-ui.icon name="trash" class="h-5 w-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6 md:col-span-2">
                            <h2 class="text-xl font-semibold text-ink-900">{{ __('despensas.index.empty.title') }}</h2>
                            <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('despensas.index.empty.text') }}</p>
                        </div>
                    @endforelse
                </section>

                <x-despensas.resumen-aside class="p-8">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ __('common.summary') }}</h2>
                    <p class="mt-3 text-sm text-ink-600">{{ __('despensas.index.summary.count') }}</p>
                    <p class="mt-1 text-center text-3xl font-bold text-ink-900">{{ $despensas->count() }}</p>
                    <div class="my-2 rounded-[10px] bg-[var(--color-info-suave)] p-3 text-center text-sm font-semibold text-brand-600">
                        <x-ui.icon name="archive-box" class="mr-1 inline h-4 w-4" />
                        {{ __('despensas.index.summary.tip') }}
                    </div>
                    <a class="ss-btn-green w-full" href="{{ route('despensas.create') }}">{{ __('despensas.index.create') }}</a>
                </x-despensas.resumen-aside>
            </div>
        </div>
    </section>

    @if ($tieneAccionesEliminacion)
        <dialog id="delete-despensa-dialog" class="w-full max-w-md rounded-[15px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_40px_rgba(0,0,0,0.15)] backdrop:bg-black/40">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-ink-900">{{ __('listas.delete.title') }}</h2>
                <p class="mt-3 text-sm leading-6 text-ink-600">
                    {{ __('despensas.index.delete_text', ['name' => '__PANTRY__']) }}
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button id="delete-despensa-cancel" type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</button>
                    <button id="delete-despensa-confirm" type="button" class="rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">{{ __('common.delete') }}</button>
                </div>
            </div>
        </dialog>
    @endif

    @if ($tieneAccionesEdicion)
        <dialog id="edit-despensa-dialog" class="w-full max-w-2xl rounded-[15px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_40px_rgba(0,0,0,0.15)] backdrop:bg-black/40">
            <div class="p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-ink-900">{{ __('despensas.edit.kicker') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('despensas.edit.title') }}</p>
                    </div>
                    <button id="edit-despensa-close" type="button" class="rounded-full border border-ink-200 p-2 text-ink-500 transition hover:border-brand-200 hover:text-brand-600" aria-label="{{ __('listas.edit.close_aria') }}">
                        <x-ui.icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <form id="edit-despensa-form" class="mt-6 grid gap-5" method="POST">
                    @csrf
                    @method('PUT')

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.form.name') }}</span>
                        <input id="edit-despensa-nombre" class="ss-input" type="text" name="nombre_despensa" maxlength="50" required>
                    </label>

                    <section id="edit-despensa-usuarios-section" class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-ink-700">{{ __('listas.edit.editors') }}</span>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ __('listas.edit.owner_or_admin') }}</span>
                        </div>

                        <div class="rounded-[14px] border border-ink-200 bg-white px-3 py-2 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                            <div id="edit-despensa-selected-users" class="flex flex-wrap gap-2"></div>
                            <label class="block">
                                <span class="sr-only">{{ __('listas.edit.search_users') }}</span>
                                <input
                                    id="edit-despensa-usuarios-search"
                                    class="h-10 w-full border-0 bg-transparent px-0 py-0 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="{{ __('listas.edit.search_placeholder') }}"
                                >
                            </label>
                        </div>

                        <p id="edit-despensa-empty-state" class="text-xs text-ink-500">
                            {{ __('listas.edit.empty') }}
                        </p>

                        <div id="edit-despensa-hidden-inputs"></div>

                        <p id="edit-despensa-usuarios-help" class="text-xs leading-5 text-ink-600">
                            {{ __('listas.edit.help') }}
                        </p>
                    </section>

                    <p id="edit-despensa-usuarios-error" class="hidden rounded-[12px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></p>

                    <div class="flex justify-end gap-3">
                        <button id="edit-despensa-cancel" type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</button>
                        <button id="edit-despensa-submit" type="submit" class="ss-btn-outline border-brand-300 text-brand-600 hover:bg-brand-500 hover:text-white">{{ __('common.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    <script id="despensas-translations" type="application/json">
        {!! json_encode([
            'deleteText' => __('despensas.index.delete_text', ['name' => '__PANTRY__']),
            'removeUserAria' => __('listas.edit.remove_user_aria', ['user' => '__USER__']),
            'invalidUsername' => __('listas.edit.errors.invalid_username'),
            'duplicateUsername' => __('listas.edit.errors.duplicate_username'),
            'loadData' => __('listas.edit.errors.load_data'),
            'loadCollaborators' => __('despensas.edit.load_collaborators'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    @vite('resources/js/despensas-index.js')
@endsection

