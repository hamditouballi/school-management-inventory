@extends('layouts.app')

@section('title', __('messages.items_management'))

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.items_management') }}</h1>
        <p class="text-gray-600">{{ __('messages.manage_inventory') }}</p>
    </div>
    @if(auth()->user()->role === 'stock_manager')
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
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('messages.status') }}</label>
            <select id="statusFilter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">{{ __('messages.filter') }}</option>
                <option value="in_stock">{{ __('messages.in_stock') }}</option>
                <option value="low_stock">{{ __('messages.low_stock') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('messages.price') }}</label>
            <select id="priceFilter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">{{ __('messages.filter') }}</option>
                <option value="0-5">0 - 5 {{ __('messages.currency') }}</option>
                <option value="5-10">5 - 10 {{ __('messages.currency') }}</option>
                <option value="10+">10+ {{ __('messages.currency') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
            <select id="quantityFilter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">{{ __('messages.filter') }}</option>
                <option value="0-50">0 - 50</option>
                <option value="50-200">50 - 200</option>
                <option value="200+">200+</option>
            </select>
        </div>
    </div>
    <button onclick="applyFilters()" class="mt-4 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">{{ __('messages.filter') }}</button>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.image') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.designation') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.description') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.quantity') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.price') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.unit') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.status') }}</th>
                    @if(auth()->user()->role === 'stock_manager')
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200" id="itemsBody">
                <tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading items...</td></tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-6 py-4 border-t flex justify-between items-center">
        <div class="text-sm text-gray-700">
            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalItems">0</span> items
        </div>
        <div class="flex gap-2" id="pagination"></div>
    </div>
</div>

<!-- Add/Edit Modal -->
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
                <input type="file" id="image" accept="image/*" class="w-full px-3 py-2 border rounded">
                <img id="imagePreview" class="mt-2 max-w-full h-32 object-cover hidden">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }}</label>
                    <input type="number" id="quantity" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('messages.unit_price') }} ({{ __('messages.currency') }})</label>
                    <input type="number" step="0.01" id="price" required class="w-full px-3 py-2 border rounded">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('messages.unit') }}</label>
                    <input type="text" id="unit" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('messages.category') }}</label>
                    <select id="category" class="w-full px-3 py-2 border rounded">
                        <option value="">-- {{ __('messages.category') }} --</option>
                        <option value="FOURNITURES DE BUREAU">{{ __('messages.office_supplies') }}</option>
                        <option value="PRODUITS D'HYGIÈNE">{{ __('messages.hygiene_products') }}</option>
                        <option value="AUTRES">{{ __('messages.others') }}</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">{{ __('messages.low_stock_threshold') }}</label>
                <input type="number" id="low_stock_threshold" value="50" class="w-full px-3 py-2 border rounded">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeItemModal()" class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const token = '{{ session("api_token") }}';
const headers = { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' };
let allItems = [];
let filteredItems = [];
let currentPage = 1;
const itemsPerPage = 10;

document.addEventListener('DOMContentLoaded', loadItems);

function loadItems() {
    fetch('/api/items', { headers })
        .then(res => res.json())
        .then(data => {
            allItems = data;
            filteredItems = data;
            renderItems();
        })
        .catch(() => {
            document.getElementById('itemsBody').innerHTML = 
                '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading items</td></tr>';
        });
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const priceRange = document.getElementById('priceFilter').value;
    const quantityRange = document.getElementById('quantityFilter').value;
    
    filteredItems = allItems.filter(item => {
        let match = true;
        
        if (search && !item.designation.toLowerCase().includes(search) && 
            !(item.description || '').toLowerCase().includes(search)) {
            match = false;
        }
        
        if (status === 'low_stock' && !item.is_low_stock) match = false;
        if (status === 'in_stock' && item.is_low_stock) match = false;
        
        if (priceRange) {
            const price = parseFloat(item.price);
            if (priceRange === '0-5' && (price < 0 || price > 5)) match = false;
            if (priceRange === '5-10' && (price < 5 || price > 10)) match = false;
            if (priceRange === '10+' && price < 10) match = false;
        }
        
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
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No items found</td></tr>';
    } else {
        tbody.innerHTML = pageItems.map(item => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    ${item.image_path ? 
                        `<img src="/storage/${item.image_path}" class="w-12 h-12 object-cover rounded">` : 
                        '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">No Image</div>'
                    }
                </td>
                <td class="px-6 py-4 font-medium">${item.designation}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${item.description || '-'}</td>
                <td class="px-6 py-4"><span class="${item.is_low_stock ? 'text-red-600 font-semibold' : ''}">${item.quantity}</span></td>
                <td class="px-6 py-4">{{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</td>
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
        html += `<button onclick="changePage(${currentPage - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Previous</button>`;
    }
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<button onclick="changePage(${i})" class="px-3 py-1 border rounded ${i === currentPage ? 'bg-green-600 text-white' : 'hover:bg-gray-100'}">${i}</button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += '<span class="px-2">...</span>';
        }
    }
    
    if (currentPage < totalPages) {
        html += `<button onclick="changePage(${currentPage + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Next</button>`;
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
    document.getElementById('price').value = item.price;
    document.getElementById('unit').value = item.unit;
    document.getElementById('category').value = item.category || '';
    document.getElementById('low_stock_threshold').value = item.low_stock_threshold;
    
    // Clear the file input
    document.getElementById('image').value = '';
    
    // Show existing image if available
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
    formData.append('price', document.getElementById('price').value);
    formData.append('unit', document.getElementById('unit').value);
    formData.append('category', document.getElementById('category').value || '');
    formData.append('low_stock_threshold', document.getElementById('low_stock_threshold').value || 50);
    
    const imageFile = document.getElementById('image').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    const url = id ? `/api/items/${id}` : '/api/items';
    let method = id ? 'POST' : 'POST'; // Use POST for both, with _method for update
    
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
        Notification.success(id ? "{{ __('messages.item_updated_success') }}" : "{{ __('messages.item_added_success') }}");
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
        Notification.success("{{ __('messages.item_deleted_success') }}");
    })
    .catch(err => Notification.error("{{ __('messages.error_deleting_item') }}"));
}

// Image preview
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
</script>
@endpush
@endsection
