<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $isStorefront = str_starts_with($page['component'], 'Frontend/')
                || str_starts_with($page['component'], 'User/')
                || str_starts_with($page['component'], 'PreOrderLead/');
            $firstSlider = $page['component'] === 'Frontend/Home/Home'
                ? data_get($page, 'props.sliders.0') : null;
            $desktopImage = data_get($firstSlider, 'image');
            $mobileImage = data_get($firstSlider, 'mobile_image');
            $primaryColor = data_get($page, 'props.settings.generalSettings.primary_color');
            $primaryColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $primaryColor) ? $primaryColor : null;
        @endphp

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts & Preconnects -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link id="site-font-stylesheet" href="https://fonts.googleapis.com/css2?{{ $isStorefront ? 'family=Heebo:wght@400..900&family=Oswald:wght@500..700&family=Great+Vibes' : 'family=Montserrat:wght@400..900' }}&display=swap" rel="stylesheet">

        {{-- Discover the actual responsive hero before the Vue bundle starts. --}}
        @if($desktopImage)
            <link rel="preload" as="image" href="{{ $desktopImage }}" fetchpriority="high"
                @if($mobileImage) media="(min-width: 769px)" @endif
                @if(data_get($firstSlider, 'responsive_image.srcset'))
                    imagesrcset="{{ data_get($firstSlider, 'responsive_image.srcset') }}"
                    imagesizes="(min-width: 1280px) 1200px, 100vw"
                @endif>
            @if($mobileImage)
                <link rel="preload" as="image" href="{{ $mobileImage }}" fetchpriority="high" media="(max-width: 768px)"
                    @if(data_get($firstSlider, 'responsive_mobile_image.srcset'))
                        imagesrcset="{{ data_get($firstSlider, 'responsive_mobile_image.srcset') }}" imagesizes="100vw"
                    @endif>
            @endif
        @endif

        <!-- Scripts -->

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])

        {{-- Apply the real theme color before first paint so it never flashes the CSS fallback color. --}}
        @if($primaryColor)
            <style>:root{--primary-color:{{ $primaryColor }};}</style>
        @endif

        @inertiaHead

        @if($isStorefront)
            {{-- Fonts download only when first used; request the section-title fonts during parsing so they are ready before Vue renders them. --}}
            <script>document.fonts&&(document.fonts.load('700 1em "Oswald"'),document.fonts.load('1em "Great Vibes"'));</script>
        @endif
    </head>
    <body class="font-sans antialiased {{ $isStorefront ? 'frontend' : '' }}">
        @if($page['component'] === 'Frontend/Home/Home' && !($__inertiaSsrResponse ?? null))
            <div id="storefront-skeleton">
                @include('storefront-bootstrap')
            </div>
            <div id="app" data-page="{{ json_encode($page) }}"></div>
        @else
            @inertia
        @endif
    </body>
</html>
