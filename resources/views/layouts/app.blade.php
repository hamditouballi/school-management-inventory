<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.available_locales')[app()->getLocale()]['dir'] }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'School Inventory System')</title>

    <link rel="icon" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="{{ asset('js/chart.js') }}"></script>
    <script src="{{ asset('js/alpine.js') }}" defer></script>
    <script src="{{ asset('js/notifications.js') }}"></script>
    

    @if (config('app.available_locales')[app()->getLocale()]['dir'] === 'rtl')
        <style>
            body { direction: rtl; }
            .rtl-flip { transform: scaleX(-1); }

        </style>
    @endif
</head>

<body class="bg-gray-100">

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
            <a href="{{ auth()->check() && auth()->user()->role === 'teacher'
                        ? route('requests.index')
                        : route('dashboard') }}"
               class="flex items-center space-x-3">
                <img src="{{ asset('images/logo-al-amine.png') }}"
                     class="h-14 transition-transform hover:scale-105"
                     alt="AL Amine Logo">

                <div class="hidden sm:block font-logo border-l-2 border-green-700 pl-2 ml-2rounded-l-md">
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
            <div class="hidden md:flex items-center space-x-1 bg-green-50 px-2 py-1 rounded-full shadow-inner">

                @if(auth()->user()->role !== 'teacher')
                    <a href="{{ route('dashboard') }}"
                       class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                       {{ request()->routeIs('dashboard')
                          ? 'bg-red-600 text-white shadow-md scale-105'
                          : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                        {{ __('messages.dashboard') }}
                    </a>
                @endif

                @if(in_array(auth()->user()->role,['stock_manager','hr_manager']))
                    <a href="{{ route('items.index') }}"
                       class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                       {{ request()->routeIs('items.*')
                          ? 'bg-red-600 text-white shadow-md scale-105'
                          : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                        {{ __('messages.items') }}
                    </a>
                @endif

                <a href="{{ route('requests.index') }}"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                   {{ request()->routeIs('requests.*')
                      ? 'bg-red-600 text-white shadow-md scale-105'
                      : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                    {{ __('messages.requests') }}
                </a>

                @if(in_array(auth()->user()->role,['stock_manager','hr_manager']))
                    <a href="{{ route('purchase-orders.index') }}"
                       class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300
                       {{ request()->routeIs('purchase-orders.*')
                          ? 'bg-red-600 text-white shadow-md scale-105'
                          : 'text-green-800 hover:bg-white hover:shadow-md hover:scale-105' }}">
                        {{ __('messages.purchase_orders') }}
                    </a>
                @endif

                @if(in_array(auth()->user()->role,['finance_manager','hr_manager']))
                    <a href="{{ route('invoices.index') }}"
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

                <!-- Language Switcher -->
                <div x-data="{ open:false }" class="relative">
                    <button @click="open=!open"
                        class="px-3 py-2 rounded-lg text-green-700 hover:bg-green-50 transition">
                        🌐 {{ config('app.available_locales')[app()->getLocale()]['name'] }}
                    </button>

                    <div x-show="open" x-transition @click.away="open=false"
                         class="absolute right-0 mt-2 w-40 bg-white rounded-xl shadow-lg border z-50">
                        @foreach(config('app.available_locales') as $code=>$locale)
                            <a href="{{ route('locale.switch',$code) }}"
                               class="block px-4 py-2 hover:bg-green-50
                               {{ app()->getLocale()===$code ? 'font-semibold text-green-700' : '' }}">
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
                        {{ ucfirst(str_replace('_',' ',auth()->user()->role)) }}
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg
                                   hover:bg-green-700 transition">
                        {{ __('messages.logout') }}
                    </button>
                </form>
                @endauth
            </div>

            <!-- MOBILE BUTTON -->
            <button @click="mobileOpen=!mobileOpen"
                    class="md:hidden text-green-700 text-xl">
                ☰
            </button>
        </div>
    </div>

    <!-- MOBILE MENU -->
    <div x-show="mobileOpen" x-transition class="md:hidden bg-white border-t shadow-lg">
        <div class="px-4 py-4 space-y-2">


            <!-- Language Mobile -->
                <div x-data="{ open:false }" class="pt-2 border-t">
                    <button @click="open=!open"
                            class="w-full px-4 py-2 text-green-700 text-left">
                        🌐 {{ config('app.available_locales')[app()->getLocale()]['name'] }} ▾
                    </button>

                    <div x-show="open" x-transition>
                        @foreach(config('app.available_locales') as $code=>$locale)
                            <a href="{{ route('locale.switch',$code) }}"
                               class="block px-6 py-2 hover:bg-green-50">
                                {{ $locale['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @auth
                @if(auth()->user()->role !== 'teacher')
                    <a href="{{ route('dashboard') }}"
                       class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('dashboard')
                          ? 'bg-red-600 text-white'
                          : 'text-green-700 hover:bg-green-50' }}">
                        {{ __('messages.dashboard') }}
                    </a>
                @endif

                @if(in_array(auth()->user()->role,['stock_manager','hr_manager']))
                    <a href="{{ route('items.index') }}"
                       class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('items.*')
                          ? 'bg-red-600 text-white'
                          : 'text-green-700 hover:bg-green-50' }}">
                        {{ __('messages.items') }}
                    </a>
                @endif

                <a href="{{ route('requests.index') }}"
                   class="block px-4 py-2 rounded-lg
                   {{ request()->routeIs('requests.*')
                      ? 'bg-red-600 text-white'
                      : 'text-green-700 hover:bg-green-50' }}">
                    {{ __('messages.requests') }}
                </a>

                @if(in_array(auth()->user()->role,['stock_manager','hr_manager']))
                    <a href="{{ route('purchase-orders.index') }}"
                       class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('purchase-orders.*')
                          ? 'bg-red-600 text-white'
                          : 'text-green-700 hover:bg-green-50' }}">
                        {{ __('messages.purchase_orders') }}
                    </a>
                @endif

                @if(in_array(auth()->user()->role,['finance_manager','hr_manager']))
                    <a href="{{ route('invoices.index') }}"
                       class="block px-4 py-2 rounded-lg
                       {{ request()->routeIs('invoices.*')
                          ? 'bg-red-600 text-white'
                          : 'text-green-700 hover:bg-green-50' }}">
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

<main class="container mx-auto px-4 py-24 fade-in {{ request()->routeIs('login') ? 'login-main' : 'py-24' }}">
    @yield('content')
</main>



@stack('scripts')
</body>
</html>
