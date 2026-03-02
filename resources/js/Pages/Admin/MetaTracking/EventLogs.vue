<script setup>
import { ref, reactive, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";

const props = defineProps({
    logs: Object,   // paginated
    filters: Object,
});

const filters = reactive({
    status:       props.filters?.status || "",
    event_name:   props.filters?.event_name || "",
    order_number: props.filters?.order_number || "",
});

function applyFilters() {
    router.get(route("admin.meta-tracking.logs"), filters, { preserveState: true, replace: true });
}

function resetFilters() {
    filters.status       = "";
    filters.event_name   = "";
    filters.order_number = "";
    applyFilters();
}

// ─── Expandable row for payload / response
const expandedRow = ref(null);
function toggleRow(id) {
    expandedRow.value = expandedRow.value === id ? null : id;
}

// ─── Status badge color
function statusClass(status) {
    return {
        sent:    "badge-success",
        failed:  "badge-error",
        pending: "badge-warning",
        skipped: "badge-ghost",
    }[status] ?? "badge-neutral";
}

function formatDate(date) {
    if (!date) return "—";
    return new Date(date).toLocaleString("en-BD", { hour12: true });
}
</script>

<template>
    <Head title="Meta Event Logs" />
    <AdminLayout>
        <div class="py-6 px-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <span class="text-blue-600">📋</span> Meta Event Logs
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">All browser + server-side Meta conversion events</p>
                </div>
                <Link :href="route('admin.meta-tracking.settings')" class="btn btn-sm btn-outline">
                    ⚙️ Settings
                </Link>
            </div>

            <!-- Filters -->
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body py-4">
                    <div class="flex flex-wrap gap-3 items-end">
                        <div class="form-control">
                            <label class="label py-0"><span class="label-text text-xs">Status</span></label>
                            <select v-model="filters.status" class="select select-bordered select-sm">
                                <option value="">All</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
                                <option value="pending">Pending</option>
                                <option value="skipped">Skipped</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label py-0"><span class="label-text text-xs">Event</span></label>
                            <select v-model="filters.event_name" class="select select-bordered select-sm">
                                <option value="">All</option>
                                <option value="Purchase">Purchase</option>
                                <option value="AddToCart">AddToCart</option>
                                <option value="ViewContent">ViewContent</option>
                                <option value="PageView">PageView</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label py-0"><span class="label-text text-xs">Order #</span></label>
                            <input
                                v-model="filters.order_number"
                                type="text"
                                placeholder="ORD-..."
                                class="input input-bordered input-sm"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button @click="applyFilters" class="btn btn-sm btn-primary">Filter</button>
                            <button @click="resetFilters" class="btn btn-sm btn-ghost border border-base-300">Reset</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-base-200">
                            <tr>
                                <th>Order #</th>
                                <th>Event</th>
                                <th>Trigger</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Fired By</th>
                                <th>Sent At</th>
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="logs.data && logs.data.length">
                                <template v-for="log in logs.data" :key="log.id">
                                    <tr class="hover">
                                        <td>
                                            <Link
                                                v-if="log.order"
                                                :href="route('admin.orders.show', log.order.id)"
                                                class="link link-hover text-primary font-mono text-xs"
                                            >
                                                {{ log.order.order_number }}
                                            </Link>
                                            <span v-else class="text-gray-400 text-xs">—</span>
                                        </td>
                                        <td><span class="font-medium text-xs">{{ log.event_name }}</span></td>
                                        <td>
                                            <span class="badge badge-outline badge-xs">{{ log.trigger }}</span>
                                        </td>
                                        <td>
                                            <span :class="['badge badge-sm', statusClass(log.status)]">
                                                {{ log.status }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ log.attempts }}</td>
                                        <td class="text-xs text-gray-500">{{ log.fired_by }}</td>
                                        <td class="text-xs text-gray-500">{{ formatDate(log.sent_at) }}</td>
                                        <td class="text-center">
                                            <button @click="toggleRow(log.id)" class="btn btn-ghost btn-xs">
                                                {{ expandedRow === log.id ? '▲ Hide' : '▼ Show' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Expanded details row -->
                                    <tr v-if="expandedRow === log.id">
                                        <td colspan="8" class="bg-base-50 p-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <p class="font-medium text-xs text-gray-500 mb-1">Event ID</p>
                                                    <p class="font-mono text-xs break-all bg-base-200 p-2 rounded">{{ log.event_id }}</p>
                                                </div>
                                                <div v-if="log.error_message">
                                                    <p class="font-medium text-xs text-red-500 mb-1">Error</p>
                                                    <p class="text-xs text-red-600 bg-red-50 p-2 rounded">{{ log.error_message }}</p>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-xs text-gray-500 mb-1">Payload Sent</p>
                                                    <pre class="text-xs bg-base-200 p-2 rounded overflow-x-auto max-h-48">{{ JSON.stringify(log.payload, null, 2) }}</pre>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-xs text-gray-500 mb-1">Meta Response</p>
                                                    <pre class="text-xs bg-base-200 p-2 rounded overflow-x-auto max-h-48">{{ JSON.stringify(log.response, null, 2) }}</pre>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td colspan="8" class="text-center py-12 text-gray-400">
                                    No event logs found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="flex justify-center py-4 border-t border-base-200">
                    <div class="join">
                        <Link
                            v-for="page in logs.last_page"
                            :key="page"
                            :href="logs.links[page]?.url || '#'"
                            class="join-item btn btn-sm"
                            :class="page === logs.current_page ? 'btn-active btn-primary' : 'btn-ghost'"
                        >
                            {{ page }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
