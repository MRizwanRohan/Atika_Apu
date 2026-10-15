<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useCart } from "@/Composables/useCart";
import { useCartSidebar } from "@/Composables/useCartSidebar";
import { useWishlist } from "@/Composables/useWishlist";
import { X, Plus, Minus, ShoppingCart, Check } from "lucide-vue-next";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const { addToCart: addToCartAction } = useCart();
const { openCart } = useCartSidebar();
const { toggleWishlist, isInWishlist } = useWishlist();

const selectedAttributes = ref({});
const quantity = ref(1);
const isAddingToCart = ref(false);
const currentImageIndex = ref(0);
const imageLoadStates = ref([]);

// Product images
const productImages = computed(() => {
    if (!props.product) return [];
    const images = [];
    if (props.product.feature_image) {
        images.push(props.product.feature_image);
    }
    if (
        props.product.gallery_images &&
        Array.isArray(props.product.gallery_images)
    ) {
        images.push(...props.product.gallery_images);
    }
    return images;
});

// Extract all unique attributes dynamically from variations
const availableAttributes = computed(() => {
    if (!props.product.variations) return [];

    const attributesMap = new Map();

    props.product.variations.forEach((variation) => {
        variation.attributes?.forEach((attr) => {
            const attrName = attr.value.attribute.name.toLowerCase();
            if (!attributesMap.has(attrName)) {
                attributesMap.set(attrName, {
                    name: attr.value.attribute.name,
                    values: new Map(),
                });
            }
            const valuesMap = attributesMap.get(attrName).values;
            if (!valuesMap.has(attr.value.id)) {
                valuesMap.set(attr.value.id, {
                    id: attr.value.id,
                    value: attr.value.value,
                    color: attr.value.color,
                    image: variation.image_path || null,
                });
            } else if (variation.image_path && !valuesMap.get(attr.value.id).image) {
                const existing = valuesMap.get(attr.value.id);
                existing.image = variation.image_path;
            }
        });
    });

    const result = [];
    attributesMap.forEach((attrData, attrKey) => {
        result.push({
            key: attrKey,
            name: attrData.name,
            values: Array.from(attrData.values.values()),
        });
    });
    return result;
});

const selectAttribute = (type, value) => {
    selectedAttributes.value[type] = value;
};

const getMatchingVariation = () => {
    if (!props.product.variations) return null;
    const selectedKeys = Object.keys(selectedAttributes.value);

    if (selectedKeys.length === 0 && availableAttributes.value.length > 0)
        return null;

    return props.product.variations.find((variation) => {
        if (!variation.attributes) return false;
        if (variation.attributes.length !== selectedKeys.length) return false;
        return selectedKeys.every((attrKey) => {
            const selectedAttr = selectedAttributes.value[attrKey];
            return variation.attributes.some(
                (varAttr) =>
                    varAttr.value.attribute.name.toLowerCase() === attrKey &&
                    varAttr.value.id === selectedAttr.id,
            );
        });
    });
};

const selectedVariation = computed(() => getMatchingVariation());

