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
                    <option value="pending_hr">Pending HR</option>
                    <option value="approved_hr">Approved HR</option>
                    <option value="rejected_hr">Rejected HR</option>
                    <option value="ordered">Ordered</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.supplier') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.items') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        @if (in_array(auth()->user()->role, ['hr_manager', 'stock_manager']))
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                        <div>
                            <label class="block text-sm font-medium mb-1">Supplier *</label>
                            <input type="text" id="poSupplier" name="supplier" required
                                class="w-full px-3 py-2 border rounded">
                        </div>
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
                    pending_hr: 'bg-yellow-100 text-yellow-800',
                    approved_hr: 'bg-green-100 text-green-800',
                    rejected_hr: 'bg-red-100 text-red-800',
                    ordered: 'bg-blue-100 text-blue-800'
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
                    po.status.replace('_', ' ').toUpperCase() == "PENDING HR" 
                    ? "{{ __('messages.pending_hr') }}" 
                    : po.status.replace('_', ' ').toUpperCase() == "APPROVED HR" 
                        ? "{{ __('messages.approved_hr') }}" 
                        : po.status.replace('_', ' ').toUpperCase() == "REJECTED HR" 
                            ? "{{ __('messages.rejected_hr') }}" 
                            : po.status.replace('_', ' ').toUpperCase() == "ORDERED" 
                                ? "{{ __('messages.ordered') }}" 
                                : po.status.toUpperCase()
                    }</span></td>
                <td class="px-6 py-4">${new Date(po.date).toLocaleDateString()}</td>
                ${(isHRManager || isStockManager) ? `
                                                                                                                                                                                                        <td class="px-6 py-4">
                                                                                                                                                                                                            ${isHRManager && po.status === 'pending_hr' ? `
                        <button onclick="approvePO(${po.id})" class="text-green-600 hover:text-green-800 mr-2">Approve</button>
                        <button onclick="rejectPO(${po.id})" class="text-red-600 hover:text-red-800">Reject</button>
                    ` : isStockManager && po.status === 'pending_hr' ? `
                        <button onclick="editPO(${po.id})" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                        <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">View</button>
                    ` : isStockManager ? `
                        <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">View</button>
                    ` : '-'}
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
                            document.querySelector(`input[name="items[${index}][unit_price]"]`).value = item
                                .unit_price;
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
                <div>
                    <label class="block text-xs font-medium mb-1">Unit Price *</label>
                    <input type="number" name="items[${currentIndex}][unit_price]" required min="0" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
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
                    const unitPrice = formData.get(`items[${i}][unit_price]`);

                    if ((itemId || newItemName) && quantity && unitPrice) {
                        const itemData = {
                            quantity: parseFloat(quantity),
                            unit_price: parseFloat(unitPrice)
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
                poFormData.append('supplier', formData.get('supplier'));
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
                            <span class="px-2 py-1 text-xs rounded ${statusColors[po.status]}">${po.status.replace('_', ' ').toUpperCase()}</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.supplier') }}</p>
                            <p class="font-semibold">${po.supplier}</p>
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
                                                                                                                                                                                                                <table class="min-w-full">
                                                                                                                                                                                                                    <thead class="bg-gray-50">
                                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Image</th>
                                                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quantity</th>
                                                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Unit Price</th>
                                                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Subtotal</th>
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
                                        <td class="px-4 py-2">{{ __('messages.currency') }} ${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td class="px-4 py-2">{{ __('messages.currency') }} ${(parseFloat(item.quantity) * parseFloat(item.unit_price)).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                                                                                                                                                                                                                    </tbody>
                                                                                                                                                                                                                </table>
                                                                                                                                                                                                            `}
                </div>
                ${isHRManager && po.status === 'pending_hr' ? `
                                                                                                                                                                                                            <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                                                                                                                                                                                                                <button onclick="approvePO(${po.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Approve</button>
                                                                                                                                                                                                                <button onclick="rejectPO(${po.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Reject</button>
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

            function approvePO(id) {
                // if (confirm('Approve this purchase order?')) {
                updatePOStatus(id, 'approved_hr');
                // }
            }

            function rejectPO(id) {
                if (confirm('Reject this purchase order?')) {
                    updatePOStatus(id, 'rejected_hr');
                }
            }

            function updatePOStatus(id, status) {
                fetch(`/api/purchase-orders/${id}/status`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            status
                        })
                    })
                    .then(() => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success(`Purchase order ${status.replace('_hr', '')}!`);
                    })
                    .catch(err => Notification.error('Error updating status'));
            }
        </script>
    @endpush
@endsection
