<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import GlobalOfferProgress from '@/Components/Frontend/GlobalOfferProgress.vue';
import { Link, router } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';
import { useCartSidebar } from '@/Composables/useCartSidebar';
import ProductQuickView from "@/Components/Frontend/Product/ProductQuickView.vue";
import { Plus, Minus, Trash2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

// Use centralized cart sidebar composable
const { isCartSidebarOpen, closeCart } = useCartSidebar();

// Sync props.isOpen with global state (if needed for parent-child flow)
watch(() => props.isOpen, (newVal) => {
    isCartSidebarOpen.value = newVal;
});

// Use centralized cart composable
const { 
    cart, 
    cartItems, 
    cartTotal, 
    cartCount, 
    isLoading, 
    incrementQuantity, 
    decrementQuantity, 
    removeItem 
} = useCart();

const recommendedProducts = ref([]);
const quickViewProduct = ref(null);
const showQuickView = ref(false);

const fetchRecommendations = async () => {
    try {
        const response = await axios.get('/api/products/recommended');
        recommendedProducts.value = response.data;
    } catch (e) {
        console.error("Failed to fetch recommendations", e);
    }
};

onMounted(() => {
    fetchRecommendations();
});

const openQuickView = (product) => {
    quickViewProduct.value = product;
    showQuickView.value = true;
};

const getLowestPrice = (product) => {
    if (product.type === 'variable' && product.variations && product.variations.length > 0) {
        const prices = product.variations.map(v => parseFloat(v.price));
        return Math.min(...prices);
    }
    return parseFloat(product.price);
};

// Refresh scheduled/admin-edited rewards while the cart is visible.
let refreshTimer;
let rewardBoundaryTimer;
const refreshRewards = () => {
    if (isCartSidebarOpen.value && !isLoading.value) {
        router.reload({ only: ['cart', 'cartCount', 'cartTotal'], preserveScroll: true });
    }
};
watch(() => cart.value?.pricing?.next_refresh_at, (boundary) => {
    if (typeof window === 'undefined') return;
    clearTimeout(rewardBoundaryTimer);
    const delay = boundary ? new Date(boundary).getTime() - Date.now() + 300 : 0;
    if (delay > 0 && delay < 2147483647) rewardBoundaryTimer = setTimeout(refreshRewards, delay);
}, { immediate: true });
onMounted(() => {
    window.addEventListener('focus', refreshRewards);
    refreshTimer = setInterval(refreshRewards, 60000);
});
onBeforeUnmount(() => {
    window.removeEventListener('focus', refreshRewards);
    clearInterval(refreshTimer);
    clearTimeout(rewardBoundaryTimer);
});

// Handle body scroll when sidebar opens/closes
watch(isCartSidebarOpen, (newVal) => {
    if (newVal) {
        refreshRewards();
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const handleOverlayClick = () => {
    closeCart();
    emit('close');
};

const handleClose = () => {
    closeCart();
    emit('close');
};
</script>

<template>
    <!-- Overlay -->
    <Transition name="fade">
        <div 
            v-if="isCartSidebarOpen" 
            class="fixed inset-0 bg-black/50 z-50"
            @click="handleOverlayClick"
        ></div>
    </Transition>

    <!-- Sidebar -->
    <Transition name="slide-left">
        <div 
            v-if="isCartSidebarOpen" 
            class="fixed top-0 right-0 h-full w-[450px] max-w-[100vw] bg-white dark:bg-gray-900 z-50 shadow-2xl flex flex-col overflow-hidden font-inter border-l border-gray-100 dark:border-gray-800"
        >
            <!-- [COMMENTED OUT] Left Side: YOU MAY ALSO LIKE -->
            <!-- 
            <div class="hidden lg:flex flex-col w-[320px] border-r border-gray-100 bg-[#FBFBFB]" >
                <div class="p-8 border-b border-gray-100 bg-white">
                    <h3 class="text-[11px] font-medium uppercase tracking-[0.2em] text-gray-400">Complete the Look</h3>
                    <p class="text-sm font-medium text-gray-900 mt-1">Recommended for you</p>
                </div>
                ... items ...
            </div>
            -->

            <!-- Main Cart Content -->
            <div class="flex-1 flex flex-col h-full bg-white dark:bg-gray-900">
                <!-- <pre>{{ cart }}</pre> -->
                <!-- Header -->
                <div class="px-6 py-6 flex items-center justify-between border-b border-gray-50 dark:border-gray-800">
                    <div>
                        <h2 class="text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-800 dark:text-gray-100 italic">
                            Shopping Cart 
                        </h2>
                        <p v-if="cartCount > 0" class="text-[9px] font-medium text-gray-400 mt-1 uppercase tracking-widest">{{ cartCount }} Items</p>
                    </div>
                    <button 
                        @click="handleClose"
                        class="group p-2 flex items-center justify-center text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all"
                    >
                        <div class="w-9 h-9 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center group-hover:rotate-90 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center gap-4 bg-gray-50/30 dark:bg-gray-900/30">
                    <div class="w-10 h-10 border-2 border-gray-100 dark:border-gray-800 border-t-blue-500 rounded-full animate-spin"></div>
                    <span class="text-[10px] font-medium uppercase tracking-widest text-gray-400">Processing...</span>
                </div>

                <!-- Empty Cart -->
                <div v-else-if="!cart || cartItems.length === 0" class="flex-1 flex flex-col items-center justify-center px-10 py-12 text-center bg-gray-50/20 dark:bg-gray-900/20">
                    <div class="w-20 h-20 bg-white dark:bg-gray-800 rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-gray-50 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-800 dark:text-gray-100 mb-2 italic">Your cart is empty</h3>
                    <p class="text-[10px] text-gray-400 mb-8 max-w-[200px] leading-relaxed mx-auto uppercase tracking-wide">Technology moves fast. Don't fall behind.</p>
                    <Link 
                        href="/" 
                        @click="handleClose"
                        class="px-8 py-4 bg-gray-900 dark:bg-blue-600 text-white text-[10px] font-bold uppercase tracking-[0.15em] hover:bg-black dark:hover:bg-blue-500 transition-all rounded-full shadow-lg active:scale-95"
                    >
                        Explore Gadgets
                    </Link>
                </div>

                <!-- Cart Items -->
                <div v-else class="flex-1 overflow-y-auto px-6 py-6 space-y-6 scrollbar-hide">
                    <div 
                        v-for="item in cartItems" 
                        :key="item.id"
                        class="flex gap-5 items-center p-3 rounded-2xl bg-white dark:bg-gray-800/50 border border-gray-50 dark:border-gray-800 hover:border-gray-200 dark:hover:border-gray-600 transition-all group"
                    >
                        <!-- Product Image -->
                        <div class="w-20 h-20 bg-gray-50 dark:bg-gray-900 rounded-xl overflow-hidden flex-shrink-0 relative">
                            <img 
                                :src="item.product_image" 
                                :alt="item.product_name"
                                class="w-full h-full object-contain p-2 transition-transform duration-500 group-hover:scale-110"
                            />
                        </div>
                        
                        <!-- Product Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <h3 class="text-[11px] font-medium text-gray-800 dark:text-gray-200 line-clamp-1 leading-tight group-hover:text-blue-500 transition-colors">
                                    {{ item.product_name }}
                                </h3>
                                <button 
                                    @click="removeItem(item.id)"
                                    class="text-gray-300 hover:text-rose-500 transition-colors p-1"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </button>
                            </div>
                            
                            <!-- Variation Attributes -->
                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1 text-[9px] text-gray-400 font-medium uppercase tracking-wider">
                                <template v-if="item.variation_attributes && item.variation_attributes.length > 0">
                                    <div v-for="(attr, idx) in item.variation_attributes" :key="idx">
                                        {{ attr.name }}: <span class="text-gray-600 dark:text-gray-300">{{ attr.value }}</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Price and Qty Row -->
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center bg-gray-100 dark:bg-gray-900 rounded-lg px-2 py-1 gap-3">
                                    <button 
                                        @click="decrementQuantity(item.id, item.quantity)"
                                        class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                    >
                                        <Minus class="w-2.5 h-2.5" />
                                    </button>
                                    <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300 w-4 text-center">{{ item.quantity }}</span>
                                    <button 
                                        @click="incrementQuantity(item.id)"
                                        class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                    >
                                        <Plus class="w-2.5 h-2.5" />
                                    </button>
                                </div>
                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100 tracking-tight italic">৳{{ parseFloat(item.price).toFixed(0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="cart && cartItems.length > 0" class="border-t border-gray-50 dark:border-gray-800 p-6 bg-white dark:bg-gray-900 space-y-5">
                    <GlobalOfferProgress :pricing="cart.pricing" :loading="isLoading" />
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-[11px] font-medium text-gray-400 uppercase tracking-widest">
                            <span>Subtotal</span>
                            <span class="text-gray-900 dark:text-white font-bold italic text-base">৳{{ Number(cartTotal).toLocaleString('en-BD', { maximumFractionDigits: 0 }) }}</span>
                        </div>
                        <div v-if="cart.pricing?.monetary_discount > 0" class="flex justify-between text-sm text-green-700 dark:text-green-400"><span>{{ cart.pricing.discount_source === 'coupon' ? 'Coupon discount' : 'Automatic discount' }}</span><span>−৳{{ Number(cart.pricing.monetary_discount).toLocaleString('en-BD', { maximumFractionDigits: 0 }) }}</span></div>
                        <div v-if="cart.pricing?.monetary_discount > 0" class="flex justify-between text-sm font-semibold dark:text-white"><span>After discount</span><span>৳{{ Number(cart.pricing.merchandise_payable).toLocaleString('en-BD', { maximumFractionDigits: 0 }) }}</span></div>
                        <p class="text-xs text-gray-500">{{ cart.pricing?.shipping_payable === 0 ? 'Free delivery' : 'Delivery calculated at checkout' }}</p>
                    </div>
                    
                    <div class="space-y-3">
                        <Link 
                            :href="route('order.create')" 
                            @click="handleClose"
                            class="group w-full py-4 bg-gray-900 dark:bg-blue-600 text-white text-[10px] font-bold uppercase tracking-[0.2em] hover:bg-black dark:hover:bg-blue-500 transition-all rounded-full flex items-center justify-center gap-3 shadow-xl hover:shadow-2xl active:scale-[0.98]"
                        >
                            <span>Checkout</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:translate-x-1.5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        
                        <div class="flex items-center justify-center gap-6">
                            <Link 
                                href="/cart" 
                                @click="handleClose"
                                class="text-[9px] font-semibold uppercase tracking-widest text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all"
                            >
                                Cart Details
                            </Link>
                            <span class="w-1 h-1 bg-gray-200 dark:bg-gray-700 rounded-full"></span>
                            <button 
                                @click="handleClose"
                                class="text-[9px] font-semibold uppercase tracking-widest text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all"
                            >
                                Shop More
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Product Quick View -->
    <ProductQuickView
        :show="showQuickView"
        :product="quickViewProduct"
        @close="showQuickView = false"
    />
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(100%);
}
</style>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(100%);
}
</style>
