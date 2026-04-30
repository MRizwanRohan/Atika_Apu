<script setup>
import { ref, computed, watch, watchEffect } from "vue";
import { useCart } from "@/Composables/useCart";
import { useCartSidebar } from "@/Composables/useCartSidebar";
import { useWishlist } from "@/Composables/useWishlist";
import { useCompare } from "@/Composables/useCompare";
import { useToast } from "@/Composables/useToast";
import { GitCompare, Truck } from "lucide-vue-next";
import { router, usePage } from "@inertiajs/vue3";
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

// Use centralized cart composable
const { addToCart: addToCartAction, isLoading: cartLoading } = useCart();
const { openCart } = useCartSidebar();
const { trackViewContent, trackAddToCart: pixelTrackAddToCart } = useMetaPixel();
const { toggleWishlist, isInWishlist } = useWishlist();
const { toggleCompare, isInCompare } = useCompare();
const { warning, error } = useToast();

const selectedAttributes = ref({});
const quantity = ref(1);
const isAddingToCart = ref(false);

const emit = defineEmits(["variation-change", "scroll-to-description"]);

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

// Helper: find the variation that matches current selection (if any)
const selectedVariation = computed(() => getMatchingVariation());

const pricing = computed(() => {
    // If a variation is explicitly selected, show its prices
    const selected = selectedVariation.value;
    let main = null;
    let cross = null;
    let discountValue = 0;
    let discountType = null;

    // Campaign logic should still prefer the product-level price/campaign when no variation selected
    if (selected) {
        main = parseFloat(selected.price);
        if (
            selected.previous_price &&
            parseFloat(selected.previous_price) > parseFloat(selected.price)
        ) {
            cross = parseFloat(selected.previous_price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
    } else if (
        props.product.type === "variable" &&
        props.product.variations &&
        props.product.variations.length > 0
    ) {
        // No selection: show lowest variation price as main
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
        if (minVar) {
            main = minVar.price;
            // Find any previous_price greater than main — prefer the maximum previous_price for a clear cross price
            const candidates = numericVars
                .filter((v) => v.previous_price && v.previous_price > main)
                .map((v) => v.previous_price);
            if (candidates.length > 0) {
                cross = Math.max(...candidates);
                discountValue = ((cross - main) / cross) * 100;
                discountType = "percentage";
            }
        }

        // If there's an active campaign on product level, we still apply it to product price display ONLY when product.price is meaningful (fallback)
        if (activeCampaign.value && main === null) {
            main = parseFloat(props.product.price);
            cross = main;
            discountValue = parseFloat(activeCampaign.value.discount_amount);
            discountType = activeCampaign.value.discount_type;

            if (discountType === "percentage") {
                main = main - main * (discountValue / 100);
            } else if (discountType === "fixed") {
                main = main - discountValue;
            }
        }
    } else {
        // Simple product or fallback
        main = parseFloat(props.product.price);

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
    }

    return {
        main,
        cross,
        discountValue,
        discountType,
    };
});

const discountBadgeText = computed(() => {
    if (!pricing.value.cross) return null;
    if (pricing.value.discountType === "percentage") {
        return `${Math.round(pricing.value.discountValue)}% OFF`;
    }
    return `৳ ${parseFloat(pricing.value.discountValue).toFixed(0)} OFF`;
});

// WhatsApp share link using global settings (provided via Inertia page props)
const page = usePage();
const settings = computed(() => page.props.settings || {});
// guard against settings.generalSettings being null
const whatsappNumberRaw = computed(
    () => settings.value.generalSettings?.whatsapp_number || "",
);
// ensure we always operate on a string
const whatsappNumberClean = computed(() =>
    (whatsappNumberRaw.value || "").replace(/\D/g, ""),
);
const whatsappMessage = computed(() => {
    const url = typeof window !== "undefined" ? window.location.href : "";
    const name = props.product.name || "";
    const code = props.product.product_code
        ? ` (Code: ${props.product.product_code})`
        : "";

    return encodeURIComponent(
        `Hello, I'm interested in ${name}${code}. Here's the link: ${url}`,
    );
});
const whatsappLink = computed(() => {
    if (!whatsappNumberClean.value) return null;
    return `https://wa.me/${whatsappNumberClean.value}?text=${whatsappMessage.value}`;
});
const showWhatsapp = computed(() => !!whatsappLink.value);

// const whatsappLink = computed(() => {
//     if (!whatsappNumberClean.value) return null;
//     return `https://api.whatsapp.com/send?phone=${whatsappNumberClean.value}&text=${whatsappMessage.value}`;
// });
// const showWhatsapp = computed(() => !!whatsappNumberClean.value);

// Extract all unique attributes dynamically from variations
const availableAttributes = computed(() => {
    const attributesMap = new Map();

    // First pass: collect unique attribute values (include image:null placeholder)
    props.product.variations?.forEach((variation) => {
        variation.attributes?.forEach((attr) => {
            const attrName = attr.value.attribute.name.toLowerCase();

            if (!attributesMap.has(attrName)) {
                attributesMap.set(attrName, {
                    name: attr.value.attribute.name,
                    values: new Set(),
                });
            }

            attributesMap.get(attrName).values.add(
                JSON.stringify({
                    id: attr.value.id,
                    value: attr.value.value,
                    color: attr.value.color,
                    image: null,
                }),
            );
        });
    });

    // Second pass: if a variation provides an image_path, attach it to the
    // corresponding attribute value (only the first image is used).
    props.product.variations?.forEach((variation) => {
        if (!variation.image_path) return;
        variation.attributes?.forEach((attr) => {
            const attrName = attr.value.attribute.name.toLowerCase();
            const set = attributesMap.get(attrName)?.values;
            if (!set) return;

            // find matching serialized item and replace with image-filled version
            for (const item of Array.from(set)) {
                try {
                    const obj = JSON.parse(item);
                    if (obj.id === attr.value.id) {
                        // replace with an object that contains the variation image
                        set.delete(item);
                        obj.image = variation.image_path;
                        set.add(JSON.stringify(obj));
                        break;
                    }
                } catch (err) {
                    // ignore malformed
                }
            }
        });
    });

    // Convert Map to array with parsed values
    const attributesArray = [];
    attributesMap.forEach((attrData, attrKey) => {
        attributesArray.push({
            key: attrKey,
            name: attrData.name,
            values: Array.from(attrData.values).map((item) => JSON.parse(item)),
        });
    });

    return attributesArray;
});

const selectAttribute = (type, value) => {
    selectedAttributes.value[type] = value;
};

// Find matching variation based on selected attributes
const getMatchingVariation = () => {
    if (!props.product.variations || props.product.variations.length === 0) {
        return null;
    }

    const selectedKeys = Object.keys(selectedAttributes.value);
    if (selectedKeys.length === 0) {
        return null;
    }

    // Find variation that matches all selected attributes EXACTLY
    return props.product.variations.find((variation) => {
        if (!variation.attributes) return false;

        // Ensure the number of attributes matches the number of selections
        if (variation.attributes.length !== selectedKeys.length) return false;

        // Check if every selected attribute matches the variation's attributes
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

// Stock Logic
const currentStock = computed(() => {
    const variation = getMatchingVariation();
    if (variation) {
        return variation.stock;
    }
    // If no variation selected, show global stock
    return props.product.stock;
});

const hasStock = computed(() => {
    return currentStock.value > 0 || props.product.is_pre_order;
});

// Helper for button text
const isPreOrderMode = computed(() => {
    return currentStock.value <= 0 && props.product.is_pre_order;
});

const decreaseQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const increaseQuantity = () => {
    if (quantity.value < currentStock.value) {
        quantity.value++;
    }
};

watch(
    selectedAttributes,
    () => {
        const variation = getMatchingVariation();
        // If a color attribute is selected, capture its image (if any)
        const colorSelection = selectedAttributes.value["color"];
        const attributeImage = colorSelection
            ? colorSelection.image || null
            : null;

        // Emit both the resolved variation (may be null) and attributeImage (for color swatches)
        emit("variation-change", { variation, attributeImage });

        // Reset quantity if it exceeds new stock limit
        if (variation && quantity.value > variation.stock) {
            quantity.value = Math.max(1, variation.stock);
        }
    },
    { deep: true },
);

const addToCart = () => {
    // Check if all attributes are selected
    if (availableAttributes.value.length > 0) {
        const selectedCount = Object.keys(selectedAttributes.value).length;
        if (selectedCount < availableAttributes.value.length) {
            const missing = availableAttributes.value
                .filter((attr) => !selectedAttributes.value[attr.key])
                .map((attr) => attr.name)
                .join(", ");
            error(`Please select: ${missing}`);
            return;
        }
    }

    // Get matching variation
    const matchingVariation = getMatchingVariation();

    // Validate: Check if attributes are required and selected
    if (
        props.product.variations &&
        props.product.variations.length > 0 &&
        !matchingVariation
    ) {
        error("Please select all product options");
        return;
    }

    isAddingToCart.value = true;

    // Use centralized cart composable
    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: matchingVariation ? matchingVariation.id : null,
        productOptions: {
            gift_wrap: false,
            message: "",
        },
        onSuccess: () => {
            isAddingToCart.value = false;
            // Track add_to_cart in Google Tag Manager
            trackAddToCart(props.product, matchingVariation, quantity.value);
            // Track add_to_cart via Meta Pixel (fbq)
            pixelTrackAddToCart(props.product, matchingVariation, quantity.value);
        },
        onError: () => {
            isAddingToCart.value = false;
        },
    });
};

const buyNow = () => {
    // Check if all attributes are selected
    if (availableAttributes.value.length > 0) {
        const selectedCount = Object.keys(selectedAttributes.value).length;
        if (selectedCount < availableAttributes.value.length) {
            const missing = availableAttributes.value
                .filter((attr) => !selectedAttributes.value[attr.key])
                .map((attr) => attr.name)
                .join(", ");
            error(`Please select: ${missing}`);
            return;
        }
    }

    const matchingVariation = getMatchingVariation();

    if (
        props.product.variations &&
        props.product.variations.length > 0 &&
        !matchingVariation
    ) {
        error("Please select all product options");
        return;
    }

    isAddingToCart.value = true;

    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: matchingVariation ? matchingVariation.id : null,
        productOptions: {
            gift_wrap: false,
            message: "",
        },
        openSidebar: false,
        onSuccess: () => {
            isAddingToCart.value = false;
            trackAddToCart(props.product, matchingVariation, quantity.value);
            // Track add_to_cart via Meta Pixel (fbq)
            pixelTrackAddToCart(props.product, matchingVariation, quantity.value);
            router.visit("/order/checkout");
        },
        onError: () => {
            isAddingToCart.value = false;
        },
    });
};

const handleWishlist = () => {
    toggleWishlist(props.product.id);
};

const handleCompare = () => {
    toggleCompare(props.product.id);
};

// Check if attribute is color type
const isColorAttribute = (attrKey) => {
    return attrKey === "color";
};

// Check if color is light (white-ish) for better border visibility
const isLightColor = (color) => {
    if (!color) return false;

    // Convert hex to RGB
    const hex = color.replace("#", "");
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);

    // Calculate brightness (perceived luminance)
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    // Return true if color is light (brightness > 200)
    return brightness > 200;
};

