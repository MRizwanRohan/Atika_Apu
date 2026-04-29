<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    SquarePen,
    MapPin,
    Trash2Icon,
    User,
    Mail,
    Phone,
    Eye,
    MoreVertical,
    X,
    Hash,
    Truck,
    ChevronDown,
    ChevronUp,
} from "lucide-vue-next";
import CourierSelectionModal from "@/Components/Couriers/CourierSelectionModal.vue";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";
import StatusDropdown from "@/Components/Order/StatusDropdown.vue";
import FraudCheckerModal from "@/Components/Couriers/FraudCheckerModal.vue";
import AdminNotesModal from "@/Components/Order/AdminNotesModal.vue";
import PartialDeliveryModal from "@/Components/Order/PartialDeliveryModal.vue";

const props = defineProps({
    orders: Object,
    statusCounts: Object,
    cities: Array,
    sentOrderIds: {
        type: Array,
        default: () => [],
    },
    courierSettings: Object,
});

const showDeleteModal = ref(false);
const orderToDelete = ref(null);
const selectedOrders = ref([]);
const selectAll = ref(false);
const showCourierModal = ref(false);
const selectedOrderId = ref(null);
const selectedOrder = ref(null);
const savedOrderData = ref({});
const showAllItems = ref(false);
const loading = ref(false);
const isDownloading = ref(false); // New loading state for invoice download
const showAdminNotesModal = ref(false);
const selectedOrderForNotes = ref(null);

const showPartialModal = ref(false);
const selectedOrderForPartial = ref(null);

// ── Meta Tracking ──────────────────────────────────────────
const metaFiringOrderId = ref(null);
const isBulkFiringMeta = ref(false);
// Tracks orders that have had a Purchase event sent (merges server + session state)
const localSentOrderIds = ref(new Set(props.sentOrderIds));

async function fireMetaEvent(order) {
    metaFiringOrderId.value = order.id;
    try {
        const { data } = await axios.post(
            route("admin.meta-tracking.orders.fire", order.id),
        );
        if (data.success) {
            localSentOrderIds.value.add(order.id);
            localSentOrderIds.value = new Set(localSentOrderIds.value); // trigger reactivity
            toast.success("Purchase event sent to Meta! ✅");
        } else {
            toast.error(data.message || "Could not fire event.");
        }
    } catch (err) {
        const msg = err.response?.data?.message || "Failed to fire Meta event.";
        toast.error(msg);
    } finally {
        metaFiringOrderId.value = null;
    }
}

async function bulkFireMetaEvent() {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected.");
        return;
    }
    isBulkFiringMeta.value = true;
    try {
        const { data } = await axios.post(
            route("admin.meta-tracking.orders.bulk-fire"),
            { order_ids: selectedOrders.value },
        );
        // Mark all newly sent orders with badge
        data.sent.forEach((id) => localSentOrderIds.value.add(id));
        localSentOrderIds.value = new Set(localSentOrderIds.value);

        const parts = [];
        if (data.sent_count) parts.push(`${data.sent_count} sent`);
        if (data.skipped_count)
            parts.push(`${data.skipped_count} skipped (already sent)`);
        if (data.failed_count) parts.push(`${data.failed_count} failed`);
        toast.success(`Meta Bulk Fire: ${parts.join(", ")} ✅`);
    } catch (err) {
        toast.error(err.response?.data?.message || "Bulk fire failed.");
    } finally {
        isBulkFiringMeta.value = false;
    }
}
// ───────────────────────────────────────────────────────────

// Pagination computed values
const currentPage = ref(props.orders.current_page || 1);
const lastPage = ref(props.orders.last_page || 1);
const from = ref(props.orders.from || 0);
const to = ref(props.orders.to || 0);
const total = ref(props.orders.total || 0);

// Initialize filters
const initFilters = () => {
    const url = new URL(window.location.href);
    return {
        status: url.searchParams.get("status") || "",
        payment_status: url.searchParams.get("payment_status") || "",
        date_from: url.searchParams.get("date_from") || "",
        date_to: url.searchParams.get("date_to") || "",
        customer_search: url.searchParams.get("customer_search") || "",
        order_number: url.searchParams.get("order_number") || "",
        min_total: url.searchParams.get("min_total") || "",
        max_total: url.searchParams.get("max_total") || "",
        per_page: parseInt(url.searchParams.get("per_page")) || 10,
        sort_by: url.searchParams.get("sort_by") || "created_at",
        sort_direction: url.searchParams.get("sort_direction") || "desc",
    };
};

const filters = ref(initFilters());

// Per page options
const perPageOptions = [10, 25, 50, 100];

// Open delete modal
const openDeleteModal = (id) => {
    orderToDelete.value = id;
    showDeleteModal.value = true;
};

// Handle delete success
const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    orderToDelete.value = null;
};

// Toggle select all checkboxes
const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    selectedOrders.value = selectAll.value
        ? visibleOrders.value.map((order) => order.id)
        : [];
};

// Toggle individual order selection
const toggleOrderSelection = (orderId) => {
    const index = selectedOrders.value.indexOf(orderId);
    if (index === -1) {
        selectedOrders.value.push(orderId);
    } else {
        selectedOrders.value.splice(index, 1);
    }
    selectAll.value =
        selectedOrders.value.length === visibleOrders.value.length &&
        visibleOrders.value.length > 0;
};

// Open courier modal
const openCourierModal = (order, orderId) => {
    if (!orderId) {
        console.error("Order ID is undefined");
        toast.error("Invalid order ID");
        return;
    }
    selectedOrderId.value = orderId;
    selectedOrder.value = order;
    showCourierModal.value = true;
};

// Handle courier submission for individual order
const handleCourierSubmit = (data) => {
    if (!data || !data.orderId) {
        console.error("Invalid data received in handleCourierSubmit:", data);
        toast.error("Invalid courier data");
        return;
    }
    savedOrderData.value[data.orderId] = {
        recipient_city: data.city_name,
        recipient_zone: data.zone_name,
        recipient_area: data.area_name,
        specialInstruction:
            data.specialInstruction || "Need to Delivery before 5 PM",
    };
    showCourierModal.value = false;
};

// Send selected orders to Pathao
const sendBulkToPathao = () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    const payload = {
        orders: selectedOrders.value.map((orderId) => ({
            orderId,
            specialInstruction: savedOrderData.value[orderId]?.specialInstruction || "Need to Delivery before 5 PM",
        })),
    };

    router.post("/admin/couriers/pathao/shipments/bulk", payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            selectedOrders.value = [];
            selectAll.value = false;
            savedOrderData.value = {};
            toast.success(page.props.flash?.message || "Bulk shipments created successfully");
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to create bulk shipments");
        },
    });
};

const sendBulkToSteadfast = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    const payload = {
        orderIds: selectedOrders.value,
    };

    try {
        await axios.post("/api/courier/orders/bulk", payload);

        selectedOrders.value = [];
        selectAll.value = false;
        savedOrderData.value = {};

        toast.success("Shipments created successfully");
    } catch (error) {
        const message =
            error.response?.data?.error || error.message || "Unknown error";

        toast.error("Failed to create shipment: " + message);
    }
};

