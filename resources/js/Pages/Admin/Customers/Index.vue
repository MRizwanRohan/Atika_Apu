<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Search,
    User,
    Phone,
    MapPin,
    Eye,
    ChevronLeft,
    ChevronRight,
    ArrowUpDown,
    FilterX,
    X,
    Send,
    MessageSquare,
    Plus,
    Ban,
    AlertTriangle,
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
    is_blocked: props.filters.is_blocked || "",
    sort_by: props.filters.sort_by || "created_at",
    sort_direction: props.filters.sort_direction || "desc",
});

const selectedCustomers = ref([]);
const showSmsModal = ref(false);
const smsMessage = ref("");

const showCreateModal = ref(false);
const createForm = useForm({
    name: "",
    phone: "",
    email: "",
    address: "",
    district: "",
    thana: "",
    area: "",
});

const createCustomer = () => {
    createForm.post(route("admin.customers.store"), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const showConfirmModal = ref(false);
const customerToToggle = ref(null);

const toggleBlock = (customer) => {
    customerToToggle.value = customer;
    showConfirmModal.value = true;
};

const confirmToggleBlock = () => {
    if (!customerToToggle.value) return;
    router.post(route("admin.customers.toggle-block", customerToToggle.value.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            customerToToggle.value = null;
        }
    });
};

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedCustomers.value = props.customers.data.map((c) => c.id);
    } else {
        selectedCustomers.value = [];
    }
};

const sendSms = () => {
    if (selectedCustomers.value.length === 0) return;
    router.post(
        route("admin.customers.send-bulk-sms"),
        {
            customer_ids: selectedCustomers.value,
            message: smsMessage.value,
        },
        {
            onSuccess: () => {
                showSmsModal.value = false;
                selectedCustomers.value = [];
                smsMessage.value = "";
            },
        },
    );
};

const applyFilters = () => {
    router.get(route("admin.customers.index"), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(
    () => form.value.district,
    () => {
        form.value.thana = "";
        applyFilters();
    },
);

watch(
    [
        () => form.value.area,
        () => form.value.thana,
        () => form.value.min_orders,
        () => form.value.min_spent,
        () => form.value.is_blocked,
    ],
    () => {
        applyFilters();
    },
);

let searchTimeout;
watch(
    () => form.value.search,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    },
);

const handleSort = (column) => {
    if (form.value.sort_by === column) {
        form.value.sort_direction =
            form.value.sort_direction === "asc" ? "desc" : "asc";
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
        is_blocked: "",
        sort_by: "created_at",
        sort_direction: "desc",
    };
    applyFilters();
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "2-digit",
    });
};
</script>

