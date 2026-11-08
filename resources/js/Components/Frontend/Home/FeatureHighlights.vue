<script setup>
const features = [
    {
        title: "100% Natural",
        description: "Real ingredients, no shortcuts.",
        icon: "leaf",
    },
    {
        title: "No Artificials",
        description: "No artificial colors, flavors or preservatives.",
        icon: "flask",
    },
    {
        title: "Real Spices",
        description: "Authentic spices for real authentic taste.",
        icon: "bowl",
    },
    {
        title: "Made with Love",
        description: "Crafted in small batches with passion and care.",
        icon: "heart",
    },
];
</script>

<template>
    <section class="relative z-10 -mt-10 md:-mt-12 px-4">
        <div
            class="container mx-auto bg-white rounded-2xl shadow-lg px-6 py-8 md:px-10 md:py-10"
        >
            <div
                class="grid grid-cols-2 md:grid-cols-4 gap-y-8 gap-x-4 md:divide-x md:divide-gray-200"
            >
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="flex flex-col items-center text-center px-2 md:px-4"
                >
                    <svg
                        v-if="feature.icon === 'leaf'"
                        class="h-9 w-9 text-primary mb-3"
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
                        class="h-9 w-9 text-primary mb-3"
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
                        class="h-9 w-9 text-primary mb-3"
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
                        class="h-9 w-9 text-primary mb-3"
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

                    <h3 class="font-bold text-gray-900 text-base md:text-lg mb-1">
                        {{ feature.title }}
                    </h3>
                    <p class="text-gray-500 text-xs md:text-sm leading-snug">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