// Download bulk invoices
const downloadBulkInvoice = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    isDownloading.value = true; // Set loading state to true
    try {
        const response = await axios.post(
            "/admin/bulk-invoice/download",
            { order_ids: selectedOrders.value },
            { responseType: "blob" },
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute(
            "download",
            `bulk-invoices-${new Date().toISOString()}.pdf`,
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.success("Invoices downloaded successfully");
    } catch (error) {
        console.error("Error downloading invoices:", error);
        toast.error(
            "Failed to download invoices: " +
                (error.response?.data?.message || "Unknown error"),
        );
    } finally {
        isDownloading.value = false; // Reset loading state
    }
};

// Status and payment status options
const statusOptions = [
    "pending",
    "processing",
    "completed",
    "cancelled",
    "incomplete",
    "on_hold",
    "confirmed",
    "partially_delivered",
];
const paymentStatusOptions = ["unpaid", "paid", "refunded", "partially_paid"];
const availableStatuses = [
    "pending",
    "processing",
    "cancelled",
    "shipped",
    "delivered",
    "returned",
    "incomplete",
    "on_hold",
    "confirmed",
    "partially_delivered",
];

// Helper to determine if a date string is today (date portion only)
const isToday = (dateString) => {
    if (!dateString) return false;
    const d = new Date(dateString);
    const today = new Date();
    return (
        d.getFullYear() === today.getFullYear() &&
        d.getMonth() === today.getMonth() &&
        d.getDate() === today.getDate()
    );
};

// Expose all orders by default
const visibleOrders = computed(() => {
    return props.orders?.data ?? [];
});

// Apply filters with debounce
let debounceTimer;
const applyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        currentPage.value = 1; // Reset to page 1 on filter change
        router.get(
            "/admin/orders",
            { ...filters.value, page: currentPage.value },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                },
                onError: () => {
                    loading.value = false;
                },
            },
        );
    }, 500);
};

// Reset filters
const resetFilters = () => {
    filters.value = initFilters();
    currentPage.value = 1; // Reset to page 1 when filters are reset
    applyFilters();
};

// Helper functions for Status Chips
const getStatusChipClass = (status) => {
    const isActive = filters.value.status === status;
    
    if (isActive) {
        const activeClasses = {
            pending: 'bg-blue-600 text-white border-blue-600 scale-105 shadow-blue-100',
            confirmed: 'bg-violet-600 text-white border-violet-600 scale-105 shadow-violet-100',
            processing: 'bg-emerald-600 text-white border-emerald-600 scale-105 shadow-emerald-100',
            shipped: 'bg-amber-600 text-white border-amber-600 scale-105 shadow-amber-100',
            delivered: 'bg-indigo-600 text-white border-indigo-600 scale-105 shadow-indigo-100',
            cancelled: 'bg-rose-600 text-white border-rose-600 scale-105 shadow-rose-100',
            returned: 'bg-primary text-white border-primary scale-105 shadow-primary/20',
            on_hold: 'bg-cyan-600 text-white border-cyan-600 scale-105 shadow-cyan-100',
            partially_delivered: 'bg-slate-700 text-white border-slate-700 scale-105 shadow-slate-200'
        };
        return activeClasses[status] || 'bg-gray-900 text-white border-gray-900 scale-105';
    }

    // Default colorful (soft versions)
    const softClasses = {
        pending: 'bg-blue-50 text-blue-700 border-blue-100 hover:bg-blue-100',
        confirmed: 'bg-violet-50 text-violet-700 border-violet-100 hover:bg-violet-100',
        processing: 'bg-emerald-50 text-emerald-700 border-emerald-100 hover:bg-emerald-100',
        shipped: 'bg-amber-50 text-amber-700 border-amber-100 hover:bg-amber-100',
        delivered: 'bg-indigo-50 text-indigo-700 border-indigo-100 hover:bg-indigo-100',
        cancelled: 'bg-rose-50 text-rose-700 border-rose-100 hover:bg-rose-100',
        returned: 'bg-primary/5 text-primary border-primary/10 hover:bg-primary/10',
        on_hold: 'bg-cyan-50 text-cyan-700 border-cyan-100 hover:bg-cyan-100',
        partially_delivered: 'bg-slate-50 text-slate-700 border-slate-100 hover:bg-slate-100'
    };
    return softClasses[status] || 'bg-white text-gray-500 border-gray-100';
};

const getStatusDotClass = (status) => {
    const classes = {
        pending: 'bg-white',
        confirmed: 'bg-white',
        processing: 'bg-white',
        shipped: 'bg-white',
        delivered: 'bg-white',
        cancelled: 'bg-white',
        returned: 'bg-white',
        on_hold: 'bg-white',
        partially_delivered: 'bg-white'
    };
    return filters.value.status === status ? (classes[status] || 'bg-white') : 
           (status === 'pending' ? 'bg-blue-500' : 
            status === 'confirmed' ? 'bg-violet-500' :
            status === 'processing' ? 'bg-emerald-500' :
            status === 'shipped' ? 'bg-amber-500' :
            status === 'delivered' ? 'bg-indigo-500' :
            status === 'cancelled' ? 'bg-rose-500' :
            status === 'returned' ? 'bg-primary' :
            status === 'on_hold' ? 'bg-cyan-500' : 'bg-gray-400');
};

const activeFilterCount = computed(() => {
    let count = 0;
    if (filters.value.payment_status) count++;
    if (filters.value.date_from) count++;
    if (filters.value.date_to) count++;
    if (filters.value.order_number) count++;
    if (filters.value.min_total) count++;
    if (filters.value.max_total) count++;
    return count;
});

// Handle status card click
const selectStatus = (status) => {
    filters.value.status = status === filters.value.status ? "" : status;
    currentPage.value = 1; // Reset to page 1 when status changes
    applyFilters();
};

// Handle sorting
const toggleSort = (column) => {
    const validColumns = [
        "id",
        "order_number",
        "status",
        "customer_email",
        "customer_name",
        "total",
        "created_at",
        "payment_status",
    ];
    if (!validColumns.includes(column)) return;
    if (filters.value.sort_by === column) {
        filters.value.sort_direction =
            filters.value.sort_direction === "asc" ? "desc" : "asc";
    } else {
        filters.value.sort_by = column;
        filters.value.sort_direction = "asc";
    }
    currentPage.value = 1; // Reset to page 1 when sorting changes
    applyFilters();
};

const truncateText = (text, limit) => {
    const words = text.split(" ");
    if (words.length > limit) {
        return words.slice(0, limit).join(" ") + "...";
    }
    return text;
};

// Get sort icon
const getSortIcon = (column) => {
    if (filters.value.sort_by !== column) return "";
    return filters.value.sort_direction === "asc" ? "↑" : "↓";
};

// Format date
const expandedOrders = ref(new Set());

