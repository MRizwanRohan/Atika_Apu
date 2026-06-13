<template>
    <Head title="Reports Dashboard" />
    <AdminLayout>
        <div class="reports-dashboard p-4 bg-gray-50/50 min-h-screen">
            <!-- Header -->
            <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-2">
                <div>
                    <h1 class="text-lg font-medium text-gray-800">
                        Reports Dashboard
                    </h1>
                    <p class="text-xs text-gray-500">
                        Track business performance and inventory insights
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="filterToday"
                        class="px-3 py-1.5 bg-orange-600 text-white rounded text-xs transition-all flex items-center gap-2 hover:bg-orange-700"
                    >
                        <TrendingUp :size="14" />
                        Today
                    </button>
                </div>
            </div>


            <!-- Source-wise Performance Summary -->
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="source in sourceStats" 
                        :key="source.source_name"
                        class="bg-white p-4 rounded-lg border border-gray-100 shadow-[0_1px_2px_rgba(0,0,0,0.05)] relative overflow-hidden group"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg" :class="getSourceBadgeClass(source.source_name)">
                                    <component :is="getSourceIcon(source.source_name)" :size="16" />
                                </div>
                                <div>
                                    <h4 class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5">{{ source.source_name }}</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-gray-600">{{ source.total_orders }} Orders</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium text-blue-600 leading-none">{{ getSuccessRate(source).toFixed(0) }}%</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full h-1 bg-gray-50 rounded-full overflow-hidden mb-4">
                            <div 
                                class="h-full bg-blue-500 rounded-full transition-all duration-1000 ease-out" 
                                :style="{ width: getSuccessRate(source) + '%' }"
                            ></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-emerald-50/50 p-2 rounded-md border border-emerald-100/50">
                                <p class="text-[8px] text-emerald-600 uppercase tracking-wider mb-1">Delivered</p>
                                <p class="text-base font-medium text-gray-800 leading-none">{{ source.delivered_count }}</p>
                                <p class="text-[9px] text-emerald-700 mt-1">৳{{ source.delivered_revenue.toLocaleString() }}</p>
                            </div>
                            
                            <div class="bg-rose-50/50 p-2 rounded-md border border-rose-100/50">
                                <p class="text-[8px] text-rose-600 uppercase tracking-wider mb-1">Lost</p>
                                <div class="flex items-center gap-2">
                                    <div>
                                        <p class="text-[8px] text-gray-400">Can: {{ source.cancelled_count }}</p>
                                    </div>
                                    <div class="w-px h-3 bg-gray-200"></div>
                                    <div>
                                        <p class="text-[8px] text-gray-400">Ret: {{ source.returned_count }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Products -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Total Products</p>
                            <h3 class="text-lg font-medium text-gray-800 leading-none">{{ totalProducts }}</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-blue-50 text-blue-600">
                            <Package :size="18" />
                        </div>
                    </div>
                </div>

                <!-- Total Stock -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">In Stock</p>
                            <h3 class="text-lg font-medium text-gray-800 leading-none">{{ totalStock }}</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                            <Layers :size="18" />
                        </div>
                    </div>
                </div>

                <!-- Total Sold -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Total Sold</p>
                            <h3 class="text-lg font-medium text-gray-800 leading-none">{{ totalSold }}</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                            <ShoppingCart :size="18" />
                        </div>
                    </div>
                </div>

                <!-- Total Value -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Stock Value</p>
                            <h3 class="text-lg font-medium text-gray-800 leading-none">৳{{ totalValue.toLocaleString() }}</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-purple-50 text-purple-600">
                            <DollarSign :size="18" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visualization Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-800 uppercase tracking-wider">Inventory Distribution</h3>
                        <Layers class="text-gray-400" :size="16" />
                    </div>
                    <div class="h-[220px]">
                        <VueApexCharts 
                            type="donut" 
                            height="220" 
                            :options="inventoryPieOptions" 
                            :series="inventoryPieSeries" 
                        />
                    </div>
                </div>

                <div class="chart-container">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-800 uppercase tracking-wider">Category Efficiency</h3>
                        <TrendingUp class="text-gray-400" :size="16" />
                    </div>
                    <div class="h-[220px]">
                        <VueApexCharts 
                            type="bar" 
                            height="220" 
                            :options="sellThroughBarOptions" 
                            :series="sellThroughBarSeries" 
                        />
                    </div>
                </div>
            </div>


            <!-- Top Performers Table -->
            <div class="chart-container mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-800 uppercase tracking-wider">Top Performing Products</h3>
                </div>
                <div class="overflow-x-auto border-t border-gray-50 pt-2">
                    <table class="w-full text-[11px]">
                        <thead>
                            <tr class="text-gray-400 uppercase tracking-tighter">
                                <th class="text-left py-2 px-2 font-normal">Product</th>
                                <th class="text-left py-2 px-2 font-normal">Category</th>
                                <th class="text-center py-2 px-2 font-normal">Stock</th>
                                <th class="text-center py-2 px-2 font-normal">Sold</th>
                                <th class="text-right py-2 px-2 font-normal">Success</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="product in topPerformers" :key="product.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-2 px-2 text-gray-700">{{ product.name }}</td>
                                <td class="py-2 px-2 text-gray-400">{{ product.category }}</td>
                                <td class="py-2 px-2 text-center tabular-nums">{{ product.stock }}</td>
                                <td class="py-2 px-2 text-center tabular-nums font-medium">{{ product.sold }}</td>
                                <td class="py-2 px-2 text-right">
                                    <span class="text-[10px] tabular-nums" :class="product.sell_through_rate > 15 ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ product.sell_through_rate.toFixed(1) }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>




            <!-- Filters Section -->
            <div class="bg-white rounded-lg border border-gray-100 p-4 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-medium text-gray-800 uppercase tracking-wider">
                        Filters
                    </h2>
                </div>

                <form @submit.prevent="applyFilters" class="space-y-3">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Period</label>
                            <select v-model="filters.period" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="yearly">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Status</label>
                            <select v-model="filters.status" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30">
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
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Payment</label>
                            <select v-model="filters.payment_status" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30">
                                <option value="">All Payments</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Source</label>
                            <select v-model="filters.source" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30">
                                <option value="">All Sources</option>
                                <option value="website">Website</option>
                                <option value="facebook">Facebook</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="phone_call">Phone Call</option>
                                <option value="telegram">Telegram</option>
                                <option value="tiktok">TikTok</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Delivery</label>
                            <select v-model="filters.delivery_status" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30">
                                <option value="">All Delivery</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="returned">Returned</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Per Page</label>
                            <select v-model="filters.per_page" @change="applyFilters" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Date Range -->
                    <div v-if="filters.period === 'custom'" class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">From</label>
                            <input type="date" v-model="filters.date_from" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30" />
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">To</label>
                            <input type="date" v-model="filters.date_to" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30" />
                        </div>
                    </div>

                    <!-- Additional Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase mb-1">Customer</label>
                            <input type="text" v-model="filters.customer_search" placeholder="Name, email, or phone" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] text-gray-400 uppercase mb-1">Min BDT</label>
                                <input type="number" v-model="filters.min_total" placeholder="0" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30" />
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-400 uppercase mb-1">Max BDT</label>
                                <input type="number" v-model="filters.max_total" placeholder="1000" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 bg-gray-50/30" />
                            </div>
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex gap-2 justify-end">
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded text-xs transition-colors hover:bg-blue-700">
                            Apply
                        </button>
                        <button type="button" @click="resetFilters" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded text-xs transition-colors hover:bg-gray-300">
                            Reset
                        </button>
                    </div>
                </form>
            </div>




            <!-- Orders Table -->
            <div class="bg-white rounded-lg border border-gray-100 p-4 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4">
                    <h3 class="text-xs font-medium text-gray-800 uppercase tracking-wider">Orders List</h3>
                    <div class="flex gap-2">
                        <button
                            @click="exportToCSV"
                            class="px-3 py-1 bg-emerald-600 text-white rounded text-[10px] hover:bg-emerald-700 transition-colors"
                        >
                            {{ selectedOrders.length > 0 ? `CSV (${selectedOrders.length})` : "Export CSV" }}
                        </button>
                        <button
                            @click="exportToPDF"
                            class="px-3 py-1 bg-rose-600 text-white rounded text-[10px] hover:bg-rose-700 transition-colors"
                        >
                            Export PDF
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-tighter">
                                <th class="text-left py-2 px-2">
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-200 rounded"
                                    />
                                </th>
                                <th class="text-left py-2 px-2 font-normal">Order #</th>
                                <th class="text-left py-2 px-2 font-normal">Date</th>
                                <th class="text-left py-2 px-2 font-normal">Customer</th>
                                <th class="text-left py-2 px-2 font-normal">Status</th>
                                <th class="py-2 px-2 font-normal text-center">Source</th>
                                <th class="text-left py-2 px-2 font-normal">Payment</th>
                                <th class="text-right py-2 px-2 font-normal">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="hover:bg-gray-50/50 transition-colors"
                            >
                                <td class="py-2 px-2">
                                    <input
                                        type="checkbox"
                                        :value="order.id"
                                        v-model="selectedOrders"
                                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-200 rounded"
                                    />
                                </td>
                                <td class="py-2 px-2 text-blue-600 font-medium">#{{ order.order_number }}</td>
                                <td class="py-2 px-2 text-gray-400 whitespace-nowrap">{{ formatDate(order.date) }}</td>
                                <td class="py-2 px-2">
                                    <div class="text-gray-700 font-medium leading-none mb-0.5">{{ order.customer_name || order.customer?.name }}</div>
                                    <div class="text-[9px] text-gray-400 tabular-nums">{{ order.customer_phone || order.customer?.phone }}</div>
                                </td>
                                <td class="py-2 px-2">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-medium uppercase tracking-tight" :class="getStatusColor(order.status)">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <span class="text-[9px] text-gray-500 bg-gray-100/50 px-1.5 py-0.5 rounded border border-gray-200/50">
                                        {{ order.source || 'website' }}
                                    </span>
                                </td>
                                <td class="py-2 px-2">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-medium uppercase tracking-tight" :class="getPaymentStatusColor(order.payment_status)">
                                        {{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 text-right font-medium text-gray-800 tabular-nums">৳{{ order.total }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50/30">
                                <td colspan="3" class="py-3 px-2">
                                    <div class="flex items-center gap-4 text-[10px]">
                                        <div class="flex items-center gap-1">
                                            <span class="text-gray-400 uppercase">Page Total:</span>
                                            <span class="text-gray-800 font-medium">৳{{ calculateTotalAmount().toLocaleString() }}</span>
                                        </div>
                                        <div class="w-px h-3 bg-gray-200"></div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-gray-400 uppercase">Grand Total:</span>
                                            <span class="text-blue-600 font-medium">৳{{ grandTotalRevenue.toLocaleString() }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td colspan="5" class="py-3 px-2 text-right">
                                    <p class="text-[9px] text-gray-400 italic">Showing {{ orders.from }}-{{ orders.to }} of {{ orders.total }} records</p>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex items-center justify-end">
                    <div class="flex gap-1">
                        <button
                            v-for="link in orders.links"
                            :key="link.label"
                            @click="changePage(link.url)"
                            :disabled="!link.url"
                            class="px-2 py-1 text-[10px] border rounded transition-colors"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : !link.url
                                    ? 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
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
    Layers,
    Facebook,
    Globe,
    MessageCircle,
    Phone,
    Send,
    User,
    Music,
    Terminal
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
    sourceStats: Array,
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
    source: "",
    delivery_status: "",
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

const filterToday = () => {
    const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Dhaka' });
    filters.date_from = today;
    filters.date_to = today;
    applyFilters();
};

const getSourceBadgeClass = (source) => {
    const classes = {
        website: 'bg-blue-600 text-white shadow-blue-100',
        facebook: 'bg-[#1877F2] text-white shadow-blue-100/50',
        whatsapp: 'bg-[#25D366] text-white shadow-green-100/50',
        phone_call: 'bg-orange-500 text-white shadow-orange-100/50',
        telegram: 'bg-[#0088cc] text-white shadow-blue-100/50',
        tiktok: 'bg-[#EE1D52] text-white shadow-pink-100/50',
        manual: 'bg-gray-700 text-white shadow-gray-100/50'
    };
    return classes[source] || 'bg-gray-600 text-white';
};

const getSourceIcon = (source) => {
    const icons = {
        website: Globe,
        facebook: Facebook,
        whatsapp: MessageCircle,
        phone_call: Phone,
        telegram: Send,
        tiktok: Music,
        manual: User
    };
    return icons[source] || Terminal;
};

const getSuccessRate = (source) => {
    if (source.total_orders === 0) return 0;
    return (source.delivered_count / source.total_orders) * 100;
};

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
    @apply bg-white p-4 rounded-lg border border-gray-100 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-all duration-200;
}

.stat-card:hover {
    @apply border-gray-200;
}

.chart-container {
    @apply bg-white p-4 rounded-lg border border-gray-100 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-all duration-200;
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
