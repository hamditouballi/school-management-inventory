<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.available_locales')[app()->getLocale()]['dir'] }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'School Inventory System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('js/chart.js') }}"></script>
    <script src="{{ asset('js/alpine.js') }}" defer></script>
    <script src="{{ asset('js/notifications.js') }}"></script>
    @if(config('app.available_locales')[app()->getLocale()]['dir'] === 'rtl')
    <style>
        body { direction: rtl; }
        .rtl-flip { transform: scaleX(-1); }
    </style>
    @endif
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="{{ auth()->check() && auth()->user()->role === 'teacher' ? route('requests.index') : route('dashboard') }}" class="text-xl font-bold hover:text-green-100 transition">
                        Complexe Scolaire AL Amine
                    </a>
                    @auth
                        <div class="hidden md:flex space-x-4">
                            @if(auth()->user()->role !== 'teacher')
                            <a href="{{ route('dashboard') }}" class="hover:bg-green-700 px-3 py-2 rounded">{{ __('messages.dashboard') }}</a>
                            @endif
                            
                            @if(in_array(auth()->user()->role, ['stock_manager', 'hr_manager']))
                            <a href="{{ route('items.index') }}" class="hover:bg-green-700 px-3 py-2 rounded">{{ __('messages.items') }}</a>
                            @endif
                            
                            <a href="{{ route('requests.index') }}" class="hover:bg-green-700 px-3 py-2 rounded">{{ __('messages.requests') }}</a>
                            
                            @if(in_array(auth()->user()->role, ['stock_manager', 'hr_manager']))
                            <a href="{{ route('purchase-orders.index') }}" class="hover:bg-green-700 px-3 py-2 rounded">{{ __('messages.purchase_orders') }}</a>
                            @endif
                            
                            @if(in_array(auth()->user()->role, ['finance_manager', 'hr_manager']))
                            <a href="{{ route('invoices.index') }}" class="hover:bg-green-700 px-3 py-2 rounded">{{ __('messages.invoices') }}</a>
                            @endif
                        </div>
                    @endauth
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Language Switcher -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="hover:bg-green-700 px-3 py-2 rounded flex items-center space-x-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                            </svg>
                            <span class="text-sm">{{ config('app.available_locales')[app()->getLocale()]['name'] }}</span>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg py-1 z-50">
                            @foreach(config('app.available_locales') as $code => $locale)
                                <a href="{{ route('locale.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() === $code ? 'bg-gray-50 font-semibold' : '' }}">
                                    {{ $locale['name'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    
                    @auth
                        <span class="text-sm">{{ auth()->user()->name }} ({{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }})</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-700 hover:bg-green-800 px-4 py-2 rounded">{{ __('messages.logout') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="bg-green-700 hover:bg-green-800 px-4 py-2 rounded">{{ __('messages.login') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8 fade-in">
        @yield('content')
    </main>
    
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notification.success('{{ session('success') }}');
        });
    </script>
    @endif
    
    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notification.error('{{ session('error') }}');
        });
    </script>
    @endif

    <footer class="bg-gray-800 text-white py-4 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2025 School Inventory Management System. All rights reserved.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