const toggleOrderExpand = (orderId) => {
    if (expandedOrders.value.has(orderId)) {
        expandedOrders.value.delete(orderId);
    } else {
        expandedOrders.value.add(orderId);
    }
};

const formatOrderDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleString();
};

// Pagination navigation
const goToPage = (page) => {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value ||
        loading.value
    )
        return;

    loading.value = true;
    currentPage.value = page;
    router.get(
        "/admin/orders",
        { ...filters.value, page: page },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
                currentPage.value = props.orders.current_page;
                lastPage.value = props.orders.last_page;
                from.value = props.orders.from;
                to.value = props.orders.to;
                total.value = props.orders.total;
            },
            onError: () => {
                loading.value = false;
            },
        },
    );
};

// Watch filters and orders
watch(filters, applyFilters, { deep: true });
watch(
    () => props.orders,
    () => {
        selectedOrders.value = [];
        selectAll.value = false;
        const currentOrderIds = props.orders.data.map((order) => order.id);
        savedOrderData.value = Object.fromEntries(
            Object.entries(savedOrderData.value).filter(([id]) =>
                currentOrderIds.includes(Number(id)),
            ),
        );
        // Update pagination values
        currentPage.value = props.orders.current_page || 1;
        lastPage.value = props.orders.last_page || 1;
        from.value = props.orders.from || 0;
        to.value = props.orders.to || 0;
        total.value = props.orders.total || 0;
    },
    { deep: true },
);

// Create a loading state map to track which orders are being updated
const updatingOrders = ref({});

// Status update function using Composition API
const updateOrderStatus = async (orderId, newStatus) => {
    if (newStatus === "partially_delivered" || newStatus === "returned") {
        const order = props.orders?.data?.find((o) => o.id === orderId);
        if (order) {
            selectedOrderForPartial.value = order;
            showPartialModal.value = true;
        } else {
            toast.error("Order details not found.");
        }
        return; // Stop here, modal will handle the rest
    }

    updatingOrders.value[orderId] = true;

    try {
        const response = await axios.put(
            route("admin.orders.status.update", { orderId }),
            {
            status: newStatus,
            },
        );
        toast.success(
            response.data.message || "Order status updated successfully",
        );
        router.reload();
    } catch (error) {
        console.error("Error updating order status:", error);
        toast.error(
            error.response?.data?.message || "Failed to update order status",
        );
    } finally {
        updatingOrders.value[orderId] = false;
    }
};

const showModal = ref(false);
const fraudData = ref({});

const checkFraudLoading = ref(false);
const showFilters = ref(false);

const checkFraud = async (phone) => {
    try {
        checkFraudLoading.value = true;
        const response = await axios.post("/admin/fraud-check", { phone });
        fraudData.value = response.data;
        showModal.value = true;
        toast.success(response.data.message || "Fraud check successful");
    } catch (error) {
        console.error("Fraud check error:", error);
        toast.error(error.response?.data?.message || "Failed to check fraud");
    } finally {
        checkFraudLoading.value = false;
    }
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

// Print bulk invoices
const printBulkInvoice = () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    // Open new window with bulk print URL
    const printWindow = window.open(
        `/admin/bulk-invoice/print?order_ids=${selectedOrders.value.join(",")}`,
        "_blank",
        "width=" +
            screen.width +
            ",height=" +
            screen.height +
            ",scrollbars=yes,resizable=yes,fullscreen=yes",
    );

    if (printWindow) {
        printWindow.focus();
    } else {
        toast.error(
            "Failed to open print window. Please allow popups for this site.",
        );
    }
};

// Action Menu State
const activeActionMenu = ref(null);

const toggleActionMenu = (orderId) => {
    if (activeActionMenu.value === orderId) {
        activeActionMenu.value = null;
    } else {
        activeActionMenu.value = orderId;
    }
};

// Admin Notes Modal Functions
const openAdminNotesModal = (order) => {
    selectedOrderForNotes.value = order;
    showAdminNotesModal.value = true;
};

const openPartialModal = (order) => {
    selectedOrderForPartial.value = order;
    showPartialModal.value = true;
};

const closeAdminNotesModal = () => {
    showAdminNotesModal.value = false;
    selectedOrderForNotes.value = null;
};

const handleNotesSaved = (newNotes) => {
    // The page will be refreshed by Inertia after successful save
    toast.success("Admin notes saved successfully");
};

// Close menu when clicking outside (optional but good for UX)
// For simplicity in this step, we'll just rely on the toggle or clicking another one.



const sendToPathao = (order) => {
    router.post(route('admin.couriers.pathao.shipment'), {
        orderId: order.id,
        specialInstruction: "Need to Delivery before 5 PM"
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            toast.success(page.props.flash?.message || "Order sent to Pathao successfully");
        },
        onError: (errors) => {
            toast.error(errors.message || "Failed to send order to Pathao");
        },
    });
};

const sendToSteadfastSingle = async (order) => {
    try {
        const response = await axios.post('/api/courier/orders', {
            oders_id: order.id.toString()
        });
        toast.success(response.data.message || 'Order sent to Steadfast successfully');
        router.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to send order to Steadfast');
    }
};


</script>

