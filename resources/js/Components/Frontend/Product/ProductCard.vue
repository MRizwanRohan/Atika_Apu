<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useWishlist } from "@/Composables/useWishlist";
import { useCompare } from "@/Composables/useCompare";
import { GitCompare, Heart, ShoppingCart, Truck } from "lucide-vue-next";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const imageLoaded = ref(false);

const { toggleWishlist, isInWishlist } = useWishlist();
const { toggleCompare, isInCompare } = useCompare();

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

    // Fallback to previous logic for simple products
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

const isOutOfStock = computed(() => props.product.stock <= 0);

const discountBadgeText = computed(() => {
    if (!pricing.value.cross) return null;
    if (pricing.value.discountType === "percentage") {
        return `${Math.round(pricing.value.discountValue)}%`;
    }
    return `৳ ${formatPrice(pricing.value.discountValue)}`;
});

const handleWishlist = () => {
    toggleWishlist(props.product.id);
};

const handleCompare = () => {
    toggleCompare(props.product.id);
};
</script>

<template>
    <div
        class="group relative flex flex-col h-full bg-white rounded-xl border border-gray-100 hover:border-primary/20 hover:shadow-lg transition-all duration-300 p-2"
        :class="{ 'opacity-75 grayscale': isOutOfStock }"
    >
        <!-- Action Buttons (Hover) -->
        <div
            class="absolute top-12 right-3 z-20 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0"
            v-if="!isOutOfStock"
        >
            <button
                @click.prevent="handleWishlist"
                class="p-2 rounded-full shadow-md bg-white text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors"
                :title="
                    isInWishlist(product.id)
                        ? 'Remove from wishlist'
                        : 'Add to wishlist'
                "
            >
                <Heart
                    :size="16"
                    :fill="isInWishlist(product.id) ? 'currentColor' : 'none'"
                    :class="{ 'text-red-500': isInWishlist(product.id) }"
                />
            </button>
            <button
                @click.prevent="handleCompare"
                class="p-2 rounded-full shadow-md bg-white text-gray-500 hover:text-blue-500 hover:bg-blue-50 transition-colors"
                :title="
                    isInCompare(product.id)
                        ? 'Remove from compare'
                        : 'Add to compare'
                "
            >
                <GitCompare
                    :size="16"
                    :class="{ 'text-blue-500': isInCompare(product.id) }"
                />
            </button>
        </div>

        <div class="relative">
            <!-- Product Image -->
            <Link
                :href="route('product.details', product.slug)"
                class="block relative w-full aspect-square mb-4 overflow-hidden"
            >
                <img
                    :src="product.feature_image"
                    :alt="product.name"
                    loading="lazy"
                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-500"
                    @load="imageLoaded = true"
                />

                <!-- Out of Stock Overlay -->
                <div
                    v-if="isOutOfStock"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/50"
                >
                    <span
                        class="bg-gray-900 text-white px-3 py-1 text-xs font-bold rounded uppercase tracking-wider"
                    >
                        Sold Out
                    </span>
                </div>
            </Link>

            <!-- Top Badges & Brand -->
            <div class="absolute top-0 left-0">
                <!-- Discount Badge -->
                <div
                    v-if="pricing.discountValue > 0 && !isOutOfStock"
                    class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"
                >
                    <span v-if="pricing.discountType === 'percentage'">
                        {{ Math.round(pricing.discountValue) }}% OFF
                    </span>
                    <span v-else>
                        ৳{{ formatPrice(pricing.discountValue) }} OFF
                    </span>
                </div>
                <div v-else>
                    <!-- Spacer if no discount -->
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="flex flex-col flex-1 mt-auto">
            <!-- Title -->
            <Link
                :href="route('product.details', product.slug)"
                class="group-hover:text-primary transition-colors"
            >
                <h3
                    class="text-sm rounded font-bold text-gray-800 leading-tight line-clamp-2"
                    :title="product.name"
                >
                    {{ product.name }}
                </h3>
            </Link>

            <!-- Price -->
            <div class="flex items-end gap-1 mt-1">
                <span class="text-lg font-bold text-primary">
                    ৳ {{ formatPrice(pricing.main) }}
                </span>
                <span
                    v-if="pricing.cross"
                    class="text-xs text-gray-400 line-through mb-1"
                >
                    ৳ {{ formatPrice(pricing.cross) }}
                </span>
            </div>

            <!-- Footer: Rating & Cart -->
            <div
                class="flex items-center justify-between border-t border-gray-50"
            >
                <!-- Rating -->
                <!-- Rating -->
                <div class="flex items-center gap-1">
                    <StarRating
                        :rating="Number(product.avg_rating || 0)"
                        size="sm"
                        :show-count="false"
                    />
                    <span
                        v-if="product.reviews_count > 0"
                        class="text-xs text-gray-800"
                    >
                        ({{ product.reviews_count }})
                    </span>
                </div>

                <!-- Cart Action -->
                <button
                    @click.prevent="
                        $inertia.visit(route('product.details', product.slug))
                    "
                    class="text-primary hover:bg-primary hover:text-white p-1.5 rounded-lg transition-all duration-200"
                    :disabled="isOutOfStock"
                >
                    <ShoppingCart :size="20" stroke-width="2.5" />
                </button>
            </div>
        </div>
    </div>
</template>
