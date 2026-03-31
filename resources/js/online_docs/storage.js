const getRoot = () => document.getElementById('storage-root');

const initMenus = () => {
    const root = getRoot();
    if (!root) {
        return;
    }

    const closeAllMenus = () => {
        root.querySelectorAll('[data-menu]').forEach((menu) => {
            const panel = menu.querySelector('[data-menu-panel]');
            panel?.classList.add('hidden');
        });
    };

    root.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-menu-trigger]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const menu = trigger.closest('[data-menu]');
        const panel = menu?.querySelector('[data-menu-panel]');
        if (!panel) {
            return;
        }

        const isOpen = !panel.classList.contains('hidden');
        closeAllMenus();
        panel.classList.toggle('hidden', isOpen);
    });

    root.querySelectorAll('[data-menu-trigger], [data-menu-panel]').forEach((el) => {
        el.setAttribute('draggable', 'false');
        el.addEventListener('dragstart', (event) => {
            event.preventDefault();
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#storage-root [data-menu]')) {
            closeAllMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllMenus();
        }
    });
};

const initQuickActions = () => {
    const root = getRoot();
    if (!root) {
        return;
    }

    const menuWrap = root.querySelector('[data-storage-new-menu]');
    const menuToggle = root.querySelector('[data-storage-new-toggle]');
    const menuPanel = root.querySelector('[data-storage-new-panel]');
    const folderForm = root.querySelector('#storage-folder-form');
    const folderInput = folderForm?.querySelector('input[name="name"]');
    const folderCancel = root.querySelector('[data-storage-folder-cancel]');
    const uploadInput = root.querySelector('[data-storage-upload-input]');
    const uploadForm = root.querySelector('#storage-upload-form');

    const closeNewMenu = () => {
        menuPanel?.classList.add('hidden');
    };

    if (menuToggle && menuPanel) {
        menuToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            menuPanel.classList.toggle('hidden');
        });

        document.addEventListener('click', (event) => {
            if (!menuWrap?.contains(event.target)) {
                closeNewMenu();
            }
        });
    }

    root.querySelector('[data-storage-action="new-folder"]')?.addEventListener('click', () => {
        closeNewMenu();
        if (!folderForm) {
            return;
        }

        folderForm.classList.remove('hidden');
        folderForm.classList.add('flex');
        folderInput?.focus();
    });

    root.querySelector('[data-storage-action="upload-file"]')?.addEventListener('click', () => {
        closeNewMenu();
        uploadInput?.click();
    });

    folderCancel?.addEventListener('click', () => {
        if (!folderForm) {
            return;
        }

        folderForm.classList.remove('flex');
        folderForm.classList.add('hidden');
        if (folderInput) {
            folderInput.value = '';
        }
    });

    uploadInput?.addEventListener('change', () => {
        if (!uploadInput.files?.length) {
            return;
        }

        uploadForm?.submit();
    });
};

const initViewToggle = () => {
    const root = getRoot();
    if (!root) {
        return;
    }

    const viewButtons = Array.from(document.querySelectorAll('[data-view-toggle]'));
    const viewContainers = Array.from(root.querySelectorAll('[data-view]'));
    if (!viewButtons.length || !viewContainers.length) {
        return;
    }

    const setView = (view) => {
        viewContainers.forEach((container) => {
            container.classList.toggle('hidden', container.dataset.view !== view);
        });

        viewButtons.forEach((button) => {
            button.classList.toggle('bg-muted-100', button.dataset.viewToggle === view);
        });

        window.localStorage.setItem('storageView', view);
    };

    const initialView = window.localStorage.getItem('storageView') || 'grid';
    setView(initialView);

    viewButtons.forEach((button) => {
        button.addEventListener('click', () => setView(button.dataset.viewToggle));
    });
};

const initSearch = () => {
    const searchInput = document.getElementById('storage-search');
    if (!searchInput) {
        return;
    }

    const items = Array.from(document.querySelectorAll('.storage-item'));
    const filterItems = () => {
        const keyword = searchInput.value.trim().toLowerCase();
        items.forEach((item) => {
            const name = (item.dataset.itemName || '').toLowerCase();
            item.classList.toggle('hidden', keyword !== '' && !name.includes(keyword));
        });
    };

    searchInput.addEventListener('input', filterItems);
};

const collectSelectedItems = () => {
    return Array.from(document.querySelectorAll('.storage-select:checked')).map((checkbox) => ({
        type: checkbox.dataset.itemType,
        id: Number(checkbox.dataset.itemId),
    }));
};

const updateToolbar = () => {
    const toolbar = document.getElementById('storage-toolbar');
    if (!toolbar) {
        return;
    }

    const selectedItems = collectSelectedItems();
    const countLabel = toolbar.querySelector('[data-selected-count]');
    if (countLabel) {
        countLabel.textContent = `${selectedItems.length} ${toolbar.dataset.selectedLabel || 'selected'}`;
    }

    const hasSelection = selectedItems.length > 0;
    toolbar.classList.toggle('hidden', !hasSelection);
    toolbar.classList.toggle('flex', hasSelection);
};

const initSelection = () => {
    const checkboxes = Array.from(document.querySelectorAll('.storage-select'));
    if (!checkboxes.length) {
        return;
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateToolbar);
    });

    updateToolbar();
};

const requestJson = async (url, payload, token) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error('Request failed');
    }

    return response.json();
};

