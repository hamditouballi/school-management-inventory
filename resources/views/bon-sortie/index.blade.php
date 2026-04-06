@extends('layouts.app')

@section('title', __('messages.bon_de_sortie'))

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.bon_de_sortie') }}</h1>
            <p class="text-gray-600">
                {{ __('messages.view_and_manage_bon_sortie') }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.image') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.id') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.item') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.requester') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.quantity') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.responsible_stock') }}</th>
                        @if (auth()->user()->role === 'stock_manager')
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ __('messages.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="bonSortieBody">
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">{{ __('messages.loading') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bon Sortie Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">{{ __('messages.bon_sortie_details') }}</h3>
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

    @if (auth()->user()->role === 'stock_manager')
        <!-- Create/Edit Bon Sortie Modal -->
        <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold" id="modalTitle">{{ __('messages.create_bon_sortie') }}</h3>
                    <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>
                <form id="bonSortieForm" onsubmit="saveBonSortie(event)">
                    <input type="hidden" id="bonSortieId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">{{ __('messages.request') }} *</label>
                        <select id="requestSelect" required class="w-full px-3 py-2 border rounded">
                            <option value="">{{ __('messages.select_request') }}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">{{ __('messages.item') }} *</label>
                        <select id="itemSelect" required class="w-full px-3 py-2 border rounded">
                            <option value="">{{ __('messages.select_item') }}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">{{ __('messages.quantity') }} *</label>
                        <input type="number" step="0.01" id="quantity" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">{{ __('messages.date') }} *</label>
                        <input type="date" id="bonSortieDate" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border rounded hover:bg-gray-50">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            let STORAGE_URL = null;
            const token = '{{ session('api_token') }}';
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            };
            const isStockManager = {{ auth()->user()->role === 'stock_manager' ? 'true' : 'false' }};
            let allBonSorties = [];

            async function initStorageUrl() {
                try {
                    const res = await fetch('{{ url("/api/server-ip") }}', { headers });
                    const data = await res.json();
                    const localIp = data.ip || 'localhost';
                    STORAGE_URL = `http://${localIp}:8000/storage`;
                } catch {
                    STORAGE_URL = '/storage';
                }
            }

            document.addEventListener('DOMContentLoaded', async function() {
                await initStorageUrl();
                loadBonSorties();
                if (isStockManager) {
                    loadRequestsForSelect();
                    loadItemsForSelect();
                }
            });

            function loadBonSorties() {
                fetch('/api/bon-sortie', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allBonSorties = data;
                        renderBonSorties();
                    })
                    .catch(() => {
                        document.getElementById('bonSortieBody').innerHTML =
                            '<tr><td colspan="9" class="px-6 py-4 text-center text-red-500">{{ __('messages.error_loading') }}</td></tr>';
                    });
            }

            function renderBonSorties() {
                const tbody = document.getElementById('bonSortieBody');

                if (allBonSorties.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-4 text-center text-gray-500">{{ __('messages.no_data_found') }}</td></tr>';
                    return;
                }

                tbody.innerHTML = allBonSorties.map(bs => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            ${bs.item?.image_path ? 
                                `<img src="${STORAGE_URL}/${bs.item.image_path}" class="w-12 h-12 object-cover rounded" onerror="this.src='/images/placeholder.png'">` : 
                                '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">{{ __('messages.no_image') }}</div>'}
                        </td>
                        <td class="px-6 py-4">#${bs.id}</td>
                        <td class="px-6 py-4">${bs.item?.designation || 'N/A'}</td>
                        <td class="px-6 py-4">${bs.request?.user?.name || 'N/A'}</td>
                        <td class="px-6 py-4">${bs.request?.user?.department?.name || 'N/A'}</td>
                        <td class="px-6 py-4">${parseFloat(bs.quantity).toFixed(2)} ${bs.item?.unit || ''}</td>
                        <td class="px-6 py-4">${new Date(bs.date).toLocaleDateString()}</td>
                        <td class="px-6 py-4">${bs.responsible_stock?.name || 'N/A'}</td>
                        ${isStockManager ? `
                            <td class="px-6 py-4">
                                <button onclick="editBonSortie(${bs.id})" class="text-blue-600 hover:text-blue-800 mr-2">{{ __('messages.edit') }}</button>
                                <button onclick="deleteBonSortie(${bs.id})" class="text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                            </td>
                        ` : ''}
                    </tr>
                `).join('');
            }

            function loadRequestsForSelect() {
                fetch('/api/requests', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const select = document.getElementById('requestSelect');
                        select.innerHTML = '<option value="">{{ __('messages.select_request') }}</option>';
                        data.forEach(req => {
                            select.innerHTML += `<option value="${req.id}">#${req.id} - ${req.user?.name || 'N/A'}</option>`;
                        });
                    });
            }

            function loadItemsForSelect() {
                fetch('/api/items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const select = document.getElementById('itemSelect');
                        select.innerHTML = '<option value="">{{ __('messages.select_item') }}</option>';
                        data.forEach(item => {
                            select.innerHTML += `<option value="${item.id}">${item.designation} (${item.quantity} ${item.unit})</option>`;
                        });
                    });
            }

            function showCreateModal() {
                document.getElementById('modalTitle').textContent = '{{ __('messages.create_bon_sortie') }}';
                document.getElementById('bonSortieId').value = '';
                document.getElementById('bonSortieForm').reset();
                document.getElementById('bonSortieDate').value = new Date().toISOString().split('T')[0];
                document.getElementById('createModal').classList.remove('hidden');
            }

            function closeCreateModal() {
                document.getElementById('createModal').classList.add('hidden');
            }

            function editBonSortie(id) {
                const bs = allBonSorties.find(b => b.id === id);
                if (!bs) return;

                document.getElementById('modalTitle').textContent = '{{ __('messages.edit_bon_sortie') }}';
                document.getElementById('bonSortieId').value = bs.id;
                document.getElementById('requestSelect').value = bs.request_id;
                document.getElementById('itemSelect').value = bs.item_id;
                document.getElementById('quantity').value = bs.quantity;
                document.getElementById('bonSortieDate').value = bs.date;

                document.getElementById('createModal').classList.remove('hidden');
            }

            function saveBonSortie(event) {
                event.preventDefault();

                const id = document.getElementById('bonSortieId').value;
                const data = {
                    request_id: document.getElementById('requestSelect').value,
                    item_id: document.getElementById('itemSelect').value,
                    quantity: parseFloat(document.getElementById('quantity').value),
                    date: document.getElementById('bonSortieDate').value
                };

                const url = id ? `/api/bon-sortie/${id}` : '/api/bon-sortie';
                const method = id ? 'PUT' : 'POST';

                fetch(url, {
                        method,
                        headers,
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(result => {
                        closeCreateModal();
                        loadBonSorties();
                        Notification.success(id ? '{{ __('messages.bon_sortie_updated') }}' : '{{ __('messages.bon_sortie_created') }}');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('{{ __('messages.error_saving') }}');
                    });
            }

            function deleteBonSortie(id) {
                if (!confirm('{{ __('messages.confirm_delete_bon_sortie') }}')) return;

                fetch(`/api/bon-sortie/${id}`, {
                        method: 'DELETE',
                        headers
                    })
                    .then(() => {
                        loadBonSorties();
                        Notification.success('{{ __('messages.bon_sortie_deleted') }}');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('{{ __('messages.error_deleting') }}');
                    });
            }
        </script>
    @endpush
@endsection
