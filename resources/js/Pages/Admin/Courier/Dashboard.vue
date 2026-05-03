<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import OrderMap from "@/Components/Order/OrderMap.vue";
import { 
    Truck, 
    MapPin, 
    Globe, 
    TrendingUp,
    Package,
    CheckCircle2,
    XCircle,
    Clock,
    AlertCircle
} from "lucide-vue-next";

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        default: () => []
    }
});

const statusConfig = {
    pending: { label: 'Pending', icon: Clock, color: 'text-amber-500', bg: 'bg-amber-50' },
    processing: { label: 'Processing', icon: TrendingUp, color: 'text-blue-500', bg: 'bg-blue-50' },
    shipped: { label: 'Shipped', icon: Truck, color: 'text-indigo-500', bg: 'bg-indigo-50' },
    delivered: { label: 'Delivered', icon: CheckCircle2, color: 'text-emerald-500', bg: 'bg-emerald-50' },
    cancelled: { label: 'Cancelled', icon: XCircle, color: 'text-rose-500', bg: 'bg-rose-50' },
    returned: { label: 'Returned', icon: AlertCircle, color: 'text-gray-500', bg: 'bg-gray-50' },
};

const getStatusLabel = (status) => statusConfig[status]?.label || status.charAt(0).toUpperCase() + status.slice(1);
const getStatusIcon = (status) => statusConfig[status]?.icon || Package;
const getStatusColor = (status) => statusConfig[status]?.color || 'text-gray-500';
const getStatusBg = (status) => statusConfig[status]?.bg || 'bg-gray-50';

const summaryCards = computed(() => [
    {
        title: "Total Courier",
        value: Object.values(props.stats.total).reduce((a, b) => a + b, 0),
        icon: Package,
        color: "text-blue-600",
        bg: "bg-blue-50"
    },
    {
        title: "Dhaka City",
        value: Object.values(props.stats.dhaka).reduce((a, b) => a + b, 0),
        icon: MapPin,
        color: "text-emerald-600",
        bg: "bg-emerald-50"
    },
    {
        title: "Outside Dhaka",
        value: Object.values(props.stats.outside).reduce((a, b) => a + b, 0),
        icon: Globe,
        color: "text-rose-600",
        bg: "bg-rose-50"
    }
]);

const allStatuses = computed(() => {
    const statuses = new Set();
    Object.values(props.stats).forEach(section => {
        Object.keys(section).forEach(status => statuses.add(status));
    });
    return Array.from(statuses).sort();
});

</script>

<template>
    <Head title="Courier Report" />
    <AdminLayout>
        <div class="max-w-7xl mx-auto space-y-6 pb-10">
            <!-- Header -->
            <div class="px-1">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Courier Report</h1>
                <p class="text-sm text-gray-500 mt-1">Delivery performance & geographic distribution</p>
            </div>

            <!-- Summary Cards (Minimal) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div v-for="card in summaryCards" :key="card.title" 
                    class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-5 flex items-center gap-4 shadow-sm">
                    <div :class="[card.bg, 'p-3 rounded-xl']">
                        <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">{{ card.title }}</p>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ card.value }}</h2>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">District Wise Distribution</h3>
                </div>
                <div class="p-1 md:p-4">
                    <OrderMap :locations="locations" />
                </div>
            </div>

            <!-- Detailed Status Report (Minimal Table) -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Status Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/30">
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Dhaka</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Outside</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-for="status in allStatuses" :key="status" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/40">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <component :is="getStatusIcon(status)" :class="[getStatusColor(status), 'w-4 h-4']" />
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 capitalize">{{ status }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-xs font-semibold text-gray-500">{{ stats.dhaka[status] || 0 }}</td>
                                <td class="px-6 py-4 text-center text-xs font-semibold text-gray-500">{{ stats.outside[status] || 0 }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs font-bold text-gray-700 dark:text-gray-200">
                                        {{ stats.total[status] || 0 }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50/50 dark:bg-gray-900/30 font-bold border-t border-gray-100 dark:border-gray-700">
                            <tr>
                                <td class="px-6 py-4 text-[10px] uppercase tracking-widest text-gray-400">Total</td>
                                <td class="px-6 py-4 text-center text-sm text-emerald-600">
                                    {{ Object.values(stats.dhaka).reduce((a, b) => a + b, 0) }}
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-rose-600">
                                    {{ Object.values(stats.outside).reduce((a, b) => a + b, 0) }}
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-blue-600">
                                    {{ Object.values(stats.total).reduce((a, b) => a + b, 0) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Minimal touch-ups */
table {
    border-collapse: separate;
    border-spacing: 0;
}
</style>
