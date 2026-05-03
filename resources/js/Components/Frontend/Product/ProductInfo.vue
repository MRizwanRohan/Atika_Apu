<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useCart } from "@/Composables/useCart";
import { useCartSidebar } from "@/Composables/useCartSidebar";
import { useWishlist } from "@/Composables/useWishlist";
import { useCompare } from "@/Composables/useCompare";
import { useToast } from "@/Composables/useToast";
import { 
    Truck, 
    ShieldCheck, 
    RefreshCcw, 
    Zap, 
    ShoppingBag, 
    ArrowRight,
    Star,
    Check
} from "lucide-vue-next";
import { router, usePage, Link } from "@inertiajs/vue3";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
import { trackViewItem, trackAddToCart } from "@/Composables/gtm";
import { useMetaPixel } from "@/Composables/useMetaPixel";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

// Use centralized cart composables
const { addToCart: addToCartAction, isLoading: cartLoading } = useCart();
const { openCart } = useCartSidebar();
const { trackViewContent, trackAddToCart: pixelTrackAddToCart } = useMetaPixel();
const { toggleWishlist, isInWishlist } = useWishlist();
const { toggleCompare, isInCompare } = useCompare();
const { warning, error, success } = useToast();

const selectedAttributes = ref({});
const quantity = ref(1);
const isAddingToCart = ref(false);

const emit = defineEmits(["variation-change", "scroll-to-description"]);

// Helper: match variations with current selection
const getMatchingVariation = () => {
    if (!props.product.variations || props.product.variations.length === 0) return null;
    const selectedKeys = Object.keys(selectedAttributes.value);
    if (selectedKeys.length === 0) return null;

    return props.product.variations.find((variation) => {
        if (!variation.attributes || variation.attributes.length !== selectedKeys.length) return false;
        return selectedKeys.every((attrKey) => {
            const selectedAttr = selectedAttributes.value[attrKey];
            return variation.attributes.some(
                (varAttr) =>
                    varAttr.value.attribute.name.toLowerCase() === attrKey &&
                    varAttr.value.id === selectedAttr.id
            );
        });
    });
};

const selectedVariation = computed(() => getMatchingVariation());

