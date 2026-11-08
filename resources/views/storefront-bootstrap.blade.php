{{-- The first screen stays usable while Vue loads, and when JavaScript is unavailable. --}}
@php
    $siteSettings = data_get($page, 'props.settings', []);
    $logo = data_get($siteSettings, 'media.logo');
    $siteName = data_get($siteSettings, 'generalSettings.app_name', config('app.name'));
    $rootCategories = collect(data_get($page, 'props.categories', []))->filter(
        fn ($category) => in_array(data_get($category, 'status'), ['active', 1], true)
    );
    $authUser = data_get($page, 'props.auth.user');
    $cartCount = (int) data_get($page, 'props.cartCount', 0);
@endphp
<header class="sticky top-0 z-50 bg-white shadow-sm font-sans">
    <div class="hidden lg:block bg-primary text-white py-1.5 overflow-hidden">
        <div class="container mx-auto px-4 flex items-center justify-between">
            @if(data_get($siteSettings, 'generalSettings.top_notice'))
                <div class="text-[10px] w-2/3 sm:text-xs tracking-widest">{{ strip_tags(data_get($siteSettings, 'generalSettings.top_notice')) }}</div>
            @endif
            <div class="flex items-center justify-end w-full gap-6">
                <a href="{{ route('order.track') }}" class="flex items-center gap-1.5 text-[10px] sm:text-xs font-medium text-white/90">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-4 h-4"><path d="M255.42,117l-14-35A15.93,15.93,0,0,0,226.58,72H192V64a8,8,0,0,0-8-8H32A16,16,0,0,0,16,72V184a16,16,0,0,0,16,16H49a32,32,0,0,0,62,0h50a32,32,0,0,0,62,0h17a16,16,0,0,0,16-16V120A7.94,7.94,0,0,0,255.42,117ZM192,88h34.58l9.6,24H192ZM32,72H176v64H32ZM80,208a16,16,0,1,1,16-16A16,16,0,0,1,80,208Zm81-24H111a32,32,0,0,0-62,0H32V152H176v12.31A32.11,32.11,0,0,0,161,184Zm31,24a16,16,0,1,1,16-16A16,16,0,0,1,192,208Zm48-24H223a32.06,32.06,0,0,0-31-24V128h48Z"/></svg>
                    <span>Track Order</span>
                </a>
                <a href="{{ route('pre-order-lead.form') }}" class="flex items-center gap-1.5 text-[10px] sm:text-xs font-medium text-white/90">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-4 h-4"><path d="M223.68,66.15,135.68,18h0a15.88,15.88,0,0,0-15.36,0l-88,48.17a16,16,0,0,0-8.32,14v95.64a16,16,0,0,0,8.32,14l88,48.17a15.88,15.88,0,0,0,15.36,0l88-48.17a16,16,0,0,0,8.32-14V80.18A16,16,0,0,0,223.68,66.15ZM128,32h0l80.34,44L128,120,47.66,76ZM40,90l80,43.78v85.79L40,175.82Zm96,129.57V133.82L216,90v85.78Z"/></svg>
                    <span>Pre Order</span>
                </a>
                <a href="{{ route('offers.index') }}" class="flex items-center gap-1.5 text-[10px] sm:text-xs font-medium text-white/90">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-4 h-4"><path d="M216,72H180.92c.39-.33.79-.65,1.17-1A29.53,29.53,0,0,0,192,49.57,32.62,32.62,0,0,0,158.44,16,29.53,29.53,0,0,0,137,25.91a54.94,54.94,0,0,0-9,14.48,54.94,54.94,0,0,0-9-14.48A29.53,29.53,0,0,0,97.56,16,32.62,32.62,0,0,0,64,49.57,29.53,29.53,0,0,0,73.91,71c.38.33.78.65,1.17,1H40A16,16,0,0,0,24,88v32a16,16,0,0,0,16,16v64a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V136a16,16,0,0,0,16-16V88A16,16,0,0,0,216,72ZM149,36.51a13.69,13.69,0,0,1,10-4.5h.49A16.62,16.62,0,0,1,176,49.08a13.69,13.69,0,0,1-4.5,10c-9.49,8.4-25.24,11.36-35,12.4C137.7,60.89,141,45.5,149,36.51Zm-64.09.36A16.63,16.63,0,0,1,96.59,32h.49a13.69,13.69,0,0,1,10,4.5c8.39,9.48,11.35,25.2,12.39,34.92-9.72-1-25.44-4-34.92-12.39a13.69,13.69,0,0,1-4.5-10A16.6,16.6,0,0,1,84.87,36.87ZM40,88h80v32H40Zm16,48h64v64H56Zm144,64H136V136h64Zm16-80H136V88h80v32Z"/></svg>
                    <span>Offers</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="bg-white border-b border-gray-100">
        <div class="container mx-auto px-4">
            <!-- Desktop Layout -->
            <div class="hidden lg:flex items-center justify-between h-14 gap-12">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex-shrink-0 flex items-center">
                    @if($logo)
                        <img src="{{ $logo }}" alt="{{ $siteName }}" class="w-[130px] object-contain">
                    @else
                        <div class="text-2xl font-black tracking-tighter">
                            <span class="text-gray-900">{{ $siteName }}</span>
                        </div>
                    @endif
                </a>

                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl px-4">
                    <form action="{{ route('search') }}" method="get" class="flex items-stretch border border-gray-200 rounded-lg bg-gray-50 overflow-hidden">
                        <input name="q" type="search" placeholder="Search in..." aria-label="Search products" class="flex-1 pl-4 pr-2 py-2 outline-none border-none text-xs bg-transparent text-gray-900 focus:ring-0">
                        <div class="flex items-center gap-1 pr-2">
                            <button type="submit" aria-label="Search" class="p-2 ml-1 text-primary hover:bg-primary/5 rounded-md transition-all active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-4 h-4"><path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-6">
                    <!-- Account -->
                    <a href="{{ $authUser ? route('user.dashboard') : route('login') }}" class="flex flex-col items-center gap-0.5 text-gray-700">
                        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-5 h-5"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"/></svg>
                        </div>
                        <span class="text-[10px] font-medium uppercase tracking-tight">
                            {{ $authUser ? explode(' ', data_get($authUser, 'name', ''))[0] : 'Login/Register' }}
                        </span>
                    </a>

                    <!-- Cart -->
                    <a href="{{ route('cart.index') }}" class="flex flex-col items-center gap-0.5 text-gray-700">
                        <div class="relative w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-5 h-5"><path d="M104,216a16,16,0,1,1-16-16A16,16,0,0,1,104,216Zm88-16a16,16,0,1,0,16,16A16,16,0,0,0,192,200ZM239.71,74.14l-25.64,92.28A24.06,24.06,0,0,1,191,184H92.16A24.06,24.06,0,0,1,69,166.42L33.92,40H16a8,8,0,0,1,0-16H40a8,8,0,0,1,7.71,5.86L57.19,64H232a8,8,0,0,1,7.71,10.14ZM221.47,80H61.64l22.81,82.14A8,8,0,0,0,92.16,168H191a8,8,0,0,0,7.71-5.86Z"/></svg>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-[9px] font-medium rounded-full flex items-center justify-center border-2 border-white">
                                {{ $cartCount }}
                            </span>
                        </div>
                        <span class="text-[10px] font-medium uppercase tracking-tight">Cart</span>
                    </a>
                </div>
            </div>

            <!-- Mobile Layout (2 Rows) -->
            <div class="lg:hidden flex flex-col py-2">
                <!-- Row 1: Menu, Logo, Cart -->
                <div class="flex items-center justify-between mb-3">
                    <span class="p-1 -ml-1 text-gray-900" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="16" viewBox="0 0 30 16" fill="currentColor">
                            <rect width="30" height="1.5"></rect>
                            <rect y="7" width="20" height="1.5"></rect>
                            <rect y="14" width="30" height="1.5"></rect>
                        </svg>
                    </span>

                    <a href="{{ route('home') }}" class="flex-shrink-0 flex items-center gap-2">
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                        @else
                            <span class="text-lg font-black italic tracking-tighter text-gray-900">{{ $siteName }}</span>
                        @endif
                    </a>

                    <a href="{{ route('cart.index') }}" class="relative p-1 -mr-1 text-gray-900" aria-label="Cart">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-7 h-7"><path d="M104,216a16,16,0,1,1-16-16A16,16,0,0,1,104,216Zm88-16a16,16,0,1,0,16,16A16,16,0,0,0,192,200ZM239.71,74.14l-25.64,92.28A24.06,24.06,0,0,1,191,184H92.16A24.06,24.06,0,0,1,69,166.42L33.92,40H16a8,8,0,0,1,0-16H40a8,8,0,0,1,7.71,5.86L57.19,64H232a8,8,0,0,1,7.71,10.14ZM221.47,80H61.64l22.81,82.14A8,8,0,0,0,92.16,168H191a8,8,0,0,0,7.71-5.86Z"/></svg>
                        @if($cartCount > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-black text-white text-[9px] font-medium rounded-full flex items-center justify-center border-2 border-white">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>
                </div>

                <!-- Row 2: Full Width Search -->
                <div class="w-full">
                    <form action="{{ route('search') }}" method="get" class="flex items-stretch border border-gray-200 rounded-lg bg-gray-50 overflow-hidden">
                        <input name="q" type="search" placeholder="Search products, models, brands..." aria-label="Search products" class="flex-1 pl-4 pr-2 py-2 outline-none border-none text-xs bg-transparent text-gray-900 focus:ring-0">
                        <div class="flex items-center gap-1 pr-2">
                            <button type="submit" aria-label="Search" class="p-2 ml-1 text-primary hover:bg-primary/5 rounded-md transition-all active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="w-4 h-4"><path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Category Navigation -->
    <nav aria-label="Categories" class="hidden lg:block bg-white text-black border-t border-b border-gray-200">
        <div class="container mx-auto px-4">
            <ul class="flex items-center justify-center gap-1">
                @foreach($rootCategories as $category)
                    <li class="relative">
                        <a href="{{ route('category.show', data_get($category, 'slug')) }}" class="flex items-center gap-1 px-2 py-3 text-sm font-medium text-black">
                            {{ data_get($category, 'name') }}
                            @if(!empty(data_get($category, 'children')))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
</header>
<main>
    @if($desktopImage)
        <section class="bg-white">
            <a href="{{ data_get($firstSlider, 'Link') ?: route('products.index') }}" class="block overflow-hidden shadow-sm">
                <picture>
                    @if($mobileImage)
                        <source media="(max-width: 768px)" srcset="{{ data_get($firstSlider, 'responsive_mobile_image.srcset') ?: $mobileImage }}" sizes="100vw"
                            width="{{ data_get($firstSlider, 'responsive_mobile_image.width') }}" height="{{ data_get($firstSlider, 'responsive_mobile_image.height') }}">
                    @endif
                    <img src="{{ $desktopImage }}" srcset="{{ data_get($firstSlider, 'responsive_image.srcset') }}" sizes="(min-width: 1280px) 1200px, 100vw"
                        width="{{ data_get($firstSlider, 'responsive_image.width') }}" height="{{ data_get($firstSlider, 'responsive_image.height') }}"
                        alt="{{ $siteName }}" fetchpriority="high" loading="eager" decoding="async" class="w-full h-full object-cover block">
                </picture>
            </a>
        </section>
    @endif
</main>
