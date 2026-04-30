<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Search,
    User,
    Phone,
    Mail,
    MapPin,
    Calendar,
    ShoppingCart,
    TrendingUp,
    MoreVertical,
    Eye,
    ChevronLeft,
    ChevronRight,
    ArrowUpDown,
    FilterX,
    Filter,
    X,
    MapPinned,
    Hash,
    Wallet,
    Truck,
    CheckSquare,
    Square,
    Send,
    MessageSquare
} from "lucide-vue-next";

const props = defineProps({
    customers: Object,
    filters: Object,
    districts: Array,
    thanas: Array,
    smsTemplates: Array,
});


const form = ref({
    search: props.filters.search || "",
    area: props.filters.area || "",
    district: props.filters.district || "",
    thana: props.filters.thana || "",
    min_orders: props.filters.min_orders || "",
    min_spent: props.filters.min_spent || "",
    sort_by: props.filters.sort_by || "created_at",
    sort_direction: props.filters.sort_direction || "desc",
});

const selectedCustomers = ref([]);
const showSmsModal = ref(false);
const smsMessage = ref("");

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedCustomers.value = props.customers.data.map(c => c.id);
    } else {
        selectedCustomers.value = [];
    }
};

const sendSms = () => {
    if (selectedCustomers.value.length === 0) return;
    router.post(route('admin.customers.send-bulk-sms'), {
        customer_ids: selectedCustomers.value,
        message: smsMessage.value,
    }, {
        onSuccess: () => {
            showSmsModal.value = false;
            selectedCustomers.value = [];
            smsMessage.value = "";
        }
    });
};


const showFilters = ref(false);

