{{-- The first screen stays usable while Vue loads, and when JavaScript is unavailable. --}}
@php
    $siteSettings = data_get($page, 'props.settings', []);
    $logo = data_get($siteSettings, 'media.logo');
    $siteName = data_get($siteSettings, 'generalSettings.app_name', config('app.name'));
    $rootCategories = collect(data_get($page, 'props.categories', []))->filter(
        fn ($category) => in_array(data_get($category, 'status'), ['active', 1], true)
    );
@endphp
<header class="sticky top-0 z-50 bg-white shadow-sm font-sans">
    <div class="hidden lg:block bg-primary text-white py-1.5 overflow-hidden">
        <div class="container mx-auto px-4 flex items-center justify-between">
            @if(data_get($siteSettings, 'generalSettings.top_notice'))
                <div class="text-[10px] w-2/3 sm:text-xs tracking-widest">{{ strip_tags(data_get($siteSettings, 'generalSettings.top_notice')) }}</div>
            @endif
            <div class="flex items-center justify-end w-full gap-6 text-xs">
                <a href="{{ route('order.track') }}">Track Order</a>
                <a href="{{ route('pre-order-lead.form') }}">Pre Order</a>
                <a href="{{ route('offers.index') }}">Offers</a>
            </div>
        </div>
    </div>
    <div class="bg-white border-b border-gray-100">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between lg:h-14 lg:gap-12 lg:py-0 py-2 flex-wrap">
                <div class="flex items-center justify-between w-full lg:w-auto h-9 lg:h-auto mb-3 lg:mb-0">
                    <a href="{{ route('home') }}" class="flex items-center">
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-8 w-auto lg:h-auto lg:w-[130px] object-contain">
                        @else
                            <span class="text-lg lg:text-2xl font-black">{{ $siteName }}</span>
                        @endif
                    </a>
                    <a href="{{ route('cart.index') }}" class="lg:hidden text-sm">Cart</a>
                </div>
                <form action="{{ route('search') }}" method="get" class="flex items-stretch border border-gray-200 rounded-lg bg-gray-50 overflow-hidden w-full lg:w-auto lg:flex-1">
                    <input name="q" type="search" placeholder="Search in..." aria-label="Search products" class="flex-1 pl-4 pr-2 py-2 outline-none border-none text-xs bg-transparent focus:ring-0">
                    <button type="submit" aria-label="Search" class="px-3 text-xs text-primary">Search</button>
                </form>
                <a href="{{ route('cart.index') }}" class="hidden lg:block text-sm">Cart</a>
            </div>
        </div>
    </div>
    <nav aria-label="Categories" class="hidden lg:block border-t border-b border-gray-200">
        <div class="container mx-auto px-4 flex items-center justify-center gap-1">
            @foreach($rootCategories as $category)
                <a href="{{ route('category.show', data_get($category, 'slug')) }}" class="flex items-center px-2 py-3 text-sm font-medium">{{ data_get($category, 'name') }}</a>
            @endforeach
        </div>
    </nav>
</header>
<main>
    @if($desktopImage)
        <section class="lg:py-6 py-0 bg-white">
            <div class="container mx-auto px-4">
                <a href="{{ data_get($firstSlider, 'Link') ?: route('products.index') }}" class="block rounded-2xl overflow-hidden shadow-sm">
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
            </div>
        </section>
    @endif
</main>
