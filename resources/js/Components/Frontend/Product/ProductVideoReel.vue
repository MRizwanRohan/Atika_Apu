<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import {
    X,
    Volume2,
    VolumeX,
    Heart,
    Share2,
    ChevronLeft,
    ChevronRight,
} from "lucide-vue-next";

const props = defineProps({
    productswithVideo: {
        type: Array,
        required: true,
    },
});

const isModalOpen = ref(false);
const currentIndex = ref(0);
const isMuted = ref(true);
const isLiked = ref(false);
const likesCount = ref(78);
const sharesCount = ref(121);

const openModal = (index) => {
    currentIndex.value = index;
    isModalOpen.value = true;
    document.body.style.overflow = "hidden";
};

const closeModal = () => {
    isModalOpen.value = false;
    document.body.style.overflow = "";
};

const nextVideo = () => {
    if (currentIndex.value < props.productswithVideo.length - 1) {
        currentIndex.value++;
    } else {
        currentIndex.value = 0;
    }
};

const prevVideo = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    } else {
        currentIndex.value = props.productswithVideo.length - 1;
    }
};

const toggleMute = () => {
    isMuted.value = !isMuted.value;
};

const toggleLike = () => {
    isLiked.value = !isLiked.value;
    if (isLiked.value) {
        likesCount.value++;
    } else {
        likesCount.value--;
    }
};

const getDisplayPrice = (product) => {
    if (!product) return 0;

    if (
        product.type === "variable" &&
        product.variations &&
        product.variations.length > 0
    ) {
        const prices = product.variations
            .map((v) => parseFloat(v.price))
            .filter((p) => !isNaN(p));
        if (prices.length > 0) {
            return Math.min(...prices);
        }
    }

    return product.price;
};

// Get previous and next product indices
const getPrevIndex = () => {
    return currentIndex.value === 0
        ? props.productswithVideo.length - 1
        : currentIndex.value - 1;
};

const getNextIndex = () => {
    return currentIndex.value === props.productswithVideo.length - 1
        ? 0
        : currentIndex.value + 1;
};

