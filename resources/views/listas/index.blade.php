@extends('layouts.app')

@section('title', __('listas.index.meta_title'))

@section('content')
    @php($tieneAccionesEdicion = $listas->contains(fn ($lista) => auth()->user()?->can('update', $lista)))
    @php($tieneAccionesEliminacion = $listas->contains(fn ($lista) => auth()->user()?->can('delete', $lista)))

    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mb-12 rounded-[20px] bg-white p-10 text-center shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <h1 class="text-4xl font-semibold text-ink-900">{{ __('listas.index.title') }}</h1>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-7 text-ink-600">
                    {{ __('listas.index.subtitle') }}
                </p>
            </section>

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
                                    <p class="mt-1 text-sm text-ink-500">{{ __('listas.index.created_at') }} {{ optional($lista->fecha_creacion)->format('d/m/Y H:i') ?? __('common.no_date') }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $estaComprada ? 'bg-[var(--color-exito-suave)] text-brand-600' : 'bg-accent-100 text-accent-800' }}">
                                        {{ __('common.states.' . $lista->estado) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <a class="ss-btn-outline inline-flex items-center justify-center" href="{{ route('listas.show', $lista) }}" aria-label="{{ __('listas.index.view_label', ['name' => $lista->nombre_lista]) }}" title="{{ __('listas.index.view_title') }}">
                                    <x-ui.icon name="eye" class="h-5 w-5" />
                                </a>
                                <a class="ss-btn-outline" href="{{ route('listas.productos', $lista) }}">{{ __('listas.index.add_products') }}</a>
                                <a class="ss-btn-outline" href="{{ route('listas.recomendacion', $lista) }}">{{ __('listas.index.recommend') }}</a>
                                @can('update', $lista)
                                    <a class="ss-btn-green" href="{{ route('listas.finalizar.confirmar', $lista) }}">{{ __('listas.index.finish') }}</a>
                                    <button
                                        class="ss-btn-outline inline-flex items-center justify-center"
                                        type="button"
                                        data-edit-lista
                                        data-edit-url="{{ route('listas.update', $lista) }}"
                                        data-edit-data-url="{{ route('listas.edit', $lista) }}"
                                        data-lista-nombre="{{ $lista->nombre_lista }}"
                                        data-lista-estado="{{ $lista->estado }}"
                                        aria-label="{{ __('listas.index.edit_title') }} {{ $lista->nombre_lista }}"
                                        title="{{ __('listas.index.edit_title') }}"
                                    >
                                        <x-ui.icon name="pencil-square" class="h-5 w-5" />
                                        <span class="sr-only">{{ __('listas.index.edit_sr') }}</span>
                                    </button>
                                @endcan
                                @can('delete', $lista)
                                    <form action="{{ route('listas.destroy', $lista) }}" method="POST" class="js-delete-lista-form">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="inline-flex items-center justify-center rounded-[10px] bg-rose-600 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500"
                                            type="button"
                                            data-delete-lista
                                            data-lista-nombre="{{ $lista->nombre_lista }}"
                                            aria-label="{{ __('listas.index.delete_label', ['name' => $lista->nombre_lista]) }}"
                                            title="{{ __('common.delete') }}"
                                        >
                                            <x-ui.icon name="trash" class="h-5 w-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                            <h2 class="text-xl font-semibold text-ink-900">{{ __('listas.index.empty.title') }}</h2>
                            <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('listas.index.empty.text') }}</p>
                        </div>
                    @endforelse
                </section>

                <x-listas.resumen-aside class="p-8">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ __('common.summary') }}</h2>
                    <p class="mt-3 text-sm text-ink-600">{{ __('listas.index.summary.count') }}</p>
                    <p class="mt-1 text-3xl font-bold text-ink-900 text-center">{{ $listas->count() }}</p>
                    <div class="my-2 rounded-[10px] bg-[var(--color-info-suave)] p-3 text-sm font-semibold text-brand-600 text-center">
                        <x-ui.icon name="shopping-cart" class="mr-1 inline h-4 w-4" />
                        {{ __('listas.index.summary.tip') }}
                    </div>
                    <a class="ss-btn-green w-full my-1" href="{{ route('listas.create') }}">{{ __('listas.index.create') }}</a>
                </x-listas.resumen-aside>
            </div>
        </div>
    </section>

    @if ($tieneAccionesEliminacion)
        <dialog id="delete-lista-dialog" class="w-full max-w-md rounded-[15px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_40px_rgba(0,0,0,0.15)] backdrop:bg-black/40">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-ink-900">{{ __('listas.delete.title') }}</h2>
                <p class="mt-3 text-sm leading-6 text-ink-600">
                    {{ __('listas.delete.text', ['name' => '__LIST__']) }}
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button id="delete-lista-cancel" type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</button>
                    <button id="delete-lista-confirm" type="button" class="rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">{{ __('common.delete') }}</button>
                </div>
            </div>
        </dialog>
    @endif

    @if ($tieneAccionesEdicion)
        <dialog id="edit-lista-dialog" class="w-full max-w-2xl rounded-[15px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_40px_rgba(0,0,0,0.15)] backdrop:bg-black/40">
            <div class="p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-ink-900">{{ __('listas.edit.modal_title') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('listas.edit.modal_text') }}</p>
                    </div>
                    <button id="edit-lista-close" type="button" class="rounded-full border border-ink-200 p-2 text-ink-500 transition hover:border-brand-200 hover:text-brand-600" aria-label="{{ __('listas.edit.close_aria') }}">
                        <x-ui.icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <form id="edit-lista-form" class="mt-6 grid gap-5" method="POST">
                    @csrf
                    @method('PUT')

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('listas.edit.name') }}</span>
                        <input id="edit-lista-nombre" class="ss-input" type="text" name="nombre_lista" maxlength="50" required>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('listas.edit.state') }}</span>
                        <select id="edit-lista-estado" class="ss-input" name="estado" required>
                            <option value="activa">{{ __('common.states.activa') }}</option>
                            <option value="comprada">{{ __('common.states.comprada') }}</option>
                        </select>
                    </label>

                    <section id="edit-lista-usuarios-section" class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-ink-700">{{ __('listas.edit.editors') }}</span>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ __('listas.edit.owner_or_admin') }}</span>
                        </div>

                        <div class="rounded-[14px] border border-ink-200 bg-white px-3 py-2 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                            <div id="edit-lista-selected-users" class="flex flex-wrap gap-2"></div>
                            <label class="block">
                                <span class="sr-only">{{ __('listas.edit.search_users') }}</span>
                                <input
                                    id="edit-lista-usuarios-search"
                                    class="h-10 w-full border-0 bg-transparent px-0 py-0 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="{{ __('listas.edit.search_placeholder') }}"
                                >
                            </label>
                        </div>

                        <p id="edit-lista-empty-state" class="text-xs text-ink-500">
                            {{ __('listas.edit.empty') }}
                        </p>

                        <div id="edit-lista-hidden-inputs"></div>

                        <p id="edit-lista-usuarios-help" class="text-xs leading-5 text-ink-600">
                            {{ __('listas.edit.help') }}
                        </p>
                    </section>

                    <p id="edit-lista-usuarios-error" class="hidden rounded-[12px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></p>

                    <div class="flex justify-end gap-3">
                        <button id="edit-lista-cancel" type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</button>
                        <button id="edit-lista-submit" type="submit" class="ss-btn-outline border-brand-300 text-brand-600 hover:bg-brand-500 hover:text-white">{{ __('common.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    <script id="listas-translations" type="application/json">
        {!! json_encode([
            'deleteText' => __('listas.delete.text', ['name' => '__LIST__']),
            'removeUserAria' => __('listas.edit.remove_user_aria', ['user' => '__USER__']),
            'invalidUsername' => __('listas.edit.errors.invalid_username'),
            'duplicateUsername' => __('listas.edit.errors.duplicate_username'),
            'loadData' => __('listas.edit.errors.load_data'),
            'loadCollaborators' => __('listas.edit.errors.load_collaborators'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script>
        (() => {
            const translations = JSON.parse(document.getElementById('listas-translations')?.textContent ?? '{}');
            const deleteDialog = document.getElementById('delete-lista-dialog');
            const deleteDialogText = deleteDialog?.querySelector('p');
            const deleteListaNombre = document.getElementById('delete-lista-nombre');
            const deleteConfirmButton = document.getElementById('delete-lista-confirm');
            const deleteCancelButton = document.getElementById('delete-lista-cancel');
            let currentDeleteForm = null;

            if (deleteDialog && deleteListaNombre && deleteConfirmButton && deleteCancelButton) {
                document.querySelectorAll('[data-delete-lista]').forEach((button) => {
                    button.addEventListener('click', () => {
                        currentDeleteForm = button.closest('form');
                        deleteListaNombre.textContent = button.dataset.listaNombre ?? '';
                        if (deleteDialogText) {
                            deleteDialogText.innerHTML = translations.deleteText
                                .replace('__LIST__', `<strong>${button.dataset.listaNombre ?? ''}</strong>`);
                        }
                        deleteDialog.showModal();
                    });
                });

                deleteConfirmButton.addEventListener('click', () => {
                    if (!currentDeleteForm) {
                        deleteDialog.close();
                        return;
                    }

                    currentDeleteForm.requestSubmit();
                });

                deleteCancelButton.addEventListener('click', () => {
                    deleteDialog.close();
                });

                deleteDialog.addEventListener('close', () => {
                    currentDeleteForm = null;
                });
            }

            const editDialog = document.getElementById('edit-lista-dialog');
            const editForm = document.getElementById('edit-lista-form');
            const editNombre = document.getElementById('edit-lista-nombre');
            const editEstado = document.getElementById('edit-lista-estado');
            const editCancelButton = document.getElementById('edit-lista-cancel');
            const editCloseButton = document.getElementById('edit-lista-close');
            const editSubmitButton = document.getElementById('edit-lista-submit');
            const usuariosSection = document.getElementById('edit-lista-usuarios-section');
            const usuariosError = document.getElementById('edit-lista-usuarios-error');
            const usuariosSelected = document.getElementById('edit-lista-selected-users');
            const usuariosSearch = document.getElementById('edit-lista-usuarios-search');
            const usuariosEmptyState = document.getElementById('edit-lista-empty-state');
            const hiddenInputs = document.getElementById('edit-lista-hidden-inputs');
            const editButtons = document.querySelectorAll('[data-edit-lista]');

            const userPickerState = {
                selectedUsers: [],
            };

            const escapeHtml = (value) => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
            const updateHiddenInputs = () => {
                if (!hiddenInputs) {
                    return;
                }

                hiddenInputs.innerHTML = userPickerState.selectedUsers
                    .map((nombreUsuario) => `<input type="hidden" name="usuarios_editores[]" value="${escapeHtml(nombreUsuario)}">`)
                    .join('');
            };

            const renderSelectedUsers = () => {
                if (!usuariosSelected) {
                    return;
                }

                if (userPickerState.selectedUsers.length === 0) {
                    usuariosSelected.innerHTML = '';
                    usuariosEmptyState?.classList.remove('hidden');
                    updateHiddenInputs();
                    return;
                }

                usuariosEmptyState?.classList.add('hidden');

                usuariosSelected.innerHTML = userPickerState.selectedUsers
                    .map((nombreUsuario) => `
                        <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-2 text-sm font-medium text-brand-700">
                            <span>@${escapeHtml(nombreUsuario)}</span>
                            <button
                                type="button"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-brand-700 transition hover:bg-brand-100"
                                data-remove-username="${escapeHtml(nombreUsuario)}"
                                aria-label="${translations.removeUserAria.replace('__USER__', escapeHtml(nombreUsuario))}"
                            >
                                &times;
                            </button>
                        </span>
                    `)
                    .join('');

                usuariosSelected.querySelectorAll('[data-remove-username]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const nombreUsuario = button.getAttribute('data-remove-username') ?? '';
                        userPickerState.selectedUsers = userPickerState.selectedUsers.filter((user) => user !== nombreUsuario);
                        renderSelectedUsers();
                        usuariosSearch?.focus();
                    });
                });

                updateHiddenInputs();
            };

            const mostrarErrorUsuarios = (message = '') => {
                if (!usuariosError) {
                    return;
                }

                if (message === '') {
                    usuariosError.textContent = '';
                    usuariosError.classList.add('hidden');
                    return;
                }

                usuariosError.textContent = message;
                usuariosError.classList.remove('hidden');
            };

            const resetUserPicker = () => {
                userPickerState.selectedUsers = [];

                if (usuariosSearch) {
                    usuariosSearch.value = '';
                }

                mostrarErrorUsuarios();

                if (usuariosSection) {
                    usuariosSection.classList.add('hidden');
                }

                renderSelectedUsers();
            };

            const addUsernameChip = () => {
                if (!usuariosSearch) {
                    return;
                }

                const nombreUsuario = usuariosSearch.value.trim();

                if (nombreUsuario === '') {
                    return;
                }

                if (!/^[A-Za-z0-9_-]+$/.test(nombreUsuario)) {
                    mostrarErrorUsuarios(translations.invalidUsername);
                    return;
                }

                if (userPickerState.selectedUsers.includes(nombreUsuario)) {
                    mostrarErrorUsuarios(translations.duplicateUsername);
                    usuariosSearch.value = '';
                    return;
                }

                userPickerState.selectedUsers = [...userPickerState.selectedUsers, nombreUsuario];
                usuariosSearch.value = '';
                mostrarErrorUsuarios();
                renderSelectedUsers();
            };

            const fillEditModal = (button, payload) => {
                editForm?.setAttribute('action', button.dataset.editUrl ?? '');

                if (editNombre) {
                    editNombre.value = payload?.lista?.nombre_lista ?? button.dataset.listaNombre ?? '';
                }

                if (editEstado) {
                    editEstado.value = payload?.lista?.estado ?? button.dataset.listaEstado ?? 'activa';
                }

                if (!usuariosSection || !usuariosSearch) {
                    return;
                }

                if (!payload?.puedeAsignarEditores) {
                    usuariosSection.classList.add('hidden');
                    return;
                }

                usuariosSection.classList.remove('hidden');
                userPickerState.selectedUsers = payload.usuariosEditoresActuales ?? [];
                renderSelectedUsers();
            };

            if (editDialog && editForm && editNombre && editEstado && editCancelButton && editButtons.length > 0) {
                const closeEditDialog = () => {
                    editDialog.close();
                    resetUserPicker();
                };

                editButtons.forEach((button) => {
                    button.addEventListener('click', async () => {
                        resetUserPicker();
                        editForm.setAttribute('action', button.dataset.editUrl ?? '');
                        editNombre.value = button.dataset.listaNombre ?? '';
                        editEstado.value = button.dataset.listaEstado ?? 'activa';
                        editDialog.showModal();
                        editSubmitButton?.setAttribute('disabled', 'disabled');

                        try {
                            const response = await fetch(button.dataset.editDataUrl ?? '', {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) {
                                throw new Error(translations.loadData);
                            }

                            const payload = await response.json();
                            fillEditModal(button, payload);
                        } catch (error) {
                            mostrarErrorUsuarios(translations.loadCollaborators);
                        } finally {
                            editSubmitButton?.removeAttribute('disabled');
                        }
                    });
                });

                editCancelButton.addEventListener('click', closeEditDialog);
                editCloseButton?.addEventListener('click', closeEditDialog);

                editDialog.addEventListener('close', () => {
                    resetUserPicker();
                });

                usuariosSearch?.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && usuariosSearch.value === '' && userPickerState.selectedUsers.length > 0) {
                        userPickerState.selectedUsers = userPickerState.selectedUsers.slice(0, -1);
                        renderSelectedUsers();
                        return;
                    }

                    if (event.key === 'Enter' || event.key === ',') {
                        event.preventDefault();
                        addUsernameChip();
                    }
                });
            }

        })();
    </script>
@endsection