const initBulkActions = () => {
    const root = getRoot();
    if (!root) {
        return;
    }

    const moveButton = root.querySelector('[data-bulk-move]');
    const deleteButton = root.querySelector('[data-bulk-delete]');
    const targetSelect = root.querySelector('[data-bulk-move-target]');
    const token = root.dataset.csrf;

    if (moveButton) {
        moveButton.addEventListener('click', async () => {
            const items = collectSelectedItems();
            if (!items.length) {
                return;
            }

            await requestJson(root.dataset.bulkMoveUrl, {
                items,
                target_folder_id: targetSelect?.value || null,
            }, token);

            window.location.reload();
        });
    }

    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            const items = collectSelectedItems();
            if (!items.length) {
                return;
            }

            await requestJson(root.dataset.bulkDeleteUrl, { items }, token);
            window.location.reload();
        });
    }
};

const initDropzoneUpload = () => {
    const root = getRoot();
    if (!root) {
        return;
    }

    const dropzone = root.querySelector('[data-dropzone]');
    const overlay = root.querySelector('[data-drop-overlay]');
    if (!dropzone || !overlay) {
        return;
    }

    const showOverlay = () => overlay.classList.remove('hidden');
    const hideOverlay = () => overlay.classList.add('hidden');

    const createLink = async (docId, folderId) => {
        const token = root.dataset.csrf;
        const linkUrl = `${root.dataset.linkBaseUrl}/${docId}`;
        const payload = new FormData();
        if (folderId) {
            payload.append('folder_id', folderId);
        }

        await fetch(linkUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
            },
            body: payload,
        });
    };

    const uploadFiles = async (files, folderId) => {
        const token = root.dataset.csrf;
        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            if (folderId) {
                formData.append('folder_id', folderId);
            }

            await fetch(root.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                },
                body: formData,
            });
        }

        window.location.reload();
    };

    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        showOverlay();
    });

    dropzone.addEventListener('dragleave', (event) => {
        if (!dropzone.contains(event.relatedTarget)) {
            hideOverlay();
        }
    });

    dropzone.addEventListener('drop', async (event) => {
        event.preventDefault();
        hideOverlay();
        const payload = event.dataTransfer?.getData('text/plain');
        if (payload) {
            try {
                const data = JSON.parse(payload);
                if (data.type === 'doc') {
                    await createLink(data.id, root.dataset.currentFolder || null);
                    window.location.reload();
                    return;
                }
            } catch (error) {
                // no-op
            }
        }

        if (!event.dataTransfer?.files?.length) {
            return;
        }

        uploadFiles(Array.from(event.dataTransfer.files), root.dataset.currentFolder || null);
    });

    const folderTargets = Array.from(root.querySelectorAll('[data-folder-drop]'));
    folderTargets.forEach((target) => {
        target.addEventListener('dragover', (event) => {
            event.preventDefault();
            target.classList.add('ring-2', 'ring-primary/40');
        });

        target.addEventListener('dragleave', () => {
            target.classList.remove('ring-2', 'ring-primary/40');
        });

        target.addEventListener('drop', async (event) => {
            event.preventDefault();
            target.classList.remove('ring-2', 'ring-primary/40');
            const payload = event.dataTransfer?.getData('text/plain');
            if (payload) {
                try {
                    const data = JSON.parse(payload);
                    if (data.type === 'doc') {
                        await createLink(data.id, target.dataset.itemId);
                        window.location.reload();
                        return;
                    }
                } catch (error) {
                    // no-op
                }
            }

            const files = Array.from(event.dataTransfer?.files || []);
            if (files.length) {
                uploadFiles(files, target.dataset.itemId);
            }
        });
    });
};

const initDragMove = () => {
    const root = getRoot();
    if (!root) {
        return;
    }

    const draggableItems = Array.from(root.querySelectorAll('.storage-item[draggable="true"]'));
    draggableItems.forEach((item) => {
        item.addEventListener('dragstart', (event) => {
            event.dataTransfer?.setData('text/plain', JSON.stringify({
                type: item.dataset.itemType,
                id: item.dataset.itemId,
            }));
        });
    });

    const folderTargets = Array.from(root.querySelectorAll('[data-folder-drop]'));
    folderTargets.forEach((target) => {
        target.addEventListener('drop', async (event) => {
            event.preventDefault();
            const payload = event.dataTransfer?.getData('text/plain');
            if (!payload) {
                return;
            }

            try {
                const data = JSON.parse(payload);
                await requestJson(root.dataset.moveUrl, {
                    item_type: data.type,
                    item_id: Number(data.id),
                    target_folder_id: Number(target.dataset.itemId),
                }, root.dataset.csrf);

                window.location.reload();
            } catch (error) {
                // no-op
            }
        });
    });
};

const initDocumentDrag = () => {
    const docItems = Array.from(document.querySelectorAll('[data-doc-drag]'));
    docItems.forEach((item) => {
        item.addEventListener('dragstart', (event) => {
            event.dataTransfer?.setData('text/plain', JSON.stringify({
                type: 'doc',
                id: Number(item.dataset.docId),
            }));
        });
    });
};

const initStorage = () => {
    initMenus();
    initQuickActions();
    initViewToggle();
    initSearch();
    initSelection();
    initBulkActions();
    initDropzoneUpload();
    initDragMove();
    initDocumentDrag();
};

document.addEventListener('DOMContentLoaded', initStorage);
