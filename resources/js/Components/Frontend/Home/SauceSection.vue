<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import SauceCard from "./SauceCard.vue";

defineProps({
    products: { type: Array, default: () => [] },
});

const swiperInstance = ref(null);
// Start unlocked so the arrows are there from the first paint; hidden later only if everything fits.
const isLocked = ref(false);
const isBeginning = ref(true);
const isEnd = ref(false);

const sync = (instance = swiperInstance.value) => {
    if (!instance) return;
    isLocked.value = instance.isLocked;
    isBeginning.value = instance.isBeginning;
    isEnd.value = instance.isEnd;
};

const onSwiper = (instance) => {
    swiperInstance.value = instance;
    sync(instance);
};

const breakpoints = {
    0: { slidesPerView: 2.2, spaceBetween: 12 },
    640: { slidesPerView: 3.2, spaceBetween: 16 },
    1024: { slidesPerView: 4, spaceBetween: 16 },
    1280: { slidesPerView: 5, spaceBetween: 20 },
};
</script>

<template>
    <section v-if="products.length" class="bg-white py-8 md:py-14">
        <div class="container mx-auto px-4">
            <div class="grid items-center gap-6 lg:grid-cols-[minmax(230px,290px)_minmax(0,1fr)] lg:gap-10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary md:text-sm">
                        Our Collection
                    </p>
                    <h2 class="mt-3 font-serif text-[1.7rem] font-semibold leading-[1.15] tracking-tight sm:text-4xl lg:text-[2.4rem]" style="color: color-mix(in srgb, var(--primary-color) 45%, black)">
                        Something delicious<br />
                        for <span class="text-primary">every taste.</span>
                    </h2>

                    <div class="mt-4 flex items-center gap-3 text-primary" aria-hidden="true">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 21c0-7 4-13 14-15-2 10-8 14-14 15z" />
                            <path d="M5 21c1-4 3-8 7-11" />
                        </svg>
                        <span class="h-px w-24 md:w-32" style="background: color-mix(in srgb, var(--primary-color) 40%, white)"></span>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-4 md:mt-6 lg:block">
                        <Link
                            :href="route('category.show', 'sauce')"
                            class="inline-flex h-11 items-center gap-2 rounded-full px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90 md:h-12"
                            style="background: color-mix(in srgb, var(--primary-color) 70%, black)"
                        >
                            View All Sauces
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </Link>

                        <div v-if="!isLocked" class="flex items-center gap-2 lg:hidden">
                            <button type="button" aria-label="Previous sauces" :disabled="isBeginning" class="nav-btn" @click="swiperInstance?.slidePrev()">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 12H5M11 18l-6-6 6-6" />
                                </svg>
                            </button>
                            <button type="button" aria-label="Next sauces" :disabled="isEnd" class="nav-btn" @click="swiperInstance?.slideNext()">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="min-w-0">
                    <div v-if="!isLocked" class="mb-2 hidden items-center justify-end gap-3 lg:flex">
                        <button type="button" aria-label="Previous sauces" :disabled="isBeginning" class="nav-btn" @click="swiperInstance?.slidePrev()">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M11 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <button type="button" aria-label="Next sauces" :disabled="isEnd" class="nav-btn" @click="swiperInstance?.slideNext()">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </button>
                    </div>

                    <!-- Padding keeps the card shadows from being clipped by the slider edge. -->
                    <Swiper
                        class="sauce-swiper"
                        :breakpoints="breakpoints"
                        :slides-per-view="2.2"
                        :space-between="12"
                        :watch-overflow="true"
                        @swiper="onSwiper"
                        @slide-change="sync()"
                        @breakpoint="sync()"
                        @resize="sync()"
                    >
                        <SwiperSlide v-for="product in products" :key="product.id" class="!h-auto">
                            <SauceCard :product="product" />
                        </SwiperSlide>
                    </Swiper>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sauce-swiper {
    padding: 10px 6px 24px;
    margin: -10px -6px -24px;
}

.nav-btn {
    @apply flex h-9 w-9 items-center justify-center rounded-full border border-primary bg-white text-primary transition-colors duration-200 hover:bg-primary hover:text-white disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-white disabled:text-gray-300 md:h-11 md:w-11;
}
</style>
