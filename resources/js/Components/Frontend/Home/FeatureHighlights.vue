<script setup>
const features = [
    {
        title: "100% Natural",
        description: "Real ingredients. No shortcuts.",
        icon: "leaf",
    },
    {
        title: "No Artificials",
        description: "No colors, flavors or preservatives.",
        icon: "flask",
    },
    {
        title: "Rich in Flavor",
        description: "Authentic spices for real taste.",
        icon: "bowl",
    },
    {
        title: "Better for You",
        description: "Clean recipes for a healthier you.",
        icon: "heart",
    },
];
</script>

<template>
    <section class="relative z-10 -mt-2 md:-mt-12 px-4">
        <div
            class="container mx-auto rounded-xl bg-primary px-2 py-3 text-white shadow-md md:rounded-[28px] md:px-10 md:py-8 md:shadow-lg"
        >
            <div
                class="grid grid-cols-2 gap-y-3 md:grid-cols-4 md:gap-y-0 md:divide-x md:divide-white/20"
            >
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="flex flex-col items-center px-1 text-center md:px-6"
                >
                    <svg
                        v-if="feature.icon === 'leaf'"
                        class="h-6 w-6 md:h-9 md:w-9 mb-1 md:mb-3" style="color: color-mix(in srgb, var(--primary-color) 30%, white)"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 21c0-7 4-13 14-15-2 10-8 14-14 15z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 21c1-4 3-8 7-11"
                        />
                    </svg>

                    <svg
                        v-else-if="feature.icon === 'flask'"
                        class="h-6 w-6 md:h-9 md:w-9 mb-1 md:mb-3" style="color: color-mix(in srgb, var(--primary-color) 30%, white)"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 3h6M10 3v5.5L5.5 17a2 2 0 001.76 3h9.48a2 2 0 001.76-3L14 8.5V3"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M7.5 14.5h9"
                        />
                    </svg>

                    <svg
                        v-else-if="feature.icon === 'bowl'"
                        class="h-6 w-6 md:h-9 md:w-9 mb-1 md:mb-3" style="color: color-mix(in srgb, var(--primary-color) 30%, white)"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 12h16a8 8 0 01-16 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12V7a1 1 0 011-1h0a1 1 0 011 1v5M14 12V8a1 1 0 011-1h0a1 1 0 011 1v4"
                        />
                    </svg>

                    <svg
                        v-else
                        class="h-6 w-6 md:h-9 md:w-9 mb-1 md:mb-3" style="color: color-mix(in srgb, var(--primary-color) 30%, white)"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 20s-7-4.35-9.5-9A5.5 5.5 0 0112 5.5 5.5 5.5 0 0121.5 11c-2.5 4.65-9.5 9-9.5 9z"
                        />
                    </svg>

                    <h3 class="text-[12px] font-bold leading-tight text-white md:text-base md:mb-1">
                        {{ feature.title }}
                    </h3>
                    <p class="text-[9.5px] leading-tight text-white/85 md:text-sm md:leading-snug">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
