<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, ref, defineAsyncComponent } from "vue";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
const ProductQuickView = defineAsyncComponent(() => import("@/Components/Frontend/Product/ProductQuickView.vue"));
import { useCart } from "@/Composables/useCart";
import { useProductPricing } from "@/Composables/useProductPricing";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const { addToCart, isInCart, cartItems } = useCart();
const imageLoaded = ref(false);
const showQuickView = ref(false);
const isAddingToCart = ref(false);

const productInCart = computed(() => {
    if (props.product.type === "variable") {
        return cartItems.value.some(
            (item) => item.product_id === props.product.id,
        );
    }
    return isInCart(props.product.id);
});

const { pricing, isOutOfStock, isPreOrderMode, formatPrice } = useProductPricing(() => props.product);

const handleCartClick = (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (props.product.type === "variable") {
        showQuickView.value = true;
    } else {
        isAddingToCart.value = true;
        addToCart({
            productId: props.product.id,
            quantity: 1,
            onSuccess: () => {
                isAddingToCart.value = false;
            },
            onError: () => {
                isAddingToCart.value = false;
            },
        });
    }
};
</script>

<template>
    <Link
        :href="route('product.details', product.slug)"
        class="group relative flex flex-col h-full bg-white rounded-md border border-gray-100 overflow-hidden cursor-pointer hover:shadow-sm transition-shadow"
        :class="{ 'opacity-80 grayscale-[0.5]': isOutOfStock }"
    >
        <!-- Product Image Section -->
        <div class="relative block aspect-square overflow-hidden">
            <!-- Badges -->
            <div class="absolute top-2 right-2 z-20 flex flex-col items-end gap-1">
                <!-- Free Delivery Badge -->
                <div
                    v-if="product.is_free_delivery && !isOutOfStock"
                    class="bg-emerald-600 text-white px-2 py-0.5 text-[10px] font-bold rounded-full shadow-sm"
                >
                    Free Delivery
                </div>
                <!-- Discount Badge -->
                <div
                    v-if="
                        pricing.discountValue > 0 &&
                        !isOutOfStock &&
                        !isPreOrderMode
                    "
                    class="bg-[#3b82f6] text-white px-2 py-0.5 text-[11px] font-bold rounded-full shadow-sm"
                >
                    <span v-if="pricing.discountType === 'percentage'">
                        {{ Math.round(pricing.discountValue) }}%
                    </span>
                    <span v-else>
                        -৳{{ formatPrice(pricing.discountValue) }}
                    </span>
                </div>
            </div>

            <!-- Pre-Order Ribbon -->
            <div
                v-if="isPreOrderMode"
                class="absolute top-0 right-0 z-30 overflow-hidden w-20 h-20 pointer-events-none"
            >
                <div
                    class="absolute top-[15px] right-[-35px] w-[110px] bg-red-600 text-white text-[9px] font-bold uppercase tracking-widest py-1 rotate-45 text-center shadow-lg"
                >
                    Pre-Order
                </div>
            </div>

            <img
                :src="product.feature_image"
                :srcset="product.responsive_image?.srcset"
                sizes="(min-width: 1280px) 240px, (min-width: 1024px) 300px, (min-width: 768px) 45vw, 50vw"
                :width="product.responsive_image?.width"
                :height="product.responsive_image?.height"
                decoding="async"
                :alt="product.name"
                loading="lazy"
                class="w-full h-full object-contain"
                @load="imageLoaded = true"
            />

            <!-- Out of Stock Overlay -->
            <div
                v-if="isOutOfStock"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[1px]"
            >
                <span
                    class="bg-gray-900/90 text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest"
                >
                    Out of Stock
                </span>
            </div>
        </div>

        <!-- Content Section -->
        <div class="flex flex-col flex-1 p-2 gap-1.5">
            <!-- Title -->
            <h3
                class="text-[15px] font-bold text-[#1e293b] leading-tight line-clamp-1"
                :title="product.name"
            >
                {{ product.name }}
            </h3>

            <!-- Pre-Order Info -->
            <!-- <div v-if="isPreOrderMode" class="mb-2 space-y-0.5">
                <p class="text-[10px] font-semibold text-gray-500">
                    20-30 Days Delivery
                </p>
                <p class="text-[10px] font-bold text-[#9333ea] uppercase">
                    ৳{{ formatPrice(pricing.main * 0.5) }} ADV
                </p>
            </div> -->

            <!-- Price Section -->
            <div class="flex items-baseline gap-2">
                <span class="text-[18px] font-bold text-primary">
                    ৳{{ formatPrice(pricing.main) }}
                </span>
                <span
                    v-if="pricing.cross"
                    class="text-[13px] font-medium text-gray-400 line-through opacity-70"
                >
                    ৳{{ formatPrice(pricing.cross) }}
                </span>
            </div>

            <!-- Footer Row: Rating & Cart -->
            <div class="mt-auto flex items-center justify-between">
                <div class="flex items-center gap-1">
                    <StarRating
                        :rating="Number(product.avg_rating || 0)"
                        size="sm"
                        :show-count="false"
                    />
                </div>

                <button
                    @click="handleCartClick"
                    :disabled="isAddingToCart"
                    class="p-1.5 text-primary transition-colors group/cart disabled:opacity-50 relative z-40"
                    :title="
                        product.type === 'variable'
                            ? 'Select Options'
                            : 'Add to Cart'
                    "
                >
                    <!-- In Cart Icon (with plus) -->
                    <svg
                        v-if="productInCart"
                        class="w-5 h-5 transition-transform group-hover/cart:scale-110"
                        :class="{ 'animate-pulse': isAddingToCart }"
                        stroke="currentColor"
                        fill="currentColor"
                        stroke-width="0"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <circle cx="10.5" cy="19.5" r="1.5"></circle>
                        <circle cx="17.5" cy="19.5" r="1.5"></circle>
                        <path
                            d="M21 7H7.334L6.18 4.23A1.995 1.995 0 0 0 4.333 3H2v2h2.334l4.743 11.385c.155.372.52.615.923.615h8c.417 0 .79-.259.937-.648l3-8A1.003 1.003 0 0 0 21 7zm-4 6h-2v2h-2v-2h-2v-2h2V9h2v2h2v2z"
                        ></path>
                    </svg>

                    <!-- Default Cart Icon -->
                    <svg
                        v-else
                        class="w-5 h-5 transition-transform group-hover/cart:scale-110"
                        :class="{ 'animate-pulse': isAddingToCart }"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="8" cy="21" r="1"></circle>
                        <circle cx="19" cy="21" r="1"></circle>
                        <path
                            d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Quick View Component -->
        <ProductQuickView
            v-if="showQuickView"
            :product="product"
            :show="showQuickView"
            @close="showQuickView = false"
        />
    </Link>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 1;
}
</style>