// Pricing logic
const pricing = computed(() => {
    const selected = selectedVariation.value;
    let main = 0;
    let cross = null;
    let discountValue = 0;
    let discountType = null;

    if (selected) {
        main = parseFloat(selected.price);
        if (selected.previous_price && parseFloat(selected.previous_price) > main) {
            cross = parseFloat(selected.previous_price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
    } else if (props.product.variations?.length > 0) {
        const prices = props.product.variations.map(v => parseFloat(v.price || 0));
        main = Math.min(...prices);
        const prevPrices = props.product.variations.filter(v => v.previous_price > main).map(v => parseFloat(v.previous_price));
        if (prevPrices.length > 0) {
            cross = Math.max(...prevPrices);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
    } else {
        main = parseFloat(props.product.price);
        if (props.product.previous_price && parseFloat(props.product.previous_price) > main) {
            cross = parseFloat(props.product.previous_price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
    }

    return { main, cross, discountValue, discountType };
});

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

// Stock Logic
const currentStock = computed(() => {
    const variation = selectedVariation.value;
    return variation ? variation.stock : props.product.stock;
});

const isPreOrderMode = computed(() => {
    return props.product.is_pre_order && currentStock.value <= 0;
});

const hasStock = computed(() => currentStock.value > 0 || props.product.is_pre_order);

// Attribute logic
const availableAttributes = computed(() => {
    const attributesMap = new Map();
    props.product.variations?.forEach((variation) => {
        variation.attributes?.forEach((attr) => {
            const attrName = attr.value.attribute.name.toLowerCase();
            if (!attributesMap.has(attrName)) {
                attributesMap.set(attrName, { name: attr.value.attribute.name, values: new Set() });
            }
            attributesMap.get(attrName).values.add(JSON.stringify({
                id: attr.value.id,
                value: attr.value.value,
                color: attr.value.color,
                image: variation.image_path || null
            }));
        });
    });

    return Array.from(attributesMap).map(([key, data]) => ({
        key,
        name: data.name,
        values: Array.from(data.values).map(v => JSON.parse(v))
    }));
});

const selectAttribute = (key, value) => {
    selectedAttributes.value[key] = value;
};

// Auto-select first options if available
onMounted(() => {
    availableAttributes.value.forEach(attr => {
        if (attr.values.length > 0) {
            selectAttribute(attr.key, attr.values[0]);
        }
    });
});

watch(selectedAttributes, () => {
    const variation = selectedVariation.value;
    const colorSelection = selectedAttributes.value["color"];
    const attributeImage = colorSelection?.image || null;
    emit("variation-change", { variation, attributeImage });
}, { deep: true });

// Actions
const addToCart = () => {
    if (!hasStock.value) return error("Product is out of stock");
    
    isAddingToCart.value = true;
    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: selectedVariation.value?.id || null,
        onSuccess: () => {
            isAddingToCart.value = false;
            success("Added to cart successfully!");
            trackAddToCart(props.product, selectedVariation.value, quantity.value);
            pixelTrackAddToCart(props.product, selectedVariation.value, quantity.value);
        },
        onError: () => isAddingToCart.value = false,
    });
};

const buyNow = () => {
    if (!hasStock.value) return error("Product is out of stock");
    
    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: selectedVariation.value?.id || null,
        openSidebar: false,
        onSuccess: () => {
            trackAddToCart(props.product, selectedVariation.value, quantity.value);
            pixelTrackAddToCart(props.product, selectedVariation.value, quantity.value);
            router.visit(route('order.create'));
        }
    });
};

// View tracking
watch(() => props.product, (newVal) => {
    if (newVal) {
        trackViewItem(newVal, pricing.value.main);
        trackViewContent(newVal, pricing.value.main);
    }
}, { immediate: true });

</script>

<template>
    <div class="flex flex-col gap-6 selection:bg-primary/10">
        <!-- Brand & Badge Row -->
        <div class="flex items-center justify-between gap-4">
            <div v-if="product.brand" class="px-3 py-1 bg-gray-50 border border-gray-100 rounded-lg">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">{{ product.brand.name }}</span>
            </div>
            
            <div v-if="isPreOrderMode" class="flex items-center gap-1.5 px-3 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-semibold ring-1 ring-violet-200">
                <Truck :size="14" />
                Pre-Order
            </div>
            <div v-else-if="!hasStock" class="px-3 py-1 bg-rose-50 text-rose-700 rounded-lg text-xs font-semibold ring-1 ring-rose-200 uppercase">
                Out of Stock
            </div>
            <div v-else class="flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-semibold ring-1 ring-emerald-200 uppercase">
                <ShieldCheck :size="14" />
                In Stock
            </div>
        </div>

        <!-- Product Title -->
        <div class="space-y-2">
            <h1 class="text-xl md:text-3xl font-bold text-gray-900 leading-[1.2] tracking-tight">
                {{ product.name }}
            </h1>
            
            <!-- Trust Bar -->
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 py-1">
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-0.5">
                        <Star v-for="i in 5" :key="i" :size="14" :class="i <= (product.avg_rating || 5) ? 'fill-orange-400 text-orange-400' : 'text-gray-200'" />
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ product.avg_rating || '5.0' }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="font-semibold text-gray-900">{{ (product.sold_stock || 0) + (product.fake_sold_count || 0) }}</span>
                    <span>Units Sold</span>
                </div>
                <div v-if="product.product_code" class="text-sm text-gray-500">
                    <span class="opacity-60">SKU:</span> <span class="font-semibold text-gray-900">{{ product.product_code }}</span>
                </div>
            </div>
        </div>

        <!-- Pricing Section -->
        <div class="flex flex-col gap-1">
            <div class="flex flex-wrap items-baseline gap-3 md:gap-4">
                <span class="text-2xl md:text-4xl font-bold text-gray-900">
                    ৳{{ formatPrice(pricing.main) }}
                </span>
                <div v-if="pricing.cross" class="flex items-center gap-2">
                    <span class="text-base md:text-lg text-gray-400 line-through font-medium">৳{{ formatPrice(pricing.cross) }}</span>
                    <span class="px-2 py-0.5 bg-rose-500 text-white text-[10px] md:text-xs font-bold rounded-full">
                        -{{ Math.round(pricing.discountValue) }}%
                    </span>
                </div>
            </div>
            <div v-if="pricing.cross" class="flex items-center gap-1.5 text-emerald-600 font-semibold text-sm">
                <Zap :size="16" />
                <span>You Save: ৳{{ formatPrice(pricing.cross - pricing.main) }}</span>
            </div>

            <!-- Pre-order Note -->
            <div v-if="isPreOrderMode" class="mt-4 p-4 bg-violet-600/5 border border-violet-100 rounded-xl">
                <p class="text-xs font-semibold text-violet-700 leading-relaxed uppercase tracking-wide">
                    20-30 days delivery • 50% advance (৳{{ formatPrice(pricing.main * 0.5) }})
                </p>
            </div>
        </div>

        <!-- Short Description -->
        <div v-if="product.short_description" class="space-y-3">
            <div 
                class="text-gray-600 text-sm leading-relaxed prose prose-sm max-w-none 
                prose-p:m-0 prose-ul:list-disc prose-ul:pl-4 prose-li:my-1"
                v-html="product.short_description"
            ></div>
        </div>

        <!-- Attributes Selector -->
        <div v-if="availableAttributes.length > 0" class="flex flex-col gap-6 pt-2">
            <div v-for="attribute in availableAttributes" :key="attribute.key" class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-500 uppercase tracking-widest">{{ attribute.name }}</span>
                    <span class="text-sm font-bold text-gray-900" v-if="selectedAttributes[attribute.key]">
                        {{ selectedAttributes[attribute.key].value }}
                    </span>
                </div>

                <!-- Color Swatches -->
                <div v-if="attribute.key.toLowerCase() === 'color'" class="flex flex-wrap gap-4">
                    <div
                        v-for="value in attribute.values"
                        :key="value.id"
                        class="group relative"
                    >
                        <!-- Color Name Tooltip -->
                        <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] font-medium rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50">
                            {{ value.value }}
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-l-4 border-l-transparent border-r-4 border-r-transparent border-t-4 border-t-gray-900"></div>
                        </div>

                        <button
                            @click="selectAttribute(attribute.key, value)"
                            class="relative w-11 h-11 rounded-full transition-all duration-300 ring-2 ring-offset-1"
                            :class="selectedAttributes[attribute.key]?.id === value.id 
                                ? 'ring-gray-900 scale-105' 
                                : 'ring-transparent hover:ring-gray-200'"
                        >
                            <div v-if="value.image" class="w-full h-full rounded-full overflow-hidden">
                                <img :src="value.image" class="w-full h-full object-cover" />
                            </div>
                            <div v-else :style="{ backgroundColor: value.color || '#eee' }" class="w-full h-full rounded-full border border-gray-100"></div>
                            
                            <!-- Selected Indicator (Check Icon) -->
                            <div v-if="selectedAttributes[attribute.key]?.id === value.id" class="absolute inset-0 bg-black/20 rounded-full flex items-center justify-center">
                                <Check class="text-white" :size="20" stroke-width="3" />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Standard Pickers (Size, Storage, etc.) -->
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <button
                        v-for="value in attribute.values"
                        :key="value.id"
                        @click="selectAttribute(attribute.key, value)"
                        class="px-4 py-3 rounded-xl border-2 text-sm font-semibold transition-all duration-200"
                        :class="selectedAttributes[attribute.key]?.id === value.id
                            ? 'border-gray-900 bg-gray-900 text-white shadow-md'
                            : 'border-gray-100 hover:border-gray-200 text-gray-700'"
                    >
                        {{ value.value }}
                    </button>
                </div>
            </div>
        </div>


        <!-- Action Section -->
        <div class="sticky bottom-0 md:relative bg-white pt-6 pb-2 md:pt-4 z-40 border-t border-gray-50 md:border-t-0 -mx-4 px-4 md:mx-0 md:px-0">
            <div class="flex flex-col gap-3">
                <div class="flex gap-4">
                    <!-- Quantity Picker -->
                    <div class="h-14 flex items-center bg-gray-100 rounded-2xl px-1">
                        <button @click="quantity > 1 && quantity--" class="w-10 h-10 flex items-center justify-center text-xl font-medium hover:bg-white rounded-xl transition-all">-</button>
                        <span class="w-10 font-semibold text-center text-lg">{{ quantity }}</span>
                        <button @click="quantity < currentStock && quantity++" class="w-10 h-10 flex items-center justify-center text-xl font-medium hover:bg-white rounded-xl transition-all">+</button>
                    </div>

                    <!-- Add to Cart -->
                    <button
                        @click="addToCart"
                        :disabled="isAddingToCart || !hasStock"
                        class="flex-1 h-14 bg-gray-900 hover:bg-black text-white rounded-2xl font-semibold flex items-center justify-center gap-3 transition-all active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none group shadow-md shadow-gray-200"
                    >
                        <ShoppingBag v-if="!isAddingToCart" :size="20" class="group-hover:translate-y-[-2px] transition-transform" />
                        <span v-if="isAddingToCart" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span class="uppercase tracking-widest text-[11px] md:text-sm font-bold">{{ isPreOrderMode ? 'Pre-Order Now' : 'Add to Cart' }}</span>
                    </button>
                </div>

                <!-- Checkout Now -->
                <button
                    @click="buyNow"
                    :disabled="isAddingToCart || !hasStock"
                    class="h-14 bg-primary text-white rounded-2xl font-semibold flex items-center justify-center gap-3 transition-all active:scale-[0.98] disabled:opacity-50 group hover:shadow-lg hover:shadow-primary/20"
                >
                    <Zap :size="20" class="group-hover:scale-110 transition-transform" />
                    <span class="uppercase tracking-widest text-[11px] md:text-sm font-bold">Instant Checkout</span>
                    <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
                </button>
            </div>
        </div>

        <!-- Service Trust Bar -->
        <!-- <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-100">
            <div class="flex flex-col items-center gap-2 text-center">
                <Truck :size="20" class="text-gray-400" />
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Fast Delivery</span>
            </div>
            <div class="flex flex-col items-center gap-2 text-center border-x border-gray-100">
                <ShieldCheck :size="20" class="text-gray-400" />
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Authentic</span>
            </div>
            <div class="flex flex-col items-center gap-2 text-center">
                <RefreshCcw :size="20" class="text-gray-400" />
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Easy Returns</span>
            </div>
        </div> -->
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
