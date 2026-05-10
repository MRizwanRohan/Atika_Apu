<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { Star, ArrowLeft, MessageSquare, CheckCircle2, LayoutDashboard, Clock } from "lucide-vue-next";

const props = defineProps({
    reviews: {
        type: Object, // Paginated
        default: () => ({ data: [] }),
    },
    reviewableOrders: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="My Reviews" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-100 flex flex-col">
                
                <!-- Header -->
                <header class="flex items-center justify-between py-4 px-4 md:py-5 md:px-6 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <Link :href="route('user.dashboard')" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 rounded-full transition-all text-slate-600 font-semibold text-[10px] md:text-xs uppercase tracking-tight">
                        <ArrowLeft :size="12" />
                        <span>Back</span>
                    </Link>
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 absolute left-1/2 -translate-x-1/2">My Reviews</h1>
                    <div class="w-10"></div>
                </header>

                <div class="p-4 md:p-6 space-y-6 md:space-y-8">
                    <!-- Items to Review -->
                    <section v-if="reviewableOrders.length > 0" class="space-y-3 md:space-y-4">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-4">
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                <Star :size="14" />
                            </div>
                            <h2 class="text-[11px] md:text-sm font-semibold text-slate-800 uppercase tracking-widest">Pending Reviews</h2>
                        </div>
                        
                        <div v-for="order in reviewableOrders" :key="order.id" class="space-y-2 md:space-y-3">
                            <div v-for="item in order.items" :key="item.id" class="bg-slate-50 border border-slate-100 rounded-xl md:rounded-2xl p-3 md:p-4 flex gap-3 md:gap-4 items-center">
                                <div class="w-14 h-14 md:w-16 md:h-16 bg-white rounded-lg md:rounded-xl overflow-hidden flex-shrink-0 border border-slate-100">
                                    <img :src="item.product?.feature_image_url || '/placeholder.png'" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[11px] md:text-xs font-semibold text-slate-800 truncate">{{ item.product?.name }}</h4>
                                    <p class="text-[9px] md:text-[10px] text-slate-400 font-medium mt-0.5">Order #{{ order.order_number }}</p>
                                    <Link v-if="item.product?.slug" :href="route('product.details', item.product.slug) + '#reviews'" class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 md:px-4 md:py-2 bg-primary text-white text-[9px] md:text-[10px] font-semibold uppercase tracking-wider rounded-lg md:rounded-xl shadow-md shadow-primary/20">
                                        <Star :size="10" class="fill-white md:block hidden" /> Write Review
                                    </Link>
                                    <div v-else class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 text-slate-400 text-[9px] font-semibold uppercase tracking-wider rounded-lg cursor-not-allowed">
                                        Product Unavailable
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- History -->
                    <section class="space-y-3 md:space-y-4">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-4">
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500">
                                <MessageSquare :size="14" />
                            </div>
                            <h2 class="text-[11px] md:text-sm font-semibold text-slate-800 uppercase tracking-widest">Review History</h2>
                        </div>

                        <div v-if="reviews.data.length === 0" class="text-center py-10 md:py-16 bg-slate-50 rounded-2xl md:rounded-3xl border-2 border-dashed border-slate-200">
                            <MessageSquare :size="32" class="mx-auto text-slate-200 mb-3" />
                            <h3 class="text-[10px] md:text-xs font-semibold text-slate-400 uppercase tracking-widest">No reviews found</h3>
                            <Link :href="route('home')" class="mt-3 md:mt-4 inline-block text-[10px] font-medium text-primary underline">Start Shopping</Link>
                        </div>

                        <div v-for="review in reviews.data" :key="review.id" class="bg-white border border-slate-100 rounded-xl md:rounded-2xl p-3 md:p-5 space-y-3 md:space-y-4 shadow-sm">
                            <div class="flex items-center gap-3 md:gap-4">
                                <div class="w-10 h-10 md:w-12 md:h-12 bg-slate-50 rounded-lg md:rounded-xl overflow-hidden flex-shrink-0 border border-slate-100">
                                    <img :src="review.product?.feature_image_url || '/placeholder.png'" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[10px] md:text-xs font-semibold text-slate-800 truncate">{{ review.product?.name }}</h4>
                                    <div class="flex gap-0.5 mt-0.5">
                                        <Star v-for="i in 5" :key="i" :size="8" :class="i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-slate-200'" />
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <span v-if="review.is_approved" class="px-1.5 py-0.5 bg-green-50 text-green-600 text-[7px] md:text-[8px] font-semibold uppercase rounded-md border border-green-100">Approved</span>
                                    <span v-else class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-[7px] md:text-[8px] font-semibold uppercase rounded-md border border-amber-100">Pending</span>
                                </div>
                            </div>
                            <div class="bg-slate-50 p-3 md:p-4 rounded-xl relative">
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed font-medium">
                                    {{ review.review_message }}
                                </p>
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
