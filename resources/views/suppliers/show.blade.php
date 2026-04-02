@extends('layouts.app')

@section('title', __('messages.supplier_stats'))

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="{{ route('suppliers.page') }}" class="text-green-600 hover:underline mb-2 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                {{ __('messages.back') }}
            </a>
            <h1 class="text-3xl font-bold text-gray-800">{{ __('messages.supplier_stats') }}</h1>
            <p class="text-gray-600" id="supplierName">{{ __('messages.loading') }}...</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-sm text-gray-500">{{ __('messages.total_ordered') }}</div>
            <div class="text-2xl font-bold text-blue-600" id="totalOrdered">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-sm text-gray-500">{{ __('messages.total_delivered') }}</div>
            <div class="text-2xl font-bold text-green-600" id="totalDelivered">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="text-sm text-gray-500">{{ __('messages.total_pending') }}</div>
            <div class="text-2xl font-bold text-yellow-600" id="totalPending">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">{{ __('messages.total_orders') }}</div>
            <div class="text-2xl font-bold text-gray-800" id="totalOrders">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="text-sm text-gray-500">{{ __('messages.deliveries_count') }}</div>
            <div class="text-2xl font-bold text-purple-600" id="deliveriesCount">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">{{ __('messages.items_available') }}</div>
            <div class="text-2xl font-bold text-indigo-600" id="itemsCount">-</div>
        </div>
    </div>

    <!-- Monthly Spending Chart -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h3 class="text-lg font-bold mb-4">{{ __('messages.monthly_spending') }}</h3>
        <div class="h-64">
            <canvas id="monthlySpendingChart"></canvas>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Order Status Breakdown -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-bold mb-4">{{ __('messages.order_status') }}</h3>
            <div class="h-48">
                <canvas id="statusChart"></canvas>
            </div>
            <div id="statusLegend" class="mt-4 grid grid-cols-2 gap-2 text-sm"></div>
        </div>

        <!-- Price Comparison -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-bold mb-4">{{ __('messages.price_comparison') }}</h3>
            <div id="priceComparison" class="max-h-64 overflow-y-auto">
                <p class="text-gray-500 text-center py-4">{{ __('messages.loading') }}...</p>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-bold">{{ __('messages.recent_orders') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.items') }}</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ __('messages.total_ordered') }}</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ __('messages.total_delivered') }}</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ __('messages.total_pending') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ __('messages.date') }}</th>
                    </tr>
                </thead>
                <tbody id="recentOrdersBody" class="divide-y divide-gray-200">
                    <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">{{ __('messages.loading') }}...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const token = '{{ session('api_token') }}';
        const headers = {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        };
        
        let supplierId = {{ $supplierId }};
        let monthlyChart = null;
        let statusChart = null;

        const statusColors = {
            pending_initial_approval: '#fbbf24',
            initial_approved: '#3b82f6',
            pending_final_approval: '#f97316',
            final_approved: '#22c55e',
            rejected: '#ef4444',
            partially_delivered: '#a855f7',
            delivered: '#14b8a6'
        };

        const statusTranslations = {
            pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
            initial_approved: '{{ __('messages.initial_approved') }}',
            pending_final_approval: '{{ __('messages.pending_final_approval') }}',
            final_approved: '{{ __('messages.final_approved') }}',
            rejected: '{{ __('messages.rejected') }}',
            partially_delivered: '{{ __('messages.partially_delivered') }}',
            delivered: '{{ __('messages.delivered') }}'
        };

        document.addEventListener('DOMContentLoaded', loadStats);

        async function loadStats() {
            try {
                const response = await fetch(`/api/suppliers/${supplierId}/stats`, { headers });
                if (!response.ok) throw new Error('Failed to load stats');
                const data = await response.json();
                renderStats(data);
            } catch (error) {
                console.error('Error loading stats:', error);
                Notification.error('{{ __('messages.error_loading') }}');
            }
        }

        function renderStats(data) {
            document.getElementById('supplierName').textContent = data.supplier.name;
            document.getElementById('totalOrders').textContent = data.total_orders;
            document.getElementById('totalOrdered').textContent = '{{ __('messages.currency') }} ' + parseFloat(data.total_ordered || 0).toFixed(2);
            document.getElementById('totalDelivered').textContent = '{{ __('messages.currency') }} ' + parseFloat(data.total_delivered || 0).toFixed(2);
            document.getElementById('totalPending').textContent = '{{ __('messages.currency') }} ' + parseFloat(data.total_pending || 0).toFixed(2);
            document.getElementById('deliveriesCount').textContent = data.deliveries_count || 0;
            document.getElementById('itemsCount').textContent = data.items_count;

            renderMonthlyChart(data.monthly_spending);
            renderStatusChart(data.status_breakdown);
            renderPriceComparison(data.price_comparison);
            renderRecentOrders(data.recent_orders);
        }

        function renderMonthlyChart(monthlyData) {
            const ctx = document.getElementById('monthlySpendingChart').getContext('2d');
            const labels = monthlyData.map(m => m.month);
            const values = monthlyData.map(m => parseFloat(m.total || 0));

            if (monthlyChart) monthlyChart.destroy();
            
            monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '{{ __('messages.total_delivered') }}',
                        data: values,
                        backgroundColor: '#22c55e',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        function renderStatusChart(statusData) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const labels = Object.keys(statusData).map(s => statusTranslations[s] || s);
            const values = Object.values(statusData);
            const colors = Object.keys(statusData).map(s => statusColors[s] || '#6b7280');

            if (statusChart) statusChart.destroy();

            statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            const legendHtml = Object.entries(statusData).map(([status, count]) => `
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: ${statusColors[status] || '#6b7280'}"></span>
                    <span>${statusTranslations[status] || status}: ${count}</span>
                </div>
            `).join('');
            document.getElementById('statusLegend').innerHTML = legendHtml;
        }

        function renderPriceComparison(comparison) {
            const container = document.getElementById('priceComparison');
            
            if (!comparison || comparison.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">{{ __('messages.no_comparison') }}</p>';
                return;
            }

            const notCheapest = comparison.filter(c => !c.is_cheapest);
            
            if (notCheapest.length === 0) {
                container.innerHTML = '<p class="text-green-600 text-center py-4">{{ __('messages.supplier_is_cheapest') }} ✓</p>';
                return;
            }

            container.innerHTML = '<div class="grid gap-3">' + notCheapest.map(item => `
                <div class="border rounded-lg p-3 bg-gray-50">
                    <div class="font-semibold text-gray-800 mb-2">${escapeHtml(item.item)}</div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500 text-xs">{{ __('messages.your_price') }}</span>
                            <div class="font-medium">{{ __('messages.currency') }} ${parseFloat(item.your_price || 0).toFixed(2)}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">{{ __('messages.best_price') }}</span>
                            <div class="font-medium text-green-600">{{ __('messages.currency') }} ${parseFloat(item.best_price || 0).toFixed(2)}</div>
                        </div>
                    </div>
                    ${item.other_prices.length > 0 ? `
                        <div class="mt-2 pt-2 border-t text-xs text-gray-500">
                            <span class="font-medium">Other suppliers:</span>
                            <div class="flex flex-wrap gap-2 mt-1">
                                ${item.other_prices.map(op => `<span class="bg-white px-2 py-1 rounded border">${escapeHtml(op.supplier)}: {{ __('messages.currency') }} ${parseFloat(op.price || 0).toFixed(2)}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `).join('') + '</div>';
        }

        function renderRecentOrders(orders) {
            const tbody = document.getElementById('recentOrdersBody');
            
            if (!orders || orders.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-4 text-center text-gray-500">{{ __('messages.no_orders_yet') }}</td></tr>`;
                return;
            }

            tbody.innerHTML = orders.map(order => {
                const itemsHtml = (order.items || []).map(item => `
                    <div class="flex items-center gap-3 py-2 ${item !== order.items[0] ? 'border-t' : ''}">
                        ${item.item_image 
                            ? `<img src="/storage/${item.item_image}" class="w-10 h-10 object-cover rounded">` 
                            : `<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-400">N/A</div>`
                        }
                        <div class="flex-1">
                            <div class="font-medium text-sm">${escapeHtml(item.item_name || 'Unknown')}</div>
                            <div class="text-xs text-gray-500">
                                {{ __('messages.qty') }}: ${parseFloat(item.init_quantity || 0).toFixed(2)} → ${parseFloat(item.final_quantity || 0).toFixed(2)} × 
                                <span class="text-green-600 font-semibold">${formatCurrency(parseFloat(item.unit_price || 0))}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 align-top">#${order.id}</td>
                        <td class="px-4 py-3 align-top">
                            <div class="max-w-xs">${itemsHtml}</div>
                        </td>
                        <td class="px-4 py-3 align-top text-right font-semibold text-blue-600">{{ __('messages.currency') }} ${parseFloat(order.supplier_ordered || 0).toFixed(2)}</td>
                        <td class="px-4 py-3 align-top text-right font-semibold text-green-600">{{ __('messages.currency') }} ${parseFloat(order.supplier_delivered || 0).toFixed(2)}</td>
                        <td class="px-4 py-3 align-top text-right font-semibold text-yellow-600">{{ __('messages.currency') }} ${parseFloat(order.supplier_pending || 0).toFixed(2)}</td>
                        <td class="px-4 py-3 align-top">
                            <span class="px-2 py-1 text-xs rounded text-white" style="background-color: ${statusColors[order.status] || '#6b7280'}">
                                ${statusTranslations[order.status] || order.status}
                            </span>
                        </td>
                        <td class="px-4 py-3 align-top text-sm text-gray-600">${new Date(order.date).toLocaleDateString()}</td>
                    </tr>
                `;
            }).join('');
        }

        function formatCurrency(amount) {
            return '{{ __('messages.currency') }} ' + amount.toFixed(2);
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