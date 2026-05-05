<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import hierarchyData from "../../../../../public/jsondata/bangladesh_hierarchy.json";
import { useForm, usePage, Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { useCart } from "@/Composables/useCart";
import { useToast } from "@/Composables/useToast";
import axios from "axios";
import { trackCheckout, trackPurchase } from "@/Composables/gtm";
import { useMetaPixel } from "@/Composables/useMetaPixel";

const toast = useToast();

const props = defineProps({
    cart: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const cartSessionId = computed(() => page.props.cartSessionId || "");

// Use cart composable for cart operations
const { incrementQuantity, decrementQuantity, removeItem } = useCart();

// Track if incomplete order has already been sent
const incompleteOrderSent = ref(false);

// Helper to get cookie value
const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return "";
};

// Initialize form with Inertia useForm
const form = useForm({
    user_id: props.user?.id || null,
    session_id: "",
    customer_name: props.user?.name || "",
    customer_phone: props.user?.phone || "",
    shipping_address: props.user?.address || "",
    shipping_district: "",
    shipping_thana: "",
    shipping_cost: 0,
    area: "inside_dhaka",
    customer_notes: "",
    payment_method: "cod",
    payment_status: "unpaid",
    payment_phone: "",
    transaction_id: "",
    coupon_code: props.cart?.data?.coupon_code || null,
    discount_type: props.cart?.data?.coupon_type || null,
    discount_amount: props.cart?.data?.coupon_value || 0,
    items: [],
});

// Set session_id and items on mount
onMounted(() => {
    form.session_id = getCookie("cart_session_id") || cartSessionId.value;

    // Map cart items to order items format
    if (props.cart?.data?.items) {
        form.items = props.cart.data.items.map((item) => ({
            product_id: item.product_id,
            product_variation_id: item.variation_id || null,
            quantity: item.quantity,
            coupon_code: item.coupon_code || null,
            discount_type: item.discount_type || null,
            discount_amount: item.discount_amount || 0,
        }));
    }

    // Set initial shipping cost
    fetchShippingCost();
});

// Watch cart items for changes to keep form.items in sync
watch(
    () => props.cart?.data?.items,
    (newItems) => {
        if (newItems) {
            form.items = newItems.map((item) => ({
                product_id: item.product_id,
                product_variation_id: item.variation_id || null,
                quantity: item.quantity,
                coupon_code: item.coupon_code || null,
                discount_type: item.discount_type || null,
                discount_amount: item.discount_amount || 0,
            }));
        }
        // Update root coupon info
        form.coupon_code = props.cart?.data?.coupon_code || null;
        form.discount_type = props.cart?.data?.coupon_type || null;
        form.discount_amount = props.cart?.data?.coupon_value || 0;

        // Recompute shipping cost when cart items change (handles items with free delivery)
        fetchShippingCost();
    },
    { deep: true }
);

// Calculate subtotal from cart
const subtotal = computed(() => {
    if (!props.cart?.data?.items) return 0;
    return props.cart.data.items.reduce((sum, item) => {
        return sum + parseFloat(item.price) * item.quantity;
    }, 0);
});

// Check if any items are pre-orders
const hasPreOrderItems = computed(() => {
    if (!props.cart?.data?.items) return false;
    return props.cart.data.items.some(item => item.is_pre_order);
});

// Calculate total
const total = computed(() => {
    const discount = props.cart?.data?.discount || 0;
    return subtotal.value + form.shipping_cost - parseFloat(discount);
});

// Calculate suggested advance payment
const advancePaymentAmount = computed(() => {
    return total.value * 0.5;
});

// Highlight numbers in instructions
const highlightNumbers = (text) => {
    if (!text) return "";
    // Regex to match BD phone numbers (01xxxxxxxxx)
    return text.replace(/(01[3-9]\d{8})/g, '<strong class="text-[#f0512e] font-bold bg-white px-1.5 py-0.5 rounded shadow-sm border border-[#f0512e]/10">$1</strong>');
};

// Setup initial payment method based on pre-order status
watch(hasPreOrderItems, (isPreOrder) => {
    if (isPreOrder && form.payment_method === 'cod') {
        form.payment_method = 'bkash';
    } else if (!isPreOrder && (form.payment_method === 'bkash' || form.payment_method === 'nagad' || form.payment_method === 'online')) {
        form.payment_method = 'cod';
    }
}, { immediate: true });

// Coupon form
const couponForm = useForm({
    code: props.cart?.data?.coupon_code || "",
});

// Mobile-friendly coupon UX helper
const showCoupon = ref(false);
const focusCoupon = () => {
    showCoupon.value = true;
    nextTick(() => {
        const el = document.getElementById("coupon_code");
        if (el) {
            el.focus();
            el.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    });
};

// Keep coupon input in sync when cart changes
watch(
    () => props.cart?.data?.coupon_code,
    (newCode) => {
        couponForm.code = newCode || "";
    }
);

const applyCoupon = () => {
    if (!couponForm.code) {
        toast.warning("Please enter a coupon code");
        return;
    }

    couponForm.post(route("cart.apply-coupon"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                toast.success(page.props.flash.success);
                // blur and hide coupon input on success
                document.getElementById("coupon_code")?.blur();
                showCoupon.value = false;
            }
            if (page.props.flash?.error) {
                toast.error(page.props.flash.error);
            }
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to apply coupon");
        },
    });
};

// Fetch dynamic shipping cost from API
const fetchShippingCost = async () => {
    // If every item in the cart has is_free_delivery === true, shipping is 0
    const items = props.cart?.data?.items || [];
    const allFree = items.length > 0 && items.every((it) => !!it.is_free_delivery);
    if (allFree) {
        form.shipping_cost = 0;
        return;
    }

    if (!form.shipping_district) {
        form.shipping_cost = 0;
        return;
    }

    try {
        const response = await axios.get(route('order.delivery-charge.get'), {
            params: {
                district: form.shipping_district,
                thana: form.shipping_thana
            }
        });
        form.shipping_cost = response.data.charge;
    } catch (error) {
        console.error("Failed to fetch shipping cost", error);
    }
};

// Watch location changes for dynamic shipping cost
watch(
    [() => form.shipping_district, () => form.shipping_thana],
    () => {
        fetchShippingCost();
    }
);

// Validate Bangladesh phone number (11 digits starting with 01)
const isValidBDPhone = (phone) => {
    return /^01[3-9]\d{8}$/.test(phone);
};

// UI state for phone validation (client-side only)
const phoneTouched = ref(false);
const isPhoneValid = computed(() => isValidBDPhone(form.customer_phone));

// Watch phone number for incomplete order creation
watch(
    () => form.customer_phone,
    (newPhone) => {
        if (isValidBDPhone(newPhone) && !incompleteOrderSent.value) {
            // Construct incomplete order payload
            const incompletePayload = {
                user_id: form.user_id,
                session_id: form.session_id,
                customer_name: form.customer_name || "Guest",
                customer_phone: newPhone,
                shipping_address: form.shipping_address || "incomplete address",
                shipping_cost: form.shipping_cost || 0,
                area: form.area || "",
                customer_notes: form.customer_notes || "",
                payment_method: form.payment_method || "cod",
                payment_status: "unpaid",
                status: "incomplete",
                items: form.items,
            };

            // Send background request
            axios
                .post(route("order.store"), incompletePayload)
                .then((res) => {
                    console.log("Incomplete order tracked successfully");
                    incompleteOrderSent.value = true;
                })
                .catch((err) => {
                    console.error("Tracking failed", err);
                });
        }
    }
);

// Form submission handler
const submitOrder = () => {
    // Client-side phone validation only (UI)
    if (!isPhoneValid.value) {
        phoneTouched.value = true;
        // Optionally scroll to the phone input
        document.getElementById("customer_phone")?.focus();
        return;
    }

    if ((form.payment_method === 'bkash' || form.payment_method === 'nagad')) {
        if (!form.transaction_id) {
            toast.warning(`Please provide your Transaction ID for ${form.payment_method} payment`);
            return;
        }
    }

    form.post(route("order.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            console.log("Order submission successful", page.props.order);
            if (page.props?.order) {
                // Track purchase via GTM
                trackPurchase(page.props.order);
            }
            if (page.props.flash?.error) {
                console.error("Order error:", page.props.flash.error);
            }
        },
        onError: (errors) => {
            console.error("Order submission failed:", errors);
        },
    });
};

