<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { Box, ArrowLeft, Clock, LayoutDashboard, MessageSquare, ShoppingBag } from "lucide-vue-next";

const props = defineProps({
    preOrderLeads: {
        type: Object, // Paginated
        default: () => ({ data: [] }),
    },
    preOrderOrders: {
        type: Array,
        default: () => [],
    }
});
</script>

<template>
    <Head title="My Pre-Orders" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-100 flex flex-col">
                
                <!-- Header -->
                <header class="flex items-center justify-between py-4 px-4 md:py-5 md:px-6 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <Link :href="route('user.dashboard')" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 rounded-full transition-all text-slate-600 font-semibold text-[10px] md:text-xs uppercase tracking-tight">
                        <ArrowLeft :size="12" />
                        <span>Back</span>
                    </Link>
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 absolute left-1/2 -translate-x-1/2">Pre-Order Status</h1>
                    <div class="w-10"></div>
                </header>

                <div class="p-4 md:p-6 space-y-8 md:space-y-10">
                    
                    <!-- Pre-Order Orders (Actual purchases) -->
                    <section v-if="preOrderOrders.length > 0" class="space-y-3 md:space-y-4">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-4">
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                <ShoppingBag :size="14" />
                            </div>
                            <h2 class="text-[11px] md:text-sm font-semibold text-slate-800 uppercase tracking-widest">Confirmed Pre-Orders</h2>
                        </div>

                        <div v-for="order in preOrderOrders" :key="order.id" class="bg-white border border-slate-100 rounded-xl md:rounded-2xl p-3 md:p-5 space-y-3 md:space-y-4 shadow-sm relative overflow-hidden group border-l-4 border-l-blue-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-[9px] md:text-xs font-semibold text-slate-400 uppercase tracking-tight">Order #{{ order.order_number }}</h4>
                                    <p class="text-[8px] md:text-[9px] text-slate-400 mt-0.5 uppercase">{{ order.created_at_formatted || 'recently' }}</p>
                                </div>
                                <span class="px-2 py-0.5 bg-green-50 text-green-600 text-[7px] md:text-[8px] font-semibold uppercase rounded-full border border-green-100">
                                    {{ order.status }}
                                </span>
                            </div>

                            <div v-for="item in order.items" :key="item.id" class="flex gap-3 md:gap-4 items-center bg-slate-50 p-2 md:p-3 rounded-lg md:rounded-xl">
                                <div class="w-10 h-10 md:w-12 md:h-12 bg-white rounded-lg border border-slate-100 overflow-hidden flex-shrink-0">
                                    <img :src="item.product?.feature_image_url || '/placeholder.png'" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-[11px] md:text-xs font-semibold text-slate-800 truncate">{{ item.product?.name }}</h5>
                                    <p class="text-[9px] md:text-[10px] text-primary font-semibold uppercase tracking-widest mt-0.5">Pre-Ordered</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Pre-Order Leads (Inquiries) -->
                    <section class="space-y-3 md:space-y-4">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-4">
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                <Box :size="14" />
                            </div>
                            <h2 class="text-[11px] md:text-sm font-semibold text-slate-800 uppercase tracking-widest">Pre-Order Inquiries</h2>
                        </div>

                        <div v-if="preOrderLeads.data.length === 0 && preOrderOrders.length === 0" class="text-center py-12 md:py-20 bg-slate-50 rounded-2xl md:rounded-3xl border-2 border-dashed border-slate-200">
                            <Box :size="32" class="mx-auto text-slate-200 mb-3" />
                            <h3 class="text-[10px] md:text-xs font-semibold text-slate-400 uppercase tracking-widest">No requests found</h3>
                            <Link :href="route('home')" class="mt-4 md:mt-6 inline-block text-[10px] md:text-[11px] font-semibold text-primary uppercase tracking-widest bg-primary/10 px-5 md:px-6 py-2.5 md:py-3 rounded-xl">Browse Catalog</Link>
                        </div>

                        <div v-for="lead in preOrderLeads.data" :key="lead.id" class="bg-white border border-slate-100 rounded-xl md:rounded-2xl p-4 md:p-6 space-y-3 md:space-y-4 shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-1 h-full bg-primary/10 group-hover:bg-primary transition-colors"></div>
                            
                            <div class="flex justify-between items-start px-1 md:px-2">
                                <div class="flex-1">
                                    <h4 class="text-[13px] md:text-base font-semibold text-slate-900 tracking-tight leading-tight">{{ lead.product_interest }}</h4>
                                    <div class="flex items-center gap-1.5 mt-1.5 md:mt-2">
                                        <Clock :size="10" class="text-slate-400" />
                                        <p class="text-[8px] md:text-[10px] text-slate-400 font-medium uppercase tracking-wider">Requested on {{ lead.created_at_formatted || 'recent' }}</p>
                                    </div>
                                </div>
                                <span :class="{
                                    'bg-primary/10 text-primary border-primary/20': lead.status === 'Pending',
                                    'bg-green-50 text-green-600 border-green-100': lead.status === 'Contacted',
                                }" class="px-2 py-0.5 text-[8px] font-semibold uppercase rounded-full tracking-tighter border">
                                    {{ lead.status || 'Pending' }}
                                </span>
                            </div>

                            <div class="bg-slate-50 rounded-lg md:rounded-2xl p-3 md:p-4 flex gap-2 md:gap-3">
                                <div class="flex-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-600 font-medium leading-relaxed italic">
                                        {{ lead.message || 'No additional details provided.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

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
