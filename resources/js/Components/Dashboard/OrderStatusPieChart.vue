<script setup>
import { ref, computed, watch } from "vue";
import VueApexCharts from "vue3-apexcharts";
import axios from "axios";
import {
    PieChart as PieIcon,
    Calendar,
    RotateCw,
    ShoppingCart,
    DollarSign,
    TrendingUp,
    Store,
    Globe,
    Layers,
    SlidersHorizontal,
    Sparkles,
} from "lucide-vue-next";

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({
            items: [],
            totalOrders: 0,
            totalSales: 0,
            period: "all",
            startDate: null,
            endDate: null,
            source: "all",
        }),
    },
});

// Active metric: 'count' (Order Count) or 'sales' (Sales Amount)
const activeMetric = ref("count");

// Chart type: 'donut' or 'pie'
const chartType = ref("donut");

// Filter state
const period = ref(props.initialData?.period || "all");
const startDate = ref(props.initialData?.startDate || "");
const endDate = ref(props.initialData?.endDate || "");
const source = ref(props.initialData?.source || "all");

// Data state
const analyticsData = ref(props.initialData || {
    items: [],
    totalOrders: 0,
    totalSales: 0,
    period: "all",
    startDate: null,
    endDate: null,
    source: "all",
});

const isLoading = ref(false);
const showCustomDate = computed(() => period.value === "custom");

// Synchronize if prop changes (e.g. Inertia navigation)
watch(
    () => props.initialData,
    (newVal) => {
        if (newVal) {
            analyticsData.value = newVal;
            if (newVal.period) period.value = newVal.period;
            if (newVal.startDate) startDate.value = newVal.startDate;
            if (newVal.endDate) endDate.value = newVal.endDate;
            if (newVal.source) source.value = newVal.source;
        }
    },
    { deep: true }
);