// Bangladesh Hierarchy Logic
const districts = ref([]);
const thanas = ref([]);

onMounted(() => {
    // Flatten all districts from all divisions
    if (hierarchyData) {
        districts.value = hierarchyData.flatMap(division => 
            division.districts.map(district => ({
                id: district.id,
                name: district.name,
                upazilas: district.upazilas
            }))
        ).sort((a, b) => a.name.localeCompare(b.name));
    }
});

watch(() => form.shipping_district, (newDistrictName) => {
    const selectedDistrict = districts.value.find(d => d.name === newDistrictName);
    if (selectedDistrict) {
        thanas.value = (selectedDistrict.upazilas || []).sort((a, b) => a.name.localeCompare(b.name));
        // Reset Thana if not in new list
        if (!thanas.value.find(t => t.name === form.shipping_thana)) {
            form.shipping_thana = "";
        }
    } else {
        thanas.value = [];
        form.shipping_thana = "";
    }

    // Auto-update area based on district (needed for courier integration)
    if (newDistrictName === 'Dhaka') {
        form.area = 'inside_dhaka';
    } else if (newDistrictName) {
        form.area = 'outside_dhaka';
    }
});



const { trackInitiateCheckout } = useMetaPixel();

onMounted(() => {
    trackCheckout(props.cart);
    // Track InitiateCheckout via Meta Pixel (fbq)
    trackInitiateCheckout(props.cart);
});