// Keyboard navigation
const handleKeydown = (e) => {
    if (!isModalOpen.value) return;
    if (e.key === "Escape") closeModal();
    if (e.key === "ArrowRight") nextVideo();
    if (e.key === "ArrowLeft") prevVideo();
};

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <section
        v-if="productswithVideo && productswithVideo.length > 0"
        class="py-2 bg-white overflow-hidden"
    >
        <!-- <pre>{{ productswithVideo }}</pre> -->
        <div class="container mx-auto px-4">
            <!-- Grid Structure (Desktop) / Horizontal Scroll (Mobile) -->
            <div
                class="flex gap-1 md:gap-2 overflow-x-auto pb-6 snap-x hide-scrollbar"
            >
                <div
                    v-for="(item, index) in productswithVideo"
                    :key="item.id"
                    @click="openModal(index)"
                    class="relative flex-none w-[45%] sm:w-[30%] md:w-[20%] aspect-[9/16] overflow-hidden cursor-pointer group snap-start rounded-lg"
                >
                    <video
                        :src="item.video"
                        class="w-full h-full object-cover"
                        autoplay
                        muted
                        loop
                        playsinline
                    ></video>

                    <div
                        class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Reels Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4 md:p-8"
            >
                <!-- Close Button -->
                <button
                    @click="closeModal"
                    class="absolute top-6 right-6 text-white hover:scale-110 transition-transform z-[110] bg-white/10 hover:bg-white/20 rounded-full p-2"
                >
                    <X :size="32" stroke-width="1.5" />
                </button>

                <!-- Main Container - Full Flex Layout -->
                <div
                    class="relative w-full h-full flex items-center justify-center max-w-7xl"
                >
                    <!-- Left Arrow Button -->
                    <button
                        v-if="productswithVideo.length > 1"
                        @click="prevVideo"
                        class="absolute left-0 top-1/2 -translate-y-1/2 z-30 bg-white rounded-full p-3 hover:bg-gray-200 transition-all shadow-lg hover:scale-110 hidden md:flex items-center justify-center"
                    >
                        <ChevronLeft :size="32" class="text-black" />
                    </button>

                    <!-- Left Preview -->
                    <div
                        v-if="productswithVideo.length > 1"
                        class="hidden lg:block absolute left-0 top-1/2 -translate-y-1/2 ml-24 h-[70vh] w-[330px] cursor-pointer group overflow-hidden rounded-2xl shadow-2xl transition-transform"
                    >
                        <video
                            :src="productswithVideo[getPrevIndex()].video"
                            class="w-full h-full object-cover"
                            muted
                            loop
                            playsinline
                        ></video>
                    </div>

                    <!-- Center Video Container -->
                    <div
                        class="relative h-[70vh] md:h-[80vh] w-full max-w-[400px] md:max-w-[450px] bg-black rounded-3xl overflow-hidden shadow-2xl flex-shrink-0"
                    >
                        <video
                            :key="productswithVideo[currentIndex].id"
                            :src="productswithVideo[currentIndex].video"
                            class="w-full h-full object-cover"
                            autoplay
                            loop
                            playsinline
                            :muted="isMuted"
                        ></video>

                        <!-- Mute Toggle -->
                        <button
                            @click="toggleMute"
                            class="absolute top-4 right-4 w-11 h-11 flex items-center justify-center rounded-full bg-black/40 hover:bg-black/60 text-white z-20 transition-all hover:scale-110"
                        >
                            <Volume2 v-if="!isMuted" :size="22" />
                            <VolumeX v-else :size="22" />
                        </button>

                        <!-- Product Card Overlay -->
                        <div class="absolute bottom-6 left-4 right-4 z-20">
                            <div
                                class="bg-white/95 backdrop-blur-xl rounded-2xl p-4 flex flex-col gap-3 border border-white/50 shadow-xl"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-14 h-20 rounded-lg overflow-hidden shrink-0 border border-gray-200 shadow-sm"
                                    >
                                        <img
                                            :src="
                                                productswithVideo[currentIndex]
                                                    .product.feature_image
                                            "
                                            alt="Product"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4
                                            class="text-gray-900 font-semibold text-sm leading-tight line-clamp-2"
                                        >
                                            {{
                                                productswithVideo[currentIndex]
                                                    .product.name
                                            }}
                                        </h4>
                                        <div
                                            class="inline-block bg-gray-900 text-white px-2.5 py-1 rounded mt-2 font-bold text-xs"
                                        >
                                            <span
                                                v-if="
                                                    productswithVideo[
                                                        currentIndex
                                                    ].product.type ===
                                                    'variable'
                                                "
                                                >Starts at
                                            </span>
                                            ৳{{
                                                getDisplayPrice(
                                                    productswithVideo[
                                                        currentIndex
                                                    ].product,
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    :href="
                                        route(
                                            'product.details',
                                            productswithVideo[currentIndex]
                                                .product.slug,
                                        )
                                    "
                                    class="w-full bg-gray-900 hover:bg-black text-white text-center py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider transition-colors"
                                >
                                    SHOP NOW
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Right Arrow Button -->
                    <button
                        v-if="productswithVideo.length > 1"
                        @click="nextVideo"
                        class="absolute right-0 top-1/2 -translate-y-1/2 z-30 bg-white rounded-full p-3 hover:bg-gray-200 transition-all shadow-lg hover:scale-110 hidden md:flex items-center justify-center"
                    >
                        <ChevronRight :size="32" class="text-black" />
                    </button>

                    <!-- Right Preview -->
                    <div
                        v-if="productswithVideo.length > 1"
                        class="hidden lg:block absolute right-0 top-1/2 -translate-y-1/2 mr-24 h-[70vh] w-[330px] cursor-pointer group overflow-hidden rounded-2xl shadow-2xl transition-transform z-[-1]"
                    >
                        <video
                            :src="productswithVideo[getNextIndex()].video"
                            class="w-full h-full object-cover"
                            muted
                            loop
                            playsinline
                        ></video>
                    </div>

                    <!-- Mobile Navigation Arrows -->
                    <div
                        class="md:hidden absolute bottom-32 left-0 right-0 flex justify-between px-4 z-20"
                    >
                        <button
                            @click="prevVideo"
                            class="bg-white rounded-full p-2 hover:bg-gray-200 transition-all"
                        >
                            <ChevronLeft :size="28" class="text-black" />
                        </button>
                        <button
                            @click="nextVideo"
                            class="bg-white rounded-full p-2 hover:bg-gray-200 transition-all"
                        >
                            <ChevronRight :size="28" class="text-black" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </section>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

video {
    object-position: center;
}
</style>
