@extends('layouts.app')

@section('title', __('messages.items_management'))

@section('content')
    <!-- Category Management Section -->
    @if (auth()->user()->role === 'stock_manager')
        <div class="mb-6 bg-white rounded-lg shadow p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">{{ __('messages.categories') }}</h2>
                <button onclick="showCategoryModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    + {{ __('messages.add_category') }}
                </button>
            </div>
            <div id="categoriesList" class="flex flex-wrap gap-2">
                <span class="text-gray-500">{{ __('messages.loading') }}...</span>
            </div>
        </div>
    @endif

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.items_management') }}</h1>
            <p class="text-gray-600">{{ __('messages.manage_inventory') }}</p>
        </div>
        @if (auth()->user()->role === 'stock_manager')
            <button onclick="showAddModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                {{ __('messages.add_item') }}
            </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.search') }}</label>
                <input type="text" id="searchInput" placeholder="{{ __('messages.search') }}..."
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                    oninput="applyFilters()">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.status') }}</label>
                <select id="statusFilter"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                    onchange="applyFilters()">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="in_stock">{{ __('messages.in_stock') }}</option>
                    <option value="low_stock">{{ __('messages.low_stock') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.category') }}</label>
                <select id="categoryFilter"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                    onchange="applyFilters()">
                    <option value="">{{ __('messages.all_categories') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
                <select id="quantityFilter"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                    onchange="applyFilters()">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="0-50">0 - 50</option>
                    <option value="50-200">50 - 200</option>
                    <option value="200+">200+</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.image') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.designation') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.description') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.quantity') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.unit') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.status') }}</th>
                        @if (auth()->user()->role === 'stock_manager')
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ __('messages.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="itemsBody">
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.loading') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t flex justify-between items-center">
            <div class="text-sm text-gray-700">
                {{ __('messages.showing') }} <span id="showingFrom">0</span> {{ __('messages.to') }} <span id="showingTo">0</span> {{ __('messages.of') }} <span id="totalItems">0</span>
                {{ __('messages.items_found') }}
            </div>
            <div class="flex gap-2" id="pagination"></div>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4" id="categoryModalTitle">{{ __('messages.add_category') }}</h3>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="categoryId">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.category_name') }}</label>
                    <input type="text" id="categoryName" required class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.description') }}</label>
                    <textarea id="categoryDescription" class="w-full px-3 py-2 border rounded"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCategoryModal()"
                        class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="itemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4" id="modalTitle">{{ __('messages.add_item') }}</h3>
            <form id="itemForm" onsubmit="saveItem(event)" enctype="multipart/form-data">
                <input type="hidden" id="itemId">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.designation') }}</label>
                    <input type="text" id="designation" required class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.description') }}</label>
                    <textarea id="description" class="w-full px-3 py-2 border rounded"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.image') }}</label>
                    <div class="flex gap-2">
                        <input type="file" id="image" accept="image/*" capture="environment" class="w-full px-3 py-2 border rounded">
                        <button type="button" onclick="showPhoneUploadModal('item_image', 0)" class="px-3 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm whitespace-nowrap">
                            📱 {{ __('messages.upload_from_phone') }}
                        </button>
                    </div>
                    <img id="imagePreview" class="mt-2 max-w-full h-32 object-cover hidden">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
                        <input type="number" id="quantity" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('messages.unit') }}</label>
                        <input type="text" id="unit" required class="w-full px-3 py-2 border rounded">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('messages.category') }}</label>
                        <select id="category_id" class="w-full px-3 py-2 border rounded">
                            <option value="">-- {{ __('messages.select_category') }} --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('messages.low_stock_threshold') }}</label>
                        <input type="number" id="low_stock_threshold" value="50"
                            class="w-full px-3 py-2 border rounded">
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeItemModal()"
                        class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const STORAGE_URL = "{{ asset('storage') }}";
            const token = '{{ session('api_token') }}';
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            };
            let allItems = [];
            let allCategories = [];
            let filteredItems = [];
            let currentPage = 1;
            const itemsPerPage = 10;

            document.addEventListener('DOMContentLoaded', () => {
                loadItems();
                loadCategories();
            });

            // ============ CATEGORIES ============
            function loadCategories() {
                fetch('/api/categories', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allCategories = data;
                        renderCategories();
                        populateCategorySelects();
                    });
            }

            function renderCategories() {
                const container = document.getElementById('categoriesList');
                if (allCategories.length === 0) {
                    container.innerHTML = '<span class="text-gray-500 text-sm">{{ __('messages.no_categories') }}</span>';
                    return;
                }

                const canEdit = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};
                container.innerHTML = allCategories.map(cat => `
                    <div class="inline-flex items-center gap-2 bg-gray-100 px-3 py-1 rounded-full">
                        <span class="text-sm font-medium">${cat.name}</span>
                        <span class="text-xs text-gray-500">(${cat.items_count || 0})</span>
                        ${canEdit ? `
                            <button onclick="editCategory(${cat.id})" class="text-blue-600 hover:text-blue-800 text-xs">✎</button>
                            <button onclick="deleteCategory(${cat.id}, '${cat.name}', ${cat.items_count || 0})" class="text-red-600 hover:text-red-800 text-xs">×</button>
                        ` : ''}
                    </div>
                `).join('');
            }

            function populateCategorySelects() {
                const categorySelect = document.getElementById('category_id');
                const categoryFilter = document.getElementById('categoryFilter');
                
                // Clear existing options except first
                categorySelect.innerHTML = '<option value="">-- {{ __('messages.select_category') }} --</option>';
                categoryFilter.innerHTML = '<option value="">{{ __('messages.all_categories') }}</option>';

                allCategories.forEach(cat => {
                    categorySelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                    categoryFilter.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                });
            }

            function showCategoryModal(id = null) {
                document.getElementById('categoryModalTitle').textContent = id ? "{{ __('messages.edit_category') }}" : "{{ __('messages.add_category') }}";
                document.getElementById('categoryForm').reset();
                document.getElementById('categoryId').value = id || '';
                
                if (id) {
                    const cat = allCategories.find(c => c.id === id);
                    if (cat) {
                        document.getElementById('categoryName').value = cat.name;
                        document.getElementById('categoryDescription').value = cat.description || '';
                    }
                }
                
                document.getElementById('categoryModal').classList.remove('hidden');
            }

            function closeCategoryModal() {
                document.getElementById('categoryModal').classList.add('hidden');
            }

            function editCategory(id) {
                showCategoryModal(id);
            }

            function saveCategory(e) {
                e.preventDefault();
                const id = document.getElementById('categoryId').value;
                const name = document.getElementById('categoryName').value;
                const description = document.getElementById('categoryDescription').value;

                const url = id ? `/api/categories/${id}` : '/api/categories';
                const method = id ? 'PUT' : 'POST';

                fetch(url, {
                        method,
                        headers,
                        body: JSON.stringify({ name, description })
                    })
                    .then(res => res.json())
                    .then(data => {
                        closeCategoryModal();
                        loadCategories();
                        Notification.success(id ? "{{ __('messages.category_updated') }}" : "{{ __('messages.category_added') }}");
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error("{{ __('messages.error_saving_category') }}");
                    });
            }

            function deleteCategory(id, name, itemsCount) {
                if (itemsCount > 0) {
                    Notification.error("{{ __('messages.cannot_delete_category_with_items') }}");
                    return;
                }
                if (!confirm(`{{ __('messages.delete_category_confirm') }} "${name}"?`)) return;

                fetch(`/api/categories/${id}`, {
                        method: 'DELETE',
                        headers
                    })
                    .then(res => res.json())
                    .then(() => {
                        loadCategories();
                        Notification.success("{{ __('messages.category_deleted') }}");
                    })
                    .catch(err => Notification.error("{{ __('messages.error_deleting_category') }}"));
            }

            // ============ ITEMS ============
            function loadItems() {
                fetch('/api/items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allItems = data;
                        filteredItems = data;
                        renderItems();
                    })
                    .catch(() => {
                        document.getElementById('itemsBody').innerHTML =
                            '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">{{ __('messages.error_loading') }}</td></tr>';
                    });
            }

            function applyFilters() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const status = document.getElementById('statusFilter').value;
                const categoryId = document.getElementById('categoryFilter').value;
                const quantityRange = document.getElementById('quantityFilter').value;

                filteredItems = allItems.filter(item => {
                    let match = true;

                    if (search && !item.designation.toLowerCase().includes(search) &&
                        !(item.description || '').toLowerCase().includes(search)) {
                        match = false;
                    }

                    if (status === 'low_stock' && !item.is_low_stock) match = false;
                    if (status === 'in_stock' && item.is_low_stock) match = false;

                    if (categoryId && item.category_id != categoryId) match = false;

                    if (quantityRange) {
                        const qty = parseFloat(item.quantity);
                        if (quantityRange === '0-50' && (qty < 0 || qty > 50)) match = false;
                        if (quantityRange === '50-200' && (qty < 50 || qty > 200)) match = false;
                        if (quantityRange === '200+' && qty < 200) match = false;
                    }

                    return match;
                });

                currentPage = 1;
                renderItems();
            }

            function renderItems() {
                const start = (currentPage - 1) * itemsPerPage;
                const end = start + itemsPerPage;
                const pageItems = filteredItems.slice(start, end);

                const tbody = document.getElementById('itemsBody');
                const canEdit = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};

                if (pageItems.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ __('messages.no_data_found') }}</td></tr>';
                } else {
                    tbody.innerHTML = pageItems.map(item => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
    ${item.image_path ? 
        `<img src="${STORAGE_URL}/${item.image_path}" class="w-12 h-12 object-cover rounded">` : 
        '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'
    }
</td>
                <td class="px-6 py-4 font-medium">${item.designation}</td>
                <td class="px-6 py-4 text-sm">
                    ${item.category ? `<span class="bg-gray-100 px-2 py-1 rounded text-xs">${item.category.name}</span>` : '<span class="text-gray-400 text-xs">-</span>'}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">${item.description || '-'}</td>
                <td class="px-6 py-4"><span class="${item.is_low_stock ? 'text-red-600 font-semibold' : ''}">${item.quantity}</span></td>
                <td class="px-6 py-4">${item.unit}</td>
                <td class="px-6 py-4">
                    ${item.is_low_stock ? 
                        '<span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">{{ __('messages.low_stock') }}</span>' : 
                        '<span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">{{ __('messages.in_stock') }}</span>'
                    }
                </td>
                ${canEdit ? `
                                        <td class="px-6 py-4">
                                            <button onclick="editItem(${item.id})" class="text-green-600 hover:text-indigo-800 mr-2">{{ __('messages.edit') }}</button>
                                            <button onclick="deleteItem(${item.id}, '${item.designation}')" class="text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                                        </td>
                                        ` : ''}
            </tr>
        `).join('');
                }

                updatePagination();
            }

            function updatePagination() {
                const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
                const start = (currentPage - 1) * itemsPerPage + 1;
                const end = Math.min(currentPage * itemsPerPage, filteredItems.length);

                document.getElementById('showingFrom').textContent = filteredItems.length ? start : 0;
                document.getElementById('showingTo').textContent = end;
                document.getElementById('totalItems').textContent = filteredItems.length;

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
                renderItems();
            }

            function showAddModal() {
                document.getElementById('modalTitle').textContent = "{{ __('messages.add_item') }}";
                document.getElementById('itemForm').reset();
                document.getElementById('itemId').value = '';
                document.getElementById('imagePreview').classList.add('hidden');
                document.getElementById('itemModal').classList.remove('hidden');
            }

            function editItem(id) {
                const item = allItems.find(i => i.id === id);
                if (!item) return;

                document.getElementById('modalTitle').textContent = "{{ __('messages.edit') }} {{ __('messages.items') }}";
                document.getElementById('itemId').value = item.id;
                document.getElementById('designation').value = item.designation;
                document.getElementById('description').value = item.description || '';
                document.getElementById('quantity').value = item.quantity;
                document.getElementById('unit').value = item.unit;
                document.getElementById('category_id').value = item.category_id || '';
                document.getElementById('low_stock_threshold').value = item.low_stock_threshold;

                document.getElementById('image').value = '';

                if (item.image_path) {
                    document.getElementById('imagePreview').src = `/storage/${item.image_path}`;
                    document.getElementById('imagePreview').classList.remove('hidden');
                } else {
                    document.getElementById('imagePreview').classList.add('hidden');
                }

                document.getElementById('itemModal').classList.remove('hidden');
            }

            function closeItemModal() {
                document.getElementById('itemModal').classList.add('hidden');
            }

            function saveItem(e) {
                e.preventDefault();
                const id = document.getElementById('itemId').value;
                const formData = new FormData();

                formData.append('designation', document.getElementById('designation').value);
                formData.append('description', document.getElementById('description').value || '');
                formData.append('quantity', document.getElementById('quantity').value);
                formData.append('unit', document.getElementById('unit').value);
                const categoryId = document.getElementById('category_id').value;
                if (categoryId) {
                    formData.append('category_id', categoryId);
                }
                formData.append('low_stock_threshold', document.getElementById('low_stock_threshold').value || 50);

                const imageFile = document.getElementById('image').files[0];
                if (imageFile) {
                    formData.append('image', imageFile);
                } else if (document.getElementById('image').dataset.phoneUploadPath) {
                    formData.append('phone_upload_path', document.getElementById('image').dataset.phoneUploadPath);
                }

                const url = id ? `/api/items/${id}` : '/api/items';

                if (id) {
                    formData.append('_method', 'PUT');
                }

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(() => {
                        closeItemModal();
                        loadItems();
                        loadCategories();
                        Notification.success(id ? "{{ __('messages.item_updated_success') }}" :
                            "{{ __('messages.item_added_success') }}");
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error("{{ __('messages.error_saving_item') }}");
                    });
            }

            function deleteItem(id, name) {
                if (!confirm(`Delete ${name}?`)) return;

                fetch(`/api/items/${id}`, {
                        method: 'DELETE',
                        headers
                    })
                    .then(() => {
                        loadItems();
                        loadCategories();
                        Notification.success("{{ __('messages.item_deleted_success') }}");
                    })
                    .catch(err => Notification.error("{{ __('messages.error_deleting_item') }}"));
            }

            document.getElementById('image')?.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('imagePreview').src = e.target.result;
                        document.getElementById('imagePreview').classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Phone Upload Modal
            window.currentPhoneUploadContext = null;
            window.currentPhoneUploadTargetId = null;
            window.phoneUploadPollingInterval = null;

            function showPhoneUploadModal(context, targetId) {
                window.currentPhoneUploadContext = context;
                window.currentPhoneUploadTargetId = targetId;
                const modal = document.getElementById('phoneUploadModal');
                const qrContainer = document.getElementById('phoneUploadQR');
                const statusEl = document.getElementById('phoneUploadStatus');
                const imageContainer = document.getElementById('phoneUploadImageContainer');
                
                modal.classList.remove('hidden');
                qrContainer.innerHTML = '<p class="text-gray-500">{{ __('messages.loading') }}...</p>';
                statusEl.innerHTML = '';
                imageContainer.classList.add('hidden');
                
                // Get local IP and generate URL
                fetch('{{ url("/api/server-ip") }}', { headers })
                    .then(res => res.json())
                    .then(data => {
                        const localIp = data.ip || 'localhost';
                        const sessionKey = '{{ session()->getId() }}';
                        const uploadUrl = `http://${localIp}:8000/phone-upload/${context}/${targetId}?session=${sessionKey}`;
                        
                        // Generate QR code using QRious library (loaded from CDN)
                        try {
                            const canvas = document.createElement('canvas');
                            new QRious({
                                element: canvas,
                                value: uploadUrl,
                                size: 200,
                                level: 'M'
                            });
                            const img = document.createElement('img');
                            img.src = canvas.toDataURL();
                            img.className = 'mx-auto border-2 border-gray-300 rounded-lg';
                            qrContainer.innerHTML = '';
                            qrContainer.appendChild(img);
                            qrContainer.innerHTML += `<p class="text-xs text-gray-500 mt-2">{{ __('messages.or_open_url') }}</p><p class="text-xs text-blue-600 break-all font-mono bg-gray-50 p-1 rounded mt-1">${uploadUrl}</p>`;
                        } catch(e) {
                            qrContainer.innerHTML = `<p class="text-sm text-gray-600">${uploadUrl}</p>`;
                        }
                        
                        statusEl.innerHTML = '<p class="text-blue-600 text-sm">{{ __("messages.waiting_for_upload") }}</p>';
                        
                        // Start polling
                        if (window.phoneUploadPollingInterval) {
                            clearInterval(window.phoneUploadPollingInterval);
                        }
                        window.phoneUploadPollingInterval = setInterval(() => pollPhoneUploads(sessionKey), 3000);
                    })
                    .catch(err => {
                        qrContainer.innerHTML = `<p class="text-red-500">Error getting IP</p>`;
                    });
            }

            function pollPhoneUploads(sessionKey) {
                fetch(`/phone-uploads/${sessionKey}`, { headers })
                    .then(res => res.json())
                    .then(data => {
                        const imageContainer = document.getElementById('phoneUploadImageContainer');
                        const statusEl = document.getElementById('phoneUploadStatus');
                        
                        if (data.uploads && data.uploads.length > 0) {
                            const upload = data.uploads[0];
                            imageContainer.innerHTML = `
                                <img src="${upload.url}" data-upload-id="${upload.id}" class="max-h-48 rounded border mx-auto">
                                <div class="flex gap-2 mt-3 justify-center">
                                    <button onclick="usePhoneImage('${upload.url}', '${upload.id}')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                        {{ __('messages.use_image') }}
                                    </button>
                                    <button onclick="discardPhoneImage('${upload.id}', '${sessionKey}')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                        {{ __('messages.discard') }}
                                    </button>
                                </div>
                            `;
                            imageContainer.classList.remove('hidden');
                            statusEl.innerHTML = '<p class="text-green-600 text-sm">{{ __("messages.image_received") }}</p>';
                        }
                    })
                    .catch(err => console.error('Poll error:', err));
            }

            function usePhoneImage(url, uploadId) {
                const sessionKey = '{{ session()->getId() }}';
                
                // First promote the image to main storage
                fetch('/phone-uploads/promote', {
                    method: 'POST',
                    headers: {
                        ...headers,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        upload_id: uploadId,
                        session_key: sessionKey,
                        context: 'item_image'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Set the file path for the form submission
                        document.getElementById('image').dataset.phoneUploadPath = data.file_path;
                        
                        // Show preview
                        document.getElementById('imagePreview').src = data.url;
                        document.getElementById('imagePreview').classList.remove('hidden');
                        
                        closePhoneUploadModal();
                        Notification.success('{{ __("messages.image_uploaded_success") }}');
                    } else {
                        throw new Error(data.error || 'Failed to promote image');
                    }
                })
                .catch(err => {
                    console.error('Promote error:', err);
                    Notification.error('Failed to process image');
                });
            }

            function discardPhoneImage(uploadId, sessionKey) {
                fetch(`/phone-uploads/${uploadId}/received?session_key=${sessionKey}`, { method: 'POST', headers })
                    .then(() => {
                        document.getElementById('phoneUploadImageContainer').classList.add('hidden');
                        document.getElementById('phoneUploadStatus').innerHTML = '<p class="text-blue-600 text-sm">{{ __("messages.waiting_for_upload") }}</p>';
                    });
            }

            function closePhoneUploadModal() {
                document.getElementById('phoneUploadModal').classList.add('hidden');
                if (window.phoneUploadPollingInterval) {
                    clearInterval(window.phoneUploadPollingInterval);
                    window.phoneUploadPollingInterval = null;
                }
            }
        </script>

    <!-- Phone Upload Modal -->
    <div id="phoneUploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">{{ __('messages.upload_from_phone') }}</h3>
                <button onclick="closePhoneUploadModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="phoneUploadQR" class="flex justify-center mb-4"></div>
            <p id="phoneUploadStatus" class="text-center text-sm mb-4"></p>
            <div id="phoneUploadImageContainer" class="hidden text-center"></div>
            <p class="text-xs text-gray-500 text-center mt-4">
                {{ __('messages.scan_qr_or_enter_url') }}
            </p>
        </div>
    </div>
    @endpush
@endsection
