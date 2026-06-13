<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    SquarePen,
    Trash2Icon,
    User,
    Clock,
    ShoppingCart,
} from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";

const props = defineProps({
    orders: Object,
});

const showDeleteModal = ref(false);
const orderToDelete = ref(null);
const selectedOrders = ref([]);
const selectAll = ref(false);

const currentPage = computed(() => props.orders.current_page || 1);
const lastPage = computed(() => props.orders.last_page || 1);
const from = computed(() => props.orders.from || 0);
const to = computed(() => props.orders.to || 0);
const total = computed(() => props.orders.total || 0);

const initFilters = () => {
    const url = new URL(window.location.href);
    return {
        payment_status: url.searchParams.get("payment_status") || "",
        date_from: url.searchParams.get("date_from") || "",
        date_to: url.searchParams.get("date_to") || "",
        customer_search: url.searchParams.get("customer_search") || "",
        order_number: url.searchParams.get("order_number") || "",
        per_page: parseInt(url.searchParams.get("per_page")) || 10,
    };
};

const filters = ref(initFilters());

const openDeleteModal = (id) => {
    orderToDelete.value = id;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    orderToDelete.value = null;
};

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    selectedOrders.value = selectAll.value
        ? props.orders.data.map((order) => order.id)
        : [];
};

const toggleOrderSelection = (orderId) => {
    const index = selectedOrders.value.indexOf(orderId);
    if (index === -1) {
        selectedOrders.value.push(orderId);
    } else {
        selectedOrders.value.splice(index, 1);
    }
    selectAll.value =
        selectedOrders.value.length === props.orders.data.length &&
        props.orders.data.length > 0;
};

let filterTimeout;
const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route("admin.orders.incomplete"),
            { ...filters.value, page: 1 },
            { preserveState: true, preserveScroll: true }
        );
    }, 500);
};

watch(() => filters.value, () => applyFilters(), { deep: true });

const clearFilters = () => {
    filters.value = {
        payment_status: "",
        date_from: "",
        date_to: "",
        customer_search: "",
        order_number: "",
        per_page: 10,
    };
};

const formatDate = (date) => {
    if (!date) return "N/A";
    const dateObj = new Date(date);
    return dateObj.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-amber-50 text-amber-600 border border-amber-100",
        processing: "bg-blue-50 text-blue-600 border border-blue-100",
        completed: "bg-emerald-50 text-emerald-600 border border-emerald-100",
        cancelled: "bg-rose-50 text-rose-600 border border-rose-100",
        incomplete: "bg-slate-50 text-slate-600 border border-slate-100",
    };
    return colors[status] || "bg-gray-50 text-gray-500";
};

const orderBorderColor = (status) => {
    const map = {
        pending: "bg-amber-400",
        processing: "bg-blue-400",
        completed: "bg-emerald-400",
        cancelled: "bg-rose-400",
        incomplete: "bg-slate-400",
    };
    return map[status] || "bg-gray-300";
};

const getPaymentStatusColor = (status) => {
    const colors = {
        paid: "bg-emerald-50 text-emerald-600 border border-emerald-100",
        unpaid: "bg-rose-50 text-rose-600 border border-rose-100",
        partial: "bg-amber-50 text-amber-600 border border-amber-100",
    };
    return colors[status] || "bg-gray-50 text-gray-500";
};

const totalSales = computed(() =>
    props.orders?.data
        ? props.orders.data.reduce((sum, o) => sum + Number(o.total || 0), 0)
        : 0
);
const avgOrder = computed(() => {
    const count = props.orders?.data?.length || 1;
    return totalSales.value / count;
});

const goToPage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        router.get(
            route("admin.orders.incomplete"),
            { ...filters.value, page },
            { preserveState: true, preserveScroll: true }
        );
    }
};
</script>

