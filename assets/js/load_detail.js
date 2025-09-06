document.addEventListener('DOMContentLoaded', function() {
    // Definisi variabel dan konstanta dari PHP
    const loadId = pageData.loadId;
    const csrfToken = pageData.csrfToken;
    const allInstrumentsData = pageData.allInstrumentsData;
    const allMasterSetsData = pageData.allMasterSetsData;
    const allMasterSetsInfo = pageData.allMasterSetsInfo;
    const allInstrumentsGrouped = pageData.allInstrumentsGrouped;
    let currentLoadData = null;

    // Referensi elemen DOM
    const mainContainer = document.getElementById('main-content-container');
    const loadingOverlay = document.getElementById('loading-overlay');
    const itemSearchInput = document.getElementById('itemSearchInput');
    const itemSearchResults = document.getElementById('itemSearchResults');
    const itemCountSpan = document.getElementById('itemCount');
    const loadItemsTableBody = document.getElementById('loadItemsTableBody');
    
    // Referensi elemen untuk Item Picker
    const itemPickerModal = document.getElementById('itemPickerModal');
    const openItemPickerBtn = document.getElementById('openItemPickerBtn');
    const closeItemPickerBtn = document.getElementById('closeItemPickerBtn');
    const pickerCategoriesContainer = document.getElementById('picker-categories');
    const pickerItemsContainer = document.getElementById('picker-items-container');
    const pickerSearchInput = document.getElementById('pickerSearchInput');
    const pickerSelectAllCheckbox = document.getElementById('pickerSelectAll');
    const pickerSelectedItemCount = document.getElementById('pickerSelectedItemCount');
    const addSelectedItemsBtn = document.getElementById('addSelectedItemsBtn');

    // ... (Referensi elemen lain tetap sama)
    const setEditorPanel = document.getElementById('setEditorPanel');
    const editingSetName = document.getElementById('editingSetName');
    const editingLoadItemIdInput = document.getElementById('editingLoadItemId');
    const editorInstrumentSearch = document.getElementById('editorInstrumentSearch');
    const instrumentPickerTbody = document.getElementById('instrumentPickerTbody');
    const cancelSetEditBtn = document.getElementById('cancelSetEditBtn');
    const saveSetChangesBtn = document.getElementById('saveSetChangesBtn');
    const processLoadModal = document.getElementById('processLoadModal');
    const cancelProcessBtn = document.getElementById('cancelProcessBtn');
    const processLoadForm = document.getElementById('processLoadForm');
    const mergeCycleContainer = document.getElementById('mergeCycleDropdownContainer');
    const targetCycleSelect = document.getElementById('target_cycle_id');
    const editLoadModal = document.getElementById('editLoadModal');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const editLoadForm = document.getElementById('editLoadForm');
    const removeItemModal = document.getElementById('removeItemModal');
    const cancelRemoveBtn = document.getElementById('cancelRemoveBtn');
    const confirmRemoveBtn = document.getElementById('confirmRemoveBtn');
    let itemToRemoveId = null;
    let debounceTimer;

    // ... (Fungsi isSetCustomized, fetchLoadDetails, renderHeader, renderInfoPanel, dll tetap sama)
     function isSetCustomized(item) {
        if (item.item_type !== 'set' || !item.item_snapshot) { return false; }
        const snapshot = JSON.parse(item.item_snapshot);
        const masterSet = allMasterSetsData[item.item_id] || [];
        const normalize = (arr) => arr.map(i => ({ id: i.instrument_id, q: i.quantity })).sort((a, b) => a.id - b.id);
        const snapshotNormalized = normalize(snapshot);
        const masterNormalized = normalize(masterSet);
        return JSON.stringify(snapshotNormalized) !== JSON.stringify(masterNormalized);
    }

    function fetchLoadDetails() {
        loadingOverlay.style.display = 'flex';
        fetch(`php_scripts/get_load_details.php?load_id=${loadId}`)
            .then(response => { if (!response.ok) throw new Error('Network response was not ok'); return response.json(); })
            .then(data => {
                if (data.success && data.load) { currentLoadData = data.load; renderAllComponents(data.load); } 
                else { showToast(data.error || 'Gagal memuat detail muatan.', 'error'); }
            })
            .catch(error => { showToast('Terjadi kesalahan jaringan saat memuat data.', 'error'); })
            .finally(() => { loadingOverlay.style.display = 'none'; });
    }
    
    // --- Bagian Rendering ---
    
    function renderAllComponents(load) {
        renderHeader(load);
        renderInfoPanel(load);
        renderActionContainer(load);
        renderItemsTable(load.items);
        document.getElementById('addItemContainer').style.display = (load.status === 'persiapan') ? 'block' : 'none';
        document.getElementById('openEditModalBtn').style.display = (load.status === 'persiapan') ? 'inline-flex' : 'none';
    }

    function renderHeader(load) {
        document.getElementById('loadNameDisplay').textContent = escapeHtml(load.load_name);
        const statusInfo = getUniversalStatusBadge(load.status);
        document.getElementById('statusBadgeContainer').innerHTML = `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
    }

    function renderInfoPanel(load) {
        const cycleLink = load.cycle_id && load.cycle_number ? `<a href="cycle_detail.php?cycle_id=${load.cycle_id}" class="text-blue-600 hover:underline">${escapeHtml(load.cycle_number)}</a>` : 'N/A';
        document.getElementById('loadInfoPanel').innerHTML = `
            <dt><span class="material-icons">badge</span>Dibuat oleh:</dt><dd>${escapeHtml(load.creator_name || 'N/A')}</dd>
            <dt><span class="material-icons">schedule</span>Waktu Dibuat:</dt><dd>${new Date(load.created_at).toLocaleString('id-ID')}</dd>
            <dt><span class="material-icons">local_convenience_store</span>Mesin:</dt><dd>${escapeHtml(load.machine_name || 'N/A')}</dd>
            <dt><span class="material-icons">maps_home_work</span>Tujuan:</dt><dd>${escapeHtml(load.destination_department_name || 'Stok Umum')}</dd>
            <dt><span class="material-icons">cyclone</span>Siklus:</dt><dd>${cycleLink}</dd>
            ${load.notes ? `<dt><span class="material-icons">notes</span>Catatan:</dt><dd class="whitespace-pre-wrap">${escapeHtml(load.notes)}</dd>` : ''}
        `;
    }
    
    function renderActionContainer(load) {
        const actionContainer = document.getElementById('actionContainer');
        actionContainer.innerHTML = '';
        let content = '';
        switch(load.status) {
            case 'persiapan':
                content = `<h3 class="text-xl font-semibold text-gray-800 mb-4">Aksi</h3>`;
                if (load.items && load.items.length > 0) { content += `<button id="processLoadBtn" class="btn btn-primary w-full"><span class="material-icons mr-2">play_circle_filled</span>Jalankan Siklus & Proses Muatan</button>`; } 
                else { content += `<div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-md p-3 text-center">Silakan tambahkan minimal satu item ke dalam muatan untuk dapat memprosesnya.</div>`; }
                break;
            case 'menunggu_validasi':
                content = `<h3 class="text-xl font-semibold text-gray-800 mb-4">Aksi</h3><p class="text-sm text-gray-600">Muatan ini sedang menunggu validasi dari siklus ${escapeHtml(load.cycle_number)}.</p><a href="cycle_detail.php?cycle_id=${load.cycle_id}" class="btn bg-yellow-500 text-white hover:bg-yellow-600 w-full mt-4"><span class="material-icons mr-2">rule</span>Lihat & Validasi Siklus</a>`;
                break;
            case 'selesai':
                // PERUBAHAN: Tombol disederhanakan menjadi satu.
                content = `<h3 class="text-xl font-semibold text-gray-800 mb-4">Aksi Lanjutan</h3><p class="text-sm text-gray-500 mb-4">Siklus telah berhasil. Gunakan tombol di bawah untuk mencetak atau mencetak ulang label.</p><div class="space-y-3">`;
                content += `<form action="php_scripts/prepare_print_queue.php" method="POST"><input type="hidden" name="csrf_token" value="${csrfToken}"><input type="hidden" name="load_id" value="${loadId}"><button type="submit" class="btn bg-green-500 text-white hover:bg-green-600 w-full"><span class="material-icons mr-2">print</span>Manajemen Cetak</button></form>`;
                content += `<a href="label_history.php?search_query=${encodeURIComponent(load.load_name)}" class="btn btn-secondary w-full"><span class="material-icons mr-2">history</span>Lihat Riwayat Label</a></div>`;
                break;
            case 'gagal':
                content = `<h3 class="text-xl font-semibold text-gray-800 mb-4">Status Muatan</h3><div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-3 text-center"><span class="material-icons text-3xl">error</span><p class="font-bold mt-2">Muatan ini Gagal!</p><p>Siklus sterilisasi untuk muatan ini gagal. Semua item harus diproses ulang. Silakan buat muatan baru.</p></div>`;
                break;
        }
        actionContainer.innerHTML = content;
    }

    function renderItemsTable(items) {
        loadItemsTableBody.innerHTML = '';
        itemCountSpan.textContent = items.length;
        if (items.length === 0) {
            loadItemsTableBody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">Belum ada item ditambahkan.</td></tr>';
            return;
        }
        items.forEach(item => {
            let actionButtons = '', expander = '', customIcon = '';

            const statusMap = {
                'tersedia': 'tr-status-tersedia', 'perbaikan': 'tr-status-perbaikan',
                'rusak': 'tr-status-rusak', 'sterilisasi': 'tr-status-sterilisasi', 'default': 'tr-status-default'
            };
            const rowStatusClass = statusMap[item.status] || statusMap['default'];
            
            let dataHref = '#_';
            if (item.item_type === 'set') {
                expander = `<button class="btn-icon btn-icon-action expand-set-btn" data-load-item-id="${item.load_item_id}" title="Lihat/Sembunyikan Isi Set"><span class="material-icons">expand_more</span></button>`;
                if(isSetCustomized(item)) { customIcon = `<span class="material-icons customized-set-icon" title="Isi set ini telah dikustomisasi untuk muatan ini">drive_file_rename_outline</span>`; }
                dataHref = `set_detail.php?set_id=${item.item_id}`;
            } else if (item.item_type === 'instrument') {
                dataHref = `instrument_detail.php?instrument_id=${item.item_id}`;
            }
            
            if (currentLoadData.status === 'persiapan') {
                if(item.item_type === 'set'){ actionButtons += `<button class="btn-icon btn-icon-edit edit-set-contents-btn" data-item-id="${item.item_id}" data-load-item-id="${item.load_item_id}" data-set-name="${escapeHtml(item.item_name)}" title="Edit Isi Set"><span class="material-icons">edit_note</span></button>`; }
                actionButtons += `<button class="btn-icon btn-icon-delete remove-item-btn" data-load-item-id="${item.load_item_id}" title="Hapus Item"><span class="material-icons">delete</span></button>`;
            } else { actionButtons = '-'; }

            const row = `
                <tr class="border-b hover:bg-gray-100 table-status-indicator clickable-row ${rowStatusClass}" data-href="${dataHref}">
                    <td class="py-2 px-4">${expander} ${escapeHtml(item.item_name)} ${customIcon}</td>
                    <td class="py-2 px-4 capitalize">${escapeHtml(item.item_type)}</td>
                    <td class="py-2 px-4 text-center space-x-1">${actionButtons}</td>
                </tr>
                <tr class="set-contents-details" data-details-for="${item.load_item_id}">
                    <td colspan="3" class="p-4"><div class="instrument-list-in-set">Memuat...</div></td>
                </tr>`;
            loadItemsTableBody.innerHTML += row;
        });
    }

    function renderPicker() {
        renderPickerCategories();
        const firstCategory = pickerCategoriesContainer.querySelector('.picker-category-link');
        if (firstCategory) {
            firstCategory.click();
        }
    }

    function renderPickerCategories() {
        pickerCategoriesContainer.innerHTML = '';
        let categoriesHtml = `
            <a href="#" class="picker-category-link active" data-category-key="all_sets">Semua Set</a>
            <a href="#" class="picker-category-link" data-category-key="all_instruments">Semua Instrumen</a>
            <div class="picker-category-divider">Tipe Instrumen</div>
        `;
        Object.keys(allInstrumentsGrouped).forEach(typeName => {
            categoriesHtml += `<a href="#" class="picker-category-link" data-category-key="${escapeHtml(typeName)}">${escapeHtml(typeName)}</a>`;
        });
        pickerCategoriesContainer.innerHTML = categoriesHtml;
    }

    function renderPickerItems(categoryKey) {
        pickerItemsContainer.innerHTML = '';
        let itemsHtml = '';
        
        if (categoryKey === 'all_sets') {
            allMasterSetsInfo.forEach(set => {
                itemsHtml += createPickerItemHtml('set', set.set_id, set.set_name, set.set_code);
            });
        } else if (categoryKey === 'all_instruments') {
            allInstrumentsData.forEach(inst => {
                itemsHtml += createPickerItemHtml('instrument', inst.instrument_id, inst.instrument_name, inst.instrument_code);
            });
        } else if (allInstrumentsGrouped[categoryKey]) {
            allInstrumentsGrouped[categoryKey].forEach(inst => {
                itemsHtml += createPickerItemHtml('instrument', inst.instrument_id, inst.instrument_name, inst.instrument_code);
            });
        }

        pickerItemsContainer.innerHTML = itemsHtml || '<div class="text-center p-4 text-gray-500">Tidak ada item dalam kategori ini.</div>';
        pickerSelectAllCheckbox.checked = false; 
        filterPickerItems(); 
    }

    function createPickerItemHtml(type, id, name, code) {
        const icon = type === 'set' ? 'inventory_2' : 'build';
        const searchTerm = `${escapeHtml((name || '').toLowerCase())} ${escapeHtml((code || '').toLowerCase())}`;
        return `
            <div class="picker-item" data-search-term="${searchTerm}">
                <input type="checkbox" class="picker-item-checkbox" data-id="${id}" data-type="${type}">
                <span class="material-icons picker-item-icon">${icon}</span>
                <div>
                    <div class="picker-item-name">${escapeHtml(name)}</div>
                    <div class="picker-item-code">${escapeHtml(code || '')}</div>
                </div>
            </div>`;
    }

    function filterPickerItems() {
        const filter = pickerSearchInput.value.toLowerCase().trim();
        let allVisibleChecked = true;
        let visibleItemsExist = false;
        
        pickerItemsContainer.querySelectorAll('.picker-item').forEach(item => {
            const searchTerm = item.dataset.searchTerm || '';
            const isVisible = searchTerm.includes(filter);
            item.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) {
                visibleItemsExist = true;
                if (!item.querySelector('.picker-item-checkbox').checked) {
                    allVisibleChecked = false;
                }
            }
        });
        pickerSelectAllCheckbox.checked = visibleItemsExist && allVisibleChecked;
    }
    
    function updatePickerFooter() {
        const selectedCount = pickerItemsContainer.querySelectorAll('.picker-item-checkbox:checked').length;
        pickerSelectedItemCount.textContent = `${selectedCount} item terpilih`;
        addSelectedItemsBtn.disabled = selectedCount === 0;
    }
    
    mainContainer.addEventListener('click', function(e) {
        const target = e.target;
        const button = target.closest('button');

        if (button) {
            if (button.id === 'processLoadBtn') { processLoadModal.classList.add('active'); } 
            else if (button.id === 'openEditModalBtn') {
                if (currentLoadData) {
                    document.getElementById('editLoadId').value = loadId;
                    document.getElementById('edit_machine_id').value = currentLoadData.machine_id;
                    document.getElementById('edit_destination_department_id').value = currentLoadData.destination_department_id || '';
                    document.getElementById('edit_notes').value = currentLoadData.notes || '';
                    editLoadModal.classList.add('active');
                }
            } else if (button.classList.contains('expand-set-btn')) {
                e.stopPropagation();
                const loadItemId = button.dataset.loadItemId;
                const detailsRow = document.querySelector(`tr[data-details-for="${loadItemId}"]`);
                const icon = button.querySelector('.material-icons');
                if (detailsRow.style.display === 'table-row') {
                    detailsRow.style.display = 'none'; icon.textContent = 'expand_more';
                } else {
                    detailsRow.style.display = 'table-row'; icon.textContent = 'expand_less';
                    loadSetContentsIntoRow(loadItemId, detailsRow.querySelector('.instrument-list-in-set'));
                }
            } else if (button.classList.contains('edit-set-contents-btn')) {
                e.stopPropagation();
                openSetEditor(button.dataset.loadItemId, button.dataset.setName);
            } else if (button.classList.contains('remove-item-btn')) {
                e.stopPropagation();
                itemToRemoveId = button.dataset.loadItemId;
                removeItemModal.classList.add('active');
            }
        }
        
        if (!button) {
            const row = target.closest('tr.clickable-row');
            if (row && row.dataset.href !== '#_') { window.location.href = row.dataset.href; }
        }
    });

    itemSearchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = itemSearchInput.value;
        if (query.length < 2) { itemSearchResults.classList.add('hidden'); return; }
        debounceTimer = setTimeout(() => {
            fetch(`php_scripts/search_items.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    itemSearchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const icon = item.type === 'set' ? 'inventory_2' : 'build';
                            const itemTypeDisplay = item.type.charAt(0).toUpperCase() + item.type.slice(1);
                            const div = document.createElement('div');
                            div.dataset.id = item.id; div.dataset.type = item.type; div.className = 'search-result-item';
                            div.innerHTML = `<span class="material-icons mr-3 text-gray-500">${icon}</span><div><div class="font-semibold">${escapeHtml(item.name)}</div><div class="text-xs text-gray-500">${itemTypeDisplay} ${item.code ? `&bull; ${escapeHtml(item.code)}` : ''}</div></div>`;
                            itemSearchResults.appendChild(div);
                        });
                        itemSearchResults.classList.remove('hidden');
                    } else { itemSearchResults.innerHTML = '<div class="p-2 text-center text-sm text-gray-500">Tidak ada hasil ditemukan.</div>'; itemSearchResults.classList.remove('hidden'); }
                });
        }, 300);
    });

    itemSearchResults.addEventListener('click', e => {
        const itemElement = e.target.closest('.search-result-item');
        if (itemElement && itemElement.dataset.id) { addItemToLoad([{ id: itemElement.dataset.id, type: itemElement.dataset.type }]); itemSearchInput.value = ''; itemSearchResults.classList.add('hidden'); }
    });
    
    document.addEventListener('click', e => {
        if (document.getElementById('addItemContainer') && !document.getElementById('addItemContainer').contains(e.target)) { itemSearchResults.classList.add('hidden'); }
    });
    
    openItemPickerBtn.addEventListener('click', () => {
        renderPicker();
        itemPickerModal.classList.add('active');
    });

    closeItemPickerBtn.addEventListener('click', () => {
        itemPickerModal.classList.remove('active');
    });

    itemPickerModal.addEventListener('click', e => {
        if (e.target === itemPickerModal) {
            itemPickerModal.classList.remove('active');
        }
    });

    pickerCategoriesContainer.addEventListener('click', e => {
        e.preventDefault();
        const link = e.target.closest('.picker-category-link');
        if (link) {
            pickerCategoriesContainer.querySelectorAll('.picker-category-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            renderPickerItems(link.dataset.categoryKey);
        }
    });

    pickerItemsContainer.addEventListener('click', e => {
        const itemDiv = e.target.closest('.picker-item');
        if (itemDiv) {
            const checkbox = itemDiv.querySelector('.picker-item-checkbox');
            if (checkbox && e.target.tagName !== 'INPUT') {
                checkbox.checked = !checkbox.checked;
            }
            updatePickerFooter();
        }
    });
    
    pickerSelectAllCheckbox.addEventListener('change', () => {
        pickerItemsContainer.querySelectorAll('.picker-item').forEach(item => {
            if (item.style.display !== 'none') {
                item.querySelector('.picker-item-checkbox').checked = pickerSelectAllCheckbox.checked;
            }
        });
        updatePickerFooter();
    });

    pickerSearchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filterPickerItems, 300);
    });

    addSelectedItemsBtn.addEventListener('click', () => {
        const itemsToAdd = [];
        pickerItemsContainer.querySelectorAll('.picker-item-checkbox:checked').forEach(cb => {
            itemsToAdd.push({ id: cb.dataset.id, type: cb.dataset.type });
        });
        if (itemsToAdd.length > 0) {
            addItemToLoad(itemsToAdd); 
            itemPickerModal.classList.remove('active');
        }
    });

    cancelSetEditBtn.addEventListener('click', () => { setEditorPanel.style.display = 'none'; });
    saveSetChangesBtn.addEventListener('click', () => saveSnapshotChanges(editingLoadItemIdInput.value));
    instrumentPickerTbody.addEventListener('change', e => {
        if (e.target.classList.contains('instrument-checkbox')) {
            const qtyInput = e.target.closest('tr').querySelector('input[type="number"]');
            qtyInput.style.display = e.target.checked ? 'block' : 'none';
        }
    });
    editorInstrumentSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        instrumentPickerTbody.querySelectorAll('tr.instrument-row').forEach(row => {
            const instrumentSearchTerm = row.dataset.searchTerm || '';
            row.style.display = instrumentSearchTerm.includes(searchTerm) ? '' : 'none';
        });
    });
    processLoadForm.addEventListener('change', e => {
        if (e.target.name === 'process_type') {
            const isMerge = e.target.value === 'merge_existing';
            mergeCycleContainer.classList.toggle('hidden', !isMerge);
            targetCycleSelect.disabled = !isMerge;
            if (isMerge) { fetchPendingCycles(currentLoadData.machine_id); }
        }
    });
    editLoadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadingOverlay.style.display = 'flex';
        fetch('php_scripts/load_update.php', { method: 'POST', body: new FormData(this) })
            .then(response => response.json())
            .then(data => {
                if (data.success) { showToast(data.message || 'Perubahan berhasil disimpan.', 'success'); editLoadModal.classList.remove('active'); fetchLoadDetails(); } 
                else { showToast(data.error || 'Gagal menyimpan perubahan.', 'error'); }
            })
            .catch(() => showToast('Kesalahan jaringan.', 'error'))
            .finally(() => loadingOverlay.style.display = 'none');
    });

    [processLoadModal, editLoadModal, removeItemModal].forEach(modal => {
        modal.addEventListener('click', e => {
            if (e.target === modal || e.target.closest('.btn-secondary, #cancelProcessBtn, #cancelEditBtn, #cancelRemoveBtn')) {
                modal.classList.remove('active');
            }
        });
    });
    confirmRemoveBtn.addEventListener('click', () => {
        if (itemToRemoveId) { removeItemFromLoad(itemToRemoveId); itemToRemoveId = null; }
        removeItemModal.classList.remove('active');
    });
    
    async function addItemToLoad(items) {
        loadingOverlay.style.display = 'flex';
        const itemsArray = Array.isArray(items) ? items : [items];

        for (const item of itemsArray) {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('load_id', loadId);
            formData.append('item_id', item.id);
            formData.append('item_type', item.type);

            try {
                const response = await fetch('php_scripts/load_add_item.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (!data.success) {
                    showToast(`Gagal menambahkan item: ${data.error}`, 'error');
                    loadingOverlay.style.display = 'none';
                    return; 
                }
            } catch (error) {
                 showToast('Kesalahan jaringan saat menambahkan item.', 'error');
                 loadingOverlay.style.display = 'none';
                 return;
            }
        }
        
        if (itemsArray.length > 0) {
            showToast(`${itemsArray.length} item berhasil ditambahkan.`, 'success');
        }
        fetchLoadDetails();
    }

    function removeItemFromLoad(loadItemId) {
        loadingOverlay.style.display = 'flex';
        const formData = new FormData();
        formData.append('csrf_token', csrfToken); formData.append('load_item_id', loadItemId);
        fetch('php_scripts/load_remove_item.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { if (data.success) { fetchLoadDetails(); } else { showToast(data.error, 'error'); } })
            .finally(() => loadingOverlay.style.display = 'none');
    }

    function fetchPendingCycles(machineId) {
        targetCycleSelect.innerHTML = '<option>Memuat siklus...</option>';
        fetch(`php_scripts/get_pending_cycles.php?machine_id=${machineId}`)
            .then(response => response.json())
            .then(data => {
                targetCycleSelect.innerHTML = '';
                if (data.success && data.cycles.length > 0) {
                    data.cycles.forEach(cycle => {
                        const option = document.createElement('option'); option.value = cycle.cycle_id;
                        option.textContent = escapeHtml(cycle.cycle_number);
                        targetCycleSelect.appendChild(option);
                    });
                } else { targetCycleSelect.innerHTML = '<option>Tidak ada siklus yang bisa digabungkan.</option>'; targetCycleSelect.disabled = true; }
            });
    }

    // --- PERUBAHAN UTAMA: FUNGSI BARU UNTUK MENAMPILKAN PERBANDINGAN ---
    function loadSetContentsIntoRow(loadItemId, targetElement) {
        const itemData = currentLoadData.items.find(i => i.load_item_id == loadItemId);
        if (!itemData || !itemData.item_snapshot) {
            targetElement.innerHTML = 'Data isi set tidak tersedia.';
            return;
        }

        const snapshot = JSON.parse(itemData.item_snapshot);
        const masterSet = allMasterSetsData[itemData.item_id] || [];
        
        const snapshotMap = new Map(snapshot.map(item => [String(item.instrument_id), item.quantity]));
        const masterMap = new Map(masterSet.map(item => [String(item.instrument_id), item.quantity]));
        
        const allInstrumentIds = new Set([...snapshotMap.keys(), ...masterMap.keys()]);

        if (allInstrumentIds.size === 0) {
            targetElement.innerHTML = 'Set ini (sesuai snapshot) kosong.';
            return;
        }

        let html = '<ul class="snapshot-comparison-list">';
        
        allInstrumentIds.forEach(id => {
            const instrumentDetail = allInstrumentsData.find(inst => String(inst.instrument_id) === id);
            const name = instrumentDetail ? escapeHtml(instrumentDetail.instrument_name) : `ID Instrumen ${id} (telah dihapus)`;
            const inSnapshot = snapshotMap.has(id);
            const inMaster = masterMap.has(id);
            const qtySnapshot = inSnapshot ? snapshotMap.get(id) : 0;
            const qtyMaster = inMaster ? masterMap.get(id) : 0;

            if (inSnapshot && !inMaster) {
                // Ditambahkan
                html += `<li class="item-added"><span class="material-icons">add_circle_outline</span> <span class="item-name">${name} (x${qtySnapshot})</span></li>`;
            } else if (!inSnapshot && inMaster) {
                // Dihapus
                html += `<li class="item-removed"><span class="material-icons">remove_circle_outline</span> <span class="item-name">${name} (x${qtyMaster})</span></li>`;
            } else if (inSnapshot && inMaster) {
                if (qtySnapshot !== qtyMaster) {
                    // Kuantitas diubah
                    html += `<li class="item-modified"><span class="material-icons">edit</span> <span class="item-name">${name}</span> <span class="quantity-change">(x${qtyMaster} &rarr; x${qtySnapshot})</span></li>`;
                } else {
                    // Tidak berubah
                    html += `<li class="item-unchanged"><span class="material-icons">radio_button_unchecked</span> <span class="item-name">${name} (x${qtySnapshot})</span></li>`;
                }
            }
        });

        html += '</ul>';
        targetElement.innerHTML = html;
    }


    function openSetEditor(loadItemId, setName) {
        const itemData = currentLoadData.items.find(i => i.load_item_id == loadItemId);
        if (!itemData || !itemData.item_snapshot) { showToast('Tidak dapat mengedit, data snapshot tidak ditemukan.', 'error'); return; }
        editingSetName.textContent = setName; editingLoadItemIdInput.value = loadItemId;
        const snapshotMap = new Map(JSON.parse(itemData.item_snapshot).map(item => [item.instrument_id.toString(), item.quantity]));
        instrumentPickerTbody.querySelectorAll('.instrument-checkbox').forEach(cb => {
            const instrumentId = cb.dataset.instrumentId;
            const quantityInput = instrumentPickerTbody.querySelector(`input[type="number"][data-instrument-id="${instrumentId}"]`);
            cb.checked = snapshotMap.has(instrumentId);
            quantityInput.value = snapshotMap.get(instrumentId) || 1;
            quantityInput.style.display = cb.checked ? 'block' : 'none';
        });
        setEditorPanel.style.display = 'block'; setEditorPanel.scrollIntoView({ behavior: 'smooth' });
    }

    function saveSnapshotChanges(loadItemId) {
        loadingOverlay.style.display = 'flex';
        const snapshot = Array.from(instrumentPickerTbody.querySelectorAll('input:checked')).map(cb => ({
            instrument_id: parseInt(cb.dataset.instrumentId),
            quantity: parseInt(instrumentPickerTbody.querySelector(`input[type="number"][data-instrument-id="${cb.dataset.instrumentId}"]`).value) || 1
        }));
        fetch('php_scripts/load_update_item_snapshot.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: csrfToken, load_item_id: loadItemId, snapshot: snapshot })
        }).then(r => r.json()).then(data => {
            if (data.success) { showToast(data.message, 'success'); setEditorPanel.style.display = 'none'; fetchLoadDetails(); } 
            else { showToast(data.error, 'error'); }
        }).catch(() => showToast('Kesalahan jaringan.', 'error')).finally(() => loadingOverlay.style.display = 'none');
    }
    
    function getUniversalStatusBadge(status) {
        const map = {
            'persiapan': {text: 'Persiapan', class: 'bg-gray-200 text-gray-800'},
            'menunggu_validasi': {text: 'Menunggu Validasi', class: 'bg-yellow-100 text-yellow-800'},
            'selesai': {text: 'Selesai (Lulus)', class: 'bg-green-100 text-green-800'},
            'gagal': {text: 'Gagal', class: 'bg-red-100 text-red-800'}
        };
        return map[status] || {text: status, class: 'bg-gray-200 text-gray-800'};
    }

    function escapeHtml(str) { 
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); 
    }

    fetchLoadDetails();
});