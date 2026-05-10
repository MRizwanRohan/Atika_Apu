<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { 
    User, 
    ShoppingBag, 
    Clock, 
    Truck, 
    XCircle, 
    Lock, 
    Box, 
    Star, 
    Headphones, 
    LogOut,
    ChevronRight,
    UserCircle,
    Phone,
    Mail
} from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    orders: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            totalOrders: 0,
            pendingOrders: 0,
            completedOrders: 0,
            canceledOrders: 0,
            incompleteOrders: 0,
            preOrderCount: 0,
            reviewsCount: 0
        }),
    },
    general_settings: {
        type: Object,
        default: () => ({}),
    }
});

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route("logout"));
};

const getStatusColor = (status) => {
    const s = (status || '').toLowerCase();
    if (s === 'delivered') return 'bg-green-50 text-green-600 border-green-100';
    if (s === 'cancelled' || s === 'returned') return 'bg-red-50 text-red-600 border-red-100';
    if (s === 'pending' || s === 'processing') return 'bg-amber-50 text-amber-600 border-amber-100';
    return 'bg-blue-50 text-blue-600 border-blue-100';
};
</script>

<template>
    <Head title="My Profile" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans selection:bg-primary/10">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl relative flex flex-col overflow-hidden border border-slate-100">
                
                <!-- Header -->
                <header class="flex justify-center items-center py-4 px-4 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 tracking-tight">Account Dashboard</h1>
                </header>

                <div class="flex-1 overflow-y-auto pb-6 custom-scrollbar">
                    
                    <!-- User Profile Card -->
                    <div class="bg-white p-4 md:p-8 flex items-center gap-4 md:gap-6 border-b border-slate-100">
                        <div class="w-12 h-12 md:w-20 md:h-20 bg-[#0a1f44] rounded-full flex items-center justify-center flex-shrink-0 shadow-lg" style="background-color: #0a1f44;">
                            <User :size="24" class="text-white md:hidden" />
                            <User :size="32" class="text-white hidden md:block" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-lg md:text-2xl font-semibold text-slate-900 truncate tracking-tight">
                                {{ user.name }}
                            </h2>
                            <p class="text-[11px] md:text-sm font-medium text-slate-500 mt-0.5">
                                {{ user.phone_number || user.email }}
                            </p>
                            <div class="mt-1.5 md:mt-2.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] md:text-[10px] font-semibold uppercase tracking-wider bg-[#e0f2fe] text-[#0284c7] border border-transparent">
                                    Customer
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- My Orders Stats Grid -->
                    <div class="bg-white mx-2 md:mx-4 mt-4 md:mt-6 rounded-xl md:rounded-2xl p-4 md:p-5 border border-slate-100 shadow-sm transition-all hover:shadow-md">
                        <h3 class="text-[10px] md:text-xs font-semibold text-slate-900 uppercase tracking-wider mb-4 md:mb-6 flex items-center gap-2">
                            <ShoppingBag :size="12" /> My Orders
                        </h3>
                        
                        <div class="grid grid-cols-4 gap-2 md:gap-4 px-1 md:px-2">
                            <Link :href="route('user.orders')" class="flex flex-col items-center gap-1.5 md:gap-2 group text-center">
                                <div class="relative w-8 h-8 md:w-10 md:h-10 flex items-center justify-center text-[#0a1f44] group-active:scale-95 transition-transform mx-auto">
                                    <ShoppingBag :size="20" />
                                    <span v-if="stats.totalOrders > 0" class="absolute -top-1 -right-1.5 bg-[#0a1f44] text-white text-[8px] md:text-[9px] font-semibold px-1 rounded-full border border-white">
                                        {{ stats.totalOrders }}
                                    </span>
                                </div>
                                <span class="text-[8px] md:text-[9px] font-medium text-slate-500 uppercase tracking-tighter">Total</span>
                            </Link>
                            <Link :href="route('user.orders', { status: 'pending' })" class="flex flex-col items-center gap-1.5 md:gap-2 group text-center">
                                <div class="relative w-8 h-8 md:w-10 md:h-10 flex items-center justify-center text-[#ff9800] group-active:scale-95 transition-transform mx-auto">
                                    <Clock :size="20" />
                                    <span v-if="stats.pendingOrders > 0" class="absolute -top-1 -right-1.5 bg-[#ff9800] text-white text-[8px] md:text-[9px] font-semibold px-1 rounded-full border border-white">
                                        {{ stats.pendingOrders }}
                                    </span>
                                </div>
                                <span class="text-[8px] md:text-[9px] font-medium text-slate-500 uppercase tracking-tighter">Pending</span>
                            </Link>
                            <Link :href="route('user.orders', { status: 'delivered' })" class="flex flex-col items-center gap-1.5 md:gap-2 group text-center">
                                <div class="relative w-8 h-8 md:w-10 md:h-10 flex items-center justify-center text-[#28a745] group-active:scale-95 transition-transform mx-auto">
                                    <Truck :size="20" />
                                </div>
                                <span class="text-[8px] md:text-[9px] font-medium text-slate-500 uppercase tracking-tighter">Delivered</span>
                            </Link>
                            <Link :href="route('user.orders', { status: 'cancelled' })" class="flex flex-col items-center gap-1.5 md:gap-2 group text-center">
                                <div class="relative w-8 h-8 md:w-10 md:h-10 flex items-center justify-center text-[#dc3545] group-active:scale-95 transition-transform mx-auto">
                                    <XCircle :size="20" />
                                </div>
                                <span class="text-[8px] md:text-[9px] font-medium text-slate-500 uppercase tracking-tighter">Cancelled</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Orders List -->
                    <div class="mt-4 md:mt-8 px-2 md:px-4">
                        <div class="flex items-center justify-between ml-1 md:ml-2 mb-2 md:mb-3">
                            <h3 class="text-[10px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-[0.2em]">Recent Orders</h3>
                            <Link v-if="orders.data.length > 0" :href="route('user.orders')" class="text-[9px] md:text-[10px] font-semibold text-primary uppercase tracking-widest hover:underline">View All</Link>
                        </div>
                        
                        <div v-if="orders.data.length === 0" class="bg-white rounded-xl md:rounded-2xl border border-slate-100 p-6 md:p-8 text-center shadow-sm">
                            <ShoppingBag :size="24" class="mx-auto text-slate-200 mb-2" />
                            <p class="text-[10px] md:text-[11px] text-slate-400 font-medium italic">No orders found yet.</p>
                        </div>

                        <div v-else class="space-y-2 md:space-y-3">
                            <div v-for="order in orders.data" :key="order.id" class="bg-white rounded-xl md:rounded-2xl border border-slate-100 p-3 md:p-4 shadow-sm group hover:border-slate-200 transition-all">
                                <div class="flex justify-between items-start mb-2 md:mb-3">
                                    <div>
                                        <p class="text-[9px] md:text-[10px] font-semibold text-slate-400 uppercase tracking-tight">Order #{{ order.order_number }}</p>
                                        <p class="text-[8px] md:text-[9px] text-slate-400 mt-0.5 uppercase">{{ order.created_at_formatted || 'recently' }}</p>
                                    </div>
                                    <span :class="getStatusColor(order.status)" class="px-2 py-0.5 text-[7px] md:text-[8px] font-semibold uppercase rounded-full border tracking-tighter">
                                        {{ order.status }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-2 md:gap-3">
                                    <div class="flex -space-x-3 md:-space-x-4 overflow-hidden">
                                        <div v-for="(item, idx) in order.items.slice(0, 3)" :key="item.id" class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-white border border-white ring-2 ring-slate-50 overflow-hidden shrink-0 z-[idx]">
                                            <img :src="item.product?.feature_image_url || '/placeholder.png'" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-if="order.items.length > 3" class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-slate-100 border border-white ring-2 ring-slate-50 flex items-center justify-center text-[8px] md:text-[9px] font-bold text-slate-600 z-10">
                                            +{{ order.items.length - 3 }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] md:text-xs font-semibold text-slate-800 truncate">
                                            {{ order.items[0]?.product?.name || 'Order Item' }}
                                            <span v-if="order.items.length > 1" class="text-slate-400"> + {{ order.items.length - 1 }} more</span>
                                        </p>
                                        <p class="text-[10px] md:text-[11px] text-slate-900 font-semibold mt-0.5">৳{{ order.total }}</p>
                                    </div>
                                    <div class="shrink-0">
                                        <Link :href="route('order.success', order.order_number)" class="w-7 h-7 md:w-8 md:h-8 flex items-center justify-center rounded-lg md:rounded-xl bg-slate-50 text-slate-400 group-hover:bg-slate-100 group-hover:text-primary transition-all">
                                            <ChevronRight :size="14" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="mt-6 md:mt-8 px-2 md:px-4">
                        <h3 class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-[0.2em] ml-1 md:ml-2 mb-2 md:mb-3">Account Settings</h3>
                        <div class="bg-white rounded-xl md:rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                            <Link :href="route('user.profile.edit')" class="flex justify-between items-center p-3 md:p-4 hover:bg-slate-50 transition-colors border-b border-slate-100">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-slate-50 flex items-center justify-center text-[#666]">
                                        <UserCircle :size="16" />
                                    </div>
                                    <span class="text-xs md:text-[14px] font-medium text-slate-700">Personal Details</span>
                                </div>
                                <ChevronRight :size="16" class="text-slate-300" />
                            </Link>
                            <Link :href="route('user.password.edit')" class="flex justify-between items-center p-3 md:p-4 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-slate-50 flex items-center justify-center text-[#444]">
                                        <Lock :size="16" />
                                    </div>
                                    <span class="text-xs md:text-[14px] font-medium text-slate-700">Change Password</span>
                                </div>
                                <ChevronRight :size="16" class="text-slate-300" />
                            </Link>
                        </div>
                    </div>

                    <!-- Activities -->
                    <div class="mt-6 md:mt-8 px-2 md:px-4">
                        <h3 class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-[0.2em] ml-1 md:ml-2 mb-2 md:mb-3">Activities</h3>
                        <div class="bg-white rounded-xl md:rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                            <Link :href="route('user.pre-orders')" class="flex justify-between items-center p-3 md:p-4 hover:bg-slate-50 transition-colors border-b border-slate-100">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-slate-50 flex items-center justify-center text-slate-500">
                                        <Box :size="16" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs md:text-[14px] font-medium text-slate-700">Pre Order</span>
                                        <span v-if="stats.preOrderCount > 0" class="text-[8px] md:text-[9px] bg-[#0a1f44] text-white px-1.5 py-0.5 rounded-md font-semibold italic">
                                            {{ stats.preOrderCount }}
                                        </span>
                                    </div>
                                </div>
                                <ChevronRight :size="16" class="text-slate-300" />
                            </Link>
                            <Link :href="route('user.reviews')" class="flex justify-between items-center p-3 md:p-4 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-slate-50 flex items-center justify-center text-slate-500">
                                        <Star :size="16" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs md:text-[14px] font-medium text-slate-700">My Reviews</span>
                                        <span v-if="stats.reviewsCount > 0" class="text-[8px] md:text-[9px] bg-[#0a1f44] text-white px-1.5 py-0.5 rounded-md font-semibold italic">
                                            {{ stats.reviewsCount }}
                                        </span>
                                    </div>
                                </div>
                                <ChevronRight :size="16" class="text-slate-300" />
                            </Link>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="mt-6 md:mt-8 px-2 md:px-4 pb-4">
                        <h3 class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-[0.2em] ml-1 md:ml-2 mb-2 md:mb-3">Help & Support</h3>
                        <div class="bg-white rounded-xl md:rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-4 md:mb-6">
                            <a v-if="general_settings.store_phone_number" :href="'tel:' + general_settings.store_phone_number" class="flex justify-between items-center p-3 md:p-4 hover:bg-slate-50 transition-colors border-b border-slate-100">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                                        <Phone :size="16" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[12px] md:text-[13px] font-medium text-slate-700">Hotline</span>
                                        <span class="text-[10px] md:text-[11px] text-slate-400">{{ general_settings.store_phone_number }}</span>
                                    </div>
                                </div>
                                <ChevronRight :size="16" class="text-slate-300" />
                            </a>
                            <a v-if="general_settings.store_email" :href="'mailto:' + general_settings.store_email" class="flex justify-between items-center p-3 md:p-4 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                                        <Mail :size="16" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[12px] md:text-[13px] font-medium text-slate-700">Email Us</span>
                                        <span class="text-[10px] md:text-[11px] text-slate-400">{{ general_settings.store_email }}</span>
                                    </div>
                                </div>
                                <ChevronRight :size="16" class="text-slate-300" />
                            </a>
                        </div>

                        <!-- Logout Button -->
                        <button 
                            @click="logout"
                            class="w-full flex items-center justify-center gap-2 p-3.5 md:p-4 bg-white border border-rose-100 text-rose-500 rounded-xl md:rounded-2xl font-semibold text-xs md:text-sm tracking-wide hover:bg-rose-50 transition-all active:scale-[0.98] shadow-sm"
                        >
                            <LogOut :size="16" />
                            Log Out
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </FrontendLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.font-sans {
    font-family: 'Montserrat', sans-serif !important;
}

.custom-scrollbar::-webkit-scrollbar {
    display: none;
}
.custom-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

h1, h2, h3, span, button {
    letter-spacing: -0.01em;
}

.active\:scale-95:active {
    transform: scale(0.95);
}
</style>
