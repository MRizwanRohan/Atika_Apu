<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Truck } from "lucide-vue-next";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const imageLoaded = ref(false);

const activeCampaign = computed(() => {
    if (!props.product.campaigns || props.product.campaigns.length === 0)
        return null;
    const now = new Date();
    return props.product.campaigns.find((c) => {
        const start = new Date(c.start_date);
        const end = new Date(c.end_date);
        return c.status === "active" && now >= start && now <= end;
    });
});

const pricing = computed(() => {
    // If product is variable, display lowest variation price by default
    if (
        props.product.type === "variable" &&
        props.product.variations &&
        props.product.variations.length > 0
    ) {
        const numericVars = props.product.variations.map((v) => ({
            price: parseFloat(v.price || 0),
            previous_price:
                v.previous_price !== null && v.previous_price !== undefined
                    ? parseFloat(v.previous_price)
                    : null,
        }));

        const minVar = numericVars.reduce(
            (acc, v) => (acc === null || v.price < acc.price ? v : acc),
            null,
        );
        let main = minVar ? minVar.price : parseFloat(props.product.price);
        let cross = null;
        let discountValue = 0;
        let discountType = null;

        if (minVar) {
            const candidates = numericVars
                .filter((v) => v.previous_price && v.previous_price > main)
                .map((v) => v.previous_price);
            if (candidates.length > 0) {
                cross = Math.max(...candidates);
                discountValue = ((cross - main) / cross) * 100;
                discountType = "percentage";
            }
        }

        return { main, cross, discountValue, discountType };
    }

    // Fallback logic for simple products
    let main = parseFloat(props.product.price);
    let cross = null;
    let discountValue = 0;
    let discountType = null;

    if (activeCampaign.value) {
        cross = main;
        discountValue = parseFloat(activeCampaign.value.discount_amount);
        discountType = activeCampaign.value.discount_type;

        if (discountType === "percentage") {
            main = main - main * (discountValue / 100);
        } else if (discountType === "fixed") {
            main = main - discountValue;
        }
    } else if (
        props.product.previous_price &&
        parseFloat(props.product.previous_price) >
            parseFloat(props.product.price)
    ) {
        cross = parseFloat(props.product.previous_price);
        main = parseFloat(props.product.price);
        discountValue = ((cross - main) / cross) * 100;
        discountType = "percentage";
    }

    return { main, cross, discountValue, discountType };
});

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const isOutOfStock = computed(
    () => props.product.stock <= 0 && !props.product.is_pre_order,
);

const isPreOrderMode = computed(
    () => props.product.stock <= 0 && props.product.is_pre_order,
);
</script>

<template>
    <div
        class="group relative flex flex-col h-full bg-white rounded-xl border border-gray-100 hover:border-primary/20 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
        :class="{ 'opacity-80 grayscale-[0.5]': isOutOfStock }"
    >
        <!-- Product Image Section -->
        <div
            class="relative block aspect-[1/1] overflow-hidden bg-gray-50/50"
        >
            <Link :href="route('product.details', product.slug)" class="block w-full h-full">
                <!-- Badges -->
                <div class="absolute top-3 left-3 z-20 flex flex-col gap-2">
                    <!-- Discount Badge (Only for normal products) -->
                    <div
                        v-if="pricing.discountValue > 0 && !isOutOfStock && !isPreOrderMode"
                        class="bg-rose-500 text-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg shadow-sm w-fit"
                    >
                        <span v-if="pricing.discountType === 'percentage'">
                            {{ Math.round(pricing.discountValue) }}% OFF
                        </span>
                        <span v-else>
                            -৳{{ formatPrice(pricing.discountValue) }}
                        </span>
                    </div>
                </div>

                <!-- Red Ribbon for Pre-Order -->
                <div v-if="isPreOrderMode" class="absolute top-0 right-0 z-30 overflow-hidden w-24 h-24 pointer-events-none">
                    <div class="absolute top-[18px] right-[-32px] w-[120px] bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest py-1 rotate-45 text-center shadow-lg border-y border-white/20">
                        Pre-Order
                    </div>
                </div>

                <img
                    :src="product.feature_image"
                    :alt="product.name"
                    loading="lazy"
                    class="w-full h-full object-contain transition-transform duration-500 ease-out"
                    :class="{ 'group-hover:scale-110': !isPreOrderMode }"
                    @load="imageLoaded = true"
                />

                <!-- Out of Stock Overlay -->
                <div
                    v-if="isOutOfStock"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[1px]"
                >
                    <span
                        class="bg-gray-900/90 text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest shadow-xl"
                    >
                        Out of Stock
                    </span>
                </div>
            </Link>
        </div>

        <!-- Content Section -->
        <div class="flex flex-col flex-1 p-4">
            <!-- Brand/Category/Rating -->
            <div class="flex items-center gap-2 mb-2">
                <div class="flex items-center gap-0.5">
                    <StarRating
                        :rating="Number(product.avg_rating || 0)"
                        size="xs"
                        :show-count="false"
                    />
                </div>
                <span v-if="product.reviews_count > 0" class="text-[10px] font-medium text-gray-400">
                    ({{ product.reviews_count }} reviews)
                </span>
            </div>

            <!-- Title -->
            <Link
                :href="route('product.details', product.slug)"
                class="group-hover:text-primary transition-colors block mb-2"
            >
                <h3
                    class="text-base font-medium text-gray-800 leading-tight line-clamp-1"
                    :title="product.name"
                >
                    {{ product.name }}
                </h3>
            </Link>

            <!-- Pre-Order Info Text -->
            <div v-if="isPreOrderMode" class="mb-3 space-y-0.5">
                <p class="text-[11px] font-semibold text-gray-600">20-30 Days Delivery</p>
                <p class="text-[11px] font-semibold text-gray-600">Full Price: ৳{{ formatPrice(pricing.main) }}</p>
                <p class="text-[11px] font-bold text-gray-800 uppercase tracking-tight">
                    Pre-order Price: ৳{{ formatPrice(pricing.main * 0.5) }} <span class="text-primary">(50% Advance)</span>
                </p>
            </div>

            <!-- Price Section (Only for normal products) -->
            <div v-if="!isPreOrderMode" class="mt-auto pt-2 mb-4 border-t border-gray-50">
                <div class="flex flex-wrap items-baseline gap-2">
                    <span class="text-lg font-extrabold text-primary">
                        ৳{{ formatPrice(pricing.main) }}
                    </span>
                    <span
                        v-if="pricing.cross"
                        class="text-xs font-medium text-gray-400 line-through"
                    >
                        ৳{{ formatPrice(pricing.cross) }}
                    </span>
                </div>
            </div>

            <!-- Order Now / Pre-Order Button -->
            <div class="mt-auto pt-3" :class="{ 'border-t border-gray-50': isPreOrderMode }">
                <Link
                    :href="route('product.details', product.slug)"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-300"
                    :class="[
                        isOutOfStock 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' 
                        : isPreOrderMode 
                          ? 'bg-violet-600 text-white hover:bg-violet-700 shadow-lg shadow-violet-500/20 active:scale-[0.98]'
                          : 'bg-primary text-white hover:bg-primary/90 shadow-md hover:shadow-xl hover:shadow-primary/20 active:scale-[0.98]'
                    ]"
                >
                    {{ isPreOrderMode ? 'Pre-Order Now' : 'Order Now' }}
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 2;
}
</style>

