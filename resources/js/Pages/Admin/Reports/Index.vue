<template>
    <Head title="Reports Dashboard" />
    <AdminLayout>
        <div class="reports-dashboard p-6 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Reports Dashboard
                </h1>
                <p class="text-gray-600">
                    Track your business performance and inventory insights
                </p>
            </div>


            <!-- Stats Cards with Premium Glassmorphism -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Products -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Products</p>
                            <h3 class="text-3xl font-bold text-gray-900 leading-none">{{ totalProducts }}</h3>
                        </div>
                        <div class="p-3 rounded-2xl bg-blue-50 text-blue-600 transition-all group-hover:bg-blue-600 group-hover:text-white shadow-sm">
                            <Package :size="24" stroke-width="2.5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-medium text-blue-600 bg-blue-50/50 w-fit px-2 py-1 rounded-full">
                        <TrendingUp :size="12" class="mr-1" />
                        <span>Inventory Active</span>
                    </div>
                </div>

                <!-- Total Stock -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">In Stock</p>
                            <h3 class="text-3xl font-bold text-gray-900 leading-none">{{ totalStock }}</h3>
                        </div>
                        <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 transition-all group-hover:bg-emerald-600 group-hover:text-white shadow-sm">
                            <Layers :size="24" stroke-width="2.5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-medium text-emerald-600 bg-emerald-50/50 w-fit px-2 py-1 rounded-full">
                        <CheckCircle :size="12" class="mr-1" />
                        <span>Ready to Ship</span>
                    </div>
                </div>

                <!-- Total Sold -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Sold</p>
                            <h3 class="text-3xl font-bold text-gray-900 leading-none">{{ totalSold }}</h3>
                        </div>
                        <div class="p-3 rounded-2xl bg-amber-50 text-amber-600 transition-all group-hover:bg-amber-600 group-hover:text-white shadow-sm">
                            <ShoppingCart :size="24" stroke-width="2.5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-medium text-amber-600 bg-amber-50/50 w-fit px-2 py-1 rounded-full">
                        <TrendingUp :size="12" class="mr-1" />
                        <span>Sales Velocity: {{ overallSellThroughRate.toFixed(1) }}%</span>
                    </div>
                </div>

                <!-- Total Value -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Inventory Value</p>
                            <h3 class="text-3xl font-bold text-gray-900 leading-none">BDT {{ totalValue.toLocaleString() }}</h3>
                        </div>
                        <div class="p-3 rounded-2xl bg-purple-50 text-purple-600 transition-all group-hover:bg-purple-600 group-hover:text-white shadow-sm">
                            <DollarSign :size="24" stroke-width="2.5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-medium text-purple-600 bg-purple-50/50 w-fit px-2 py-1 rounded-full">
                        <TrendingUp :size="12" class="mr-1" />
                        <span>Asset Value Peak</span>
                    </div>
                </div>
            </div>

            <!-- Visualization Section (Phase 2 Additions) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Stock Distribution Chart -->
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Inventory Distribution</h3>
                        <Layers class="text-gray-400" :size="20" />
                    </div>
                    <div class="h-[300px]">
                        <VueApexCharts 
                            type="donut" 
                            height="300" 
                            :options="inventoryPieOptions" 
                            :series="inventoryPieSeries" 
                        />
                    </div>
                </div>

                <!-- Sell-Through Performance Chart -->
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Category Efficiency</h3>
                        <TrendingUp class="text-gray-400" :size="20" />
                    </div>
                    <div class="h-[300px]">
                        <VueApexCharts 
                            type="bar" 
                            height="300" 
                            :options="sellThroughBarOptions" 
                            :series="sellThroughBarSeries" 
                        />
                    </div>
                </div>
            </div>


            <!-- Top Performers Table -->
            <div class="chart-container mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 uppercase tracking-tight">Top Performing Products</h3>
                    <div class="px-3 py-1 bg-green-50 text-green-600 rounded-full text-xs font-bold">High Velocity</div>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-gray-50">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="text-left py-4 px-6 font-medium text-gray-500 lowercase tracking-wider">Product</th>
                                <th class="text-left py-4 px-6 font-medium text-gray-500 lowercase tracking-wider">Category</th>
                                <th class="text-center py-4 px-6 font-medium text-gray-500 lowercase tracking-wider">Stock</th>
                                <th class="text-center py-4 px-6 font-medium text-gray-500 lowercase tracking-wider">Sold</th>
                                <th class="text-right py-4 px-6 font-medium text-gray-500 lowercase tracking-wider">Sell-Through</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="product in topPerformers" :key="product.id" class="hover:bg-blue-50/30 transition-colors group">
                                <td class="py-4 px-6 font-semibold text-gray-900 group-hover:text-blue-600">{{ product.name }}</td>
                                <td class="py-4 px-6 text-gray-500">{{ product.category }}</td>
                                <td class="py-4 px-6 text-center tabular-nums">{{ product.stock }}</td>
                                <td class="py-4 px-6 text-center tabular-nums font-semibold">{{ product.sold }}</td>
                                <td class="py-4 px-6 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold shadow-sm"
                                        :class="product.sell_through_rate > 15 ? 'bg-green-500 text-white' : 'bg-amber-500 text-white'">
                                        {{ product.sell_through_rate.toFixed(1) }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>




                        <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Filters
                </h2>

                <form @submit.prevent="applyFilters" class="space-y-4">
                    <!-- Time Period Filter -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Time Period</label
                            >
                            <select
                                v-model="filters.period"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="yearly">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Order Status</label
                            >
                            <select
                                v-model="filters.status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="on_hold">On Hold</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Payment Status</label
                            >
                            <select
                                v-model="filters.payment_status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Payments</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Per Page</label
                            >
                            <select
                                v-model="filters.per_page"
                                @change="applyFilters"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Date Range -->
                    <div
                        v-if="filters.period === 'custom'"
                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >From Date</label
                            >
                            <input
                                type="date"
                                v-model="filters.date_from"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >To Date</label
                            >
                            <input
                                type="date"
                                v-model="filters.date_to"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Additional Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Customer Search</label
                            >
                            <input
                                type="text"
                                v-model="filters.customer_search"
                                placeholder="Search by name, email, or phone"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Min Total</label
                            >
                            <input
                                type="number"
                                v-model="filters.min_total"
                                placeholder="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Max Total</label
                            >
                            <input
                                type="number"
                                v-model="filters.max_total"
                                placeholder="1000"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </form>
            </div>




            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Orders</h3>
                    <div class="flex gap-2">
                        <button
                            @click="exportToCSV"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors text-sm"
                        >
                            {{
                                selectedOrders.length > 0
                                    ? `Export Selected (${selectedOrders.length}) to CSV`
                                    : "Export All to CSV"
                            }}
                        </button>
                        <button
                            @click="exportToPDF"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors text-sm"
                        >
                            Export to PDF
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                </th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-500 uppercase text-xs tracking-wider">Order #</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-500 uppercase text-xs tracking-wider">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-500 uppercase text-xs tracking-wider">Customer</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-500 uppercase text-xs tracking-wider">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-500 uppercase text-xs tracking-wider">Payment</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-500 uppercase text-xs tracking-wider">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <td class="py-3 px-4">
                                    <input
                                        type="checkbox"
                                        :value="order.id"
                                        v-model="selectedOrders"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="py-3 px-4 font-semibold text-blue-600">#{{ order.order_number }}</td>
                                <td class="py-3 px-4 text-xs text-gray-500 font-normal whitespace-nowrap">{{ formatDate(order.date) }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-900 leading-tight">{{ order.customer_name || order.customer?.name }}</div>
                                    <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-tighter">{{ order.customer_phone || order.customer?.phone }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest shadow-sm" :class="getStatusColor(order.status)">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest shadow-sm" :class="getPaymentStatusColor(order.payment_status)">
                                        {{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 tabular-nums text-sm">BDT {{ order.total }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-100 bg-gray-50/50">
                                <td colspan="3" class="py-4 px-6">
                                    <div class="space-y-1">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Revenue Overview</p>
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                                <span class="text-sm font-medium text-gray-600">Page Total: <span class="text-gray-900 font-semibold">BDT {{ calculateTotalAmount().toLocaleString() }}</span></span>
                                            </div>
                                            <div class="flex items-center gap-2 border-l border-gray-200 pl-4">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                <span class="text-sm font-medium text-gray-600">Grand Total: <span class="text-gray-900 font-bold text-lg">BDT {{ grandTotalRevenue.toLocaleString() }}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td colspan="4" class="py-4 px-6 text-right">
                                    <p class="text-xs text-gray-400 font-medium italic">Calculated across {{ orders.total }} matching records</p>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ orders.from }} to {{ orders.to }} of
                        {{ orders.total }} results
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-for="link in orders.links"
                            :key="link.label"
                            @click="changePage(link.url)"
                            :disabled="!link.url"
                            class="px-3 py-1 text-sm border rounded-md"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : !link.url
                                    ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                            "
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, reactive, onMounted, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { jsPDF } from "jspdf";
import VueApexCharts from "vue3-apexcharts";
import { 
    Package, 
    ShoppingCart, 
    TrendingUp, 
    DollarSign, 
    Filter, 
    Download, 
    FileText,
    AlertCircle,
    CheckCircle,
    Layers
} from "lucide-vue-next";

const props = defineProps({
    orders: Object,
    grandTotalRevenue: Number,
    totalProducts: Number,
    totalVariations: Number,
    totalStock: Number,
    totalSold: Number,
    totalValue: Number,
    overallSellThroughRate: Number,
    lowStockProducts: Array,
    productsByCategory: Object,
    topPerformers: Array,
});

const filters = reactive({
    period: "all",
    status: "",
    payment_status: "",
    date_from: "",
    date_to: "",
    customer_search: "",
    min_total: "",
    max_total: "",
    per_page: 10,
});

const selectedOrders = ref([]);
const selectAll = ref(false);

// Chart Configurations
const inventoryPieOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
    labels: Object.keys(props.productsByCategory),
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
    legend: { position: 'bottom' },
    plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Categories' } } } } },
    dataLabels: { enabled: false }
}));