// Currency formatting helper
const formatCurrency = (val) => {
    return new Intl.NumberFormat("en-BD", {
        style: "currency",
        currency: "BDT",
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
        .format(val || 0)
        .replace("BDT", "৳");
};

const items = computed(() => analyticsData.value?.items || []);
const totalOrders = computed(() => analyticsData.value?.totalOrders || 0);
const totalSales = computed(() => analyticsData.value?.totalSales || 0);

const hasData = computed(() => items.value.length > 0 && (totalOrders.value > 0 || totalSales.value > 0));

// Average order value
const averageOrderValue = computed(() => {
    if (totalOrders.value === 0) return 0;
    return totalSales.value / totalOrders.value;
});

// Top status
const topStatus = computed(() => {
    if (!items.value || items.value.length === 0) return null;
    return items.value[0];
});

// ApexCharts Series
const chartSeries = computed(() => {
    if (!hasData.value) return [];
    if (activeMetric.value === "count") {
        return items.value.map((item) => Number(item.count));
    }
    return items.value.map((item) => Number(item.sales));
});

// ApexCharts Labels & Colors
const chartLabels = computed(() => items.value.map((item) => item.label));
const chartColors = computed(() => items.value.map((item) => item.color));

// ApexCharts Options
const chartOptions = computed(() => {
    return {
        chart: {
            type: chartType.value,
            fontFamily: "inherit",
            animations: {
                enabled: true,
                speed: 450,
                dynamicAnimation: {
                    enabled: true,
                    speed: 350,
                },
            },
            toolbar: {
                show: false,
            },
        },
        labels: chartLabels.value,
        colors: chartColors.value,
        stroke: {
            width: 2,
            colors: ["#ffffff"],
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + "%";
            },
            style: {
                fontSize: "11px",
                fontWeight: 600,
            },
            dropShadow: {
                enabled: false,
            },
        },
        legend: {
            show: true,
            position: "bottom",
            horizontalAlign: "center",
            fontSize: "12px",
            fontWeight: 500,
            markers: {
                width: 10,
                height: 10,
                radius: 12,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 4,
            },
            formatter: function (seriesName, opts) {
                const item = items.value[opts.seriesIndex];
                if (!item) return seriesName;
                if (activeMetric.value === "count") {
                    return `${seriesName} (${item.count})`;
                }
                return `${seriesName} (৳${Math.round(item.sales).toLocaleString()})`;
            },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "72%",
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: "13px",
                            fontWeight: 600,
                            color: "#64748b",
                            offsetY: -4,
                        },
                        value: {
                            show: true,
                            fontSize: "20px",
                            fontWeight: 700,
                            color: "#0f172a",
                            offsetY: 6,
                            formatter: function (val) {
                                if (activeMetric.value === "count") {
                                    return Number(val).toLocaleString();
                                }
                                return "৳" + Math.round(Number(val)).toLocaleString();
                            },
                        },
                        total: {
                            show: true,
                            label: activeMetric.value === "count" ? "Total Orders" : "Total Revenue",
                            color: "#64748b",
                            fontSize: "12px",
                            fontWeight: 600,
                            formatter: function () {
                                if (activeMetric.value === "count") {
                                    return totalOrders.value.toLocaleString() + " Orders";
                                }
                                return formatCurrency(totalSales.value);
                            },
                        },
                    },
                },
            },
        },
        tooltip: {
            theme: "light",
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                const item = items.value[seriesIndex];
                if (!item) return "";
                const val = series[seriesIndex];
                const isCount = activeMetric.value === "count";
                return `
                    <div class="p-3 bg-white shadow-xl rounded-xl border border-gray-100 text-xs font-sans min-w-[190px]">
                        <div class="flex items-center gap-2 mb-2 pb-1.5 border-b border-gray-100">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: ${item.color}"></span>
                            <span class="font-semibold text-gray-900">${item.label}</span>
                        </div>
                        <div class="space-y-1.5 text-gray-600">
                            <div class="flex justify-between items-center">
                                <span>Orders:</span>
                                <span class="font-semibold text-gray-900">${item.count} <span class="text-gray-400 font-normal">(${item.count_percentage}%)</span></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Sales:</span>
                                <span class="font-semibold text-gray-900">${formatCurrency(item.sales)} <span class="text-gray-400 font-normal">(${item.sales_percentage}%)</span></span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-50 text-[11px]">
                                <span>Avg Order:</span>
                                <span class="font-medium text-emerald-600">${formatCurrency(item.avg_order_value)}</span>
                            </div>
                        </div>
                    </div>
                `;
            },
        },
    };
});

// Fetch filtered status data from server
const fetchFilteredData = async () => {
    isLoading.value = true;
    try {
        const params = {
            period: period.value,
            source: source.value,
        };
        if (period.value === "custom") {
            if (startDate.value) params.start_date = startDate.value;
            if (endDate.value) params.end_date = endDate.value;
        }

        const endpoint = typeof route === "function" ? route("admin.dashboard.status-chart") : "/admin/dashboard/status-chart-data";
        const response = await axios.get(endpoint, { params });
        analyticsData.value = response.data;
    } catch (error) {
        console.error("Failed to load status chart data:", error);
    } finally {
        isLoading.value = false;
    }
};

// Preset filter selection
const selectPeriod = (p) => {
    period.value = p;
    if (p !== "custom") {
        fetchFilteredData();
    }
};

// Reset to all time
const resetFilter = () => {
    period.value = "all";
    startDate.value = "";
    endDate.value = "";
    source.value = "all";
    fetchFilteredData();
};
</script>

