<script setup>
// Four selling points in a light glass bar centred on the seam between the banner and the
// next section. The wrapper takes no height, so the two sections touch with no gap.
const features = [
    { title: "100% Natural Ingredients", icon: "leaf" },
    { title: "Rich in Nutrients", icon: "heart" },
    { title: "No Artificial Additives", icon: "mortar" },
    { title: "Supports Gut Health", icon: "sprout" },
];
</script>

<template>
    <section class="relative z-10 h-0" aria-label="Why our sauces">
        <div class="absolute inset-x-0 top-0 -translate-y-1/2 px-4">
        <div
            class="container mx-auto rounded-2xl border border-white/50 bg-white/15 px-3 py-3 shadow-[0_8px_30px_rgba(0,0,0,0.08)] backdrop-blur-[3px] md:rounded-[22px] md:px-6 md:py-4"
        >
            <div class="grid grid-cols-2 gap-x-2 gap-y-3 md:grid-cols-4 md:gap-0 md:divide-x md:divide-[#3d5a2c]/30">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="flex items-center gap-2.5 md:justify-center md:gap-4 md:px-4"
                >
                    <span class="shrink-0" style="color: color-mix(in srgb, var(--primary-color) 70%, black)">
                        <!-- leaf in a circle -->
                        <svg v-if="feature.icon === 'leaf'" class="h-8 w-8 md:h-12 md:w-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="24" cy="21" r="15" />
                            <path d="M24 44V12" />
                            <path d="M24 30c-5-1-8-5-8-10 5 0 8 3 8 8M24 24c5-1 8-5 8-10-5 0-8 3-8 8" />
                        </svg>
                        <!-- heart with a pulse line -->
                        <svg v-else-if="feature.icon === 'heart'" class="h-8 w-8 md:h-12 md:w-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M24 41S6 30.5 6 17.5A9.5 9.5 0 0124 13a9.5 9.5 0 0118 4.5C42 30.5 24 41 24 41z" />
                            <path d="M8 24h9l3-6 5 12 3-6h12" />
                        </svg>
                        <!-- mortar and pestle -->
                        <svg v-else-if="feature.icon === 'mortar'" class="h-8 w-8 md:h-12 md:w-12" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true">
                            <path d="M37.6 6.2a3 3 0 014.2 4.2L30.5 21.7l-4.2-4.2L37.6 6.2z" />
                            <path d="M5 22h38a2 2 0 012 2.2C44 33 37 39.5 29 40.8V43a1 1 0 01-1 1H20a1 1 0 01-1-1v-2.2C11 39.5 4 33 3 24.2A2 2 0 015 22z" />
                        </svg>
                        <!-- sprout -->
                        <svg v-else class="h-8 w-8 md:h-12 md:w-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M24 38V22" />
                            <path d="M24 24c0-8-6-13-15-13 0 8 6 13 15 13zM24 22c0-8 6-13 15-13 0 8-6 13-15 13z" />
                            <path d="M10 42c4-4 9-6 14-6s10 2 14 6" />
                        </svg>
                    </span>

                    <p class="text-[12px] font-medium leading-tight text-gray-900 md:max-w-[9.5rem] md:text-[17px] md:leading-snug">
                        {{ feature.title }}
                    </p>
                </div>
            </div>
        </div>
        </div>
    </section>
</template>