const inventoryPieSeries = computed(() => 
    Object.values(props.productsByCategory).map(cat => cat.stock)
);

const sellThroughBarOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, horizontal: true } },
    colors: ['#3b82f6'],
    xaxis: { categories: Object.keys(props.productsByCategory) },
    title: { text: 'Sell-Through Rate (%)', align: 'center', style: { fontSize: '14px', fontWeight: 600 } },
    grid: { borderColor: '#f1f1f1' }
}));

const sellThroughBarSeries = computed(() => [{
    name: 'Sell-Through Rate',
    data: Object.values(props.productsByCategory).map(cat => parseFloat(cat.sell_through_rate.toFixed(1)))
}]);

// Initialize filters with URL parameters on mount
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    Object.keys(filters).forEach((key) => {
        const value = urlParams.get(key);
        if (value !== null) {
            filters[key] = value;
        }
    });
});

// Toggle select all orders
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedOrders.value = props.orders.data.map((order) => order.id);
    } else {
        selectedOrders.value = [];
    }
};

// Calculate total amount of selected orders
const calculateTotalAmount = () => {
    const ordersToSum =
        selectedOrders.value.length > 0
            ? props.orders.data.filter((order) =>
                  selectedOrders.value.includes(order.id)
              )
            : props.orders.data;
    return ordersToSum.reduce(
        (total, order) => total + parseFloat(order.total),
        0
    );
};

