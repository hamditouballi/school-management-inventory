@extends('layouts.app')

@section('title', __('messages.purchase_orders'))

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.purchase_orders') }}</h1>
            <p class="text-gray-600">
                {{ __('messages.view_and_manage_purchase_orders') }}
            </p>
        </div>
        @if (auth()->user()->role === 'stock_manager')
            <button onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                {{ __('messages.create_purchase_order') }}
            </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ __('messages.search') }}
                </label>
                <input type="text" id="searchInput" placeholder="{{ __('messages.search') }}"
                    class="w-full px-3 py-2 border rounded" oninput="applyFilters()">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ __('messages.status') }}
                </label>
                <select id="statusFilter" class="w-full px-3 py-2 border rounded" onchange="applyFilters()">
                    <option value="">All Statuses</option>
                    <option value="pending_initial_approval">{{ __('messages.pending_initial_approval') }}</option>
                    <option value="initial_approved">{{ __('messages.initial_approved') }}</option>
                    <option value="pending_final_approval">{{ __('messages.pending_final_approval') }}</option>
                    <option value="final_approved">{{ __('messages.final_approved') }}</option>
                    <option value="rejected">{{ __('messages.rejected') }}</option>
                    <option value="ordered">{{ __('messages.ordered') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ __('messages.date_from') }}
                </label>
                <input type="date" id="dateFrom" class="w-full px-3 py-2 border rounded" onchange="applyFilters()">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.image') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.supplier') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.items') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.amount') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.date') }}
                        </th>
                        @if (in_array(auth()->user()->role, ['hr_manager', 'stock_manager']))
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ __('messages.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="poBody">
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalPOs">0</span>
                purchase orders
            </div>
            <div id="pagination" class="flex gap-2">
            </div>
        </div>
    </div>

    <!-- PO Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Purchase Order Details</h3>
                <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div id="detailsContent" class="space-y-4">
                <p class="text-gray-500">Loading...</p>
            </div>
        </div>
    </div>

    <!-- Create/Edit PO Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 id="modalTitle" class="text-xl font-bold mb-4">{{ __('messages.create_purchase_order') }}</h3>
            <form id="createPOForm" onsubmit="createPO(event)">
                <input type="hidden" id="poId" value="">
                <div class="space-y-4 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <label class="block text-sm font-medium mb-1" style="display:none;">Supplier *</label>
                        <input type="hidden" id="poSupplier" name="supplier" value="">
                        <div>
                            <label class="block text-sm font-medium mb-1">Date *</label>
                            <input type="date" id="poDate" name="date" required
                                class="w-full px-3 py-2 border rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Items *</label>
                        <div id="poItemsList" class="space-y-4"></div>
                        <button type="button" onclick="addPOItem()"
                            class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">+ Add
                            Item</button>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</button>
                    <button type="submit" id="submitBtn"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Create</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const token = '{{ session('api_token') }}';
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            };
            let allPOs = [];
            let filteredPOs = [];
            let allItems = [];
            let activeItems = {};
            let currentPage = 1;
            const posPerPage = 10;
            let poItemCounter = 1;
            const isHRManager = {{ auth()->user()->role === 'hr_manager' ? 'true' : 'false' }};
            const isStockManager = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};

            document.addEventListener('DOMContentLoaded', () => {
                loadPOs();
                if (isStockManager) {
                    loadItemsForPO();
                }
            });

            function loadPOs() {
                fetch('/api/purchase-orders', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allPOs = data;
                        filteredPOs = data;
                        renderPOs();
                    })
                    .catch(() => {
                        document.getElementById('poBody').innerHTML =
                            '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading purchase orders</td></tr>';
                    });
            }

            function loadItemsForPO() {
                fetch('/api/items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allItems = data;
                        updatePOItemSelects();
                    });
            }

            function applyFilters() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const status = document.getElementById('statusFilter').value;
                const dateFrom = document.getElementById('dateFrom').value;

                filteredPOs = allPOs.filter(po => {
                    let match = true;

                    if (search && !po.supplier.toLowerCase().includes(search)) {
                        match = false;
                    }

                    if (status && po.status !== status) {
                        match = false;
                    }

                    const poDate = new Date(po.date);
                    if (dateFrom && poDate < new Date(dateFrom)) {
                        match = false;
                    }

                    return match;
                });

                currentPage = 1;
                renderPOs();
            }

            function renderPOs() {
                const start = (currentPage - 1) * posPerPage;
                const end = start + posPerPage;
                const pagePOs = filteredPOs.slice(start, end);

                const statusColors = {
                    pending_initial_approval: 'bg-yellow-100 text-yellow-800',
                    initial_approved: 'bg-blue-100 text-blue-800',
                    pending_final_approval: 'bg-orange-100 text-orange-800',
                    final_approved: 'bg-green-100 text-green-800',
                    rejected: 'bg-red-100 text-red-800',
                    ordered: 'bg-purple-100 text-purple-800'
                };

                const tbody = document.getElementById('poBody');
                if (pagePOs.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No purchase orders found</td></tr>';
                } else {
                    tbody.innerHTML = pagePOs.map(po => {
                        const poItems = po.purchase_order_items || [];
                        const totalQty = poItems.reduce((sum, item) => sum + parseFloat(item.quantity || 0), 0);

                        const firstItemImage = poItems.find(item => item.item?.image_path)?.item?.image_path;
                        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    ${firstItemImage ? 
                        `<img src="/storage/${firstItemImage}" class="w-12 h-12 object-cover rounded cursor-pointer" onclick="viewPODetails(${po.id})">` : 
                        '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">No Image</div>'
                    }
                </td>
                <td class="px-6 py-4">#${po.id}</td>
                <td class="px-6 py-4">${po.supplier}</td>
                <td class="px-6 py-4">
                    <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:underline">
                        ${poItems.length} {{ __('messages.items') }} (${totalQty.toFixed(2)} {{ __('messages.unit') }})
                    </button>
                </td>
                <td class="px-6 py-4">{{ __('messages.currency') }} ${parseFloat(po.total_amount).toFixed(2)}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded ${statusColors[po.status]}">${
                    po.status.replace(/_/g, ' ').toUpperCase() == "PENDING INITIAL APPROVAL" 
                    ? "{{ __('messages.pending_initial_approval') }}" 
                    : po.status.replace(/_/g, ' ').toUpperCase() == "INITIAL APPROVED" 
                        ? "{{ __('messages.initial_approved') }}" 
                        : po.status.replace(/_/g, ' ').toUpperCase() == "PENDING FINAL APPROVAL" 
                            ? "{{ __('messages.pending_final_approval') }}" 
                            : po.status.replace(/_/g, ' ').toUpperCase() == "FINAL APPROVED" 
                                ? "{{ __('messages.final_approved') }}" 
                                : po.status.replace(/_/g, ' ').toUpperCase() == "REJECTED" 
                                    ? "{{ __('messages.rejected') }}" 
                                    : po.status.replace(/_/g, ' ').toUpperCase() == "ORDERED" 
                                        ? "{{ __('messages.ordered') }}" 
                                        : po.status.toUpperCase()
                    }</span></td>
                <td class="px-6 py-4">${new Date(po.date).toLocaleDateString()}</td>
                ${(isHRManager || isStockManager) ? `
                                                                                                            <td class="px-6 py-4">
                                                                                                                ${isHRManager && po.status === 'pending_initial_approval' ? `
                            <button onclick="approveInitial(${po.id})" class="text-green-600 hover:text-green-800 mr-2">{{ __('messages.approve_initial') }}</button>
                            <button onclick="rejectInitial(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.reject') }}</button>
                        ` : isHRManager && po.status === 'pending_final_approval' ? `
                            <button onclick="viewPODetails(${po.id})" class="text-blue-600 hover:text-blue-800">{{ __('messages.select_final') }}</button>
                        ` : isStockManager && po.status === 'pending_initial_approval' ? `
                            <button onclick="editPO(${po.id})" class="text-blue-600 hover:text-blue-800 mr-2">{{ __('messages.edit') }}</button>
                            <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                        ` : isStockManager && po.status === 'initial_approved' ? `
                            <button onclick="viewPODetails(${po.id})" class="text-orange-600 hover:text-orange-800">{{ __('messages.add_proposals') }}</button>
                        ` : `
                            <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                        `}
                                                                                                            </td>
                                                                                                        ` : ''}
            </tr>
        `;
                    }).join('');
                }

                updatePagination();
            }

            function updatePagination() {
                const totalPages = Math.ceil(filteredPOs.length / posPerPage);
                const start = (currentPage - 1) * posPerPage + 1;
                const end = Math.min(currentPage * posPerPage, filteredPOs.length);

                document.getElementById('showingFrom').textContent = filteredPOs.length ? start : 0;
                document.getElementById('showingTo').textContent = end;
                document.getElementById('totalPOs').textContent = filteredPOs.length;

                const pagination = document.getElementById('pagination');
                let html = '';

                if (currentPage > 1) {
                    html +=
                        `<button onclick="changePage(${currentPage - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Previous</button>`;
                }

                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                        html +=
                            `<button onclick="changePage(${i})" class="px-3 py-1 border rounded ${i === currentPage ? 'bg-green-600 text-white' : 'hover:bg-gray-100'}">${i}</button>`;
                    } else if (i === currentPage - 2 || i === currentPage + 2) {
                        html += '<span class="px-2">...</span>';
                    }
                }

                if (currentPage < totalPages) {
                    html +=
                        `<button onclick="changePage(${currentPage + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Next</button>`;
                }

                pagination.innerHTML = html;
            }

            function changePage(page) {
                currentPage = page;
                renderPOs();
            }

            function showCreateModal() {
                document.getElementById('modalTitle').textContent = '{{ __('messages.create_purchase_order') }}';
                document.getElementById('submitBtn').textContent = 'Create';
                document.getElementById('poId').value = '';
                document.getElementById('poItemsList').innerHTML = '';
                poItemCounter = 0;
                addPOItem(); // Add first item
                document.getElementById('createModal').classList.remove('hidden');
            }

            function editPO(id) {
                fetch(`/api/purchase-orders/${id}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(po => {
                        document.getElementById('modalTitle').textContent = 'Edit Purchase Order';
                        document.getElementById('submitBtn').textContent = 'Update';
                        document.getElementById('poId').value = po.id;
                        document.getElementById('poSupplier').value = po.supplier;
                        document.getElementById('poDate').value = po.date;

                        // Clear and populate items
                        document.getElementById('poItemsList').innerHTML = '';
                        poItemCounter = 0;

                        const poItems = po.purchase_order_items || [];
                        poItems.forEach((item, index) => {
                            addPOItem();

                            if (item.item_id) {
                                // Existing item
                                document.querySelector(`select[name="items[${index}][item_id]"]`).value = item
                                    .item_id;
                            } else if (item.new_item_name) {
                                // New item
                                const radio = document.querySelector(
                                    `input[name="item_type_${index}"][value="new"]`);
                                radio.checked = true;
                                toggleItemType(index, 'new');
                                document.querySelector(`input[name="items[${index}][new_item_name]"]`).value = item
                                    .new_item_name;
                            }

                            document.querySelector(`input[name="items[${index}][quantity]"]`).value = item.quantity;
                        });

                        document.getElementById('createModal').classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('Error loading purchase order');
                    });
            }

            function closeCreateModal() {
                document.getElementById('createModal').classList.add('hidden');
                document.getElementById('createPOForm').reset();
                document.getElementById('poItemsList').innerHTML = '';
                poItemCounter = 0;
            }

            function addPOItem() {
                const container = document.getElementById('poItemsList');
                const itemDiv = document.createElement('div');
                itemDiv.className = 'border p-4 rounded-lg bg-gray-50';
                const currentIndex = poItemCounter;

                itemDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-sm">Item #${currentIndex + 1}</h4>
            ${currentIndex > 0 ? `<button type="button" onclick="removePOItem(this)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>` : ''}
        </div>
        <div class="space-y-3">
            <div class="flex gap-2 items-center">
                <input type="radio" name="item_type_${currentIndex}" value="existing" checked onchange="toggleItemType(${currentIndex}, 'existing')">
                <label class="text-sm">Select Existing Item</label>
                <input type="radio" name="item_type_${currentIndex}" value="new" onchange="toggleItemType(${currentIndex}, 'new')">
                <label class="text-sm">Create New Item</label>
            </div>
            <div id="existing_item_${currentIndex}" class="space-y-2">
                <label class="block text-xs font-medium">Item *</label>
                <select name="items[${currentIndex}][item_id]" onchange="setActive(this)" class="w-full px-3 py-2 border rounded text-sm">
    <option value="">Select Item...</option>
    ${allItems.map(item => `
                                                                                                                        <option
                                                                                                  value="${item.id}"
                                                                                                  data-item="${encodeURIComponent(JSON.stringify(item))}">
                                                                                                  ${item.designation} - ${item.price}
                                                                                                </option>

                                                                                                                    `).join('')}
</select>
            </div>
            <div id="new_item_${currentIndex}" class="hidden space-y-2">
                <label class="block text-xs font-medium">New Item Name *</label>
                <input type="text" name="items[${currentIndex}][new_item_name]" class="w-full px-3 py-2 border rounded text-sm" placeholder="Enter item name">
                <label class="block text-xs font-medium mt-2">Unit *</label>
                <select name="items[${currentIndex}][unit]" class="w-full px-3 py-2 border rounded text-sm">
                    <option value="unit">Unit</option>
                    <option value="kg">Kg</option>
                    <option value="liter">Liter</option>
                    <option value="meter">Meter</option>
                    <option value="box">Box</option>
                    <option value="pack">Pack</option>
                    <option value="piece">Piece</option>
                </select>
                <label class="block text-xs font-medium mt-2">Item Image (Optional)</label>
                <input type="file" name="items[${currentIndex}][image]" accept="image/*" class="w-full px-3 py-2 border rounded text-sm" onchange="previewNewItemImage(${currentIndex})">
                <img id="new_item_preview_${currentIndex}" class="hidden mt-2 w-24 h-24 object-cover rounded border">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1">Quantity *</label>
                    <input type="number" name="items[${currentIndex}][quantity]" required min="0.01" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Unit</label>
                    <input
  id="unit_${currentIndex}"
  disabled
  class="w-full px-3 py-2 border rounded text-sm">
                </div>
            </div>
        </div>
    `;

                container.appendChild(itemDiv);
                poItemCounter++;
            }

            function removePOItem(button) {
                button.closest('.border').remove();
            }

            function toggleItemType(index, type) {
                const existingDiv = document.getElementById(`existing_item_${index}`);
                const newDiv = document.getElementById(`new_item_${index}`);
                const itemSelect = document.querySelector(`select[name="items[${index}][item_id]"]`);
                const itemNameInput = document.querySelector(`input[name="items[${index}][new_item_name]"]`);

                if (type === 'existing') {
                    existingDiv.classList.remove('hidden');
                    newDiv.classList.add('hidden');
                    itemSelect.required = true;
                    itemNameInput.required = false;
                    itemNameInput.value = '';
                } else {
                    existingDiv.classList.add('hidden');
                    newDiv.classList.remove('hidden');
                    itemSelect.required = false;
                    itemSelect.value = '';
                    itemNameInput.required = true;
                }
            }

            function setActive(select) {
                const option = select.options[select.selectedIndex];
                if (!option.dataset.item) return;

                const index = select.name.match(/\d+/)[0];

                activeItems[index] = JSON.parse(
                    decodeURIComponent(option.dataset.item)
                );

                document.getElementById(`unit_${index}`).value = activeItems[index].unit;

                console.log(activeItems[index]);
            }


            function previewNewItemImage(index) {
                const input = document.querySelector(`input[name="items[${index}][image]"]`);
                const preview = document.getElementById(`new_item_preview_${index}`);

                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function createPO(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const poId = document.getElementById('poId').value;
                const isEdit = !!poId;
                const items = [];

                for (let i = 0; i < poItemCounter; i++) {
                    const itemId = formData.get(`items[${i}][item_id]`);
                    const newItemName = formData.get(`items[${i}][new_item_name]`);
                    const unit = formData.get(`items[${i}][unit]`);
                    const quantity = formData.get(`items[${i}][quantity]`);

                    if ((itemId || newItemName) && quantity) {
                        const itemData = {
                            quantity: parseFloat(quantity)
                        };

                        if (itemId) {
                            itemData.item_id = parseInt(itemId);
                        } else if (newItemName) {
                            itemData.new_item_name = newItemName;
                            itemData.unit = unit || 'unit';
                        }

                        items.push(itemData);
                    }
                }

                const poFormData = new FormData();
                poFormData.append('date', formData.get('date'));
                poFormData.append('items', JSON.stringify(items));

                // Add images for new items
                for (let i = 0; i < poItemCounter; i++) {
                    const imageFile = formData.get(`items[${i}][image]`);
                    if (imageFile && imageFile.size > 0) {
                        poFormData.append(`item_image_${i}`, imageFile);
                    }
                }

                if (isEdit) {
                    poFormData.append('_method', 'PUT');
                }

                const url = isEdit ? `/api/purchase-orders/${poId}` : '/api/purchase-orders';

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        },
                        body: poFormData
                    })
                    .then(res => res.json())
                    .then(() => {
                        closeCreateModal();
                        loadPOs();
                        Notification.success(`Purchase order ${isEdit ? 'updated' : 'created'} successfully!`);
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error(`Error ${isEdit ? 'updating' : 'creating'} purchase order`);
                    });
            }

            function viewPODetails(id) {
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsContent').innerHTML = '<p class="text-gray-500">Loading...</p>';

                fetch(`/api/purchase-orders/${id}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(po => {
                        const poItems = po.purchase_order_items || [];
                        const statusColors = {
                            pending_hr: 'bg-yellow-100 text-yellow-800',
                            approved_hr: 'bg-green-100 text-green-800',
                            rejected_hr: 'bg-red-100 text-red-800',
                            ordered: 'bg-blue-100 text-blue-800'
                        };

                        const html = `
                <div class="border-b pb-4 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">PO ID</p>
                            <p class="font-semibold">#${po.id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span class="px-2 py-1 text-xs rounded ${statusColors[po.status]}">${po.status.replace(/_/g, ' ').toUpperCase()}</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.supplier') }}</p>
                            <p class="font-semibold">${po.supplier || 'Pending Selection'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="font-semibold">${new Date(po.date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Amount</p>
                            <p class="font-semibold">{{ __('messages.currency') }} ${parseFloat(po.total_amount).toFixed(2)}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Items</h4>
                    ${poItems.length === 0 ? '<p class="text-gray-500">No items</p>' : `
                                                                                                        <table class="min-w-full mb-6">
                                                                                                            <thead class="bg-gray-50">
                                                                                                                <tr>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Image</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quantity</th>
                                                                                                                </tr>
                                                                                                            </thead>
                                                                                                            <tbody class="divide-y">
                                                                                                                ${poItems.map(item => `
                                    <tr>
                                        <td class="px-4 py-2">
                                            ${item.item?.image_path ? 
                                                `<img src="/storage/${item.item.image_path}" class="w-16 h-16 object-cover rounded">` : 
                                                '<div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">No Img</div>'
                                            }
                                        </td>
                                        <td class="px-4 py-2">
                                            ${item.item?.designation || item.new_item_name || 'Unknown'}
                                            ${item.new_item_name && !item.item ? '<span class="ml-2 text-xs text-gray-500">(New Item)</span>' : ''}
                                        </td>
                                        <td class="px-4 py-2">${parseFloat(item.quantity).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                                                                                                            </tbody>
                                                                                                        </table>
                                                                                                    `}
                </div>

                <!-- Proposals Section -->
                ${po.proposals && po.proposals.length > 0 ? `
                                                                                                    <div class="mt-6 pt-4 border-t">
                                                                                                        <h4 class="font-semibold mb-3">Supplier Proposals</h4>
                                                                                                        <div class="grid gap-4" id="finalSelectionForm">
                                                                                                            ${po.proposals.map(prop => `
                                <label class="border p-4 rounded-lg flex justify-between items-center cursor-pointer hover:bg-gray-50 ${prop.is_selected ? 'bg-green-50 border-green-200' : 'bg-white'}">
                                    <div class="flex items-center gap-4">
                                        ${isHRManager && po.status === 'pending_final_approval' ? `
                                                                                                            <input type="radio" name="selected_proposal" value="${prop.id}" class="w-5 h-5 text-blue-600">
                                                                                                        ` : ''}
                                        <div>
                                            <h5 class="font-bold">${prop.supplier_name}</h5>
                                            <p class="text-sm text-gray-600">Unit Price: <span class="font-semibold">{{ __('messages.currency') }} ${parseFloat(prop.price).toFixed(2)}</span></p>
                                            <p class="text-sm text-gray-600">Quality: ${prop.quality_rating || 'N/A'}/10</p>
                                            ${prop.notes ? `<p class="text-xs text-gray-500 mt-1">${prop.notes}</p>` : ''}
                                        </div>
                                    </div>
                                    <div>
                                        ${prop.is_selected ? `<span class="px-3 py-1 bg-green-100 text-green-800 rounded font-semibold text-sm">Selected Final</span>` : ''}
                                    </div>
                                </label>
                            `).join('')}
                                                                                                        </div>
                                                                                                        ${isHRManager && po.status === 'pending_final_approval' ? `
                                            <div class="mt-6 pt-4 border-t flex justify-end">
                                                <button onclick="submitFinalSelection(${po.id})" class="px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700">Accept</button>
                                            </div>
                                        ` : ''}
                                                                                                    </div>
                                                                                                ` : ''}

                <!-- Add Proposals Form for Stock Manager -->
                ${isStockManager && po.status === 'initial_approved' ? `
                                                                                                    <div class="mt-6 pt-4 border-t">
                                                                                                        <h4 class="font-semibold mb-3 text-orange-600">Add Supplier Proposals</h4>
                                                                                                        <form id="addProposalsForm" onsubmit="submitProposals(event, ${po.id})">
                                                                                                            <div id="proposalsList" class="space-y-4 mb-4">
                                                                                                                <!-- First proposal always visible -->
                                                                                                                <div class="border p-4 rounded bg-orange-50 proposal-entry">
                                                                                                                    <div class="grid grid-cols-2 gap-4">
                                                                                                                        <div><label class="block text-xs font-semibold">Supplier Name *</label><input type="text" name="supplier_name[]" required class="w-full px-2 py-1 border rounded text-sm"></div>
                                                                                                                        <div><label class="block text-xs font-semibold">Unit Price *</label><input type="number" name="price[]" step="0.01" required class="w-full px-2 py-1 border rounded text-sm"></div>
                                                                                                                        <div>
                                                                                                                            <label class="block text-xs font-semibold">Quality Rating *</label>
                                                                                                                            <select name="quality_rating[]" required class="w-full px-2 py-1 border rounded text-sm">
                                                                                                                                <option value="">Select (1-10)</option>
                                                                                                                                <option value="1">1</option>
                                                                                                                                <option value="2">2</option>
                                                                                                                                <option value="3">3</option>
                                                                                                                                <option value="4">4</option>
                                                                                                                                <option value="5">5</option>
                                                                                                                                <option value="6">6</option>
                                                                                                                                <option value="7">7</option>
                                                                                                                                <option value="8">8</option>
                                                                                                                                <option value="9">9</option>
                                                                                                                                <option value="10">10</option>
                                                                                                                            </select>
                                                                                                                        </div>
                                                                                                                        <div><label class="block text-xs font-semibold">Notes</label><input type="text" name="notes[]" class="w-full px-2 py-1 border rounded text-sm"></div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="mt-6 pt-4 border-t flex justify-between items-center">
                                                                                                                <button type="button" onclick="addProposalField()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 font-semibold text-sm">Add Supplier</button>
                                                                                                                <button type="submit" class="z-10 px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700 w-32">Accept</button>
                                                                                                            </div>
                                                                                                        </form>
                                                                                                    </div>
                                                                                                ` : ''}

                <!-- Action Buttons -->
                ${isHRManager && po.status === 'pending_initial_approval' ? `
                                                                                                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                                                                                                        <button onclick="approveInitial(${po.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Approve Initial</button>
                                                                                                        <button onclick="rejectInitial(${po.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Reject</button>
                                                                                                    </div>
                                                                                                ` : ''}
            `;
                        document.getElementById('detailsContent').innerHTML = html;
                    })
                    .catch(() => {
                        document.getElementById('detailsContent').innerHTML =
                            '<p class="text-red-500">Error loading details</p>';
                    });
            }

            function closeDetailsModal() {
                document.getElementById('detailsModal').classList.add('hidden');
            }

            function addProposalField() {
                const list = document.getElementById('proposalsList');
                const html = `
                    <div class="border p-4 rounded bg-orange-50 mt-4 proposal-entry">
                        <div class="flex justify-end mb-2"><button type="button" onclick="this.closest('.proposal-entry').remove()" class="text-xs text-red-600">Remove</button></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold">Supplier Name *</label><input type="text" name="supplier_name[]" required class="w-full px-2 py-1 border rounded text-sm"></div>
                            <div><label class="block text-xs font-semibold">Unit Price *</label><input type="number" name="price[]" step="0.01" required class="w-full px-2 py-1 border rounded text-sm"></div>
                            <div>
                                <label class="block text-xs font-semibold">Quality Rating *</label>
                                <select name="quality_rating[]" required class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">Select (1-10)</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-semibold">Notes</label><input type="text" name="notes[]" class="w-full px-2 py-1 border rounded text-sm"></div>
                        </div>
                    </div>
                `;
                list.insertAdjacentHTML('beforeend', html);
            }

            function submitProposals(e, poId) {
                e.preventDefault();
                const form = e.target;
                const suppliers = form.querySelectorAll('input[name="supplier_name[]"]');
                const prices = form.querySelectorAll('input[name="price[]"]');
                const ratings = form.querySelectorAll('select[name="quality_rating[]"]');
                const notes = form.querySelectorAll('input[name="notes[]"]');

                let proposals = [];
                for (let i = 0; i < suppliers.length; i++) {
                    proposals.push({
                        supplier_name: suppliers[i].value,
                        price: prices[i].value,
                        quality_rating: ratings[i].value,
                        notes: notes[i].value
                    });
                }

                fetch(`/api/purchase-orders/${poId}/proposals`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            proposals
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success('Proposals added successfully! Awaiting HR selection.');
                    })
                    .catch(err => Notification.error('Error submitting proposals'));
            }

            function submitFinalSelection(poId) {
                const selectedRadio = document.querySelector('input[name="selected_proposal"]:checked');

                if (!selectedRadio) {
                    Notification.error('Please select a supplier proposal first.');
                    return;
                }

                if (confirm('Select this supplier as the final choice?')) {
                    fetch(`/api/purchase-orders/${poId}/final-approval`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                proposal_id: selectedRadio.value
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            closeDetailsModal();
                            loadPOs();
                            Notification.success('Supplier successfully selected!');
                        })
                        .catch(err => Notification.error('Error selecting supplier'));
                }
            }

            function approveInitial(id) {
                updateInitialApproval(id, 'approve');
            }

            function rejectInitial(id) {
                if (confirm('Reject this purchase order?')) {
                    updateInitialApproval(id, 'reject');
                }
            }

            function updateInitialApproval(id, action) {
                fetch(`/api/purchase-orders/${id}/initial-approval`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action
                        })
                    })
                    .then(() => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success(`Purchase order ${action}d successfully!`);
                    })
                    .catch(err => Notification.error('Error updating status'));
            }
        </script>
    @endpush
@endsection
