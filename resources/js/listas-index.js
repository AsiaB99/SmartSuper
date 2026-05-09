(() => {
    const createDialog = document.getElementById('create-lista-dialog');
    const openCreateButton = document.getElementById('open-create-lista-dialog');
    const closeCreateButton = document.getElementById('create-lista-close');
    const cancelCreateButton = document.getElementById('create-lista-cancel');
    const createNameInput = document.getElementById('create-lista-nombre');

    if (createDialog && openCreateButton) {
        const closeCreateDialog = () => createDialog.close();

        openCreateButton.addEventListener('click', () => {
            createDialog.showModal();
            createNameInput?.focus();
        });

        closeCreateButton?.addEventListener('click', closeCreateDialog);
        cancelCreateButton?.addEventListener('click', closeCreateDialog);

        createDialog.addEventListener('close', () => {
            const form = document.getElementById('create-lista-form');
            if (form instanceof HTMLFormElement) {
                form.reset();
            }
        });
    }

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