// Export to CSV function
const exportToCSV = () => {
    // If no orders selected, export ALL data across ALL pages via backend
    if (selectedOrders.value.length === 0) {
        const params = new URLSearchParams(filters).toString();
        window.location.href = route('admin.reports.export') + '?' + params;
        return;
    }

    // Otherwise, export only SELECTED data from current page
    const selectedData = props.orders.data.filter((order) =>
        selectedOrders.value.includes(order.id)
    );

    if (selectedData.length === 0) {
        alert("No orders to export");
        return;
    }

    // CSV Headers
    const headers = [
        "Order #",
        "Date",
        "Customer Name",
        "Customer Phone",
        "Status",
        "Payment Status",
        "Total Amount",
    ];

    // Convert data to CSV format
    let csvContent = [
        // Add headers
        headers.join(","),
        // Add data rows
        ...selectedData.map((order) =>
            [
                `"#${order.order_number}"`,
                `"${formatDate(order.date)}"`,
                `"${order.customer_name || order.customer?.name}"`,
                `"${order.customer_phone || order.customer?.phone}"`,
                `"${order.status}"`,
                `"${order.payment_status}"`,
                `"BDT ${order.total}"`,
            ].join(",")
        ),
    ].join("\n");

    // Add Grand Total row for context (Big Tech Standard)
    if (selectedOrders.value.length === 0) {
        csvContent += `\n\n"","","","","","GRAND TOTAL (All Filtered Pages)","BDT ${props.grandTotalRevenue}"`;
    }

    // Create and download CSV file
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");

    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);

        // Generate filename based on selection
        const filename =
            selectedOrders.value.length > 0
                ? `selected_orders_${selectedOrders.value.length}_${
                      new Date().toISOString().split("T")[0]
                  }.csv`
                : `all_orders_${selectedData.length}_${
                      new Date().toISOString().split("T")[0]
                  }.csv`;

        link.setAttribute("download", filename);
        link.style.visibility = "hidden";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

