
@extends('layouts.app')

@section('title', __('messages.purchase_orders'))

@push('styles')
<style>
.ts-wrapper .ts-control .item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.ts-wrapper .ts-control .item img {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
}
.ts-dropdown .ts-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
}
.ts-dropdown .ts-option img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}
.ts-dropdown .ts-option .item-info {
    flex: 1;
    min-width: 0;
}
.ts-dropdown .ts-option .item-name {
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ts-dropdown .ts-option .item-meta {
    font-size: 11px;
    color: #6b7280;
    display: flex;
    gap: 8px;
}
.ts-dropdown .ts-option .item-price {
    font-weight: 600;
    color: #059669;
    flex-shrink: 0;
}
</style>
@endpush

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
                    <option value="">{{ __('messages.all_statuses') }}</option>
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
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.loading') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t flex items-center justify-between">
            <div class="text-sm text-gray-700">
                {{ __('messages.showing') }} <span id="showingFrom">0</span> {{ __('messages.to') }} <span id="showingTo">0</span> {{ __('messages.of') }} <span id="totalPOs">0</span>
                {{ __('messages.purchase_orders') }}
            </div>
            <div id="pagination" class="flex gap-2">
            </div>
        </div>
    </div>

    <!-- PO Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <p class="text-xl font-bold">{{ __('messages.purchase_order_details') }}</p>
                <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div id="detailsContent" class="space-y-4">
                <p class="text-gray-500">{{ __('messages.loading') }}</p>
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
                        <label class="block text-sm font-medium mb-1" style="display:none;">{{ __('messages.supplier') }} *</label>
                        <input type="hidden" id="poSupplier" name="supplier" value="">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.date') }} *</label>
                            <input type="date" id="poDate" name="date" required
                                value="{{ date('Y-m-d') }}"
                                class="w-full px-3 py-2 border rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('messages.items') }} *</label>
                        <div id="poItemsList" class="space-y-4"></div>
                        <button type="button" onclick="addPOItem()"
                            class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">+ {{ __('messages.add_item') }}</button>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                    <button type="submit" id="submitBtn"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.create') }}</button>
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
                            '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">{{ __('messages.error_loading_purchase_orders') }}</td></tr>';
                    });
            }

            function loadItemsForPO() {
                fetch('/api/items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allItems = data;
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

                const statusTranslations = {
                    pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
                    initial_approved: '{{ __('messages.initial_approved') }}',
                    pending_final_approval: '{{ __('messages.pending_final_approval') }}',
                    final_approved: '{{ __('messages.final_approved') }}',
                    rejected: '{{ __('messages.rejected') }}',
                    ordered: '{{ __('messages.ordered') }}'
                };

                const tbody = document.getElementById('poBody');
                if (pagePOs.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.no_purchase_orders_found') }}</td></tr>';
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
                        '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'
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
                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded ${statusColors[po.status]}">${statusTranslations[po.status] || po.status}</span></td>
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
                        `<button onclick="changePage(${currentPage - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">{{ __('messages.previous') }}</button>`;
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
                        `<button onclick="changePage(${currentPage + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">{{ __('messages.next') }}</button>`;
                }

                pagination.innerHTML = html;
            }

            function changePage(page) {
                currentPage = page;
                renderPOs();
            }

            function showCreateModal() {
                document.getElementById('modalTitle').textContent = '{{ __('messages.create_purchase_order') }}';
                document.getElementById('submitBtn').textContent = '{{ __('messages.create') }}';
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
document.getElementById('modalTitle').textContent = '{{ __('messages.edit') }} {{ __('messages.purchase_orders') }}';
                document.getElementById('submitBtn').textContent = '{{ __('messages.update') }}';
                        document.getElementById('poId').value = po.id;
                        document.getElementById('poSupplier').value = po.supplier;
                        document.getElementById('poDate').value = po.date ? new Date(po.date).toISOString().split('T')[0] : '';

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
                        Notification.error('{{ __('messages.error_loading_purchase_order') }}');
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
            <h4 class="font-semibold text-sm">{{ __('messages.item_number') }} #${currentIndex + 1}</h4>
            ${currentIndex > 0 ? `<button type="button" onclick="removePOItem(this)" class="text-red-600 hover:text-red-800 text-sm">{{ __('messages.remove') }}</button>` : ''}
        </div>
        <div class="space-y-3">
            <div class="flex gap-2 items-center">
                <input type="radio" name="item_type_${currentIndex}" value="existing" checked onchange="toggleItemType(${currentIndex}, 'existing')">
                <label class="text-sm">{{ __('messages.select_existing_item') }}</label>
                <input type="radio" name="item_type_${currentIndex}" value="new" onchange="toggleItemType(${currentIndex}, 'new')">
                <label class="text-sm">{{ __('messages.create_new_item') }}</label>
            </div>
            <div id="existing_item_${currentIndex}" class="space-y-2">
                <label class="block text-xs font-medium">{{ __('messages.item') }} *</label>
                <select name="items[${currentIndex}][item_id]" id="item_select_${currentIndex}" onchange="setActive(this)" class="item-select w-full">
    <option value="">{{ __('messages.select_item') }}</option>
    ${allItems.map(item => `
                                                                                                                        <option
                                                                                                  value="${item.id}"
                                                                                                  data-item="${encodeURIComponent(JSON.stringify(item))}"
                                                                                                  data-image-path="${item.image_path || ''}"
                                                                                                  data-category-name="${item.category?.name || ''}"
                                                                                                  data-price="${item.price || ''}">
                                                                                                  ${item.designation}
                                                                                                </option>

                                                                                                                    `).join('')}
</select>
            </div>
            <div id="new_item_${currentIndex}" class="hidden space-y-2">
                <label class="block text-xs font-medium">{{ __('messages.new_item_name') }}</label>
                <input type="text" name="items[${currentIndex}][new_item_name]" class="w-full px-3 py-2 border rounded text-sm" placeholder="{{ __('messages.enter_item_name') }}">
                <label class="block text-xs font-medium mt-2">{{ __('messages.unit') }}</label>
                <select name="items[${currentIndex}][unit]" class="w-full px-3 py-2 border rounded text-sm">
                    <option value="unit">{{ __('messages.unit') }}</option>
                    <option value="kg">{{ __('messages.kg') }}</option>
                    <option value="liter">{{ __('messages.liter') }}</option>
                    <option value="meter">{{ __('messages.meter') }}</option>
                    <option value="box">{{ __('messages.box') }}</option>
                    <option value="pack">{{ __('messages.pack') }}</option>
                    <option value="piece">{{ __('messages.piece') }}</option>
                </select>
                <label class="block text-xs font-medium mt-2">{{ __('messages.item_image_optional') }}</label>
                <input type="file" name="items[${currentIndex}][image]" accept="image/*" class="w-full px-3 py-2 border rounded text-sm" onchange="previewNewItemImage(${currentIndex})">
                <img id="new_item_preview_${currentIndex}" class="hidden mt-2 w-24 h-24 object-cover rounded border">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1">{{ __('messages.quantity') }}</label>
                    <input type="number" name="items[${currentIndex}][quantity]" required min="0.01" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">{{ __('messages.unit') }}</label>
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
                initItemSelect(currentIndex);
            }

            function initItemSelect(index) {
                const selectEl = document.getElementById(`item_select_${index}`);
                if (!selectEl || selectEl.dataset.tomSelectInit) return;
                
                const ts = new TomSelect(selectEl, {
                    plugins: ['dropdown_input'],
                    searchField: ['text', 'categoryName'],
                    render: {
                        option: function(data, escape) {
                            console.log('Rendering option:', data);
                            const image = data.imagePath ? `<img src="/storage/${data.imagePath}" alt="" class="w-10 h-10 object-cover rounded border border-gray-200">` : '<div class="w-10 h-10 rounded border border-gray-200 bg-gray-100 flex items-center justify-center text-gray-400 text-xs">N/A</div>';
                            const categoryName = data.categoryName || '';
                            const price = data.price ? parseFloat(data.price).toFixed(2) : '';
                            return `<div class="flex items-center gap-3 p-2">
                                ${image}
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 truncate">${escape(data.text)}</div>
                                    ${categoryName ? `<div class="text-xs text-gray-500">${escape(categoryName)}</div>` : ''}
                                </div>
                                ${price ? `<div class="text-green-600 font-semibold text-sm">${price}</div>` : ''}
                            </div>`;
                        },
                        item: function(data, escape) {
                            const image = data.imagePath ? `<img src="/storage/${data.imagePath}" alt="" class="w-6 h-6 object-cover rounded">` : '';
                            return `<div class="flex items-center gap-2">${image}<span>${escape(data.text)}</span></div>`;
                        }
                    },
                    onChange: function(value) {
                        const originalSelect = document.querySelector(`select[name="items[${index}][item_id]"]`);
                        if (originalSelect) {
                            setActive(originalSelect);
                        }
                    }
                });
                
                selectEl.dataset.tomSelectInit = 'true';
            }

            function removePOItem(button) {
                button.closest('.border').remove();
            }

            function toggleItemType(index, type) {
                const existingDiv = document.getElementById(`existing_item_${index}`);
                const newDiv = document.getElementById(`new_item_${index}`);
                const itemSelect = document.getElementById(`item_select_${index}`);
                const itemNameInput = document.querySelector(`input[name="items[${index}][new_item_name]"]`);

                if (type === 'existing') {
                    existingDiv.classList.remove('hidden');
                    newDiv.classList.add('hidden');
                    if (itemSelect) itemSelect.required = true;
                    itemNameInput.required = false;
                    itemNameInput.value = '';
                } else {
                    existingDiv.classList.add('hidden');
                    newDiv.classList.remove('hidden');
                    if (itemSelect) {
                        itemSelect.required = false;
                        if (itemSelect.tomselect) {
                            itemSelect.tomselect.clear();
                        } else {
                            itemSelect.value = '';
                        }
                    }
                    itemNameInput.required = true;
                }
            }

            function setActive(select) {
                const originalSelect = select.tagName === 'SELECT' ? select : document.querySelector(`select[name="${select.name}"]`);
                const option = originalSelect.options[originalSelect.selectedIndex];
                if (!option || !option.dataset.item) return;

                const index = originalSelect.name.match(/\d+/)[0];

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
                        Notification.success(isEdit ? '{{ __('messages.po_updated_success') }}' : '{{ __('messages.po_created_success') }}');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error(isEdit ? '{{ __('messages.error_updating_po') }}' : '{{ __('messages.error_creating_po') }}');
                    });
            }

            function viewPODetails(id) {
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsContent').innerHTML = '<p class="text-gray-500">{{ __('messages.loading') }}</p>';

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

                        const statusTranslations = {
                            pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
                            initial_approved: '{{ __('messages.initial_approved') }}',
                            pending_final_approval: '{{ __('messages.pending_final_approval') }}',
                            final_approved: '{{ __('messages.final_approved') }}',
                            rejected: '{{ __('messages.rejected') }}',
                            ordered: '{{ __('messages.ordered') }}'
                        };

                        const html = `
                <div class="border-b pb-4 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.po_id') }}</p>
                            <p class="font-semibold">#${po.id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.status') }}</p>
                            <span class="px-2 py-1 text-xs rounded ${statusColors[po.status]}">${statusTranslations[po.status] || po.status}</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.supplier') }}</p>
                            <p class="font-semibold">${po.supplier || '{{ __('messages.pending_selection') }}'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.date') }}</p>
                            <p class="font-semibold">${new Date(po.date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.total_amount') }}</p>
                            <p class="font-semibold">{{ __('messages.currency') }} ${parseFloat(po.total_amount).toFixed(2)}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">{{ __('messages.items') }}</h4>
                    ${poItems.length === 0 ? '<p class="text-gray-500">{{ __('messages.no_data_found') }}</p>' : `
                                                                                                        <table class="min-w-full mb-6">
                                                                                                            <thead class="bg-gray-50">
                                                                                                                <tr>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.image') }}</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.item') }}</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.quantity') }}</th>
                                                                                                                </tr>
                                                                                                            </thead>
                                                                                                            <tbody class="divide-y">
                                                                                                                ${poItems.map(item => `
                                    <tr>
                                        <td class="px-4 py-2">
                                            ${item.item?.image_path ? 
                                                `<img src="/storage/${item.item.image_path}" class="w-16 h-16 object-cover rounded">` : 
                                                '<div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'
                                            }
                                        </td>
                                        <td class="px-4 py-2">
                                            ${item.item?.designation || item.new_item_name || '{{ __('messages.unknown_item') }}'}
                                            ${item.new_item_name && !item.item ? '<span class="ml-2 text-xs text-gray-500">{{ __('messages.new_item_label') }}</span>' : ''}
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
                                                                                                        <h4 class="font-semibold mb-3">{{ __('messages.supplier_proposals') }}</h4>
                                                                                                        <div class="grid gap-4" id="finalSelectionForm">
                                                                                                            ${po.proposals.map(prop => `
                                <label class="border p-4 rounded-lg flex justify-between items-center cursor-pointer hover:bg-gray-50 ${prop.is_selected ? 'bg-green-50 border-green-200' : 'bg-white'}">
                                    <div class="flex items-center gap-4">
                                        ${isHRManager && po.status === 'pending_final_approval' ? `
                                                                                                            <input type="radio" name="selected_proposal" value="${prop.id}" class="w-5 h-5 text-blue-600">
                                                                                                        ` : ''}
                                        <div>
                                            <h5 class="font-bold">${prop.supplier_name}</h5>
                                            <p class="text-sm text-gray-600">{{ __('messages.unit_price') }}: <span class="font-semibold">{{ __('messages.currency') }} ${parseFloat(prop.price).toFixed(2)}</span></p>
                                            <p class="text-sm text-gray-600">{{ __('messages.quality_rating') }}: ${prop.quality_rating || 'N/A'}/10</p>
                                            ${prop.notes ? `<p class="text-xs text-gray-500 mt-1">${prop.notes}</p>` : ''}
                                        </div>
                                    </div>
                                    <div>
                                        ${prop.is_selected ? `<span class="px-3 py-1 bg-green-100 text-green-800 rounded font-semibold text-sm">{{ __('messages.selected_final') }}</span>` : ''}
                                    </div>
                                </label>
                            `).join('')}
                                                                                                        </div>
                                                                                                        ${isHRManager && po.status === 'pending_final_approval' ? `
                                            <div class="mt-6 pt-4 border-t flex justify-end">
                                                <button onclick="submitFinalSelection(${po.id})" class="px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700">{{ __('messages.accept') }}</button>
                                            </div>
                                        ` : ''}
                                                                                                    </div>
                                                                                                ` : ''}

                <!-- Add Proposals Form for Stock Manager -->
                ${isStockManager && po.status === 'initial_approved' ? `
                                                                                                    <div class="mt-6 pt-4 border-t">
                                                                                                        <h4 class="font-semibold mb-3 text-orange-600">{{ __('messages.add_supplier_proposals') }}</h4>
                                                                                                        <form id="addProposalsForm" onsubmit="submitProposals(event, ${po.id})">
                                                                                                            <div id="proposalsList" class="space-y-4 mb-4">
                                                                                                                <!-- First proposal always visible -->
                                                                                                                <div class="border p-4 rounded bg-orange-50 proposal-entry">
                                                                                                                    <div class="grid grid-cols-2 gap-4">
                                                                                                                        <div><label class="block text-xs font-semibold">{{ __('messages.supplier_name') }}</label><input type="text" name="supplier_name[]" required class="w-full px-2 py-1 border rounded text-sm"></div>
                                                                                                                        <div><label class="block text-xs font-semibold">{{ __('messages.unit_price') }}</label><input type="number" name="price[]" step="0.01" required class="w-full px-2 py-1 border rounded text-sm"></div>
                                                                                                                        <div>
                                                                                                                            <label class="block text-xs font-semibold">{{ __('messages.quality_rating') }}</label>
                                                                                                                            <select name="quality_rating[]" required class="w-full px-2 py-1 border rounded text-sm">
                                                                                                                                <option value="">{{ __('messages.select_1_10') }}</option>
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
                                                                                                                        <div><label class="block text-xs font-semibold">{{ __('messages.notes') }}</label><input type="text" name="notes[]" class="w-full px-2 py-1 border rounded text-sm"></div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="mt-6 pt-4 border-t flex justify-between items-center">
                                                                                                                <button type="button" onclick="addProposalField()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 font-semibold text-sm">{{ __('messages.add_supplier') }}</button>
                                                                                                                <button type="submit" class="z-10 px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700 w-32">{{ __('messages.accept') }}</button>
                                                                                                            </div>
                                                                                                        </form>
                                                                                                    </div>
                                                                                                ` : ''}

                <!-- Action Buttons -->
                ${isHRManager && po.status === 'pending_initial_approval' ? `
                                                                                                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                                                                                                        <button onclick="approveInitial(${po.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.approve_initial') }}</button>
                                                                                                        <button onclick="rejectInitial(${po.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('messages.reject') }}</button>
                                                                                                    </div>
                                                                                                ` : ''}
            `;
                        document.getElementById('detailsContent').innerHTML = html;
                    })
                    .catch(() => {
                        document.getElementById('detailsContent').innerHTML =
                            '<p class="text-red-500">{{ __('messages.error_loading_details') }}</p>';
                    });
            }

            function closeDetailsModal() {
                document.getElementById('detailsModal').classList.add('hidden');
            }

            function addProposalField() {
                const list = document.getElementById('proposalsList');
                const html = `
                    <div class="border p-4 rounded bg-orange-50 mt-4 proposal-entry">
                        <div class="flex justify-end mb-2"><button type="button" onclick="this.closest('.proposal-entry').remove()" class="text-xs text-red-600">{{ __('messages.remove') }}</button></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold">{{ __('messages.supplier_name') }}</label><input type="text" name="supplier_name[]" required class="w-full px-2 py-1 border rounded text-sm"></div>
                            <div><label class="block text-xs font-semibold">{{ __('messages.unit_price') }}</label><input type="number" name="price[]" step="0.01" required class="w-full px-2 py-1 border rounded text-sm"></div>
                            <div>
                                <label class="block text-xs font-semibold">{{ __('messages.quality_rating') }}</label>
                                <select name="quality_rating[]" required class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">{{ __('messages.select_1_10') }}</option>
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
                            <div><label class="block text-xs font-semibold">{{ __('messages.notes') }}</label><input type="text" name="notes[]" class="w-full px-2 py-1 border rounded text-sm"></div>
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
                        Notification.success('{{ __('messages.proposals_added') }}');
                    })
                    .catch(err => Notification.error('{{ __('messages.error_submitting_proposals') }}'));
            }

            function submitFinalSelection(poId) {
                const selectedRadio = document.querySelector('input[name="selected_proposal"]:checked');

                if (!selectedRadio) {
                    Notification.error('{{ __('messages.select_supplier_proposal') }}');
                    return;
                }

                if (confirm('{{ __('messages.confirm_select_supplier') }}')) {
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
                            Notification.success('{{ __('messages.supplier_selected') }}');
                        })
                        .catch(err => Notification.error('{{ __('messages.error_selecting_supplier') }}'));
                }
            }

            function approveInitial(id) {
                updateInitialApproval(id, 'approve');
            }

            function rejectInitial(id) {
                if (confirm('{{ __('messages.confirm_reject_po') }}')) {
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
                        Notification.success(action === 'approve' ? '{{ __('messages.po_approved') }}' : '{{ __('messages.po_rejected') }}');
                    })
                    .catch(err => Notification.error('{{ __('messages.error_updating_status') }}'));
            }
        </script>
    @endpush
@endsection
