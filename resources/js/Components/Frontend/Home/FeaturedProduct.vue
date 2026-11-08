<script setup>
import { computed, ref, defineAsyncComponent } from "vue";
import { Link } from "@inertiajs/vue3";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
import { useCart } from "@/Composables/useCart";
import { useWishlist } from "@/Composables/useWishlist";
import { useProductPricing } from "@/Composables/useProductPricing";
const ProductQuickView = defineAsyncComponent(() => import("@/Components/Frontend/Product/ProductQuickView.vue"));

const props = defineProps({
    featured: { type: Object, required: true },
});

const product = computed(() => props.featured.product);
const { pricing, isOutOfStock, isPreOrderMode, formatPrice } = useProductPricing(product);
const { addToCart } = useCart();
const { toggleWishlist, isInWishlist } = useWishlist();

const showQuickView = ref(false);
const isAddingToCart = ref(false);

const reviewsCount = computed(() => Number(product.value.reviews_count || 0));
const inWishlist = computed(() => isInWishlist(product.value.id));

const cartLabel = computed(() => {
    if (isOutOfStock.value) return "Out of Stock";
    if (product.value.type === "variable") return "Select Options";
    return isPreOrderMode.value ? "Pre-Order Now" : "Add to Cart";
});

const handleCart = () => {
    if (isOutOfStock.value || isAddingToCart.value) return;
    if (product.value.type === "variable") {
        showQuickView.value = true;
        return;
    }
    isAddingToCart.value = true;
    const done = () => (isAddingToCart.value = false);
    addToCart({ productId: product.value.id, quantity: 1, onSuccess: done, onError: done });
};
</script>

<template>
    <section class="py-10 md:py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 items-center gap-8 md:gap-14">
                <!-- Arch image -->
                <Link
                    :href="route('product.details', product.slug)"
                    class="relative block w-full max-w-[300px] sm:max-w-sm md:max-w-md mx-auto aspect-[4/5]"
                >
                    <div
                        aria-hidden="true"
                        class="absolute inset-0 translate-x-3 -translate-y-3 md:translate-x-5 md:-translate-y-5 rounded-t-full"
                        :style="{ backgroundColor: featured.bg_color }"
                    ></div>
                    <div class="relative h-full overflow-hidden rounded-t-full bg-gray-100 shadow-sm">
                        <img
                            :src="product.feature_image"
                            :srcset="product.responsive_image?.srcset"
                            sizes="(min-width: 768px) 448px, 80vw"
                            :width="product.responsive_image?.width"
                            :height="product.responsive_image?.height"
                            :alt="product.name"
                            loading="lazy"
                            decoding="async"
                            class="w-full h-full object-cover transition-transform duration-700 hover:scale-105"
                        />
                    </div>
                </Link>

                <!-- Details -->
                <div class="min-w-0">
                    <div class="flex items-center gap-3 mb-3 md:mb-4">
                        <span class="text-xs md:text-sm font-semibold uppercase tracking-[0.2em] text-primary">
                            {{ featured.badge }}
                        </span>
                        <span aria-hidden="true" class="h-px w-10 md:w-14 bg-primary"></span>
                    </div>

                    <Link :href="route('product.details', product.slug)">
                        <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl leading-tight text-gray-900 break-words hover:text-primary transition-colors">
                            {{ product.name }}
                        </h2>
                    </Link>

                    <div v-if="reviewsCount > 0" class="mt-3 flex items-center gap-2">
                        <StarRating :rating="Math.min(5, Number(product.avg_rating || 0))" size="sm" :show-count="false" />
                        <span class="text-sm text-gray-600">({{ reviewsCount }} {{ reviewsCount === 1 ? "Review" : "Reviews" }})</span>
                    </div>

                    <p v-if="featured.description" class="mt-4 md:mt-5 max-w-lg text-sm md:text-base leading-relaxed text-gray-600">
                        {{ featured.description }}
                    </p>

                    <ul
                        v-if="featured.highlights.length"
                        class="mt-5 md:mt-6 py-4 border-y border-gray-200 flex flex-wrap gap-x-6 gap-y-3"
                    >
                        <li
                            v-for="(item, i) in featured.highlights"
                            :key="i"
                            class="flex items-center gap-2 text-sm text-gray-700"
                        >
                            <svg class="w-5 h-5 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M8 12.5l2.5 2.5L16 9.5" />
                            </svg>
                            {{ item }}
                        </li>
                    </ul>

                    <div class="mt-5 md:mt-6 flex items-baseline gap-3">
                        <span class="text-2xl md:text-3xl font-bold text-gray-900">৳{{ formatPrice(pricing.main) }}</span>
                        <span v-if="pricing.cross" class="text-base md:text-lg text-gray-400 line-through">
                            ৳{{ formatPrice(pricing.cross) }}
                        </span>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <button
                            type="button"
                            :disabled="isOutOfStock || isAddingToCart"
                            class="inline-flex items-center justify-center gap-2 h-12 px-6 md:px-8 rounded-md bg-primary text-white text-sm font-semibold transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="handleCart"
                        >
                            {{ cartLabel }}
                            <svg
                                v-if="!isOutOfStock"
                                class="w-4 h-4"
                                :class="{ 'animate-pulse': isAddingToCart }"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="8" cy="21" r="1" />
                                <circle cx="19" cy="21" r="1" />
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            :aria-label="inWishlist ? 'Remove from wishlist' : 'Add to wishlist'"
                            :aria-pressed="inWishlist"
                            class="flex items-center justify-center w-12 h-12 rounded-full border border-gray-300 text-gray-700 transition-colors hover:border-primary hover:text-primary"
                            @click="toggleWishlist(product.id)"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" :fill="inWishlist ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20s-7-4.35-9.5-9A5.5 5.5 0 0112 5.5 5.5 5.5 0 0121.5 11c-2.5 4.65-9.5 9-9.5 9z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ProductQuickView
            v-if="showQuickView"
            :product="product"
            :show="showQuickView"
            @close="showQuickView = false"
        />
    </section>
</template>