const applyFilters = () => {
    router.get(route("admin.customers.index"), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(() => form.value.district, () => {
    form.value.thana = "";
    applyFilters();
});

watch([() => form.value.area, () => form.value.thana, () => form.value.min_orders, () => form.value.min_spent], () => {
    applyFilters();
});

let searchTimeout;
watch(() => form.value.search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

const handleSort = (column) => {
    if (form.value.sort_by === column) {
        form.value.sort_direction = form.value.sort_direction === "asc" ? "desc" : "asc";
    } else {
        form.value.sort_by = column;
        form.value.sort_direction = "desc";
    }
    applyFilters();
};

const resetFilters = () => {
    form.value = {
        search: "",
        area: "",
        district: "",
        thana: "",
        min_orders: "",
        min_spent: "",
        sort_by: "created_at",
        sort_direction: "desc",
    };
    applyFilters();
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <Head title="Customers" />

    <AdminLayout>
        <div class="w-full mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Customer Directory
                        <span class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs px-2.5 py-1 rounded-full font-semibold border border-blue-100 dark:border-blue-800">
                            {{ customers.total }} Total
                        </span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage and track your customer base activities</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <button 
                        v-if="selectedCustomers.length > 0"
                        @click="showSmsModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all animate-in zoom-in-95"
                    >
                        <MessageSquare class="w-4 h-4" />
                        Send SMS ({{ selectedCustomers.length }})
                    </button>

                    <Link :href="route('admin.customers.sms-logs')" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-sm font-bold hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all">
                        <CheckSquare class="w-4 h-4" />
                        SMS Logs
                    </Link>

                    <div class="relative min-w-[300px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Search name, phone or email..."
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none"
                        />
                    </div>
                </div>
            </div>




            <!-- Full Width Filters Row (Always Visible) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <select v-model="form.area" class="h-10 w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 text-xs font-semibold focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                    <option value="">All Zones</option>
                    <option value="inside_dhaka">Inside Dhaka</option>
                    <option value="outside_dhaka">Outside Dhaka</option>
                </select>

                <select v-model="form.district" class="h-10 w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 text-xs font-semibold focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                    <option value="">All Districts</option>
                    <option v-for="d in districts" :key="d" :value="d">{{ d }}</option>
                </select>

                <select v-model="form.thana" class="h-10 w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 text-xs font-semibold focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                    <option value="">All Thanas</option>
                    <option v-for="t in thanas" :key="t" :value="t">{{ t }}</option>
                </select>

                <div class="relative">
                    <Hash class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input v-model="form.min_orders" type="number" placeholder="Min Orders" class="h-10 w-full pl-9 pr-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold focus:ring-1 focus:ring-blue-500 outline-none transition-all" />
                </div>

                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                    <input v-model="form.min_spent" type="number" placeholder="Min Spent" class="h-10 w-full pl-9 pr-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold focus:ring-1 focus:ring-blue-500 outline-none transition-all" />
                </div>
                
                <div class="lg:col-span-5 flex justify-end">
                    <button @click="resetFilters" class="text-[10px] font-bold text-rose-500 hover:text-rose-600 flex items-center gap-1 uppercase tracking-wider">
                        <X class="w-3 h-3" /> Clear All Filters
                    </button>
                </div>
            </div>



            <!-- Table Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4">
                                    <input 
                                        type="checkbox" 
                                        :checked="selectedCustomers.length === customers.data.length && customers.data.length > 0"
                                        @change="toggleAll"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider cursor-pointer group" @click="handleSort('name')">

                                    <div class="flex items-center gap-1.5">
                                        Customer
                                        <ArrowUpDown class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Contact & Location</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Zone</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider cursor-pointer group" @click="handleSort('orders_count')">
                                    <div class="flex items-center gap-1.5">
                                        Orders
                                        <ArrowUpDown class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider cursor-pointer group" @click="handleSort('total_spent')">
                                    <div class="flex items-center gap-1.5">
                                        Revenue
                                        <ArrowUpDown class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Last Active</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">SMS Activity</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">Action</th>

                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-for="customer in customers.data" :key="customer.id" 
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-900/40 transition-colors"
                                :class="{'bg-blue-50/10 dark:bg-blue-900/10': selectedCustomers.includes(customer.id)}"
                            >
                                <td class="px-6 py-4">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectedCustomers" 
                                        :value="customer.id"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <!-- Customer Info -->

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm border border-blue-100 dark:border-blue-800">
                                            {{ customer.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ customer.name }}</div>
                                            <div class="text-[11px] text-gray-400 mt-0.5">Joined {{ formatDate(customer.created_at) }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact & Location -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                <Phone class="w-3 h-3 text-emerald-500" /> {{ customer.phone }}
                                            </div>
                                            <div v-if="customer.email" class="flex items-center gap-1 text-[11px] text-gray-400">
                                                <Mail class="w-3 h-3" /> {{ customer.email }}
                                            </div>
                                        </div>
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded text-[10px] text-gray-500">
                                            <MapPin class="w-2.5 h-2.5 text-rose-400" />
                                            <span>{{ customer.district || 'General' }} <span v-if="customer.thana">/ {{ customer.thana }}</span></span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 italic line-clamp-1 max-w-[200px]" :title="customer.address">
                                            {{ customer.address || 'No address details' }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Zone Badge -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-semibold uppercase tracking-wider"
                                        :class="customer.area === 'inside_dhaka' 
                                            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800' 
                                            : 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400 border border-orange-100 dark:border-orange-800'">
                                        {{ (customer.area || 'website').replace('_', ' ') }}
                                    </span>
                                </td>

                                <!-- Orders -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ customer.orders_count }}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-medium">Orders</div>
                                </td>

                                <!-- Revenue -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1 text-sm font-bold text-gray-900 dark:text-white bg-emerald-50/50 dark:bg-emerald-900/10 px-2 py-1 rounded-lg w-fit border border-emerald-100/50 dark:border-emerald-800/50">
                                        ৳{{ Number(customer.total_spent).toLocaleString() }}
                                        <TrendingUp class="w-3 h-3 text-emerald-500" v-if="customer.total_spent > 0" />
                                    </div>
                                </td>

                                <!-- Active Date -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <Calendar class="w-3.5 h-3.5" />
                                        {{ formatDate(customer.last_order_at) }}
                                    </div>
                                </td>

                                <!-- SMS Activity -->
                                <td class="px-6 py-4">
                                    <div v-if="customer.last_sms_at" class="space-y-1">
                                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-gray-600 dark:text-gray-400 uppercase">
                                            <Send class="w-3 h-3 text-blue-500" />
                                            {{ formatDate(customer.last_sms_at) }}
                                        </div>
                                        <div class="text-[9px] text-gray-400 line-clamp-1 max-w-[120px]" :title="customer.last_sms_message">
                                            {{ customer.last_sms_message }}
                                        </div>
                                    </div>
                                    <div v-else class="text-[10px] text-gray-300 italic">No SMS sent</div>
                                </td>


                                <!-- Action -->
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="route('admin.orders.index', { customer_search: customer.phone })"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all border border-transparent hover:border-blue-100"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </Link>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="customers.data.length === 0">
                                <td colspan="7" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center mb-4 text-gray-300">
                                            <FilterX class="w-8 h-8" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">No results found</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-xs mx-auto">Try adjusting your filters or search terms.</p>
                                        <button @click="resetFilters" class="mt-4 text-blue-600 dark:text-blue-400 font-semibold text-sm hover:underline">Reset filters</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-xs text-gray-500 font-medium">
                        Showing {{ customers.from || 0 }} - {{ customers.to || 0 }} of {{ customers.total }} entries
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Link v-if="customers.prev_page_url" :href="customers.prev_page_url" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 transition-colors">
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <template v-for="link in customers.links.slice(1, -1)" :key="link.label">
                            <Link v-if="link.url" :href="link.url" 
                                class="w-8 h-8 flex items-center justify-center text-xs rounded-lg transition-all border"
                                :class="link.active 
                                    ? 'bg-blue-600 text-white border-blue-600 font-bold shadow-sm' 
                                    : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50'"
                                v-html="link.label"></Link>

                        </template>
                        <Link v-if="customers.next_page_url" :href="customers.next_page_url" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk SMS Modal -->
        <div v-if="showSmsModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700 animate-in zoom-in-95 duration-200">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <MessageSquare class="w-5 h-5 text-blue-600" />
                            Send Bulk SMS
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Sending to {{ selectedCustomers.length }} selected customers</p>
                    </div>
                    <button @click="showSmsModal = false" class="p-2 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                        <X class="w-5 h-5 text-gray-400" />
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Template Selector -->
                    <div v-if="smsTemplates.length > 0" class="space-y-3">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Select Template</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                v-for="template in smsTemplates" 
                                :key="template.id"
                                @click="smsMessage = template.content"
                                class="p-3 text-left bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-xl hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all text-xs font-semibold group"
                            >
                                <span class="block text-gray-900 dark:text-gray-100 mb-1 group-hover:text-blue-600">{{ template.name }}</span>
                                <span class="block text-gray-400 line-clamp-1 font-normal">{{ template.content }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div class="space-y-3">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Message Content</label>
                        <textarea 
                            v-model="smsMessage"
                            rows="5"
                            placeholder="Type your message here..."
                            class="w-full p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                        ></textarea>
                        <div class="flex flex-wrap gap-2">
                            <span class="text-[10px] px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded border border-gray-200 dark:border-gray-600 font-bold uppercase tracking-tighter cursor-help" title="Customer Name"> {name} </span>
                            <span class="text-[10px] px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded border border-gray-200 dark:border-gray-600 font-bold uppercase tracking-tighter cursor-help" title="Total Orders"> {orders_count} </span>
                            <span class="text-[10px] px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded border border-gray-200 dark:border-gray-600 font-bold uppercase tracking-tighter cursor-help" title="Total Spent"> {total_spent} </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button @click="showSmsModal = false" class="px-5 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancel</button>
                    <button 
                        @click="sendSms"
                        :disabled="!smsMessage || selectedCustomers.length === 0"
                        class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all disabled:opacity-50"
                    >
                        <Send class="w-4 h-4" />
                        Send Broadcast
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>


<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

</style>
