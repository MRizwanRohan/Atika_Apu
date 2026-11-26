<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import paperBg from "@/../images/product-section-bg.webp";
import greenBg from "@/../images/footer-bg.webp";

// Brand story strip: spice photo | story with the chef illustration | health promises.
// Replace public/assets/img/bg/story-spices.jpg to change the photo on the left.
const spicesImage = "/assets/img/bg/story-spices.jpg";

const page = usePage();
const logo = computed(() => page.props.settings?.media?.logo || "");

const promises = ["Rich in Nutrients", "Supports Gut Health", "No Artificial Additives", "Locally Sourced"];
const dark = "color-mix(in srgb, var(--primary-color) 45%, black)";
</script>

<template>
    <section class="grid md:grid-cols-[1fr_1.45fr_1fr]">
        <!-- Spices photo -->
        <div class="h-56 bg-[#5a3a1e] bg-cover bg-center md:h-auto md:min-h-[340px]" :style="{ backgroundImage: `url(${spicesImage})` }" role="img" aria-label="Bowls of spices" />

        <!-- Story -->
        <div
            class="flex items-center justify-between gap-4 overflow-hidden bg-[#f6d999] bg-cover bg-center px-6 py-10 md:px-8 lg:px-12"
            :style="{ backgroundImage: `url(${paperBg})` }"
        >
            <div class="min-w-0 flex-1">
                <h2 class="font-serif text-3xl font-semibold leading-tight lg:text-[2.4rem]" :style="{ color: dark }">
                    The Humming Chef Story
                </h2>
                <p class="mt-4 text-[15px] leading-relaxed text-gray-800 lg:text-base">
                    A love letter to Bengali food, good health and joyful eating.
                </p>
                <Link
                    :href="route('pages.show', 'about-us')"
                    class="mt-6 inline-flex items-center gap-2 border-b-2 pb-0.5 text-[15px] font-semibold transition-opacity hover:opacity-80"
                    :style="{ color: dark, borderColor: dark }"
                >
                    Our Story
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </Link>
            </div>

            <img
                v-if="logo"
                :src="logo"
                alt=""
                aria-hidden="true"
                loading="lazy"
                class="pointer-events-none h-32 w-auto shrink-0 object-contain sm:h-40 lg:h-56 xl:h-64"
            />
        </div>

        <!-- Health promises -->
        <div
            class="flex items-center bg-[#2f4a22] bg-cover bg-center px-6 py-10 text-white md:px-8 lg:px-12"
            :style="{ backgroundImage: `url(${greenBg})` }"
        >
            <div>
                <h2 class="font-serif text-2xl font-semibold leading-tight lg:text-[2rem]">
                    Good Food.<br />Greater Health.
                </h2>
                <ul class="mt-5 space-y-3">
                    <li v-for="item in promises" :key="item" class="flex items-center gap-3 text-[15px]">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#f3b334] text-[#2f4a22]">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12.5l4.5 4.5L19 7.5" />
                            </svg>
                        </span>
                        {{ item }}
                    </li>
                </ul>
                <Link
                    :href="route('pages.show', 'why-humming-chef')"
                    class="mt-7 inline-flex h-11 items-center gap-2 rounded-full border border-white/80 px-6 text-sm font-semibold text-white transition-colors hover:bg-white hover:text-[#2f4a22]"
                >
                    Learn More
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </Link>
            </div>
        </div>
    </section>
</template>
