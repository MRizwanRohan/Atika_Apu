<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';
import { useCartSidebar } from '@/Composables/useCartSidebar';
import ProductQuickView from "@/Components/Frontend/Product/ProductQuickView.vue";
import { Plus, Minus } from 'lucide-vue-next';
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
            class="fixed top-0 right-0 h-full w-[850px] max-w-[95vw] bg-white z-50 shadow-xl flex overflow-hidden"
        >
            <!-- <pre>{{ recommendedProducts }}</pre> -->
            <!-- Left Side: YOU MAY ALSO LIKE -->
            <div class="hidden md:flex flex-col w-[300px] border-r border-gray-100 bg-[#F9F9F9]" >
                <div class="p-6 border-b border-gray-100 bg-white">
                    <h3 class="text-xs font-black uppercase font-heebo text-gray-900">You May Also Like</h3>
                </div>
                <div class="flex-1 overflow-y-auto p-6 scrollbar-hide">
                    <div class="space-y-10">
                        <div 
                            v-for="product in recommendedProducts" 
                            :key="product.id"
                            class="flex flex-col items-center text-center group"
                        >
                            <div class="relative w-full aspect-[4/5] bg-white rounded-lg overflow-hidden mb-4 shadow-sm border border-gray-50">
                                <img 
                                    :src="product.feature_image" 
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                />
                                <div class="absolute inset-x-0 bottom-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform bg-white/90 backdrop-blur-sm">
                                    <button 
                                        @click="openQuickView(product)"
                                        class="text-[10px] font-bold uppercase font-heebo text-black hover:underline"
                                    >
                                        Quick View
                                    </button>
                                </div>
                            </div>
                            <Link 
                                :href="route('product.details', product.slug)"
                                class="text-sm font-heebo text-black line-clamp-1 mb-1 hover:text-primary transition-colors px-2"
                            >
                                {{ product.name }}
                            </Link>
                            <span class="text-xs font-bold text-gray-500">৳{{ getLowestPrice(product).toFixed(0) }}</span>
                            <!-- <button 
                                @click="openQuickView(product)" 
                                class="mt-2 text-[9px] font-bold uppercase font-heebo text-gray-400 border-b border-gray-200 pb-0.5 hover:text-black hover:border-black transition-all"
                            >
                                Quick View
                            </button> -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Shopping Cart -->
            <div class="flex-1 flex flex-col h-full bg-white">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <h2 class="text-xs font-black uppercase font-heebo text-gray-900">
                        Cart 
                        <span v-if="cartCount > 0" class="text-[10px] font-normal text-gray-400 ml-2">({{ cartCount }} items)</span>
                    </h2>
                    <button 
                        @click="handleClose"
                        class="p-1 hover:rotate-90 transition-transform duration-300"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex-1 flex items-center justify-center">
                    <div class="h-8 w-8 border-2 border-black border-t-transparent rounded-full animate-spin"></div>
                </div>

                <!-- Empty Cart -->
                <div v-else-if="!cart || cartItems.length === 0" class="flex-1 flex flex-col items-center justify-center p-12 text-center bg-[#FDFDFD]">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black uppercase font-heebo text-gray-900 mb-2">Cart is empty</h3>
                    <p class="text-xs text-gray-400 mb-8 max-w-[200px] leading-relaxed italic">Explore our collections and add something beautiful to your cart.</p>
                    <Link 
                        href="/" 
                        @click="handleClose"
                        class="px-8 py-4 bg-black text-white text-[10px] font-black uppercase font-heebo hover:bg-black/90 transition-all rounded"
                    >
                        Continue Shopping
                    </Link>
                </div>

                <!-- Cart Items -->
                <div v-else class="flex-1 overflow-y-auto p-6 space-y-8 scrollbar-hide">
                    <div 
                        v-for="item in cartItems" 
                        :key="item.id"
                        class="flex gap-6 relative group"
                    >
                        <!-- Product Image -->
                        <div class="w-32 aspect-[4/5] bg-gray-50 rounded overflow-hidden flex-shrink-0">
                            <img 
                                :src="item.product_image" 
                                :alt="item.product_name"
                                class="w-full h-full object-cover transition-transform group-hover:scale-105 duration-500"
                            />
                        </div>
                        
                        <!-- Product Details -->
                        <div class="flex-1 min-w-0 flex flex-col">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <h3 class="text-xs leading-tight">
                                    {{ item.product_name }}
                                </h3>
                                <p class="text-[11px] font-bold text-gray-800">৳{{ parseFloat(item.price).toFixed(0) }}</p>
                            </div>
                            
                            <!-- Variation Attributes -->
                            <div v-if="item.variation_attributes" class="text-[10px] text-gray-600 mb-3 space-y-1">
                                <span 
                                    v-for="(attr, idx) in item.variation_attributes" 
                                    :key="idx"
                                    class="inline-block mr-3 italic"
                                >
                                    {{ attr.name }}: {{ attr.value }}
                                </span>
                            </div>

                            <!-- Quantity Controls -->
                            <div class="mt-auto flex items-center justify-between">
                                <div class="flex items-center border border-gray-100 rounded p-1">
                                    <button 
                                        @click="decrementQuantity(item.id, item.quantity)"
                                        class="w-6 h-6 flex items-center justify-center hover:bg-gray-50 transition-colors"
                                    >
                                        <Minus class="w-3 h-3 text-gray-400" />
                                    </button>
                                    <span class="w-8 text-center text-[11px] font-bold">{{ item.quantity }}</span>
                                    <button 
                                        @click="incrementQuantity(item.id)"
                                        class="w-6 h-6 flex items-center justify-center hover:bg-gray-50 transition-colors"
                                    >
                                        <Plus class="w-3 h-3 text-gray-400" />
                                    </button>
                                </div>
                                <button 
                                    @click="removeItem(item.id)"
                                    class="text-red-600 hover:text-red-500 transition-colors p-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="cart && cartItems.length > 0" class="border-t border-gray-100 p-8 space-y-6">

                    <div class="text-center">
                        <p class="text-[10px] text-gray-400 italic mb-6">Taxes and shipping calculated at checkout</p>
                        
                        <Link 
                            :href="route('order.create')" 
                            @click="handleClose"
                            class="block w-full py-5 bg-black text-white text-[11px] font-black uppercase font-heebo hover:bg-black/90 transition-all rounded mb-4"
                        >
                            Checkout • ৳{{ cartTotal }}
                        </Link>
                        
                        <Link 
                            href="/cart" 
                            @click="handleClose"
                            class="text-[9px] font-black uppercase font-heebo text-gray-400 hover:text-black border-b border-gray-200 pb-0.5 transition-all"
                        >
                            View Cart
                        </Link>
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
