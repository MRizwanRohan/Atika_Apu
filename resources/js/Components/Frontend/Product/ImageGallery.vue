<template>
    <div class="w-full mx-auto">
        <div class="flex flex-col gap-4">
            <!-- Main Image Display -->
            <div class="w-full">
                <div
                    class="relative w-full aspect-square border border-gray-100 bg-white rounded-3xl overflow-hidden shadow-sm"
                >
                    <!-- Main Content -->
                    <div
                        v-if="activeMedia"
                        ref="imageContainer"
                        class="relative w-full h-full cursor-zoom-in"
                        @mousemove="handleMouseMove"
                        @mouseleave="handleMouseLeave"
                        @touchstart="handleTouchStart"
                        @touchmove="handleTouchMove"
                        @touchend="handleTouchEnd"
                    >
                        <!-- Image with Zoom -->
                        <img
                            v-if="activeMedia.type === 'image'"
                            :src="activeMedia.src"
                            :alt="`Product image ${activeIndex + 1}`"
                            class="w-full h-full object-contain p-8 transition-transform duration-200"
                            :style="zoomStyle"
                        />

                        <!-- Video -->
                        <video
                            v-else
                            :key="activeMedia.src"
                            :src="activeMedia.src"
                            controls
                            autoplay
                            class="w-full h-full object-cover"
                        />

                        <!-- Discount Badge -->
                        <div
                            v-if="pricingProp && pricingProp.cross && !isOutOfStockProp"
                            class="absolute top-6 right-6 z-30 bg-rose-500 text-white px-3 py-1 rounded-full text-[10px] font-bold shadow-md"
                        >
                            <span v-if="pricingProp.discountType === 'percentage'">
                                {{ Math.round(pricingProp.discountValue) }}% OFF
                            </span>
                            <span v-else>৳ {{ formatPrice(pricingProp.discountValue) }} OFF</span>
                        </div>

                        <!-- New Arrival Badge -->
                        <div v-if="isNewProp && !isOutOfStockProp" class="absolute top-2 left-2 z-30">
                            <img src="/assets/img/new-arrival.svg" alt="New Arrival" class="w-16 h-16" />
                        </div>

                        <!-- Red Ribbon for Pre-Order -->
                        <div v-if="isPreOrderMode" class="absolute top-0 right-0 z-40 overflow-hidden w-28 h-28 pointer-events-none">
                            <div class="absolute top-[22px] right-[-36px] w-[140px] bg-red-600 text-white text-[11px] font-bold uppercase tracking-widest py-1.5 rotate-45 text-center shadow-lg border-y border-white/20">
                                Pre-Order
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Thumbnail Gallery -->
            <div class="relative group">
                <div
                    ref="thumbnailContainer"
                    class="flex gap-3 overflow-x-auto scrollbar-hide pb-2"
                >
                    <button
                        v-for="(media, index) in allMedia"
                        :key="index"
                        @click="handleThumbnailClick(index)"
                        class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden border-2 transition-all duration-200 flex-shrink-0"
                        :class="
                            index === activeIndex
                                ? 'border-orange-500 shadow-md scale-[1.02]'
                                : 'border-gray-100 hover:border-gray-200'
                        "
                    >
                        <img
                            v-if="media.type === 'image'"
                            :src="media.src"
                            :alt="`Thumbnail ${index + 1}`"
                            class="w-full h-full object-cover p-1"
                        />
                        <div
                            v-else
                            class="w-full h-full bg-gray-50 flex items-center justify-center relative"
                        >
                            <video
                                :src="media.src"
                                class="w-full h-full object-cover"
                                muted
                            />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/10">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 2v20l18-10L4 2z" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from "vue";

interface Media {
    type: "image" | "video";
    src: string;
    isVariation: boolean;
}

interface Props {
    images?: string[];
    selectedVariationImage?: string | null;
    video?: string | null;
    pricing?: { main?: number; cross?: number | null; discountValue?: number; discountType?: string } | null;
    isOutOfStock?: boolean;
    isNew?: boolean;
    isPreOrderMode?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    images: () => [],
    selectedVariationImage: null,
    video: null,
    pricing: null,
    isOutOfStock: false,
    isNew: false,
    isPreOrderMode: false,
});

const activeIndex = ref(0);
const isZooming = ref(false);
const mousePosition = ref({ x: 50, y: 50 });
const imageContainer = ref<HTMLElement | null>(null);
const thumbnailContainer = ref<HTMLElement | null>(null);
const canScrollUp = ref(false);
const canScrollDown = ref(false);

// Touch handling for mobile swipe
const touchStartX = ref(0);
const touchEndX = ref(0);
const touchStartY = ref(0);
const touchEndY = ref(0);
const minSwipeDistance = 50;