// Check if an attribute option is available (has > 0 stock) given other current selections
const isOptionAvailable = (key, valueObj) => {
    // If no variations, assume available (fallback)
    if (!props.product.variations || props.product.variations.length === 0)
        return true;

    // Create a hypothetical selection merging potentially new value
    const potentialSelection = { ...selectedAttributes.value, [key]: valueObj };

    // Search for ANY variation that matches this combination AND has stock
    return props.product.variations.some((variation) => {
        // Must have stock
        if (variation.stock <= 0) return false;

        // Must match ALL attributes in the potential selection
        // Start by checking if the variation has the attributes we are looking for
        if (!variation.attributes) return false; // Should not happen if data integrity is good

        // For every selected attribute key (color, ram, etc.)
        return Object.keys(potentialSelection).every((selKey) => {
            const desiredVal = potentialSelection[selKey];

            // Find corresponding attribute in variation
            // Note: variation.attributes structure: [ { value: { attribute: { name: 'Color' }, id: 123 } } ]
            const matchingAttr = variation.attributes.find(
                (a) => a.value.attribute.name.toLowerCase() === selKey,
            );

            // If variation doesn't have this attribute defined, it's not a match for this specific combination
            // (Unless we treat missing as 'any', but typically variations are explicit)
            if (!matchingAttr) return false;

            return matchingAttr.value.id === desiredVal.id;
        });
    });
};