<template>
    <Head title="Incomplete Orders" />
    <AdminLayout>
        <div class="space-y-3 sm:space-y-4">
            <!-- Slim Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-3 rounded-xl border border-gray-200 shadow-sm"
            >
                <div>
                    <h1
                        class="text-base font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <Clock class="w-4 h-4 text-gray-500" />
                        Incomplete Orders
                    </h1>
                    <p
                        class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider leading-none mt-1"
                    >
                        Pending setup or data
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.orders.index')"
                        class="px-3 py-1.5 bg-gray-50 text-gray-600 rounded-lg text-[10px] font-semibold uppercase tracking-wider border border-gray-200 hover:bg-gray-100 transition-all shadow-sm"
                    >
                        All Orders
                    </Link>
                </div>
            </div>

            <!-- High-Density Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-3">
                <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                    <p
                        class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1.5"
                    >
                        Total Issues
                    </p>
                    <p class="text-base font-semibold text-gray-900">
                        {{ props.orders?.total || 0 }}
                    </p>
                </div>
                <div
                    class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm hidden sm:block"
                >
                    <p
                        class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1.5"
                    >
                        Volume
                    </p>
                    <p class="text-base font-semibold text-gray-900">
                        ৳{{ totalSales.toLocaleString() }}
                    </p>
                </div>
                <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                    <p
                        class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1.5"
                    >
                        Avg Ticket
                    </p>
                    <p class="text-base font-semibold text-gray-900">
                        ৳{{ Math.round(avgOrder).toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- Sleek Filter Bar -->
            <div class="bg-white p-2.5 rounded-xl border border-gray-200 shadow-sm">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 items-end">
                    <div>
                        <label
                            class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1"
                            >Order ID</label
                        >
                        <input
                            v-model="filters.order_number"
                            type="text"
                            placeholder="Search..."
                            class="w-full px-2 py-1.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-500 rounded-lg text-[10px] font-medium outline-none transition-all"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1"
                            >Customer</label
                        >
                        <input
                            v-model="filters.customer_search"
                            type="text"
                            placeholder="Name/Phone..."
                            class="w-full px-2 py-1.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-500 rounded-lg text-[10px] font-medium outline-none transition-all"
                        />
                    </div>
                    <div class="hidden lg:block text-right lg:col-span-3">
                        <button
                            @click="clearFilters"
                            class="text-[9px] font-bold text-gray-400 uppercase tracking-widest hover:text-red-500 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile: Minimal Order Cards -->
            <div class="md:hidden space-y-2">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden active:scale-[0.98] transition-transform"
                >
                    <div
                        :class="orderBorderColor(order.status)"
                        class="absolute left-0 top-0 bottom-0 w-1"
                    ></div>

                    <div class="flex justify-between items-start mb-2.5">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :checked="selectedOrders.includes(order.id)"
                                @change="toggleOrderSelection(order.id)"
                                class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600"
                            />
                            <div>
                                <p class="text-[11px] font-semibold text-gray-900 leading-none">
                                    #{{ order.order_number }}
                                </p>
                                <p
                                    class="text-[9px] text-gray-400 font-medium uppercase tracking-tight mt-0.5"
                                >
                                    {{ formatDate(order.date || order.created_at) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span
                                :class="getStatusColor(order.status || 'incomplete')"
                                class="px-1.5 py-0.5 rounded-md text-[8px] font-bold uppercase tracking-tight"
                                >{{ order.status || "incomplete" }}</span
                            >
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 shrink-0 shadow-inner"
                        >
                            <User class="w-4 text-gray-300" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-[11px] font-semibold text-gray-800 leading-tight truncate"
                            >
                                {{ order.customer?.name || "No Name" }}
                            </p>
                            <p class="text-[10px] text-gray-400 truncate font-medium">
                                {{ order.customer?.phone || "No Phone" }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2.5 border-t border-gray-50">
                        <div class="flex gap-4">
                            <div>
                                <p
                                    class="text-[8px] font-bold text-gray-300 uppercase tracking-widest leading-none mb-1"
                                >
                                    Amount
                                </p>
                                <p class="text-[11px] font-semibold text-gray-900 leading-none">
                                    ৳{{ Math.round(order.total || 0).toLocaleString() }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[8px] font-bold text-gray-300 uppercase tracking-widest leading-none mb-1"
                                >
                                    Items
                                </p>
                                <p class="text-[11px] font-semibold text-gray-900 leading-none">
                                    {{ order.items?.length || 0 }} Unit
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Link
                                :href="route('admin.orders.edit', order.id)"
                                class="p-1.5 bg-gray-50 text-gray-400 rounded-lg border border-gray-100"
                            >
                                <SquarePen class="w-3.5 h-3.5" />
                            </Link>
                            <button
                                @click="openDeleteModal(order.id)"
                                class="p-1.5 bg-gray-50 text-gray-400 rounded-lg border border-gray-100"
                            >
                                <Trash2Icon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="orders.data.length === 0"
                    class="py-12 bg-white rounded-xl border-2 border-dashed border-gray-100 text-center"
                >
                    <ShoppingCart class="w-10 h-10 text-gray-100 mx-auto mb-2" />
                    <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">
                        Nothing Here
                    </p>
                </div>
            </div>

            <!-- Desktop: High-Density Table -->
            <div
                class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
            >
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="p-3 w-10">
                                <input
                                    type="checkbox"
                                    :checked="selectAll"
                                    @change="toggleSelectAll"
                                    class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600"
                                />
                            </th>
                            <th
                                class="p-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Order
                            </th>
                            <th
                                class="p-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Customer info
                            </th>
                            <th
                                class="p-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Notes
                            </th>
                            <th
                                class="p-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Amount
                            </th>
                            <th
                                class="p-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Status
                            </th>
                            <th
                                class="p-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-right"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="hover:bg-gray-50/50 transition-colors"
                        >
                            <td class="p-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedOrders.includes(order.id)"
                                    @change="toggleOrderSelection(order.id)"
                                    class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600"
                                />
                            </td>
                            <td class="p-3">
                                <p class="text-[11px] font-semibold text-gray-900 leading-none">
                                    #{{ order.order_number }}
                                </p>
                                <p
                                    class="text-[9px] text-gray-400 font-medium uppercase tracking-tighter mt-1"
                                >
                                    {{ formatDate(order.date || order.created_at) }}
                                </p>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 shrink-0 text-gray-300"
                                    >
                                        <User class="w-3.5 h-3.5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[11px] font-semibold text-gray-800 leading-tight truncate"
                                        >
                                            {{ order.customer?.name || "N/A" }}
                                        </p>
                                        <p class="text-[9px] text-gray-400 truncate">
                                            {{ order.customer?.phone || "No phone" }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 max-w-[150px]">
                                <p class="text-[10px] text-gray-400 truncate italic">
                                    {{ order.admin_notes || "—" }}
                                </p>
                            </td>
                            <td class="p-3">
                                <p class="text-[11px] font-semibold text-gray-900 leading-none">
                                    ৳{{ Math.round(order.total || 0).toLocaleString() }}
                                </p>
                                <p
                                    class="text-[9px] text-gray-400 font-medium uppercase tracking-tighter mt-1"
                                >
                                    {{ order.items?.length || 0 }} SKU
                                </p>
                            </td>
                            <td class="p-3">
                                <span
                                    :class="getStatusColor(order.status || 'incomplete')"
                                    class="px-2 py-0.5 rounded-md text-[8px] font-bold uppercase tracking-tight"
                                    >{{ order.status || "incomplete" }}</span
                                >
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="route('admin.orders.edit', order.id)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg transition-all"
                                    >
                                        <SquarePen class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="openDeleteModal(order.id)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg transition-all"
                                    >
                                        <Trash2Icon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    class="p-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between"
                >
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                        {{ from }}-{{ to }} / {{ total }} Total
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="p-1.5 text-gray-400 hover:text-gray-900 disabled:opacity-20"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-[10px] font-bold text-gray-900 px-2"
                            >{{ currentPage }} / {{ lastPage }}</span
                        >
                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === lastPage"
                            class="p-1.5 text-gray-400 hover:text-gray-900 disabled:opacity-20"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <DeleteModal
            :item-id="orderToDelete"
            item-name="order"
            route-name="admin.orders.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </AdminLayout>
</template>
