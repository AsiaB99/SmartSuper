@extends('layouts.app')

@section('title', 'Mi Lista | SmartSuper')

@section('content')
    @php($tieneAccionesEdicion = $listas->contains(fn ($lista) => auth()->user()?->can('update', $lista)))
    @php($tieneAccionesEliminacion = $listas->contains(fn ($lista) => auth()->user()?->can('delete', $lista)))

    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mb-12 rounded-[20px] bg-white p-10 text-center shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <h1 class="text-4xl font-semibold text-ink-900">Mis Listas</h1>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-7 text-ink-600">
                    Organiza tus compras, revisa productos y calcula la mejor recomendación de supermercado.
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
                                    <p class="mt-1 text-sm text-ink-500">Creada: {{ optional($lista->fecha_creacion)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $estaComprada ? 'bg-[var(--color-exito-suave)] text-brand-600' : 'bg-accent-100 text-accent-800' }}">
                                        {{ ucfirst($lista->estado) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <a class="ss-btn-outline inline-flex items-center justify-center" href="{{ route('listas.show', $lista) }}" aria-label="Ver {{ $lista->nombre_lista }}" title="Ver lista">
                                    <x-ui.icon name="eye" class="h-5 w-5" />
                                </a>
                                <a class="ss-btn-outline" href="{{ route('listas.productos', $lista) }}">Añadir productos</a>
                                <a class="ss-btn-outline" href="{{ route('listas.recomendacion', $lista) }}">Recomendar super</a>
                                @can('update', $lista)
                                    <a class="ss-btn-green" href="{{ route('listas.finalizar.confirmar', $lista) }}">Finalizar</a>
                                    <button
                                        class="ss-btn-outline inline-flex items-center justify-center"
                                        type="button"
                                        data-edit-lista
                                        data-edit-url="{{ route('listas.update', $lista) }}"
                                        data-lista-nombre="{{ $lista->nombre_lista }}"
                                        data-lista-estado="{{ $lista->estado }}"
                                        aria-label="Editar {{ $lista->nombre_lista }}"
                                        title="Editar"
                                    >
                                        <x-ui.icon name="pencil-square" class="h-5 w-5" />
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
                                            aria-label="Eliminar {{ $lista->nombre_lista }}"
                                            title="Eliminar"
                                        >
                                            <x-ui.icon name="trash" class="h-5 w-5" />
                                        </button>
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
                    <p class="mt-3 text-sm text-ink-600">Listas guardadas:</p>
                    <p class="mt-1 text-3xl font-bold text-ink-900 text-center">{{ $listas->count() }}</p>
                    <div class="my-2 rounded-[10px] bg-[var(--color-info-suave)] p-3 text-sm font-semibold text-brand-600 text-center">
                        <x-ui.icon name="shopping-cart" class="mr-1 inline h-4 w-4" />
                        Entra en una lista para ajustar cantidades.
                    </div>
                    <a class="ss-btn-green w-full" href="{{ route('listas.create') }}">Crear lista</a>
                </aside>
            </div>
        </div>
    </section>

    @if ($tieneAccionesEliminacion)
        <dialog id="delete-lista-dialog" class="w-full max-w-md rounded-[15px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_40px_rgba(0,0,0,0.15)] backdrop:bg-black/40">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-ink-900">Confirmar eliminación</h2>
                <p class="mt-3 text-sm leading-6 text-ink-600">
                    Vas a eliminar la lista <strong id="delete-lista-nombre"></strong>. Esta acción no se puede deshacer.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button id="delete-lista-cancel" type="button" class="rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">Cancelar</button>
                    <button id="delete-lista-confirm" type="button" class="rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">Eliminar</button>
                </div>
            </div>
        </dialog>
    @endif

    @if ($tieneAccionesEdicion)
        <dialog id="edit-lista-dialog" class="w-full max-w-xl rounded-[15px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_40px_rgba(0,0,0,0.15)] backdrop:bg-black/40">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-ink-900">Editar lista</h2>
                <form id="edit-lista-form" class="mt-5 grid gap-5" method="POST">
                    @csrf
                    @method('PUT')
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">Nombre de la lista</span>
                        <input id="edit-lista-nombre" class="ss-input" type="text" name="nombre_lista" maxlength="50" required>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">Estado</span>
                        <select id="edit-lista-estado" class="ss-input" name="estado" required>
                            <option value="activa">Activa</option>
                            <option value="comprada">Comprada</option>
                        </select>
                    </label>
                    <div class="flex justify-end gap-3">
                        <button id="edit-lista-cancel" type="button" class="rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">Cancelar</button>
                        <button type="submit" class="ss-btn-outline border-brand-300 text-brand-600 hover:bg-brand-500 hover:text-white">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    <script>
        (() => {
            const deleteDialog = document.getElementById('delete-lista-dialog');
            const deleteListaNombre = document.getElementById('delete-lista-nombre');
            const deleteConfirmButton = document.getElementById('delete-lista-confirm');
            const deleteCancelButton = document.getElementById('delete-lista-cancel');
            let currentDeleteForm = null;

            if (deleteDialog && deleteListaNombre && deleteConfirmButton && deleteCancelButton) {
                document.querySelectorAll('[data-delete-lista]').forEach((button) => {
                    button.addEventListener('click', () => {
                        currentDeleteForm = button.closest('form');
                        deleteListaNombre.textContent = button.dataset.listaNombre ?? '';
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

            if (editDialog && editForm && editNombre && editEstado && editCancelButton) {
                document.querySelectorAll('[data-edit-lista]').forEach((button) => {
                    button.addEventListener('click', () => {
                        editForm.setAttribute('action', button.dataset.editUrl ?? '');
                        editNombre.value = button.dataset.listaNombre ?? '';
                        editEstado.value = button.dataset.listaEstado ?? 'activa';
                        editDialog.showModal();
                    });
                });

                editCancelButton.addEventListener('click', () => {
                    editDialog.close();
                });
            }
        })();
    </script>
@endsection
