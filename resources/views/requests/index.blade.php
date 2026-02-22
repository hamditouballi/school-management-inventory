@extends('layouts.app')

@section('title', __('messages.requests'))

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.requests') }}</h1>
            <p class="text-gray-600">{{ __('messages.view_and_manage_item_requests') }}</p>
        </div>
        @if (auth()->user()->role !== 'stock_manager')
            <button onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                {{ __('messages.create_request') }}
            </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.search') }}</label>
                <input type="text" id="searchInput" placeholder="{{ __('messages.search') }}"
                    class="w-full px-3 py-2 border rounded" oninput="applyFilters()">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.status') }}</label>
                <select id="statusFilter" class="w-full px-3 py-2 border rounded" onchange="applyFilters()">
                    <option value="">All Statuses</option>
                    <option value="pending">{{ __('messages.pending') }}</option>
                    <option value="approved">{{ __('messages.approved') }}</option>
                    <option value="rejected">{{ __('messages.rejected') }}</option>
                    <option value="fulfilled">{{ __('messages.fulfilled') }}</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.requester') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.items') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.date') }}</th>
                        @if (auth()->user()->role === 'stock_manager')
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ __('messages.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="requestsBody">
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Loading requests...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span
                    id="totalRequests">0</span> requests
            </div>
            <div id="pagination" class="flex gap-2">
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Request Details</h3>
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

    <!-- {{ __('messages.create_request') }} Modal - Stock Manager Form -->
    @if (auth()->user()->role === 'stock_manager')
        <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold mb-4">Create New Request</h3>
                <form id="createRequestForm" onsubmit="createRequest(event)">
                    <div id="itemsList" class="space-y-4 mb-4">
                        <div class="border p-4 rounded">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Item</label>
                                    <select name="items[0][item_id]" required class="w-full px-3 py-2 border rounded">
                                        <option value="">Select Item...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Quantity</label>
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
                    <h3 class="text-xl font-bold">Select Items for Request</h3>
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
                    <h4 class="font-semibold mb-3">Selected Items (<span id="cartCount">0</span>)</h4>
                    <div id="cartItems" class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                        <p class="text-gray-500 text-sm">No items selected</p>
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
            let cart = {}; // { itemId: { item: itemData, quantity: number } }

            document.addEventListener('DOMContentLoaded', () => {
                loadRequests();
                loadItemsForSelect();
            });

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
                            '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Error loading requests</td></tr>';
                    });
            }

            function applyFilters() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const status = document.getElementById('statusFilter').value;
                const dateFrom = document.getElementById('dateFrom').value;

                filteredRequests = allRequests.filter(req => {
                    let match = true;

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
                const start = (currentPage - 1) * requestsPerPage;
                const end = start + requestsPerPage;
                const pageRequests = filteredRequests.slice(start, end);

                const tbody = document.getElementById('requestsBody');
                const canManage = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};

                if (pageRequests.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No requests found</td></tr>';
                } else {
                    tbody.innerHTML = pageRequests.map(req => {
                        const statusColors = {
                            pending: 'bg-yellow-100 text-yellow-800',
                            approved: 'bg-blue-100 text-blue-800',
                            rejected: 'bg-red-100 text-red-800',
                            fulfilled: 'bg-green-100 text-green-800'
                        };

                        // Handle both snake_case and camelCase from API
                        const requestItems = req.request_items || req.requestItems || [];
                        const userName = req.user?.name || 'N/A';
                        const deptName = req.user?.department?.name || 'N/A';
                        const dateCreated = req.dateCreated || req.date_created || req.created_at;

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
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded ${statusColors[req.status] || 'bg-gray-100 text-gray-800'}">${
                        req.status == "pending" 
                            ? "{{ __('messages.pending') }}" 
                            : req.status == "approved" 
                                ? "{{ __('messages.approved') }}" 
                                : req.status == "rejected" 
                                    ? "{{ __('messages.rejected') }}" 
                                    : req.status == "fulfilled" 
                                        ? "{{ __('messages.fulfilled') }}" 
                                        : req.status
                    }</span>
                </td>
                <td class="px-6 py-4">${dateCreated ? new Date(dateCreated).toLocaleDateString() : 'N/A'}</td>
                ${canManage && req.status === 'pending' ? `
                                                                                                                                                                <td class="px-6 py-4">
                                                                                                                                                                    <button onclick="approveRequest(${req.id})" class="text-green-600 hover:text-green-800 mr-2">{{ __('messages.approve') }}</button>
                                                                                                                                                                    <button onclick="rejectRequest(${req.id})" class="text-red-600 hover:text-red-800">{{ __('messages.reject') }}</button>
                                                                                                                                                                </td>
                                                                                                                                                                ` : canManage && req.status === 'approved' ? `
                                                                                                                                                                <td class="px-6 py-4">
                                                                                                                                                                    <button onclick="fulfillRequest(${req.id})" class="text-green-600 hover:text-indigo-800">{{ __('messages.fulfill') }}</button>
                                                                                                                                                                </td>
                                                                                                                                                                ` : canManage ? '<td class="px-6 py-4">-</td>' : ''}
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
                    select.innerHTML = '<option value="">Select Item...</option>' +
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
                        <label class="block text-sm font-medium mb-1">Item</label>
                        <select name="items[0][item_id]" required class="w-full px-3 py-2 border rounded"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Quantity</label>
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
                <label class="block text-sm font-medium mb-1">Item</label>
                <select name="items[${itemCounter}][item_id]" required class="w-full px-3 py-2 border rounded"></select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Quantity</label>
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
                        Notification.success('Request created successfully!');
                    })
                    .catch(err => Notification.error('Error creating request'));
            }

            function approveRequest(id) {
                updateStatus(id, 'approved');
            }

            function rejectRequest(id) {
                if (confirm('Reject this request?')) {
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
                    .catch(err => Notification.error('Error updating request'));
            }

            function fulfillRequest(id) {
                if (confirm('Fulfill this request? This will decrease stock and generate Bon de Sortie.')) {
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
                                Notification.success('Request fulfilled successfully!');
                                loadRequests();
                            }
                        })
                        .catch(err => Notification.error('Error fulfilling request'));
                }
            }

            function viewRequestDetails(id) {
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsContent').innerHTML = '<p class="text-gray-500">Loading...</p>';

                const canManage = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};

                fetch(`/api/requests/${id}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(req => {
                        const requestItems = req.request_items || req.requestItems || [];
                        const statusColors = {
                            pending: 'bg-yellow-100 text-yellow-800',
                            approved: 'bg-blue-100 text-blue-800',
                            rejected: 'bg-red-100 text-red-800',
                            fulfilled: 'bg-green-100 text-green-800'
                        };

                        const html = `
                <div class="border-b pb-4 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Request ID</p>
                            <p class="font-semibold">#${req.id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.status') }}</p>
                            <span class="px-2 py-1 text-xs rounded ${statusColors[req.status]}">${req.status}</span>
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
                            <p class="text-sm text-gray-500">Date Created</p>
                            <p class="font-semibold">${new Date(req.dateCreated || req.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Requested Items</h4>
                    ${requestItems.length === 0 ? '<p class="text-gray-500">No items</p>' : `
                                                                                                                                                                        <table class="min-w-full">
                                                                                                                                                                            <thead class="bg-gray-50">
                                                                                                                                                                                <tr>
                                                                                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Image</th>
                                                                                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                                                                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Price</th>
                                                                                                                                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quantity</th>
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
                                                '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">No Image</div>'
                                            }
                                        </td>
                                        <td class="px-4 py-2">${item.designation || 'Unknown Item'}</td>
                                        <td class="px-4 py-2">{{ __('messages.currency') }} ${item.price ? parseFloat(item.price).toFixed(2) : 'N/A'}</td>
                                        <td class="px-4 py-2">${ri.quantity_requested}</td>
                                    </tr>
                                `
                    }).join('')
            }
            `}
                </div>
                ${canManage ? `
                                                                                                                                                                    <div class="mt-6 pt-4 border-t flex gap-3 justify-end">
                                                                                                                                                                        ${req.status === 'pending' ? `
                            <button onclick="approveRequest(${req.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.approve') }}</button>
                            <button onclick="rejectRequest(${req.id})" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('messages.reject') }}</button>
                        ` : req.status === 'approved' ? `
                            <button onclick="fulfillRequest(${req.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.fulfill') }}</button>
                        ` : ''}
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

            // Grid view functions for non-stock managers
            function renderItemsGrid() {
                filteredItems = allItems.filter(item => item.quantity > 0); // Only show available items
                const grid = document.getElementById('itemsGrid');

                if (filteredItems.length === 0) {
                    grid.innerHTML = '<p class="col-span-full text-center text-gray-500">No items available</p>';
                    return;
                }

                grid.innerHTML = filteredItems.map(item => {
                    const inCart = cart[item.id];
                    return `
            <div class="border rounded-lg p-3 hover:shadow-md transition ${inCart ? 'ring-2 ring-indigo-500' : ''}">
                <div class="aspect-square mb-2 overflow-hidden rounded">
                    ${item.image_path ? 
                        `<img src="/storage/${item.image_path}" class="w-full h-full object-cover">` : 
                        '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">No Image</div>'
                    }
                </div>
                <h4 class="font-semibold text-sm mb-1 truncate" title="${item.designation}">${item.designation}</h4>
                <p class="text-xs text-gray-500 mb-2">Available: ${parseFloat(item.quantity).toFixed(2)}</p>
                <p class="text-sm font-bold text-green-600 mb-2">{{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</p>
                ${inCart ? `
                                                                                                                                                                    <div class="flex items-center gap-2">
                                                                                                                                                                        <button onclick="decreaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">-</button>
                                                                                                                                                                        <input type="number" id="qty_${item.id}" value="${inCart.quantity}" min="1" max="${item.quantity}" class="w-16 px-2 py-1 border rounded text-center" onchange="updateQuantity(${item.id}, this.value)">
                                                                                                                                                                        <button onclick="increaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">+</button>
                                                                                                                                                                        <button onclick="removeFromCart(${item.id})" class="ml-auto text-red-600 hover:text-red-800 text-xs">Remove</button>
                                                                                                                                                                    </div>
                                                                                                                                                                ` : `
                                                                                                                                                                    <button onclick="addToCart(${item.id})" class="w-full px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                                                                                                                                        Add to Cart
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
                    grid.innerHTML = '<p class="col-span-full text-center text-gray-500">No items found</p>';
                    return;
                }

                grid.innerHTML = filteredItems.map(item => {
                    const inCart = cart[item.id];
                    return `
            <div class="border rounded-lg p-3 hover:shadow-md transition ${inCart ? 'ring-2 ring-indigo-500' : ''}">
                <div class="aspect-square mb-2 overflow-hidden rounded">
                    ${item.image_path ? 
                        `<img src="/storage/${item.image_path}" class="w-full h-full object-cover">` : 
                        '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">No Image</div>'
                    }
                </div>
                <h4 class="font-semibold text-sm mb-1 truncate" title="${item.designation}">${item.designation}</h4>
                <p class="text-xs text-gray-500 mb-2">Available: ${parseFloat(item.quantity).toFixed(2)}</p>
                <p class="text-sm font-bold text-green-600 mb-2">{{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</p>
                ${inCart ? `
                                                                                                                                                                    <div class="flex items-center gap-2">
                                                                                                                                                                        <button onclick="decreaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">-</button>
                                                                                                                                                                        <input type="number" id="qty_${item.id}" value="${inCart.quantity}" min="1" max="${item.quantity}" class="w-16 px-2 py-1 border rounded text-center" onchange="updateQuantity(${item.id}, this.value)">
                                                                                                                                                                        <button onclick="increaseQuantity(${item.id})" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">+</button>
                                                                                                                                                                        <button onclick="removeFromCart(${item.id})" class="ml-auto text-red-600 hover:text-red-800 text-xs">Remove</button>
                                                                                                                                                                    </div>
                                                                                                                                                                ` : `
                                                                                                                                                                    <button onclick="addToCart(${item.id})" class="w-full px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                                                                                                                                        Add to Cart
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
                    cartContainer.innerHTML = '<p class="text-gray-500 text-sm">No items selected</p>';
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
                    '<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">No Img</div>'
                }
                <div>
                    <p class="text-sm font-semibold">${item.designation}</p>
                    <p class="text-xs text-gray-500">Qty: ${quantity} × {{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</p>
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
                    alert('Please select at least one item');
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
                        alert('Error creating request');
                    });
            }
        </script>
    @endpush
@endsection
