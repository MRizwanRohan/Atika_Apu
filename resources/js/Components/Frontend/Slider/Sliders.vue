<template>
    <section class="py-6 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Main Slider (Left side) -->
                <div class="lg:col-span-8">
                    <swiper
                        v-if="sliders && sliders.length > 0"
                        :modules="modules"
                        :slides-per-view="1"
                        :loop="sliders.length > 1"
                        :autoplay="{
                            delay: 5000,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true,
                        }"
                        :pagination="{
                            clickable: true,
                            dynamicBullets: true,
                        }"
                        :navigation="false"
                        class="main-slider rounded-2xl overflow-hidden shadow-sm h-full lg:h-[500px]"
                    >
                        <swiper-slide v-for="slider in sliders" :key="slider.id">
                            <Link
                                v-if="slider.Link"
                                :href="slider.Link"
                                class="block w-full h-full outline-none"
                            >
                                <img
                                    :src="slider.image"
                                    :alt="'G&G Motion Slide ' + slider.id"
                                    class="w-full h-full object-cover block"
                                />
                            </Link>
                            <div v-else class="w-full h-full outline-none">
                                <img
                                    :src="slider.image"
                                    :alt="'G&G Motion Slide ' + slider.id"
                                    class="w-full h-full object-cover block"
                                />
                            </div>
                        </swiper-slide>
                    </swiper>
                </div>

                <!-- Side Banners (Right side) -->
                <div class="lg:col-span-4 flex flex-row lg:flex-col gap-4 lg:h-[500px]">
                    <!-- Banner 1 -->
                    <div class="flex-1 relative overflow-hidden rounded-2xl shadow-sm group h-full">
                        <Link 
                            v-if="sideBanners?.new_arrival_b_banner1" 
                            :href="sideBanners.new_arrival_b_banner1_url || '#'" 
                            class="block h-full w-full"
                        >
                            <img 
                                :src="sideBanners.new_arrival_b_banner1" 
                                alt="Side Banner 1" 
                                class="w-full h-full object-cover transition-transform duration-500 "
                            />
                        </Link>
                    </div>
                    
                    <!-- Banner 2 -->
                    <div class="flex-1 relative overflow-hidden rounded-2xl shadow-sm group h-full">
                        <Link 
                            v-if="sideBanners?.new_arrival_b_banner2" 
                            :href="sideBanners.new_arrival_b_banner2_url || '#'" 
                            class="block h-full w-full"
                        >
                            <img 
                                :src="sideBanners.new_arrival_b_banner2" 
                                alt="Side Banner 2" 
                                class="w-full h-full object-cover transition-transform duration-500"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Swiper, SwiperSlide } from "swiper/vue";
import { Autoplay, Pagination, Navigation } from "swiper/modules";
import { Link } from "@inertiajs/vue3";

// Import Swiper styles
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/navigation";

const props = defineProps({
    sliders: {
        type: Array,
        required: true,
        default: () => [],
    },
    sideBanners: {
        type: Object,
        required: false,
        default: () => ({}),
    }
});

const modules = [Autoplay, Pagination, Navigation];
</script>

<style scoped>
.main-slider {
    --swiper-theme-color: #9c19fa;
    --swiper-navigation-size: 24px;
}

:deep(.swiper-button-next),
:deep(.swiper-button-prev) {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(8px);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: #9c19fa;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    opacity: 0;
}

.main-slider:hover :deep(.swiper-button-next),
.main-slider:hover :deep(.swiper-button-prev) {
    opacity: 1;
}

:deep(.swiper-button-next):after,
:deep(.swiper-button-prev):after {
    font-size: 14px;
    font-weight: bold;
}

:deep(.swiper-pagination-bullet-active) {
    width: 24px;
    border-radius: 4px;
}

@media (max-width: 1024px) {
}

@media (max-width: 640px) {
}

:deep(.swiper-button-next:hover),
:deep(.swiper-button-prev:hover) {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
}

:deep(.swiper-pagination-bullet) {
    width: 6px;
    height: 6px;
    background: #fff;
    opacity: 0.6;
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    :deep(.swiper-button-next),
    :deep(.swiper-button-prev) {
        display: none;
    }
}
</style>