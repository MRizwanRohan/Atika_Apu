<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";

defineProps({
    products: { type: Array, default: () => [] },
});

const swiperInstance = ref(null);
const isLocked = ref(true);
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
    0: { slidesPerView: 2, spaceBetween: 8 },
    768: { slidesPerView: 3, spaceBetween: 16 },
    1024: { slidesPerView: 4, spaceBetween: 24 },
    1280: { slidesPerView: 5, spaceBetween: 24 },
};
</script>

<template>
    <section v-if="products.length" class="py-8 md:py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex items-end justify-between gap-4 mb-6 md:mb-8">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-semibold uppercase tracking-[0.15em] text-primary">
                        Explore Our Collection
                    </p>
                    <h2 class="mt-1 font-serif text-2xl sm:text-3xl md:text-4xl leading-tight text-gray-900">
                        Find your style
                    </h2>
                </div>

                <div class="flex items-center gap-2 md:gap-3 shrink-0">
                    <Link
                        :href="route('products.index')"
                        class="hidden md:inline-flex items-center gap-2 mr-1 text-sm font-semibold text-primary hover:gap-3 transition-all"
                    >
                        View All
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                    <template v-if="!isLocked">
                        <button type="button" aria-label="Previous" :disabled="isBeginning" class="nav-btn" @click="swiperInstance?.slidePrev()">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M11 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <button type="button" aria-label="Next" :disabled="isEnd" class="nav-btn" @click="swiperInstance?.slideNext()">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>

            <Swiper
                :breakpoints="breakpoints"
                :slides-per-view="2"
                :space-between="8"
                :watch-overflow="true"
                @swiper="onSwiper"
                @slide-change="sync()"
                @breakpoint="sync()"
                @resize="sync()"
            >
                <SwiperSlide v-for="product in products" :key="product.id" class="!h-auto">
                    <ProductCard :product="product" />
                </SwiperSlide>
            </Swiper>

            <div class="mt-6 md:hidden">
                <Link
                    :href="route('products.index')"
                    class="flex w-full items-center justify-center px-8 py-3 text-[12px] font-bold text-gray-900 bg-gray-50 border border-gray-200 rounded-lg active:bg-gray-100 transition-colors"
                >
                    View All Products
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
.nav-btn {
    @apply flex items-center justify-center w-9 h-9 md:w-11 md:h-11 rounded-full border border-primary text-primary bg-white transition-colors duration-200 hover:bg-primary hover:text-white disabled:border-gray-200 disabled:text-gray-300 disabled:bg-white disabled:cursor-not-allowed;
}
</style>
