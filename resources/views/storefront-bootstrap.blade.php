{{-- The first screen stays usable while Vue loads, and when JavaScript is unavailable. --}}
@php
    $siteSettings = data_get($page, 'props.settings', []);
    $logo = data_get($siteSettings, 'media.logo');
    $siteName = data_get($siteSettings, 'generalSettings.app_name', config('app.name'));
    $navCategories = (array) data_get($page, 'props.navCategories', []);
    $isHome = request()->routeIs('home');
    $navLink = 'relative inline-flex h-24 items-center gap-1 text-[16px] font-medium transition-colors hover:text-primary';
    $authUser = data_get($page, 'props.auth.user');
    $cartCount = (int) data_get($page, 'props.cartCount', 0);
@endphp
<header class="sticky top-0 z-50 bg-white shadow-sm font-sans transition-[background-color,box-shadow] duration-300 lg:shadow-none {{ $isHome ? 'lg:fixed lg:inset-x-0' : '' }} lg:bg-transparent">
    <!-- Main Header -->
    <div class="bg-white border-b border-gray-100 lg:border-0 lg:bg-transparent">
        <div class="container mx-auto px-4 lg:max-w-none lg:px-0">
            <!-- Desktop Layout (keep in sync with FrontendHeader.vue) -->
            <div class="relative hidden h-24 items-center pl-[clamp(200px,18vw,320px)] pr-6 lg:flex xl:pr-10">
                <a href="{{ route('home') }}" class="absolute left-[clamp(24px,4vw,72px)] flex items-start transition-all duration-300 {{ $isHome ? 'top-3' : 'top-1/2 -translate-y-1/2' }}" aria-label="{{ $siteName }}">
                    @if($logo)
                        <img src="{{ $logo }}" alt="{{ $siteName }}" class="w-auto max-w-[220px] object-contain transition-all duration-300 {{ $isHome ? 'h-[150px] xl:h-[180px]' : 'h-[72px]' }}">
                    @else
                        <span class="text-2xl font-black tracking-tighter text-gray-900">{{ $siteName }}</span>
                    @endif
                </a>

                <!-- Menu -->
                <nav aria-label="Main" class="flex items-center gap-x-7 xl:gap-x-10">
                    <div class="group relative">
                        <a href="{{ route('products.index') }}" class="{{ $navLink }} text-gray-800">
                            Our Kitchen
                            @if(count($navCategories))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200 group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            @endif
                        </a>
                    </div>
                    <a href="{{ route('pages.show', 'about-us') }}" class="{{ $navLink }} text-gray-800">Our Story</a>
                    <a href="{{ route('recipes.index') }}" class="{{ $navLink }} text-gray-800">Food Journal</a>
                    <a href="{{ route('wellness.index') }}" class="{{ $navLink }} text-gray-800">Wellness</a>
                    <a href="{{ route('products.index') }}" class="{{ $navLink }} text-gray-800">Shop</a>
                </nav>

                <!-- Actions -->
                <div class="ml-auto flex items-center gap-4 xl:gap-5">
                    <a href="{{ route('products.index') }}" class="flex h-11 w-11 items-center justify-center text-gray-800 transition-colors hover:text-primary" aria-label="Search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="h-7 w-7"><path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg>
                    </a>
                    <span class="h-9 w-px bg-gray-500/50" aria-hidden="true"></span>
                    <div class="group relative">
                        <a href="{{ $authUser ? route('user.dashboard') : route('login') }}" class="flex h-11 w-11 items-center justify-center text-gray-800 transition-colors hover:text-primary" aria-label="{{ $authUser ? 'My account' : 'Login or register' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="h-7 w-7"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"/></svg>
                        </a>
                    </div>
                    <a href="{{ route('cart.index') }}" class="relative flex h-11 w-11 items-center justify-center text-gray-800 transition-colors hover:text-primary" aria-label="Cart">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" class="h-7 w-7"><path d="M239.89,198.12l-14.26-120a16,16,0,0,0-16-14.12H176a48,48,0,0,0-96,0H46.33a16,16,0,0,0-16,14.12l-14.26,120A16,16,0,0,0,20,210.6a16.13,16.13,0,0,0,12,5.4H223.92A16.13,16.13,0,0,0,236,210.6,16,16,0,0,0,239.89,198.12ZM128,32a32,32,0,0,1,32,32H96A32,32,0,0,1,128,32ZM32,200,46.33,80H80v24a8,8,0,0,0,16,0V80h64v24a8,8,0,0,0,16,0V80h33.75l14.17,120Z"/></svg>
                        @if($cartCount > 0)
                            <span class="absolute -right-0.5 top-0 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1 text-[11px] font-semibold leading-none text-white">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('products.index') }}" class="inline-flex h-14 items-center gap-2 rounded-full px-8 text-[17px] font-medium text-white shadow-sm transition-opacity hover:opacity-90" style="background-color: color-mix(in srgb, var(--primary-color) 70%, black)">
                        Order Now
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
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
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-[9px] font-medium rounded-full flex items-center justify-center border-2 border-white">
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
</header>
<main>
    @if($desktopImage)
        <section>
            <a href="{{ data_get($firstSlider, 'Link') ?: route('products.index') }}" class="block overflow-hidden">
                <picture>
                    @if($mobileImage)
                        <source media="(max-width: 768px)" srcset="{{ data_get($firstSlider, 'responsive_mobile_image.srcset') ?: $mobileImage }}" sizes="100vw"
                            width="{{ data_get($firstSlider, 'responsive_mobile_image.width') }}" height="{{ data_get($firstSlider, 'responsive_mobile_image.height') }}">
                    @endif
                    <img src="{{ $desktopImage }}" srcset="{{ data_get($firstSlider, 'responsive_image.srcset') }}" sizes="100vw"
                        width="{{ data_get($firstSlider, 'responsive_image.width') }}" height="{{ data_get($firstSlider, 'responsive_image.height') }}"
                        alt="{{ $siteName }}" fetchpriority="high" loading="eager" decoding="async" class="w-full h-full object-cover block">
                </picture>
            </a>
        </section>
    @endif
</main>
