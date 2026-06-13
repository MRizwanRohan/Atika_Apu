<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    SquarePen,
    Package,
    Search,
    Hash,
    ShoppingCart
} from "lucide-vue-next";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";

const props = defineProps({
    orders: Object,
});

const filters = ref({
    customer_search: new URL(window.location.href).searchParams.get("customer_search") || "",
    order_number: new URL(window.location.href).searchParams.get("order_number") || "",
});

const applyFilters = () => {
    router.get(route('admin.orders.pre-order'), {
        ...filters.value,
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.value = {
        customer_search: "",
        order_number: "",
    };
    applyFilters();
};

const goToPage = (page) => {
    router.get(route('admin.orders.pre-order'), {
        ...filters.value,
        page: page,
    });
};

const getStatusBadgeClass = (status) => {
    const base = "px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ";
    switch (status) {
        case "pending": return base + "bg-blue-50 text-blue-600";
        case "processing": return base + "bg-indigo-50 text-indigo-600";
        case "completed": return base + "bg-green-50 text-green-600";
        case "cancelled": return base + "bg-red-50 text-red-600";
        case "shipped": return base + "bg-orange-50 text-orange-600";
        case "received_china": return base + "bg-orange-100 text-orange-800";
        case "received_bd_airport": return base + "bg-pink-100 text-pink-800";
        case "received_bd_warehouse": return base + "bg-emerald-100 text-emerald-800";
        case "purchase_ok": return base + "bg-amber-100 text-amber-800";
        default: return base + "bg-gray-50 text-gray-600";
    }
};

const updateOrderStatus = async (orderId, newStatus) => {
    try {
        await axios.put(route('admin.orders.status.update', orderId), { status: newStatus });
        toast.success("Status updated");
        router.reload();
    } catch (error) {
        toast.error("Status update failed");
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Pre-Orders" />
        <div class="p-4 md:p-8 bg-[#FBFBFD] min-h-screen">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Pre-Orders</h1>
                <p class="text-sm text-gray-400 mt-1">Manage product reservation requests</p>
            </div>

            <!-- Minimal Search -->
            <div class="flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-1 flex gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-300" />
                        <input
                            v-model="filters.customer_search"
                            type="text"
                            placeholder="Find by customer..."
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-100 rounded-xl focus:ring-0 focus:border-indigo-200 transition-all outline-none text-sm shadow-sm shadow-gray-100/50"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <div class="relative w-32 md:w-48">
                        <Hash class="absolute left-3 top-2.5 h-4 w-4 text-gray-300" />
                        <input
                            v-model="filters.order_number"
                            type="text"
                            placeholder="Order ID"
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-100 rounded-xl focus:ring-0 focus:border-indigo-200 transition-all outline-none text-sm shadow-sm shadow-gray-100/50"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="resetFilters" class="px-6 py-2 bg-white text-gray-400 rounded-xl font-medium text-sm hover:text-gray-600 transition-colors border border-gray-50">
                        Reset
                    </button>
                    <button @click="applyFilters" class="px-8 py-2 bg-gray-900 text-white rounded-xl font-semibold text-sm hover:bg-black transition-colors shadow-lg shadow-gray-200">
                        Search
                    </button>
                </div>
            </div>

            <!-- Minimal Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 max-w-6xl mx-auto">
                <div v-for="order in orders.data" :key="order.id" 
                     class="bg-white rounded-2xl border border-gray-50 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300">
                    
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                            <span class="text-sm font-bold text-gray-900 tracking-tight">#{{ order.id }}</span>
                            <div class="flex gap-1.5">
                                <span class="bg-indigo-50/50 text-indigo-500 px-2 py-0.5 rounded-md text-[9px] font-bold tracking-wider uppercase">
                                    {{ order.source || 'WEB' }}
                                </span>
                                <span class="bg-purple-50/50 text-purple-500 px-2 py-0.5 rounded-md text-[9px] font-bold tracking-wider uppercase">
                                    Pre-Order
                                </span>
                            </div>
                        </div>
                        <div class="text-[#10B981] font-bold text-lg tracking-tight">
                            ৳{{ order.total }}
                        </div>
                    </div>

                    <div class="flex items-center gap-5">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gray-50 border border-gray-50 flex-shrink-0">
                            <img v-if="order.items?.[0]?.product?.image" :src="order.items[0].product.image" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Package class="w-6 h-6 text-gray-200" />
                            </div>
                        </div>
                        
                        <div class="flex-1">
                            <div class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.2em] mb-1">Items Summary</div>
                            <div class="text-xs font-bold text-gray-600">
                                {{ order.items?.length || 0 }} SKU • {{ order.items?.reduce((acc, item) => acc + item.quantity, 0) || 0 }} UNITS
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-8">
                        <div :class="getStatusBadgeClass(order.status)">
                            {{ order.status }}
                        </div>
                        <Link :href="route('admin.orders.edit', order.id)" 
                              class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-indigo-50 hover:text-indigo-500 transition-all">
                            <SquarePen class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="orders.data.length === 0" class="flex flex-col items-center justify-center py-24">
                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-gray-50 mb-4">
                    <ShoppingCart class="w-5 h-5 text-gray-200" />
                </div>
                <p class="text-sm font-medium text-gray-400">No active pre-orders found</p>
            </div>

            <!-- Minimal Pagination -->
            <div v-if="orders.last_page > 1" class="mt-12 flex justify-center gap-3">
                <button 
                    v-for="page in orders.last_page" 
                    :key="page"
                    @click="goToPage(page)"
                    :class="[
                        'w-8 h-8 rounded-lg text-xs font-bold transition-all',
                        orders.current_page === page 
                            ? 'bg-gray-900 text-white' 
                            : 'bg-white text-gray-400 border border-gray-50 hover:border-gray-200'
                    ]"
                >
                    {{ page }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Smooth transitions */
.transition-all {
    transition-duration: 400ms;
}
</style>
