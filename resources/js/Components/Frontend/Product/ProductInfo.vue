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
    ShoppingCart,
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
                attributesMap.set(attrName, { name: attr.value.attribute.name, values: new Map() });
            }
            
            const valuesMap = attributesMap.get(attrName).values;
            if (!valuesMap.has(attr.value.id)) {
                valuesMap.set(attr.value.id, {
                    id: attr.value.id,
                    value: attr.value.value,
                    color: attr.value.color,
                    image: variation.image_path || null
                });
            } else if (variation.image_path && !valuesMap.get(attr.value.id).image) {
                // If it already exists but has no image, and this variation has an image, update it
                const existing = valuesMap.get(attr.value.id);
                existing.image = variation.image_path;
            }
        });
    });

    return Array.from(attributesMap).map(([key, data]) => ({
        key,
        name: data.name,
        values: Array.from(data.values.values())
    }));
});

const selectAttribute = (key, value) => {
    selectedAttributes.value[key] = value;
};

// Auto-select first options if available
// onMounted(() => {
//     availableAttributes.value.forEach(attr => {
//         if (attr.values.length > 0) {
//             selectAttribute(attr.key, attr.values[0]);
//         }
//     });
// });

watch(selectedAttributes, () => {
    const variation = selectedVariation.value;
    const colorSelection = selectedAttributes.value["color"] || selectedAttributes.value["colour"];
    const designSelection = selectedAttributes.value["design"] || selectedAttributes.value["designs"];
    const attributeImage = colorSelection?.image || designSelection?.image || null;
    emit("variation-change", { variation, attributeImage });
}, { deep: true });

// Check if all required attributes are selected
const allAttributesSelected = computed(() => {
    if (availableAttributes.value.length === 0) return true;
    return availableAttributes.value.every(attr => selectedAttributes.value[attr.key]);
});

// Find which attributes are missing selection
const missingAttributes = computed(() => {
    return availableAttributes.value
        .filter(attr => !selectedAttributes.value[attr.key])
        .map(attr => attr.name);
});

// Actions
const addToCart = () => {
    if (!hasStock.value) return error("Product is out of stock");

    // Require variation selection for products with attributes
    if (availableAttributes.value.length > 0 && !allAttributesSelected.value) {
        return warning(`Please select: ${missingAttributes.value.join(', ')}`);
    }
    
    isAddingToCart.value = true;
    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: selectedVariation.value?.id || null,
        onSuccess: () => {
            isAddingToCart.value = false;
            // Note: success toast is shown globally by FrontendLayout flash watcher
            trackAddToCart(props.product, selectedVariation.value, quantity.value);
            pixelTrackAddToCart(props.product, selectedVariation.value, quantity.value);
        },
        onError: () => isAddingToCart.value = false,
    });
};