<template>
    <Head title="Order Management" />
    <AdminLayout>
        <div
            v-if="checkFraudLoading"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div
                class="w-12 h-12 border-4 border-white border-t-transparent rounded-full animate-spin"
            ></div>
        </div>
        <!-- <pre>{{ orders }}</pre> -->

        <div class="order_management p-1 rounded-lg">
            <div class="flex items-center flex-wrap justify-between mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Order Management
                    </h1>
                    <Link
                        :href="route('admin.orders.incomplete')"
                        class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm font-semibold shadow hover:bg-amber-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3"
                            />
                        </svg>
                        Incomplete:
                        {{
                            props.statusCounts.find(
                                (s) => s.status === "incomplete",
                            )?.count || 0
                        }}
                    </Link>
                </div>
                <button
                    @click="resetFilters"
                    class="btn bg-white border px-3 py-1 rounded text-sm"
                >
                    Reset Filters
                </button>
            </div>

            <!-- Status Horizontal Scroll (Minimalist Chips) -->
            <div class="mb-8 mt-4 ">
                <div class="flex items-center flex-wrap  gap-2 pb-2">
                    <!-- Total Orders Chip -->
                    <button
                        @click="selectStatus('')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-300 font-black text-[11px] uppercase tracking-tighter border shadow-sm"
                        :class="filters.status === '' 
                            ? 'bg-gray-900 text-white border-gray-900 shadow-gray-200 scale-105' 
                            : 'bg-white text-gray-500 border-gray-100 hover:border-gray-300 hover:bg-gray-50'"
                    >
                        <div class="w-1.5 h-1.5 rounded-full" :class="filters.status === '' ? 'bg-blue-400' : 'bg-gray-300'"></div>
                        Total
                        <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="filters.status === '' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-400'">
                            {{ props.statusCounts.find(s => s.status === 'total')?.count || 0 }}
                        </span>
                    </button>

                    <!-- Dynamic Status Chips -->
                    <button
                        v-for="status in availableStatuses.filter(s => s !== 'incomplete')"
                        :key="status"
                        @click="selectStatus(status)"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-300 font-black text-[11px] uppercase tracking-tighter border shadow-sm"
                        :class="getStatusChipClass(status)"
                    >
                        <div class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(status)"></div>
                        {{ status.replace('_', ' ') }}
                        <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="filters.status === status ? 'bg-white/20 text-white' : 'bg-black/5 text-black/40'">
                            {{ props.statusCounts.find(s => s.status === status)?.count || 0 }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Filter Toggle & Search Bar -->
            <div class="mb-6 flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="relative w-full md:w-1/2">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="filters.customer_search"
                        type="text"
                        placeholder="Search customer name, phone or email or order number..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 transition-all font-medium"
                    />
                </div>
                
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button
                        @click="toggleFilters"
                        class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all"
                        :class="showFilters 
                            ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Advanced Filters
                        <span v-if="activeFilterCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-blue-600 text-[10px] font-black">
                            {{ activeFilterCount }}
                        </span>
                    </button>
                    
                    <button
                        @click="resetFilters"
                        class="p-2.5 rounded-xl bg-gray-50 text-gray-400 hover:text-rose-500 hover:bg-rose-50 transition-all border border-gray-100"
                        title="Reset all filters"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Advanced Filters Panel -->
            <div
                v-show="showFilters"
                class="mb-6 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Date Range -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest pl-1">Order Date</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="flex-1 px-3 py-2 bg-gray-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/20"
                            />
                            <span class="text-gray-300">to</span>
                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="flex-1 px-3 py-2 bg-gray-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/20"
                            />
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest pl-1">Payment Status</label>
                        <select
                            v-model="filters.payment_status"
                            class="w-full px-3 py-2 bg-gray-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/20 cursor-pointer"
                        >
                            <option value="">All Payments</option>
                            <option v-for="status in paymentStatusOptions" :key="status" :value="status">
                                {{ status.toUpperCase().replace('_', ' ') }}
                            </option>
                        </select>
                    </div>

                    <!-- Order ID -->
                    <!-- <div class="space-y-2">
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest pl-1">Order ID</label>
                        <input
                            v-model="filters.order_number"
                            type="text"
                            placeholder="ORD-XXXX..."
                            class="w-full px-3 py-2 bg-gray-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div> -->

                    <!-- Amount Range -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest pl-1">Total Amount</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="filters.min_total"
                                type="number"
                                placeholder="Min"
                                class="flex-1 px-3 py-2 bg-gray-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/20"
                            />
                            <input
                                v-model="filters.max_total"
                                type="number"
                                placeholder="Max"
                                class="flex-1 px-3 py-2 bg-gray-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/20"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 flex justify-end flex-wrap gap-1 items-center space-x-2">
                <button
                    v-if="selectedOrders.length > 0"
                    @click="bulkFireMetaEvent"
                    :disabled="isBulkFiringMeta"
                    class="px-2 py-2 bg-blue-700 text-white rounded-md text-sm font-medium hover:bg-blue-800 disabled:opacity-60 flex items-center gap-1"
                >
                    <span
                        v-if="isBulkFiringMeta"
                        class="loading loading-spinner loading-xs"
                    ></span>
                    <span v-else>📡</span>
                    {{
                        isBulkFiringMeta
                            ? "Sending..."
                            : `Send ${selectedOrders.length} to Meta`
                    }}
                </button>
                <button
                    v-if="selectedOrders.length > 0 && courierSettings?.steadfast_is_enabled === 'yes'"
                    @click="sendBulkToSteadfast"
                    class="px-2 py-2 bg-orange-600 text-white rounded-md text-sm font-medium hover:bg-orange-700 hover:shadow-lg transition-all duration-200"
                    :disabled="isDownloading"
                >
                    🚚 Send {{ selectedOrders.length }} to Steadfast
                </button>
                <button
                    v-if="selectedOrders.length > 0 && courierSettings?.is_enabled === 'yes'"
                    @click="sendBulkToPathao"
                    class="px-2 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 hover:shadow-lg transition-all duration-200"
                    :disabled="isDownloading"
                >
                    🛵 Send {{ selectedOrders.length }} to Pathao
                </button>
                <!-- <button
                    v-if="selectedOrders.length > 0"
                    @click="downloadBulkInvoice"
                    class="px-2 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isDownloading"
                >
                    <span v-if="isDownloading">Downloading...</span>
                    <span v-else
                        >Download {{ selectedOrders.length }} Invoices</span
                    >
                </button> -->

                <button
                    v-if="selectedOrders.length > 0"
                    @click="printBulkInvoice"
                    class="px-4 py-2 bg-purple-500 text-white rounded-md text-sm font-medium hover:bg-purple-600"
                >
                    Print {{ selectedOrders.length }} Invoices
                </button>
            </div>

            <!-- Per Page Filter -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <label class="text-sm font-medium text-gray-700 mr-2"
                        >Show</label
                    >
                    <select
                        v-model="filters.per_page"
                        class="rounded-md border-gray-300 shadow-sm text-sm"
                    >
                        <option
                            v-for="option in perPageOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ option }}
                        </option>
                    </select>
                    <span class="text-sm text-gray-700 ml-2">entries</span>
                </div>
            </div>

            <!-- <pre>{{ orders }}</pre> -->

            <!-- Orders Table (Desktop) -->
            <div
                class="hidden md:block overflow-x-auto rounded-xl shadow-lg border border-gray-100"
            >
                <table class="min-w-full divide-y table-auto">
                    <thead class="bg-gray-900 text-white">
                        <tr>
                            <th
                                class="px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider w-12 rounded-tl-xl transition-colors duration-200"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectAll"
                                    @change="toggleSelectAll"
                                    class="rounded text-blue-500 focus:ring-blue-500 bg-gray-800 border-gray-600"
                                />
                            </th>
                            <th
                                @click="toggleSort('order_number')"
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Order {{ getSortIcon("order_number") }}
                                </div>
                            </th>
                            <th
                                @click="toggleSort('customer_name')"
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Customer {{ getSortIcon("customer_name") }}
                                </div>
                            </th>
                            <th
                                class="px-3 py-4 text-left text-xs min-w-[200px] font-semibold uppercase tracking-wider transition-colors duration-200"
                            >
                                Products
                            </th>
                            <th
                                @click="toggleSort('total')"
                                class="px-3 py-4 text-left text-xs min-w-[100px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Total {{ getSortIcon("total") }}
                                </div>
                            </th>
                            <th
                                @click="toggleSort('status')"
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Status {{ getSortIcon("status") }}
                                </div>
                            </th>
                            <!-- <th
                                @click="toggleSort('payment_status')"
                                class="px-3 py-4 text-left text-xs font-semibold min-w-[100px] uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Payment {{ getSortIcon("payment_status") }}
                                </div>
                            </th> -->
                            <th
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider transition-colors duration-200"
                            >
                                Courier Info
                            </th>
                            <th
                                class="px-3 py-4 text-left text-xs min-w-[50px] font-semibold uppercase tracking-wider rounded-tr-xl transition-colors duration-200"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="(order, index) in visibleOrders"
                            :key="order.id"
                            class="hover:bg-gray-50 transition-colors duration-150"
                            :class="{
                                'bg-blue-50/50': selectedOrders.includes(
                                    order.id,
                                ),
                            }"
                        >
                            <td class="px-1 py-3 text-sm">
                                <input
                                    type="checkbox"
                                    :value="order.id"
                                    :checked="selectedOrders.includes(order.id)"
                                    @change="toggleOrderSelection(order.id)"
                                    class="rounded text-blue-500"
                                />
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <p
                                    class="font-semibold text-sm bg-green-500 text-white px-2 py-1 rounded inline-flex items-center gap-1"
                                >
                                    {{ order.order_number }}
                                    <span
                                        v-if="order.admin_notes"
                                        class="inline-flex items-center justify-center w-4 h-4 bg-blue-500 rounded-full text-white text-[10px] font-bold"
                                        title="Has admin notes"
                                    >
                                        N
                                    </span>
                                </p>
                                <!-- Meta event sent badge -->
                                <span
                                    v-if="localSentOrderIds.has(order.id)"
                                    class="mt-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800 border border-blue-300"
                                    title="Purchase event sent to Meta"
                                >
                                    📡 Meta Sent
                                </span>
                                <p
                                    class="mt-1 text-black flex items-center gap-1"
                                >
                                    <span
                                        v-if="isToday(order.date)"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800 border border-green-300"
                                    >
                                        TODAY
                                    </span>
                                    Date: {{ formatOrderDate(order.date) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Updated:
                                    {{ formatOrderDate(order.updated_at) }}
                                </p>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <div
                                    class="border border-gray-200 rounded-lg p-3 bg-gradient-to-br from-white to-gray-50 hover:shadow-md transition-shadow"
                                >
                                    <p
                                        class="truncate flex items-center flex-wrap font-medium text-gray-800"
                                    >
                                        <User
                                            class="w-4 h-4 mr-1 text-blue-600"
                                        />{{
                                            truncateText(order.customer.name, 2)
                                        }}
                                    </p>
                                    <p
                                        v-if="order.customer?.email && order.customer?.email !== 'N/A'"
                                        class="text-gray-600 flex items-center text-xs mt-1"
                                    >
                                        <Mail
                                            class="w-3 h-3 mr-1 text-gray-500"
                                        />{{ order.customer?.email }}
                                    </p>
                                    <div
                                        class="text-gray-600 flex items-center flex-wrap gap-1 text-xs mt-1"
                                    >
                                        <Phone
                                            class="w-3 h-3 text-gray-500"
                                        /><span>{{ order.customer?.phone }}</span>
                                        <span
                                            v-if="order.duplicate_percentage > 0"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200"
                                            title="Duplicate order from this phone number"
                                        >
                                            Duplicate {{ order.duplicate_percentage }}%
                                        </span>
                                    </div>
                                    <p
                                        class="text-gray-600 flex items-center text-xs mt-1"
                                    >
                                        <MapPin
                                            class="w-3 h-3 mr-1 text-gray-500"
                                        />{{ order.customer?.address }}
                                    </p>
                                    <p
                                        v-if="
                                            order.customer?.note &&
                                            order.customer.note !== 'N/A'
                                        "
                                        class="flex text-green-800 bg-green-200 py-1 rounded text-sm mt-1 items-center"
                                    >
                                        <Hash class="w-4 h-4 mr-1" />Note:
                                        {{ order.customer?.note || "N/A" }}
                                    </p>
                                    <div v-if="order.admin_notes" class="mt-2">
                                        <div
                                            class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-2"
                                        >
                                            <div class="flex items-start gap-2">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    />
                                                </svg>
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="text-xs font-semibold text-blue-800 mb-0.5"
                                                    >
                                                        Admin Notes:
                                                    </p>
                                                    <p
                                                        class="text-xs text-blue-700 line-clamp-2"
                                                    >
                                                        {{ order.admin_notes }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        @click="openAdminNotesModal(order)"
                                        class="mt-2 w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-all"
                                        :class="
                                            order.admin_notes
                                                ? 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        "
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                        {{
                                            order.admin_notes
                                                ? "Edit Notes"
                                                : "Add Notes"
                                        }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <div class="flex flex-col gap-2">
                                    <div
                                        v-if="order.items.length === 0"
                                        class="text-gray-400 italic text-xs py-2 px-3 border border-dashed border-gray-300 rounded-lg bg-gray-50"
                                    >
                                        No items
                                    </div>

                                    <div class="space-y-2">
                                        <div
                                            v-for="(item, index) in order.items"
                                            :key="item.id"
                                            v-show="index < (showAllItems ? order.items.length : 2)"
                                            class="group relative flex items-start gap-3 bg-gray-50 border border-gray-200 rounded-xl p-2.5 hover:border-blue-400 hover:bg-blue-50/30 hover:shadow-md transition-all duration-200"
                                        >
                                            <div class="relative flex-shrink-0">
                                                <img
                                                    :src="item.product.image || ''"
                                                    alt="Product Image"
                                                    class="w-14 h-14 object-cover rounded-lg border border-gray-300 shadow-sm"
                                                />
                                                <span class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-[11px] font-bold text-white shadow-md ring-2 ring-white">
                                                    {{ item.quantity }}
                                                </span>
                                            </div>

                                            <div class="flex-1 min-w-0 flex flex-col justify-center">
                                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                    <p class="text-[13px] font-bold text-gray-900 leading-tight group-hover:text-blue-700 transition-colors">
                                                        {{ truncateText(item.product.name, 3) }}
                                                    </p>
                                                    <span
                                                        v-if="item.is_pre_order"
                                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-orange-100 text-orange-700 border border-orange-200 uppercase tracking-tight"
                                                    >
                                                        Pre-Order
                                                    </span>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    <span class="text-[11px] font-bold text-blue-700 bg-blue-100/50 px-1.5 py-0.5 rounded">
                                                        ৳{{ item.price }}
                                                    </span>
                                                    
                                                    <div v-if="item.variation" class="flex items-center gap-1 text-[11px] text-gray-600 font-semibold bg-gray-200/50 px-1.5 py-0.5 rounded">
                                                        <span v-for="(value, key, i) in item.variation.attributes" :key="key">
                                                            {{ value }}{{ i < Object.keys(item.variation.attributes).length - 1 ? ', ' : '' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Status badges for partial delivery -->
                                                <div
                                                    v-if="order.status === 'partially_delivered' || order.status === 'returned' || item.returned_quantity > 0"
                                                    class="flex gap-1.5 mt-2"
                                                >
                                                    <span
                                                        v-if="item.quantity - item.returned_quantity > 0"
                                                        class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white text-[10px] font-black shadow-sm"
                                                    >
                                                        DELIVERED: {{ item.quantity - item.returned_quantity }}
                                                    </span>
                                                    <span
                                                        v-if="item.returned_quantity > 0"
                                                        class="px-2 py-0.5 rounded-lg bg-rose-600 text-white text-[10px] font-black shadow-sm"
                                                    >
                                                        RETURNED: {{ item.returned_quantity }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="order.items.length > 2" class="mt-2.5 pl-1">
                                    <button
                                        @click="showAllItems = !showAllItems"
                                        class="inline-flex items-center gap-1.5 text-[12px] font-black text-white px-3 py-1.5 bg-gray-800 hover:bg-blue-600 rounded-lg shadow-sm transition-all active:scale-95"
                                    >
                                        {{ showAllItems ? 'Show Less' : `+${order.items.length - 2} More Items` }}
                                        <ChevronDown v-if="!showAllItems" class="w-3.5 h-3.5" />
                                        <ChevronUp v-else class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-2.5 space-y-1.5">
                                    <!-- Total -->
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total</span>
                                        <span class="text-sm font-black text-gray-900">৳{{ order.total }}</span>
                                    </div>

                                    <!-- Paid -->
                                    <div v-if="order.status === 'partially_delivered' || order.paid_amount > 0" class="flex items-center justify-between border-t border-gray-100 pt-1.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Paid</span>
                                        <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">
                                            <div class="w-1 h-1 rounded-full bg-emerald-500"></div>
                                            ৳{{ order.paid_amount || 0 }}
                                        </div>
                                    </div>

                                    <!-- Return Charge -->
                                    <div v-if="order.return_charge > 0" class="flex items-center justify-between border-t border-gray-100 pt-1.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Return</span>
                                        <span class="text-[11px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-md">
                                            ৳{{ order.return_charge }}
                                        </span>
                                    </div>

                                    <!-- Due (Optional Visual) -->
                                    <div v-if="order.total > (order.paid_amount || 0)" class="flex items-center justify-between border-t border-gray-100 pt-1.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Due</span>
                                        <span class="text-[11px] font-bold text-amber-600">
                                            ৳{{ order.total - (order.paid_amount || 0) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <StatusDropdown
                                    v-model="order.status"
                                    :order-id="order.id"
                                    :is-loading="updatingOrders[order.id]"
                                    @status-change="updateOrderStatus"
                                />
                            </td>
                            <!-- <td
                                class="px-1 py-3 text-sm gap-1"
                            >
                                <div
                                    :class="{
                                        'px-2 py-1 rounded-full text-xs font-medium inline-block text-center': true,
                                        'bg-red-100 text-red-800':
                                            order.payment_status === 'unpaid',
                                        'bg-green-100 text-green-800':
                                            order.payment_status === 'paid',
                                        'bg-blue-100 text-blue-800':
                                            order.payment_status ===
                                            'partially_paid',
                                        'bg-gray-100 text-black':
                                            order.payment_status === 'refunded',
                                    }"
                                >
                                    {{ order.payment_status }}
                                </div>
                            </td> -->
                            <td class="px-2 py-2 text-sm w-32">
                                <div
                                    v-if="order.is_courier"
                                    class="flex flex-col gap-1.5 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm group hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-300"
                                >
                                    <!-- Courier Badge -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5 overflow-hidden">
                                            <div class="p-1 rounded bg-blue-50 dark:bg-blue-900/30">
                                                <Truck class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate">
                                                {{ order.courier_name }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Tracking ID -->
                                    <div class="flex flex-col">
                                        <div class="text-[9px] text-gray-400 dark:text-gray-500 font-medium">CONSIGNMENT ID</div>
                                        <div class="font-mono text-[11px] font-bold text-gray-700 dark:text-gray-200 truncate leading-tight">
                                            {{ order.tracking_number || 'PENDING' }}
                                        </div>
                                    </div>

                                    <!-- Action Link -->
                                    <a
                                        v-if="order?.tracking_number && order?.tracking_number !== 'N/A'"
                                        :href="order.courier_name === 'pathao'
                                            ? `https://merchant.pathao.com/tracking?consignment_id=${order.tracking_number}`
                                            : `https://steadfast.com.bd/t/${order.tracking_number}`"
                                        target="_blank"
                                        class="mt-1 flex items-center justify-center gap-1 px-2 py-1 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 text-[10px] font-bold rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600 transition-all duration-200 border border-blue-100 dark:border-blue-800/50"
                                    >
                                        <span>Track</span>
                                        <Eye class="w-3 h-3" />
                                    </a>
                                </div>
                                <div v-else class="text-center italic text-gray-300 dark:text-gray-600 text-[10px]">
                                    Not Shipped
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm">
                                <div class="relative flex justify-center">
                                    <button
                                        @click.stop="toggleActionMenu(order.id)"
                                        class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'bg-blue-50 text-blue-600':
                                                activeActionMenu === order.id,
                                        }"
                                    >
                                        <component
                                            :is="
                                                activeActionMenu === order.id
                                                    ? X
                                                    : MoreVertical
                                            "
                                            class="w-5 h-5"
                                        />
                                    </button>

                                    <!-- Action Menu Dropdown -->
                                    <div
                                        v-if="activeActionMenu === order.id"
                                        class="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-200"
                                    >
                                        <div class="py-1">
                                            <button
                                                @click="
                                                    checkFraud(
                                                        order?.customer?.phone,
                                                    );
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 transition-colors"
                                            >
                                                <User class="w-4 h-4" />
                                                Check Fraud
                                            </button>

                                            <!-- <Link
                                                :href="
                                                    route(
                                                        'admin.orders.show',
                                                        order.id
                                                    )
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 transition-colors"
                                            >
                                                <Eye class="w-4 h-4" />
                                                View Details
                                            </Link> -->

                                            <!-- <button
                                                @click="
                                                    openCourierModal(
                                                        order,
                                                        order.id,
                                                    );
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-green-600 flex items-center gap-2 transition-colors"
                                            >
                                                <MapPin class="w-4 h-4" />
                                                Couriers
                                            </button> -->

                                            <button
                                                @click="
                                                    sendToPathao(order);
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-green-600 flex items-center gap-2 transition-colors"
                                            >
                                                <Truck class="w-4 h-4" />
                                                Send to Pathao
                                            </button>

                                            <button
                                                @click="
                                                    sendToSteadfastSingle(order);
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 transition-colors"
                                            >
                                                <Truck class="w-4 h-4" />
                                                Send to Steadfast
                                            </button>

                                            <!-- Partial Delivery Button -->
                                            <Link
                                                :href="
                                                    route(
                                                        'admin.orders.edit',
                                                        order.id,
                                                    )
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-amber-600 flex items-center gap-2 transition-colors"
                                            >
                                                <SquarePen class="w-4 h-4" />
                                                Edit Order
                                            </Link>

                                            <div
                                                class="border-t border-gray-100 my-1"
                                            ></div>

                                            <!-- ── Meta Tracking Actions ── -->
                                            <button
                                                @click="
                                                    fireMetaEvent(order);
                                                    toggleActionMenu(order.id);
                                                "
                                                :disabled="
                                                    metaFiringOrderId ===
                                                    order.id
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 flex items-center gap-2 transition-colors"
                                            >
                                                <span
                                                    v-if="
                                                        metaFiringOrderId ===
                                                        order.id
                                                    "
                                                    class="loading loading-spinner loading-xs"
                                                ></span>
                                                <span v-else>📡</span>
                                                Send to Meta
                                            </button>

                                            <div
                                                class="border-t border-gray-100 my-1"
                                            ></div>

                                            <button
                                                @click="
                                                    openDeleteModal(order.id);
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2 transition-colors"
                                            >
                                                <Trash2Icon class="w-4 h-4" />
                                                Delete
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Backdrop to close menu -->
                                    <div
                                        v-if="activeActionMenu === order.id"
                                        class="fixed inset-0 z-40 cursor-default"
                                        @click="activeActionMenu = null"
                                    ></div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="visibleOrders.length === 0">
                            <td
                                colspan="10"
                                class="px-1 py-6 text-center text-black"
                            >
                                No orders found matching your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Bulk Actions & Select All -->
            <div class="md:hidden mb-4 bg-white p-3 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-2 py-1 bg-gray-50 rounded-xl border border-gray-200">
                        <input
                            type="checkbox"
                            :checked="selectAll"
                            @change="toggleSelectAll"
                            class="rounded text-blue-500 w-5 h-5 border-gray-300 focus:ring-offset-0"
                        />
                        <span class="text-xs font-black text-gray-700 uppercase tracking-tighter">Select All</span>
                    </div>
                    <span v-if="selectedOrders.length > 0" class="text-[10px] font-black bg-blue-600 text-white px-2 py-1 rounded-lg">
                        {{ selectedOrders.length }} SELECTED
                    </span>
                </div>
            </div>

            <!-- Mobile Orders View -->
            <div class="md:hidden space-y-3">
                <div 
                    v-for="order in visibleOrders" 
                    :key="order.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300"
                    :class="{ 'ring-2 ring-blue-500/20 shadow-md': expandedOrders.has(order.id) }"
                >
                    <!-- Compact Header (Always Visible) -->
                    <div 
                        @click="toggleOrderExpand(order.id)"
                        class="p-3 flex items-center justify-between cursor-pointer active:bg-gray-50 bg-white"
                    >
                        <div class="flex items-center gap-2.5">
                            <input
                                type="checkbox"
                                :value="order.id"
                                :checked="selectedOrders.includes(order.id)"
                                @change.stop="toggleOrderSelection(order.id)"
                                class="rounded text-blue-500 w-5 h-5 border-gray-300 focus:ring-offset-0"
                            />
                            <div class="flex flex-col">
                                <span class="bg-blue-600 text-white px-2 py-0.5 rounded-lg text-[10px] font-black tracking-tight w-fit">
                                    {{ order.order_number }}
                                </span>
                                <span class="text-[11px] font-bold text-gray-900 mt-0.5">
                                    {{ order.customer.name }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-[12px] font-black text-gray-900">৳{{ order.total }}</span>
                                <div class="flex items-center gap-1.5 flex-wrap justify-end">
                                    <!-- Payment Status -->
                                    <span 
                                        class="text-[8px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded shadow-sm border"
                                        :class="order.payment_status === 'paid' 
                                            ? 'bg-emerald-50 text-emerald-600 border-emerald-200' 
                                            : 'bg-rose-50 text-rose-600 border-rose-200'"
                                    >
                                        {{ order.payment_status }}
                                    </span>
                                    <!-- Delivery Status -->
                                    <span 
                                        class="text-[8px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded shadow-sm border bg-blue-50 text-blue-600 border-blue-200"
                                    >
                                        {{ order.status.replace('_', ' ') }}
                                    </span>
                                </div>
                            </div>
                            <ChevronDown 
                                class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                :class="{ 'rotate-180': expandedOrders.has(order.id) }"
                            />
                        </div>
                    </div>

                    <!-- Collapsible Section -->
                    <div 
                        v-show="expandedOrders.has(order.id)"
                        class="border-t border-gray-50 bg-white"
                    >
                        <!-- Date & Status Bar -->
                        <div class="px-4 py-2 bg-gray-50/50 flex items-center justify-between border-b border-gray-50">
                            <span class="text-[10px] text-gray-500 font-bold">
                                {{ formatOrderDate(order.date) }} {{ new Date(order.date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                            </span>
                            <div class="w-28 scale-90 origin-right">
                                <StatusDropdown
                                    v-model="order.status"
                                    :order-id="order.id"
                                    :is-loading="updatingOrders[order.id]"
                                    @status-change="updateOrderStatus"
                                />
                            </div>
                        </div>

                        <!-- Customer Details -->
                        <div class="px-4 py-3 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-black text-xs shrink-0">
                                {{ order.customer.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 text-gray-700 text-[12px] font-bold">
                                    <Phone class="w-3.5 h-3.5" />
                                    <span>{{ order.customer.phone }}</span>
                                </div>
                                <div class="flex items-start gap-1.5 mt-1 text-gray-500 text-[11px] font-medium leading-normal">
                                    <MapPin class="w-3.5 h-3.5 shrink-0 mt-0.5" />
                                    <span class="line-clamp-2 uppercase">{{ order.customer.address }}</span>
                                </div>
                            </div>
                            <!-- Courier Mini Card -->
                            <div v-if="order.is_courier" class="bg-blue-50/80 px-2 py-1.5 rounded-xl flex flex-col items-center gap-1 border border-blue-100 min-w-[65px]">
                                <Truck class="w-3.5 h-3.5 text-blue-600" />
                                <span class="text-[8px] font-black text-blue-700 uppercase tracking-tighter line-clamp-1">{{ order.courier_name }}</span>
                                <a 
                                    v-if="order.tracking_number"
                                    :href="order.courier_name === 'pathao' ? `https://merchant.pathao.com/tracking?consignment_id=${order.tracking_number}` : `https://steadfast.com.bd/t/${order.tracking_number}`"
                                    target="_blank"
                                    class="text-[8px] font-black text-blue-600 border-t border-blue-200 pt-0.5 mt-0.5 tracking-tighter"
                                >
                                    TRACK
                                </a>
                            </div>
                        </div>

                        <!-- Product Items -->
                        <div class="px-4 py-3 space-y-2 bg-white">
                            <div 
                                v-for="item in order.items" 
                                :key="item.id"
                                class="flex items-center gap-3 bg-gray-50/50 p-2 rounded-xl border border-gray-100"
                            >
                                <div class="relative shrink-0">
                                    <img :src="item.product.image || ''" class="w-10 h-10 rounded-lg object-cover border border-white shadow-sm" />
                                    <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-gray-900 text-[9px] font-bold text-white ring-1 ring-white">
                                        {{ item.quantity }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[11px] font-bold text-gray-800 line-clamp-1 uppercase">{{ item.product.name }}</h4>
                                    <div class="text-[10px] text-blue-600 font-black mt-0.5">
                                        ৳{{ item.price }}
                                        <span v-if="item.variation" class="text-gray-400 font-normal ml-1 border-l border-gray-300 pl-1 uppercase">
                                            <span v-for="(v, k, i) in item.variation.attributes" :key="k">
                                                {{ v }}{{ i < Object.keys(item.variation.attributes).length - 1 ? ',' : '' }}
                                            </span>
                                        </span>
                                    </div>
                                    <!-- Status badges (Mobile Mini) -->
                                    <div v-if="order.status === 'partially_delivered' || order.status === 'returned' || item.returned_quantity > 0" class="flex gap-1 mt-1">
                                        <span v-if="item.quantity - item.returned_quantity > 0" class="px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[8px] font-black">
                                            ✓ {{ item.quantity - item.returned_quantity }} DEL
                                        </span>
                                        <span v-if="item.returned_quantity > 0" class="px-1.5 py-0.5 rounded bg-rose-600 text-white text-[8px] font-black">
                                            ↺ {{ item.returned_quantity }} RET
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="px-4 py-3 bg-gray-50/80 flex items-center justify-between border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">STATUS:</span>
                                <div class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-white border border-gray-200 text-blue-700 shadow-sm">
                                    {{ order.payment_status }}
                                </div>
                            </div>

                            <div class="relative">
                                <button 
                                    @click.stop="toggleActionMenu(order.id)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 shadow-sm text-[11px] font-black text-gray-700 active:scale-95 transition-all"
                                >
                                    OPTIONS
                                    <MoreVertical class="w-3.5 h-3.5" />
                                </button>
                                
                                <div v-if="activeActionMenu === order.id" class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 py-1">
                                    <button @click="checkFraud(order?.customer?.phone); toggleActionMenu(order.id)" class="w-full text-left px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-blue-50 flex items-center gap-2">
                                        <User class="w-4 h-4 text-orange-500" /> Fraud Check
                                    </button>
                                    <button @click="sendToPathao(order); toggleActionMenu(order.id)" class="w-full text-left px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-blue-50 flex items-center gap-2 border-t border-gray-50">
                                        <Truck class="w-4 h-4 text-emerald-500" /> Pathao
                                    </button>
                                    <button @click="sendToSteadfastSingle(order); toggleActionMenu(order.id)" class="w-full text-left px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-blue-50 flex items-center gap-2 border-t border-gray-50">
                                        <Truck class="w-4 h-4 text-blue-500" /> Steadfast
                                    </button>
                                    <Link :href="route('admin.orders.edit', order.id)" class="w-full text-left px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-blue-50 flex items-center gap-2 border-t border-gray-50">
                                        <SquarePen class="w-4 h-4 text-amber-500" /> Edit Order
                                    </Link>
                                    <button @click="openDeleteModal(order.id); toggleActionMenu(order.id)" class="w-full text-left px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 flex items-center gap-2 border-t border-gray-50">
                                        <Trash2Icon class="w-4 h-4" /> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="visibleOrders.length === 0" class="md:hidden py-10 text-center text-gray-500 bg-white rounded-2xl border border-dashed border-gray-300">
                No orders found matching your filters.
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ from }}</span> to
                    <span class="font-medium">{{ to }}</span> of
                    <span class="font-medium">{{ total }}</span> orders
                </div>
                <div class="flex space-x-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        «
                    </button>
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        ‹
                    </button>
                    <template v-if="lastPage <= 7">
                        <button
                            v-for="page in lastPage"
                            :key="page"
                            @click="goToPage(page)"
                            :disabled="loading"
                            class="pagination-button"
                            :class="{ active: currentPage === page }"
                        >
                            {{ page }}
                        </button>
                    </template>
                    <template v-else>
                        <button
                            v-if="currentPage > 3"
                            @click="goToPage(1)"
                            class="pagination-button"
                        >
                            1
                        </button>
                        <span v-if="currentPage > 4" class="pagination-ellipsis"
                            >...</span
                        >
                        <template v-for="page in lastPage" :key="page">
                            <button
                                v-if="
                                    page >= currentPage - 1 &&
                                    page <= currentPage + 1
                                "
                                @click="goToPage(page)"
                                class="pagination-button"
                                :class="{ active: currentPage === page }"
                            >
                                {{ page }}
                            </button>
                        </template>
                        <span
                            v-if="currentPage < lastPage - 3"
                            class="pagination-ellipsis"
                            >...</span
                        >
                        <button
                            v-if="currentPage < lastPage - 2"
                            @click="goToPage(lastPage)"
                            class="pagination-button"
                        >
                            {{ lastPage }}
                        </button>
                    </template>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        ›
                    </button>
                    <button
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        »
                    </button>
                </div>

                <!-- <pre>{{ selectedOrderForPartial }}</pre> -->
            </div>

            <!-- Delete Confirmation Modal -->
            <DeleteModal
                :item-id="orderToDelete"
                item-name="order"
                route-name="admin.orders.destroy"
                v-model:visible="showDeleteModal"
                @deleted="handleDeleteSuccess"
            />

            <!-- Courier Selection Modal -->
            <CourierSelectionModal
                :show="showCourierModal"
                :cities="cities?.data?.data || []"
                :orderId="selectedOrderId"
                :order="selectedOrder"
                :settings="courierSettings"
                @close="showCourierModal = false"
                @submit="handleCourierSubmit"
            />
        </div>

        <!-- Fraud Checker Modal -->
        <FraudCheckerModal
            :visible="showModal"
            :fraudData="fraudData"
            @close="showModal = false"
        />

        <!-- Admin Notes Modal -->
        <AdminNotesModal
            :visible="showAdminNotesModal"
            :order-id="selectedOrderForNotes?.id"
            :order-number="selectedOrderForNotes?.order_number"
            :initial-notes="selectedOrderForNotes?.admin_notes"
            :customer-note="selectedOrderForNotes?.customer?.note"
            @close="closeAdminNotesModal"
            @saved="handleNotesSaved"
        />

        <!-- Partial Delivery logic Modal -->
        <PartialDeliveryModal
            :visible="showPartialModal"
            :order="selectedOrderForPartial"
            @close="showPartialModal = false"
        />
    </AdminLayout>
</template>

<style scoped>
.pagination-button {
    @apply px-3 py-1 rounded-md text-sm font-medium transition-colors;
    @apply bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300;
    @apply border border-gray-300 dark:border-gray-600;
    @apply hover:bg-blue-100 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-200;
}

.pagination-button:disabled {
    @apply opacity-50 cursor-not-allowed;
}

.pagination-button.active {
    @apply bg-blue-500 text-white border-blue-500;
}

.pagination-ellipsis {
    @apply px-3 py-1 text-sm text-gray-500 dark:text-gray-400;
}
</style>