// Helper function to wait for dataLayer to be ready
const waitForDataLayer = () => {
    return new Promise((resolve) => {
        if (typeof window !== "undefined" && window.dataLayer) {
            resolve();
        } else {
            // Check every 100ms for up to 5 seconds
            let attempts = 0;
            const maxAttempts = 50;
            const interval = setInterval(() => {
                attempts++;
                if (window.dataLayer) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    console.warn("DataLayer not initialized after 5 seconds");
                    resolve(); // Resolve anyway to not block
                }
            }, 100);
        }
    });
};

// Track view when product is loaded (works for both navigation and reload)
watch(
    () => props.product,
    async (newProduct) => {
        if (newProduct) {
            // Wait for dataLayer to be ready
            await waitForDataLayer();
            // Track product view in Google Tag Manager
            //console.log("Tracking view_item for:", newProduct);
            // Pass the computed main price for accurate ecommerce value
            trackViewItem(newProduct, pricing.value.main);
            // Track ViewContent via Meta Pixel (fbq)
            //trackViewContent(newProduct, pricing.value.main);
        }
    },
    { immediate: true }, // Run immediately if product is already loaded
);

let fired = false;

watch(
    () => [props.product, pricing.value.main],
    ([product, price]) => {
        if (product?.id && price && !fired) {
            console.log("🔥 firing view content", product);
            trackViewContent(product, price);
            fired = true;
        }
    },
    { immediate: true, deep: true }
);

