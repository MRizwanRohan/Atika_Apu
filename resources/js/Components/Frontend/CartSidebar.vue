<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
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

// Handle body scroll when sidebar opens/closes
watch(isCartSidebarOpen, (newVal) => {
    if (newVal) {
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
            class="fixed top-0 right-0 h-full w-[850px] max-w-[95vw] bg-white z-50 shadow-2xl flex overflow-hidden font-inter"
        >
            <!-- Left Side: YOU MAY ALSO LIKE (Curated Recommendations) -->
            <div class="hidden lg:flex flex-col w-[320px] border-r border-gray-100 bg-[#FBFBFB]" >
                <div class="p-8 border-b border-gray-100 bg-white">
                    <h3 class="text-[11px] font-medium uppercase tracking-[0.2em] text-gray-400">Complete the Look</h3>
                    <p class="text-sm font-medium text-gray-900 mt-1">Recommended for you</p>
                </div>
                <div class="flex-1 overflow-y-auto p-8 scrollbar-hide">
                    <div class="space-y-12">
                        <div 
                            v-for="product in recommendedProducts" 
                            :key="product.id"
                            class="flex flex-col group cursor-pointer"
                        >
                            <div class="relative aspect-[3/4] bg-white rounded-sm overflow-hidden mb-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-50">
                                <img 
                                    :src="product.feature_image" 
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000 ease-out"
                                />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-500"></div>
                                <button 
                                    @click="openQuickView(product)"
                                    class="absolute bottom-4 left-4 right-4 py-3 bg-white/90 backdrop-blur-md text-[10px] font-bold uppercase tracking-widest text-black opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300"
                                >
                                    Quick Add
                                </button>
                            </div>
                            <Link 
                                :href="route('product.details', product.slug)"
                                class="text-xs font-medium text-gray-900 group-hover:text-primary transition-colors line-clamp-1"
                            >
                                {{ product.name }}
                            </Link>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-[11px] font-medium text-gray-500">৳{{ getLowestPrice(product).toFixed(0) }}</span>
                                <span class="text-[9px] font-normal text-gray-300 uppercase tracking-tighter">New Arrival</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Shopping Cart -->
            <div class="flex-1 flex flex-col h-full bg-white">
                <!-- Header -->
                <div class="px-8 py-7 flex items-center justify-between border-b border-gray-50">
                    <div>
                        <h2 class="text-xs font-medium uppercase tracking-[0.2em] text-gray-900">
                            Your Bag 
                        </h2>
                        <p v-if="cartCount > 0" class="text-[10px] font-medium text-gray-400 mt-1 uppercase">{{ cartCount }} Items Selected</p>
                    </div>
                    <button 
                        @click="handleClose"
                        class="group p-2 -mr-2 flex items-center gap-2 text-gray-400 hover:text-black transition-colors"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all">Close</span>
                        <div class="w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center group-hover:border-black transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center gap-4">
                    <div class="relative w-12 h-12">
                        <div class="absolute inset-0 border-2 border-gray-100 rounded-full"></div>
                        <div class="absolute inset-0 border-2 border-black border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Updating Bag...</span>
                </div>

                <!-- Empty Cart -->
                <div v-else-if="!cart || cartItems.length === 0" class="flex-1 flex flex-col items-center justify-center p-12 text-center">
                    <div class="w-24 h-24 bg-[#F9F9F9] rounded-full flex items-center justify-center mb-8 relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-white border border-gray-100 rounded-full flex items-center justify-center shadow-sm">
                            <span class="text-xs font-bold text-gray-300">0</span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium uppercase tracking-[0.2em] text-gray-900 mb-3">Your Bag is Empty</h3>
                    <p class="text-xs text-gray-400 mb-10 max-w-[240px] leading-relaxed mx-auto">Discover our latest collections and find pieces that resonate with your style.</p>
                    <Link 
                        href="/" 
                        @click="handleClose"
                        class="group flex items-center gap-3 px-10 py-5 bg-black text-white text-[10px] font-medium uppercase tracking-[0.2em] hover:bg-gray-900 transition-all rounded-sm shadow-xl hover:shadow-2xl active:scale-95"
                    >
                        <span>Start Shopping</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </Link>
                </div>

                <!-- Cart Items -->
                <div v-else class="flex-1 overflow-y-auto px-8 py-8 space-y-10 scrollbar-hide">
                    <div 
                        v-for="item in cartItems" 
                        :key="item.id"
                        class="flex gap-8 relative group items-start"
                    >
                        <!-- Product Image -->
                        <div class="w-36 aspect-[3/4] bg-[#F9F9F9] rounded-sm overflow-hidden flex-shrink-0 relative group/img cursor-pointer">
                            <img 
                                :src="item.product_image" 
                                :alt="item.product_name"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover/img:scale-110"
                            />
                            <div class="absolute inset-0 bg-black/0 group-hover/img:bg-black/5 transition-colors"></div>
                        </div>
                        
                        <!-- Product Details -->
                        <div class="flex-1 min-w-0 pt-1">
                            <div class="flex justify-between items-start gap-4 mb-2">
                                <h3 class="text-[11px] font-medium uppercase tracking-wider text-gray-900 leading-relaxed max-w-[200px]">
                                    {{ item.product_name }}
                                </h3>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs font-medium text-gray-900 tracking-tight">৳{{ parseFloat(item.price).toFixed(0) }}</p>
                                </div>
                            </div>
                            
                            <!-- Variation Attributes & Meta -->
                            <div class="flex flex-wrap items-center gap-y-2 gap-x-4 mb-6">
                                <template v-if="item.variation_attributes && item.variation_attributes.length > 0">
                                    <div 
                                        v-for="(attr, idx) in item.variation_attributes" 
                                        :key="idx"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span class="text-[9px] text-gray-400 font-normal uppercase tracking-widest">{{ attr.name }}:</span>
                                        <span class="text-[10px] text-gray-900 font-medium">{{ attr.value }}</span>
                                    </div>
                                    <div class="w-px h-2.5 bg-gray-200"></div>
                                </template>
                                
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] text-gray-400 font-normal uppercase tracking-widest">Total:</span>
                                    <span class="text-[10px] text-gray-900 font-medium">৳{{ item.subtotal }}</span>
                                </div>
                            </div>

                            <!-- Bottom Row: Qty & Remove -->
                            <div class="flex items-center justify-between">
                                <!-- Modern Quantity Picker -->
                                <div class="flex items-center bg-gray-50 rounded-sm p-0.5 border border-gray-100">
                                    <button 
                                        @click="decrementQuantity(item.id, item.quantity)"
                                        class="w-8 h-8 flex items-center justify-center hover:bg-white hover:shadow-sm rounded-sm transition-all text-gray-400 hover:text-black active:scale-95"
                                    >
                                        <Minus class="w-3 h-3" />
                                    </button>
                                    <span class="w-10 text-center text-[11px] font-medium text-gray-900">{{ item.quantity }}</span>
                                    <button 
                                        @click="incrementQuantity(item.id)"
                                        class="w-8 h-8 flex items-center justify-center hover:bg-white hover:shadow-sm rounded-sm transition-all text-gray-400 hover:text-black active:scale-95"
                                    >
                                        <Plus class="w-3 h-3" />
                                    </button>
                                </div>

                                <button 
                                    @click="removeItem(item.id)"
                                    class="group flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-widest text-gray-500 hover:text-red-600 transition-colors"
                                >
                                    <Trash2 class="h-3.5 w-3.5 group-hover:scale-110 transition-transform" />
                                    <span>Remove</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="cart && cartItems.length > 0" class="border-t border-gray-100 p-8 bg-white space-y-6">
                    <!-- Price Breakdown -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] font-medium uppercase tracking-[0.2em] text-gray-900">Subtotal</span>
                            <span class="text-lg font-medium text-gray-900 tracking-tighter italic">৳{{ cartTotal }}</span>
                        </div>
                        <p class="text-[9px] text-gray-400 text-center uppercase tracking-widest leading-loose">
                            Shipping and taxes calculated at checkout.<br>
                            International duties may apply.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <Link 
                            :href="route('order.create')" 
                            @click="handleClose"
                            class="group relative flex items-center justify-center w-full py-6 bg-black text-white text-[11px] font-medium uppercase tracking-[0.2em] hover:bg-[#111] transition-all rounded-sm overflow-hidden shadow-2xl active:scale-[0.98]"
                        >
                            <span class="relative z-10 mr-4">Proceed to Secure Checkout</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 relative z-10 group-hover:translate-x-2 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/5 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        </Link>
                        
                        <div class="flex items-center justify-center gap-8">
                            <Link 
                                href="/cart" 
                                @click="handleClose"
                                class="text-[9px] font-medium uppercase tracking-widest text-gray-400 hover:text-black border-b border-white hover:border-black transition-all pb-1"
                            >
                                View Detailed Cart
                            </Link>
                            <button 
                                @click="handleClose"
                                class="text-[9px] font-medium uppercase tracking-widest text-gray-400 hover:text-black border-b border-white hover:border-black transition-all pb-1"
                            >
                                Continue Shopping
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
