<template>
    <div class="w-full mx-auto">
        <div class="flex gap-1">
            <!-- Main Image Column -->
            <div class="flex-1 relative aspect-[4/5] bg-[#F9F9F9] rounded-2xl overflow-hidden group">
                <!-- Main Content -->
                <div
                    v-if="activeMedia"
                    ref="imageContainer"
                    class="w-full h-full cursor-zoom-in"
                    @mousemove="handleMouseMove"
                    @mouseleave="handleMouseLeave"
                    @touchstart="handleTouchStart"
                    @touchmove="handleTouchMove"
                    @touchend="handleTouchEnd"
                >
                    <img
                        v-if="activeMedia.type === 'image'"
                        :src="activeMedia.src"
                        :alt="`Product image ${activeIndex + 1}`"
                        class="w-full h-full object-cover transition-transform duration-300"
                        :style="zoomStyle"
                    />
                    <video
                        v-else
                        :key="activeMedia.src"
                        :src="activeMedia.src"
                        controls
                        autoplay
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>

            <!-- Side Controls (Outside Image) -->
            <div class="flex flex-col w-14 items-center justify-between pb-4">
                <!-- Action Buttons (Top) -->
                <div class="flex flex-col gap-2 pt-2">
                    <button 
                        @click="handleShare"
                        class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:bg-gray-50 transition-all text-gray-900"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13" />
                        </svg>
                    </button>
                    <button 
                        @click="handleWishlist"
                        class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:bg-gray-50 transition-all"
                    >
                        <svg 
                            class="w-5 h-5 transition-colors" 
                            :class="isInWishlist ? 'fill-red-500 text-red-500' : 'text-gray-900'"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Arrows (Bottom) -->
                <div class="flex flex-col gap-2">
                    <button
                        @click="handlePrevious"
                        class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-100 transition-colors text-gray-500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        @click="handleNext"
                        class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-100 transition-colors text-gray-500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Horizontal Thumbnails -->
        <div class="mt-6 flex gap-3 overflow-x-auto pb-4 scrollbar-hide">
            <button
                v-for="(media, index) in allMedia"
                :key="index"
                @click="handleThumbnailClick(index)"
                class="w-20 aspect-square rounded-xl overflow-hidden border-2 flex-shrink-0 transition-all duration-200"
                :class="index === activeIndex ? 'border-primary ring-2 ring-primary/5' : 'border-transparent bg-gray-50'"
            >
                <img
                    v-if="media.type === 'image'"
                    :src="media.src"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center relative bg-gray-100">
                    <video :src="media.src" class="w-full h-full object-cover opacity-50" />
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-700" fill="currentColor" viewBox="0 0 24 24"><path d="M4 2v20l18-10L4 2z" /></svg>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from "vue";
import { useWishlist } from "@/Composables/useWishlist";

interface Media {
    type: "image" | "video";
    src: string;
    isVariation: boolean;
}

interface Props {
    images?: string[];
    selectedVariationImage?: string | null;
    video?: string | null;
    productId: number;
}

const props = withDefaults(defineProps<Props>(), {
    images: () => [],
    selectedVariationImage: null,
    video: null,
});

const { toggleWishlist, isInWishlist: checkWishlist } = useWishlist();
const isInWishlist = computed(() => checkWishlist(props.productId));

const handleWishlist = () => {
    toggleWishlist(props.productId);
};

const handleShare = () => {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback or toast
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard');
    }
};

const activeIndex = ref(0);
const isZooming = ref(false);
const mousePosition = ref({ x: 50, y: 50 });
const imageContainer = ref<HTMLElement | null>(null);

// Touch handling for mobile swipe
const touchStartX = ref(0);
const touchEndX = ref(0);
const touchStartY = ref(0);
const touchEndY = ref(0);
const minSwipeDistance = 50;

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

// Reset to variation image when it changes
watch(
    () => props.selectedVariationImage,
    (newVal) => {
        if (newVal) {
            activeIndex.value = 0;
        }
    },
);

const handlePrevious = () => {
    activeIndex.value = activeIndex.value > 0 ? activeIndex.value - 1 : allMedia.value.length - 1;
};

const handleNext = () => {
    activeIndex.value = activeIndex.value < allMedia.value.length - 1 ? activeIndex.value + 1 : 0;
};

const handleThumbnailClick = (index: number) => {
    activeIndex.value = index;
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

    // Only trigger swipe if horizontal movement is greater than vertical
    if (Math.abs(deltaX) > minSwipeDistance && Math.abs(deltaX) > deltaY) {
        if (deltaX > 0) {
            handleNext();
        } else {
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
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