const buyNow = () => {
    if (!hasStock.value) return error("Product is out of stock");

    // Require variation selection for products with attributes
    if (availableAttributes.value.length > 0 && !allAttributesSelected.value) {
        return warning(`Please select: ${missingAttributes.value.join(', ')}`);
    }
    
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
const messengerLink = computed(() => {
    const pageId = props.settings?.generalSettings?.facebook_page_id || "AuxtechbdDev";
    const productUrl = `${window.location.origin}/products/${props.product.slug}`;
    // Messenger m.me links use the 'ref' parameter for passing context (max 30 chars, alphanumeric)
    const ref = `product_${props.product.id} ${props.product.name} URL: ${productUrl}`;
    return `https://m.me/${pageId}?ref=${ref}`;
});

</script>

<template>
    <div class="flex flex-col gap-5 selection:bg-primary/10">
        <!-- Brand & Title -->
        <div class="space-y-1">
            <div v-if="product.brand" class="text-[10px] font-semibold text-gray-400 uppercase tracking-[0.2em]">
                {{ product.brand.name }}
            </div>
            <h1 class="text-xl md:text-3xl font-semibold text-gray-900 leading-tight">
                {{ product.name }}
            </h1>
        </div>

        <!-- <pre>{{ settings }}</pre> -->

        <!-- Price & Info Bar -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <span class="text-2xl font-semibold text-gray-900">৳{{ formatPrice(pricing.main) }}</span>
                <div v-if="pricing.cross" class="flex items-center gap-2">
                    <span class="text-gray-400 line-through font-normal">৳{{ formatPrice(pricing.cross) }}</span>
                    <span class="bg-rose-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-semibold">
                        -{{ Math.round(pricing.discountValue) }}%
                    </span>
                </div>
                <!-- <span class="text-gray-400 font-normal">(Cash Price)</span> -->
            </div>
            
            <div class="h-4 w-px bg-gray-200 hidden sm:block"></div>
            
            <div class="flex items-center gap-1.5">
                <span class="text-gray-500 font-normal">Availability:</span>
                <span v-if="isPreOrderMode" class="bg-red-50 text-red-700 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-red-200 shadow-sm shadow-red-50">Pre-Order</span> 
                <span v-else-if="!hasStock" class="bg-rose-50 text-rose-700 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-rose-200 shadow-sm shadow-rose-50">Out of Stock</span>
                <span v-else class="bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-emerald-200 shadow-sm shadow-emerald-50">In Stock</span>
            </div>

            <div v-if="product.product_code" class="flex items-center gap-1">
                <span class="font-normal">Code:</span>
                <span class="text-gray-900 font-semibold">{{ product.product_code }}</span>
            </div>

            <div class="h-4 w-px bg-gray-200 hidden sm:block"></div>

            <div class="flex items-center gap-1 bg-primary text-white px-3 py-1 rounded-full">
                <span class="font-normal text-white">Sold:</span>
                <span class="text-white font-semibold">{{ (Number(product.fake_sold_count || 0) + Number(product.sold_stock || 0)) }}</span>
            </div>
        </div>        <!-- Attributes Grid (2 Columns) -->
        <div v-if="availableAttributes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
            <div v-for="attribute in availableAttributes" :key="attribute.key" 
                class="border border-gray-200 rounded-2xl p-4 space-y-3 bg-white">
                <span class="text-xs font-semibold text-gray-900 block">{{ attribute.name }}</span>

                <div class="flex flex-wrap gap-3">
                    <!-- Color & Design Swatches (Circular) -->
                    <template v-if="['color', 'colour', 'design', 'designs'].includes(attribute.key.toLowerCase())">
                        <div
                            v-for="value in attribute.values"
                            :key="value.id"
                            class="group relative"
                        >
                            <!-- Tooltip -->
                            <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] font-semibold rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50">
                                {{ value.value }}
                                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-l-4 border-l-transparent border-r-4 border-r-transparent border-t-4 border-t-gray-900"></div>
                            </div>

                            <button
                                @click="selectAttribute(attribute.key, value)"
                                class="w-11 h-11 rounded-full border-2 transition-all duration-300 p-0.5"
                                :class="selectedAttributes[attribute.key]?.id === value.id 
                                    ? 'border-orange-500 scale-105' 
                                    : 'border-gray-200 hover:border-gray-200'"
                            >
                                <div v-if="value.image" class="w-full h-full rounded-full overflow-hidden">
                                    <img :src="value.image" class="w-full h-full object-cover" />
                                </div>
                                <div v-else-if="value.color" :style="{ backgroundColor: value.color }" class="w-full h-full rounded-full border border-black/5"></div>
                                <div v-else class="w-full h-full rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase">
                                    {{ value.value.substring(0, 2) }}
                                </div>
                            </button>
                        </div>
                    </template>

                    <!-- Standard Pickers (Size, Storage, etc.) -->
                    <template v-else>
                        <button
                            v-for="value in attribute.values"
                            :key="value.id"
                            @click="selectAttribute(attribute.key, value)"
                            class="px-4 py-2 rounded-xl border text-xs font-semibold transition-all duration-200"
                            :class="selectedAttributes[attribute.key]?.id === value.id
                                ? 'border-orange-500 bg-orange-50 text-orange-600'
                                : 'border-gray-200 hover:border-gray-200 text-gray-500 bg-white'"
                        >
                            {{ value.value }}
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Actions Section -->
        <div class="flex flex-col gap-6 mt-4">
            <!-- Quantity Section -->
            <div class="space-y-3">
                <span class="text-sm font-semibold text-gray-900 block">Select Quantity:</span>
                <div class="inline-flex items-center bg-[#f3f4f6] rounded-full p-1.5 gap-4">
                    <button @click="quantity > 1 && quantity--" 
                        class="w-8 h-8 flex items-center justify-center text-gray-900 bg-white rounded-full shadow-sm hover:bg-gray-50 transition-all">-</button>
                    <span class="w-6 font-semibold text-center text-gray-900 text-base">{{ quantity }}</span>
                    <button @click="quantity < currentStock && quantity++" 
                        class="w-8 h-8 flex items-center justify-center text-gray-900 bg-white rounded-full shadow-sm hover:bg-gray-50 transition-all">+</button>
                </div>
            </div>

            <!-- Primary Actions (Shop Now & Add to Cart) -->
            <div class="hidden md:block">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button
                        @click="buyNow"
                        :disabled="isAddingToCart || !hasStock"
                        class="h-12 bg-primary hover:bg-black text-white rounded-full font-semibold text-base transition-all active:scale-[0.97] disabled:opacity-50 shadow-sm"
                    >
                        {{ isPreOrderMode ? 'Pre Order' : 'Shop Now' }}
                    </button>

                    <button
                        @click="addToCart"
                        :disabled="isAddingToCart || !hasStock"
                        class="h-12 bg-white border border-gray-200 text-gray-900 rounded-full font-semibold flex items-center justify-center gap-3 transition-all active:scale-[0.97] disabled:opacity-50 group hover:bg-gray-50"
                    >
                        <ShoppingCart :size="20" class="group-hover:translate-y-[-1px] transition-transform" />
                        <span>Add To Cart</span>
                    </button>
                </div>
            </div>
        </div>

            <!-- Social/Support Actions -->
            <div class="flex flex-col gap-4">
                <a
                    :href="messengerLink"
                    target="_blank"
                    class="w-full h-14 bg-[#E7F3FF] hover:bg-[#D1E9FF] text-[#0084FF] rounded-2xl font-semibold flex items-center justify-center gap-3 transition-all active:scale-[0.97]"
                >
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.908 1.464 5.503 3.746 7.234.195.148.308.38.306.63l-.004 2.18c-.002.508.514.887.973.666l2.454-1.18a.8.8 0 0 1 .533-.049c.64.167 1.306.257 1.992.257 5.523 0 10-4.145 10-9.258S17.523 2 12 2zm.797 12.35l-2.586-2.756-5.047 2.756 5.553-5.894 2.586 2.756L18.441 8.45l-5.644 5.9z"/>
                    </svg>
                    <span>Order via Messenger</span>
                </a>

                <!-- Delivery Info -->
                <div 
                    :class="[
                        'border rounded-2xl p-4 flex items-center gap-4 transition-all duration-300',
                        isPreOrderMode 
                            ? 'border-red-300 bg-gradient-to-r from-red-50/80 via-rose-50/40 to-red-50/50 shadow-md shadow-red-100/50' 
                            : 'border-gray-200 bg-gray-50/50'
                    ]"
                >
                    <div 
                        :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300',
                            isPreOrderMode ? 'bg-red-600 text-white shadow-sm shadow-red-200' : 'bg-white text-gray-400'
                        ]"
                    >
                        <Truck :size="24" :class="isPreOrderMode ? 'text-white' : 'text-gray-400'" />
                    </div>
                    <div class="text-sm flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span 
                                :class="[
                                    'font-semibold uppercase text-[10px] tracking-wider block',
                                    isPreOrderMode ? 'text-red-600 font-bold' : 'text-gray-400'
                                ]"
                            >
                                Estimated Delivery
                            </span>
                            <span 
                                v-if="isPreOrderMode" 
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-100 text-red-700 animate-pulse animate-duration-1000"
                            >
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-600"></span>
                                </span>
                                Pre-Order
                            </span>
                        </div>
                        <span v-if="isPreOrderMode" class="font-semibold text-gray-900 text-sm leading-tight block mt-1">
                            20-30 days delivery • 50% advance (৳{{ formatPrice(pricing.main * 0.5) }})
                        </span>
                        <span v-else class="font-semibold text-gray-900 text-base block mt-0.5">3-5 Working Days</span>
                    </div>
                </div>
        </div>

        <!-- Mobile fixed bottom actions -->
        <div class="fixed inset-x-0 bottom-0 z-40 bg-white border-t border-gray-200 p-4 pb-safe shadow-[0_-1px_15px_rgba(0,0,0,0.05)] md:hidden">
            <div class="flex items-center gap-3">
                <button
                    @click="buyNow"
                    :disabled="isAddingToCart || !hasStock"
                    class="flex-1 h-12 bg-primary hover:bg-black text-white rounded-full font-semibold text-base transition-all active:scale-[0.97] disabled:opacity-50 shadow-sm"
                >
                    {{ isPreOrderMode ? 'Pre Order' : 'Shop Now' }}
                </button>
                <button
                    @click="addToCart"
                    :disabled="isAddingToCart || !hasStock"
                    class="flex-1 h-12 bg-white border border-gray-200 text-gray-900 rounded-full font-semibold flex items-center justify-center gap-3 transition-all active:scale-[0.97] disabled:opacity-50 group hover:bg-gray-50"
                >
                    <ShoppingCart :size="20" class="group-hover:translate-y-[-1px] transition-transform" />
                    <span>Add To Cart</span>
                </button>
            </div>
        </div>
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
