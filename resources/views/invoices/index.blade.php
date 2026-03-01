@extends('layouts.app')

@section('title', __('messages.invoices'))

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.invoices') }}</h1>
            <p class="text-gray-600">
                {{ __('messages.view_and_manage_invoices') }}
            </p>
        </div>
        @if (auth()->user()->role === 'finance_manager')
            <button onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                {{ __('messages.create_invoice') }} (Manual)
            </button>
        @endif
    </div>

    @if (auth()->user()->role === 'finance_manager')
        <!-- Approved Purchase Orders Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('messages.approved_pos_awaiting') }}</h2>
            <div id="approvedPOsContainer">
                <p class="text-gray-500">Loading...</p>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.image') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.supplier') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.description') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.quantity') }}</th>
                        {{-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th> --}}
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.total') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="invoiceBody">
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">{{ __('messages.invoice_details') }}</h3>
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

    <!-- Create/Edit Invoice Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 id="modalTitle" class="text-xl font-bold mb-4">{{ __('messages.create_invoice') }}</h3>
            <form id="createInvoiceForm" onsubmit="createInvoice(event)">
                <input type="hidden" id="editInvoiceId" value="">
                <div class="space-y-4 mb-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('messages.type') }} *</label>
                            <select id="type" name="type" required class="w-full px-3 py-2 border rounded">
                                <option value="incoming">{{ __('messages.incoming') }}</option>
                                <option value="return">{{ __('messages.return') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Supplier *</label>
                            <input type="text" id="supplier" name="supplier" required
                                class="w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Date *</label>
                            <input type="date" id="date" name="date" required
                                class="w-full px-3 py-2 border rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Invoice Image (Optional)</label>
                        <input type="file" id="invoiceImage" accept="image/*" class="w-full px-3 py-2 border rounded"
                            onchange="previewMainInvoiceImage()">
                        <img id="invoiceImagePreview" class="hidden mt-2 w-32 h-32 object-cover rounded border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Items *</label>
                        <div id="invoiceItemsList" class="space-y-4"></div>
                        <button type="button" onclick="addInvoiceItem()"
                            class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">+ Add
                            Item</button>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</button>
                    <button id="submitButton" type="submit"
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
            let allInvoices = [];
            let allInventoryItems = [];
            let allApprovedPOs = [];
            let invoiceItemCounter = 0;
            let selectedPOId = null;

            document.addEventListener('DOMContentLoaded', () => {
                loadInvoices();
                loadInventoryItems();
                @if (auth()->user()->role === 'finance_manager')
                    loadApprovedPOs();
                @endif
            });

            function loadInventoryItems() {
                fetch('/api/items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(items => {
                        allInventoryItems = items;
                    })
                    .catch(err => console.error('Error loading items:', err));
            }

            function loadApprovedPOs() {
                Promise.all([
                        fetch('/api/purchase-orders', {
                            headers
                        }).then(res => res.json()),
                        fetch('/api/invoices', {
                            headers
                        }).then(res => res.json())
                    ])
                    .then(([pos, invoices]) => {
                        // Get PO IDs that already have invoices
                        const poIdsWithInvoices = new Set(
                            invoices
                            .filter(inv => inv.id_purchase_order)
                            .map(inv => inv.id_purchase_order)
                        );

                        // Filter approved POs that don't have invoices yet
                        allApprovedPOs = pos.filter(po =>
                            po.status === 'final_approved' && !poIdsWithInvoices.has(po.id)
                        );
                        renderApprovedPOs();
                    })
                    .catch(err => {
                        console.error('Error loading purchase orders:', err);
                        document.getElementById('approvedPOsContainer').innerHTML =
                            '<p class="text-red-500">Error loading purchase orders</p>';
                    });
            }

            function renderApprovedPOs() {
                const container = document.getElementById('approvedPOsContainer');
                if (allApprovedPOs.length === 0) {
                    container.innerHTML = '<p class="text-gray-500">{{ __('messages.no_approved_pos_awaiting') }}</p>';
                    return;
                }

                container.innerHTML = `
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.supplier') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.date') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.items') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.total') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    ${allApprovedPOs.map(po => {
                        const items = po.purchase_order_items || [];
                        return `
                                                                                <tr class="hover:bg-gray-50">
                                                                                    <td class="px-4 py-2">#${po.id}</td>
                                                                                    <td class="px-4 py-2">${po.supplier}</td>
                                                                                    <td class="px-4 py-2">${new Date(po.date).toLocaleDateString()}</td>
                                                                                    <td class="px-4 py-2">${items.length} item(s)</td>
                                                                                    <td class="px-4 py-2 font-semibold">{{ __('messages.currency') }} ${parseFloat(po.total_amount).toFixed(2)}</td>
                                                                                    <td class="px-4 py-2">
                                                                                        <button onclick="createInvoiceFromPO(${po.id})" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                                                            {{ __('messages.create_invoice') }}
                                                                                        </button>
                                                                                    </td>
                                                                                </tr>
                                                                            `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;
            }

            function loadInvoices() {
                fetch('/api/invoices', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        allInvoices = data;
                        renderInvoices();
                    })
                    .catch(() => {
                        document.getElementById('invoiceBody').innerHTML =
                            '<tr><td colspan="9" class="px-6 py-4 text-center text-red-500">Error loading invoices</td></tr>';
                    });
            }

            function renderInvoices() {
                const tbody = document.getElementById('invoiceBody');
                if (allInvoices.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="9" class="px-6 py-4 text-center text-gray-500">No invoices found</td></tr>';
                    return;
                }

                tbody.innerHTML = allInvoices.map(inv => {
                    const items = inv.invoice_items || [];
                    const displayImage = inv.image_path || items.find(item => item.image_path)?.image_path;
                    const itemsCount = items.length;
                    const totalQty = items.reduce((sum, item) => sum + parseFloat(item.quantity || 0), 0);
                    const total = inv.total_amount || 0;

                    return `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4">
                <img src="${displayImage ? '/storage/' + displayImage : '/images/placeholder.png'}" 
                     class="w-12 h-12 object-cover rounded cursor-pointer" 
                     onclick="viewInvoiceDetails(${inv.id})"
                     onerror="this.src='/images/placeholder.png'">
            </td>
            <td class="px-6 py-4">#${inv.id}</td>
            <td class="px-6 py-4">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${inv.type === 'return' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                    ${inv.type === 'return' ? '{{ __('messages.return') }}' : '{{ __('messages.incoming') }}'}
                </span>
            </td>
            <td class="px-6 py-4">${inv.supplier}</td>
            <td class="px-6 py-4">
                <button onclick="viewInvoiceDetails(${inv.id})" class="text-green-600 hover:underline">
                    ${itemsCount} item(s)
                </button>
            </td>
            <td class="px-6 py-4">${totalQty.toFixed(2)}</td>
            <td class="px-6 py-4 font-semibold">{{ __('messages.currency') }} ${parseFloat(total).toFixed(2)}</td>
            <td class="px-6 py-4">${new Date(inv.date).toLocaleDateString()}</td>
            <td class="px-6 py-4">
                <button onclick="viewInvoiceDetails(${inv.id})" class="text-green-600 hover:text-indigo-800 mr-2">View</button>
                @if (auth()->user()->role === 'finance_manager')
                <button onclick="editInvoice(${inv.id})" class="text-green-600 hover:text-green-800">Edit</button>
                @endif
            </td>
        </tr>
    `;
                }).join('');
            }

            function showCreateModal() {
                document.getElementById('modalTitle').textContent = '{{ __('messages.create_invoice') }} (Manual)';
                document.getElementById('submitButton').textContent = 'Create';
                document.getElementById('editInvoiceId').value = '';
                selectedPOId = null;
                document.getElementById('invoiceItemsList').innerHTML = '';
                invoiceItemCounter = 0;
                addInvoiceItem();
                document.getElementById('createModal').classList.remove('hidden');
            }

            function createInvoiceFromPO(poId) {
                selectedPOId = poId;
                fetch(`/api/purchase-orders/${poId}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(po => {
                        document.getElementById('modalTitle').textContent =
                            `{{ __('messages.create_invoice') }} from PO #${po.id}`;
                        document.getElementById('submitButton').textContent = '{{ __('messages.create_invoice') }}';
                        document.getElementById('editInvoiceId').value = '';

                        // Pre-fill supplier and date
                        document.getElementById('supplier').value = po.supplier;
                        document.getElementById('date').value = new Date().toISOString().split('T')[0];
                        document.getElementById('type').value = 'incoming';

                        // Clear and populate items with checkboxes
                        document.getElementById('invoiceItemsList').innerHTML = '';
                        invoiceItemCounter = 0;

                        const poItems = po.purchase_order_items || [];
                        poItems.forEach((item, index) => {
                            addInvoiceItemFromPO(item, index);
                        });

                        document.getElementById('createModal').classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('Error loading purchase order');
                    });
            }

            function editInvoice(id) {
                document.getElementById('modalTitle').textContent = 'Edit Invoice';
                document.getElementById('submitButton').textContent = 'Update';
                document.getElementById('editInvoiceId').value = id;

                fetch(`/api/invoices/${id}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(inv => {
                        console.log(inv);

                        document.getElementById('supplier').value = inv.supplier;
                        document.getElementById('date').value = inv.date;
                        document.getElementById('type').value = inv.type || 'incoming';

                        // Show existing invoice image if available
                        if (inv.image_path) {
                            document.getElementById('invoiceImagePreview').src = `/storage/${inv.image_path}`;
                            document.getElementById('invoiceImagePreview').classList.remove('hidden');
                        }

                        // Clear and add items
                        document.getElementById('invoiceItemsList').innerHTML = '';
                        invoiceItemCounter = 0;

                        const items = inv.invoice_items || [];
                        if (items.length === 0) {
                            addInvoiceItem();
                        } else {
                            items.forEach((item, index) => {
                                addInvoiceItem();

                                // Set as new item mode to populate data
                                const radio = document.querySelector(
                                    `input[name="item_type_${index}"][value="new"]`);
                                // radio.checked = true;
                                // toggleInvoiceItemType(index, 'new');

                                document.getElementById(`item_name_${index}`).value = item.item_name;
                                // document.getElementById(`item_description_${index}`).value = item.description || '';
                                document.getElementById(`item_quantity_${index}`).value = item.quantity;
                                document.getElementById(`item_unit_${index}`).value = item.unit || 'unit';
                                document.getElementById(`item_price_${index}`).value = item.unit_price;

                                const preview = document.getElementById(`item_image_preview_${index}`);
                                const existingImageInput = document.getElementById(`item_existing_image_${index}`);

                                if (item.image_path) {
                                    preview.src = `/storage/${item.image_path}`;
                                    if (existingImageInput) existingImageInput.value = item.image_path;
                                } else {
                                    preview.src = '/images/placeholder.png';
                                    if (existingImageInput) existingImageInput.value = '';
                                }
                                preview.classList.remove('hidden');
                            });
                        }

                        document.getElementById('createModal').classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('Error loading invoice');
                    });
            }

            function closeCreateModal() {
                document.getElementById('createModal').classList.add('hidden');
                document.getElementById('createInvoiceForm').reset();
                document.getElementById('invoiceItemsList').innerHTML = '';
                document.getElementById('invoiceImagePreview').classList.add('hidden');
                document.getElementById('editInvoiceId').value = '';
                invoiceItemCounter = 0;
            }

            function addInvoiceItemFromPO(poItem, index) {
                const container = document.getElementById('invoiceItemsList');
                const itemDiv = document.createElement('div');
                itemDiv.className = 'border p-4 rounded-lg bg-gray-50';
                const currentIndex = invoiceItemCounter;

                const itemName = poItem.item?.designation || poItem.new_item_name || 'Unknown';
                const itemId = poItem.item_id;

                itemDiv.innerHTML = `
        <div class="flex items-start gap-3">
            <input type="checkbox" id="po_item_check_${currentIndex}" checked class="mt-1" onchange="togglePOItem(${currentIndex})">
            <div class="flex-1">
                <div class="font-semibold text-sm mb-2">${itemName}</div>
                <input type="hidden" id="po_item_id_${currentIndex}" value="${itemId || ''}">
                <input type="hidden" id="po_item_name_${currentIndex}" value="${itemName}">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Quantity *</label>
                        <input type="number" id="item_quantity_${currentIndex}" name="items[${currentIndex}][quantity]" value="${poItem.quantity}" required min="0.01" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Unit *</label>
                        <input type="text"
                               id="item_unit_${currentIndex}"
                               name="items[${currentIndex}][unit]"
                               class="w-full px-3 py-2 border rounded text-sm bg-gray-100"
                               readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Unit Price *</label>
                        <input type="number" id="item_price_${currentIndex}" name="items[${currentIndex}][unit_price]" value="${poItem.unit_price}" required min="0" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
                    </div>
                </div>
                <!-- Added Item Image Input -->
                <div class="mt-3">
                    <label class="block text-xs font-medium mb-1">Item Image (Optional)</label>
                    <input type="hidden" id="item_existing_image_${currentIndex}" name="items[${currentIndex}][image_path]" value="${poItem.item?.image_path || ''}">
                    <input type="file" id="item_image_${currentIndex}" accept="image/*" class="w-full px-3 py-1 border rounded text-xs" onchange="previewInvoiceItemImage(${currentIndex})">
                    <img id="item_image_preview_${currentIndex}" 
                         src="${poItem.item?.image_path ? '/storage/' + poItem.item.image_path : '/images/placeholder.png'}" 
                         class="mt-2 w-20 h-20 object-cover rounded border"
                         onerror="this.src='/images/placeholder.png'">
                </div>
            </div>
        </div>
    `;

                container.appendChild(itemDiv);
                document.getElementById(`item_unit_${currentIndex}`).value =
                    poItem.item?.unit || poItem.unit || 'unit';
                invoiceItemCounter++;
            }

            function togglePOItem(index) {
                const checkbox = document.getElementById(`po_item_check_${index}`);
                const quantity = document.getElementById(`item_quantity_${index}`);
                const unit = document.getElementById(`item_unit_${index}`);
                const price = document.getElementById(`item_price_${index}`);

                if (checkbox.checked) {
                    quantity.required = true;
                    unit.required = true;
                    price.required = true;
                } else {
                    quantity.required = false;
                    unit.required = false;
                    price.required = false;
                }
            }

            function addInvoiceItem() {
                const container = document.getElementById('invoiceItemsList');
                const itemDiv = document.createElement('div');
                itemDiv.className = 'border p-4 rounded-lg bg-gray-50';
                const currentIndex = invoiceItemCounter;

                itemDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-sm">Item #${currentIndex + 1}</h4>
            ${currentIndex > 0 ? `<button type="button" onclick="removeInvoiceItem(this)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>` : ''}
        </div>
        <div class="space-y-3">
           
            <div id="existing_item_${currentIndex}" class="space-y-2">
                <label class="block text-xs font-medium">Item *</label>
                <select id="inventory_item_${currentIndex}" name="items[${currentIndex}][item_id]" onchange="populateItemFromInventory(${currentIndex})" class="w-full px-3 py-2 border rounded text-sm">
                    <option value="">Select Item...</option>
                    ${allInventoryItems.map(item => `<option value="${item.id}" data-designation="${item.designation}"  data-price="${item.price}" data-image="${item.image_path || ''}">${item.designation} - {{ __('messages.currency') }} ${parseFloat(item.price).toFixed(2)}</option>`).join('')}
                </select>
            </div>
            <div id="new_item_${currentIndex}" class="hidden space-y-2">
                <label class="block text-xs font-medium">Item Name *</label>
                <input type="text" id="item_name_${currentIndex}" name="items[${currentIndex}][item_name]" class="w-full px-3 py-2 border rounded text-sm" placeholder="Enter item name">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1">Quantity *</label>
                    <input type="number" id="item_quantity_${currentIndex}" name="items[${currentIndex}][quantity]" required min="0.01" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Unit *</label>
                    <select id="item_unit_${currentIndex}" name="items[${currentIndex}][unit]" class="w-full px-3 py-2 border rounded text-sm" disabled>
                        <option value="unit">Unit</option>
                        <option value="kg">Kg</option>
                        <option value="liter">Liter</option>
                        <option value="meter">Meter</option>
                        <option value="box">Box</option>
                        <option value="pack">Pack</option>
                        <option value="piece">Piece</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Unit Price *</label>
                    <input type="number" id="item_price_${currentIndex}" name="items[${currentIndex}][unit_price]" required min="0" step="0.01" class="w-full px-3 py-2 border rounded text-sm">
                </div>
            </div>
            <div>
                 <label class="block text-xs font-medium mb-1">Item Image (Optional)</label>
                 <input type="hidden" id="item_existing_image_${currentIndex}" name="items[${currentIndex}][image_path]" value="">
                 <input type="file" id="item_image_${currentIndex}" accept="image/*" class="w-full px-3 py-1 border rounded text-xs" onchange="previewInvoiceItemImage(${currentIndex})">
                 <img id="item_image_preview_${currentIndex}" 
                      src="/images/placeholder.png" 
                      class="mt-2 w-20 h-20 object-cover rounded border"
                      onerror="this.src='/images/placeholder.png'">
            </div>
        </div>
    `;

                container.appendChild(itemDiv);

                invoiceItemCounter++;
            }

            function removeInvoiceItem(button) {
                button.closest('.border').remove();
            }

            // function toggleInvoiceItemType(index, type) {
            //     const existingDiv = document.getElementById(`existing_item_${index}`);
            //     const newDiv = document.getElementById(`new_item_${index}`);
            //     // const descriptionDiv = document.getElementById(`item_description_div_${index}`);
            //     const itemSelect = document.getElementById(`inventory_item_${index}`);
            //     const itemNameInput = document.getElementById(`item_name_${index}`);
            //     // const itemDescription = document.getElementById(`item_description_${index}`);
            //     const itemPrice = document.getElementById(`item_price_${index}`);
            //     const itemQuantity = document.getElementById(`item_quantity_${index}`);

            //     if (type === 'existing') {
            //         existingDiv.classList.remove('hidden');
            //         newDiv.classList.add('hidden');
            //         // descriptionDiv.classList.add('hidden');
            //         itemSelect.required = true;
            //         itemNameInput.required = false;
            //         itemNameInput.value = '';
            //         // itemDescription.value = '';
            //         itemPrice.value = '';
            //         itemQuantity.value = '';
            //         // document.getElementById(`item_image_preview_${index}`).classList.add('hidden');
            //     } else {
            //         existingDiv.classList.add('hidden');
            //         newDiv.classList.remove('hidden');
            //         // descriptionDiv.classList.remove('hidden');
            //         itemSelect.required = false;
            //         itemSelect.value = '';
            //         itemNameInput.required = true;
            //         itemPrice.value = '';
            //         itemQuantity.value = '';
            //         // itemDescription.value = '';
            //         // document.getElementById(`item_image_preview_${index}`).classList.add('hidden');
            //     }
            // }

            function toggleInvoiceItemType(index, type) {
                const existingDiv = document.getElementById(`existing_item_${index}`);
                const newDiv = document.getElementById(`new_item_${index}`);
                const itemSelect = document.getElementById(`inventory_item_${index}`);
                const itemNameInput = document.getElementById(`item_name_${index}`);
                const itemPrice = document.getElementById(`item_price_${index}`);
                const itemQuantity = document.getElementById(`item_quantity_${index}`);

                if (type === 'existing') {
                    if (existingDiv) existingDiv.classList.remove('hidden');
                    if (newDiv) newDiv.classList.add('hidden');

                    if (itemSelect) itemSelect.required = true;
                    if (itemNameInput) itemNameInput.required = false;

                    if (itemNameInput) itemNameInput.value = '';
                    if (itemPrice) itemPrice.value = '';
                    if (itemQuantity) itemQuantity.value = '';
                } else {
                    if (existingDiv) existingDiv.classList.add('hidden');
                    if (newDiv) newDiv.classList.remove('hidden');

                    if (itemSelect) itemSelect.required = false;
                    if (itemNameInput) itemNameInput.required = true;

                    if (itemSelect) itemSelect.value = '';
                    if (itemPrice) itemPrice.value = '';
                    if (itemQuantity) itemQuantity.value = '';
                }
            }

            function populateItemFromInventory(index) {
                const select = document.getElementById(`inventory_item_${index}`);
                const option = select.options[select.selectedIndex];
                if (!option.value) return;

                const item = allInventoryItems.find(i => i.id == option.value);
                if (!item) return;

                // price
                document.getElementById(`item_price_${index}`).value =
                    parseFloat(item.price).toFixed(2);

                // ✅ UNIT → disabled input
                document.getElementById(`item_unit_${index}`).value =
                    item.unit || 'unit';


                // image
                const preview = document.getElementById(`item_image_preview_${index}`);
                const hiddenImageInput = document.getElementById(`item_existing_image_${index}`);

                if (item.image_path) {
                    preview.src = `/storage/${item.image_path}`;
                    preview.classList.remove('hidden');
                    if (hiddenImageInput) hiddenImageInput.value = item.image_path;
                } else {
                    preview.src = '/images/placeholder.png';
                    preview.classList.remove('hidden');
                    if (hiddenImageInput) hiddenImageInput.value = '';
                }
                console.log(item);
            }



            function previewInvoiceItemImage(index) {
                const file = document.getElementById(`item_image_${index}`).files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById(`item_image_preview_${index}`);
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            }

            function previewMainInvoiceImage() {
                const file = document.getElementById('invoiceImage').files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('invoiceImagePreview');
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            }

            function createInvoice(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const items = [];
                const editId = document.getElementById('editInvoiceId').value;
                const isEdit = editId !== '';

                for (let i = 0; i < invoiceItemCounter; i++) {
                    // Check if this is a PO item with checkbox
                    const checkbox = document.getElementById(`po_item_check_${i}`);
                    if (checkbox && !checkbox.checked) {
                        continue; // Skip unchecked PO items
                    }

                    // For PO items, get from hidden fields
                    let itemId = document.getElementById(`po_item_id_${i}`)?.value;
                    let itemName = document.getElementById(`po_item_name_${i}`)?.value;

                    // For manual items, get from form data
                    if (!itemId) {
                        itemId = formData.get(`items[${i}][item_id]`);
                    }
                    if (!itemName) {
                        itemName = formData.get(`items[${i}][item_name]`);
                    }

                    // Fallback to designation if itemId exists but itemName doesn't (existing item selection)
                    if (itemId && !itemName) {
                        const select = document.getElementById(`inventory_item_${i}`);
                        if (select && select.value) {
                            const option = select.options[select.selectedIndex];
                            itemName = option.getAttribute('data-designation');
                        }
                    }

                    const description = formData.get(`items[${i}][description]`);
                    const quantity = formData.get(`items[${i}][quantity]`);
                    const unit = formData.get(`items[${i}][unit]`);
                    const unitPrice = formData.get(`items[${i}][unit_price]`);
                    const imagePath = formData.get(`items[${i}][image_path]`);

                    if ((itemId || itemName) && quantity && unitPrice) {
                        const item = {
                            quantity: parseFloat(quantity),
                            unit: unit || 'unit',
                            unit_price: parseFloat(unitPrice),
                            image_path: imagePath || null
                        };

                        if (itemId) {
                            item.item_id = parseInt(itemId);
                            item.item_name = itemName;
                        } else if (itemName) {
                            item.item_name = itemName;
                            item.description = description || '';
                        }

                        items.push(item);
                    }
                }

                const invoiceFormData = new FormData();
                invoiceFormData.append('type', formData.get('type') || 'incoming');
                invoiceFormData.append('supplier', formData.get('supplier'));
                invoiceFormData.append('date', formData.get('date'));
                invoiceFormData.append('items', JSON.stringify(items));

                // Link to purchase order if creating from PO
                if (selectedPOId) {
                    invoiceFormData.append('purchase_order_id', selectedPOId);
                }

                // Add main invoice image
                const mainImageFile = document.getElementById('invoiceImage')?.files[0];
                if (mainImageFile) {
                    invoiceFormData.append('image', mainImageFile);
                }

                // Add per-item images
                for (let i = 0; i < invoiceItemCounter; i++) {
                    const imageFile = document.getElementById(`item_image_${i}`)?.files[0];
                    if (imageFile) {
                        invoiceFormData.append(`item_image_${i}`, imageFile);
                    }
                }

                // Add _method for PUT request if editing
                if (isEdit) {
                    invoiceFormData.append('_method', 'PUT');
                }

                const url = isEdit ? `/api/invoices/${editId}` : '/api/invoices';
                const method = 'POST'; // Always POST since we use _method for PUT

                fetch(url, {
                        method: method,
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        },
                        body: invoiceFormData
                    })
                    .then(res => res.json())
                    .then(() => {
                        closeCreateModal();
                        loadInvoices();
                        @if (auth()->user()->role === 'finance_manager')
                            loadApprovedPOs(); // Reload to remove PO from list if invoice was created from PO
                        @endif
                        const message = isEdit ? 'Invoice updated successfully!' :
                            'Invoice created successfully! Items have been added to inventory.';
                        Notification.success(message);
                    })
                    .catch(err => {
                        console.error(err);
                        Notification.error('Error ' + (isEdit ? 'updating' : 'creating') + ' invoice');
                    });
            };


            function viewInvoiceDetails(id) {
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsContent').innerHTML = '<p class="text-gray-500">Loading...</p>';

                fetch(`/api/invoices/${id}`, {
                        headers
                    })
                    .then(res => res.json())
                    .then(inv => {
                        const items = inv.invoice_items || [];
                        const total = inv.total_amount || 0;
                        const html = `
                <div class="space-y-4">
                    ${inv.image_path ? `
                                                                                    <div class="mb-4">
                                                                                        <p class="text-sm text-gray-500 mb-2">{{ __('messages.invoice_image') }}</p>
                                                                                        <img src="/storage/${inv.image_path}" class="w-full max-w-md rounded border">
                                                                                    </div>
                                                                                ` : ''}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.invoice_id') }}</p>
                            <p class="font-semibold">#${inv.id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.date') }}</p>
                            <p class="font-semibold">${new Date(inv.date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.type') }}</p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${inv.type === 'return' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                                ${inv.type === 'return' ? '{{ __('messages.return') }}' : '{{ __('messages.incoming') }}'}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.supplier') }}</p>
                            <p class="font-semibold">${inv.supplier}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('messages.finance_manager') }}</p>
                            <p class="font-semibold">${inv.responsible_finance?.name || 'N/A'}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t">
                        <h4 class="font-semibold mb-3">{{ __('messages.items') }}</h4>
                        ${items.length === 0 ? '<p class="text-gray-500">No items</p>' : `
                                                                                    <table class="min-w-full">
                                                                                        <thead class="bg-gray-50">
                                                                                            <tr>
                                                                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.image') }}</th>
                                                                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.items') }}</th>
                                                                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.quantity') }}</th>
                                                                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.unit') }}</th>
                                                                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.unit_price') }}</th>
                                                                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.subtotal') }}</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="divide-y">
                                                                                            ${items.map(item => `
                                        <tr>
                                            <td class="px-4 py-2">
                                                <img src="${item.image_path ? '/storage/' + item.image_path : '/images/placeholder.png'}" 
                                                     class="w-16 h-16 object-cover rounded" 
                                                     onerror="this.src='/images/placeholder.png'">
                                            </td>
                                            <td class="px-4 py-2">
                                               ${item.item_name} 
                                            </td>
                                            <td class="px-4 py-2">${parseFloat(item.quantity).toFixed(2)}</td>
                                            <td class="px-4 py-2">${item.unit || 'unit'}</td>
                                            <td class="px-4 py-2">{{ __('messages.currency') }} ${parseFloat(item.unit_price).toFixed(2)}</td>
                                            <td class="px-4 py-2 font-semibold">{{ __('messages.currency') }} ${item.subtotal.toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                                                                                                                                                                                                                                                                                                                                                                </tbody>
                                                                                                                                                                                                                                                                                                                                                                            </table>
                                                                                                                                                                                                                                                                                                                                                                        `}
                        <div class="mt-4 pt-4 border-t text-right">
                            <p class="text-lg font-bold text-green-600">Total: {{ __('messages.currency') }} ${parseFloat(total).toFixed(2)}</p>
                        </div>
                    </div>
                </div>
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
        </script>
    @endpush
@endsection
