@extends('layouts.app')

@section('title', __('messages.supplier_management'))

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.supplier_management') }}</h1>
            <p class="text-gray-600">{{ __('messages.supplier_management') }}</p>
        </div>
        @if (auth()->user()->role === 'stock_manager')
            <button onclick="showAddSupplierModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                {{ __('messages.add_supplier') }}
            </button>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.supplier_name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.contact_info') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.supplier_notes') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.supplier_items') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="suppliersBody">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">{{ __('messages.loading') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div id="supplierModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4" id="supplierModalTitle">{{ __('messages.add_supplier') }}</h3>
            <form id="supplierForm" onsubmit="saveSupplier(event)">
                <input type="hidden" id="supplierId">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.supplier_name') }}</label>
                    <input type="text" id="supplierName" required class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.contact_info') }}</label>
                    <input type="text" id="supplierContact" class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('messages.supplier_notes') }}</label>
                    <textarea id="supplierNotes" class="w-full px-3 py-2 border rounded"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 border rounded">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Supplier Items Modal -->
    <div id="supplierItemsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">{{ __('messages.supplier_items_list') }} - <span id="supplierItemsName"></span></h3>
                <button onclick="closeSupplierItemsModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="supplierItemsList" class="mb-4 max-h-64 overflow-y-auto">
            </div>
            @if (auth()->user()->role === 'stock_manager')
                <div class="border-t pt-4">
                    <h4 class="font-medium mb-2">{{ __('messages.add_item_to_supplier') }}</h4>
                    <div class="flex gap-2">
                        <select id="newSupplierItem" class="flex-1 px-3 py-2 border rounded">
                            <option value="">{{ __('messages.select_item') }}...</option>
                        </select>
                        <input type="number" id="newSupplierItemPrice" step="0.01" placeholder="{{ __('messages.price') }}" class="w-32 px-3 py-2 border rounded">
                        <button onclick="addItemToSupplier()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.add') }}</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        let currentSupplierId = null;
        let allItems = [];
        let allSuppliers = [];
        const token = '{{ session('api_token') }}';
        const headers = {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadSuppliers();
            loadItems();
        });

        async function loadItems() {
            if (!token) return;
            try {
                const response = await fetch('/api/items', { headers });
                const data = await response.json();
                allItems = data.data || data;
            } catch (error) {
                console.error('Error loading items:', error);
            }
        }

        async function loadSuppliers() {
            try {
                if (!token) {
                    document.getElementById('suppliersBody').innerHTML = 
                        '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Not logged in. <a href="/login" class="underline">Login</a></td></tr>';
                    return;
                }
                const response = await fetch('/api/suppliers', { headers });
                
                console.log('Token:', token ? 'present' : 'missing');
                
                if (response.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error('HTTP ' + response.status);
                }
                const data = await response.json();
                console.log('Suppliers data:', data);
                allSuppliers = data.data || data;
                renderSuppliers(allSuppliers);
            } catch (error) {
                console.error('Error loading suppliers:', error);
                document.getElementById('suppliersBody').innerHTML = 
                    `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">{{ __('messages.error_loading') }}: ${error.message}</td></tr>`;
            }
        }

        function renderSuppliers(suppliers) {
            const tbody = document.getElementById('suppliersBody');
            if (!suppliers || suppliers.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">{{ __('messages.no_data_found') }}</td></tr>`;
                return;
            }
            
            tbody.innerHTML = suppliers.map(supplier => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">${escapeHtml(supplier.name)}</td>
                    <td class="px-6 py-4 text-gray-600">${escapeHtml(supplier.contact_info || '-')}</td>
                    <td class="px-6 py-4 text-gray-600">${escapeHtml(supplier.notes || '-')}</td>
                    <td class="px-6 py-4">
                        <button onclick="viewSupplierItems(${supplier.id})" class="text-green-600 hover:underline">
                            ${supplier.supplier_items_count || supplier.supplier_items?.length || 0} {{ __('messages.items') }}
                        </button>
                    </td>
                    <td class="px-6 py-4">
                        @if (auth()->user()->role === 'stock_manager' || auth()->user()->role === 'hr_manager')
                            <button onclick="viewSupplierStats(${supplier.id})" class="text-purple-600 hover:underline mr-2">{{ __('messages.view_stats') }}</button>
                        @endif
                        @if (auth()->user()->role === 'stock_manager')
                            <button onclick="editSupplier(${supplier.id})" class="text-blue-600 hover:underline mr-2">{{ __('messages.edit') }}</button>
                            <button onclick="deleteSupplier(${supplier.id})" class="text-red-600 hover:underline">{{ __('messages.delete') }}</button>
                        @endif
                    </td>
                </tr>
            `).join('');
        }

        function showAddSupplierModal() {
            document.getElementById('supplierModalTitle').textContent = '{{ __('messages.add_supplier') }}';
            document.getElementById('supplierId').value = '';
            document.getElementById('supplierForm').reset();
            document.getElementById('supplierModal').classList.remove('hidden');
        }

        function editSupplier(id) {
            const supplier = allSuppliers.find(s => s.id === id);
            if (!supplier) return;

            document.getElementById('supplierModalTitle').textContent = '{{ __('messages.edit') }}';
            document.getElementById('supplierId').value = supplier.id;
            document.getElementById('supplierName').value = supplier.name;
            document.getElementById('supplierContact').value = supplier.contact_info || '';
            document.getElementById('supplierNotes').value = supplier.notes || '';
            document.getElementById('supplierModal').classList.remove('hidden');
        }

        function closeSupplierModal() {
            document.getElementById('supplierModal').classList.add('hidden');
        }

        async function saveSupplier(event) {
            event.preventDefault();
            const id = document.getElementById('supplierId').value;
            const name = document.getElementById('supplierName').value;
            const contact_info = document.getElementById('supplierContact').value;
            const notes = document.getElementById('supplierNotes').value;

            try {
                const url = id ? `/api/suppliers/${id}` : '/api/suppliers';
                const method = id ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, contact_info, notes })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || 'Error saving supplier');
                }

                closeSupplierModal();
                loadSuppliers();
                Notification.success('{{ __('messages.supplier_created') }}');
            } catch (error) {
                Notification.error(error.message);
            }
        }

        async function deleteSupplier(id) {
            if (!confirm('{{ __('messages.confirm_delete') }}'.replace(':item', '{{ __('messages.supplier') }}'))) {
                return;
            }

            try {
                const response = await fetch(`/api/suppliers/${id}`, { 
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || '{{ __('messages.error_deleting') }}');
                }
                loadSuppliers();
                Notification.success('{{ __('messages.supplier_deleted') }}');
            } catch (error) {
                Notification.error(error.message);
            }
        }

        function viewSupplierItems(id) {
            currentSupplierId = id;
            const supplier = allSuppliers.find(s => s.id === id);
            if (!supplier) return;

            document.getElementById('supplierItemsName').textContent = supplier.name;
            
            const items = supplier.supplierItems || supplier.supplier_items || [];
            renderSupplierItems(items);
            
            const itemSelect = document.getElementById('newSupplierItem');
            itemSelect.innerHTML = '<option value="">{{ __('messages.select_item') }}...</option>' + 
                allItems.map(item => `<option value="${item.id}">${escapeHtml(item.designation)}</option>`).join('');
            
            document.getElementById('supplierItemsModal').classList.remove('hidden');
        }

        function renderSupplierItems(items) {
            const container = document.getElementById('supplierItemsList');
            if (!items || items.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">{{ __('messages.no_items') }}</p>';
                return;
            }

            container.innerHTML = `
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.item') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.price') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${items.map(item => `
                            <tr>
                                <td class="px-4 py-2">${escapeHtml(item.item?.designation || item.designation || 'Unknown')}</td>
                                <td class="px-4 py-2">${parseFloat(item.unit_price || item.pivot?.unit_price || 0).toFixed(2)} {{ __('messages.currency') }}</td>
                                <td class="px-4 py-2">
                                    @if (auth()->user()->role === 'stock_manager')
                                        <button onclick="editSupplierItem(${item.item_id || item.id}, ${item.unit_price || item.pivot?.unit_price})" class="text-blue-600 hover:underline mr-2">{{ __('messages.edit') }}</button>
                                        <button onclick="removeSupplierItem(${item.item_id || item.id})" class="text-red-600 hover:underline">{{ __('messages.delete') }}</button>
                                    @endif
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function closeSupplierItemsModal() {
            document.getElementById('supplierItemsModal').classList.add('hidden');
            currentSupplierId = null;
        }

        async function addItemToSupplier() {
            const itemId = document.getElementById('newSupplierItem').value;
            const unitPrice = document.getElementById('newSupplierItemPrice').value;

            if (!itemId || !unitPrice) {
                Notification.error('{{ __('messages.error_saving_supplier') }}');
                return;
            }

            try {
                const response = await fetch(`/api/suppliers/${currentSupplierId}/items`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ item_id: parseInt(itemId), unit_price: parseFloat(unitPrice) })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || 'Error adding item');
                }

                document.getElementById('newSupplierItemPrice').value = '';
                document.getElementById('newSupplierItem').value = '';
                loadSuppliers();
                const supplier = allSuppliers.find(s => s.id === currentSupplierId);
                viewSupplierItems(currentSupplierId);
                Notification.success('{{ __('messages.item_added_success') }}');
            } catch (error) {
                Notification.error(error.message);
            }
        }

        function editSupplierItem(itemId, currentPrice) {
            const newPrice = prompt('{{ __('messages.unit_price') }}:', currentPrice);
            if (newPrice === null) return;

            fetch(`/api/suppliers/${currentSupplierId}/items/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ unit_price: parseFloat(newPrice) })
            }).then(response => {
                if (!response.ok) throw new Error('Error updating price');
                loadSuppliers();
                viewSupplierItems(currentSupplierId);
                Notification.success('{{ __('messages.item_updated_success') }}');
            }).catch(error => {
                Notification.error(error.message);
            });
        }

        async function removeSupplierItem(itemId) {
            if (!confirm('{{ __('messages.confirm_delete') }}'.replace(':item', '{{ __('messages.item') }}'))) {
                return;
            }

            try {
                const response = await fetch(`/api/suppliers/${currentSupplierId}/items/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error removing item');
                
                loadSuppliers();
                viewSupplierItems(currentSupplierId);
                Notification.success('{{ __('messages.item_deleted_success') }}');
            } catch (error) {
                Notification.error(error.message);
            }
        }

        function viewSupplierStats(id) {
            window.location.href = `/suppliers/${id}`;
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
@endsection