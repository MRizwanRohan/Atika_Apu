<script setup>
import { Link } from "@inertiajs/vue3";

const points = [
    { title: "Fresh Ingredients", text: "Sourced with care", icon: "M5 21c0-7 4-13 14-15-2 10-8 14-14 15zM5 21c1-4 3-8 7-11" },
    { title: "Small Batch", text: "Made with passion", icon: "M5 10h14v8a2 2 0 01-2 2H7a2 2 0 01-2-2v-8zM3 10h18M9 6h6M8 6a4 4 0 018 0" },
    { title: "Safe & Clean", text: "Hygienic & tested", icon: "M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3zM8.5 12l2.5 2.5 4.5-5" },
    { title: "Loved by Thousands", text: "Real reviews. Real people.", icon: "M16 20v-1a4 4 0 00-4-4H8a4 4 0 00-4 4v1M10 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM20 20v-1a4 4 0 00-3-3.9M16 4.2a3.5 3.5 0 010 6.6" },
];
</script>

<template>
    <section class="py-8 md:py-12">
        <div class="container mx-auto px-4">
            <div class="overflow-hidden rounded-3xl bg-[#f3f2ea]">
                <!-- Trust points -->
                <div class="grid grid-cols-2 gap-x-3 gap-y-4 px-4 py-5 md:grid-cols-4 md:gap-0 md:px-8 md:py-6 md:divide-x md:divide-gray-300/70">
                    <div v-for="p in points" :key="p.title" class="flex items-center gap-3 md:justify-center md:px-4">
                        <svg class="h-8 w-8 shrink-0 text-primary md:h-9 md:w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path :d="p.icon" />
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold leading-tight text-gray-900 md:text-sm">{{ p.title }}</p>
                            <p class="mt-0.5 text-[11px] leading-tight text-gray-600 md:text-[13px]">{{ p.text }}</p>
                        </div>
                    </div>
                </div>

                <!-- Call to action -->
                <div
                    class="flex flex-col items-start gap-5 rounded-3xl px-6 py-7 text-white md:flex-row md:items-center md:justify-between md:px-12 md:py-9"
                    style="background-color: color-mix(in srgb, var(--primary-color) 75%, black)"
                >
                    <h2 class="font-serif text-2xl font-semibold leading-snug md:text-[1.9rem]">
                        Be the first to know<br />
                        about <span style="color: color-mix(in srgb, var(--primary-color) 45%, #c6f26b)">new flavors &amp; recipes.</span>
                    </h2>
                    <Link
                        :href="route('pages.show', 'contact')"
                        class="inline-flex h-12 shrink-0 items-center gap-2 rounded-full bg-primary px-8 text-sm font-semibold text-white ring-2 ring-white/70 transition-opacity hover:opacity-90"
                    >
                        Contact Us
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