<template>
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 transition-all duration-300 relative overflow-hidden">
        <!-- Top Loading Overlay -->
        <div
            v-if="isLoading"
            class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center gap-2 transition-all"
        >
            <div class="w-8 h-8 border-3 border-gray-200 border-t-[#0f172a] rounded-full animate-spin"></div>
            <span class="text-xs font-medium text-gray-600">Updating status analytics...</span>
        </div>

        <!-- Card Header: Title & View Switches -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <PieIcon class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base sm:text-lg font-semibold text-[#0f172a]">
                            Order Status Analytics
                        </h2>
                        <span
                            v-if="hasData"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"
                        >
                            {{ totalOrders }} Orders • {{ formatCurrency(totalSales) }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        Status-wise distribution of order volume and generated revenue
                    </p>
                </div>
            </div>

            <!-- Controls: Metric Toggle & Chart Type Toggle -->
            <div class="flex items-center gap-2 flex-wrap self-start lg:self-auto">
                <!-- Metric Selector: Order Count vs Sales Amount -->
                <div class="inline-flex p-1 bg-gray-100 rounded-xl text-xs font-medium">
                    <button
                        type="button"
                        @click="activeMetric = 'count'"
                        :class="[
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all',
                            activeMetric === 'count'
                                ? 'bg-[#0f172a] text-white shadow-sm'
                                : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        <ShoppingCart class="w-3.5 h-3.5" />
                        <span>Order Count</span>
                    </button>
                    <button
                        type="button"
                        @click="activeMetric = 'sales'"
                        :class="[
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all',
                            activeMetric === 'sales'
                                ? 'bg-[#0f172a] text-white shadow-sm'
                                : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        <DollarSign class="w-3.5 h-3.5" />
                        <span>Sales Amount</span>
                    </button>
                </div>

                <!-- Chart Type Toggle: Donut vs Pie -->
                <div class="inline-flex p-1 bg-gray-100 rounded-xl text-xs font-medium">
                    <button
                        type="button"
                        @click="chartType = 'donut'"
                        :class="[
                            'px-2.5 py-1.5 rounded-lg transition-all',
                            chartType === 'donut'
                                ? 'bg-white text-gray-900 shadow-sm font-semibold'
                                : 'text-gray-500 hover:text-gray-800',
                        ]"
                        title="Donut Chart View"
                    >
                        Donut
                    </button>
                    <button
                        type="button"
                        @click="chartType = 'pie'"
                        :class="[
                            'px-2.5 py-1.5 rounded-lg transition-all',
                            chartType === 'pie'
                                ? 'bg-white text-gray-900 shadow-sm font-semibold'
                                : 'text-gray-500 hover:text-gray-800',
                        ]"
                        title="Pie Chart View"
                    >
                        Pie
                    </button>
                </div>

                <!-- Refresh Button -->
                <button
                    type="button"
                    @click="fetchFilteredData"
                    :disabled="isLoading"
                    class="p-2 text-gray-400 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-100 transition-colors"
                    title="Refresh data"
                >
                    <RotateCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="mt-4 pt-1">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-wrap">
                <!-- Preset Period Chips -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-medium text-gray-400 mr-1 flex items-center gap-1">
                        <SlidersHorizontal class="w-3.5 h-3.5" />
                        Filter:
                    </span>
                    <button
                        v-for="p in [
                            { key: 'all', label: 'All Time' },
                            { key: 'today', label: 'Today' },
                            { key: 'yesterday', label: 'Yesterday' },
                            { key: 'week', label: 'This Week' },
                            { key: 'month', label: 'This Month' },
                            { key: 'year', label: 'This Year' },
                            { key: 'custom', label: 'Custom' },
                        ]"
                        :key="p.key"
                        type="button"
                        @click="selectPeriod(p.key)"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-lg font-medium transition-all duration-200',
                            period === p.key
                                ? 'bg-[#0f172a] text-white shadow-sm ring-1 ring-[#0f172a]'
                                : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200/60',
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </div>

                <!-- Source Filter (All / Website / POS) -->
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 hidden md:inline">Source:</span>
                    <select
                        v-model="source"
                        @change="fetchFilteredData"
                        class="text-xs bg-gray-50 hover:bg-gray-100 border border-gray-200/70 rounded-lg py-1.5 px-2.5 text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    >
                        <option value="all">All Sources</option>
                        <option value="website">Website Orders</option>
                        <option value="pos">POS Orders</option>
                    </select>
                </div>
            </div>

            <!-- Custom Date Range Sub-Bar -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="showCustomDate"
                    class="mt-3 p-3 bg-gray-50/80 rounded-xl border border-gray-200/60 flex flex-wrap items-center gap-3"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">From:</span>
                        <div class="relative">
                            <input
                                type="date"
                                v-model="startDate"
                                class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">To:</span>
                        <div class="relative">
                            <input
                                type="date"
                                v-model="endDate"
                                class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="fetchFilteredData"
                        class="px-4 py-1.5 bg-[#0f172a] hover:bg-black text-white text-xs font-medium rounded-lg shadow-sm transition-all active:scale-95"
                    >
                        Apply Filter
                    </button>
                    <button
                        type="button"
                        @click="resetFilter"
                        class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        Reset
                    </button>
                </div>
            </transition>
        </div>

        <!-- Main Chart & Breakdown Section -->
        <div class="mt-6">
            <!-- When Data Exists -->
            <div v-if="hasData" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Pie / Donut Chart Column -->
                <div class="lg:col-span-6 xl:col-span-5 flex flex-col items-center justify-center">
                    <div class="w-full max-w-[380px] py-2">
                        <VueApexCharts
                            :key="`${chartType}-${activeMetric}-${period}-${items.length}`"
                            height="340"
                            :options="chartOptions"
                            :series="chartSeries"
                        />
                    </div>
                </div>

                <!-- Status Breakdown List Column -->
                <div class="lg:col-span-6 xl:col-span-7">
                    <div class="border border-gray-100 rounded-xl overflow-hidden bg-gray-50/40">
                        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <span>Status</span>
                            <div class="flex items-center gap-6">
                                <span class="w-20 text-right">Orders</span>
                                <span class="w-28 text-right">Revenue</span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100 max-h-[320px] overflow-y-auto">
                            <div
                                v-for="item in items"
                                :key="item.status"
                                class="px-4 py-2.5 hover:bg-white transition-colors flex flex-col gap-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span
                                            class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: item.color }"
                                        ></span>
                                        <span class="font-medium text-gray-900 truncate">
                                            {{ item.label }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-6 flex-shrink-0">
                                        <span class="w-20 text-right text-gray-700 font-medium">
                                            {{ item.count }}
                                            <span class="text-[10px] text-gray-400 font-normal">({{ item.count_percentage }}%)</span>
                                        </span>
                                        <span class="w-28 text-right font-semibold text-[#0f172a]">
                                            {{ formatCurrency(item.sales) }}
                                            <span class="text-[10px] text-gray-400 font-normal">({{ item.sales_percentage }}%)</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Progress Proportion Bar -->
                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{
                                            width: (activeMetric === 'count' ? item.count_percentage : item.sales_percentage) + '%',
                                            backgroundColor: item.color,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State (No orders for selected filter) -->
            <div
                v-else
                class="py-12 px-4 flex flex-col items-center justify-center text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200"
            >
                <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-3">
                    <Calendar class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900">No Orders Found</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-sm">
                    There are no orders recorded for the selected period
                    <span v-if="period !== 'all'" class="font-medium text-gray-700">({{ period }})</span>.
                </p>
                <button
                    v-if="period !== 'all' || source !== 'all'"
                    type="button"
                    @click="resetFilter"
                    class="mt-4 px-3.5 py-1.5 bg-[#0f172a] text-white text-xs font-medium rounded-lg hover:bg-black transition-all"
                >
                    Reset to All Time
                </button>
            </div>
        </div>

        <!-- Bottom Insights Summary Row -->
        <div v-if="hasData" class="mt-6 pt-4 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider block">Total Volume</span>
                <span class="text-base font-semibold text-[#0f172a] mt-0.5 block">{{ totalOrders.toLocaleString() }} Orders</span>
            </div>
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider block">Total Revenue</span>
                <span class="text-base font-semibold text-emerald-600 mt-0.5 block">{{ formatCurrency(totalSales) }}</span>
            </div>
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider block">Average Order</span>
                <span class="text-base font-semibold text-indigo-600 mt-0.5 block">{{ formatCurrency(averageOrderValue) }}</span>
            </div>
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider block">Top Status</span>
                <span class="text-base font-semibold text-[#0f172a] mt-0.5 block truncate">
                    {{ topStatus?.label || 'N/A' }}
                    <span v-if="topStatus" class="text-xs text-gray-400 font-normal">({{ topStatus.count }})</span>
                </span>
            </div>
        </div>
    </div>
</template>
