
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
            <button dusk="create-po-btn" onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
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
                            <input type="date" id="poDate" name="date" required dusk="po-date-input"
                                value="{{ date('Y-m-d') }}"
                                class="w-full px-3 py-2 border rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('messages.items') }} *</label>
                        <div id="poItemsList" class="space-y-4"></div>
                        <button type="button" dusk="add-item-btn" onclick="addPOItem()"
                            class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">+ {{ __('messages.add_item') }}</button>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                    <button type="submit" id="submitBtn" dusk="submit-po-btn"
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
                loadSuppliers();
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
                
                // Filter out child POs - only show parent POs
                const parentPOs = filteredPOs.filter(po => !po.parent_id);
                const pagePOs = parentPOs.slice(start, end);

                const statusColors = {
                    pending_initial_approval: 'bg-yellow-100 text-yellow-800',
                    initial_approved: 'bg-blue-100 text-blue-800',
                    pending_final_approval: 'bg-orange-100 text-orange-800',
                    final_approved: 'bg-green-100 text-green-800',
                    rejected: 'bg-red-100 text-red-800',
                    ordered: 'bg-purple-100 text-purple-800',
                    split: 'bg-indigo-100 text-indigo-800'
                };

                const statusTranslations = {
                    pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
                    initial_approved: '{{ __('messages.initial_approved') }}',
                    pending_final_approval: '{{ __('messages.pending_final_approval') }}',
                    final_approved: '{{ __('messages.final_approved') }}',
                    rejected: '{{ __('messages.rejected') }}',
                    ordered: '{{ __('messages.ordered') }}',
                    split: '{{ __('messages.split') }}'
                };

                const tbody = document.getElementById('poBody');
                if (pagePOs.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.no_purchase_orders_found') }}</td></tr>';
                } else {
                    tbody.innerHTML = pagePOs.map(po => {
                        const isSplit = po.status === 'split';
                        const childCount = po.children ? po.children.length : 0;
                        const poItems = po.purchase_order_items || [];
                        const firstItemImage = poItems.find(item => item.item?.image_path)?.item?.image_path;
                        const totalQty = poItems.reduce((sum, item) => sum + parseFloat(item.init_quantity || 0), 0);
                        const totalOfChildren = (po.children || []).reduce((sum, c) => sum + parseFloat(c.total_amount || 0), 0);
                        
                        const parentRow = `
            <tr class="hover:bg-gray-50 bg-gray-50" data-po-id="${po.id}">
                <td class="px-6 py-4">
                    ${isSplit ? `<button onclick="toggleChildren(${po.id})" class="text-blue-600 font-bold text-lg">▶</button>` : firstItemImage ? `<img src="/storage/${firstItemImage}" class="w-12 h-12 object-cover rounded cursor-pointer" onclick="viewPODetails(${po.id})">` : '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'}
                </td>
                <td class="px-6 py-4 font-semibold">${isSplit ? '-' : '#' + po.id}</td>
                <td class="px-6 py-4">${isSplit ? `<span class="text-blue-600 font-semibold whitespace-nowrap">${childCount} {{ __('messages.sub_orders') }}</span>` : (po.supplier || '-')}</td>
                <td class="px-6 py-4"><button onclick="viewPODetails(${po.id})" class="text-green-600 hover:underline">${isSplit ? '-' : poItems.length + ' {{ __('messages.items') }} (' + totalQty.toFixed(2) + ' {{ __('messages.unit') }})'}</button></td>
                <td class="px-6 py-4 font-semibold text-lg">{{ __('messages.currency') }} ${isSplit ? totalOfChildren.toFixed(2) : parseFloat(po.total_amount).toFixed(2)}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded ${statusColors[po.status]} whitespace-nowrap">${isSplit ? childCount + ' {{ __('messages.sub_orders') }}' : (statusTranslations[po.status] || po.status)}</span></td>
                <td class="px-6 py-4">${new Date(po.date).toLocaleDateString()}</td>
                ${(isHRManager || isStockManager) ? `
                                                                                                               <td class="px-6 py-4">
                                                                                                                   ${isHRManager && po.status === 'pending_initial_approval' ? `
                              <button dusk="initial-approve-btn-${po.id}" onclick="approveInitial(${po.id})" class="text-green-600 hover:text-green-800 mr-2">{{ __('messages.approve_initial') }}</button>
                              <button dusk="initial-reject-btn-${po.id}" onclick="rejectInitial(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.reject') }}</button>
                          ` : isHRManager && po.status === 'pending_final_approval' ? `
                              <button dusk="select-supplier-btn-${po.id}" onclick="viewPODetails(${po.id})" class="text-blue-600 hover:text-blue-800">{{ __('messages.select_final') }}</button>
                          ` : isStockManager && po.status === 'pending_initial_approval' ? `
                              <button onclick="editPO(${po.id})" class="text-blue-600 hover:text-blue-800 mr-2">{{ __('messages.edit') }}</button>
                              <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                          ` : isStockManager && po.status === 'initial_approved' ? `
                              <button dusk="add-proposals-btn-${po.id}" onclick="viewPODetails(${po.id})" class="text-orange-600 hover:text-orange-800">{{ __('messages.add_proposals') }}</button>
                          ` : isStockManager && po.status === 'final_approved' ? `
                              <button dusk="mark-ordered-btn-${po.id}" onclick="markAsOrdered(${po.id})" class="text-purple-600 hover:text-purple-800">{{ __('messages.mark_ordered') }}</button>
                          ` : isStockManager && isSplit ? `
                              <button onclick="viewPODetails(${po.id})" class="text-blue-600 hover:text-blue-800">{{ __('messages.view') }}</button>
                          ` : `
                              <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                          `}
                                                                                                               </td>
                                                                                                          ` : ''}
            </tr>`;

                        let childRows = '';
                        if (isSplit && po.children) {
                            po.children.forEach(child => {
                                const childItems = child.purchase_order_items || [];
                                const childTotalQty = childItems.reduce((sum, item) => sum + parseFloat(item.init_quantity || 0), 0);
                                const childFirstImage = childItems.find(item => item.item?.image_path)?.item?.image_path;
                                childRows += `
            <tr id="child-${po.id}-${child.id}" class="hidden bg-white">
                <td class="px-6 py-3 pl-12">${childFirstImage ? `<img src="/storage/${childFirstImage}" class="w-10 h-10 object-cover rounded">` : '<div class="w-10 h-10 bg-gray-200 rounded"></div>'}</td>
                <td class="px-6 py-3 pl-8">#${child.id}</td>
                <td class="px-6 py-3 text-blue-600 font-medium">${child.supplier?.name || '-'}</td>
                <td class="px-6 py-3">${childItems.length} {{ __('messages.items') }}</td>
                <td class="px-6 py-3">{{ __('messages.currency') }} ${parseFloat(child.total_amount || 0).toFixed(2)}</td>
                <td class="px-6 py-3"><span class="px-2 py-1 text-xs rounded ${statusColors[child.status]}">${statusTranslations[child.status] || child.status}</span></td>
                <td class="px-6 py-3">${new Date(child.date).toLocaleDateString()}</td>
                <td class="px-6 py-3">${isStockManager && child.status === 'pending_initial_approval' ? `<button onclick="viewPODetails(${child.id})" class="text-green-600 hover:underline text-sm">{{ __('messages.view') }}</button>` : ''}</td>
            </tr>`;
                            });
                        }

                        return parentRow + childRows;
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

            function toggleChildren(poId) {
                const childRows = document.querySelectorAll(`[id^="child-${poId}-"]`);
                const toggleBtn = document.querySelector(`button[onclick="toggleChildren(${poId})"]`);
                childRows.forEach(row => row.classList.toggle('hidden'));
                if (toggleBtn) toggleBtn.textContent = toggleBtn.textContent.includes('▶') ? '▼' : '▶';
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

                            document.querySelector(`input[name="items[${index}][quantity]"]`).value = item.init_quantity;
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
                    <input type="number" name="items[${currentIndex}][quantity]" required min="0.01" step="0.01" class="w-full px-3 py-2 border rounded text-sm" dusk="quantity-input-${currentIndex}">
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
                        console.log('PO Data:', po);
                        console.log('Propositions:', po.propositions);
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
                                        <td class="px-4 py-2">${parseFloat(item.init_quantity).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                                                                                                            </tbody>
                                                                                                        </table>
                                                                                                    `}
                </div>

                <!-- Add Proposals Form for Stock Manager (Grouped Structure) -->
                ${isStockManager && po.status === 'initial_approved' ? `
                    <div class="mt-6 pt-4 border-t">
                        <h4 class="font-semibold mb-3 text-orange-600">{{ __('messages.add_supplier_proposals') }}</h4>
                        <p class="text-sm text-gray-600 mb-4">{{ __('messages.create_proposal_groups') }}</p>
                        
                        <form id="addProposalsForm" onsubmit="submitProposals(event, ${po.id})">
                            <div id="proposalGroupsList" class="space-y-6">
                                <!-- Dynamic proposal groups will be added here -->
                            </div>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <button type="button" onclick="addProposalGroup()" class="px-4 py-2 bg-orange-100 text-orange-800 rounded hover:bg-orange-200 text-sm font-semibold">+ {{ __('messages.add_proposal_group') }}</button>
                                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700">{{ __('messages.submit_proposals') }}</button>
                            </div>
                        </form>
                    </div>
                ` : ''}

                <!-- Proposals for HR Manager to Select -->
                ${isHRManager && po.status === 'pending_final_approval' ? `
                    <div class="mt-6 pt-4 border-t">
                        <h4 class="font-semibold mb-3 text-blue-600">{{ __('messages.select_proposal_option') }}</h4>
                        <p class="text-sm text-gray-600 mb-4">{{ __('messages.select_one_group_per_item') }}</p>
                        
                        <div id="hrPropositionGroups" class="space-y-4">
                            ${renderHRProposalGroups(po)}
                        </div>
                        
                        <div class="mt-6 flex gap-3 justify-end">
                            <button onclick="rejectAllProposals(${po.id})" class="px-6 py-2 bg-red-600 text-white rounded font-bold hover:bg-red-700">{{ __('messages.reject_all') }}</button>
                            <button onclick="approveSelectedProposals(${po.id})" class="px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700">{{ __('messages.approve_selected') }}</button>
                        </div>
                    </div>
                ` : ''}

                <!-- Action Buttons -->
                ${isHRManager && po.status === 'pending_initial_approval' ? `
                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                        <button onclick="approveInitial(${po.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.approve_initial') }}</button>
                        <button onclick="rejectInitial(${po.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('messages.reject') }}</button>
                    </div>
                ` : ''}
                ${isStockManager && po.status === 'final_approved' ? `
                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                        <button onclick="markAsOrdered(${po.id})" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">{{ __('messages.mark_ordered') }}</button>
                    </div>
                ` : ''}
            `;
                        document.getElementById('detailsContent').innerHTML = html;
                        
                        currentPOItems = po.purchase_order_items || [];
                        existingProposals = po.propositions || [];
                        existingGroups = po.proposition_groups || [];
                        if (allSuppliers.length === 0) {
                            loadSuppliers();
                        }
                        if (po.status === 'initial_approved') {
                            setTimeout(() => {
                                const list = document.getElementById('proposalGroupsList');
                                if (list && list.children.length === 0) {
                                    addProposalGroup();
                                }
                            }, 100);
                        }
                    })
                    .catch(() => {
                        document.getElementById('detailsContent').innerHTML =
                            '<p class="text-red-500">{{ __('messages.error_loading_details') }}</p>';
                    });
            }

            function closeDetailsModal() {
                document.getElementById('detailsModal').classList.add('hidden');
                currentPOItems = [];
                existingProposals = [];
                existingGroups = [];
            }

            let allSuppliers = [];
            let supplierItemsMap = {};
            let currentPOItems = [];
            let proposalCounter = 0;
            let existingProposals = [];
            let existingGroups = [];
            let groupCounter = 0;

            function loadSuppliers() {
                Promise.all([
                    fetch('/api/suppliers', { headers }).then(res => res.json()),
                    fetch('/api/suppliers/all-with-items', { headers }).then(res => res.json())
                ]).then(([suppliers, suppliersWithItems]) => {
                    allSuppliers = suppliers;
                    suppliersWithItems.forEach(supplier => {
                        supplierItemsMap[supplier.id] = supplier;
                    });
                }).catch(err => console.error('Error loading suppliers:', err));
            }

            function getSuppliersForItem(itemId) {
                const availableSuppliers = [];
                const itemIdNum = parseInt(itemId);
                
                allSuppliers.forEach(supplier => {
                    const supplierData = supplierItemsMap[supplier.id];
                    if (supplierData && supplierData.items) {
                        const hasItem = supplierData.items.some(si => parseInt(si.id) === itemIdNum);
                        if (hasItem) {
                            const item = supplierData.items.find(si => parseInt(si.id) === itemIdNum);
                            availableSuppliers.push({
                                id: supplier.id,
                                name: supplier.name,
                                unit_price: item ? item.unit_price : null
                            });
                        }
                    }
                });
                return availableSuppliers;
            }

            function getRemainingQty(itemId) {
                const poItem = currentPOItems.find(pi => pi.item_id === itemId);
                const totalNeeded = poItem ? parseFloat(poItem.init_quantity) : 0;
                let alreadyProposed = 0;
                
                document.querySelectorAll('.proposal-entry').forEach(entry => {
                    const entryItemId = entry.querySelector('select[name$="[item_id]"]')?.value;
                    const entryQty = parseFloat(entry.querySelector('input[name$="[quantity]"]')?.value) || 0;
                    if (parseInt(entryItemId) === itemId) {
                        alreadyProposed += entryQty;
                    }
                });
                
                return Math.max(0, totalNeeded - alreadyProposed);
            }

            function updateQuantityValidation(counter) {
                const itemSelect = document.querySelector(`select[name="proposals[${counter}][item_id]"]`);
                const qtyInput = document.querySelector(`input[name="proposals[${counter}][quantity]"]`);
                
                if (!itemSelect || !qtyInput) return;
                
                const itemId = parseInt(itemSelect.value);
                if (!itemId) return;
                
                const poItem = currentPOItems.find(pi => pi.item_id === itemId);
                const totalNeeded = poItem ? parseFloat(poItem.init_quantity) : 0;
                
                const infoDiv = document.getElementById(`qty-info-${counter}`);
                if (infoDiv) {
                    const remaining = getRemainingQty(itemId);
                    infoDiv.textContent = `{{ __('messages.total_needed') }}: ${totalNeeded.toFixed(2)}`;
                }
            }

            function addProposalGroup(itemId = '') {
                const list = document.getElementById('proposalGroupsList');
                if (!list) return;
                
                const groupId = crypto.randomUUID ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    const r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
                const groupOrder = groupCounter;
                
                const groupHtml = `
                    <div class="border-2 border-orange-300 rounded-lg p-4 bg-orange-50 proposal-group" id="${groupId}" data-group-id="${groupId}" data-group-order="${groupOrder}">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-orange-700">{{ __('messages.proposition') }} #${groupCounter + 1}</span>
                                <span class="text-xs px-2 py-1 rounded bg-orange-200 text-orange-800">{{ __('messages.competing_option') }}</span>
                            </div>
                            <button type="button" onclick="removeProposalGroup('${groupId}')" class="text-xs text-red-600 hover:text-red-800 font-medium">{{ __('messages.remove_group') }}</button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-xs font-medium mb-1">{{ __('messages.item') }} *</label>
                            <select name="groups[${groupCounter}][item_id]" required class="w-full px-2 py-1 border rounded text-sm" onchange="onGroupItemChange('${groupId}')">
                                <option value="">{{ __('messages.select_item') }}</option>
                                ${currentPOItems.map(item => {
                                    const remaining = getRemainingQtyForItem(item.item_id);
                                    const totalRequested = parseFloat(item.init_quantity);
                                    return `<option value="${item.item_id}" data-qty="${item.init_quantity}" data-remaining="${remaining}">
                                        ${item.item?.designation || item.new_item_name || 'Item #' + item.item_id} 
                                        ({{ __('messages.total') }}: ${totalRequested.toFixed(2)})
                                    </option>`;
                                }).join('')}
                            </select>
                            <p class="text-xs text-gray-500 mt-1">{{ __('messages.select_same_item_for_competitors') }}</p>
                        </div>
                        
                        <div class="border-t border-orange-200 pt-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-orange-600">{{ __('messages.suppliers_in_this_option') }}</span>
                                <button type="button" onclick="addSupplierToGroup('${groupId}')" class="text-xs px-2 py-1 bg-white border border-orange-300 rounded hover:bg-orange-100">+ {{ __('messages.add_supplier') }}</button>
                            </div>
                            <div class="group-suppliers space-y-2" id="${groupId}-suppliers">
                                <!-- Supplier entries will be added here -->
                            </div>
                        </div>
                        
                        <div class="mt-3 pt-2 border-t border-orange-200 flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('messages.total_qty') }}: <span class="font-semibold group-total-qty">0.00</span></span>
                            <span class="text-gray-600">{{ __('messages.est_total') }}: <span class="font-semibold text-green-600 group-total-price">{{ __('messages.currency') }} 0.00</span></span>
                        </div>
                    </div>
                `;
                
                list.insertAdjacentHTML('beforeend', groupHtml);
                
                if (itemId) {
                    const itemSelect = document.querySelector(`select[name="groups[${groupCounter}][item_id]"]`);
                    if (itemSelect) {
                        itemSelect.value = itemId;
                        onGroupItemChange(groupId);
                    }
                }
                
                addSupplierToGroup(groupId);
                groupCounter++;
            }

            function addSupplierToGroup(groupId) {
                const suppliersContainer = document.getElementById(groupId + '-suppliers');
                if (!suppliersContainer) return;
                
                const group = document.getElementById(groupId);
                const groupOrder = group.dataset.groupOrder;
                const itemSelect = group.querySelector('select[name^="groups["]');
                const itemId = itemSelect?.value;
                
                const supplierIndex = suppliersContainer.children.length;
                const supplierId = groupId + '-supplier-' + supplierIndex;
                
                const supplierHtml = `
                    <div class="bg-white rounded border p-3 supplier-entry" id="${supplierId}" data-group-id="${groupId}">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs text-gray-500">{{ __('messages.supplier') }} #${supplierIndex + 1}</span>
                            <button type="button" onclick="removeSupplierEntry('${supplierId}')" class="text-xs text-red-500 hover:text-red-700">×</button>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium mb-1">{{ __('messages.supplier') }} *</label>
                                <select name="groups[${groupOrder}][suppliers][${supplierIndex}][supplier_id]" required class="w-full px-2 py-1 border rounded text-sm supplier-select" onchange="updateSupplierInfo('${supplierId}')">
                                    <option value="">{{ __('messages.select_supplier') }}</option>
                                    ${itemId ? getSuppliersForItem(itemId).map(s => `
                                        <option value="${s.id}" data-price="${s.unit_price || ''}">${s.name}</option>
                                    `).join('') : '<option value="">{{ __('messages.select_item_first') }}</option>'}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">{{ __('messages.quantity') }} *</label>
                                <input type="number" name="groups[${groupOrder}][suppliers][${supplierIndex}][quantity]" required step="0.01" min="0.01" class="w-full px-2 py-1 border rounded text-sm" oninput="updateGroupTotals('${groupId}')">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">{{ __('messages.unit_price') }} *</label>
                                <input type="number" name="groups[${groupOrder}][suppliers][${supplierIndex}][unit_price]" required step="0.01" min="0" class="w-full px-2 py-1 border rounded text-sm" oninput="updateGroupTotals('${groupId}')">
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="block text-xs font-medium mb-1">{{ __('messages.notes') }}</label>
                            <input type="text" name="groups[${groupOrder}][suppliers][${supplierIndex}][notes]" class="w-full px-2 py-1 border rounded text-sm">
                        </div>
                    </div>
                `;
                
                suppliersContainer.insertAdjacentHTML('beforeend', supplierHtml);
            }

            function onGroupItemChange(groupId) {
                const group = document.getElementById(groupId);
                const itemSelect = group.querySelector('select[name^="groups["]');
                const itemId = itemSelect?.value;
                
                group.querySelectorAll('.supplier-select').forEach(select => {
                    const currentValue = select.value;
                    select.innerHTML = '<option value="">{{ __('messages.select_supplier') }}</option>';
                    
                    if (itemId) {
                        getSuppliersForItem(itemId).forEach(s => {
                            const option = document.createElement('option');
                            option.value = s.id;
                            option.textContent = s.name;
                            option.dataset.price = s.unit_price || '';
                            select.appendChild(option);
                        });
                    }
                });
            }

            function updateSupplierInfo(supplierId) {
                const entry = document.getElementById(supplierId);
                const supplierSelect = entry.querySelector('.supplier-select');
                const priceInput = entry.querySelector('input[name$="[unit_price]"]');
                
                if (supplierSelect && priceInput) {
                    const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
                    if (selectedOption && selectedOption.dataset.price) {
                        priceInput.value = selectedOption.dataset.price;
                    }
                }
            }

            function updateGroupTotals(groupId) {
                const group = document.getElementById(groupId);
                const itemSelect = group.querySelector('select[name^="groups["]');
                const itemId = parseInt(itemSelect?.value);
                const poItem = currentPOItems.find(pi => pi.item_id === itemId);
                const maxQty = poItem ? parseFloat(poItem.init_quantity) : Infinity;
                
                let totalQty = 0;
                let totalPrice = 0;
                
                group.querySelectorAll('.supplier-entry').forEach(entry => {
                    const qty = parseFloat(entry.querySelector('input[name$="[quantity]"]')?.value) || 0;
                    const price = parseFloat(entry.querySelector('input[name$="[unit_price]"]')?.value) || 0;
                    totalQty += qty;
                    totalPrice += qty * price;
                });
                
                group.querySelector('.group-total-qty').textContent = totalQty.toFixed(2);
                group.querySelector('.group-total-price').textContent = '{{ __('messages.currency') }} ' + totalPrice.toFixed(2);
                
                const totalDiv = group.querySelector('.group-total-qty');
                if (totalQty > maxQty) {
                    totalDiv.classList.add('text-red-600', 'font-bold');
                    totalDiv.title = '{{ __('messages.exceeds_requested') }} ' + maxQty.toFixed(2);
                } else {
                    totalDiv.classList.remove('text-red-600', 'font-bold');
                    totalDiv.title = '';
                }
            }

            function removeSupplierEntry(supplierId) {
                const entry = document.getElementById(supplierId);
                if (entry) {
                    const groupId = entry.dataset.groupId;
                    entry.remove();
                    updateGroupTotals(groupId);
                }
            }

            function removeProposalGroup(groupId) {
                const group = document.getElementById(groupId);
                if (group) {
                    group.remove();
                }
            }

            function getRemainingQtyForItem(itemId) {
                const poItem = currentPOItems.find(pi => pi.item_id === itemId);
                const totalNeeded = poItem ? parseFloat(poItem.init_quantity) : 0;
                let alreadyProposed = 0;
                
                existingProposals.forEach(prop => {
                    if (prop.item_id === itemId) {
                        alreadyProposed += parseFloat(prop.quantity) || 0;
                    }
                });
                
                document.querySelectorAll('.proposal-group').forEach(group => {
                    const itemSelect = group.querySelector('select[name^="groups["]');
                    if (itemSelect && parseInt(itemSelect.value) === itemId) {
                        group.querySelectorAll('.supplier-entry').forEach(entry => {
                            const entryQty = parseFloat(entry.querySelector('input[name$="[quantity]"]')?.value) || 0;
                            alreadyProposed += entryQty;
                        });
                    }
                });
                
                return Math.max(0, totalNeeded - alreadyProposed);
            }

            function validateProposalsTotal(proposals) {
                const itemQtys = {};
                
                proposals.forEach(p => {
                    if (!itemQtys[p.item_id]) {
                        itemQtys[p.item_id] = 0;
                    }
                    itemQtys[p.item_id] += p.quantity;
                });
                
                for (const itemId in itemQtys) {
                    const poItem = currentPOItems.find(pi => pi.item_id === parseInt(itemId));
                    if (poItem) {
                        const needed = parseFloat(poItem.init_quantity);
                        const proposed = itemQtys[itemId];
                        if (Math.abs(proposed - needed) > 0.01) {
                            const itemName = poItem.item?.designation || poItem.new_item_name || 'Item';
                            return {
                                valid: false,
                                message: `{{ __('messages.quantity_mismatch') }}: ${itemName}. {{ __('messages.needed') }}: ${needed.toFixed(2)}, {{ __('messages.proposed') }}: ${proposed.toFixed(2)}`
                            };
                        }
                    }
                }
                
                return { valid: true };
            }

            function renderHRProposalGroups(po) {
                console.log('renderHRProposalGroups called');
                console.log('po:', po);
                console.log('proposition_groups:', po.proposition_groups);
                
                if (!po.proposition_groups || po.proposition_groups.length === 0) {
                    return '<p class="text-gray-500">{{ __('messages.no_propositions_yet') }}</p>';
                }
                
                var html = '';
                po.proposition_groups.forEach(function(group, groupIdx) {
                    console.log('Rendering group:', groupIdx, group);
                    var propositions = group.propositions || [];
                    console.log('Propositions in group:', propositions);
                    var totalQty = 0;
                    var totalPrice = 0;
                    var itemName = (group.item && group.item.designation) ? group.item.designation : 'Item';
                    
                    propositions.forEach(function(p) {
                        totalQty += parseFloat(p.quantity) || 0;
                        totalPrice += (parseFloat(p.quantity) || 0) * (parseFloat(p.unit_price) || 0);
                    });
                    
                    var supplierHtml = '';
                    propositions.forEach(function(prop) {
                        console.log('Processing prop:', prop);
                        var supplierName = (prop.supplier && prop.supplier.name) ? prop.supplier.name : 'N/A';
                        console.log('Supplier name:', supplierName);
                        var qty = (parseFloat(prop.quantity) || 0).toFixed(2);
                        var price = (parseFloat(prop.unit_price) || 0).toFixed(2);
                        supplierHtml += '<div class="flex justify-between items-center p-2 rounded bg-white border">' +
                            '<div>' +
                                '<span class="font-medium">' + supplierName + '</span>' +
                                '<span class="text-sm text-gray-600 ml-2">Qté: ' + qty + '</span>' +
                            '</div>' +
                            '<span class="font-semibold text-green-600">DH ' + price + '</span>' +
                        '</div>';
                    });
                    
                    var borderClass = groupIdx === 0 ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white';
                    
                    html += '<div class="border-2 rounded-lg p-4 ' + borderClass + '">' +
                        '<div class="flex items-start gap-3">' +
                            '<input type="radio" name="selectedGroup" value="' + group.id + '" class="mt-1 w-5 h-5" onchange="selectProposalGroup(this)">' +
                            '<div class="flex-1">' +
                                '<div class="flex justify-between items-center mb-2">' +
                                    '<span class="text-xs font-semibold text-gray-500">Proposition #' + (groupIdx + 1) + '</span>' +
                                    '<span class="text-xs px-2 py-1 rounded bg-gray-100">' + itemName + '</span>' +
                                '</div>' +
                                '<div class="space-y-2">' + supplierHtml + '</div>' +
                                '<div class="mt-2 pt-2 border-t flex justify-between text-sm">' +
                                    '<span class="text-gray-500">Total Qté: <strong>' + totalQty.toFixed(2) + '</strong></span>' +
                                    '<span class="text-gray-500">Total: <strong class="text-green-600">DH ' + totalPrice.toFixed(2) + '</strong></span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                });
                
                console.log('Generated HTML length:', html.length);
                return html;
            }

            function updateProposalItemInfo(counter) {
                const itemSelect = document.querySelector(`select[name="proposals[${counter}][item_id]"]`);
                const supplierSelect = document.querySelector(`select[name="proposals[${counter}][supplier_id]"]`);
                const qtyInput = document.querySelector(`input[name="proposals[${counter}][quantity]"]`);
                const priceInput = document.querySelector(`input[name="proposals[${counter}][unit_price]"]`);
                
                if (!itemSelect || !supplierSelect) return;
                
                const selectedOption = itemSelect.options[itemSelect.selectedIndex];
                const itemId = parseInt(itemSelect.value);
                
                if (itemId && selectedOption.dataset.qty) {
                    const remaining = getRemainingQtyForItem(itemId);
                    if (remaining > 0) {
                        qtyInput.value = Math.min(parseFloat(selectedOption.dataset.qty), remaining);
                    } else {
                        qtyInput.value = parseFloat(selectedOption.dataset.qty);
                    }
                }
                
                supplierSelect.innerHTML = '<option value="">{{ __('messages.loading') }}</option>';
                
                const availableSuppliers = getSuppliersForItem(itemId);
                
                if (availableSuppliers.length === 0) {
                    supplierSelect.innerHTML = `<option value="">${itemId ? '{{ __('messages.no_suppliers_for_item') }}' : '{{ __('messages.select_item_first') }}'}</option>`;
                    return;
                }
                
                let optionsHtml = '<option value="">{{ __('messages.select_supplier') }}</option>';
                availableSuppliers.forEach(supplier => {
                    optionsHtml += `<option value="${supplier.id}" data-price="${supplier.unit_price || ''}">${supplier.name}</option>`;
                });
                supplierSelect.innerHTML = optionsHtml;
                
                supplierSelect.onchange = function() {
                    const selectedSupplier = supplierSelect.options[supplierSelect.selectedIndex];
                    if (selectedSupplier && selectedSupplier.dataset.price) {
                        priceInput.value = selectedSupplier.dataset.price;
                    }
                };
                
                updateQuantityValidation(counter);
            }

            function submitProposals(e, poId) {
                e.preventDefault();
                const form = e.target;
                
                const proposals = [];
                const groups = document.querySelectorAll('.proposal-group');
                
                if (groups.length === 0) {
                    Notification.error('{{ __('messages.add_at_least_one_proposal_group') }}');
                    return;
                }
                
                for (const group of groups) {
                    const itemSelect = group.querySelector('select[name^="groups["]');
                    const itemId = parseInt(itemSelect?.value);
                    const groupOrder = group.dataset.groupOrder;
                    
                    if (!itemId) {
                        Notification.error('{{ __('messages.select_item_for_each_group') }}');
                        return;
                    }
                    
                    const poItem = currentPOItems.find(pi => pi.item_id === itemId);
                    const maxQty = poItem ? parseFloat(poItem.init_quantity) : 0;
                    
                    let groupTotalQty = 0;
                    const supplierEntries = group.querySelectorAll('.supplier-entry');
                    
                    supplierEntries.forEach(entry => {
                        const supplierSelect = entry.querySelector('select[name$="[supplier_id]"]');
                        const qtyInput = entry.querySelector('input[name$="[quantity]"]');
                        const priceInput = entry.querySelector('input[name$="[unit_price]"]');
                        const notesInput = entry.querySelector('input[name$="[notes]"]');
                        
                        const supplierId = supplierSelect?.value;
                        const quantity = parseFloat(qtyInput?.value) || 0;
                        const unitPrice = priceInput?.value;
                        
                        if (supplierId && qtyInput?.value && unitPrice) {
                            groupTotalQty += quantity;
                            proposals.push({
                                item_id: itemId,
                                supplier_id: parseInt(supplierId),
                                quantity: quantity,
                                unit_price: parseFloat(unitPrice),
                                notes: notesInput?.value || null,
                                proposition_group_id: group.dataset.groupId,
                                proposition_order: parseInt(groupOrder)
                            });
                        }
                    });
                    
                    if (groupTotalQty > maxQty) {
                        const itemName = poItem?.item?.designation || poItem?.new_item_name || 'Item';
                        Notification.error(`{{ __('messages.group_exceeds_quantity') }}: ${itemName}. {{ __('messages.maximum') }}: ${maxQty.toFixed(2)}, {{ __('messages.proposed') }}: ${groupTotalQty.toFixed(2)}`);
                        return;
                    }
                }

                if (proposals.length === 0) {
                    Notification.error('{{ __('messages.add_at_least_one_supplier') }}');
                    return;
                }

                fetch(`/api/purchase-orders/${poId}/proposals`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ proposals })
                    })
                    .then(async res => {
                        if (!res.ok) {
                            const errorData = await res.json();
                            throw new Error(errorData.message || errorData.error || 'Error submitting proposals');
                        }
                        return res.json();
                    })
                    .then(data => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success('{{ __('messages.proposals_added') }}');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('{{ __('messages.error_submitting_proposals') }}: ' + err.message);
                    });
            }

            function selectProposalGroup(radio) {
                document.querySelectorAll('.proposal-group').forEach(group => {
                    const radioInput = group.querySelector('input[type="radio"]');
                    if (radioInput === radio) {
                        group.classList.add('border-blue-300', 'bg-blue-50');
                        group.classList.remove('border-gray-200', 'bg-white');
                    } else {
                        group.classList.remove('border-blue-300', 'bg-blue-50');
                        group.classList.add('border-gray-200', 'bg-white');
                    }
                });
            }

            function approveSelectedProposals(poId) {
                const selectedRadio = document.querySelector('input[name="selectedGroup"]:checked');
                if (!selectedRadio) {
                    Notification.error('{{ __('messages.select_one_proposal_option') }}');
                    return;
                }
                
                const selectedGroupId = selectedRadio.value;
                
                fetch(`/api/purchase-orders/${poId}/final-approval`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            action: 'approve',
                            selected_group_id: selectedGroupId 
                        })
                    })
                    .then(async res => {
                        if (!res.ok) {
                            const errorData = await res.json();
                            throw new Error(errorData.message || errorData.error || 'Error');
                        }
                        return res.json();
                    })
                    .then(data => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success('{{ __('messages.proposals_approved') }}');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('{{ __('messages.error_approving') }}: ' + err.message);
                    });
            }

            function rejectAllProposals(poId) {
                if (!confirm('{{ __('messages.confirm_reject_all_proposals') }}')) {
                    return;
                }
                
                fetch(`/api/purchase-orders/${poId}/proposals/reject`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(async res => {
                        if (!res.ok) {
                            const errorData = await res.json();
                            throw new Error(errorData.message || errorData.error || 'Error');
                        }
                        return res.json();
                    })
                    .then(data => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success('{{ __('messages.proposals_rejected') }}');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('{{ __('messages.error_rejecting') }}: ' + err.message);
                    });
            }

            function submitFinalSelection(poId) {
                const selectedRadio = document.querySelector('input[name="selectedGroup"]:checked');
                if (!selectedRadio) {
                    Notification.error('{{ __('messages.select_one_proposal_option') }}');
                    return;
                }
                approveSelectedProposals(poId);
            }

            function rejectProposals(poId) {
                rejectAllProposals(poId);
            }

            function approveInitial(id) {
                updateInitialApproval(id, 'approve');
            }

            function rejectInitial(id) {
                if (confirm('{{ __('messages.confirm_reject_po') }}')) {
                    updateInitialApproval(id, 'reject');
                }
            }

            function markAsOrdered(id) {
                fetch(`/api/purchase-orders/${id}/status`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            status: 'ordered'
                        })
                    })
                    .then(res => res.json())
                    .then(() => {
                        closeDetailsModal();
                        loadPOs();
                        Notification.success('{{ __('messages.po_marked_ordered') }}');
                    })
                    .catch(err => Notification.error('{{ __('messages.error_updating_status') }}'));
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