<template>
    <Head title="Customers" />

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
                        Customer Directory
                        <span
                            class="rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[9px] font-bold text-blue-600 uppercase tracking-tighter"
                        >
                            {{ customers.total }} Total
                        </span>
                    </h1>
                    <p
                        class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider mt-1 leading-none"
                    >
                        Relationship management
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="selectedCustomers.length > 0"
                        @click="showSmsModal = true"
                        class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-sm active:scale-95 transition-all"
                    >
                        <MessageSquare class="w-3 h-3" />
                        SMS ({{ selectedCustomers.length }})
                    </button>

                    <button
                        @click="showCreateModal = true"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-sm active:scale-95 transition-all"
                    >
                        <Plus class="w-3 h-3" />
                        New Customer
                    </button>

                    <Link
                        :href="route('admin.customers.sms-logs')"
                        class="px-3 py-1.5 bg-gray-50 text-gray-600 rounded-lg text-[10px] font-semibold uppercase tracking-wider border border-gray-200 hover:bg-gray-100 transition-all shadow-sm"
                    >
                        Logs
                    </Link>
                </div>
            </div>

            <!-- Sleek Filter Row -->
            <div
                class="bg-white p-2.5 rounded-xl border border-gray-200 shadow-sm"
            >
                <div
                    class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 items-end"
                >
                    <div class="col-span-2">
                        <div class="relative group">
                            <Search
                                class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors"
                            />
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Search by name/phone..."
                                class="w-full pl-8 pr-3 py-2 bg-gray-50 border border-gray-100 focus:bg-white focus:border-blue-500 rounded-lg text-[11px] font-medium outline-none transition-all"
                            />
                        </div>
                    </div>
                    <div>
                        <select
                            v-model="form.area"
                            class="w-full px-2 py-2 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-semibold outline-none appearance-none"
                        >
                            <option value="">All Zones</option>
                            <option value="inside_dhaka">In Dhaka</option>
                            <option value="outside_dhaka">Out Dhaka</option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="form.district"
                            class="w-full px-2 py-2 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-semibold outline-none appearance-none"
                        >
                            <option value="">All Districts</option>
                            <option v-for="d in districts" :key="d" :value="d">
                                {{ d }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="form.is_blocked"
                            class="w-full px-2 py-2 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-semibold outline-none appearance-none"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active Only</option>
                            <option value="blocked">Blocked Only</option>
                        </select>
                    </div>
                    <div class="hidden lg:block lg:col-span-1"></div>
                    <div class="text-right">
                        <button
                            @click="resetFilters"
                            class="text-[9px] font-bold text-gray-400 uppercase tracking-widest hover:text-red-500 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile: Minimal Customer Cards -->
            <div class="md:hidden space-y-2">
                <div
                    v-for="customer in customers.data"
                    :key="customer.id"
                    class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm relative active:scale-[0.98] transition-transform"
                    :class="{
                        'border-blue-200 bg-blue-50/20':
                            selectedCustomers.includes(customer.id),
                    }"
                >
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <input
                                type="checkbox"
                                v-model="selectedCustomers"
                                :value="customer.id"
                                class="w-3.5 h-3.5 rounded border-gray-300 text-blue-600"
                            />
                            <div
                                class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-[11px] font-bold text-indigo-600 border border-indigo-100 shrink-0 shadow-inner"
                            >
                                {{ customer.name.charAt(0).toUpperCase() }}
                            </div>
                             <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p
                                        class="text-[11px] font-semibold text-gray-900 leading-none truncate"
                                    >
                                        {{ customer.name }}
                                    </p>
                                    <span
                                        v-if="customer.is_blocked"
                                        class="px-1 py-0.2 bg-red-50 text-red-600 border border-red-100 rounded text-[7px] font-bold uppercase tracking-tight"
                                    >
                                        Blocked
                                    </span>
                                </div>
                                <p
                                    class="text-[9px] text-gray-400 font-medium uppercase tracking-tighter mt-1"
                                >
                                    Joined {{ formatDate(customer.created_at) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <Link
                                :href="
                                    route('admin.orders.index', {
                                        customer_search: customer.phone,
                                    })
                                "
                                class="p-1.5 bg-gray-50 text-gray-400 rounded-lg border border-gray-100 active:bg-white inline-flex"
                            >
                                <Eye class="w-3.5 h-3.5" />
                            </Link>
                            <button
                                @click="toggleBlock(customer)"
                                class="p-1.5 rounded-lg border border-gray-100 active:bg-white inline-flex"
                                :class="customer.is_blocked ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-500 border-red-100'"
                            >
                                <Ban class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <Phone class="w-3 h-3 text-emerald-500" />
                            <p
                                class="text-[10px] font-medium text-gray-700 leading-none"
                            >
                                {{ customer.phone }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin class="w-3 h-3 text-rose-400" />
                            <p
                                class="text-[10px] font-medium text-gray-700 leading-none truncate"
                            >
                                {{ customer.district || "General" }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between pt-2.5 border-t border-gray-50"
                    >
                        <div class="flex gap-4">
                            <div>
                                <p
                                    class="text-[8px] font-bold text-gray-300 uppercase tracking-widest leading-none mb-1"
                                >
                                    Orders
                                </p>
                                <p
                                    class="text-[10px] font-semibold text-gray-900"
                                >
                                    {{ customer.orders_count }} Units
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[8px] font-bold text-gray-300 uppercase tracking-widest leading-none mb-1"
                                >
                                    Spent
                                </p>
                                <p
                                    class="text-[10px] font-semibold text-gray-900"
                                >
                                    ৳{{
                                        Number(
                                            customer.total_spent,
                                        ).toLocaleString()
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="customer.area"
                                class="px-1.5 py-0.5 bg-gray-50 text-gray-400 border border-gray-100 rounded text-[8px] font-bold uppercase tracking-tight"
                            >
                                {{ customer.area.replace("_", " ") }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="customers.data.length === 0"
                    class="py-12 bg-white rounded-xl border-2 border-dashed border-gray-100 text-center"
                >
                    <FilterX class="w-10 h-10 text-gray-100 mx-auto mb-2" />
                    <p
                        class="text-[10px] font-bold text-gray-300 uppercase tracking-widest"
                    >
                        No matching souls found
                    </p>
                </div>
            </div>

            <!-- Desktop: High-Density Table -->
            <div
                class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
            >
                <table class="w-full text-left">
                    <thead
                        class="bg-gray-50 border-b border-gray-100 font-semibold text-gray-400 uppercase tracking-widest text-[9px]"
                    >
                        <tr>
                            <th class="p-4 w-10">
                                <input
                                    type="checkbox"
                                    :checked="
                                        selectedCustomers.length ===
                                            customers.data.length &&
                                        customers.data.length > 0
                                    "
                                    @change="toggleAll"
                                    class="w-3.5 h-3.5 rounded border-gray-300 text-blue-600"
                                />
                            </th>
                            <th
                                class="p-4 cursor-pointer hover:text-gray-900 transition-colors"
                                @click="handleSort('name')"
                            >
                                Customer
                                <ArrowUpDown class="inline w-2.5 h-2.5 ml-1" />
                            </th>
                            <th class="p-4">Contact</th>
                            <th class="p-4">Location</th>
                            <th class="p-4">Status</th>
                            <th
                                class="p-4 cursor-pointer hover:text-gray-900 transition-colors"
                                @click="handleSort('orders_count')"
                            >
                                Orders
                                <ArrowUpDown class="inline w-2.5 h-2.5 ml-1" />
                            </th>
                            <th
                                class="p-4 cursor-pointer hover:text-gray-900 transition-colors"
                                @click="handleSort('total_spent')"
                            >
                                Volume
                                <ArrowUpDown class="inline w-2.5 h-2.5 ml-1" />
                            </th>
                            <th class="p-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-[11px]">
                        <tr
                            v-for="customer in customers.data"
                            :key="customer.id"
                            class="hover:bg-gray-50/50 transition-colors"
                            :class="{
                                'bg-blue-50/10': selectedCustomers.includes(
                                    customer.id,
                                ),
                            }"
                        >
                            <td class="p-4">
                                <input
                                    type="checkbox"
                                    v-model="selectedCustomers"
                                    :value="customer.id"
                                    class="w-3.5 h-3.5 rounded border-gray-300 text-blue-600"
                                />
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-[11px] font-bold text-gray-400 border border-gray-100 shrink-0"
                                    >
                                        {{
                                            customer.name
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="font-semibold text-gray-900 leading-none truncate"
                                        >
                                            {{ customer.name }}
                                        </p>
                                        <p
                                            class="text-[9px] text-gray-400 font-medium uppercase mt-1"
                                        >
                                            #{{ customer.id }} since
                                            {{
                                                formatDate(customer.created_at)
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <p
                                    class="font-medium text-gray-700 leading-none mb-1"
                                >
                                    {{ customer.phone }}
                                </p>
                                <p
                                    class="text-[10px] text-gray-400 leading-none"
                                >
                                    {{ customer.email || "No email" }}
                                </p>
                            </td>
                            <td class="p-4">
                                <p
                                    class="font-medium text-gray-700 leading-none mb-1 truncate max-w-[120px]"
                                >
                                    {{ customer.district || "General" }}
                                </p>
                                <p
                                    class="text-[10px] text-gray-400 leading-none italic uppercase tracking-tighter"
                                >
                                    {{
                                        customer.area?.replace("_", " ") ||
                                        "Website"
                                    }}
                                </p>
                            </td>
                            <td class="p-4">
                                <span
                                    v-if="customer.is_blocked"
                                    class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-100 rounded-full text-[9px] font-bold uppercase tracking-tight"
                                >
                                    Blocked
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-0.5 bg-green-50 text-green-600 border border-green-100 rounded-full text-[9px] font-bold uppercase tracking-tight"
                                >
                                    Active
                                </span>
                            </td>
                            <td class="p-4 font-semibold text-gray-900">
                                {{ customer.orders_count }}
                            </td>
                            <td class="p-4 font-semibold text-gray-900">
                                ৳{{
                                    Number(
                                        customer.total_spent,
                                    ).toLocaleString()
                                }}
                            </td>
                            <td class="p-4 text-right">
                                <Link
                                    :href="
                                        route('admin.orders.index', {
                                            customer_search: customer.phone,
                                        })
                                    "
                                    class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-white transition-all shadow-sm inline-flex items-center"
                                >
                                    <Eye class="w-4 h-4" />
                                </Link>
                                <button
                                    @click="toggleBlock(customer)"
                                    class="p-1.5 rounded-lg transition-all shadow-sm ml-1.5 inline-flex items-center"
                                    :class="customer.is_blocked ? 'text-emerald-600 hover:bg-emerald-50' : 'text-red-500 hover:bg-red-50'"
                                    :title="customer.is_blocked ? 'Unblock Customer' : 'Block Customer'"
                                >
                                    <Ban class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    class="p-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between"
                >
                    <p
                        class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                    >
                        {{ customers.from }}-{{ customers.to }} of
                        {{ customers.total }} Soul(s)
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-if="customers.prev_page_url"
                            :href="customers.prev_page_url"
                            class="p-1.5 text-gray-400 hover:text-gray-900 transition-colors"
                            ><ChevronLeft class="w-4 h-4"
                        /></Link>
                        <div class="flex items-center gap-1 mx-2">
                            <template
                                v-for="link in customers.links.slice(1, -1)"
                                :key="link.label"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="w-7 h-7 rounded flex items-center justify-center text-[10px] font-bold border transition-all"
                                    :class="
                                        link.active
                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-100 border-blue-600'
                                            : 'bg-white text-gray-400 border-gray-200 hover:border-gray-300'
                                    "
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                        <Link
                            v-if="customers.next_page_url"
                            :href="customers.next_page_url"
                            class="p-1.5 text-gray-400 hover:text-gray-900 transition-colors"
                            ><ChevronRight class="w-4 h-4"
                        /></Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Modal -->
        <div
            v-if="showSmsModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/40 p-4 transition-all sm:p-6"
        >
            <div
                class="w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl animate-in zoom-in-95 duration-200"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4"
                >
                    <div>
                        <h3
                            class="flex items-center gap-2 text-sm font-bold text-gray-900 uppercase tracking-tight"
                        >
                            <MessageSquare class="w-4 h-4 text-blue-600" />
                            Bulk SMS Broadcast
                        </h3>
                        <p
                            class="mt-0.5 text-[10px] font-semibold text-gray-400 uppercase tracking-tighter"
                        >
                            Reach {{ selectedCustomers.length }} Recipients
                        </p>
                    </div>
                    <button
                        @click="showSmsModal = false"
                        class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div v-if="smsTemplates.length > 0">
                        <p
                            class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1"
                        >
                            Templates
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button
                                v-for="template in smsTemplates"
                                :key="template.id"
                                @click="smsMessage = template.content"
                                class="p-2.5 bg-gray-50 border border-gray-100 rounded-xl text-left hover:border-blue-300 hover:bg-blue-50/50 transition-all group"
                            >
                                <p
                                    class="text-[10px] font-bold text-gray-800 group-hover:text-blue-700 leading-none mb-1"
                                >
                                    {{ template.name }}
                                </p>
                                <p
                                    class="text-[9px] text-gray-400 truncate leading-none"
                                >
                                    {{ template.content }}
                                </p>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1"
                        >
                            Message Body
                        </p>
                        <textarea
                            v-model="smsMessage"
                            rows="4"
                            placeholder="Type your strategic message..."
                            class="w-full p-4 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 rounded-2xl text-[12px] font-medium outline-none transition-all resize-none shadow-inner"
                        ></textarea>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span
                                class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[8px] font-bold uppercase tracking-tighter"
                                title="Customer Name"
                                >{name}</span
                            >
                            <span
                                class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[8px] font-bold uppercase tracking-tighter"
                                title="Order Count"
                                >{orders_count}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="px-5 py-4 bg-gray-50 flex items-center justify-end gap-3 border-t border-gray-100"
                >
                    <button
                        @click="showSmsModal = false"
                        class="text-[11px] font-bold text-gray-400 uppercase tracking-widest hover:text-gray-600"
                    >
                        Cancel
                    </button>
                    <button
                        @click="sendSms"
                        :disabled="
                            !smsMessage || selectedCustomers.length === 0
                        "
                        class="px-5 py-2 bg-blue-600 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-blue-100 disabled:opacity-30 active:scale-95 transition-all"
                    >
                        <Send class="w-3.5 h-3.5" />
                        Fire Broadcast
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Customer Modal -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/40 p-4 transition-all sm:p-6"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl animate-in zoom-in-95 duration-200"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4"
                >
                    <div>
                        <h3
                            class="flex items-center gap-2 text-sm font-bold text-gray-900 uppercase tracking-tight"
                        >
                            <User class="w-4 h-4 text-emerald-600" />
                            Add New Customer
                        </h3>
                        <p
                            class="mt-0.5 text-[10px] font-semibold text-gray-400 uppercase tracking-tighter"
                        >
                            Manually register a customer profile
                        </p>
                    </div>
                    <button
                        @click="showCreateModal = false"
                        class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="createCustomer" class="p-5 space-y-3.5">
                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Customer Name *</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="e.g. John Doe"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-medium outline-none transition-all"
                            :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.name }"
                        />
                        <p v-if="createForm.errors.name" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Phone Number *</label>
                            <input
                                v-model="createForm.phone"
                                type="text"
                                required
                                placeholder="e.g. 01712345678"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-medium outline-none transition-all"
                                :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.phone }"
                            />
                            <p v-if="createForm.errors.phone" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                                {{ createForm.errors.phone }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email Address</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                placeholder="e.g. john@example.com"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-medium outline-none transition-all"
                                :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.email }"
                            />
                            <p v-if="createForm.errors.email" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                                {{ createForm.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Shipping Address</label>
                        <textarea
                            v-model="createForm.address"
                            rows="2"
                            placeholder="e.g. House 12, Road 4, Sector 3, Uttara"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-medium outline-none transition-all resize-none shadow-inner"
                            :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.address }"
                        ></textarea>
                        <p v-if="createForm.errors.address" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                            {{ createForm.errors.address }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">District</label>
                            <input
                                v-model="createForm.district"
                                type="text"
                                placeholder="e.g. Dhaka"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-medium outline-none transition-all"
                                :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.district }"
                            />
                            <p v-if="createForm.errors.district" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                                {{ createForm.errors.district }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Thana</label>
                            <input
                                v-model="createForm.thana"
                                type="text"
                                placeholder="e.g. Uttara"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-medium outline-none transition-all"
                                :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.thana }"
                            />
                            <p v-if="createForm.errors.thana" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                                {{ createForm.errors.thana }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Zone Area</label>
                            <select
                                v-model="createForm.area"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 focus:bg-white focus:border-emerald-500 rounded-xl text-[11px] font-semibold outline-none appearance-none"
                                :class="{ 'border-red-500 bg-red-50/20 focus:border-red-500': createForm.errors.area }"
                            >
                                <option value="">Select Zone</option>
                                <option value="inside_dhaka">Inside Dhaka</option>
                                <option value="outside_dhaka">Outside Dhaka</option>
                            </select>
                            <p v-if="createForm.errors.area" class="text-[10px] text-red-500 font-semibold tracking-tight mt-1 ml-1">
                                {{ createForm.errors.area }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100"
                    >
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="text-[11px] font-bold text-gray-400 uppercase tracking-widest hover:text-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-emerald-100 active:scale-95 transition-all"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            Create Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Block/Unblock Confirmation Modal -->
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm"
        >
            <div
                class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200"
            >
                <div class="p-6 text-center">
                    <div
                        class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4"
                        :class="customerToToggle?.is_blocked ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'"
                    >
                        <User v-if="customerToToggle?.is_blocked" class="w-8 h-8" />
                        <Ban v-else class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2 font-outfit tracking-tight">
                        {{ customerToToggle?.is_blocked ? 'Unblock Customer' : 'Block Customer' }}
                    </h3>
                    <p class="text-[13px] text-gray-500 mb-6 leading-relaxed">
                        Are you sure you want to {{ customerToToggle?.is_blocked ? 'unblock' : 'block' }} <strong class="text-gray-800">{{ customerToToggle?.name }}</strong>?
                        <span v-if="!customerToToggle?.is_blocked" class="block mt-1">They will not be able to place new orders.</span>
                        <span v-else class="block mt-1">They will be able to place new orders again.</span>
                    </p>

                    <div class="flex items-center gap-3 w-full">
                        <button
                            @click="showConfirmModal = false"
                            class="flex-1 px-4 py-2.5 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-[11px] font-bold uppercase tracking-wider transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmToggleBlock"
                            class="flex-1 px-4 py-2.5 text-white rounded-xl text-[11px] font-bold uppercase tracking-wider shadow-md active:scale-95 transition-all"
                            :class="customerToToggle?.is_blocked ? 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/20' : 'bg-red-500 hover:bg-red-600 shadow-red-500/20'"
                        >
                            Yes, {{ customerToToggle?.is_blocked ? 'Unblock' : 'Block' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
