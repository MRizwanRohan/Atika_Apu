<script setup>
import { defineProps, ref, computed, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import VueApexCharts from "vue3-apexcharts";
import OrderMap from "@/Components/Order/OrderMap.vue";

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

// Sales Analytics period toggle
const salesPeriod = ref('week'); // 'week' or 'month'

// Get chart data based on selected period
const currentChartData = computed(() => {
    return salesPeriod.value === 'week' 
        ? props.data.dailyOrdersData 
        : (props.data.monthlyOrdersData || props.data.dailyOrdersData);
});

// Daily orders chart options
const dailyOrdersOptions = computed(() => ({
    chart: {
        type: "area",
        height: 350,
        toolbar: {
            show: false,
        },
        zoom: {
            enabled: false,
        },
        sparkline: {
            enabled: false,
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        curve: "smooth",
        width: 2,
    },
    colors: ["#7C3AED", "#06B6D4"],
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        categories: currentChartData.value.map((item) => item.date),
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
    },
    yaxis: {
        labels: {
            formatter: function (value) {
                return value.toFixed(0);
            },
        },
    },
    tooltip: {
        enabled: true,
        y: {
            formatter: function (value, { seriesIndex }) {
                return seriesIndex === 0
                    ? value + " orders"
                    : "৳" + value.toFixed(2);
            },
        },
    },
}));

const dailyOrdersSeries = computed(() => [
    {
        name: "Orders",
        data: currentChartData.value.map((item) => item.orders),
    },
    {
        name: "Revenue",
        data: currentChartData.value.map((item) =>
            parseFloat(item.revenue)
        ),
    },
]);

// Helper for currency formatting
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0,
    }).format(value).replace('BDT', '৳');
};

// Top selling filtering
const topSellingPeriod = ref('month');
const currentTopSelling = computed(() => {
    if (!props.data.topSelling) return [];
    return props.data.topSelling[topSellingPeriod.value] || [];
});

// Calculate progress bar max for current period
const maxSold = computed(() => {
    if (currentTopSelling.value.length === 0) return 1;
    return Math.max(...currentTopSelling.value.map(p => p.total_sold)) || 1;
});
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="bg-[#f8fafc] min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-2xl font-bold text-[#0f172a]">Overview</h1>
            </div>

            <!-- Summary Summary Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Sales Today -->
                <div class="bg-[#0f172a] text-white p-6 rounded-2xl shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm font-medium">Sales Today</p>
                        <h3 class="text-2xl font-bold">{{ formatCurrency(data.summary.salesToday.value) }}</h3>
                    </div>
                </div>

                <!-- Orders -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-[#e0f2fe] rounded-xl flex items-center justify-center text-[#0ea5e9]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <p class="text-gray-500 text-sm font-medium">Orders</p>
                            <span class="w-2 h-2 bg-[#0ea5e9] rounded-full"></span>
                        </div>
                        <h3 class="text-2xl font-bold text-[#0f172a]">{{ data.summary.ordersToday.count }}</h3>
                    </div>
                </div>

                <!-- Pre-Orders -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-[#fff7ed] rounded-xl flex items-center justify-center text-[#f97316]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <p class="text-gray-500 text-sm font-medium">Pre-Orders</p>
                            <span class="w-2 h-2 bg-[#f97316] rounded-full"></span>
                        </div>
                        <h3 class="text-2xl font-bold text-[#0f172a]">{{ data.summary.preOrders.count }}</h3>
                    </div>
                </div>

                <!-- Requests -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-[#f5f3ff] rounded-xl flex items-center justify-center text-[#8b5cf6]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <p class="text-gray-500 text-sm font-medium">Requests</p>
                            <span class="w-2 h-2 bg-[#8b5cf6] rounded-full"></span>
                        </div>
                        <h3 class="text-2xl font-bold text-[#0f172a]">{{ data.summary.requests.count }}</h3>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-8">
                <!-- Top Selling -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-[#0f172a]">Top Selling</h2>
                        <select v-model="topSellingPeriod" class="border-none bg-gray-50 rounded-lg text-sm font-medium text-gray-600 focus:ring-0">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                            <option value="overall">Overall</option>
                        </select>
                    </div>
                    <div class="space-y-6">
                        <div v-for="(product, index) in currentTopSelling" :key="product.id" class="flex items-center">
                            <div class="w-8 text-lg font-bold" :class="index === 0 ? 'text-[#f97316]' : 'text-[#38bdf8]'">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 overflow-hidden mr-4">
                                <img :src="product.feature_image_url" alt="" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-bold text-[#0f172a] line-clamp-1">
                                        {{ product.name }}
                                        <span v-if="product.is_pre_order" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                            Pre-Order
                                        </span>
                                    </h4>
                                    <span class="text-sm font-bold text-gray-500">{{ product.total_sold }} Sold</span>
                                </div>
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000" 
                                         :style="{ width: (product.total_sold / maxSold) * 100 + '%' }"
                                         :class="index === 0 ? 'bg-[#0f172a]' : 'bg-[#0ea5e9]'">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="currentTopSelling.length === 0" class="text-center py-8 text-gray-400">
                            No sales data for this period
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-xl font-bold text-[#0f172a] mb-6">Low Stock Alerts</h2>
                    <div class="space-y-4">
                        <div v-for="product in data.lowStock" :key="product.id" class="flex items-center p-4 border border-gray-50 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 overflow-hidden mr-4">
                                <img :src="product.feature_image_url" alt="" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-grow">
                                <h4 class="font-bold text-[#0f172a]">
                                    {{ product.name }}
                                    <span v-if="product.is_pre_order" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        Pre-Order
                                    </span>
                                </h4>
                                <p class="text-sm text-gray-400">Stock: {{ product.stock }} items left</p>
                            </div>
                            <div class="bg-red-50 text-red-500 px-3 py-1 rounded-full text-xs font-bold">
                                {{ product.stock }} Left
                            </div>
                        </div>
                        <div v-if="data.lowStock.length === 0" class="text-center py-8 text-gray-400">
                            No low stock alerts
                        </div>
                    </div>
                </div>

                <!-- Analytics Graph (Keeping it but styling it) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mt-4">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-[#0f172a]">Sales Analytics</h2>
                        <div class="flex space-x-2">
                            <button @click="salesPeriod = 'week'" 
                                    :class="salesPeriod === 'week' ? 'bg-[#0f172a] text-white' : 'bg-gray-100 text-gray-500'"
                                    class="px-4 py-1 rounded-lg text-sm font-medium transition-all">
                                Week
                            </button>
                            <button @click="salesPeriod = 'month'"
                                    :class="salesPeriod === 'month' ? 'bg-[#0f172a] text-white' : 'bg-gray-100 text-gray-500'"
                                    class="px-4 py-1 rounded-lg text-sm font-medium transition-all">
                                Month
                            </button>
                        </div>
                    </div>
                    <VueApexCharts height="350" :options="dailyOrdersOptions" :series="dailyOrdersSeries" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
