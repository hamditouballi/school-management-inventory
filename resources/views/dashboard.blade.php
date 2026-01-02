@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.dashboard') }}</h1>
        <p class="text-gray-600">{{ __('messages.welcome') }}, {{ auth()->user()->name }}</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-semibold mb-2">{{ __('messages.total_items') }}</h3>
            <p class="text-3xl font-bold text-green-600" id="totalItems">-</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-semibold mb-2">{{ __('messages.low_stock_items') }}</h3>
            <p class="text-3xl font-bold text-red-600" id="lowStockItems">-</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-semibold mb-2">{{ __('messages.pending_requests') }}</h3>
            <p class="text-3xl font-bold text-yellow-600" id="pendingRequests">-</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-semibold mb-2">{{ __('messages.monthly_spending') }}</h3>
            <p class="text-3xl font-bold text-green-600" id="monthlySpending">-</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">{{ __('messages.monthly_consumption') }}</h3>
            <canvas id="consumptionChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">{{ __('messages.consumption_by_department') }}</h3>
            <canvas id="departmentChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">{{ __('messages.monthly_spending') }}</h3>
            <canvas id="spendingChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">{{ __('messages.top_consumed_items') }}</h3>
            <canvas id="topItemsChart"></canvas>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h3 class="text-lg font-semibold mb-4">{{ __('messages.reports') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="border rounded-lg p-4 hover:shadow-md transition">
                <h4 class="font-semibold mb-2">{{ __('messages.consumed_materials_report') }}</h4>
                <p class="text-sm text-gray-600 mb-3">Export consumed materials report for a date range</p>
                <div class="space-y-2 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.start_date') }}</label>
                        <input type="date" id="reportStartDate" class="w-full px-2 py-1 border rounded text-sm"
                            value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.end_date') }}</label>
                        <input type="date" id="reportEndDate" class="w-full px-2 py-1 border rounded text-sm"
                            value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                    </div>
                </div>
                <button onclick="downloadConsumedMaterialsReport()"
                    class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    {{ __('messages.download_excel') }}
                </button>
            </div>
            <!-- Department Consumption Report -->
            <div class="border rounded-lg p-4 hover:shadow-md transition">
                <h4 class="font-semibold mb-2">{{ __('messages.department_consumption_report') }}</h4>
                <p class="text-sm text-gray-600 mb-3">Export consumption report by department with item selection</p>
                <div class="space-y-2 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.start_date') }}</label>
                        <input type="date" id="deptReportStartDate" class="w-full px-2 py-1 border rounded text-sm"
                            value="{{ now()->startOfYear()->format('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.end_date') }}</label>
                        <input type="date" id="deptReportEndDate" class="w-full px-2 py-1 border rounded text-sm"
                            value="{{ now()->endOfYear()->format('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.items') }}</label>
                        <button onclick="showItemSelector()"
                            class="w-full px-2 py-1 border rounded text-sm text-left hover:bg-gray-50">
                            <span id="selectedItemsText">{{ __('messages.all_items') }}</span>
                        </button>
                    </div>
                </div>
                <button onclick="downloadDepartmentReport()"
                    class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    {{ __('messages.download_excel') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Item Selector Modal -->
    <div id="itemSelectorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">{{ __('messages.select_items') }}</h3>
                <button onclick="closeItemSelector()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <button onclick="selectAllItems()"
                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm mr-2">Tout
                    sélectionner</button>
                <button onclick="deselectAllItems()"
                    class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">Tout désélectionner</button>
            </div>
            <div id="itemCheckboxList" class="space-y-2 mb-4 max-h-96 overflow-y-auto">
                <!-- Items will be loaded here -->
            </div>
            <div class="flex justify-end gap-2">
                <button onclick="closeItemSelector()"
                    class="px-4 py-2 border rounded hover:bg-gray-100">{{ __('messages.cancel') }}</button>
                <button onclick="applyItemSelection()"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('messages.save') }}</button>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">{{ __('messages.low_stock_alerts') }}</h3>
        <div id="lowStockTable" class="overflow-x-auto">
            <p class="text-gray-500">Loading...</p>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const token = '{{ session('api_token') }}';
                const headers = {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                };

                // Fetch dashboard stats
                fetch('/api/stats/dashboard', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('totalItems').textContent = data.total_items;
                        document.getElementById('lowStockItems').textContent = data.low_stock_items;
                        document.getElementById('pendingRequests').textContent = data.pending_requests;
                        document.getElementById('monthlySpending').textContent = '{{ __('messages.currency') }} ' +
                            parseFloat(data.total_spent_this_month).toFixed(2);
                    });

                // Monthly Consumption Chart
                fetch('/api/stats/consumption', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const ctx = document.getElementById('consumptionChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.map(d => d.month),
                                datasets: [{
                                    label: 'Quantity Consumed',
                                    data: data.map(d => d.total_quantity),
                                    borderColor: 'rgb(34, 197, 94)',
                                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    });

                // Consumption by Department Chart
                fetch('/api/stats/consumption-by-department', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const ctx = document.getElementById('departmentChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.map(d => d.department),
                                datasets: [{
                                    label: 'Total Consumption',
                                    data: data.map(d => d.total_quantity),
                                    backgroundColor: ['#22c55e', '#16a34a', '#15803d', '#ef4444']
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    });

                // Monthly Spending Chart
                fetch('/api/stats/spending', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const ctx = document.getElementById('spendingChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.map(d => d.month),
                                datasets: [{
                                    label: 'Amount Spent ({{ __('messages.currency') }})',
                                    data: data.map(d => d.total_spent),
                                    backgroundColor: 'rgb(34, 197, 94)'
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    });

                // Top Items Chart
                fetch('/api/stats/top-items', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        const ctx = document.getElementById('topItemsChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.map(d => d.designation),
                                datasets: [{
                                    label: 'Total Consumed',
                                    data: data.map(d => d.total_consumed),
                                    backgroundColor: 'rgb(220, 38, 38)'
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    });

                // Low Stock Table
                fetch('/api/stats/low-stock', {
                        headers
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            document.getElementById('lowStockTable').innerHTML =
                                '<p class="text-gray-500">No low stock items</p>';
                            return;
                        }
                        let html =
                            '<table class="min-w-full"><thead><tr class="bg-gray-50"><th class="px-4 py-2 text-left">{{ __('messages.designation') }}</th><th class="px-4 py-2 text-left">{{ __('messages.quantity') }}</th><th class="px-4 py-2 text-left">{{ __('messages.unit') }}</th></tr></thead><tbody>';
                        data.forEach(item => {
                            html +=
                                `<tr class="border-t"><td class="px-4 py-2">${item.designation}</td><td class="px-4 py-2 text-red-600 font-semibold">${item.quantity}</td><td class="px-4 py-2">${item.unit}</td></tr>`;
                        });
                        html += '</tbody></table>';
                        document.getElementById('lowStockTable').innerHTML = html;
                    });
            });

            function downloadConsumedMaterialsReport() {
                const token = '{{ session('api_token') }}';
                const startDate = document.getElementById('reportStartDate').value;
                const endDate = document.getElementById('reportEndDate').value;

                if (!startDate || !endDate) {
                    Notification.warning("{{ __('messages.select_dates') }}");
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    Notification.error("{{ __('messages.invalid_date_range') }}");
                    return;
                }

                // Show loading state
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML =
                    '<svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

                fetch(`/api/reports/consumed-materials?start_date=${startDate}&end_date=${endDate}`, {
                        method: 'GET',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Download failed');
                        return response.blob();
                    })
                    .then(blob => {
                        // Create download link
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `Inventaire_des_matieres_consommees_${startDate}_to_${endDate}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        // Reset button
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    })
                    .catch(error => {
                        console.error('Error downloading report:', error);
                        Notification.error("{{ __('messages.error_downloading_report') }}");
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
            }

            let allItemsList = [];
            let selectedItemIds = []; // Empty array means all items selected by default

            // Load items for selector
            fetch('/api/items', {
                    headers
                })
                .then(res => res.json())
                .then(data => {
                    allItemsList = data;
                });

            function showItemSelector() {
                const list = document.getElementById('itemCheckboxList');
                list.innerHTML = allItemsList.map(item => `
        <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
            <input type="checkbox" 
                   value="${item.id}" 
                   ${selectedItemIds.length === 0 || selectedItemIds.includes(item.id) ? 'checked' : ''}
                   class="item-checkbox mr-2">
            <span>${item.designation}</span>
        </label>
    `).join('');
                document.getElementById('itemSelectorModal').classList.remove('hidden');
            }

            function closeItemSelector() {
                document.getElementById('itemSelectorModal').classList.add('hidden');
            }

            function selectAllItems() {
                document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = true);
            }

            function deselectAllItems() {
                document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
            }

            function applyItemSelection() {
                const checkboxes = document.querySelectorAll('.item-checkbox:checked');
                selectedItemIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

                const text = (selectedItemIds.length === 0 || selectedItemIds.length === allItemsList.length) ?
                    "{{ __('messages.all_items') }}" :
                    `${selectedItemIds.length} {{ __('messages.items_selected') }}`;

                document.getElementById('selectedItemsText').textContent = text;
                closeItemSelector();
            }

            function downloadDepartmentReport() {
                const token = '{{ session('api_token') }}';
                const startDate = document.getElementById('deptReportStartDate').value;
                const endDate = document.getElementById('deptReportEndDate').value;

                if (!startDate || !endDate) {
                    Notification.warning("{{ __('messages.select_dates') }}");
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    Notification.error("{{ __('messages.invalid_date_range') }}");
                    return;
                }

                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML =
                    '<svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

                let url = `/api/reports/department-consumption?start_date=${startDate}&end_date=${endDate}`;

                // Only add item_ids if specific items are selected (not all)
                if (selectedItemIds.length > 0 && selectedItemIds.length < allItemsList.length) {
                    selectedItemIds.forEach(id => {
                        url += `&item_ids[]=${id}`;
                    });
                }

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Download failed');
                        return response.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `Rapport_Consommation_Departements_${startDate}_to_${endDate}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    })
                    .catch(error => {
                        console.error('Error downloading report:', error);
                        Notification.error("{{ __('messages.error_downloading_report') }}");
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
            }
        </script>
    @endpush
@endsection
