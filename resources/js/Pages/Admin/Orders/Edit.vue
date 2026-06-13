<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";
import StatusDropdown from "@/Components/Order/StatusDropdown.vue";

// Debounce function to delay execution
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
};

import {
    ArrowLeft,
    Copy,
    Phone as PhoneIcon,
    MessageCircle,
    Plus,
    Trash2,
    Search,
    X,
    ChevronRight,
    ChevronLeft,
    Truck,
    Shield,
    Eye,
    CheckCircle2,
} from "lucide-vue-next";
import FraudCheckerModal from "@/Components/Couriers/FraudCheckerModal.vue";
import PaymentVerifyModal from "@/Components/Order/PaymentVerifyModal.vue";

const props = defineProps({
    order: Object,
    products: Object,
    paymentMethods: Array,
    orderStatuses: Array,
    paymentStatuses: Array,
    courierSettings: Object,
});

const perPage = ref(props.products.per_page || 10);
const search = ref("");
const currentPage = ref(props.products.current_page || 1);
const loading = ref(false);

// State for selected attributes per product
const selectedAttributes = ref({});

// State for selected variations per product
const selectedVariations = ref({});

// State for error messages per product
const errorMessages = ref({});

// State for quantities per product
const quantities = ref({});

// State for pending quantity changes (item.id => { originalQuantity, newQuantity })
const pendingQuantityChanges = ref({});

// State for display order items
const displayOrderItems = ref([
    ...props.order.items.map((item) => ({
        ...item,
        isNew: false,
        stockLimit:
            item.product.type === "variable"
                ? item.product_variation?.stock
                : item.product.stock,
        isVariationDeleted:
            item.product_variation_id &&
            item.product_variation?.is_deleted === true,
        isProductDeleted: item.product?.is_deleted === true,
    })),
]);

// Check if there are pending changes
const hasPendingChanges = computed(() => {
    return Object.keys(pendingQuantityChanges.value).length > 0;
});

// Form for order updates
const form = useForm({
    customer_name: props.order.customer_name,
    customer_phone: props.order.customer_phone,
    customer_email: props.order.customer_email,
    shipping_address: props.order.shipping_address,
    admin_notes: props.order.admin_notes || "",
    payment_status: props.order.payment_status,
    payment_method: props.order.payment_method,
    discount: props.order.pos_discount || 0,
    discount_type: props.order.discount_type || "fixed",
    shipping_cost: props.order.shipping_cost || 0,
    area: props.order.area || "inside_dhaka",
    status: props.order.status || "pending",
    source: props.order.source || "website",
    paid_amount: props.order.paid_amount || 0,
    customer_notes: props.order.customer_notes || "",
});

const showProductModal = ref(false);

const dueBill = computed(() => {
    return Math.max(
        0,
        orderTotalWithDiscount.value - (parseFloat(form.paid_amount) || 0),
    );
});

const copyToClipboard = (text) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    toast.success("Copied to clipboard");
};

const makeCall = (phone) => {
    if (!phone) return;
    window.location.href = `tel:${phone}`;
};

const openWhatsApp = (phone) => {
    if (!phone) return;
    const cleanPhone = phone.replace(/\D/g, "");
    window.open(
        `https://wa.me/${cleanPhone.startsWith("88") ? cleanPhone : "88" + cleanPhone}`,
        "_blank",
    );
};

// Store the referring URL on mount so we can navigate back with filters intact
const previousUrl = ref('');

// Initialize quantities and search value on mount
onMounted(() => {
    // Read saved orders index URL from sessionStorage (set by Index.vue with full filter params)
    previousUrl.value = sessionStorage.getItem('ordersIndexUrl') || route('admin.orders.index');

    const url = new URL(window.location.href);
    const searchParam = url.searchParams.get("search");
    if (searchParam) {
        search.value = searchParam;
    }
    // Initialize quantities for all products
    props.products.data.forEach((product) => {
        quantities.value[product.id] = 1;
    });
});

// Go back to previous page
const goBack = () => {
    window.history.back();
};

const sendToPathao = () => {
    router.post(
        route("admin.couriers.pathao.shipment"),
        {
            orderId: props.order.id,
            specialInstruction: "Need to Delivery before 5 PM",
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                toast.success(
                    page.props.flash?.message ||
                        "Order sent to Pathao successfully",
                );
            },
            onError: (errors) => {
                toast.error(errors.message || "Failed to send order to Pathao");
            },
        },
    );
};

const sendToSteadfast = async () => {
    try {
        const response = await axios.post("/api/courier/orders", {
            oders_id: props.order.id.toString(),
        });
        toast.success(
            response.data.message || "Order sent to Steadfast successfully",
        );
        router.reload();
    } catch (error) {
        toast.error(
            error.response?.data?.message ||
                "Failed to send order to Steadfast",
        );
    }
};

const showFraudModal = ref(false);
const fraudData = ref({});
const checkFraudLoading = ref(false);

const checkFraud = async () => {
    try {
        checkFraudLoading.value = true;
        const response = await axios.post("/admin/fraud-check", {
            phone: form.customer_phone,
        });
        fraudData.value = response.data;
        showFraudModal.value = true;
        toast.success(response.data.message || "Fraud check successful");
    } catch (error) {
        console.error("Fraud check error:", error);
        toast.error(error.response?.data?.message || "Failed to check fraud");
    } finally {
        checkFraudLoading.value = false;
    }
};

const showPaymentModal = ref(false);
const verifyPayment = () => {
    showPaymentModal.value = true;
};

const showVariationModal = ref(false);
const activeProductForVariation = ref(null);

const selectVariationAndAdd = (product) => {
    activeProductForVariation.value = product;
    if (product.type === "variable") {
        showVariationModal.value = true;
    } else {
        orderNew(product);
    }
};

const hasPreOrder = computed(() => {
    return props.order.items?.some((item) => !!item.is_pre_order);
});