const checkScroll = () => {
    if (!thumbnailContainer.value) return;
    const { scrollTop, scrollHeight, clientHeight } = thumbnailContainer.value;
    canScrollUp.value = scrollTop > 0;
    canScrollDown.value = scrollTop + clientHeight < scrollHeight - 1;
};

// Combine all media sources
const allMedia = computed<Media[]>(() => {
    const media: Media[] = [];

    if (props.selectedVariationImage) {
        media.push({
            type: "image",
            src: props.selectedVariationImage,
            isVariation: true,
        });
    }

    props.images.forEach((img) => {
        media.push({ type: "image", src: img, isVariation: false });
    });

    if (props.video) {
        media.push({ type: "video", src: props.video, isVariation: false });
    }

    return media;
});

const activeMedia = computed(() => allMedia.value[activeIndex.value]);

const zoomStyle = computed(() => {
    if (!isZooming.value) {
        return {
            transform: "scale(1)",
        };
    }

    return {
        transform: "scale(2)",
        transformOrigin: `${mousePosition.value.x}% ${mousePosition.value.y}%`,
    };
});

const formatPrice = (price: number) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const pricingProp = computed(() => props.pricing || null);
const isOutOfStockProp = computed(() => !!props.isOutOfStock);
const isNewProp = computed(() => !!props.isNew);

// Reset to variation image when it changes
watch(
    () => props.selectedVariationImage,
    (newVal) => {
        if (newVal) {
            activeIndex.value = 0;
            nextTick(() => {
                scrollThumbnailIntoView(0);
                checkScroll();
            });
        }
    }
);

const scrollThumbnailIntoView = (index: number) => {
    if (thumbnailContainer.value) {
        const thumbnail = thumbnailContainer.value.children[
            index
        ] as HTMLElement;
        if (thumbnail) {
            thumbnail.scrollIntoView({ behavior: "smooth", block: "nearest" });
            setTimeout(checkScroll, 500);
        }
    }
};

onMounted(() => {
    checkScroll();
    window.addEventListener("resize", checkScroll);
});

watch(
    () => allMedia.value,
    () => {
        nextTick(checkScroll);
    }
);

const handlePrevious = () => {
    const newIndex =
        activeIndex.value > 0
            ? activeIndex.value - 1
            : allMedia.value.length - 1;
    activeIndex.value = newIndex;
    scrollThumbnailIntoView(newIndex);
};

const handleNext = () => {
    const newIndex =
        activeIndex.value < allMedia.value.length - 1
            ? activeIndex.value + 1
            : 0;
    activeIndex.value = newIndex;
    scrollThumbnailIntoView(newIndex);
};

const handleThumbnailClick = (index: number) => {
    activeIndex.value = index;
    scrollThumbnailIntoView(index);
};

const handleMouseMove = (event: MouseEvent) => {
    if (!imageContainer.value) return;
    const rect = imageContainer.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    mousePosition.value = { x, y };
    isZooming.value = true;
};

const handleMouseLeave = () => {
    isZooming.value = false;
};

const scrollThumbnails = (direction: "up" | "down") => {
    if (thumbnailContainer.value) {
        const scrollAmount = 100;
        thumbnailContainer.value.scrollBy({
            top: direction === "up" ? -scrollAmount : scrollAmount,
            behavior: "smooth",
        });
    }
};

// Touch event handlers for mobile swipe
const handleTouchStart = (event: TouchEvent) => {
    touchStartX.value = event.touches[0].clientX;
    touchStartY.value = event.touches[0].clientY;
};

const handleTouchMove = (event: TouchEvent) => {
    touchEndX.value = event.touches[0].clientX;
    touchEndY.value = event.touches[0].clientY;
};

const handleTouchEnd = () => {
    const deltaX = touchStartX.value - touchEndX.value;
    const deltaY = Math.abs(touchStartY.value - touchEndY.value);

    // Only trigger swipe if horizontal movement is greater than vertical (to avoid interfering with scrolling)
    if (Math.abs(deltaX) > minSwipeDistance && Math.abs(deltaX) > deltaY) {
        if (deltaX > 0) {
            // Swiped left, go to next image
            handleNext();
        } else {
            // Swiped right, go to previous image
            handlePrevious();
        }
    }

    // Reset values
    touchStartX.value = 0;
    touchEndX.value = 0;
    touchStartY.value = 0;
    touchEndY.value = 0;
};
</script>

<style scoped>
/* Custom Scrollbar */
.scrollbar-y-hide::-webkit-scrollbar {
    width: 0px; /* Chrome, Safari */
    background: transparent; /* Optional */
}

.scrollbar-y-hide {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}
</style>