const pricing = computed(() => {
    if (!props.product) return { main: 0, cross: null, discountValue: 0, discountType: null };
    const selected = selectedVariation.value;
    if (selected) {
        let main = parseFloat(selected.price || 0);
        let cross = null;
        let discountValue = 0;
        let discountType = null;

        if (
            selected.previous_price &&
            parseFloat(selected.previous_price) > parseFloat(selected.price)
        ) {
            cross = parseFloat(selected.previous_price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
        return { main, cross, discountValue, discountType };
    }

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

    let main = parseFloat(props.product.price);
    let cross = null;
    let discountValue = 0;
    let discountType = null;

    if (
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

// Calculate total stock for variable products
const totalStock = computed(() => {
    if (!props.product) return 0;
    if (props.product.type === "variable" && props.product.variations) {
        return props.product.variations.reduce((sum, variation) => {
            return sum + (variation.stock || 0);
        }, 0);
    }
    return props.product.stock || 0;
});

// Current stock based on selection
const currentStock = computed(() => {
    if (!props.product) return 0;
    if (props.product.type === "variable") {
        const variation = getMatchingVariation();
        // If variation is selected, show its stock, otherwise show total stock
        if (variation) {
            return variation.stock || 0;
        }
        return totalStock.value;
    }
    return props.product.stock || 0;
});

// Stock display message
const stockMessage = computed(() => {
    if (!props.product) return "";
    if (currentStock.value > 0) {
        if (props.product.type === "variable" && !getMatchingVariation()) {
            return `Total Stock: ${currentStock.value} available`;
        }
        return `In Stock (${currentStock.value} available)`;
    }
    if (props.product.is_pre_order) return "Pre-Order Product";
    return "Out of Stock";
});

const isPreOrderMode = computed(() => {
    if (!props.product) return false;
    return props.product.is_pre_order && currentStock.value <= 0;
});

const canAddToCart = computed(() => {
    if (!props.product) return false;
    if (props.product.type !== "variable") return props.product.stock > 0 || props.product.is_pre_order;

    const requiredAttrs = availableAttributes.value.map((a) => a.key);
    const selected = Object.keys(selectedAttributes.value);
    const allSelected = requiredAttrs.every((k) => selected.includes(k));

    if (!allSelected) return false;

    const variation = getMatchingVariation();
    return variation && (variation.stock > 0 || props.product.is_pre_order);
});

const performAddToCart = (redirectToCheckout = false) => {
    isAddingToCart.value = true;

    let variationId = null;
    if (props.product.type === "variable") {
        const v = getMatchingVariation();
        if (v) variationId = v.id;
    }

    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: variationId,
        productOptions: {},
        openSidebar: !redirectToCheckout,
        onSuccess: () => {
            isAddingToCart.value = false;
            emit("close");
            quantity.value = 1;
            selectedAttributes.value = {};
            if (redirectToCheckout) {
                router.visit(route("order.create"));
            }
        },
        onError: () => {
            isAddingToCart.value = false;
        },
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const nextImage = () => {
    if (currentImageIndex.value < productImages.value.length - 1) {
        currentImageIndex.value++;
    } else {
        currentImageIndex.value = 0;
    }
};

const prevImage = () => {
    if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
    } else {
        currentImageIndex.value = productImages.value.length - 1;
    }
};

const selectImage = (index) => {
    currentImageIndex.value = index;
};

const handleImageLoad = (index) => {
    imageLoadStates.value[index] = true;
};

// Animation sequence when opening
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            // Reset and prepare image load states
            imageLoadStates.value = new Array(productImages.value.length).fill(
                false,
            );

            // Stagger image loading for one-by-one appearance
            productImages.value.forEach((_, index) => {
                setTimeout(() => {
                    imageLoadStates.value[index] = true;
                }, index * 100); // 100ms delay between each image
            });

            // Lock body scroll
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
            // Reset on close
            selectedAttributes.value = {};
            quantity.value = 1;
            currentImageIndex.value = 0;
            imageLoadStates.value = [];
        }
    },
    { immediate: true },
);

// Close on ESC key
const handleEscape = (e) => {
    if (e.key === "Escape" && props.show) {
        emit("close");
    }
};

onMounted(() => {
    document.addEventListener("keydown", handleEscape);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleEscape);
    document.body.style.overflow = "";
});
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition name="fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 z-[9999]"
                @click="$emit('close')"
            ></div>
        </Transition>

        <!-- Quick View Sidebar Panel (Slides from right like cart) -->
        <Transition name="slide-left">
            <div
                v-if="show"
                class="fixed top-0 right-0 h-full w-full md:w-[850px] lg:w-[1000px] max-w-[95vw] bg-white z-[9999] shadow-2xl flex flex-col"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between p-3 md:p-6 border-b bg-gray-50"
                >
                    <h2
                        class="text-base md:text-xl font-bold text-gray-900 uppercase tracking-wide"
                    >
                        Select Options
                    </h2>
                    <button
                        @click="$emit('close')"
                        class="p-1.5 hover:bg-gray-200 rounded-full transition-colors"
                    >
                        <X :size="20" class="md:hidden" />
                        <X :size="24" class="hidden md:block" />
                    </button>
                </div>

                <!-- Main Content Area: Images (Left) + Details (Right) -->
                <div class="flex-1 flex flex-col md:flex-row overflow-y-auto md:overflow-hidden">
                    <!-- Left Side: Horizontally Scrollable Images (Mobile) / Vertically Scrollable (Desktop) -->
                    <div
                        class="md:w-1/2 bg-gray-50 md:overflow-y-auto p-3 md:p-6 scrollbar-hide flex-shrink-0"
                    >
                        <div class="grid grid-cols-3 md:flex md:flex-col gap-2 md:space-y-3">
                            <Transition
                                v-for="(image, index) in productImages"
                                :key="index"
                                name="image-appear"
                                appear
                            >
                                <div
                                    v-if="imageLoadStates[index]"
                                    class="relative flex-shrink-0 w-full aspect-square md:aspect-[3/4] rounded-lg overflow-hidden bg-white shadow-sm cursor-pointer border-2 transition-all hover:shadow-lg"
                                    :class="
                                        currentImageIndex === index
                                            ? 'border-primary ring-2 ring-primary/30'
                                            : 'border-gray-200 hover:border-gray-300'
                                    "
                                    @click="selectImage(index)"
                                >
                                    <img
                                        :src="image"
                                        :alt="`${product.name} ${index + 1}`"
                                        class="w-full h-full object-cover transition-all duration-300"
                                        :class="
                                            currentStock <= 0 && !product.is_pre_order
                                                ? 'blur-sm grayscale'
                                                : ''
                                        "
                                        loading="lazy"
                                    />
                                    <!-- Out of Stock Overlay -->
                                    <div
                                        v-if="currentStock <= 0 && !product.is_pre_order"
                                        class="absolute inset-0 bg-white/60 flex items-center justify-center"
                                    >
                                        <span
                                            class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-sm"
                                        >
                                            OUT OF STOCK
                                        </span>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Right Side: Product Details (Scrollable on desktop) -->
                    <div class="md:w-1/2 flex flex-col">
                        <div class="p-2 md:p-4 md:flex-1 md:overflow-y-auto">
                            <div class="space-y-2">
                                <!-- Product Name -->
                                <h2
                                    class="text-xl md:text-2xl font-bold text-gray-900 uppercase"
                                >
                                    {{ product.name }}
                                </h2>

                                <!-- Price -->
                                <div class="flex items-baseline gap-3">
                                    <span
                                        class="text-xl md:text-3xl font-bold text-gray-900"
                                    >
                                        ৳{{ formatPrice(pricing.main) }}
                                    </span>
                                    <span
                                        v-if="pricing.cross"
                                        class="text-base text-gray-400 line-through"
                                    >
                                        ৳{{ formatPrice(pricing.cross) }}
                                    </span>
                                </div>

                                <!-- Shipping Info -->
                                <p class="text-sm text-gray-600">
                                    <span class="underline cursor-pointer"
                                        >Shipping</span
                                    >
                                    calculated at checkout.
                                </p>

                                <!-- Attributes Selection -->
                                <div
                                    v-if="availableAttributes.length > 0"
                                    class="space-y-4 pt-4 border-t border-gray-100 mt-4"
                                >
                                    <div
                                        v-for="attr in availableAttributes"
                                        :key="attr.key"
                                        class="space-y-2.5"
                                    >
                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">{{ attr.name }}</span>
                                                <span v-if="!selectedAttributes[attr.key]" class="text-[9px] bg-rose-50 text-rose-600 border border-rose-100 px-1.5 py-0.5 rounded font-semibold uppercase tracking-wider">
                                                    Required
                                                </span>
                                            </div>
                                            <span v-if="selectedAttributes[attr.key]" class="text-xs font-medium text-gray-500">
                                                Selected: <span class="font-bold text-gray-900">{{ selectedAttributes[attr.key].value }}</span>
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-2.5">
                                            <!-- Color & Design Swatches (Circular) -->
                                            <template v-if="['color', 'colour', 'design', 'designs'].includes(attr.key.toLowerCase())">
                                                <div
                                                    v-for="val in attr.values"
                                                    :key="val.id"
                                                    class="group relative"
                                                >
                                                    <!-- Tooltip -->
                                                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-2.5 py-1 bg-gray-950 text-white text-[10px] font-semibold rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity duration-150 whitespace-nowrap pointer-events-none z-50">
                                                        {{ val.value }}
                                                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-l-4 border-l-transparent border-r-4 border-r-transparent border-t-4 border-t-gray-950"></div>
                                                    </div>

                                                    <button
                                                        @click="selectAttribute(attr.key, val)"
                                                        class="w-10 h-10 rounded-full border-2 transition-all duration-300 p-0.5 relative flex items-center justify-center focus:outline-none"
                                                        :class="selectedAttributes[attr.key]?.id === val.id 
                                                            ? 'border-primary ring-2 ring-primary/30 ring-offset-2 scale-105' 
                                                            : 'border-gray-200 hover:border-gray-400 hover:scale-105'"
                                                    >
                                                        <div v-if="val.image" class="w-full h-full rounded-full overflow-hidden">
                                                            <img :src="val.image" class="w-full h-full object-cover" />
                                                        </div>
                                                        <div v-else-if="val.color" :style="{ backgroundColor: val.color }" class="w-full h-full rounded-full border border-black/5"></div>
                                                        <div v-else class="w-full h-full rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase">
                                                            {{ val.value.substring(0, 2) }}
                                                        </div>
                                                        
                                                        <!-- Premium Checkmark Overlay for selected item -->
                                                        <span v-if="selectedAttributes[attr.key]?.id === val.id" 
                                                              class="absolute -top-1 -right-1 bg-primary text-white w-4 h-4 rounded-full flex items-center justify-center shadow-md border border-white">
                                                            <Check :size="10" stroke-width="3" />
                                                        </span>
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- Standard Pickers (Size, Storage, etc.) -->
                                            <template v-else>
                                                <button
                                                    v-for="val in attr.values"
                                                    :key="val.id"
                                                    @click="selectAttribute(attr.key, val)"
                                                    class="px-4 py-2 rounded-xl border text-xs font-bold transition-all duration-200 relative min-w-[3rem] text-center focus:outline-none"
                                                    :class="selectedAttributes[attr.key]?.id === val.id
                                                        ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary'
                                                        : 'border-gray-200 hover:border-gray-400 text-gray-700 bg-white hover:bg-gray-50'"
                                                >
                                                    {{ val.value }}
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quantity Selector -->
                                <div class="pt-2 md:pt-4">
                                    <div
                                        class="flex items-center border border-gray-300 h-10 md:h-12"
                                    >
                                        <button
                                            @click="
                                                quantity > 1 ? quantity-- : null
                                            "
                                            class="w-10 md:w-12 h-full flex items-center justify-center text-gray-900 hover:bg-gray-100 transition-colors disabled:opacity-50"
                                            :disabled="quantity <= 1"
                                        >
                                            <Minus :size="14" />
                                        </button>
                                        <span
                                            class="flex-1 text-center font-medium text-gray-900 text-sm md:text-base"
                                        >
                                            {{ quantity }}
                                        </span>
                                        <button
                                            @click="quantity++"
                                            class="w-10 md:w-12 h-full flex items-center justify-center text-gray-900 hover:bg-gray-100 transition-colors"
                                        >
                                            <Plus :size="14" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="grid grid-cols-2 md:grid-cols-1 gap-2 md:gap-3 pt-2 md:pt-4">
                                    <button
                                        @click="performAddToCart"
                                        :disabled="
                                            !canAddToCart || isAddingToCart
                                        "
                                        class="w-full bg-white border-2 border-gray-900 text-gray-900 font-bold py-2.5 md:py-4 text-xs md:text-base hover:bg-gray-900 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all duration-300"
                                    >
                                        <span v-if="isAddingToCart"
                                            >Adding...</span
                                        >
                                        <span v-else>{{ isPreOrderMode ? 'PRE-ORDER' : 'ADD TO CART' }}</span>
                                    </button>

                                    <button
                                        @click="performAddToCart(true)"
                                        :disabled="
                                            !canAddToCart || isAddingToCart
                                        "
                                        class="w-full bg-gray-900 text-white font-bold py-2.5 md:py-4 text-xs md:text-base hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
                                    >
                                        BUY NOW
                                    </button>

                                </div>

                                <!-- Stock Status -->
                                <div class="pt-2">
                                    <span
                                        class="inline-flex items-center text-sm font-medium"
                                        :class="
                                            currentStock > 0
                                                ? 'text-green-700'
                                                : 'text-red-700'
                                        "
                                    >
                                        {{ currentStock > 0 || product.is_pre_order ? "✓" : "✗" }}
                                        {{ stockMessage }}
                                    </span>
                                </div>

                                <!-- Pre-Order Details -->
                                <div v-if="isPreOrderMode" class="bg-violet-50 border border-violet-100 rounded-xl p-4 space-y-1">
                                    <p class="text-violet-900 font-bold text-sm uppercase">Pre-Order Details</p>
                                    <p class="text-violet-700 text-xs leading-relaxed">
                                        • 20-30 days delivery time<br>
                                        • 50% advance payment (৳{{ formatPrice(pricing.main * 0.5) }})
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Fade transition for backdrop (same as cart) */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Slide from right animation (same as cart sidebar) */
.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(100%);
}

/* One-by-one image appearance animation */
.image-appear-enter-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.image-appear-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.9);
}

/* Hide scrollbar completely on both mobile and desktop */
.scrollbar-hide {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none; /* Chrome, Safari and Opera */
}

/* Hide scrollbar but keep functionality for detail section */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