</script>

<template>
    <div class="font-sans text-[#1a1a1a]">
        <!-- Brand Name -->
        <div v-if="product.brand" class="mb-1">
            <span class="text-sm text-gray-500 font-normal">
                {{ product.brand.name }}
            </span>
        </div>

        <!-- Product Title -->
        <h1 class="text-xl md:text-3xl font-extrabold mb-6 leading-tight tracking-tight">
            {{ product.name }}
        </h1>

        <!-- Price, Sold & Rating Section -->
        <div class="flex items-center justify-between flex-wrap mb-2">
            <div class="flex items-baseline gap-4">
                <span class="text-gray-400 line-through md:text-lg text-xs" v-if="pricing.cross">
                    ৳{{ parseFloat(pricing.cross).toFixed(2) }}
                </span>
                <span class="md:text-3xl text-base font-extrabold">
                    ৳{{ parseFloat(pricing.main).toFixed(2) }}
                </span>
            </div>
            
            <div class="flex items-center gap-2 md:text-sm text-xs text-gray-400">
                <span class="font-medium text-gray-400">{{ (product.sold_stock || 0) + (product.fake_sold_count || 0) }} Sold</span>
                <span>•</span>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-orange-400 fill-current" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="font-bold text-gray-900 ml-0.5">{{ product.avg_rating || '4.5' }}</span>
                </div>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 my-6"></div>

        <!-- Pre-order badge -->
        <div v-if="isPreOrderMode" class="mb-4 inline-block bg-orange-100 text-orange-800 text-xs px-3 py-1.5 rounded-full font-semibold border border-orange-200">
            🚚 Pre-order: Optional 50% advance payment available
        </div>

        <!-- Short Description / Summary -->
        <div class="mb-8">
            <h3 class="text-lg font-bold mb-3">Description:</h3>
            <div 
                class="text-gray-500 text-sm leading-relaxed"
                v-html="product.short_description ? product.short_description.substring(0, 200) + '...' : ''"
            ></div>
            <button @click="$emit('scroll-to-description')" class="text-sm font-bold mt-2 hover:underline inline-flex items-center">
                See More....
            </button>
        </div>

        <!-- Dynamic Attributes -->
        <div v-if="availableAttributes.length > 0" class="space-y-8 mb-8">
            <div v-for="attribute in availableAttributes" :key="attribute.key">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-medium text-gray-600">
                        {{ attribute.name }}: 
                        <span class="font-bold text-gray-900" v-if="selectedAttributes[attribute.key]">
                            {{ selectedAttributes[attribute.key].value }}
                        </span>
                    </h3>
                    <button v-if="attribute.key === 'size'" class="text-xs text-gray-500 underline decoration-gray-400 hover:text-black hover:decoration-black font-semibold uppercase tracking-wider">
                        View Size Chart
                    </button>
                </div>

                <!-- Color Attribute -->
                <div
                    v-if="isColorAttribute(attribute.key)"
                    class="flex flex-wrap gap-3"
                >
                    <div
                        v-for="attrValue in attribute.values"
                        :key="attrValue.id"
                        @click="isOptionAvailable(attribute.key, attrValue) && selectAttribute(attribute.key, attrValue)"
                        class="w-[60px] h-10 rounded-lg cursor-pointer transition-all relative flex items-center justify-center p-0.5"
                        :class="[
                            selectedAttributes[attribute.key]?.id === attrValue.id 
                                ? 'ring-2 ring-black' 
                                : 'ring-1 ring-gray-100 hover:ring-gray-300',
                            !isOptionAvailable(attribute.key, attrValue) ? 'opacity-30 cursor-not-allowed border-dashed' : ''
                        ]"
                    >
                        <div 
                            class="w-full h-full rounded-[6px]"
                            :style="{ backgroundColor: attrValue.color || '#ffffff' }"
                        >
                            <img v-if="attrValue.image" :src="attrValue.image" class="w-full h-full object-cover rounded-[6px]" />
                        </div>
                    </div>
                </div>

                <!-- Size / Other Attributes -->
                <div v-else class="flex flex-wrap gap-2.5">
                    <button
                        v-for="attrValue in attribute.values"
                        :key="attrValue.id"
                        @click="selectAttribute(attribute.key, attrValue)"
                        class="px-6 py-3 min-w-[70px] flex items-center justify-center rounded-lg border text-sm font-bold transition-all"
                        :class="[
                            selectedAttributes[attribute.key]?.id === attrValue.id
                                ? 'border-black bg-gray-100 text-black'
                                : 'border-gray-100 bg-white text-gray-800 hover:border-gray-200',
                        ]"
                    >
                        {{ attrValue.value }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-4">
            <div class="flex gap-4">
                <button
                    @click="addToCart"
                    :disabled="isAddingToCart || !hasStock"
                    class="flex-1 h-[54px] bg-[#121212] text-white text-[15px] font-bold rounded-lg hover:bg-black transition-all disabled:opacity-50"
                >
                    <span v-if="isAddingToCart" class="loading loading-spinner"></span>
                    <span v-else>{{ isPreOrderMode ? 'Pre-Order' : 'Add To Cart' }}</span>
                </button>
                <button
                    @click="buyNow"
                    :disabled="isAddingToCart || !hasStock"
                    class="flex-1 h-[54px] bg-white border border-gray-200 text-black text-[15px] font-bold rounded-lg hover:bg-gray-50 transition-all disabled:opacity-50"
                >
                    Checkout Now
                </button>
            </div>

            <!-- <button class="text-xs font-semibold text-gray-500 underline decoration-gray-400 hover:text-black hover:decoration-black w-fit mt-2 uppercase tracking-tight">
                Delivery T&C
            </button> -->
        </div>
    </div>
</template>

<style scoped>
/* Custom styles if needed */
</style>
