<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.available_locales')[app()->getLocale()]['dir'] }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'School Inventory System')</title>

    <link rel="icon" href="{{ asset('images/favicon.png') }}">
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="{{ asset('js/chart.js') }}"></script>
    <script src="{{ asset('js/alpine.js') }}" defer></script>
    <script src="{{ asset('js/notifications.js') }}"></script>
    <script src="{{ asset('js/notifications-inbox.js') }}"></script>


    @if (config('app.available_locales')[app()->getLocale()]['dir'] === 'rtl')
        <style>
            body {
                direction: rtl;
            }

            .rtl-flip {
                transform: scaleX(-1);
            }

            /* RTL Table alignment - override text-left */
            table th, table td {
                text-align: right !important;
            }

            /* RTL Logo border */
            [dir="rtl"] .logo-text {
                border-left: none !important;
                border-right: 2px solid #15803d !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                margin-left: 0.5rem !important;
                margin-right: 0.5rem !important;
            }
        </style>
    @endif
</head>

<body class="bg-gray-100" @auth data-auth="true" @else data-auth="false" @endauth>

    <nav x-data="{ mobileOpen: false }"
        class="sticky top-0 z-50
            backdrop-blur-md
            shadow-[0_6px_20px_rgba(22,163,74,0.15)]
            border-b border-green-200"
        style="
        background:
        linear-gradient(
            to right,
            rgba(22,163,74,0.25) 0%,
            rgba(255,255,255,0.95) 15%,
            rgba(255,255,255,0.95) 85%,
            rgba(22,163,74,0.25) 100%
        );
     ">



        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">

                <!-- LOGO -->
                <a href="{{ auth()->check() && auth()->user()->role === 'director' ? route('requests.page') : route('dashboard') }}"
                    class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo-al-amine.png') }}" class="h-14 transition-transform hover:scale-105"
                        alt="AL Amine Logo">

                    <div class="hidden 2xl:block font-logo border-l-2 border-green-700 pl-2 ml-2 logo-text">
                        <p class="text-green-700 text-lg font-bold tracking-wide">
                            Complexe Scolaire
                        </p>
                        <p class="text-sm text-red-600 font-semibold tracking-widest">
                            AL AMINE
                        </p>
                    </div>
                </a>

                <!-- DESKTOP MENU -->
                @auth
                    <div class="hidden md:flex items-center space-x-3 bg-green-50 px-3 py-1 rounded-full shadow-inner">

                        @if (auth()->user()->role !== 'director')
                            <a href="{{ route('dashboard') }}"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                       {{ request()->routeIs('dashboard')
                           ? 'bg-red-600 text-white shadow-md scale-105'
                           : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                                {{ __('messages.dashboard') }}
                            </a>
                        @endif

                        @if (in_array(auth()->user()->role, ['stock_manager', 'hr_manager']))
                            <a href="{{ route('items.page') }}"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                       {{ request()->routeIs('items.*')
                           ? 'bg-red-600 text-white shadow-md scale-105'
                           : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                                {{ __('messages.items') }}
                            </a>
                        @endif

                        <a href="{{ route('requests.page') }}"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                   {{ request()->routeIs('requests.*')
                       ? 'bg-red-600 text-white shadow-md scale-105'
                       : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                            {{ __('messages.requests') }}
                        </a>

                        @if (in_array(auth()->user()->role, ['stock_manager', 'hr_manager']))
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open=!open" @click.away="open=false"
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 flex items-center gap-1
                       {{ request()->routeIs('purchase-orders.*', 'bon-sortie.*', 'suppliers.*')
                           ? 'bg-red-600 text-white shadow-md scale-105'
                           : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                                    <span>{{ __('messages.purchase_&_sortie') }}</span>
                                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition
                                    class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-lg border z-40 overflow-hidden">
                                    <a href="{{ route('purchase-orders.page') }}"
                                        class="block px-4 py-2 text-sm transition-colors
                                       {{ request()->routeIs('purchase-orders.*')
                                           ? 'bg-red-50 text-red-700 font-medium'
                                           : 'text-gray-700 hover:bg-gray-50' }}">
                                        {{ __('messages.purchase_orders') }}
                                    </a>
                                    <a href="{{ route('suppliers.page') }}"
                                        class="block px-4 py-2 text-sm transition-colors
                                       {{ request()->routeIs('suppliers.*')
                                           ? 'bg-red-50 text-red-700 font-medium'
                                           : 'text-gray-700 hover:bg-gray-50' }}">
                                        {{ __('messages.suppliers') }}
                                    </a>
                                    <a href="{{ route('bon-sortie.page') }}"
                                        class="block px-4 py-2 text-sm transition-colors
                                       {{ request()->routeIs('bon-sortie.*')
                                           ? 'bg-red-50 text-red-700 font-medium'
                                           : 'text-gray-700 hover:bg-gray-50' }}">
                                        {{ __('messages.bon_de_sortie') }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if (in_array(auth()->user()->role, ['finance_manager', 'hr_manager']))
                            <a href="{{ route('invoices.page') }}"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                       {{ request()->routeIs('invoices.*')
                           ? 'bg-red-600 text-white shadow-md scale-105'
                           : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                                {{ __('messages.invoices') }}
                            </a>
                        @endif



                    </div>
                @endauth

                <!-- RIGHT DESKTOP -->
                <div class="hidden md:flex items-center space-x-4">

                    <!-- Notification Bell -->
                    <div x-data="{ open: false }" class="relative" @auth data-auth="true" @else data-auth="false" @endauth
                         x-on:open-dropdown.window="open && NotificationInbox.updateDropdown()">
                        <button @click="open=!open; open && NotificationInbox.updateDropdown()" class="relative p-2 text-green-700 hover:bg-green-50 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span id="notification-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1"></span>
                        </button>

                        <div x-show="open" x-transition @click.away="open=false"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <span class="font-semibold text-gray-800">{{ __('messages.notifications') }}</span>
                                <div class="flex items-center gap-2">
                                    <button @click="NotificationInbox.markAllAsRead()" class="text-xs text-green-600 hover:text-green-700 font-medium">
                                        {{ __('messages.mark_all_as_read') }}
                                    </button>
                                    <a href="{{ route('notifications.page') }}" class="text-sm text-green-600 hover:text-green-700">
                                        {{ __('messages.view_all') }}
                                    </a>
                                </div>
                            </div>
                            <div id="notification-list" class="max-h-96 overflow-y-auto">
                                <div class="px-4 py-6 text-center text-gray-500 text-sm">
                                    <p>{{ __('messages.loading') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Language Switcher -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open=!open"
                            class="px-3 py-2 rounded-lg text-green-700 hover:bg-green-50 transition">
                            🌐 {{ config('app.available_locales')[app()->getLocale()]['name'] }}
                        </button>

                        <div x-show="open" x-transition @click.away="open=false"
                            class="absolute right-0 mt-2 w-40 bg-white rounded-xl shadow-lg border z-50">
                            @foreach (config('app.available_locales') as $code => $locale)
                                <a href="{{ route('locale.switch', $code) }}"
                                    class="block px-4 py-2 hover:bg-green-50
                               {{ app()->getLocale() === $code ? 'font-semibold text-green-700' : '' }}">
                                    {{ $locale['name'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @auth
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-700">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                class="bg-green-600 text-white px-4 py-2 rounded-lg
                                   hover:bg-green-700 transition">
                                {{ __('messages.logout') }}
                            </button>
                        </form>
                    @endauth
                </div>

                <!-- MOBILE BUTTON -->
                <button @click="mobileOpen=!mobileOpen" class="md:hidden text-green-700 text-xl">
                    ☰
                </button>
            </div>
        </div>

        <!-- MOBILE MENU -->
        <div x-show="mobileOpen" x-transition class="md:hidden bg-white border-t shadow-lg">
            <div class="px-4 py-4 space-y-2">


                <!-- Language Mobile -->
                <div x-data="{ open: false }" class="pt-2 border-t">
                    <button @click="open=!open" class="w-full px-4 py-2 text-green-700 text-left">
                        🌐 {{ config('app.available_locales')[app()->getLocale()]['name'] }} ▾
                    </button>

                    <div x-show="open" x-transition>
                        @foreach (config('app.available_locales') as $code => $locale)
                            <a href="{{ route('locale.switch', $code) }}" class="block px-6 py-2 hover:bg-green-50">
                                {{ $locale['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @auth
                    @if (auth()->user()->role !== 'director')
                        <a href="{{ route('dashboard') }}"
                            class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('dashboard') ? 'bg-red-600 text-white' : 'text-green-700 hover:bg-green-50' }}">
                            {{ __('messages.dashboard') }}
                        </a>
                    @endif

                    @if (in_array(auth()->user()->role, ['stock_manager', 'hr_manager']))
                        <a href="{{ route('items.page') }}"
                            class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('items.*') ? 'bg-red-600 text-white' : 'text-green-700 hover:bg-green-50' }}">
                            {{ __('messages.items') }}
                        </a>
                    @endif

                    <a href="{{ route('requests.page') }}"
                        class="block px-4 py-2 rounded-lg
                   {{ request()->routeIs('requests.*') ? 'bg-red-600 text-white' : 'text-green-700 hover:bg-green-50' }}">
                        {{ __('messages.requests') }}
                    </a>

                    @if (in_array(auth()->user()->role, ['stock_manager', 'hr_manager']))
                        <div x-data="{ open: false }">
                            <button @click="open=!open"
                                class="w-full px-4 py-2 rounded-lg text-left flex items-center justify-between
                               {{ request()->routeIs('purchase-orders.*', 'bon-sortie.*') ? 'bg-red-600 text-white' : 'text-green-700 hover:bg-green-50' }}">
                                <span>{{ __('messages.purchase_&_sortie') }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="pl-4">
                                <a href="{{ route('purchase-orders.page') }}"
                                    class="block px-4 py-2 rounded-lg text-sm
                                   {{ request()->routeIs('purchase-orders.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-green-700 hover:bg-green-50' }}">
                                    {{ __('messages.purchase_orders') }}
                                </a>
                                <a href="{{ route('bon-sortie.page') }}"
                                    class="block px-4 py-2 rounded-lg text-sm
                                   {{ request()->routeIs('bon-sortie.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-green-700 hover:bg-green-50' }}">
                                    {{ __('messages.bon_de_sortie') }}
                                </a>
                            </div>
                        </div>
                    @endif

                    @if (in_array(auth()->user()->role, ['finance_manager', 'hr_manager']))
                        <a href="{{ route('invoices.page') }}"
                            class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('invoices.*') ? 'bg-red-600 text-white' : 'text-green-700 hover:bg-green-50' }}">
                            {{ __('messages.invoices') }}
                        </a>
                    @endif





                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full bg-green-600 text-white py-2 rounded-lg">
                            {{ __('messages.logout') }}
                        </button>
                    </form>
                @endauth

            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-1 fade-in {{ request()->routeIs('login') ? 'login-main' : 'py-24' }}">
        @yield('content')
    </main>



    @stack('scripts')
</body>

</html>
