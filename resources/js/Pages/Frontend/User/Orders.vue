<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { ShoppingBag, ArrowLeft, ChevronRight, LayoutDashboard } from "lucide-vue-next";

const props = defineProps({
    orders: {
        type: Object, // Paginated
        default: () => ({ data: [] }),
    },
    status: {
        type: String,
        default: null,
    }
});

const getStatusColor = (status) => {
    const s = (status || '').toLowerCase();
    if (s === 'delivered') return 'bg-green-50 text-green-600 border-green-100';
    if (s === 'cancelled' || s === 'returned') return 'bg-red-50 text-red-600 border-red-100';
    if (s === 'pending' || s === 'processing') return 'bg-amber-50 text-amber-600 border-amber-100';
    return 'bg-blue-50 text-blue-600 border-blue-100';
};
</script>

<template>
    <Head title="My Orders" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-100 flex flex-col">
                
                <!-- Header -->
                <header class="flex items-center justify-between py-4 px-4 md:py-5 md:px-6 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <Link :href="route('user.dashboard')" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 rounded-full transition-all text-slate-600 font-semibold text-[10px] md:text-xs uppercase tracking-tight">
                        <ArrowLeft :size="12" />
                        <span>Back</span>
                    </Link>
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 absolute left-1/2 -translate-x-1/2">
                        {{ status ? status.charAt(0).toUpperCase() + status.slice(1) : 'All' }} Orders
                    </h1>
                    <div class="w-10"></div>
                </header>

                <div class="p-3 md:p-6 space-y-3 md:space-y-4">
                    <div v-if="orders.data.length === 0" class="text-center py-12 md:py-20 bg-slate-50 rounded-2xl md:rounded-3xl border-2 border-dashed border-slate-200">
                        <ShoppingBag :size="32" class="mx-auto text-slate-200 mb-3" />
                        <h3 class="text-[10px] md:text-xs font-semibold text-slate-400 uppercase tracking-widest">No orders found</h3>
                        <Link :href="route('home')" class="mt-4 md:mt-6 inline-block text-[10px] md:text-[11px] font-semibold text-primary uppercase tracking-widest bg-primary/10 px-5 md:px-6 py-2.5 md:py-3 rounded-xl">Browse Shop</Link>
                    </div>

                    <div v-for="order in orders.data" :key="order.id" class="bg-white rounded-xl md:rounded-2xl border border-slate-100 p-3 md:p-5 shadow-sm group hover:border-slate-200 transition-all">
                        <div class="flex justify-between items-start mb-3 md:mb-4">
                            <div>
                                <p class="text-[9px] md:text-[10px] font-semibold text-slate-400 uppercase tracking-tight">Order #{{ order.order_number }}</p>
                                <p class="text-[8px] md:text-[9px] text-slate-400 mt-0.5 uppercase">{{ order.created_at_formatted || 'recently' }}</p>
                            </div>
                            <span :class="getStatusColor(order.status)" class="px-2 py-0.5 text-[7px] md:text-[8px] font-semibold uppercase rounded-full border tracking-tighter">
                                {{ order.status }}
                            </span>
                        </div>
                        
                        <div class="space-y-2 md:space-y-3">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 md:gap-4 bg-slate-50/50 p-2 rounded-xl border border-transparent group-hover:border-slate-50 transition-colors">
                                <div class="w-10 h-10 md:w-12 md:h-12 bg-white rounded-lg border border-slate-100 overflow-hidden flex-shrink-0">
                                    <img :src="item.product?.feature_image_url || '/placeholder.png'" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-[10px] md:text-[11px] font-semibold text-slate-800 truncate">{{ item.product?.name }}</h5>
                                    <p class="text-[9px] md:text-[10px] text-slate-500 font-medium mt-0.5">Qty: {{ item.quantity }} × ৳{{ item.unit_price }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 md:mt-4 pt-3 md:pt-4 border-t border-slate-50 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] md:text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Total</span>
                                <span class="text-xs font-semibold text-slate-900">৳{{ order.total }}</span>
                            </div>
                            <Link :href="route('order.success', order.order_number)" class="flex items-center gap-1 text-[9px] md:text-[10px] font-semibold text-primary uppercase tracking-widest hover:underline group/btn">
                                Details 
                                <ChevronRight :size="10" class="group-hover/btn:translate-x-0.5 transition-transform" />
                            </Link>
                        </div>
                    </div>

                    <Link :href="route('user.dashboard')" class="flex items-center justify-center gap-2 pt-6 md:pt-10 text-[10px] md:text-xs font-medium text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                        <LayoutDashboard :size="12" />
                        Back to Dashboard
                    </Link>
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
</style>
