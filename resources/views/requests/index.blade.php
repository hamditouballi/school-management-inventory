@extends('layouts.app')

@section('title', __('messages.requests'))

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.requests') }}</h1>
            <p class="text-gray-600">{{ __('messages.view_and_manage_item_requests') }}</p>
        </div>
        <div class="flex items-center gap-4">
            @if (auth()->user()->role !== 'stock_manager')
                <button onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    {{ __('messages.create_request') }}
                </button>
            @endif
            <div class="flex gap-2">
                <button onclick="switchTab('all')" id="tabAll" class="px-4 py-2 rounded-lg font-medium bg-green-600 text-white shadow">
                    {{ __('messages.all_statuses') }}
                </button>
                <button onclick="switchTab('unconfirmed')" id="tabUnconfirmed" class="px-4 py-2 rounded-lg font-medium bg-gray-200 hover:bg-gray-300 relative">
                    <span id="unconfirmedText">{{ __('messages.unconfirmed') }}</span>
                    <span id="unconfirmedBadge" class="hidden ml-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">0</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">{{ __('messages.search') }}</label>
                <input type="text" id="searchInput" placeholder="{{ __('messages.search') }}"
                    class="w-full px-3 py-2 border rounded" oninput="applyFilters()">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">{{ __('messages.status') }}</label>
                <select id="statusFilter" class="w-full px-3 py-2 border rounded" onchange="applyFilters()">
                    <option value="">{{ __('messages.all_statuses') }}</option>
                    <option value="pending">{{ __('messages.pending') }}</option>
                    <option value="hr_approved">{{ __('messages.hr_approved') }}</option>
                    <option value="rejected">{{ __('messages.rejected') }}</option>
                    <option value="fulfilled">{{ __('messages.fulfilled') }}</option>
                    <option value="received">{{ __('messages.received') }}</option>
                </select>
            </div>
            <div class="flex-1">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.requester') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.items') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.total_price') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.date') }}</th>
                        @if (auth()->user()->role === 'hr_manager' || auth()->user()->role === 'stock_manager')
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ __('messages.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="requestsBody">
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.loading_requests') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t flex items-center justify-between">
            <div class="text-sm text-gray-700">
                {{ __('messages.showing') }} <span id="showingFrom">0</span> {{ __('messages.to') }} <span id="showingTo">0</span> {{ __('messages.of') }} <span
                    id="totalRequests">0</span> {{ __('messages.requests') }}
            </div>
            <div id="pagination" class="flex gap-2">
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">{{ __('messages.request_details') }}</h3>
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

    <!-- {{ __('messages.create_request') }} Modal - Stock Manager Form -->
    @if (auth()->user()->role === 'stock_manager')
        <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold mb-4">{{ __('messages.create_new_request') }}</h3>
                <form id="createRequestForm" onsubmit="createRequest(event)">
                    <div id="itemsList" class="space-y-4 mb-4">
                        <div class="border p-4 rounded">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">{{ __('messages.item') }}</label>
                                    <select name="items[0][item_id]" required class="w-full px-3 py-2 border rounded">
                                        <option value="">{{ __('messages.select_item') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
                                    <input type="number" name="items[0][quantity_requested]" required min="1"
                                        class="w-full px-3 py-2 border rounded">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addItem()" class="mb-4 text-green-600 hover:text-indigo-800">+ Add
                        Another Item</button>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeCreateModal()"
                            class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.create_request') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- {{ __('messages.create_request') }} Modal - Grid Shopping View for Non-Stock Managers -->
        <div id="createModal"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg p-6 w-full max-w-6xl max-h-[90vh] my-8 overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">{{ __('messages.select_items_for_request') }}</h3>
                    <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Search bar -->
                <div class="mb-4">
                    <input type="text" id="itemSearchInput" placeholder="{{ __('messages.search') }} items..."
                        class="w-full px-4 py-2 border rounded-lg" oninput="filterItems()">
                </div>

                <!-- Items Grid -->
                <div id="itemsGrid"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6 max-h-96 overflow-y-auto">
                    <!-- Items will be populated here -->
                </div>

                <!-- Cart Summary -->
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">{{ __('messages.selected_items') }} (<span id="cartCount">0</span>)</h4>
                    <div id="cartItems" class="space-y-2 mb-4 max-h-48 overflow-y-auto">
<p class="text-gray-500 text-sm">{{ __('messages.no_items_selected') }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeCreateModal()"
                            class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                        <button onclick="submitCartRequest()"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.create_request') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            const token = '{{ session('api_token') }}';
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            };
            let allItems = [];
            let filteredItems = [];
            let allRequests = [];
            let filteredRequests = [];
            let currentPage = 1;
            const requestsPerPage = 10;
            let itemCounter = 1;
            const isStockManager = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};
            const isFinanceManager = {{ auth()->user()->role === 'finance_manager' ? 'true' : 'false' }};
            const currentUserId = {{ auth()->user()->id }};
            let cart = {};
            let currentTab = 'all'; // { itemId: { item: itemData, quantity: number } }

            document.addEventListener('DOMContentLoaded', () => {
                loadRequests();
                loadItemsForSelect();
                loadUnconfirmedCount();
            });

            function loadUnconfirmedCount() {
                const endpoint = (isStockManager || isFinanceManager) 
                    ? '/api/requests/unconfirmed' 
                    : '/api/requests/my-unconfirmed';
                
                fetch(endpoint, {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const badge = document.getElementById('unconfirmedBadge');
                        if (Array.isArray(data) && data.length > 0) {
                            badge.textContent = data.length;
                            badge.classList.remove('hidden');
                        } else {
                            badge.textContent = '0';
                            badge.classList.add('hidden');
                        }
                    });
            }

            function switchTab(tab) {
                currentTab = tab;
                document.getElementById('tabAll').className = tab === 'all' ? 'px-6 py-2 rounded-lg font-medium bg-green-600 text-white shadow' : 'px-6 py-2 rounded-lg font-medium bg-gray-200 hover:bg-gray-300';
                document.getElementById('tabUnconfirmed').className = tab === 'unconfirmed' ? 'px-6 py-2 rounded-lg font-medium bg-green-600 text-white shadow relative' : 'px-6 py-2 rounded-lg font-medium bg-gray-200 hover:bg-gray-300 relative';
                
                // Disable status filter when on unconfirmed tab
                const statusFilter = document.getElementById('statusFilter');
                statusFilter.disabled = tab === 'unconfirmed';
                if (tab === 'unconfirmed') {
                    statusFilter.value = '';
                }
                
                if (tab === 'unconfirmed') {
                    // Use different endpoint based on role
                    const endpoint = (isStockManager || isFinanceManager) 
                        ? '/api/requests/unconfirmed' 
                        : '/api/requests/my-unconfirmed';
                    
                    fetch(endpoint, {
                            headers
                        })
                        .then(res => res.json())
                        .then(data => {
                            filteredRequests = data;
                            currentPage = 1;
                            renderRequests();
                        });
                } else {
                    loadRequests();
                }
            }

            function loadRequests() {
                fetch('/api/requests', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allRequests = data;
                        filteredRequests = data;
                        renderRequests();
                    })
                    .catch(() => {
                        document.getElementById('requestsBody').innerHTML =
                            '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">{{ __('messages.error_loading_requests') }}</td></tr>';
                    });
            }

            function applyFilters() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const status = document.getElementById('statusFilter').value;
                const dateFrom = document.getElementById('dateFrom').value;

                filteredRequests = allRequests.filter(req => {
                    let match = true;

                    // Exclude unconfirmed fulfilled requests from "All" tab
                    if (currentTab === 'all' && req.status === 'fulfilled' && !req.confirmed_received_at) {
                        return false;
                    }

                    // Search filter
                    if (search) {
                        const userName = (req.user?.name || '').toLowerCase();
                        const deptName = (req.user?.department?.name || '').toLowerCase();
                        if (!userName.includes(search) && !deptName.includes(search)) {
                            match = false;
                        }
                    }

                    // Status filter
                    if (status && req.status !== status) {
                        match = false;
                    }

                    // Date filter
                    const reqDate = new Date(req.dateCreated || req.created_at);
                    if (dateFrom && reqDate < new Date(dateFrom)) {
                        match = false;
                    }

                    return match;
                });

                currentPage = 1;
                renderRequests();
            }

            function renderRequests() {
                let displayRequests = filteredRequests;

                // Filter out unconfirmed fulfilled requests from "all" tab only if no filters active
                if (currentTab === 'all' && !document.getElementById('searchInput').value && !document.getElementById('statusFilter').value && !document.getElementById('dateFrom').value) {
                    displayRequests = filteredRequests.filter(req => {
                        return !(req.status === 'fulfilled' && !req.confirmed_received_at);
                    });
                }

                const start = (currentPage - 1) * requestsPerPage;
                const end = start + requestsPerPage;
                const pageRequests = displayRequests.slice(start, end);

                const tbody = document.getElementById('requestsBody');
                const isStockManager = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};
                const isHrManager = {{ auth()->user()->role === 'hr_manager' ? 'true' : 'false' }};

                if (pageRequests.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.no_requests_found') }}</td></tr>';
                } else {
                    tbody.innerHTML = pageRequests.map(req => {
                        const statusColors = {
                            pending: 'bg-yellow-100 text-yellow-800',
                            hr_approved: 'bg-blue-100 text-blue-800',
                            rejected: 'bg-red-100 text-red-800',
                            fulfilled: 'bg-green-100 text-green-800',
                            received: 'bg-gray-100 text-gray-800'
                        };

                        // Handle both snake_case and camelCase from API
                        const requestItems = req.request_items || req.requestItems || [];
                        const userName = req.user?.name || 'N/A';
                        const deptName = req.user?.department?.name || 'N/A';
                        const dateCreated = req.dateCreated || req.date_created || req.created_at;
                        
                        // Calculate total price
                        const totalPrice = requestItems.reduce((sum, item) => {
                            const price = item.item?.price || 0;
                            const qty = item.quantity_requested || item.quantityRequested || 0;
                            return sum + (price * qty);
                        }, 0);

                        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">#${req.id}</td>
                <td class="px-6 py-4">${userName}</td>
                <td class="px-6 py-4">${deptName}</td>
                <td class="px-6 py-4">
                    <button onclick="viewRequestDetails(${req.id})" class="text-green-600 hover:underline">
                        ${requestItems.length} {{ __('messages.items') }}
                    </button>
                </td>
                <td class="px-6 py-4">{{ __('messages.currency') }} ${totalPrice.toFixed(2)}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded ${statusColors[req.status] || 'bg-gray-100 text-gray-800'}">${
                        req.status == "pending" 
                            ? "{{ __('messages.pending') }}" 
                            : req.status == "hr_approved" 
                                ? "{{ __('messages.hr_approved') }}" 
                                : req.status == "rejected" 
                                    ? "{{ __('messages.rejected') }}" 
                                    : req.status == "fulfilled" 
                                        ? "{{ __('messages.fulfilled') }}" 
                                        : req.status == "received"
                                            ? "{{ __('messages.received') }}"
                                            : req.status
                    }</span>
                </td>
                <td class="px-6 py-4">${dateCreated ? new Date(dateCreated).toLocaleDateString() : 'N/A'}</td>
                ${isHrManager && req.status === 'pending' ? `
                            <td class="px-6 py-4">
                                <button onclick="approveRequest(${req.id})" class="text-green-600 hover:text-green-800 mr-2">{{ __('messages.approve') }}</button>
                                <button onclick="rejectRequest(${req.id})" class="text-red-600 hover:text-red-800">{{ __('messages.reject') }}</button>
                            </td>
                        ` : isStockManager && req.status === 'hr_approved' ? `
                            <td class="px-6 py-4">
                                <button onclick="fulfillRequest(${req.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.fulfill') }}</button>
                            </td>
                        ` : (req.status === 'fulfilled' && !req.confirmed_received_at && (req.user_id === currentUserId || req.user?.id === currentUserId)) ? `
                            <td class="px-6 py-4">
                                <button onclick="confirmReceipt(${req.id})" class="text-indigo-600 hover:text-indigo-800">{{ __('messages.confirm_receipt') }}</button>
                            </td>
                        ` : (isHrManager || isStockManager) ? '<td class="px-6 py-4">-</td>' : '<td class="px-6 py-4">-</td>'}
            </tr>
        `;
                    }).join('');
                }

                updatePagination();
            }

            function updatePagination() {
                const totalPages = Math.ceil(filteredRequests.length / requestsPerPage);
                const start = (currentPage - 1) * requestsPerPage + 1;
                const end = Math.min(currentPage * requestsPerPage, filteredRequests.length);

                document.getElementById('showingFrom').textContent = filteredRequests.length ? start : 0;
                document.getElementById('showingTo').textContent = end;
                document.getElementById('totalRequests').textContent = filteredRequests.length;

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
                renderRequests();
            }

            function loadItemsForSelect() {
                fetch('/api/items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allItems = data;
                        updateItemSelects();
                    });
            }

            function updateItemSelects() {
                document.querySelectorAll('select[name*="item_id"]').forEach(select => {
                    select.innerHTML = '<option value="">{{ __('messages.select_item') }}</option>' +
                        allItems.map(item => {
                            const imgIcon = item.image_path ? '🖼️ ' : '';
                            return `<option value="${item.id}" data-image="${item.image_path || ''}">${imgIcon}${item.designation} - {{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</option>`;
                        }).join('');
                });
            }

            function showCreateModal() {
                document.getElementById('createModal').classList.remove('hidden');
                if (!isStockManager) {
                    cart = {};
                    renderItemsGrid();
                    updateCartDisplay();
                }
            }

            function closeCreateModal() {
                document.getElementById('createModal').classList.add('hidden');
                if (isStockManager) {
                    document.getElementById('createRequestForm').reset();
                    document.getElementById('itemsList').innerHTML = `
            <div class="border p-4 rounded">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('messages.item') }}</label>
                        <select name="items[0][item_id]" required class="w-full px-3 py-2 border rounded"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
                        <input type="number" name="items[0][quantity_requested]" required min="1" class="w-full px-3 py-2 border rounded">
                    </div>
                </div>
            </div>
        `;
                    itemCounter = 1;
                    updateItemSelects();
                } else {
                    cart = {};
                    document.getElementById('itemSearchInput').value = '';
                }
            }

            function addItem() {
                const container = document.getElementById('itemsList');
                const newItem = document.createElement('div');
                newItem.className = 'border p-4 rounded';
                newItem.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.item') }}</label>
                <select name="items[${itemCounter}][item_id]" required class="w-full px-3 py-2 border rounded"></select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
                <input type="number" name="items[${itemCounter}][quantity_requested]" required min="1" class="w-full px-3 py-2 border rounded">
            </div>
        </div>
    `;
                container.appendChild(newItem);
                itemCounter++;
                updateItemSelects();
            }

            function createRequest(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const items = [];

                for (let i = 0; i < itemCounter; i++) {
                    const itemId = formData.get(`items[${i}][item_id]`);
                    const quantity = formData.get(`items[${i}][quantity_requested]`);
                    if (itemId && quantity) {
                        items.push({
                            item_id: parseInt(itemId),
                            quantity_requested: parseFloat(quantity)
                        });
                    }
                }

                fetch('/api/requests', {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({
                            items
                        })
                    })
                    .then(res => res.json())
                    .then(() => {
                        closeCreateModal();
                        loadRequests();
                        Notification.success('{{ __('messages.request_created_success') }}');
                    })
                    .catch(err => Notification.error('{{ __('messages.error_message') }}'));
            }

            function approveRequest(id) {
                updateStatus(id, 'hr_approved');
            }

            function rejectRequest(id) {
                if (confirm('{{ __('messages.confirm_delete', ['item' => __('messages.requests')]) }}')) {
                    updateStatus(id, 'rejected');
                }
            }

            function updateStatus(id, status) {
                fetch(`/api/requests/${id}/status`, {
                        method: 'PUT',
                        headers,
                        body: JSON.stringify({
                            status
                        })
                    })
                    .then(() => {
                        closeDetailsModal();
                        loadRequests();
                        Notification.success(`Request ${status}!`);
                    })
                    .catch(err => Notification.error('{{ __('messages.error_updating_request') }}'));
            }

            function fulfillRequest(id) {
                if (confirm('{{ __('messages.confirm_fulfill') }}')) {
                    fetch(`/api/requests/${id}/fulfill`, {
                            method: 'POST',
                            headers
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error + '\n\n' + JSON.stringify(data.insufficient_items, null, 2));
                            } else {
                                closeDetailsModal();
                                Notification.success('{{ __('messages.request_fulfilled') }}');
                                loadRequests();
                            }
                        })
                        .catch(err => Notification.error('{{ __('messages.error_fulfilling_request') }}'));
                }
            }

            function confirmReceipt(id) {
                if (confirm('{{ __('messages.confirm_receipt') }}?')) {
                    fetch(`/api/requests/${id}/confirm-receipt`, {
                            method: 'POST',
                            headers
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.error) {
                                Notification.error(data.error);
                            } else {
                                closeDetailsModal();
                                Notification.success('{{ __('messages.receipt_confirmed') }}');
                                loadRequests();
                                loadUnconfirmedCount();
                            }
                        })
                        .catch(err => Notification.error('{{ __('messages.error_confirming_receipt') }}'));
                }
            }

            function viewRequestDetails(id) {
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsContent').innerHTML = '<p class="text-gray-500">{{ __('messages.loading') }}</p>';

                const isStockManager = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};
                const isHrManager = {{ auth()->user()->role === 'hr_manager' ? 'true' : 'false' }};

                fetch(`/api/requests/${id}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(req => {
                        const requestItems = req.request_items || req.requestItems || [];
                        const statusColors = {
                            pending: 'bg-yellow-100 text-yellow-800',
                            hr_approved: 'bg-blue-100 text-blue-800',
                            rejected: 'bg-red-100 text-red-800',
                            fulfilled: 'bg-green-100 text-green-800',
                            received: 'bg-purple-100 text-purple-800'
                        };

                        const statusTranslations = {
                            pending: '{{ __('messages.pending') }}',
                            hr_approved: '{{ __('messages.hr_approved') }}',
                            rejected: '{{ __('messages.rejected') }}',
                            fulfilled: '{{ __('messages.fulfilled') }}',
                            received: '{{ __('messages.received') }}'
                        };

                        const html = `
                <div class="border-b pb-4 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.request_id') }}</p>
                            <p class="font-semibold">#${req.id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.status') }}</p>
                            <span class="px-2 py-1 text-xs rounded ${statusColors[req.status]}">${statusTranslations[req.status] || req.status}</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.requester') }}</p>
                            <p class="font-semibold">${req.user?.name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.department') }}</p>
                            <p class="font-semibold">${req.user?.department?.name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.date_created') }}</p>
                            <p class="font-semibold">${new Date(req.dateCreated || req.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">{{ __('messages.requested_items') }}</h4>
                    ${requestItems.length === 0 ? '<p class="text-gray-500">{{ __('messages.no_items') }}</p>' : `
                                                                                                                                                                                <table class="min-w-full">
                                                                                                                                                                                    <thead class="bg-gray-50">
                                                                                                                                                                                        <tr>
                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.image') }}</th>
                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.item') }}</th>
                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.price') }}</th>
                                                                                                                                                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.quantity') }}</th>
                                                                                                                                                                                        </tr>
                                                                                                                                                                                    </thead>
                                                                                                                                                                                    <tbody class="divide-y">
                                                                                                                                                                                        ${requestItems.map(ri => {
                                                                                                                                                                                            const item = ri.item || {};
                                                                                                                                                                                            return `
                                    <tr>
                                        <td class="px-4 py-2">
                                            ${item.image_path ? 
                                                `<img src="/storage/${item.image_path}" class="w-12 h-12 object-cover rounded">` : 
                                                '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'
                                            }
                                        </td>
                                        <td class="px-4 py-2">${item.designation || '{{ __('messages.unknown_item') }}'}</td>
                                        <td class="px-4 py-2">{{ __('messages.currency') }} ${item.price ? parseFloat(item.price).toFixed(2) : 'N/A'}</td>
                                        <td class="px-4 py-2">${ri.quantity_requested}</td>
                                    </tr>
                                `
                    }).join('')
            }
            `}
                </div>
                ${(isHrManager || isStockManager) ? `
                            <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                                ${isHrManager && req.status === 'pending' ? `
                            <button onclick="approveRequest(${req.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.approve') }}</button>
                            <button onclick="rejectRequest(${req.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('messages.reject') }}</button>
                        ` : isStockManager && req.status === 'hr_approved' ? `
                            <button onclick="fulfillRequest(${req.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.fulfill') }}</button>
                        ` : ''}
                            </div>
                        ` : ''}
                        ${req.status === 'fulfilled' && (req.user_id === currentUserId || req.user?.id === currentUserId) && !req.confirmed_received_at ? `
                            <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                                <button onclick="confirmReceipt(${req.id})" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">{{ __('messages.confirm_receipt') }}</button>
                            </div>
                        ` : ''}
                        ${req.status === 'received' && req.confirmed_received_at ? `
                            <div class="mt-4 p-3 bg-gray-50 rounded text-sm text-gray-600">
                                <p>{{ __('messages.receipt_confirmed') }}: ${new Date(req.confirmed_received_at).toLocaleDateString()}</p>
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

            // Grid view functions for non-stock managers
            function renderItemsGrid() {
                filteredItems = allItems.filter(item => item.quantity > 0); // Only show available items
                const grid = document.getElementById('itemsGrid');

                if (filteredItems.length === 0) {
                    grid.innerHTML = '<p class="col-span-full text-center text-gray-500">{{ __('messages.no_items_available') }}</p>';
                    return;
                }

                grid.innerHTML = filteredItems.map(item => {
                    const inCart = cart[item.id];
                    return `
            <div class="border rounded-lg p-3 hover:shadow-md transition ${inCart ? 'ring-2 ring-indigo-500' : ''}">
                <div class="aspect-square mb-2 overflow-hidden rounded">
                    ${item.image_path ? 
                        `<img src="/storage/${item.image_path}" class="w-full h-full object-cover">` : 
                        '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">{{ __('messages.no_image') }}</div>'
                    }
                </div>
                <h4 class="font-semibold text-sm mb-1 truncate" title="${item.designation}">${item.designation}</h4>
                <p class="text-xs text-gray-500 mb-2">{{ __('messages.available') }}: ${parseFloat(item.quantity).toFixed(2)}</p>
                <p class="text-sm font-bold text-green-600 mb-2">{{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</p>
                ${inCart ? `
                                                                                                                                                                            <div class="flex items-center gap-2">
                                                                                                                                                                                <button onclick="decreaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">-</button>
                                                                                                                                                                                <input type="number" id="qty_${item.id}" value="${inCart.quantity}" min="1" max="${item.quantity}" class="w-16 px-2 py-1 border rounded text-center" onchange="updateQuantity(${item.id}, this.value)">
                                                                                                                                                                                <button onclick="increaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">+</button>
                                                                                                                                                                                <button onclick="removeFromCart(${item.id})" class="ml-auto text-red-600 hover:text-red-800 text-xs">{{ __('messages.remove') }}</button>
                                                                                                                                                                            </div>
                                                                                                                                                                        ` : `
                                                                                                                                                                             <button onclick="addToCart(${item.id})" class="w-full px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                                                                                                                                                 {{ __('messages.add_to_cart') }}
                                                                                                                                                                             </button>
                                                                                                                                                                        `}
            </div>
        `;
                }).join('');
            }

            function filterItems() {
                const search = document.getElementById('itemSearchInput').value.toLowerCase();
                filteredItems = allItems.filter(item => {
                    return item.quantity > 0 && item.designation.toLowerCase().includes(search);
                });

                const grid = document.getElementById('itemsGrid');
                if (filteredItems.length === 0) {
                    grid.innerHTML = '<p class="col-span-full text-center text-gray-500">{{ __('messages.no_items_found') }}</p>';
                    return;
                }

                grid.innerHTML = filteredItems.map(item => {
                    const inCart = cart[item.id];
                    return `
            <div class="border rounded-lg p-3 hover:shadow-md transition ${inCart ? 'ring-2 ring-indigo-500' : ''}">
                <div class="aspect-square mb-2 overflow-hidden rounded">
                    ${item.image_path ? 
                        `<img src="/storage/${item.image_path}" loading="lazy" class="w-full h-full object-cover">` : 
                        '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">{{ __('messages.no_image') }}</div>'
                    }
                </div>
                <h4 class="font-semibold text-sm mb-1 truncate" title="${item.designation}">${item.designation}</h4>
                <p class="text-xs text-gray-500 mb-2">{{ __('messages.available') }}: ${parseFloat(item.quantity).toFixed(2)}</p>
                <p class="text-sm font-bold text-green-600 mb-2">{{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</p>
                ${inCart ? `
                                                                                                                                                                            <div class="flex items-center gap-2">
                                                                                                                                                                                <button onclick="decreaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">-</button>
                                                                                                                                                                                <input type="number" id="qty_${item.id}" value="${inCart.quantity}" min="1" max="${item.quantity}" class="w-16 px-2 py-1 border rounded text-center" onchange="updateQuantity(${item.id}, this.value)">
                                                                                                                                                                                <button onclick="increaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">+</button>
                                                                                                                                                                                <button onclick="removeFromCart(${item.id})" class="ml-auto text-red-600 hover:text-red-800 text-xs">{{ __('messages.remove') }}</button>
                                                                                                                                                                            </div>
                                                                                                                                                                        ` : `
                                                                                                                                                                             <button onclick="addToCart(${item.id})" class="w-full px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                                                                                                                                                 {{ __('messages.add_to_cart') }}
                                                                                                                                                                             </button>
                                                                                                                                                                        `}
            </div>
        `;
                }).join('');
            }

            function addToCart(itemId) {
                const item = allItems.find(i => i.id === itemId);
                if (item) {
                    cart[itemId] = {
                        item: item,
                        quantity: 1
                    };
                    renderItemsGrid();
                    updateCartDisplay();
                }
            }

            function removeFromCart(itemId) {
                delete cart[itemId];
                renderItemsGrid();
                updateCartDisplay();
            }

            function increaseQuantity(itemId) {
                const item = allItems.find(i => i.id === itemId);
                if (cart[itemId] && cart[itemId].quantity < item.quantity) {
                    cart[itemId].quantity++;
                    document.getElementById(`qty_${itemId}`).value = cart[itemId].quantity;
                    updateCartDisplay();
                }
            }

            function decreaseQuantity(itemId) {
                if (cart[itemId] && cart[itemId].quantity > 1) {
                    cart[itemId].quantity--;
                    document.getElementById(`qty_${itemId}`).value = cart[itemId].quantity;
                    updateCartDisplay();
                }
            }

            function updateQuantity(itemId, value) {
                const item = allItems.find(i => i.id === itemId);
                const qty = parseInt(value);
                if (qty >= 1 && qty <= item.quantity) {
                    cart[itemId].quantity = qty;
                    updateCartDisplay();
                } else {
                    document.getElementById(`qty_${itemId}`).value = cart[itemId].quantity;
                }
            }

            function updateCartDisplay() {
                const cartCount = Object.keys(cart).length;
                document.getElementById('cartCount').textContent = cartCount;

                const cartContainer = document.getElementById('cartItems');
                if (cartCount === 0) {
                    cartContainer.innerHTML = '<p class="text-gray-500 text-sm">{{ __('messages.no_items_selected') }}</p>';
                    return;
                }

                cartContainer.innerHTML = Object.values(cart).map(({
                    item,
                    quantity
                }) => `
        <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
            <div class="flex items-center gap-2">
                ${item.image_path ? 
                    `<img src="/storage/${item.image_path}" class="w-10 h-10 object-cover rounded">` : 
                    '<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'
                }
                <div>
                    <p class="text-sm font-semibold">${item.designation}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.qty') }}: ${quantity} × {{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</p>
                </div>
            </div>
            <button onclick="removeFromCart(${item.id})" class="text-red-600 hover:text-red-800 text-sm">×</button>
        </div>
    `).join('');
            }

            function submitCartRequest() {
                const items = Object.values(cart).map(({
                    item,
                    quantity
                }) => ({
                    item_id: item.id,
                    quantity_requested: quantity
                }));

                if (items.length === 0) {
                    alert('{{ __('messages.select_at_least_one_item') }}');
                    return;
                }

                fetch('/api/requests', {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({
                            items
                        })
                    })
                    .then(res => res.json())
                    .then(() => {
                        closeCreateModal();
                        loadRequests();
                        // alert('Request created successfully!');
                    })
                    .catch(err => {
                        console.error(err);
                        alert('{{ __('messages.error_message') }}');
                    });
            }
        </script>
    @endpush
@endsection