// Export to PDF function
const exportToPDF = () => {
    // If no orders selected, export ALL data across ALL pages via backend
    if (selectedOrders.value.length === 0) {
        const params = new URLSearchParams(filters).toString();
        window.location.href = route('admin.reports.export-pdf') + '?' + params;
        return;
    }

    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    let y = 20;

    // Select only SELECTED data from current page
    const selectedData = props.orders.data.filter((order) =>
        selectedOrders.value.includes(order.id)
    );

    // Header
    doc.setFont("helvetica", "bold");
    doc.setFontSize(15);
    doc.setTextColor(0, 0, 0); // Black text
    doc.text("Orders Report", pageWidth / 2, y, { align: "center" });
    y += 10;

    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    doc.text(
        `Generated on: ${new Date().toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        })}`,
        pageWidth / 2,
        y,
        { align: "center" }
    );
    y += 20;

    // Table setup
    const headers = [
        "Order #",
        "Date",
        "Customer",
        "Status",
        "Payment",
        "Total",
    ];
    const columnWidths = [25, 35, 55, 25, 25, 20];
    const startX = margin;

    const drawTableRow = (rowData, isHeader = false, rowHeight = 10) => {
        let currentX = startX;

        // Draw each cell
        rowData.forEach((cellData, index) => {
            const cellWidth = columnWidths[index];

            // IMPORTANT: Set colors BEFORE drawing each cell
            doc.setFillColor(255, 255, 255); // White background
            doc.setDrawColor(0, 0, 0); // Black border
            doc.setTextColor(0, 0, 0); // Black text

            if (isHeader) {
                doc.setFont("helvetica", "bold");
            } else {
                doc.setFont("helvetica", "normal");
            }

            // Fill cell with white background and draw border
            doc.rect(currentX, y, cellWidth, rowHeight, "FD");

            // Add text with proper padding
            const textX = currentX + 2;
            const textY = y + 7;

            if (typeof cellData === "string" && cellData.includes("\n")) {
                // Handle multi-line text
                const lines = cellData.split("\n");
                lines.forEach((line, lineIndex) => {
                    doc.text(line, textX, textY + lineIndex * 4, {
                        maxWidth: cellWidth - 4,
                    });
                });
            } else {
                doc.text(String(cellData), textX, textY, {
                    maxWidth: cellWidth - 4,
                });
            }

            currentX += cellWidth;
        });

        y += rowHeight;
    };

    const checkPageBreak = (requiredSpace = 20) => {
        if (y > pageHeight - requiredSpace) {
            doc.addPage();
            y = 20;
            // Redraw headers on new page
            drawTableRow(headers, true);
            return true;
        }
        return false;
    };

    // Draw table headers
    drawTableRow(headers, true);

    // Draw table rows
    selectedData.forEach((order, index) => {
        checkPageBreak(15);

        const row = [
            `#${order.order_number}`,
            formatDate(order.date),
            `${order.customer_name || order.customer?.name}\n${order.customer_phone || order.customer?.phone}`,
            order.status,
            order.payment_status,
            `BDT ${order.total}`,
        ];

        drawTableRow(row, false, 12);
    });

    // Total Amount section
    y += 10;
    checkPageBreak(25);

    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0); // Black text
    doc.setFillColor(240, 240, 240); // Light gray background for total
    doc.setDrawColor(0, 0, 0); // Black border
    const tableWidth = columnWidths.reduce((a, b) => a + b, 0);
    doc.rect(startX, y, tableWidth, 20, "FD");
    doc.text(
        `Current Page Total: BDT ${calculateTotalAmount().toLocaleString()}`,
        startX + 5,
        y + 8
    );
    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text(
        `GRAND TOTAL (ALL PAGES): BDT ${props.grandTotalRevenue.toLocaleString()}`,
        startX + 5,
        y + 16
    );

    // Footer
    doc.setFont("helvetica", "italic");
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100); // Gray text for footer
    doc.text(
        "Generated by Inventory Management System",
        pageWidth / 2,
        pageHeight - 10,
        { align: "center" }
    );

    // Save PDF
    doc.save(`orders_report_${new Date().toISOString().split("T")[0]}.pdf`);
};

