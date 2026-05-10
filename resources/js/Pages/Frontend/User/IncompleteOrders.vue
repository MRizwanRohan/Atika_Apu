<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { AlertCircle, ArrowLeft, LayoutDashboard, ChevronRight } from "lucide-vue-next";

const props = defineProps({
    orders: {
        type: Object, // Paginated
        default: () => ({ data: [] }),
    }
});
</script>

<template>
    <Head title="Incomplete Orders" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-100 flex flex-col">
                
                <!-- Header -->
                <header class="flex items-center justify-between py-4 px-4 md:py-5 md:px-6 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <Link :href="route('user.dashboard')" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 rounded-full transition-all text-slate-600 font-semibold text-[10px] md:text-xs uppercase tracking-tight">
                        <ArrowLeft :size="12" />
                        <span>Back</span>
                    </Link>
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 absolute left-1/2 -translate-x-1/2">Incomplete Orders</h1>
                    <div class="w-10"></div>
                </header>

                <div class="p-4 md:p-6 space-y-4 md:space-y-6">
                    <div v-if="orders.data.length === 0" class="text-center py-12 md:py-20 bg-slate-50 rounded-2xl md:rounded-3xl border-2 border-dashed border-slate-200">
                        <AlertCircle :size="32" class="mx-auto text-slate-200 mb-3" />
                        <h3 class="text-[10px] md:text-xs font-semibold text-slate-400 uppercase tracking-widest">No incomplete orders</h3>
                        <p class="text-[9px] md:text-[10px] text-slate-400 mt-2 px-6 md:px-10">All your orders are being processed successfully.</p>
                        <Link :href="route('home')" class="mt-4 md:mt-6 inline-block text-[10px] md:text-[11px] font-semibold text-primary uppercase tracking-widest bg-primary/10 px-5 md:px-6 py-2.5 md:py-3 rounded-xl">Continue Shopping</Link>
                    </div>

                    <div v-for="order in orders.data" :key="order.id" class="bg-white border border-slate-100 rounded-xl md:rounded-2xl p-4 md:p-6 space-y-3 md:space-y-4 shadow-sm relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-400/20 group-hover:bg-amber-400 transition-colors"></div>
                        
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="text-[9px] md:text-xs font-semibold text-slate-400 uppercase tracking-tight">Order #{{ order.order_number }}</h4>
                                <p class="text-[10px] md:text-[11px] text-slate-900 font-semibold mt-0.5 md:mt-1">Total: ৳{{ order.total }}</p>
                                <p class="text-[9px] md:text-[10px] text-slate-400 font-medium mt-0.5 md:mt-1 uppercase tracking-tighter">{{ order.created_at_formatted || 'recently' }}</p>
                            </div>
                            <span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-[8px] md:text-[9px] font-semibold uppercase rounded-full border border-amber-100">
                                Incomplete
                            </span>
                        </div>

                        <div class="bg-slate-50 rounded-xl md:rounded-2xl p-3 md:p-4 space-y-2 md:space-y-3">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-white rounded-lg border border-slate-100 overflow-hidden flex-shrink-0">
                                    <img :src="item.product?.feature_image_url || '/placeholder.png'" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-[10px] md:text-[11px] font-semibold text-slate-800 truncate">{{ item.product?.name }}</h5>
                                    <p class="text-[9px] md:text-[10px] text-slate-500 font-medium">Qty: {{ item.quantity }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 md:pt-3 border-t border-slate-50 flex justify-between items-center">
                            <p class="text-[9px] md:text-[10px] text-slate-500 font-medium italic truncate pr-2">Verification pending.</p>
                            <Link :href="route('order.success', order.order_number)" class="shrink-0 flex items-center gap-1 text-[9px] md:text-[10px] font-semibold text-primary uppercase tracking-widest hover:underline">
                                Details <ChevronRight :size="10" />
                            </Link>
                        </div>
                    </div>

                    <Link :href="route('user.dashboard')" class="flex items-center justify-center gap-2 pt-6 md:pt-10 text-[10px] md:text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                        <LayoutDashboard :size="12" />
                        Back to Dashboard
                    </Link>
                </div>

            </div>
        </div>
    </FrontendLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

.font-sans {
    font-family: 'Montserrat', sans-serif !important;
}
</style>
