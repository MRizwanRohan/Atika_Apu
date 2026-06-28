<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
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
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'all',
            date_from: null,
            date_to: null
        })
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

const filters = ref({
    period: props.filters?.period || 'all',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
});

const applyFilters = () => {
    const params = { ...filters.value };

    if (filters.value.period !== "custom") {
        params.date_from = null;
        params.date_to = null;
    }

    Object.keys(params).forEach((key) => {
        if (
            params[key] === "" ||
            params[key] === null ||
            params[key] === undefined
        ) {
            delete params[key];
        }
    });

    router.get(route("admin.courier.dashboard"), params, {
        preserveState: true,
        preserveScroll: true
    });
};

watch(() => filters.value.period, (newPeriod) => {
    if (newPeriod !== 'custom') {
        applyFilters();
    }
});

watch([() => filters.value.date_from, () => filters.value.date_to], () => {
    if (filters.value.period === 'custom' && filters.value.date_from && filters.value.date_to) {
        applyFilters();
    }
});
</script>

<template>
    <Head title="Courier Report" />
    <AdminLayout>
        <div class="w-full mx-auto space-y-6 pb-10 px-2 sm:px-4">
            <!-- Header -->
            <div class="px-1">
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Courier Report</h1>
                <p class="text-xs text-gray-500 mt-1">Delivery performance & geographic distribution</p>
            </div>

            <!-- Filters Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-xs">
                <div class="flex items-center justify-between mb-3 border-b border-gray-50 dark:border-gray-700 pb-2">
                    <h2 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Filters
                    </h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
                    <!-- Period Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1">Period</label>
                        <select v-model="filters.period" class="w-full px-2.5 py-2 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg focus:ring-1 focus:ring-blue-500 bg-gray-50/30 text-gray-700 dark:text-gray-200 font-medium">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <!-- Custom Date Range -->
                    <template v-if="filters.period === 'custom'">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1">From</label>
                            <input type="date" v-model="filters.date_from" class="w-full px-2.5 py-2 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg focus:ring-1 focus:ring-blue-500 bg-white text-gray-700 dark:text-gray-200" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1">To</label>
                            <input type="date" v-model="filters.date_to" class="w-full px-2.5 py-2 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg focus:ring-1 focus:ring-blue-500 bg-white text-gray-700 dark:text-gray-200" />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                <div v-for="(card, idx) in summaryCards" :key="card.title" 
                    :class="[
                        'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-4 flex items-center gap-3 shadow-xs transition-transform duration-200 hover:scale-[1.01]',
                        idx === 2 ? 'col-span-2 sm:col-span-1' : ''
                    ]">
                    <div :class="[card.bg, 'p-2.5 rounded-xl shrink-0']">
                        <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ card.title }}</p>
                        <h2 class="text-xl sm:text-2xl font-black text-gray-800 dark:text-white mt-0.5 leading-none">{{ card.value }}</h2>
                    </div>
                </div>
            </div>

            <!-- Detailed Status Report and District Table Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Detailed Status Report (Minimal Table) -->
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-xs">
                    <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700">
                        <h3 class="text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Status Breakdown</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-900/30">
                                    <th class="px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                    <th class="px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Dhaka</th>
                                    <th class="px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Outside</th>
                                    <th class="px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                <tr v-for="status in allStatuses" :key="status" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/40">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <component :is="getStatusIcon(status)" :class="[getStatusColor(status), 'w-4 h-4']" />
                                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 capitalize">{{ status }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-xs font-bold text-gray-500">{{ stats.dhaka[status] || 0 }}</td>
                                    <td class="px-5 py-3.5 text-center text-xs font-bold text-gray-500">{{ stats.outside[status] || 0 }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md text-[11px] font-bold text-gray-700 dark:text-gray-200">
                                            {{ stats.total[status] || 0 }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-if="allStatuses.length === 0">
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-xs text-gray-400 font-medium">
                                        No status data found for this period.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50/50 dark:bg-gray-900/30 font-bold border-t border-gray-100 dark:border-gray-700">
                                <tr>
                                    <td class="px-5 py-4 text-[10px] uppercase tracking-widest text-gray-400">Total</td>
                                    <td class="px-5 py-4 text-center text-xs font-bold text-emerald-600">
                                        {{ Object.values(stats.dhaka).reduce((a, b) => a + b, 0) }}
                                    </td>
                                    <td class="px-5 py-4 text-center text-xs font-bold text-rose-600">
                                        {{ Object.values(stats.outside).reduce((a, b) => a + b, 0) }}
                                    </td>
                                    <td class="px-5 py-4 text-center text-xs font-bold text-blue-600">
                                        {{ Object.values(stats.total).reduce((a, b) => a + b, 0) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Order Count by District Table -->
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-xs flex flex-col">
                    <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between shrink-0">
                        <h3 class="text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Order Count by District</h3>
                        <span class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full text-[10px] font-bold">
                            {{ locations.length }} Districts
                        </span>
                    </div>
                    <div class="overflow-y-auto max-h-[365px] grow">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-white dark:bg-gray-800 z-10">
                                <tr class="bg-gray-50/50 dark:bg-gray-900/30">
                                    <th class="px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">District</th>
                                    <th class="px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Orders</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                <tr v-for="loc in locations" :key="loc.district" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/40">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <MapPin class="w-3.5 h-3.5 text-gray-400" />
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ loc.district }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-md text-[11px] font-bold">
                                            {{ loc.total_orders }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="locations.length === 0">
                                    <td colspan="2" class="text-center py-8 text-xs text-gray-400 font-medium">
                                        No district orders found for this period.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
table {
    border-collapse: separate;
    border-spacing: 0;
}
</style>