const applyFilters = () => {
    const params = { ...filters };

    // Reset dates if not custom to ensure 'All Time' works correctly
    if (filters.period !== "custom") {
        params.date_from = null;
        params.date_to = null;
    }

    if (filters.period === "today") {
        const today = new Date().toISOString().split("T")[0];
        params.date_from = today;
        params.date_to = today;
    } else if (filters.period === "weekly") {
        const today = new Date();
        const weekStart = new Date(
            today.setDate(today.getDate() - today.getDay())
        );
        const weekEnd = new Date(
            today.setDate(today.getDate() - today.getDay() + 6)
        );
        params.date_from = weekStart.toISOString().split("T")[0];
        params.date_to = weekEnd.toISOString().split("T")[0];
    } else if (filters.period === "monthly") {
        const today = new Date();
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        params.date_from = monthStart.toISOString().split("T")[0];
        params.date_to = monthEnd.toISOString().split("T")[0];
    } else if (filters.period === "yearly") {
        const today = new Date();
        const yearStart = new Date(today.getFullYear(), 0, 1);
        const yearEnd = new Date(today.getFullYear(), 11, 31);
        params.date_from = yearStart.toISOString().split("T")[0];
        params.date_to = yearEnd.toISOString().split("T")[0];
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

    router.get(route("admin.reports.generateReport"), params, {
        preserveState: false,
        preserveScroll: false,
    });
};

const resetFilters = () => {
    Object.keys(filters).forEach((key) => {
        if (key === "per_page") {
            filters[key] = 10;
        } else if (key === "period") {
            filters[key] = "all";
        } else {
            filters[key] = "";
        }
    });
    selectedOrders.value = [];
    selectAll.value = false;

    router.get(
        route("admin.reports.generateReport"),
        {},
        {
            preserveState: false,
            preserveScroll: false,
        }
    );
};

const changePage = (url) => {
    if (url) {
        selectedOrders.value = [];
        selectAll.value = false;
        router.get(
            url,
            {},
            {
                preserveState: false,
                preserveScroll: false,
            }
        );
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-100 text-yellow-800",
        processing: "bg-blue-100 text-blue-800",
        shipped: "bg-purple-100 text-purple-800",
        delivered: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
        on_hold: "bg-teal-100 text-teal-800",
        confirmed: "bg-indigo-100 text-indigo-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getPaymentStatusColor = (status) => {
    const colors = {
        paid: "bg-green-100 text-green-800",
        unpaid: "bg-red-100 text-red-800",
        partial: "bg-yellow-100 text-yellow-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.reports-dashboard {
    font-family: 'Inter', sans-serif;
}

.stat-card {
    @apply bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition-all duration-300;
    backdrop-filter: blur(10px);
}

.stat-card:hover {
    @apply shadow-xl border-blue-100 -translate-y-1;
}

.chart-container {
    @apply bg-white p-8 rounded-3xl border border-gray-100 shadow-sm transition-all duration-300;
}

.chart-container:hover {
    @apply shadow-lg border-blue-50;
}

/* Glassmorphism utility */
.glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Custom transitions */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
