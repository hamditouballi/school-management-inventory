
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
    width: 24px;
    height: 24px;
    border-radius: 4px;
    margin-right: 8px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
                    <option value="delivered">{{ __('messages.delivered') }}</option>
                    <option value="invoiced">{{ __('messages.invoiced') }}</option>
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

    <!-- Bon de Livraison Modal -->
    <div id="deliveryNotesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <p class="text-xl font-bold">{{ __('messages.delivery_notes') }}</p>
                <button onclick="closeDeliveryNotesModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="deliveryNotesList" class="space-y-4 mb-6">
                <p class="text-gray-500">{{ __('messages.loading') }}</p>
            </div>

            <div id="uploadDeliverySection" class="border-t pt-4">
                <h4 class="font-semibold mb-3">{{ __('messages.add_bon_de_livraison') }}</h4>
                <form id="uploadDeliveryNoteForm" onsubmit="saveDeliveryNoteDraft(event)">
                    <input type="hidden" id="deliveryPoId" value="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.delivery_date') }} *</label>
                            <input type="date" id="deliveryDate" required class="w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.upload_file') }}</label>
                            <input type="file" id="deliveryFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full px-3 py-2 border rounded" onchange="handleFileSelect(this)">
                            <p id="filePreviewName" class="text-sm text-gray-500 mt-1"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.notes') }}</label>
                            <textarea id="deliveryNotes" rows="2" class="w-full px-3 py-2 border rounded"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('messages.items') }}</label>
                            <div id="deliveryItemsList" class="space-y-3"></div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" onclick="closeDeliveryNotesModal()"
                            class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">{{ __('messages.save_draft') }}</button>
                    </div>
                </form>
            </div>
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

                    if (search) {
                        let supplierText = '';
                        if (po.status === 'split' && po.children) {
                            supplierText = po.children.map(c => c.supplier?.name).filter(Boolean).join(' ');
                        } else if (po.proposition_groups && po.proposition_groups.length > 0) {
                            supplierText = [...new Set(po.proposition_groups.flatMap(g => g.propositions?.map(p => p.supplier?.name)).flat().filter(Boolean))].join(' ');
                        } else {
                            supplierText = po.supplier?.name || po.supplier || '';
                        }
                        if (!supplierText.toLowerCase().includes(search)) {
                            match = false;
                        }
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
                    partially_delivered: 'bg-purple-100 text-purple-800',
                    delivered: 'bg-teal-100 text-teal-800',
                    invoiced: 'bg-cyan-100 text-cyan-800',
                    split: 'bg-indigo-100 text-indigo-800'
                };

                const statusTranslations = {
                    pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
                    initial_approved: '{{ __('messages.initial_approved') }}',
                    pending_final_approval: '{{ __('messages.pending_final_approval') }}',
                    final_approved: '{{ __('messages.final_approved') }}',
                    rejected: '{{ __('messages.rejected') }}',
                    partially_delivered: '{{ __('messages.partially_delivered') }}',
                    delivered: '{{ __('messages.delivered') }}',
                    invoiced: '{{ __('messages.invoiced') }}',
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
                        const itemsWithImages = poItems.filter(item => item.item?.image_path).map(item => item.item.image_path);
                        const extraCount = Math.max(0, poItems.length - 2);
                        const totalQty = poItems.reduce((sum, item) => sum + parseFloat(item.init_quantity || 0), 0);
                        const totalOfChildren = (po.children || []).reduce((sum, c) => sum + parseFloat(c.total_amount || 0), 0);
                        
                        const parentRow = `
            <tr class="hover:bg-gray-50 bg-gray-50" data-po-id="${po.id}">
                <td class="px-6 py-4">
                    ${isSplit ? `<button onclick="toggleChildren(${po.id})" class="text-blue-600 font-bold text-lg">▶</button>` : `
                    <div class="flex items-center cursor-pointer" onclick="viewPODetails(${po.id})">
                        ${itemsWithImages.length > 0 ? itemsWithImages.slice(0, 2).map((img, idx) => `<img src="/storage/${img}" class="w-10 h-10 object-cover rounded-full border-2 border-white ${idx > 0 ? '-ml-3' : ''}">`).join('') : `<div class="w-10 h-10 bg-gray-200 rounded-full border-2 border-white flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>`}
                        ${extraCount > 0 ? `<div class="w-10 h-10 rounded-full bg-gray-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold -ml-3">+${extraCount}</div>` : ''}
                    </div>`}
                </td>
                <td class="px-6 py-4 font-semibold">${isSplit ? '-' : '#' + po.id}</td>
                <td class="px-6 py-4">${isSplit ? `<span class="text-blue-600 font-semibold whitespace-nowrap">${childCount} {{ __('messages.sub_orders') }}</span>` : (() => {
    if (po.status === 'split' && po.children) {
        const supplierNames = po.children.map(c => c.supplier?.name).filter(Boolean);
        if (supplierNames.length <= 2) {
            return supplierNames.join(', ') || '-';
        }
        return supplierNames.slice(0, 2).join(', ') + ` +${supplierNames.length - 2}`;
    }
    if (po.proposition_groups && po.proposition_groups.length > 0) {
        const suppliers = [...new Set(po.proposition_groups.flatMap(g => g.propositions?.map(p => p.supplier?.name)).flat().filter(Boolean))];
        if (suppliers.length <= 2) {
            return suppliers.length ? suppliers.join(', ') : (po.supplier?.name || po.supplier || '-');
        }
        return suppliers.slice(0, 2).join(', ') + ` +${suppliers.length - 2}`;
    }
            return po.supplier?.name || po.supplier || '-';
})()}</td>
                <td class="px-6 py-4"><button onclick="viewPODetails(${po.id})" class="text-green-600 hover:underline">${isSplit ? '-' : (() => {
    const ordered = totalQty;
    if (po.status === 'partially_delivered' || po.status === 'delivered') {
        const delivered = poItems.reduce((sum, item) => sum + (parseFloat(item.final_quantity || 0) - parseFloat(item.init_quantity || 0)), 0);
        return poItems.length + ' {{ __('messages.items') }} ({{ __('messages.delivered') }}: ' + delivered.toFixed(2) + ' / {{ __('messages.ordered') }}: ' + ordered.toFixed(2) + ')';
    }
    return poItems.length + ' {{ __('messages.items') }} (' + ordered.toFixed(2) + ' {{ __('messages.unit') }})';
})()}</button></td>
                <td class="px-6 py-4 font-semibold text-lg">{{ __('messages.currency') }} ${isSplit ? totalOfChildren.toFixed(2) : parseFloat(po.total_amount).toFixed(2)}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded ${statusColors[po.status]} whitespace-nowrap">${isSplit ? childCount + ' {{ __('messages.sub_orders') }}' : (statusTranslations[po.status] || po.status)}</span></td>
                <td class="px-6 py-4">${new Date(po.date).toLocaleDateString()}</td>
                ${(isHRManager || isStockManager) ? `
                                                                                                               <td class="px-6 py-4">
                                                                                                                   ${isHRManager && po.status === 'pending_initial_approval' ? `
                              <button dusk="initial-approve-btn-${po.id}" onclick="approveInitial(${po.id})" class="text-green-600 hover:text-green-800 mr-2">{{ __('messages.approve_initial') }}</button>
                              <button dusk="initial-reject-btn-${po.id}" onclick="rejectInitial(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.reject') }}</button>
                          ` : isHRManager && po.status === 'pending_final_approval' ? `
                              <button dusk="select-supplier-btn-${po.id}" onclick="viewPODetails(${po.id}, 'selection')" class="text-blue-600 hover:text-blue-800">{{ __('messages.select_final') }}</button>
                           ` : isStockManager && po.status === 'pending_initial_approval' ? `
                              <button onclick="editPO(${po.id})" class="text-blue-600 hover:text-blue-800 mr-2">{{ __('messages.edit') }}</button>
                              <button onclick="deletePO(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.delete') }}</button>
                              <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                          ` : isStockManager && po.status === 'initial_approved' ? `
                              <button dusk="add-proposals-btn-${po.id}" onclick="viewPODetails(${po.id}, 'proposals')" class="text-orange-600 hover:text-orange-800 mr-2">{{ __('messages.add_proposals') }}</button>
                              <button onclick="deletePO(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.delete') }}</button>
                              <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                           ` : isStockManager && po.status === 'pending_final_approval' ? `
                              <button onclick="deletePO(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.delete') }}</button>
                              <button onclick="viewPODetails(${po.id})" class="text-blue-600 hover:text-blue-800">{{ __('messages.view') }}</button>
                           ` : isStockManager && po.status === 'rejected' ? `
                              <button onclick="deletePO(${po.id})" class="text-red-600 hover:text-red-800 mr-2">{{ __('messages.delete') }}</button>
                              <button onclick="viewPODetails(${po.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.view') }}</button>
                            ` : isStockManager && po.status === 'final_approved' ? `
                               <button onclick="viewPODetails(${po.id}, 'delivery')" class="text-purple-600 hover:text-purple-800">{{ __('messages.bon_de_livraison') }}</button>
                             ` : isStockManager && po.status === 'partially_delivered' ? `
                               <button onclick="viewPODetails(${po.id}, 'delivery')" class="text-purple-600 hover:text-purple-800 mr-2">{{ __('messages.view_delivery_notes') }}</button>
                               <button onclick="markDelivered(${po.id})" class="text-green-600 hover:text-green-800">{{ __('messages.mark_delivered') }}</button>
                             ` : isStockManager && po.status === 'delivered' ? `
                               <button onclick="viewPODetails(${po.id}, 'invoices')" class="text-green-600 hover:text-green-800 mr-2">{{ __('messages.view_invoices') }}</button>
                               <button onclick="viewPODetails(${po.id}, 'delivery')" class="text-purple-600 hover:text-purple-800">{{ __('messages.view_delivery_notes') }}</button>
                             ` : isStockManager && po.status === 'invoiced' ? `
                               <button onclick="viewPODetails(${po.id}, 'invoices')" class="text-green-600 hover:text-green-800">{{ __('messages.view_invoices') }}</button>
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

            function viewPODetails(id, defaultTab = 'details') {
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsContent').innerHTML = '<p class="text-gray-500 p-4">{{ __('messages.loading') }}</p>';

                fetch(`/api/purchase-orders/${id}`, {
                        headers
                    })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('HTTP ' + res.status);
                        }
                        return res.json();
                    })
                    .then(po => {
                        if (po.error) {
                            throw new Error(po.error);
                        }
                        console.log('PO Data:', po);
                        const poItems = po.purchase_order_items || [];
                        const statusColors = {
                            pending_hr: 'bg-yellow-100 text-yellow-800',
                            approved_hr: 'bg-green-100 text-green-800',
                            rejected_hr: 'bg-red-100 text-red-800',
                            partially_delivered: 'bg-purple-100 text-purple-800',
                            delivered: 'bg-teal-100 text-teal-800',
                            invoiced: 'bg-cyan-100 text-cyan-800'
                        };

                        const statusTranslations = {
                            pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
                            initial_approved: '{{ __('messages.initial_approved') }}',
                            pending_final_approval: '{{ __('messages.pending_final_approval') }}',
                            final_approved: '{{ __('messages.final_approved') }}',
                            rejected: '{{ __('messages.rejected') }}',
                            partially_delivered: '{{ __('messages.partially_delivered') }}',
                            delivered: '{{ __('messages.delivered') }}',
                            invoiced: '{{ __('messages.invoiced') }}'
                        };

                        const html = `
                <!-- Tab Navigation -->
                <div class="flex border-b mb-4 -mt-2">
                    <button onclick="switchPOTab('details')" class="po-tab-btn px-4 py-2 border-b-2 border-blue-500 text-blue-600 font-semibold text-sm" data-tab="details">{{ __('messages.details') }}</button>
                    <button onclick="switchPOTab('proposals')" class="po-tab-btn px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 text-sm" data-tab="proposals">{{ __('messages.supplier_proposals') }}</button>
                    <button onclick="switchPOTab('selection')" class="po-tab-btn px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 text-sm" data-tab="selection">{{ __('messages.hr_selection') }}</button>
                    ${['final_approved', 'partially_delivered', 'delivered', 'invoiced'].includes(po.status) ? `<button onclick="switchPOTab('delivery')" class="po-tab-btn px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 text-sm" data-tab="delivery">{{ __('messages.delivery_notes') }}</button>` : ''}
                    ${(po.status === 'delivered' || po.status === 'invoiced') ? `<button onclick="switchPOTab('invoices')" class="po-tab-btn px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 text-sm" data-tab="invoices">{{ __('messages.invoices') }}</button>` : ''}
                </div>

                <!-- Tab: Details -->
                <div id="po-tab-details" class="po-tab-content">
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
                            <p class="text-sm text-gray-500">{{ __('messages.suppliers') }}</p>
                            <p class="font-semibold">${(() => {
                                if (po.status === 'split' && po.children) {
                                    const supplierNames = po.children.map(c => c.supplier?.name).filter(Boolean);
                                    return supplierNames.length ? supplierNames.join(', ') : '{{ __('messages.pending_selection') }}';
                                }
                                if (po.proposition_groups && po.proposition_groups.length > 0) {
                                    const suppliers = [...new Set(po.proposition_groups.flatMap(g => g.propositions?.map(p => p.supplier?.name)).flat().filter(Boolean))];
                                    return suppliers.length ? suppliers.join(', ') : (po.supplier?.name || po.supplier || '{{ __('messages.pending_selection') }}');
                                }
                                return po.supplier?.name || po.supplier || '{{ __('messages.pending_selection') }}';
                            })()}</p>
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
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.supplier') }}</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.unit') }}</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.qty_ordered') }}</th>
                                                                                                                    ${(po.status === 'partially_delivered' || po.status === 'delivered') ? `
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.delivered') }}</th>
                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.remaining') }}</th>
                                                                                                                    ` : ''}
                                                                                                                </tr>
                                                                                                            </thead>
                                                                                                            <tbody class="divide-y">
                                                                                                                ${poItems.map(item => {
    const ordered = parseFloat(item.init_quantity || 0);
    const final = parseFloat(item.final_quantity || 0);
    const delivered = (po.status === 'partially_delivered' || po.status === 'delivered') ? (final - ordered) : 0;
    const remaining = delivered > 0 ? ordered - delivered : 0;
    const unit = item.item?.unit || '';
    const supplierName = item.proposition?.supplier?.name || '-';
    return `
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
                                        <td class="px-4 py-2">${supplierName}</td>
                                        <td class="px-4 py-2">${unit}</td>
                                        <td class="px-4 py-2">${ordered.toFixed(2)}</td>
                                        ${(po.status === 'partially_delivered' || po.status === 'delivered') ? `
                                        <td class="px-4 py-2">${delivered.toFixed(2)}</td>
                                        <td class="px-4 py-2">${remaining.toFixed(2)}</td>
                                        ` : ''}
                                    </tr>
                                `}).join('')}
                                                                                                            </tbody>
                                                                                                     </table>
                                                                                                     `}
                </div>
                </div>

                <!-- Tab: Supplier Proposals -->
                <div id="po-tab-proposals" class="po-tab-content hidden">
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
                ` : (po.proposition_groups && po.proposition_groups.length > 0) ? `
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-green-600">{{ __('messages.submitted_proposals') }}</h4>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">${po.proposition_groups.length} {{ __('messages.proposal_groups') }}</span>
                        </div>
                        ${(() => {
                            const selectedPropIds = (po.purchase_order_items || [])
                                .map(item => item.proposition_id)
                                .filter(Boolean);
                            return po.proposition_groups.map(group => {
                                const hasSelected = (group.propositions || []).some(prop => selectedPropIds.includes(prop.id));
                                return `
                                    <div class="border rounded-lg p-4 mb-3 ${hasSelected ? 'border-green-400 bg-green-50 border-l-4 border-l-green-500' : 'bg-gray-50'}">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-medium">{{ __('messages.item') }}: ${group.item?.designation || group.new_item_name || 'Item'}</span>
                                            ${hasSelected ? '<span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded font-medium">✓ {{ __('messages.selected') }}</span>' : ''}
                                        </div>
                                        <div class="space-y-2">
                                            ${(group.propositions || []).map(prop => `
                                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                                    <span class="text-sm">${prop.supplier?.name || 'N/A'}</span>
                                                    <span class="text-sm">${parseFloat(prop.quantity || 0).toFixed(2)} × {{ __('messages.currency') }} ${parseFloat(prop.unit_price || 0).toFixed(2)}</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        })()}
                    </div>
                ` : ''}
                </div>

                <!-- Tab: HR Selection -->
                <div id="po-tab-selection" class="po-tab-content hidden">
                <!-- Proposals for HR Manager to Select -->
                ${isHRManager && po.status === 'pending_final_approval' ? `
                    <div class="mt-6 pt-4 border-t">
                        <h4 class="font-semibold mb-3 text-blue-600">{{ __('messages.select_proposal_option') }}</h4>
                        <p class="text-sm text-gray-600 mb-4">{{ __('messages.select_one_group_per_item') }}</p>
                        
                        <div id="hrPropositionGroups" class="space-y-4">
                            ${renderHRProposalGroups(po)}
                        </div>
                        
                        <div class="mt-6 pt-4 border-t">
                            <div class="flex items-center justify-between mb-4">
                                <p id="hrSelectionStatus" class="text-sm text-gray-600">
                                    <span id="selectedCount">0</span> of <span id="totalItems">0</span> items selected
                                </p>
                                <div id="hrApprovalStatus" class="text-sm text-green-600 hidden">
                                    ✓ {{ __('messages.ready_to_approve') }}
                                </div>
                            </div>
                            <div class="flex gap-3 justify-end">
                                <button onclick="rejectAllProposals(${po.id})" class="px-6 py-2 bg-red-600 text-white rounded font-bold hover:bg-red-700">{{ __('messages.reject_all') }}</button>
                                <button id="approveSelectedBtn" onclick="approveSelectedProposals(${po.id})" class="px-6 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700 opacity-50 cursor-not-allowed" disabled>{{ __('messages.approve_selected') }}</button>
                            </div>
                        </div>
                    </div>
                ` : (po.status === 'final_approved' || po.status === 'delivered') ? `
                    <div class="pt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-green-600">{{ __('messages.selected_proposals') }}</h4>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">{{ __('messages.ready_for_procurement') }}</span>
                        </div>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.item') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.supplier') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.quantity') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.unit_price') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    ${poItems.map(item => {
                                        const supplier = item.proposition?.supplier?.name || '-';
                                        const qty = parseFloat(item.proposition?.quantity || item.init_quantity || 0);
                                        const price = parseFloat(item.proposition?.unit_price || item.unit_price || 0);
                                        const total = qty * price;
                                        return `
                                            <tr>
                                                <td class="px-4 py-2 text-sm">${item.item?.designation || item.new_item_name || 'Item'}</td>
                                                <td class="px-4 py-2 text-sm">${supplier}</td>
                                                <td class="px-4 py-2 text-sm">${qty.toFixed(2)}</td>
                                                <td class="px-4 py-2 text-sm">{{ __('messages.currency') }} ${price.toFixed(2)}</td>
                                                <td class="px-4 py-2 text-sm font-semibold">{{ __('messages.currency') }} ${total.toFixed(2)}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ` : isStockManager && po.status === 'initial_approved' ? `
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p>{{ __('messages.submit_proposals_first') }}</p>
                    </div>
                ` : `
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>{{ __('messages.waiting_for_hr_selection') }}</p>
                    </div>
                `}
                </div>

                <!-- Tab: Delivery Notes -->
                <div id="po-tab-delivery" class="po-tab-content hidden">
                    <p class="text-sm text-gray-500 mb-4">{{ __('messages.delivery_notes') }}</p>
                    <div id="deliveryNotesListInTab"></div>
                    <div id="uploadDeliverySectionInTab" class="border-t pt-4 mt-4">
                        <button onclick="openDeliveryNotesModal()" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm">
                            + {{ __('messages.upload_bon_de_livraison') }}
                        </button>
                    </div>
                </div>

                <!-- Tab: Invoices -->
                <div id="po-tab-invoices" class="po-tab-content hidden">
                    <p class="text-sm text-gray-500 mb-4">{{ __('messages.invoices') }}</p>
                    <div id="invoicesListInTab"></div>
                    <div id="uploadInvoiceSectionInTab" class="border-t pt-4 mt-4">
                        <button onclick="openInvoiceModal()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                            + {{ __('messages.upload_invoice') }}
                        </button>
                    </div>
                </div>

                <!-- Action Buttons (always visible at bottom) -->
                ${isHRManager && po.status === 'pending_initial_approval' ? `
                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                        <button onclick="approveInitial(${po.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.approve_initial') }}</button>
                        <button onclick="rejectInitial(${po.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('messages.reject') }}</button>
                    </div>
                ` : ''}
                ${isStockManager && po.status === 'partially_delivered' ? `
                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                        <button onclick="markDelivered(${po.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.mark_delivered') }}</button>
                    </div>
                ` : ''}
            `;
                        document.getElementById('detailsContent').innerHTML = html;
                        window.currentPOPoId = po.id;
                        
                        // Switch to default tab if not 'details'
                        if (defaultTab !== 'details') {
                            switchPOTab(defaultTab);
                        }
                        
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
                        if (po.status === 'pending_final_approval') {
                            setTimeout(() => {
                                const totalItemsEl = document.getElementById('totalItems');
                                if (totalItemsEl) {
                                    totalItemsEl.textContent = totalItems;
                                    updateSelectionProgress();
                                }
                            }, 100);
                        }
                    })
                    .catch(err => {
                        console.error('Error loading PO:', err);
                        document.getElementById('detailsContent').innerHTML =
                            '<p class="text-red-500 p-4">{{ __('messages.error_loading_details') }}: ' + err.message + '</p>';
                    });
            }

            let currentPOTab = 'details';

            window.currentPOPoId = null;

            function loadPODeliveryNotes(poId) {
                const container = document.getElementById('deliveryNotesListInTab');
                if (!container) return;
                
                container.innerHTML = '<p class="text-gray-500">{{ __('messages.loading') }}...</p>';
                
                fetch(`/api/purchase-orders/${poId}/bon-de-livraison`, { headers })
                    .then(res => res.json())
                    .then(notes => {
                        if (!notes || notes.length === 0) {
                            container.innerHTML = '<p class="text-gray-500">{{ __('messages.no_delivery_notes') }}</p>';
                        } else {
                            container.innerHTML = notes.map(note => {
                                const items = note.items || [];
                                const itemsHtml = items.map(item => `
                                    <div class="flex justify-between text-sm">
                                        <span>${item.purchase_order_item?.item?.designation || '-'}</span>
                                        <span>${parseFloat(item.quantity || 0).toFixed(2)} ${item.purchase_order_item?.item?.unit || ''}</span>
                                    </div>
                                `).join('');
                                const isImage = note.file_path && (note.file_path.endsWith('.jpg') || note.file_path.endsWith('.jpeg') || note.file_path.endsWith('.png') || note.file_path.endsWith('.gif') || note.file_path.endsWith('.webp'));
                                return `
                                    <div class="border rounded p-4 mb-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p class="font-semibold">{{ __('messages.delivery_date') }}: ${new Date(note.date).toLocaleDateString()}</p>
                                                <p class="text-sm text-gray-500">{{ __('messages.status') }}: <span class="text-green-600">{{ __('messages.confirmed') }}</span></p>
                                            </div>
                                            ${note.file_path ? (isImage ? `
                                                <img src="/storage/${note.file_path}" alt="Delivery document" class="h-20 w-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity border" onclick="openLightbox('/storage/${note.file_path}')">
                                            ` : `<a href="/storage/${note.file_path}" target="_blank" class="text-blue-600 hover:underline text-sm">{{ __('messages.view_file') }}</a>`) : ''}
                                        </div>
                                        <div class="mt-2">${itemsHtml}</div>
                                    </div>
                                `;
                            }).join('');
                        }
                    })
                    .catch(err => {
                        container.innerHTML = '<p class="text-red-500">{{ __('messages.error_loading') }}</p>';
                    });
            }

            function openDeliveryNotesModal() {
                const poId = window.currentPOPoId;
                if (!poId) return;
                
                document.getElementById('deliveryNotesModal').classList.remove('hidden');
                document.getElementById('deliveryPoId').value = poId;
                loadDeliveryNotes(poId);
                loadPOItemsForDelivery(poId);
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
            let selectedGroups = {};
            let totalItems = 0;

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
                selectedGroups = {};
                
                if (!po.proposition_groups || po.proposition_groups.length === 0) {
                    return '<p class="text-gray-500">{{ __('messages.no_propositions_yet') }}</p>';
                }
                
                var groupsByItem = {};
                po.proposition_groups.forEach(function(group) {
                    var itemId = group.item_id;
                    if (!groupsByItem[itemId]) {
                        groupsByItem[itemId] = {
                            item: group.item,
                            groups: []
                        };
                    }
                    groupsByItem[itemId].groups.push(group);
                });
                
                totalItems = Object.keys(groupsByItem).length;
                
                var html = '';
                var itemIndex = 0;
                
                Object.keys(groupsByItem).forEach(function(itemId) {
                    var itemData = groupsByItem[itemId];
                    var itemName = (itemData.item && itemData.item.designation) ? itemData.item.designation : 'Item';
                    var itemImage = itemData.item && itemData.item.image_path ? itemData.item.image_path : null;
                    
                    html += '<div class="border rounded-lg p-4 bg-white shadow-sm">';
                    
                    html += '<div class="flex items-center gap-3 mb-4 pb-3 border-b">';
                    if (itemImage) {
                        html += '<img src="/storage/' + itemImage + '" class="w-10 h-10 object-cover rounded">';
                    } else {
                        html += '<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-xs">N/A</div>';
                    }
                    html += '<span class="font-semibold flex-1">' + itemName + '</span>';
                    html += '<span class="text-xs px-2 py-1 rounded bg-orange-100 text-orange-800">' + itemData.groups.length + ' proposals</span>';
                    html += '<span id="item-selected-' + itemId + '" class="text-sm text-gray-400">☐</span>';
                    html += '</div>';
                    
                    html += '<div class="space-y-3">';
                    itemData.groups.forEach(function(group, groupIdx) {
                        var propositions = group.propositions || [];
                        var totalQty = 0;
                        var totalPrice = 0;
                        
                        propositions.forEach(function(p) {
                            totalQty += parseFloat(p.quantity) || 0;
                            totalPrice += (parseFloat(p.quantity) || 0) * (parseFloat(p.unit_price) || 0);
                        });
                        
                        var supplierHtml = '';
                        propositions.forEach(function(prop) {
                            var supplierName = (prop.supplier && prop.supplier.name) ? prop.supplier.name : 'N/A';
                            var qty = (parseFloat(prop.quantity) || 0).toFixed(2);
                            var price = (parseFloat(prop.unit_price) || 0).toFixed(2);
                            supplierHtml += '<div class="flex justify-between items-center p-2 rounded bg-gray-50 border">' +
                                '<div class="flex items-center gap-2">' +
                                    '<span class="font-medium text-sm">' + supplierName + '</span>' +
                                '</div>' +
                                '<div class="text-right">' +
                                    '<span class="text-xs text-gray-500">Qté: ' + qty + '</span>' +
                                    '<span class="ml-2 font-semibold text-green-600">DH ' + price + '</span>' +
                                '</div>' +
                            '</div>';
                        });
                        
                        var borderClass = groupIdx === 0 ? 'border-blue-300' : 'border-gray-200';
                        
                        html += '<div class="border-2 rounded-lg p-3 cursor-pointer hover:shadow-md transition-shadow ' + borderClass + ' bg-white" onclick="selectGroupForItem(this, \'' + group.id + '\', ' + itemId + ')" data-group-id="' + group.id + '" data-item-id="' + itemId + '">' +
                            '<div class="flex items-start gap-3">' +
                                '<input type="radio" name="item-' + itemId + '" value="' + group.id + '" class="mt-1 w-5 h-5">' +
                                '<div class="flex-1">' +
                                    '<div class="flex justify-between items-center mb-2">' +
                                        '<span class="text-xs font-semibold text-gray-500">Option ' + (groupIdx + 1) + '</span>' +
                                    '</div>' +
                                    '<div class="space-y-2 mb-3">' + supplierHtml + '</div>' +
                                    '<div class="flex justify-between text-sm border-t pt-2">' +
                                        '<span class="text-gray-500">Total: <strong>' + totalQty.toFixed(2) + '</strong></span>' +
                                        '<span class="text-gray-500">Est: <strong class="text-green-600">DH ' + totalPrice.toFixed(2) + '</strong></span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    });
                    
                    html += '</div></div>';
                    itemIndex++;
                });
                
                return html;
            }

            function selectGroupForItem(card, groupId, itemId) {
                var itemCards = document.querySelectorAll('[data-item-id="' + itemId + '"]');
                itemCards.forEach(function(c) {
                    c.classList.remove('border-blue-300', 'bg-blue-50');
                    c.classList.add('border-gray-200', 'bg-white');
                    var radio = c.querySelector('input[type="radio"]');
                    if (radio) radio.checked = false;
                });
                
                card.classList.add('border-blue-300', 'bg-blue-50');
                card.classList.remove('border-gray-200', 'bg-white');
                var radio = card.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                
                selectedGroups[itemId] = groupId;
                
                var itemSelectedEl = document.getElementById('item-selected-' + itemId);
                if (itemSelectedEl) {
                    itemSelectedEl.textContent = '✓';
                    itemSelectedEl.classList.remove('text-gray-400');
                    itemSelectedEl.classList.add('text-green-600');
                }
                
                updateSelectionProgress();
            }

            function updateSelectionProgress() {
                var selectedCount = Object.keys(selectedGroups).length;
                var selectedCountEl = document.getElementById('selectedCount');
                var approveBtn = document.getElementById('approveSelectedBtn');
                var statusEl = document.getElementById('hrApprovalStatus');
                
                if (selectedCountEl) selectedCountEl.textContent = selectedCount;
                
                if (approveBtn && statusEl) {
                    if (selectedCount === totalItems) {
                        approveBtn.disabled = false;
                        approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        statusEl.classList.remove('hidden');
                    } else {
                        approveBtn.disabled = true;
                        approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        statusEl.classList.add('hidden');
                    }
                }
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

            function selectProposalCard(card, groupId) {
                document.querySelectorAll('[data-group-id]').forEach(c => {
                    c.classList.remove('border-blue-300', 'bg-blue-50');
                    c.classList.add('border-gray-200', 'bg-white');
                    const radio = c.querySelector('input[type="radio"]');
                    if (radio) radio.checked = false;
                });
                
                card.classList.add('border-blue-300', 'bg-blue-50');
                card.classList.remove('border-gray-200', 'bg-white');
                const radio = card.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            }

            function selectProposalGroup(radio) {
                const card = radio.closest('[data-group-id]');
                if (card) {
                    selectProposalCard(card, radio.value);
                }
            }

            function approveSelectedProposals(poId) {
                if (Object.keys(selectedGroups).length !== totalItems) {
                    Notification.error('{{ __('messages.select_all_items') }}');
                    return;
                }
                
                const selectedGroupIds = Object.values(selectedGroups);
                
                fetch(`/api/purchase-orders/${poId}/final-approval`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            action: 'approve',
                            selected_group_ids: selectedGroupIds
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

            function markDelivered(id) {
                if (!confirm('{{ __('messages.confirm_mark_delivered_warning') }}')) {
                    return;
                }
                fetch(`/api/purchase-orders/${id}/mark-delivered`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to mark as delivered');
                    }
                    return res.json();
                })
                .then(() => {
                    loadPOs();
                    Notification.success('{{ __('messages.po_marked_delivered') }}');
                })
                .catch(err => Notification.error('{{ __('messages.error_updating_status') }}: ' + err.message));
            }

            function showDeliveryNotesModal(poId) {
                document.getElementById('deliveryNotesModal').classList.remove('hidden');
                document.getElementById('deliveryPoId').value = poId;
                loadDeliveryNotes(poId);
                loadPOItemsForDelivery(poId);
                
                const po = allPOs.find(p => p.id === poId);
                const uploadSection = document.getElementById('uploadDeliverySection');
                if (po && po.status === 'delivered') {
                    uploadSection.classList.add('hidden');
                } else {
                    uploadSection.classList.remove('hidden');
                }
            }

            function closeDeliveryNotesModal() {
                const poId = document.getElementById('deliveryPoId').value;
                const drafts = getDraftNotes(poId);
                
                if (drafts.length > 0) {
                    const count = drafts.length;
                    const message = count === 1 
                        ? '{{ __('messages.one_draft_pending') }}'
                        : '{{ __('messages.multiple_drafts_pending') }}'.replace(':count', count);
                    Notification.warning(message);
                    return;
                }
                
                document.getElementById('deliveryNotesModal').classList.add('hidden');
                document.getElementById('deliveryNotesList').innerHTML = '<p class="text-gray-500">{{ __('messages.loading') }}</p>';
                document.getElementById('deliveryItemsList').innerHTML = '';
                document.getElementById('uploadDeliveryNoteForm').reset();
                document.getElementById('filePreviewName').textContent = '';
                selectedFileData = null;
            }

            // LocalStorage helpers for draft delivery notes
            function getDraftNotesKey(poId) {
                return `bon_de_livraison_drafts_${poId}`;
            }

            function getDraftNotes(poId) {
                const data = localStorage.getItem(getDraftNotesKey(poId));
                return data ? JSON.parse(data) : [];
            }

            function saveDraftNotes(poId, notes) {
                localStorage.setItem(getDraftNotesKey(poId), JSON.stringify(notes));
            }

            function getDraftNote(poId, localId) {
                const drafts = getDraftNotes(poId);
                return drafts.find(d => d.localId === localId);
            }

            function addDraftNote(poId, note) {
                const notes = getDraftNotes(poId);
                note.localId = 'local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                notes.push(note);
                saveDraftNotes(poId, notes);
                renderDeliveryNotes(poId);
            }

            function updateDraftNote(poId, localId, updates) {
                const notes = getDraftNotes(poId);
                const index = notes.findIndex(d => d.localId === localId);
                if (index !== -1) {
                    notes[index] = { ...notes[index], ...updates };
                    saveDraftNotes(poId, notes);
                }
            }

            function deleteDraftNote(poId, localId) {
                const notes = getDraftNotes(poId).filter(d => d.localId !== localId);
                saveDraftNotes(poId, notes);
                renderDeliveryNotes(poId);
            }

            function dataURItoBlob(dataURI) {
                const byteString = atob(dataURI.split(',')[1]);
                const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
                const ab = new ArrayBuffer(byteString.length);
                const ia = new Uint8Array(ab);
                for (let i = 0; i < byteString.length; i++) {
                    ia[i] = byteString.charCodeAt(i);
                }
                return new Blob([ab], { type: mimeString });
            }

            function renderDeliveryNotes(poId) {
                const list = document.getElementById('deliveryNotesList');
                const drafts = getDraftNotes(poId);
                
                console.log('[renderDeliveryNotes] Starting render. Drafts:', drafts.length, 'confirmingDraftId:', window.confirmingDraftId);
                
                // Sync state: if confirmingDraftId exists but draft was deleted, clear it
                if (window.confirmingDraftId && !drafts.find(d => d.localId === window.confirmingDraftId)) {
                    console.log('[renderDeliveryNotes] Clearing confirmingDraftId - draft not found');
                    window.confirmingDraftId = null;
                }
                
                // First fetch PO details to get item names
                fetch(`/api/purchase-orders/${poId}`, { headers })
                    .then(res => {
                        if (!res.ok) throw new Error('Failed to fetch PO');
                        return res.json();
                    })
                    .then(po => {
                        const itemsMap = {};
                        (po.purchase_order_items || []).forEach(item => {
                            itemsMap[item.id] = {
                                name: item.item?.designation || item.new_item_name || 'Item',
                                unit: item.item?.unit || ''
                            };
                        });
                        window.itemsMapForDelivery = itemsMap;
                        
                        // Now fetch confirmed notes
                        return fetch(`/api/purchase-orders/${poId}/bon-de-livraison`, { headers });
                    })
                    .then(res => res.json())
                    .then(confirmedNotes => {
                        // Render confirmed notes (final - no edit/delete)
                        let html = '';
                        
                        if ((!confirmedNotes || confirmedNotes.length === 0) && drafts.length === 0) {
                            list.innerHTML = '<p class="text-gray-500">{{ __('messages.no_delivery_notes') }}</p>';
                            return;
                        }
                        
                        // Render confirmed notes
                        if (confirmedNotes && confirmedNotes.length > 0) {
                            html += confirmedNotes.map(note => {
                                const items = note.items || [];
                                const itemsHtml = items.map(item => `
                                    <div class="flex justify-between text-sm">
                                        <span>${item.purchase_order_item?.item?.designation || '-'}</span>
                                        <span>${parseFloat(item.quantity || 0).toFixed(2)} ${item.purchase_order_item?.item?.unit || ''}</span>
                                    </div>
                                `).join('');
                                const isImage = note.file_path && (note.file_path.endsWith('.jpg') || note.file_path.endsWith('.jpeg') || note.file_path.endsWith('.png') || note.file_path.endsWith('.gif') || note.file_path.endsWith('.webp'));
                                return `
                                    <div class="border rounded p-4 mb-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p class="font-semibold">{{ __('messages.delivery_date') }}: ${new Date(note.date).toLocaleDateString()}</p>
                                                <p class="text-sm text-gray-500">{{ __('messages.status') }}: <span class="text-green-600">{{ __('messages.confirmed') }}</span></p>
                                            </div>
                                            ${note.file_path ? (isImage ? `
                                                <img src="/storage/${note.file_path}" alt="Delivery document" class="h-20 w-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity border" onclick="openLightbox('/storage/${note.file_path}')">
                                            ` : `<a href="/storage/${note.file_path}" target="_blank" class="text-blue-600 hover:underline text-sm">{{ __('messages.view_file') }}</a>`) : ''}
                                        </div>
                                        <div class="mt-2">${itemsHtml}</div>
                                    </div>
                                `;
                            }).join('');
                        }
                        
                        // Render drafts with item names
                        if (drafts.length > 0) {
                            html += drafts.map(draft => renderDraftNote(draft, poId)).join('');
                        }
                        
                        list.innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Error loading delivery notes:', err);
                        list.innerHTML = '<p class="text-red-500">{{ __('messages.error_loading') }}</p>';
                    });
            }

            function renderDraftNote(draft, poId) {
                const itemsMap = window.itemsMapForDelivery || {};
                const itemsHtml = (draft.items || []).map(item => {
                    const itemInfo = itemsMap[item.purchase_order_item_id] || {};
                    return `
                        <div class="flex justify-between text-sm">
                            <span>${itemInfo.name || 'Item #' + item.purchase_order_item_id}</span>
                            <span>${parseFloat(item.quantity || 0).toFixed(2)} ${itemInfo.unit || ''}</span>
                        </div>
                    `;
                }).join('');
                
                let filePreview = '';
                if (draft.fileData) {
                    const isImage = draft.fileData.startsWith('data:image');
                    if (isImage) {
                        filePreview = `<img src="${draft.fileData}" alt="Delivery document" class="h-20 w-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity border" onclick="openLightbox('${draft.fileData}')">`;
                    } else {
                        filePreview = `<span class="text-blue-600 text-sm">File attached</span>`;
                    }
                }
                
                return `
                    <div class="border-2 border-yellow-400 rounded p-4 mb-3 bg-yellow-50" id="draft-${draft.localId}">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-semibold">{{ __('messages.delivery_date') }}: <span id="draft-date-${draft.localId}">${new Date(draft.date).toLocaleDateString()}</span></p>
                                <p class="text-sm text-yellow-700 font-medium">{{ __('messages.draft') }}</p>
                            </div>
                            ${filePreview}
                        </div>
                        <div class="mt-2" id="draft-items-${draft.localId}">${itemsHtml}</div>
                        <div class="mt-3 flex gap-2" id="draft-actions-${draft.localId}">
                            ${window.confirmingDraftId === draft.localId ? `
                                <button disabled class="px-3 py-1 bg-blue-300 text-white rounded text-sm cursor-not-allowed">{{ __('messages.edit') }}</button>
                                <button disabled class="px-3 py-1 bg-green-300 text-white rounded text-sm cursor-not-allowed flex items-center gap-2">
                                    <svg class="h-4 w-4 text-white" style="animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('messages.confirming') }}...</span>
                                </button>
                                <button disabled class="px-3 py-1 bg-red-300 text-white rounded text-sm cursor-not-allowed">{{ __('messages.delete') }}</button>
                            ` : `
                                <button onclick="editDraftDeliveryNote('${draft.localId}', '${poId}')" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">{{ __('messages.edit') }}</button>
                                <button onclick="confirmDraftDeliveryNote('${draft.localId}')" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">{{ __('messages.confirm_delivery') }}</button>
                                <button onclick="deleteDraftDeliveryNote('${draft.localId}', '${poId}')" class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">{{ __('messages.delete') }}</button>
                            `}
                        </div>
                    </div>
                `;
            }

            function editDraftDeliveryNote(localId, poId) {
                const draft = getDraftNote(poId, localId);
                if (!draft) return;
                
                const card = document.getElementById(`draft-${localId}`);
                if (!card) return;
                
                // Load item names
                fetch(`/api/purchase-orders/${poId}`, { headers })
                    .then(res => res.json())
                    .then(po => {
                        const itemsMap = {};
                        (po.purchase_order_items || []).forEach(item => {
                            itemsMap[item.id] = item.item?.designation || item.new_item_name || 'Item';
                        });
                        
                        card.innerHTML = `
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium mb-1">{{ __('messages.delivery_date') }}</label>
                                    <input type="date" id="edit-date-${localId}" value="${draft.date}" class="w-full px-3 py-2 border rounded">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">{{ __('messages.notes') }}</label>
                                    <textarea id="edit-notes-${localId}" class="w-full px-3 py-2 border rounded">${draft.notes || ''}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">{{ __('messages.items') }}</label>
                                    <div id="edit-items-${localId}" class="space-y-2">
                                        ${(draft.items || []).map((item, idx) => `
                                            <div class="flex items-center gap-2">
                                                <input type="number" value="${item.quantity}" min="0.01" step="0.01" 
                                                    class="w-24 px-2 py-1 border rounded edit-qty" data-item-idx="${idx}">
                                                <span class="text-sm text-gray-600">${itemsMap[item.purchase_order_item_id] || 'Item'}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="saveDraftDeliveryNote('${localId}', '${poId}')" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">{{ __('messages.save') }}</button>
                                    <button onclick="renderDeliveryNotes('${poId}')" class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700">{{ __('messages.cancel') }}</button>
                                </div>
                            </div>
                        `;
                    });
            }

            function saveDraftDeliveryNote(localId, poId) {
                const date = document.getElementById(`edit-date-${localId}`).value;
                const notes = document.getElementById(`edit-notes-${localId}`).value;
                const qtyInputs = document.querySelectorAll(`#edit-items-${localId} .edit-qty`);
                
                const draft = getDraftNote(poId, localId);
                if (!draft) return;
                
                const items = [];
                qtyInputs.forEach((input, idx) => {
                    items.push({
                        ...draft.items[idx],
                        quantity: parseFloat(input.value)
                    });
                });
                
                updateDraftNote(poId, localId, { date, notes, items });
                renderDeliveryNotes(poId);
                Notification.success('Draft updated');
            }

            function deleteDraftDeliveryNote(localId, poId) {
                if (!confirm('{{ __('messages.confirm_delete_delivery_note') }}')) {
                    return;
                }
                deleteDraftNote(poId, localId);
                renderDeliveryNotes(poId);
                loadPOItemsForDelivery(poId);
                Notification.success('Draft deleted');
            }

            function confirmDraftDeliveryNote(localId) {
                const poId = document.getElementById('deliveryPoId').value;
                const draft = getDraftNote(poId, localId);
                if (!draft) {
                    Notification.error('Draft not found');
                    return;
                }
                
                if (!token) {
                    Notification.error('Authentication required');
                    return;
                }
                
                // Set loading state
                console.log('[confirmDraftDeliveryNote] Starting confirm for draft:', localId);
                window.confirmingDraftId = localId;
                renderDeliveryNotes(poId);
                
                const formData = new FormData();
                formData.append('date', draft.date);
                formData.append('notes', draft.notes || '');
                formData.append('items', JSON.stringify(draft.items));
                
                if (draft.fileData) {
                    const blob = dataURItoBlob(draft.fileData);
                    const ext = draft.fileData.includes('png') ? '.png' : draft.fileData.includes('jpg') || draft.fileData.includes('jpeg') ? '.jpg' : '.pdf';
                    formData.append('file', blob, 'delivery_note' + ext);
                }
                
                const url = `/api/purchase-orders/${poId}/bon-de-livraison`;
                
                fetch(url, {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async res => {
                    const contentType = res.headers.get('content-type');
                    let data;
                    if (contentType && contentType.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        throw new Error(`Server error (${res.status}): ${text.substring(0, 100)}`);
                    }
                    if (!res.ok) {
                        throw new Error(data.error || `HTTP ${res.status}`);
                    }
                    return data;
                })
                .then(() => {
                    console.log('[confirmDraftDeliveryNote] Success! Clearing state');
                    window.confirmingDraftId = null;
                    deleteDraftNote(poId, localId);
                    renderDeliveryNotes(poId);
                    loadPOItemsForDelivery(poId);
                    loadPODeliveryNotes(poId);
                    loadPOs();
                    Notification.success('{{ __('messages.delivery_note_confirmed') }}');
                })
                .catch(err => {
                    console.log('[confirmDraftDeliveryNote] Error! Clearing state');
                    window.confirmingDraftId = null;
                    renderDeliveryNotes(poId);
                    console.error('Confirm error:', err);
                    Notification.error('{{ __('messages.error_confirming_delivery') }}: ' + err.message);
                });
            }

            function loadDeliveryNotes(poId) {
                renderDeliveryNotes(poId);
            }

            function loadPOItemsForDelivery(poId) {
                fetch(`/api/purchase-orders/${poId}`, { headers })
                    .then(res => res.json())
                    .then(po => {
                        const list = document.getElementById('deliveryItemsList');
                        const items = po.purchase_order_items || [];
                        
                        // Group items by supplier
                        const supplierGroups = {};
                        items.forEach(item => {
                            const supplierName = item.proposition?.supplier?.name || 'unknown';
                            if (!supplierGroups[supplierName]) {
                                supplierGroups[supplierName] = [];
                            }
                            supplierGroups[supplierName].push(item);
                        });
                        
                        // Build supplier tabs
                        const suppliers = Object.keys(supplierGroups);
                        
                        let activeSupplier = suppliers[0] || '';
                        
                        list.innerHTML = `
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">{{ __('messages.supplier') }}</label>
                                <select id="deliverySupplierSelect" class="w-full px-3 py-2 border rounded" onchange="updateDeliveryItemsBySupplier(this.value)">
                                    ${suppliers.map(s => `<option value="${s}">${s}</option>`).join('')}
                                </select>
                            </div>
                            <div id="deliveryItemsBySupplier"></div>
                        `;
                        
                        window.currentSupplierGroups = supplierGroups;
                        updateDeliveryItemsBySupplier(activeSupplier);
                    });
            }
            
            function updateDeliveryItemsBySupplier(supplierName) {
                const supplierGroups = window.currentSupplierGroups || {};
                const items = supplierGroups[supplierName] || [];
                const container = document.getElementById('deliveryItemsBySupplier');
                
                container.innerHTML = items.map(item => {
                    const ordered = parseFloat(item.init_quantity || 0);
                    const delivered = parseFloat(item.final_quantity || 0);
                    const alreadyDelivered = delivered - ordered;
                    const remaining = ordered - alreadyDelivered;
                    return `
                    <div class="flex items-center gap-3 p-3 border rounded bg-gray-50">
                        <div class="flex-1">
                            <p class="font-medium">${item.item?.designation || item.new_item_name || '{{ __('messages.unknown_item') }}'}</p>
                            <p class="text-sm text-gray-500">
                                {{ __('messages.ordered') }}: ${ordered.toFixed(2)} ${item.item?.unit || ''} | 
                                {{ __('messages.delivered') }}: ${alreadyDelivered.toFixed(2)} | 
                                {{ __('messages.remaining') }}: ${remaining.toFixed(2)}
                            </p>
                        </div>
                        <input type="hidden" name="delivery_item_id" value="${item.id}">
                        <input type="number" name="delivery_qty" min="0.01" step="0.01" placeholder="{{ __('messages.quantity') }}" class="w-24 px-2 py-1 border rounded">
                        <button type="button" onclick="this.previousElementSibling.value = ${remaining.toFixed(2)}" class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded" title="{{ __('messages.fill_max') }}">
                            Max
                        </button>
                    </div>
                `}).join('');
            }

            function uploadDeliveryNote(e) {
                e.preventDefault();
                
                const poId = document.getElementById('deliveryPoId').value;
                
                if (!poId || !token) {
                    Notification.error('Missing required data');
                    return;
                }
                
                const formData = new FormData();
                formData.append('date', document.getElementById('deliveryDate').value);
                
                const fileInput = document.getElementById('deliveryFile');
                if (fileInput.files.length > 0) {
                    formData.append('file', fileInput.files[0]);
                }
                
                const notes = document.getElementById('deliveryNotes').value;
                if (notes) formData.append('notes', notes);

                const items = [];
                document.querySelectorAll('#deliveryItemsList .border').forEach(row => {
                    const itemIdInput = row.querySelector('input[name="delivery_item_id"]');
                    const qtyInput = row.querySelector('input[name="delivery_qty"]');
                    if (itemIdInput && qtyInput) {
                        const itemId = itemIdInput.value;
                        const qty = qtyInput.value;
                        if (itemId && qty && parseFloat(qty) > 0) {
                            items.push({
                                purchase_order_item_id: parseInt(itemId),
                                quantity: parseFloat(qty)
                            });
                        }
                    }
                });
                
                if (items.length === 0) {
                    Notification.error('Please add at least one item quantity');
                    return;
                }
                
                formData.append('items', JSON.stringify(items));

                const url = `/api/purchase-orders/${poId}/bon-de-livraison`;
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => {
                    if (res.status === 401) {
                        Notification.error('Authentication failed - please login again');
                        window.location.href = '/login';
                        return Promise.reject('Unauthorized');
                    }
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error('Server error: ' + res.status);
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    document.getElementById('uploadDeliveryNoteForm').reset();
                    document.getElementById('filePreviewName').textContent = '';
                    renderDeliveryNotes(poId);
                    loadPOItemsForDelivery(poId);
                    loadPODeliveryNotes(poId);
                    Notification.success('{{ __('messages.delivery_note_uploaded') }}');
                })
                .catch(err => {
                    Notification.error('{{ __('messages.error_uploading_delivery_note') }}: ' + err.message);
                });
            }

            let selectedFileData = null;

            function handleFileSelect(input) {
                const file = input.files[0];
                if (!file) {
                    selectedFileData = null;
                    document.getElementById('filePreviewName').textContent = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    selectedFileData = e.target.result;
                    document.getElementById('filePreviewName').textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                };
                reader.readAsDataURL(file);
            }

            function saveDeliveryNoteDraft(e) {
                e.preventDefault();
                
                const poId = document.getElementById('deliveryPoId').value;
                
                if (!poId) {
                    Notification.error('Missing PO ID');
                    return;
                }
                
                const date = document.getElementById('deliveryDate').value;
                const notes = document.getElementById('deliveryNotes').value;

                const items = [];
                document.querySelectorAll('#deliveryItemsList .border').forEach(row => {
                    const itemIdInput = row.querySelector('input[name="delivery_item_id"]');
                    const qtyInput = row.querySelector('input[name="delivery_qty"]');
                    if (itemIdInput && qtyInput) {
                        const itemId = itemIdInput.value;
                        const qty = qtyInput.value;
                        if (itemId && qty && parseFloat(qty) > 0) {
                            items.push({
                                purchase_order_item_id: parseInt(itemId),
                                quantity: parseFloat(qty)
                            });
                        }
                    }
                });
                
                if (items.length === 0) {
                    Notification.error('Please add at least one item quantity');
                    return;
                }
                
                const draft = {
                    date: date,
                    notes: notes,
                    items: items,
                    fileData: selectedFileData
                };
                
                addDraftNote(poId, draft);
                
                // Reset form
                document.getElementById('uploadDeliveryNoteForm').reset();
                document.getElementById('filePreviewName').textContent = '';
                selectedFileData = null;
                
                Notification.success('{{ __('messages.draft_saved') }}');
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

            function deletePO(id) {
                if (!confirm('{{ __('messages.confirm_delete_po') }}')) {
                    return;
                }
                fetch(`/api/purchase-orders/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    if (!res.ok) {
                        const data = await res.json();
                        throw new Error(data.error || '{{ __('messages.error_deleting_po') }}');
                    }
                    return res.json();
                })
                .then(() => {
                    loadPOs();
                    Notification.success('{{ __('messages.po_deleted_success') }}');
                })
                .catch(err => Notification.error(err.message));
            }

            function openLightbox(imageUrl) {
                document.getElementById('lightboxImage').src = imageUrl;
                document.getElementById('imageLightbox').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                document.getElementById('deliveryNotesModal').style.zIndex = '1';
            }

            function closeLightbox() {
                document.getElementById('imageLightbox').classList.add('hidden');
                document.body.style.overflow = '';
                document.getElementById('deliveryNotesModal').style.zIndex = '50';
            }

            // Invoice Modal Functions
            function openInvoiceModal() {
                const poId = window.currentPOPoId;
                if (!poId) return;
                
                document.getElementById('invoiceModal').classList.remove('hidden');
                document.getElementById('invoicePoId').value = poId;
                loadBonDeLivraisonsForInvoice(poId);
            }

            function closeInvoiceModal() {
                const poId = document.getElementById('invoicePoId').value;
                const drafts = getInvoiceDrafts(poId);
                
                if (drafts.length > 0) {
                    Notification.warning('{{ __('messages.invoice_draft_pending') }}');
                    return;
                }
                
                document.getElementById('invoiceModal').classList.add('hidden');
                document.getElementById('bonDeLivraisonSelector').innerHTML = '';
                document.getElementById('uploadInvoiceForm').reset();
                document.getElementById('invoiceFilePreviewName').textContent = '';
                window.invoiceSelectedFileData = null;
            }

            // LocalStorage helpers for invoice drafts
            function getInvoiceDraftsKey(poId) {
                return `invoice_drafts_${poId}`;
            }

            function getInvoiceDrafts(poId) {
                const data = localStorage.getItem(getInvoiceDraftsKey(poId));
                return data ? JSON.parse(data) : [];
            }

            function saveInvoiceDrafts(poId, drafts) {
                localStorage.setItem(getInvoiceDraftsKey(poId), JSON.stringify(drafts));
            }

            function loadBonDeLivraisonsForInvoice(poId) {
                const container = document.getElementById('bonDeLivraisonSelector');
                container.innerHTML = '<p class="text-gray-500 text-sm">{{ __('messages.loading') }}...</p>';
                
                Promise.all([
                    fetch(`/api/purchase-orders/${poId}/bon-de-livraison`, { headers }).then(res => res.json()),
                    fetch(`/api/invoices?purchase_order_id=${poId}`, { headers }).then(res => res.json())
                ])
                    .then(([bonDeLivraisons, invoices]) => {
                        // Collect already used BDL IDs and their sources
                        const alreadyUsedMap = {};
                        
                        // From confirmed invoices
                        (invoices || []).forEach(inv => {
                            const ids = inv.bon_de_livraison_ids || [];
                            ids.forEach(id => {
                                alreadyUsedMap[id] = { source: 'invoice', invoiceId: inv.id };
                            });
                        });
                        
                        // From localStorage drafts
                        const drafts = getInvoiceDrafts(poId);
                        drafts.forEach((draft, idx) => {
                            (draft.bon_de_livraison_ids || []).forEach(id => {
                                if (!alreadyUsedMap[id]) {
                                    alreadyUsedMap[id] = { source: 'draft', draftIndex: idx + 1 };
                                }
                            });
                        });
                        
                        if (!bonDeLivraisons || bonDeLivraisons.length === 0) {
                            container.innerHTML = '<p class="text-gray-500 text-sm">{{ __('messages.no_delivery_notes') }}</p>';
                            return;
                        }
                        
                        container.innerHTML = bonDeLivraisons.map(bdl => {
                            const items = bdl.items || [];
                            const totalQty = items.reduce((sum, item) => sum + parseFloat(item.quantity || 0), 0);
                            const itemNames = items.map(item => item.purchase_order_item?.item?.designation || item.purchase_order_item?.new_item_name || 'Item').join(', ');
                            const isImage = bdl.file_path && (bdl.file_path.endsWith('.jpg') || bdl.file_path.endsWith('.jpeg') || bdl.file_path.endsWith('.png'));
                            const usage = alreadyUsedMap[bdl.id];
                            const isInInvoice = usage?.source === 'invoice';
                            const isInDraft = usage?.source === 'draft';
                            
                            let badge = '';
                            if (isInInvoice) {
                                badge = `<span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">{{ __('messages.already_invoiced') }}</span>`;
                            } else if (isInDraft) {
                                badge = `<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">{{ __('messages.in_draft') }} #${usage.draftIndex}</span>`;
                            }
                            
                            return `
                                <div class="border rounded p-3 ${isInInvoice || isInDraft ? 'bg-gray-100 opacity-60' : 'bg-white cursor-pointer hover:bg-blue-50'} transition-colors" ${!(isInInvoice || isInDraft) ? `onclick="toggleBdlSelection(${bdl.id}, this)"` : ''}>
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" id="bdl_${bdl.id}" class="mt-1 bdl-checkbox" onchange="updateSelectedBdlCount()" ${isInInvoice || isInDraft ? 'disabled' : ''}>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center gap-2">
                                                    <p class="font-semibold">{{ __('messages.delivery_note') }} #${bdl.id}</p>
                                                    ${badge}
                                                </div>
                                                ${bdl.file_path ? (isImage ? `
                                                    <img src="/storage/${bdl.file_path}" alt="Delivery" class="h-16 w-16 object-cover rounded cursor-pointer hover:opacity-80" onclick="event.stopPropagation(); openLightbox('/storage/${bdl.file_path}')">
                                                ` : `<a href="/storage/${bdl.file_path}" target="_blank" class="text-blue-600 hover:underline text-sm" onclick="event.stopPropagation()">{{ __('messages.view_file') }}</a>`) : ''}
                                            </div>
                                            <div class="mt-1 text-sm text-gray-500">{{ __('messages.date') }}: ${new Date(bdl.date).toLocaleDateString()}</div>
                                            <div class="mt-2 text-sm text-gray-600">
                                                <p><strong>{{ __('messages.items') }}:</strong> ${itemNames || '-'}</p>
                                                <p><strong>{{ __('messages.total_qty') }}:</strong> ${totalQty.toFixed(2)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                        
                        updateSelectedBdlCount();
                    })
                    .catch(err => {
                        container.innerHTML = '<p class="text-red-500 text-sm">{{ __('messages.error_loading') }}</p>';
                    });
            }

            function toggleBdlSelection(bdlId, element) {
                const checkbox = element.querySelector('.bdl-checkbox');
                checkbox.checked = !checkbox.checked;
                element.classList.toggle('bg-blue-50', checkbox.checked);
                element.classList.toggle('border-blue-400', checkbox.checked);
                updateSelectedBdlCount();
            }

            function updateSelectedBdlCount() {
                const checked = document.querySelectorAll('.bdl-checkbox:checked').length;
                document.getElementById('selectedBdlCount').textContent = checked;
            }

            function getSelectedBdlIds() {
                const checked = document.querySelectorAll('.bdl-checkbox:checked');
                return Array.from(checked).map(cb => parseInt(cb.id.replace('bdl_', '')));
            }

            window.invoiceSelectedFileData = null;

            function handleInvoiceFileSelect(input) {
                const file = input.files[0];
                if (!file) {
                    window.invoiceSelectedFileData = null;
                    document.getElementById('invoiceFilePreviewName').textContent = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    window.invoiceSelectedFileData = e.target.result;
                    document.getElementById('invoiceFilePreviewName').textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                };
                reader.readAsDataURL(file);
            }

            function saveInvoiceDraft(e) {
                e.preventDefault();
                
                const poId = document.getElementById('invoicePoId').value;
                
                if (!poId) {
                    Notification.error('Missing PO ID');
                    return;
                }
                
                const date = document.getElementById('invoiceDate').value;
                const notes = document.getElementById('invoiceNotes').value;
                const selectedBdlIds = getSelectedBdlIds();
                
                if (selectedBdlIds.length === 0) {
                    Notification.error('{{ __('messages.select_at_least_one_bdl') }}');
                    return;
                }
                
                const draft = {
                    date: date,
                    notes: notes,
                    bon_de_livraison_ids: selectedBdlIds,
                    fileData: window.invoiceSelectedFileData
                };
                
                const drafts = getInvoiceDrafts(poId);
                drafts.push(draft);
                saveInvoiceDrafts(poId, drafts);
                
                document.getElementById('uploadInvoiceForm').reset();
                document.getElementById('invoiceFilePreviewName').textContent = '';
                document.getElementById('selectedBdlCount').textContent = '0';
                window.invoiceSelectedFileData = null;
                
                // Uncheck all checkboxes
                document.querySelectorAll('.bdl-checkbox').forEach(cb => cb.checked = false);
                document.querySelectorAll('#bonDeLivraisonSelector > div').forEach(el => {
                    el.classList.remove('bg-blue-50', 'border-blue-400');
                });
                
                Notification.success('{{ __('messages.draft_saved') }}');
                renderInvoiceDrafts(poId);
                loadBonDeLivraisonsForInvoice(poId);
            }

            function renderInvoiceDrafts(poId) {
                const container = document.getElementById('invoiceDraftsList');
                if (!container) return;
                
                const drafts = getInvoiceDrafts(poId);
                
                if (drafts.length === 0) {
                    container.innerHTML = '';
                    return;
                }
                
                container.innerHTML = drafts.map((draft, idx) => `
                    <div class="border-2 border-yellow-400 rounded p-4 mb-3 bg-yellow-50" id="invoice-draft-${idx}">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-semibold">{{ __('messages.invoice_date') }}: ${new Date(draft.date).toLocaleDateString()}</p>
                                <p class="text-sm text-gray-600">{{ __('messages.linked_bdl') }}: ${(draft.bon_de_livraison_ids || []).length} {{ __('messages.delivery_notes') }}</p>
                                <p class="text-sm text-yellow-700 font-medium">{{ __('messages.draft') }}</p>
                            </div>
                            ${draft.fileData ? `<span class="text-blue-600 text-sm">{{ __('messages.file_attached') }}</span>` : ''}
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button onclick="confirmInvoiceDraft(${idx})" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">{{ __('messages.confirm') }}</button>
                            <button onclick="deleteInvoiceDraft(${idx})" class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">{{ __('messages.delete') }}</button>
                        </div>
                    </div>
                `).join('');
            }

            function confirmInvoiceDraft(idx) {
                const poId = document.getElementById('invoicePoId').value;
                const drafts = getInvoiceDrafts(poId);
                const draft = drafts[idx];
                
                if (!draft) return;
                
                if (!token) {
                    Notification.error('Authentication required');
                    return;
                }
                
                if (!draft.bon_de_livraison_ids || draft.bon_de_livraison_ids.length === 0) {
                    Notification.error('{{ __('messages.select_at_least_one_bdl') }}');
                    return;
                }
                
                const formData = new FormData();
                formData.append('date', draft.date);
                formData.append('notes', draft.notes || '');
                formData.append('bon_de_livraison_ids', JSON.stringify(draft.bon_de_livraison_ids));
                formData.append('purchase_order_id', poId);
                formData.append('type', 'incoming');
                formData.append('supplier', '');
                
                if (draft.fileData) {
                    const blob = dataURItoBlob(draft.fileData);
                    const ext = draft.fileData.includes('png') ? '.png' : draft.fileData.includes('jpg') || draft.fileData.includes('jpeg') ? '.jpg' : '.pdf';
                    formData.append('image', blob, 'invoice' + ext);
                }
                
                fetch('/api/invoices', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async res => {
                    const contentType = res.headers.get('content-type');
                    let data;
                    if (contentType && contentType.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        throw new Error(`Server error (${res.status}): ${text.substring(0, 100)}`);
                    }
                    if (!res.ok) {
                        throw new Error(data.error || `HTTP ${res.status}`);
                    }
                    return data;
                })
                .then(() => {
                    drafts.splice(idx, 1);
                    saveInvoiceDrafts(poId, drafts);
                    renderInvoiceDrafts(poId);
                    loadPOInvoices(poId);
                    
                    // Manually disable the BDLs that were just confirmed
                    draft.bon_de_livraison_ids.forEach(bdlId => {
                        const checkbox = document.getElementById(`bdl_${bdlId}`);
                        const row = checkbox?.closest('.border.rounded');
                        
                        if (checkbox) {
                            checkbox.disabled = true;
                            checkbox.checked = false;
                        }
                        if (row) {
                            row.classList.add('bg-gray-100', 'opacity-60');
                            row.classList.remove('bg-white', 'hover:bg-blue-50', 'cursor-pointer');
                            row.onclick = null;
                            
                            // Update badge to "Already invoiced"
                            const badgeArea = row.querySelector('.flex.items-center.gap-2');
                            if (badgeArea) {
                                badgeArea.innerHTML = `
                                    <p class="font-semibold">{{ __('messages.delivery_note') }} #${bdlId}</p>
                                    <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">{{ __('messages.already_invoiced') }}</span>
                                `;
                            }
                        }
                    });
                    
                    loadPOs();
                    Notification.success('{{ __('messages.invoice_created_success') }}');
                })
                .catch(err => {
                    console.error('Invoice error:', err);
                    Notification.error('{{ __('messages.error_creating_invoice') }}: ' + err.message);
                });
            }

            function deleteInvoiceDraft(idx) {
                const poId = document.getElementById('invoicePoId').value;
                const drafts = getInvoiceDrafts(poId);
                drafts.splice(idx, 1);
                saveInvoiceDrafts(poId, drafts);
                renderInvoiceDrafts(poId);
                loadBonDeLivraisonsForInvoice(poId);
                Notification.success('{{ __('messages.draft_deleted') }}');
            }

            function loadPOInvoices(poId) {
                const container = document.getElementById('invoicesListInTab');
                if (!container) return;
                
                container.innerHTML = '<p class="text-gray-500">{{ __('messages.loading') }}...</p>';
                
                fetch(`/api/invoices?purchase_order_id=${poId}`, { headers })
                    .then(res => res.json())
                    .then(invoices => {
                        if (!invoices || invoices.length === 0) {
                            container.innerHTML = '<p class="text-gray-500">{{ __('messages.no_invoices_found') }}</p>';
                        } else {
                            container.innerHTML = invoices.map(inv => {
                                const isImage = inv.image_path && (inv.image_path.endsWith('.jpg') || inv.image_path.endsWith('.jpeg') || inv.image_path.endsWith('.png'));
                                return `
                                    <div class="border rounded p-4 mb-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p class="font-semibold">{{ __('messages.invoice_date') }}: ${new Date(inv.date).toLocaleDateString()}</p>
                                                <p class="text-sm text-gray-500">{{ __('messages.status') }}: <span class="text-green-600">{{ __('messages.confirmed') }}</span></p>
                                            </div>
                                            ${inv.image_path ? (isImage ? `
                                                <img src="/storage/${inv.image_path}" alt="Invoice" class="h-20 w-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity border" onclick="openLightbox('/storage/${inv.image_path}')">
                                            ` : `<a href="/storage/${inv.image_path}" target="_blank" class="text-blue-600 hover:underline text-sm">{{ __('messages.view_file') }}</a>`) : ''}
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        }
                    })
                    .catch(err => {
                        container.innerHTML = '<p class="text-red-500">{{ __('messages.error_loading') }}</p>';
                    });
            }

            function switchPOTab(tabName) {
                currentPOTab = tabName;
                document.querySelectorAll('.po-tab-content').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.po-tab-btn').forEach(el => {
                    if (el.dataset.tab === tabName) {
                        el.classList.add('border-blue-500', 'text-blue-600');
                        el.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        el.classList.remove('border-blue-500', 'text-blue-600');
                        el.classList.add('border-transparent', 'text-gray-500');
                    }
                });
                const tabContent = document.getElementById('po-tab-' + tabName);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }
                if (tabName === 'delivery' && window.currentPOPoId) {
                    loadPODeliveryNotes(window.currentPOPoId);
                    const po = allPOs.find(p => p.id === window.currentPOPoId);
                    const uploadSection = document.getElementById('uploadDeliverySectionInTab');
                    if (uploadSection) {
                        if (po && po.status === 'delivered') {
                            uploadSection.classList.add('hidden');
                        } else {
                            uploadSection.classList.remove('hidden');
                        }
                    }
                }
                if (tabName === 'invoices' && window.currentPOPoId) {
                    loadPOInvoices(window.currentPOPoId);
                    renderInvoiceDrafts(window.currentPOPoId);
                }
            }
        </script>
    @endpush

    <!-- Invoice Modal -->
    <div id="invoiceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <p class="text-xl font-bold">{{ __('messages.invoices') }}</p>
                <button onclick="closeInvoiceModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="invoiceDraftsList" class="mb-6"></div>

            <div class="border-t pt-4">
                <h4 class="font-semibold mb-3">{{ __('messages.add_invoice') }}</h4>
                <form id="uploadInvoiceForm" onsubmit="saveInvoiceDraft(event)">
                    <input type="hidden" id="invoicePoId" value="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.invoice_date') }} *</label>
                            <input type="date" id="invoiceDate" required class="w-full px-3 py-2 border rounded" value="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.select_bon_de_livraison') }} *</label>
                            <p class="text-xs text-gray-500 mb-2">{{ __('messages.select_bdl_for_invoice') }}</p>
                            <div id="bonDeLivraisonSelector" class="space-y-3 max-h-64 overflow-y-auto border rounded p-3 bg-gray-50">
                                <p class="text-gray-500 text-sm">{{ __('messages.loading') }}...</p>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">{{ __('messages.selected_count') }}: <span id="selectedBdlCount">0</span></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.upload_file') }}</label>
                            <input type="file" id="invoiceFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full px-3 py-2 border rounded" onchange="handleInvoiceFileSelect(this)">
                            <p id="invoiceFilePreviewName" class="text-sm text-gray-500 mt-1"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.notes') }}</label>
                            <textarea id="invoiceNotes" rows="2" class="w-full px-3 py-2 border rounded"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" onclick="closeInvoiceModal()"
                            class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                        <button type="submit" id="saveInvoiceBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.save_draft') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div id="imageLightbox" class="hidden fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[9999] cursor-pointer" onclick="closeLightbox()">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <img id="lightboxImage" src="" alt="Full size" class="max-w-[95vw] max-h-[95vh] object-contain">
    </div>
@endsection
