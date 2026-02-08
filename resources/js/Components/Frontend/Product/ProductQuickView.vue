<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { useCart } from "@/Composables/useCart";
import { X, Plus, Minus, ShoppingCart } from "lucide-vue-next";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const { addToCart: addToCartAction } = useCart();

const selectedAttributes = ref({});
const quantity = ref(1);
const isAddingToCart = ref(false);
const currentImageIndex = ref(0);
const imageLoadStates = ref([]);

// Product images
const productImages = computed(() => {
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
                });
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
    if (props.product.type === "variable" && props.product.variations) {
        return props.product.variations.reduce((sum, variation) => {
            return sum + (variation.stock || 0);
        }, 0);
    }
    return props.product.stock || 0;
});

// Current stock based on selection
const currentStock = computed(() => {
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
    if (currentStock.value > 0) {
        if (props.product.type === "variable" && !getMatchingVariation()) {
            return `Total Stock: ${currentStock.value} available`;
        }
        return `In Stock (${currentStock.value} available)`;
    }
    return "Out of Stock";
});

const canAddToCart = computed(() => {
    if (props.product.type !== "variable") return props.product.stock > 0;

    const requiredAttrs = availableAttributes.value.map((a) => a.key);
    const selected = Object.keys(selectedAttributes.value);
    const allSelected = requiredAttrs.every((k) => selected.includes(k));

    if (!allSelected) return false;

    const variation = getMatchingVariation();
    return variation && variation.stock > 0;
});

const performAddToCart = () => {
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
        onSuccess: () => {
            isAddingToCart.value = false;
            emit("close");
            quantity.value = 1;
            selectedAttributes.value = {};
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
            imageLoadStates.value = new Array(productImages.value.length).fill(false);
            
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
                    class="flex items-center justify-between p-4 md:p-6 border-b bg-gray-50"
                >
                    <h2 class="text-lg md:text-xl font-bold text-gray-900 uppercase tracking-wide">
                        Select Options
                    </h2>
                    <button
                        @click="$emit('close')"
                        class="p-2 hover:bg-gray-200 rounded-full transition-colors"
                    >
                        <X :size="24" />
                    </button>
                </div>

                <!-- Main Content Area: Images (Left) + Details (Right) -->
                <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
                    <!-- Left Side: Horizontally Scrollable Images (Mobile) / Vertically Scrollable (Desktop) -->
                    <div class="md:w-1/2 bg-gray-50 overflow-x-auto md:overflow-x-hidden md:overflow-y-auto p-4 md:p-6 scrollbar-hide">
                        <div class="flex md:flex-col gap-3 md:space-y-0">
                            <Transition
                                v-for="(image, index) in productImages"
                                :key="index"
                                name="image-appear"
                                appear
                            >
                                <div
                                    v-if="imageLoadStates[index]"
                                    class="relative flex-shrink-0 w-[280px] md:w-full aspect-[3/4] rounded-lg overflow-hidden bg-white shadow-md cursor-pointer border-2 transition-all hover:shadow-lg"
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
                                        :class="currentStock <= 0 ? 'blur-sm grayscale' : ''"
                                        loading="lazy"
                                    />
                                    <!-- Out of Stock Overlay -->
                                    <div
                                        v-if="currentStock <= 0"
                                        class="absolute inset-0 bg-white/60 flex items-center justify-center"
                                    >
                                        <span class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-sm">
                                            OUT OF STOCK
                                        </span>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Right Side: Product Details (Scrollable) -->
                    <div class="md:w-1/2 flex flex-col overflow-hidden">
                        <div class="flex-1 overflow-y-auto p-4 md:p-6">
                            <div class="space-y-4">
                                <!-- Product Name -->
                                <h2 class="text-xl md:text-2xl font-bold text-gray-900 uppercase">
                                    {{ product.name }}
                                </h2>

                                <!-- Price -->
                                <div class="flex items-baseline gap-3">
                                    <span class="text-2xl md:text-3xl font-bold text-gray-900">
                                        ${{ formatPrice(pricing.main) }}
                                    </span>
                                    <span
                                        v-if="pricing.cross"
                                        class="text-lg text-gray-400 line-through"
                                    >
                                        ${{ formatPrice(pricing.cross) }}
                                    </span>
                                </div>

                                <!-- Shipping Info -->
                                <p class="text-sm text-gray-600">
                                    <span class="underline cursor-pointer">Shipping</span> calculated at checkout.
                                </p>

                                <!-- Attributes Selection -->
                                <div
                                    v-if="availableAttributes.length > 0"
                                    class="space-y-4 pt-2"
                                >
                                    <div
                                        v-for="attr in availableAttributes"
                                        :key="attr.key"
                                        class="space-y-3"
                                    >
                                        <div class="flex justify-between items-center">
                                            <label class="text-sm font-bold text-gray-900 uppercase tracking-wide">
                                                {{ attr.name }}:
                                                <span v-if="selectedAttributes[attr.key]" class="font-normal">
                                                    {{ selectedAttributes[attr.key].value }}
                                                </span>
                                            </label>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="val in attr.values"
                                                :key="val.id"
                                                @click="selectAttribute(attr.key, val)"
                                                class="px-4 py-2 text-sm font-medium border transition-all duration-200"
                                                :class="
                                                    selectedAttributes[attr.key]?.id === val.id
                                                        ? 'bg-gray-900 border-gray-900 text-white'
                                                        : 'bg-white border-gray-300 text-gray-900 hover:border-gray-900'
                                                "
                                            >
                                                {{ val.value }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quantity Selector -->
                                <div class="pt-4">
                                    <div class="flex items-center border border-gray-300">
                                        <button
                                            @click="quantity > 1 ? quantity-- : null"
                                            class="w-12 h-12 flex items-center justify-center text-gray-900 hover:bg-gray-100 transition-colors disabled:opacity-50"
                                            :disabled="quantity <= 1"
                                        >
                                            <Minus :size="16" />
                                        </button>
                                        <span class="flex-1 text-center font-medium text-gray-900">
                                            {{ quantity }}
                                        </span>
                                        <button
                                            @click="quantity++"
                                            class="w-12 h-12 flex items-center justify-center text-gray-900 hover:bg-gray-100 transition-colors"
                                        >
                                            <Plus :size="16" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="space-y-3 pt-4">
                                    <button
                                        @click="performAddToCart"
                                        :disabled="!canAddToCart || isAddingToCart"
                                        class="w-full bg-white border-2 border-gray-900 text-gray-900 font-bold py-3 md:py-4 hover:bg-gray-900 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all duration-300"
                                    >
                                        <span v-if="isAddingToCart">Adding...</span>
                                        <span v-else>ADD TO CART</span>
                                    </button>

                                    <button
                                        @click="performAddToCart"
                                        :disabled="!canAddToCart || isAddingToCart"
                                        class="w-full bg-gray-900 text-white font-bold py-3 md:py-4 hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
                                    >
                                        BUY IT NOW
                                    </button>

                                    <button
                                        class="w-full bg-white border border-gray-300 text-gray-900 font-medium py-3 hover:bg-gray-50 transition-all duration-300 flex items-center justify-center gap-2"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        Add to Wishlist
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
                                        {{ currentStock > 0 ? '✓' : '✗' }} {{ stockMessage }}
                                    </span>
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
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;  /* Chrome, Safari and Opera */
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