// Compute filtered products based on search
const filteredProducts = computed(() => {
    if (!search.value) return props.products.data;
    const searchTerm = search.value.toLowerCase();
    return props.products.data.filter(
        (product) =>
            product.name.toLowerCase().includes(searchTerm) ||
            product.id.toString().includes(searchTerm) ||
            product.price.toString().includes(searchTerm) ||
            product.status.toLowerCase().includes(searchTerm),
    );
});

// Compute order subtotal (sum of all item prices)
const orderSubtotal = computed(() => {
    return parseFloat(props.order.subtotal) || 0;
});

// Compute discount amount based on discount type
// POS discount is calculated on: subtotal + shipping
const discountAmount = computed(() => {
    const discountValue = parseFloat(form.discount) || 0;

    if (!discountValue || discountValue <= 0) return 0;

    // Base for discount calculation: subtotal + shipping (same as POS)
    const base = orderSubtotal.value + (parseFloat(form.shipping_cost) || 0);

    if (form.discount_type === "percentage") {
        return (base * Math.min(discountValue, 100)) / 100;
    }

    return Math.min(discountValue, base);
});

// Compute order total with discount
// Formula: subtotal + shipping - discount (same as database and POS)
const orderTotalWithDiscount = computed(() => {
    const subtotal = orderSubtotal.value;
    const shipping = parseFloat(form.shipping_cost) || 0;
    const discount = discountAmount.value;
    return Math.max(0, subtotal + shipping - discount);
});

// Truncate product name to a specified word limit
const truncateName = (name, wordLimit = 2) => {
    const words = name.split(" ");
    if (words.length <= wordLimit) return name;
    return words.slice(0, wordLimit).join(" ") + "...";
};

// Helper to check if a variation combination is out of stock
const isVariationOutOfStock = (product, attrName, value) => {
    if (product.type !== "variable" || !product.variations) return false;

    const currentSelection = { ...selectedAttributes.value[product.id] };
    currentSelection[attrName] = value;

    // Find all variations that match the currentSelection so far
    const matchingVariations = product.variations.filter((v) => {
        if (!v.attributes) return false;
        return Object.entries(currentSelection).every(([a, v_val]) => {
            return v.attributes.some(
                (attr) =>
                    attr.value?.attribute?.name === a &&
                    attr.value?.value === v_val,
            );
        });
    });

    // If no matching variations, it's "unavailable" (effectively out of stock)
    if (matchingVariations.length === 0) return true;

    // If all matching variations have 0 stock
    return matchingVariations.every((v) => (v.stock || 0) <= 0);
};

// Get display price: lowest variation price for variable products, base price for simple
const getDisplayPrice = (product) => {
    if (product.type === 'variable' && product.variations?.length > 0) {
        const prices = product.variations.map(v => parseFloat(v.price) || 0).filter(p => p > 0);
        return prices.length > 0 ? Math.min(...prices) : parseFloat(product.price) || 0;
    }
    return parseFloat(product.price) || 0;
};

// Get unique attributes for a product
const getUniqueAttributes = (product) => {
    const attributes = {};
    if (product.variations && product.variations.length > 0) {
        product.variations.forEach((variation) => {
            if (variation.attributes && variation.attributes.length > 0) {
                variation.attributes.forEach((attr) => {
                    const attrName = attr.value?.attribute?.name;
                    const attrValue = attr.value?.value;
                    if (attrName && attrValue) {
                        if (!attributes[attrName]) {
                            attributes[attrName] = new Set();
                        }
                        attributes[attrName].add(attrValue);
                    }
                });
            }
        });
    }
    return attributes;
};

// Compute available variations for a product based on selected attributes
const getAvailableVariations = (product) => {
    if (!product.variations || product.variations.length === 0) return [];

    const selectedAttrs = selectedAttributes.value[product.id] || {};
    const hasAllAttributesSelected = Object.keys(
        getUniqueAttributes(product),
    ).every((attrName) => selectedAttrs[attrName]);

    if (product.id === 4 && hasAllAttributesSelected) {
        const selectedSize = selectedAttrs["Size"];
        const selectedColor = selectedAttrs["Color"];
        if (
            (selectedSize === "XL" && selectedColor === "Black") ||
            (selectedSize === "L" && selectedColor === "Black")
        ) {
            errorMessages.value[product.id] =
                `Variation not available for ${selectedSize} ${selectedColor}`;
            return [];
        }
    }

    const matchingVariations = product.variations.filter((variation) => {
        return variation.attributes.every((attr) => {
            const attrName = attr.value.attribute.name;
            const attrValue = attr.value.value;
            return selectedAttrs[attrName] === attrValue;
        });
    });

    if (hasAllAttributesSelected && matchingVariations.length === 0) {
        errorMessages.value[product.id] =
            "Selected variation combination not available";
    } else {
        errorMessages.value[product.id] = null;
    }

    return matchingVariations;
};

// Get attributes for an order item
const getItemAttributes = (item) => {
    if (!item.product_variation || !item.product_variation.attributes)
        return [];
    return item.product_variation.attributes.map((attr) => ({
        name: attr.value.attribute.name,
        value: attr.value.value,
    }));
};

// Handle attribute selection
const selectAttribute = (product, attrName, attrValue) => {
    if (!selectedAttributes.value[product.id]) {
        selectedAttributes.value[product.id] = {};
    }
    selectedAttributes.value[product.id][attrName] = attrValue;

    const matchingVariations = getAvailableVariations(product);
    selectedVariations.value[product.id] =
        matchingVariations.length === 1 ? matchingVariations[0] : null;
};

// Increment quantity for a product
const incrementQuantity = (product) => {
    const stockLimit =
        product.type === "variable"
            ? selectedVariations.value[product.id]?.stock || 0
            : product.stock;
    if (quantities.value[product.id] < stockLimit) {
        quantities.value[product.id] += 1;
    } else {
        toast.error(`Maximum stock reached (${stockLimit})`);
    }
};

// Decrement quantity for a product
const decrementQuantity = (product) => {
    if (quantities.value[product.id] > 1) {
        quantities.value[product.id] -= 1;
    }
};

