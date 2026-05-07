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
const whatsappLink = computed(() => {
    const number = props.settings?.generalSettings?.whatsapp_number || "8801887573015";
    const message = encodeURIComponent(`Hello, I'm interested in buying this product: "${props.product.name}".\n\nLink: ${window.location.href}`);
    return `https://wa.me/${number}?text=${message}`;
});

</script>

<template>
    <div class="flex flex-col gap-5 selection:bg-primary/10">
        <!-- Brand & Title -->
        <div class="space-y-1">
            <div v-if="product.brand" class="text-[10px] font-semibold text-gray-400 uppercase tracking-[0.2em]">
                {{ product.brand.name }}
            </div>
            <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 leading-tight">
                {{ product.name }}
            </h1>
        </div>

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
            
            <div class="flex items-center gap-1">
                <span class="font-normal">Availability:</span>
                <span v-if="isPreOrderMode" class="text-violet-600 font-semibold">Pre-Order</span> 
                <span v-else-if="!hasStock" class="text-rose-600 font-semibold">Out of Stock</span>
                <span v-else class="text-emerald-600 font-semibold">In Stock</span>
            </div>

            <div v-if="product.product_code" class="h-4 w-px bg-gray-200 hidden sm:block"></div>

            <div v-if="product.product_code" class="flex items-center gap-1">
                <span class="font-normal">Code:</span>
                <span class="text-gray-900 font-semibold">{{ product.product_code }}</span>
            </div>
        </div>        <!-- Attributes Grid (2 Columns) -->
        <div v-if="availableAttributes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
            <div v-for="attribute in availableAttributes" :key="attribute.key" 
                class="border border-gray-200 rounded-2xl p-4 space-y-3 bg-white">
                <span class="text-xs font-semibold text-gray-900 block">{{ attribute.name }}</span>

                <div class="flex flex-wrap gap-3">
                    <!-- Color Swatches (Circular) -->
                    <template v-if="attribute.key.toLowerCase() === 'color'">
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
                                <div v-else :style="{ backgroundColor: value.color || '#eee' }" class="w-full h-full rounded-full border border-black/5"></div>
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
                        Shop Now
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
                    :href="whatsappLink"
                    target="_blank"
                    class="w-full h-14 bg-[#e8fbf3] hover:bg-[#d4f7e8] text-[#1bd741] rounded-2xl font-semibold flex items-center justify-center gap-3 transition-all active:scale-[0.97]"
                >
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                    </svg>
                    <span>Order via Whatsapp</span>
                </a>

                <!-- Delivery Info -->
                <div class="border border-gray-200 rounded-2xl p-4 flex items-center gap-4 bg-gray-50/50">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center">
                        <Truck :size="24" class="text-gray-400" />
                    </div>
                    <div class="text-sm">
                        <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Estimated Delivery</span>
                        <span v-if="isPreOrderMode" class="font-semibold text-gray-900 text-sm leading-tight block">
                            20-30 days delivery • 50% advance (৳{{ formatPrice(pricing.main * 0.5) }})
                        </span>
                        <span v-else class="font-semibold text-gray-900 text-base">3-5 Working Days</span>
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
                    Shop Now
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