</script>

<template>
    <Head title="Checkout" />
    <FrontendLayout>
        <div class="min-h-screen bg-[#f2f4f7] py-8">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-1 font-outfit uppercase tracking-tight">Checkout</h1>
                    <nav class="flex justify-center items-center space-x-2 text-xs text-gray-500 font-normal">
                        <Link href="/" class="hover:text-orange-500 transition-colors">Home</Link>
                        <span class="text-gray-400">&gt;</span>
                        <span class="text-orange-500">Checkout</span>
                    </nav>
                </div>

                <!-- Empty Cart Message -->
                <div
                    v-if="!cart?.data?.items || cart.data.items.length === 0"
                    class="flex flex-col items-center justify-center py-16"
                >
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center max-w-md"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-24 w-24 text-gray-300 mb-4 mx-auto"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Your cart is empty
                        </h2>
                        <p class="text-gray-600 mb-6">
                            Add some products to your cart before checking out.
                        </p>
                        <Link
                            href="/"
                            class="inline-flex items-center px-6 py-3 bg-orange-500 text-white font-medium rounded-lg hover:bg-orange-600 transition-colors"
                        >
                            <svg
                                class="w-5 h-5 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            Continue Shopping
                        </Link>
                    </div>
                </div>

                <div v-else>
                    <!-- Login/Register Alert -->
                    <div v-if="!user" class="bg-gray-100/80 border border-gray-200 rounded-lg p-5 flex flex-wrap items-center justify-between gap-4 mb-8">
                        <span class="text-gray-700 font-normal tracking-tight">Have any account? please login or register</span>
                        <div class="flex gap-2">
                            <Link :href="route('login')" class="px-7 py-2 bg-white border border-gray-300 rounded text-sm font-medium hover:bg-gray-50 transition-all">Login</Link>
                            <Link :href="route('register')" class="px-7 py-2 bg-orange-500 text-white rounded text-sm font-medium hover:bg-orange-600 transition-all shadow-sm">Register</Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Left Column -->
                        <div class="lg:col-span-7 space-y-6">
                            <!-- Order review section -->
                            <section>
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-1 h-5 bg-orange-500 rounded-full"></div>
                                    <h2 class="text-lg font-medium text-gray-900 tracking-tight">Order review</h2>
                                </div>
                                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
                                    <div v-for="(item, index) in cart?.data?.items" :key="item.id" 
                                         class="p-4 flex items-center gap-4 relative"
                                         :class="{'border-b border-gray-100': index !== cart.data.items.length - 1}">
                                        <div class="w-16 h-16 border border-gray-200 rounded-xl overflow-hidden flex-shrink-0 bg-gray-50 p-1">
                                            <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="w-full h-full object-contain" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-[10px]">No Image</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-[14px] font-medium text-gray-900 mb-2">{{ item.product_name }}</h4>
                                            <div class="flex items-center gap-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-normal text-gray-600">Qty:</span>
                                                    <div class="flex items-center bg-[#f2f4f7] rounded-full p-0.5 select-none">
                                                        <button @click="decrementQuantity(item.id, item.quantity)" class="w-7 h-7 flex items-center justify-center text-red-400 hover:text-red-500 transition-colors text-lg font-medium">−</button>
                                                        <div class="bg-white w-8 h-7 flex items-center justify-center text-gray-900 font-semibold text-xs">
                                                            {{ item.quantity }}
                                                        </div>
                                                        <button @click="incrementQuantity(item.id)" class="w-7 h-7 flex items-center justify-center text-orange-500 hover:text-orange-600 transition-colors text-lg font-medium">+</button>
                                                    </div>
                                                </div>
                                                <span class="text-[14px] font-medium text-gray-900">৳{{ (parseFloat(item.price) * item.quantity).toLocaleString() }}.00</span>
                                            </div>
                                        </div>
                                        <button @click="removeItem(item.id)" class="bg-red-500/90 text-white p-1 rounded-md hover:bg-red-600 transition-all shadow-sm ml-auto flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <!-- Shipping Address section -->
                            <section>
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-1 h-5 bg-orange-500 rounded-full"></div>
                                    <h2 class="text-lg font-medium text-gray-900 tracking-tight">Shipping Address</h2>
                                </div>
                                <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="relative">
                                            <input v-model="form.customer_name" type="text" placeholder="Your Full Name *" 
                                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all placeholder:text-gray-400 font-normal text-gray-800 text-sm" />
                                        </div>
                                        <div class="flex items-center border border-gray-200 rounded-lg px-4 py-2.5 focus-within:ring-1 focus-within:ring-orange-500 focus-within:border-orange-500 bg-white transition-all">
                                            <span class="mr-2 text-gray-500 font-normal border-r pr-2 border-gray-200 text-sm">88</span>
                                            <input v-model="form.customer_phone" type="tel" placeholder="01*********" 
                                                   class="flex-1 bg-transparent border-none outline-none p-0 focus:ring-0 placeholder:text-gray-400 font-normal text-gray-800 text-sm" />
                                        </div>
                                        
                                        <div class="relative">
                                            <select v-model="form.shipping_district" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all bg-white text-gray-700 font-normal appearance-none text-sm cursor-pointer" style="appearance: none; -webkit-appearance: none;">
                                                <option value="" disabled>Select District</option>
                                                <option v-for="d in districts" :key="d.id" :value="d.name">{{ d.name }}</option>
                                            </select>
                                        </div>
                                        
                                        <div class="relative">
                                            <select v-model="form.shipping_thana" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all bg-white text-gray-700 font-normal appearance-none text-sm cursor-pointer" :disabled="!form.shipping_district" style="appearance: none; -webkit-appearance: none;">
                                                <option value="" disabled>Select Thana (Optional)</option>
                                                <option v-for="t in thanas" :key="t.id" :value="t.name">{{ t.name }}</option>
                                            </select>
                                        </div>

                                        <div class="md:col-span-2">
                                            <textarea v-model="form.shipping_address" rows="2" placeholder="ex: House no. / building / street / area" 
                                                      class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all placeholder:text-gray-400 font-medium text-gray-800 resize-none text-sm"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- Right Column -->
                        <div class="lg:col-span-5 space-y-6">
                            <!-- Payment method section -->
                            <section>
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-1 h-5 bg-orange-500 rounded-full"></div>
                                    <h2 class="text-lg font-medium text-gray-900 tracking-tight">Payment method</h2>
                                </div>
                                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
                                    <div class="grid gap-3 mb-3" :class="hasPreOrderItems ? 'grid-cols-2' : 'grid-cols-1'">
                                        <button type="button" @click="form.payment_method = 'cod'" 
                                                class="flex items-center gap-3 p-3.5 border-2 rounded-xl transition-all relative overflow-hidden"
                                                :class="form.payment_method === 'cod' ? 'border-orange-500 bg-orange-50/50' : 'border-gray-100 hover:border-gray-200 bg-gray-50/30'">
                                            <div class="w-8 h-8 flex items-center justify-center text-xl">💵</div>
                                            <span class="text-sm font-medium text-gray-800">Cash On Delivery</span>
                                            <div v-if="form.payment_method === 'cod'" class="absolute -top-1 -right-1 flex items-center justify-center w-8 h-8">
                                                <div class="bg-orange-500 w-full h-full rotate-45 translate-x-4 -translate-y-4"></div>
                                                <svg class="w-3 h-3 text-white absolute top-1.5 right-1.5 z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" /></svg>
                                            </div>
                                        </button>
                                        <button v-if="hasPreOrderItems" type="button" @click="form.payment_method = 'nagad'" 
                                                class="flex items-center gap-3 p-3.5 border-2 rounded-xl transition-all relative overflow-hidden"
                                                :class="form.payment_method === 'nagad' ? 'border-orange-500 bg-orange-50/50' : 'border-gray-100 hover:border-gray-200 bg-gray-50/30'">
                                            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center overflow-hidden">
                                                <img src="/assets/img/logo/nogod.png" class="w-full h-full object-contain p-0.5" alt="nagad" />
                                            </div>
                                            <span class="text-sm font-medium text-gray-800">Nagad</span>
                                            <div v-if="form.payment_method === 'nagad'" class="absolute -top-1 -right-1 flex items-center justify-center w-8 h-8">
                                                <div class="bg-orange-500 w-full h-full rotate-45 translate-x-4 -translate-y-4"></div>
                                                <svg class="w-3 h-3 text-white absolute top-1.5 right-1.5 z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" /></svg>
                                            </div>
                                        </button>
                                    </div>
                                    <button v-if="hasPreOrderItems" type="button" @click="form.payment_method = 'bkash'" 
                                            class="w-full flex items-center gap-4 p-3.5 border-2 rounded-xl transition-all relative overflow-hidden"
                                            :class="form.payment_method === 'bkash' ? 'border-orange-500 bg-orange-50/50' : 'border-gray-100 hover:border-gray-200 bg-gray-50/30'">
                                        <div class="w-10 h-10 rounded-lg p-1 bg-white flex items-center justify-center overflow-hidden">
                                            <img src="/assets/img/logo/BKash-Icon-Logo.wine.svg" class="w-full h-full object-contain" alt="bkash" />
                                        </div>
                                        <span class="text-base font-medium text-gray-800">Bkash</span>
                                        <div v-if="form.payment_method === 'bkash'" class="absolute -top-1 -right-1 flex items-center justify-center w-8 h-8">
                                            <div class="bg-orange-500 w-full h-full rotate-45 translate-x-4 -translate-y-4"></div>
                                            <svg class="w-3 h-3 text-white absolute top-1.5 right-1.5 z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                    </button>

                                    <!-- Manual Payment Instructions -->
                                    <div v-if="form.payment_method === 'bkash' || form.payment_method === 'nagad'" class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-xl animate-in fade-in slide-in-from-top-2 duration-300">
                                        <h3 class="text-sm font-bold text-blue-900 mb-2 uppercase tracking-tight">Instructions:</h3>
                                        <p class="text-[13px] text-blue-800 mb-4 whitespace-pre-line leading-relaxed" v-html="highlightNumbers(form.payment_method === 'bkash' ? (settings.generalSettings?.bkash_instructions || 'Please send the amount to our official bKash number. Then provide your Transaction ID below.') : (settings.generalSettings?.nagad_instructions || 'Please send the amount to our official Nagad number. Then provide your Transaction ID below.'))"></p>
                                        
                                        <div class="space-y-3 pt-3 border-t border-blue-200/50">
                                            <div>
                                                <label class="block text-[10px] font-bold text-blue-900 uppercase tracking-wider mb-1">Transaction ID (TrxID) <span class="text-red-500">*</span></label>
                                                <input type="text" v-model="form.transaction_id" placeholder="Ex: 8NH7YH9L" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-1 focus:ring-blue-500 outline-none text-sm font-medium" required />
                                                <p v-if="form.errors.transaction_id" class="mt-1 text-[10px] text-red-500 font-bold tracking-tight">{{ form.errors.transaction_id }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)] space-y-4">
                                <!-- Coupon Accordion -->
                                <div>
                                    <button @click="showCoupon = !showCoupon" class="w-full flex items-center justify-between text-sm font-medium text-gray-800 group py-1">
                                        <span>Have any coupon or gift voucher?</span>
                                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="{'rotate-180': showCoupon}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <div v-show="showCoupon" class="mt-3 flex gap-2 animate-in fade-in slide-in-from-top-2 duration-200">
                                        <input v-model="couponForm.code" type="text" placeholder="Enter code" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-orange-500 outline-none font-normal text-sm" />
                                        <button @click="applyCoupon" class="px-5 py-2 bg-gray-900 text-white rounded-lg font-medium hover:bg-black transition-all text-sm">Apply</button>
                                    </div>
                                </div>

                                <!-- Summary -->
                                <div class="space-y-3 pt-4 border-t border-gray-100">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-normal text-gray-600">Sub total</span>
                                        <span class="text-sm font-normal text-gray-900">{{ subtotal.toLocaleString() }}.00 BDT</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-normal text-gray-600">Delivery cost</span>
                                        <span class="text-sm font-normal text-gray-900">{{ form.shipping_cost }}.00 BDT</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                        <span class="text-base font-semibold text-gray-900">Total</span>
                                        <span class="text-base font-semibold text-gray-900">{{ total.toLocaleString() }}.00BDT</span>
                                    </div>

                                    <!-- Pre-Order Notice -->
                                    <div v-if="hasPreOrderItems" class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-xl space-y-3 animate-in fade-in slide-in-from-top-2 duration-300">
                                        <div class="flex items-center gap-2 text-blue-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span class="text-sm font-bold uppercase tracking-tight">Pre-Order Notice</span>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center text-[13px] font-medium text-blue-800">
                                                <span>Suggested Advance (50%)</span>
                                                <span class="font-bold">৳{{ (total / 2).toLocaleString() }}.00</span>
                                            </div>
                                            <div class="flex justify-between items-center text-[13px] font-medium text-blue-800">
                                                <span>Due on Delivery</span>
                                                <span class="font-bold">৳{{ (total / 2).toLocaleString() }}.00</span>
                                            </div>
                                        </div>
                                        <p class="text-[11px] leading-relaxed text-blue-700 pt-2 border-t border-blue-200/50">
                                            You can optionally send the suggested advance via bKash/Nagad to confirm your pre-order quickly, or simply place via Cash on Delivery.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Special notes section -->
                            <section>
                                <div class="flex items-center space-x-3 mb-3 mt-1">
                                    <div class="w-1 h-5 bg-orange-500 rounded-full"></div>
                                    <h2 class="text-sm font-bold text-gray-900 tracking-tight">Special notes <span class="text-gray-400 font-semibold">(Optional)</span></h2>
                                </div>
                                <textarea v-model="form.customer_notes" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-orange-500 outline-none transition-all resize-none font-medium bg-white text-sm"></textarea>
                            </section>

                            <!-- Terms and Submit -->
                            <div class="space-y-6">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" required class="mt-1 w-5 h-5 accent-orange-500 rounded border-gray-300 text-orange-500 focus:ring-orange-500" checked />
                                    <span class="text-xs text-gray-500 leading-relaxed font-medium">
                                        I have read and agree to the <span class="text-orange-500 font-bold hover:underline">Terms and Conditions</span>, <span class="text-orange-500 font-bold hover:underline">Privacy Policy</span> & <span class="text-orange-500 font-bold hover:underline">Refund and Return Policy</span>.
                                    </span>
                                </label>

                                <button @click="submitOrder" :disabled="form.processing"
                                        class="w-full bg-orange-500 text-white font-medium py-3.5 rounded-lg shadow-[0_10px_20px_rgba(249,115,22,0.15)] hover:bg-orange-600 transition-all transform hover:scale-[1.01] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-[0.1em] text-sm">
                                    {{ form.processing ? 'Processing...' : 'PLACE ORDER' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating coupon action on mobile (Custom style) -->
        <div v-if="cart?.data?.items?.length > 0" class="fixed bottom-6 right-6 lg:hidden z-50">
            <button @click="focusCoupon" class="w-14 h-14 bg-orange-500 text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-transform active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
            </button>
        </div>
    </FrontendLayout>
</template>