// Handle per page change
const updatePerPage = () => {
    router.get(
        `/admin/orders/${props.order.id}/edit`,
        {
            per_page: perPage.value,
            search: search.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Debounced search function
const debouncedSearch = debounce(() => {
    router.get(
        `/admin/orders/${props.order.id}/edit`,
        {
            per_page: perPage.value,
            search: search.value,
            page: currentPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}, 500);

// Watch for search input changes
watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        debouncedSearch();
    }
});

// Clear search input
const clearSearch = () => {
    search.value = "";
    router.get(
        `/admin/orders/${props.order.id}/edit`,
        {
            per_page: perPage.value,
            search: "",
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Handle pagination
const goToPage = (page) => {
    currentPage.value = page;
    router.get(
        `/admin/orders/${props.order.id}/edit`,
        {
            page: page,
            per_page: perPage.value,
            search: search.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Handle Order New button click
const orderNew = (product) => {
    if (['delivered', 'cancelled', 'returned', 'partially_delivered'].includes(props.order.status)) {
        toast.error(`Cannot add items to order in ${props.order.status} status`);
        return;
    }

    const variationId = selectedVariations.value[product.id]?.id || null;
    if (product.type === "variable" && !variationId) {
        toast.error("Please select a valid variation");
        return;
    }

    // Get the selected quantity to add
    const quantity = quantities.value[product.id] || 1;

    // Check for existing item
    const existingItem = displayOrderItems.value.find(
        (item) =>
            item.product_id === product.id &&
            item.product_variation_id === variationId,
    );

    // Get current available stock (this is what's LEFT in inventory)
    const availableStock =
        product.type === "variable"
            ? selectedVariations.value[product.id].stock
            : product.stock;

    // Simple validation: Can we add the requested quantity based on available stock?
    // We only check if the AMOUNT TO ADD exceeds available stock
    if (quantity > availableStock) {
        toast.error(
            `Cannot add ${quantity} items. Only ${availableStock} available in stock.`,
        );
        return;
    }

    loading.value = true;
    // Send only the quantity to ADD, not the new total (backend will handle the addition)
    router.post(
        route("admin.orders.update-new", { orderId: props.order.id }),
        {
            items: [
                {
                    product_id: product.id,
                    product_variation_id: variationId,
                    quantity: quantity,
                },
            ],
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.error) {
                    toast.error(page.props.flash.error);
                    return;
                }
                // Reload order to sync with server state
                router.reload({
                    only: ["order"],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        // Update displayOrderItems with the latest order items
                        displayOrderItems.value = props.order.items.map(
                            (item) => ({
                                ...item,
                                isNew: false,
                                stockLimit:
                                    item.product.type === "variable"
                                        ? item.product_variation?.stock
                                        : item.product.stock,
                                isVariationDeleted:
                                    item.product_variation_id &&
                                    item.product_variation?.is_deleted === true,
                                isProductDeleted:
                                    item.product?.is_deleted === true,
                            }),
                        );
                        toast.success("Item added to order successfully");
                        showProductModal.value = false; // Close modal after adding
                        // Reset selection and quantity for this product
                        selectedAttributes.value[product.id] = {};
                        selectedVariations.value[product.id] = null;
                        quantities.value[product.id] = 1;
                    },
                });
            },
            onError: (errors) => {
                console.error("Error adding item to order:", errors);
                const errorMessage =
                    errors.message || "Failed to add item to order";
                errorMessages.value[product.id] = errorMessage;
                toast.error(errorMessage);
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

// Handle item removal
const removeItem = (itemId) => {
    if (['delivered', 'cancelled', 'returned', 'partially_delivered'].includes(props.order.status)) {
        toast.error(`Cannot remove items from order in ${props.order.status} status`);
        return;
    }

    loading.value = true;
    router.delete(
        route("admin.orders.items.destroy", {
            order: props.order.id,
            item: itemId,
        }),
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.error) {
                    toast.error(page.props.flash.error);
                    return;
                }
                // Reload order to sync with server state
                router.reload({
                    only: ["order"],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        // Update displayOrderItems with the latest order items
                        displayOrderItems.value = props.order.items.map(
                            (item) => ({
                                ...item,
                                isNew: false,
                                stockLimit:
                                    item.product.type === "variable"
                                        ? item.product_variation?.stock
                                        : item.product.stock,
                                isVariationDeleted:
                                    item.product_variation_id &&
                                    item.product_variation?.is_deleted === true,
                                isProductDeleted:
                                    item.product?.is_deleted === true,
                            }),
                        );
                        toast.success("Item removed from order successfully");
                    },
                    onError: (errors) => {
                        console.error("Error reloading order:", errors);
                        toast.error("Failed to refresh order");
                    },
                });
            },
            onError: (errors) => {
                console.error("Error removing item:", errors);
                toast.error(errors.message || "Failed to remove item");
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

// Increment quantity (for order items - now tracks pending changes)
const incrementOrderItemQuantity = (item) => {
    const pending = pendingQuantityChanges.value[item.id];

    // Get the TRUE original quantity (before any pending changes)
    // If we already have pending changes, use the stored originalQuantity
    // Otherwise, the current item.quantity IS the original
    const trueOriginalQuantity = pending
        ? pending.originalQuantity
        : item.quantity;
    const currentQuantity = pending ? pending.newQuantity : item.quantity;

    // Get available stock (what's LEFT in inventory)
    const availableStock = item.stockLimit;

    // Calculate how many items we've ALREADY added (pending additions)
    const pendingAdditions = currentQuantity - trueOriginalQuantity;

    // Check if adding 1 more would exceed available stock
    // pendingAdditions + 1 > availableStock means we can't add more
    if (pendingAdditions + 1 > availableStock) {
        toast.error(
            `Cannot add more. Only ${availableStock} available in stock. Already added ${pendingAdditions}.`,
        );
        return;
    }

    const newQuantity = currentQuantity + 1;

    // Track the pending change - always use the TRUE original
    if (newQuantity === trueOriginalQuantity) {
        // If back to original, remove from pending
        delete pendingQuantityChanges.value[item.id];
    } else {
        pendingQuantityChanges.value[item.id] = {
            originalQuantity: trueOriginalQuantity,
            newQuantity,
            itemId: item.id,
        };
    }

    // Update display immediately
    const displayItem = displayOrderItems.value.find((i) => i.id === item.id);
    if (displayItem) {
        displayItem.quantity = newQuantity;
        displayItem.final_price = (
            newQuantity * parseFloat(displayItem.unit_price)
        ).toFixed(2);
    }
};

// Decrement quantity (for order items - now tracks pending changes)
const decrementOrderItemQuantity = (item) => {
    const pending = pendingQuantityChanges.value[item.id];

    // Get the TRUE original quantity (before any pending changes)
    const trueOriginalQuantity = pending
        ? pending.originalQuantity
        : item.quantity;
    const currentQuantity = pending ? pending.newQuantity : item.quantity;

    if (currentQuantity <= 1) {
        toast.error(
            "Quantity cannot be less than 1. Use remove button to delete item.",
        );
        return;
    }

    const newQuantity = currentQuantity - 1;

    // Track the pending change - always use the TRUE original
    if (newQuantity === trueOriginalQuantity) {
        // If back to original, remove from pending
        delete pendingQuantityChanges.value[item.id];
    } else {
        pendingQuantityChanges.value[item.id] = {
            originalQuantity: trueOriginalQuantity,
            newQuantity,
            itemId: item.id,
        };
    }

    // Update display immediately
    const displayItem = displayOrderItems.value.find((i) => i.id === item.id);
    if (displayItem) {
        displayItem.quantity = newQuantity;
        displayItem.final_price = (
            newQuantity * parseFloat(displayItem.unit_price)
        ).toFixed(2);
    }
};

// Save all pending quantity changes
const saveQuantityChanges = () => {
    if (!hasPendingChanges.value) return;

    if (['delivered', 'cancelled', 'returned', 'partially_delivered'].includes(props.order.status)) {
        toast.error(`Cannot save quantity changes for order in ${props.order.status} status`);
        return;
    }

    loading.value = true;
    const changes = Object.values(pendingQuantityChanges.value);
    let completed = 0;
    let errors = [];

    // Process each change sequentially
    const processNext = (index) => {
        if (index >= changes.length) {
            // All done
            loading.value = false;
            if (errors.length === 0) {
                toast.success("All quantity changes saved successfully");
                pendingQuantityChanges.value = {};
                // Reload to get fresh data
                router.reload({
                    only: ["order"],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        displayOrderItems.value = props.order.items.map(
                            (orderItem) => ({
                                ...orderItem,
                                isNew: false,
                                stockLimit:
                                    orderItem.product.type === "variable"
                                        ? orderItem.product_variation?.stock
                                        : orderItem.product.stock,
                                isVariationDeleted:
                                    orderItem.product_variation_id &&
                                    orderItem.product_variation?.is_deleted ===
                                        true,
                                isProductDeleted:
                                    orderItem.product?.is_deleted === true,
                            }),
                        );
                    },
                });
            } else {
                toast.error(
                    `${errors.length} change(s) failed. Please check and try again.`,
                );
            }
            return;
        }

        const change = changes[index];
        router.put(
            route("admin.orders.items.update", {
                order: props.order.id,
                item: change.itemId,
            }),
            {
                quantity: change.newQuantity,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    if (page.props.flash?.error) {
                        errors.push({ itemId: change.itemId, error: page.props.flash.error });
                        toast.error(page.props.flash.error);
                    } else {
                        completed++;
                        delete pendingQuantityChanges.value[change.itemId];
                    }
                    processNext(index + 1);
                },
                onError: (err) => {
                    errors.push({ itemId: change.itemId, error: err });
                    processNext(index + 1);
                },
            },
        );
    };

    processNext(0);
};

// Cancel pending changes and revert display
const cancelQuantityChanges = () => {
    // Revert display items to original quantities
    displayOrderItems.value = props.order.items.map((item) => ({
        ...item,
        isNew: false,
        stockLimit:
            item.product.type === "variable"
                ? item.product_variation?.stock
                : item.product.stock,
        isVariationDeleted:
            item.product_variation_id &&
            item.product_variation?.is_deleted === true,
        isProductDeleted: item.product?.is_deleted === true,
    }));
    pendingQuantityChanges.value = {};
    toast.info("Changes cancelled");
};

// Submit form for customer info updates
const submitForm = () => {
    form.post(route("admin.orders.basic-info", { orderId: props.order.id }), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Check for flash message from backend
            if (page.props.flash?.success) {
                toast.success(page.props.flash.success);
            } else {
                toast.success("Order updated successfully");
            }
            // Navigate back to the exact previous URL (preserves all active filters)
            router.visit(previousUrl.value || route("admin.orders.index"));
        },
        onError: (errors) => {
            console.log("Error updating order:", errors);
            toast.error("Failed to update order");
        },
    });
};
</script>

<template>
    <Head title="Edit Order" />
    <AdminLayout>
        <div class="w-full px-1 md:p-2 lg:px-4 space-y-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between bg-white p-2 rounded-lg border border-gray-100 shadow-sm"
            >
                <div class="flex items-center gap-2">
                    <button
                        @click="goBack"
                        class="p-1.5 hover:bg-gray-100 rounded-full transition-colors text-gray-600"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </button>
                    <div class="flex items-center gap-2">
                        <h1
                            class="text-sm font-bold text-gray-900 leading-none"
                        >
                            Order #{{ order.id }}
                        </h1>
                        <span
                            v-if="hasPreOrder"
                            class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-[9px] font-bold uppercase tracking-wider"
                        >
                            Pre-Order
                        </span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider',
                                order.status === 'completed'
                                    ? 'bg-green-100 text-green-700'
                                    : order.status === 'pending'
                                      ? 'bg-amber-100 text-amber-700'
                                      : order.status === 'cancelled'
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-blue-100 text-blue-700',
                            ]"
                        >
                            {{ order.status }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[9px] font-bold uppercase tracking-wider"
                        >
                            {{ order.source }}
                        </span>
                        <span
                            v-if="order.duplicate_percentage > 0"
                            class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[9px] font-bold uppercase tracking-wider border border-red-200 animate-pulse"
                            :title="`Potential duplicate order: ${order.duplicate_percentage}% match`"
                        >
                            Dup {{ order.duplicate_percentage }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Main Layout -->
            <div class="space-y-4">
                <!-- <pre>{{ order.items }}</pre> -->
                <!-- Ordered Items Card -->
                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                >
                    <div
                        class="px-3 py-2 flex items-center justify-between border-b border-gray-100 bg-gray-50/30"
                    >
                        <h2
                            class="text-xs font-bold text-gray-900 uppercase tracking-tight"
                        >
                            Items
                        </h2>
                        <button
                            v-if="!['delivered', 'cancelled', 'returned', 'partially_delivered'].includes(order.status)"
                            @click="showProductModal = true"
                            class="flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-50 transition-all"
                        >
                            <Plus class="w-3 h-3" />
                            Add Items
                        </button>
                    </div>

                    <!-- Items List -->
                    <div class="p-2 space-y-2">
                        <div
                            v-for="item in displayOrderItems"
                            :key="item.id"
                            class="flex items-center justify-between bg-gray-50/30 p-2 rounded-lg border border-gray-100"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white border border-gray-100 rounded-lg overflow-hidden flex-shrink-0"
                                >
                                    <img
                                        :src="item.product_variation && item.product_variation.image_path ? item.product_variation.image_path : item.product.feature_image"
                                        alt=""
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div>
                                    <h3
                                        class="font-bold text-gray-900 text-[11px] leading-tight flex items-center gap-1"
                                    >
                                        {{ item.product.name }}
                                        <span v-if="item.is_pre_order" class="bg-purple-100 text-purple-600 px-1 rounded text-[8px] font-bold">PRE</span>
                                    </h3>
                                    <div
                                        class="flex items-center gap-2 text-[9px] text-gray-500 font-medium mt-0.5"
                                    >
                                        <span
                                            v-if="
                                                getItemAttributes(item).length >
                                                0
                                            "
                                        >
                                            {{
                                                getItemAttributes(item)
                                                    .map((a) => a.value)
                                                    .join(" | ")
                                            }}
                                        </span>
                                        <span
                                            v-if="
                                                getItemAttributes(item).length >
                                                0
                                            "
                                            >|</span
                                        >
                                        <span>Qty: {{ item.quantity }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-gray-900"
                                    >৳{{ item.final_price }}</span
                                >
                                <button
                                    v-if="!['delivered', 'cancelled', 'returned', 'partially_delivered'].includes(order.status)"
                                    @click="removeItem(item.id)"
                                    class="p-1.5 text-gray-300 hover:text-red-500 transition-colors"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="displayOrderItems.length === 0"
                            class="text-center py-6"
                        >
                            <p class="text-gray-400 font-medium text-[10px]">
                                No items in this order.
                            </p>
                        </div>

                        <!-- Summary -->
                        <div class="space-y-2 pt-3 border-t border-gray-100">
                            <div
                                class="flex justify-between items-center text-[11px] font-bold"
                            >
                                <span class="text-gray-500">Subtotal:</span>
                                <span class="text-gray-900"
                                    >৳ {{ orderSubtotal.toFixed(2) }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-[11px] font-bold"
                            >
                                <span class="text-gray-500">Delivery:</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-400">৳</span>
                                    <input
                                        v-model.number="form.shipping_cost"
                                        type="number"
                                        class="w-16 px-1 py-0.5 text-right bg-transparent border-none focus:ring-0 font-bold p-0"
                                    />
                                </div>
                            </div>

                            <!-- Total Bill (Green Highlight) -->
                            <div
                                class="flex justify-between items-center bg-emerald-50/50 text-emerald-700 py-1.5 px-3 rounded-lg border border-emerald-100"
                            >
                                <span
                                    class="text-xs font-bold uppercase tracking-tight"
                                    >Total Bill:</span
                                >
                                <span class="text-sm font-black tracking-tight"
                                    >৳{{
                                        orderTotalWithDiscount.toFixed(2)
                                    }}</span
                                >
                            </div>

                            <!-- Payment Inputs -->
                            <div class="grid grid-cols-2 gap-3 pb-1">
                                <div class="space-y-1">
                                    <label
                                        class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                        >Paid (৳)</label
                                    >
                                    <input
                                        v-model.number="form.paid_amount"
                                        type="number"
                                        class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-xs"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label
                                        class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                        >Method</label
                                    >
                                    <select
                                        v-model="form.payment_method"
                                        class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-xs cursor-pointer"
                                    >
                                        <option
                                            v-for="(
                                                method, key
                                            ) in paymentMethods"
                                            :key="key"
                                            :value="key"
                                        >
                                            {{ method }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Payment Verification Section (Only for Pre-Orders with TrxID) -->
                            <div
                                v-if="hasPreOrder && order.transaction_id"
                                class="p-2 bg-indigo-50/50 border border-indigo-100 rounded-lg space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-wider text-indigo-400"
                                        >Advance Verification</span
                                    >
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            v-if="
                                                order.payment_verification_status ===
                                                'approved'
                                            "
                                            disabled
                                            class="text-[9px] font-bold uppercase text-emerald-600 bg-white px-2 py-0.5 rounded-md border border-emerald-100 flex items-center gap-1 shadow-sm"
                                        >
                                            <CheckCircle2 class="w-3 h-3" />
                                            Approved
                                        </button>
                                        <button
                                            v-else-if="
                                                order.payment_verification_status ===
                                                'rejected'
                                            "
                                            @click="verifyPayment"
                                            class="text-[9px] font-bold uppercase text-rose-600 bg-white px-2 py-0.5 rounded-md border border-rose-100 flex items-center gap-1 hover:bg-rose-50 transition-all shadow-sm"
                                        >
                                            <Shield class="w-3 h-3" />
                                            Rejected
                                        </button>
                                        <button
                                            v-else
                                            @click="verifyPayment"
                                            class="text-[9px] font-bold uppercase text-indigo-600 bg-white px-2 py-0.5 rounded-md border border-indigo-200 flex items-center gap-1 hover:bg-indigo-50 transition-all shadow-sm"
                                        >
                                            <CheckCircle2 class="w-3 h-3" />
                                            Verify
                                        </button>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between bg-white/50 px-2 py-1 rounded-md border border-indigo-100/30"
                                >
                                    <span
                                        class="text-[9px] font-bold text-indigo-600 uppercase"
                                        >Payment Number</span
                                    >
                                    <span
                                        class="text-[10px] font-bold text-indigo-900 font-mono"
                                        >{{ order.transaction_id }}</span
                                    >
                                </div>
                            </div>

                            <!-- Due Bill (Red Highlight) -->
                            <div
                                class="flex justify-between items-center bg-rose-50/50 text-rose-700 py-1.5 px-3 rounded-lg border border-rose-100"
                            >
                                <span
                                    class="text-xs font-bold uppercase tracking-tight"
                                    >Due Bill:</span
                                >
                                <span class="text-sm font-black tracking-tight"
                                    >৳{{ dueBill.toFixed(2) }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Card -->
                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                >
                    <div
                        class="px-3 py-2 border-b border-gray-100 flex items-center justify-between bg-gray-50/30"
                    >
                        <h2
                            class="text-xs font-bold text-gray-900 uppercase tracking-tight"
                        >
                            Customer Information
                        </h2>
                        <div
                            v-if="order.is_courier"
                            class="flex items-center gap-1.5 px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100"
                        >
                            <div
                                class="w-1 h-1 bg-emerald-500 rounded-full"
                            ></div>
                            <span
                                class="text-[9px] font-bold uppercase tracking-wider"
                                >{{ order.courier_name }}</span
                            >
                        </div>
                    </div>
                    <div class="p-3 space-y-3">
                        <!-- Fraud Check Button -->
                        <button
                            @click="checkFraud"
                            :disabled="checkFraudLoading"
                            class="w-full flex items-center justify-center gap-1.5 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg font-bold text-[10px] uppercase tracking-wider hover:bg-gray-50 transition-all disabled:opacity-50"
                        >
                            <span
                                v-if="checkFraudLoading"
                                class="loading loading-spinner loading-xs"
                            ></span>
                            <template v-else>
                                <Shield class="w-3.5 h-3.5 text-blue-500" />
                                Fraud Check / History
                            </template>
                        </button>

                        <div class="space-y-1 single-input-field">
                            <label
                                class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                >Name</label
                            >
                            <input
                                v-model="form.customer_name"
                                class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-xs"
                            />
                        </div>

                        <div class="space-y-1 single-input-field">
                            <label
                                class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                >Phone</label
                            >
                            <div class="relative group">
                                <input
                                    v-model="form.customer_phone"
                                    class="w-full pl-2 pr-28 py-1.5 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-xs"
                                />
                                <div
                                    class="absolute right-1 top-1/2 -translate-y-1/2 flex items-center gap-0.5"
                                >
                                    <button
                                        @click="
                                            copyToClipboard(form.customer_phone)
                                        "
                                        class="p-1 px-1.5 bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 transition-colors"
                                    >
                                        <Copy class="w-3 h-3" />
                                    </button>
                                    <button
                                        @click="makeCall(form.customer_phone)"
                                        class="p-1 px-1.5 bg-emerald-50 text-emerald-600 rounded-md hover:bg-emerald-100 transition-colors"
                                    >
                                        <PhoneIcon class="w-3 h-3" />
                                    </button>
                                    <button
                                        @click="
                                            openWhatsApp(form.customer_phone)
                                        "
                                        class="p-1 px-1.5 bg-green-50 text-green-600 rounded-md hover:bg-green-100 transition-colors"
                                    >
                                        <MessageCircle class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1 single-input-field">
                            <label
                                class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                >Address</label
                            >
                            <textarea
                                v-model="form.shipping_address"
                                rows="1"
                                class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-xs resize-none"
                            ></textarea>
                        </div>

                        <!-- Courier Options (Relocated after Address) -->
                        <div class="space-y-2 py-1">
                            <div
                                v-if="order.is_courier && order.consignment_id"
                                class="flex flex-col gap-0.5"
                            >
                                <span
                                    class="text-[8px] font-black uppercase text-gray-400 tracking-widest pl-0.5"
                                    >Consignment</span
                                >
                                <div
                                    class="flex items-center justify-between bg-slate-50 px-2 py-1 rounded-md border border-gray-100"
                                >
                                    <span
                                        class="text-[16px] font-bold font-mono text-slate-900"
                                        >{{ order.consignment_id }}</span
                                    >
                                    <button
                                        @click="
                                            copyToClipboard(
                                                order.consignment_id,
                                            )
                                        "
                                        class="text-gray-400 hover:text-blue-500 transition-colors"
                                    >
                                        <Copy class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="!order.is_courier"
                                :class="[
                                    'grid gap-2',
                                    courierSettings?.pathao_is_enabled &&
                                    courierSettings?.steadfast_is_enabled
                                        ? 'grid-cols-2'
                                        : 'grid-cols-1',
                                ]"
                            >
                                <button
                                    v-if="courierSettings?.pathao_is_enabled"
                                    @click="sendToPathao"
                                    class="group flex items-center justify-center gap-1.5 py-1.5 px-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all shadow-sm active:scale-95"
                                >
                                    <Truck class="w-3.5 h-3.5" />
                                    Pathao
                                </button>
                                <button
                                    v-if="courierSettings?.steadfast_is_enabled"
                                    @click="sendToSteadfast"
                                    class="group flex items-center justify-center gap-1.5 py-1.5 px-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all shadow-sm active:scale-95"
                                >
                                    <Truck class="w-3.5 h-3.5" />
                                    Steadfast
                                </button>
                            </div>

                            <!-- Tracking Link -->
                            <div
                                v-if="
                                    order?.tracking_number &&
                                    order?.tracking_number !== 'N/A'
                                "
                            >
                                <a
                                    :href="
                                        order.courier_name === 'pathao'
                                            ? `https://merchant.pathao.com/tracking?consignment_id=${order.tracking_number}`
                                            : `https://steadfast.com.bd/t/${order.tracking_number}`
                                    "
                                    target="_blank"
                                    class="flex items-center justify-center gap-1.5 py-1.5 px-3 bg-slate-900 text-white rounded-lg font-bold text-[10px] hover:bg-black transition-all"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                    Track Shipment
                                </a>
                            </div>
                        </div>

                        <!-- Desktop Status Dropdown -->
                        <div class="hidden md:block space-y-1">
                            <label
                                class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                >Status</label
                            >
                            <StatusDropdown
                                v-model="form.status"
                                :order-id="order.id"
                                :is-loading="loading"
                                :is-pre-order="hasPreOrder"
                                @status-change="
                                    (orderId, newStatus) =>
                                        (form.status = newStatus)
                                "
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3 pb-20 md:pb-0">
                            <div class="space-y-1 single-input-field">
                                <label
                                    class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                    >Customer Note</label
                                >
                                <textarea
                                    v-model="form.customer_notes"
                                    rows="1"
                                    class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-[10px] resize-none"
                                ></textarea>
                            </div>

                            <div class="space-y-1 single-input-field">
                                <label
                                    class="text-[9px] font-bold uppercase tracking-wider text-gray-400 pl-1"
                                    >Admin Note</label
                                >
                                <textarea
                                    v-model="form.admin_notes"
                                    rows="1"
                                    class="w-full px-2 py-1.5 bg-blue-50/20 border border-blue-100 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-[10px] resize-none text-blue-800"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Desktop Update Button -->
                        <button
                            @click="submitForm"
                            :disabled="loading"
                            class="hidden md:flex w-full py-2 bg-slate-900 text-white rounded-lg font-bold text-xs hover:bg-black transition-all disabled:bg-gray-400 items-center justify-center gap-1.5"
                        >
                            <span
                                v-if="loading"
                                class="loading loading-spinner loading-xs"
                            ></span>
                            {{ loading ? "Saving..." : "Update Order" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Fixed Bottom Action Bar -->
        <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-8px_30px_-15px_rgba(0,0,0,0.1)] z-40 p-3 flex items-center gap-3">
            <div class="flex-1">
                <StatusDropdown
                    v-model="form.status"
                    :order-id="order.id"
                    :is-loading="loading"
                    :is-pre-order="hasPreOrder"
                    @status-change="
                        (orderId, newStatus) =>
                            (form.status = newStatus)
                    "
                />
            </div>
            <button
                @click="submitForm"
                :disabled="loading"
                class="flex-1 py-2 bg-slate-900 text-white rounded-lg font-bold text-xs hover:bg-black transition-all disabled:bg-gray-400 flex items-center justify-center gap-1.5 h-[38px]"
            >
                <span
                    v-if="loading"
                    class="loading loading-spinner loading-xs"
                ></span>
                {{ loading ? "Saving..." : "Update" }}
            </button>
        </div>

        <!-- Product Search Modal -->
        <div
            v-if="showProductModal"
            class="fixed inset-0 z-[60] flex items-center justify-center p-2 bg-black/40"
        >
            <div
                class="bg-white w-full max-w-4xl rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]"
            >
                <!-- Modal Header -->
                <div
                    class="px-4 py-2 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10"
                >
                    <div>
                        <h2 class="text-xs font-bold text-gray-900 uppercase">
                            Add Products
                        </h2>
                        <p class="text-[9px] text-gray-400 font-medium">
                            Search and select items
                        </p>
                    </div>
                    <button
                        @click="showProductModal = false"
                        class="p-1 hover:bg-gray-100 rounded-full transition-colors"
                    >
                        <X class="w-4 h-4 text-gray-400" />
                    </button>
                </div>

                <!-- Modal Search & Grid -->
                <div class="p-3 space-y-3 overflow-y-auto">
                    <div class="relative">
                        <Search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                        />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search products..."
                            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-100 rounded-lg focus:ring-1 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-xs"
                        />
                    </div>

                    <!-- Product Grid -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2"
                    >
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="group bg-white border border-gray-100 rounded-lg p-1.5 hover:border-blue-300 transition-all flex flex-col h-full"
                        >
                            <!-- Image Container -->
                            <div
                                class="relative aspect-square rounded-md overflow-hidden bg-gray-50 border border-gray-50 mb-2 shrink-0"
                            >
                                <img
                                    :src="product.feature_image"
                                    alt=""
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div
                                    v-if="product.type === 'variable'"
                                    class="absolute top-1 right-1 px-1 py-0.5 bg-blue-500 text-white text-[7px] font-black uppercase rounded shadow-sm"
                                >
                                    VAR
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 flex flex-col">
                                <p
                                    class="text-[10px] font-bold text-gray-800 line-clamp-2 leading-tight mb-1 h-6"
                                >
                                    {{ product.name }}
                                </p>
                                <div class="mt-auto">
                                    <div
                                        class="flex items-center justify-between mb-1.5"
                                    >
                                        <span
                                            class="text-[10px] font-black text-emerald-600"
                                            ><template v-if="product.type === 'variable'">From </template>৳{{ getDisplayPrice(product).toFixed(2) }}</span
                                        >
                                        <span
                                            v-if="
                                                product.stock <= 0 &&
                                                product.type !== 'variable'
                                            "
                                            class="text-[7px] font-black text-red-500 uppercase"
                                            >Out</span
                                        >
                                    </div>

                                    <button
                                        @click="selectVariationAndAdd(product)"
                                        class="w-full py-1.5 bg-slate-900 text-white text-[9px] font-bold uppercase tracking-wider rounded-md hover:bg-black transition-all flex items-center justify-center gap-1 active:scale-95"
                                    >
                                        <Plus class="w-3 h-3" />
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex justify-between items-center"
                >
                    <div
                        class="text-[8px] font-black text-gray-400 uppercase tracking-widest"
                    >
                        {{ products.total }} items
                    </div>
                    <div class="flex gap-1">
                        <button
                            v-for="link in products.links?.slice(1, -1)"
                            :key="link.label"
                            @click="
                                link.url &&
                                goToPage(parseInt(link.url.split('page=')[1]))
                            "
                            :class="[
                                'w-6 h-6 flex items-center justify-center rounded-md text-[10px] font-bold transition-all',
                                link.active
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
                            ]"
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fraud Checker Modal -->
        <FraudCheckerModal
            :visible="showFraudModal"
            :fraudData="fraudData"
            @close="showFraudModal = false"
        />
        <!-- Payment Verify Modal -->
        <PaymentVerifyModal
            :visible="showPaymentModal"
            :order="order"
            @close="showPaymentModal = false"
        />

        <!-- Variation Selector Modal -->
        <div
            v-if="showVariationModal"
            class="fixed inset-0 z-[70] flex items-center justify-center p-2 bg-black/60"
        >
            <div
                class="bg-white w-full max-w-xs rounded-xl shadow-2xl overflow-hidden flex flex-col"
            >
                <!-- Modal Header -->
                <div
                    class="px-4 py-2 border-b border-gray-100 flex items-center justify-between"
                >
                    <h3 class="text-xs font-bold text-gray-900 uppercase">
                        Select Variation
                    </h3>
                    <button
                        @click="showVariationModal = false"
                        class="p-1 hover:bg-gray-100 rounded-full transition-colors"
                    >
                        <X class="w-4 h-4 text-gray-400" />
                    </button>
                </div>

                <!-- Variation Options -->
                <div class="p-3 space-y-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-lg border border-gray-100 overflow-hidden bg-white shrink-0"
                        >
                            <img
                                :src="activeProductForVariation?.feature_image"
                                alt=""
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-[11px] font-bold text-gray-900 line-clamp-1 leading-tight"
                            >
                                {{ activeProductForVariation?.name }}
                            </p>
                            <div
                                class="flex items-center gap-1 mt-0.5 text-emerald-600"
                            >
                                <span class="text-[10px] font-black"
                                    >৳{{
                                        selectedVariations[
                                            activeProductForVariation?.id
                                        ]?.price ||
                                        activeProductForVariation?.price
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div v-if="activeProductForVariation" class="space-y-3">
                        <div
                            v-for="(values, attrName) in getUniqueAttributes(
                                activeProductForVariation,
                            )"
                            :key="attrName"
                            class="flex flex-col gap-1"
                        >
                            <span
                                class="text-[9px] font-black text-gray-400 uppercase tracking-wider pl-1"
                                >{{ attrName }}</span
                            >
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="value in Array.from(values)"
                                    :key="value"
                                    @click="
                                        selectAttribute(
                                            activeProductForVariation,
                                            attrName,
                                            value,
                                        )
                                    "
                                    :class="[
                                        'px-3 py-1 rounded-md text-[10px] font-bold border transition-all uppercase',
                                        selectedAttributes[
                                            activeProductForVariation.id
                                        ]?.[attrName] === value
                                            ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                                            : 'bg-white text-slate-600 border-gray-200 hover:border-slate-400',
                                        isVariationOutOfStock(
                                            activeProductForVariation,
                                            attrName,
                                            value,
                                        )
                                            ? 'opacity-50 grayscale cursor-not-allowed'
                                            : '',
                                    ]"
                                    :disabled="
                                        isVariationOutOfStock(
                                            activeProductForVariation,
                                            attrName,
                                            value,
                                        )
                                    "
                                >
                                    {{ value }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Selector -->
                    <div
                        class="pt-2 border-t border-gray-100 flex items-center justify-between"
                    >
                        <span
                            class="text-[9px] font-black text-gray-400 uppercase tracking-wider pl-1"
                            >Quantity</span
                        >
                        <div
                            class="flex items-center bg-gray-50 border border-gray-100 rounded-lg p-0.5"
                        >
                            <button
                                @click="
                                    decrementQuantity(activeProductForVariation)
                                "
                                class="p-1 hover:bg-white rounded-md transition-all text-gray-500"
                            >
                                <ChevronLeft class="w-3.5 h-3.5" />
                            </button>
                            <span
                                class="text-xs font-black w-8 text-center text-slate-900"
                                >{{
                                    quantities[activeProductForVariation?.id] ||
                                    1
                                }}</span
                            >
                            <button
                                @click="
                                    incrementQuantity(activeProductForVariation)
                                "
                                class="p-1 hover:bg-white rounded-md transition-all text-gray-500"
                            >
                                <ChevronRight class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="
                            activeProductForVariation &&
                            selectedVariations[activeProductForVariation.id] &&
                            selectedVariations[activeProductForVariation.id]
                                .stock <= 0
                        "
                        class="text-[8px] font-black text-center text-rose-500 uppercase tracking-wider bg-rose-50 py-1 rounded-md border border-rose-100"
                    >
                        Stock Out
                    </div>
                </div>

                <!-- Add Button -->
                <div class="p-3 bg-gray-50 border-t border-gray-100">
                    <button
                        @click="
                            orderNew(activeProductForVariation);
                            showVariationModal = false;
                        "
                        :disabled="
                            !selectedVariations[
                                activeProductForVariation?.id
                            ] ||
                            selectedVariations[activeProductForVariation?.id]
                                ?.stock <= 0
                        "
                        class="w-full py-2.5 bg-slate-900 text-white rounded-lg font-black text-[11px] uppercase tracking-widest hover:bg-black disabled:bg-gray-200 transition-all shadow-sm active:scale-[0.98]"
                    >
                        Confirm Add
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
