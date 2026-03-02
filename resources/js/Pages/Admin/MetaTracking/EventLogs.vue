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

// ─── Trigger badge color
function triggerClass(trigger) {
    return {
        'manual':           'bg-purple-100 text-purple-700 border-purple-200',
        'on_order_created': 'bg-blue-100 text-blue-700 border-blue-200',
        'on_confirmed':     'bg-indigo-100 text-indigo-700 border-indigo-200',
        'on_delivered':     'bg-teal-100 text-teal-700 border-teal-200',
        'system':           'bg-gray-100 text-gray-700 border-gray-200',
    }[trigger] ?? "bg-slate-100 text-slate-700 border-slate-200";
}

// ─── Status badge color
function statusClass(status) {
    return {
        sent:    "bg-emerald-100 text-emerald-700 border-emerald-200",
        failed:  "bg-rose-100 text-rose-700 border-rose-200",
        pending: "bg-amber-100 text-amber-700 border-amber-200",
        skipped: "bg-slate-100 text-slate-500 border-slate-200",
    }[status] ?? "bg-slate-100 text-slate-700";
}

function formatDate(date) {
    if (!date) return "—";
    return new Date(date).toLocaleString("en-BD", { hour12: true });
}
</script>

<template>
    <Head title="Meta Event Logs" />
    <AdminLayout>
        <div class="py-10 px-6 bg-slate-50 min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-4 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl shadow-lg shadow-blue-200">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                            Meta Event Logs
                        </h1>
                        <p class="text-slate-500 font-medium">Monitoring real-time conversion signals across the funnel</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.meta-tracking.settings')" class="btn btn-md bg-white border-transparent hover:bg-slate-100/50 shadow-sm text-slate-700 capitalize font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Settings
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="card bg-white border border-slate-200 shadow-sm mb-6 rounded-2xl overflow-hidden overflow-visible">
                <div class="card-body py-6 px-8">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="form-control flex-1 min-w-[150px]">
                            <label class="label mb-1.5 px-0"><span class="label-text-alt text-xs font-bold uppercase tracking-wider text-slate-500">Status</span></label>
                            <select v-model="filters.status" class="select select-bordered select-md bg-slate-50 border-slate-200 text-slate-700 font-semibold focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Statuses</option>
                                <option value="sent">Sent Successfully</option>
                                <option value="failed">Delivery Failed</option>
                                <option value="pending">Awaiting Send</option>
                                <option value="skipped">Manually Skipped</option>
                            </select>
                        </div>
                        <div class="form-control flex-1 min-w-[150px]">
                            <label class="label mb-1.5 px-0"><span class="label-text-alt text-xs font-bold uppercase tracking-wider text-slate-500">Event Name</span></label>
                            <select v-model="filters.event_name" class="select select-bordered select-md bg-slate-50 border-slate-200 text-slate-700 font-semibold focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Total Coverage</option>
                                <option value="Purchase">Purchase (Final)</option>
                                <option value="AddToCart">AddToCart (Intent)</option>
                                <option value="ViewContent">ViewContent (Interest)</option>
                                <option value="PageView">PageView (Traffic)</option>
                            </select>
                        </div>
                        <div class="form-control flex-1 min-w-[150px]">
                            <label class="label mb-1.5 px-0"><span class="label-text-alt text-xs font-bold uppercase tracking-wider text-slate-500">Order Reference</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">#</span>
                                <input
                                    v-model="filters.order_number"
                                    type="text"
                                    placeholder="ORD-..."
                                    class="input input-bordered input-md w-full bg-slate-50 border-slate-200 pl-8 text-slate-700 font-mono focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                        <div class="flex gap-2 min-w-[180px]">
                            <button @click="applyFilters" class="btn btn-md btn-primary grow text-white shadow-md shadow-blue-500/20 px-8">Filter Results</button>
                            <button @click="resetFilters" class="btn btn-md bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-600 px-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card bg-white border border-slate-200 shadow-md rounded-2xl overflow-hidden overflow-visible">
                <div class="overflow-x-auto min-h-[400px]">
                    <table class="table table-md w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200">
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500 py-5">Order Reference</th>
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500">Event Action</th>
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500">Trigger Source</th>
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500">Delivery Status</th>
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500 text-center">Retries</th>
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500">Sent Timestamp</th>
                                <th class="text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="logs.data && logs.data.length">
                                <template v-for="log in logs.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td>
                                            <div class="flex flex-col">
                                                <Link
                                                    v-if="log.order"
                                                    :href="route('admin.orders.show', log.order.id)"
                                                    class="text-blue-600 hover:text-blue-800 font-bold font-mono text-sm"
                                                >
                                                    {{ log.order.order_number }}
                                                </Link>
                                                <span v-else class="text-slate-400 text-xs italic">System Level</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full" :class="log.event_name === 'Purchase' ? 'bg-indigo-500' : 'bg-slate-300'"></div>
                                                <span class="font-bold text-slate-700 text-sm tracking-tight">{{ log.event_name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span 
                                                class="px-2.5 py-1 rounded-full text-[10px] font-bold border" 
                                                :class="triggerClass(log.trigger)"
                                            >
                                                {{ log.trigger?.replace(/_/g, ' ') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span 
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wide" 
                                                    :class="statusClass(log.status)"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="log.status === 'sent' ? 'bg-emerald-500' : log.status === 'failed' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                                                    {{ log.status }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span :class="['font-bold text-sm px-2 py-0.5 rounded', log.attempts > 1 ? 'bg-amber-50 text-amber-700' : 'text-slate-500']">
                                                {{ log.attempts }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="text-slate-700 font-bold text-xs">{{ formatDate(log.sent_at).split(',')[1] }}</span>
                                                <span class="text-slate-400 text-[10px]">{{ formatDate(log.sent_at).split(',')[0] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <button 
                                                @click="toggleRow(log.id)" 
                                                class="btn btn-sm btn-ghost hover:bg-slate-100 rounded-lg group"
                                            >
                                                <span class="mr-1 text-slate-500 group-hover:text-blue-600 transition-colors font-bold text-xs">Details</span>
                                                <svg 
                                                    class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-transform duration-300" 
                                                    :class="{'rotate-180': expandedRow === log.id}"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Expanded details row -->
                                    <tr v-if="expandedRow === log.id" class="animate-in fade-in slide-in-from-top-4 duration-300">
                                        <td colspan="7" class="bg-slate-50/80 p-0 border-b border-slate-200 shadow-inner">
                                            <div class="p-8">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-7xl mx-auto">
                                                    <!-- Meta Info -->
                                                    <div class="space-y-6">
                                                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                                                            <div class="flex items-center justify-between mb-4">
                                                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Contextual Data</h4>
                                                                <span class="text-[10px] font-mono text-slate-400">{{ log.event_id }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-4 mb-4">
                                                                <div class="flex-1 p-3 bg-slate-50 rounded-xl">
                                                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Fired By</p>
                                                                    <p class="text-sm font-bold text-slate-700">{{ log.fired_by }}</p>
                                                                </div>
                                                                <div class="flex-1 p-3 bg-slate-50 rounded-xl">
                                                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Browser/Side</p>
                                                                    <p class="text-sm font-bold text-slate-700 capitalize">{{ log.fired_by === 'system' ? 'Server-Side' : 'Client-Side' }}</p>
                                                                </div>
                                                            </div>
                                                            <div v-if="log.error_message" class="p-4 bg-rose-50 border border-rose-100 rounded-xl">
                                                                <div class="flex items-start gap-3">
                                                                    <svg class="w-5 h-5 text-rose-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                                    <div>
                                                                        <p class="text-xs font-black text-rose-700 uppercase tracking-wider mb-1">Execution Error</p>
                                                                        <p class="text-sm font-medium text-rose-600 leading-relaxed">{{ log.error_message }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                                                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Meta API Response</h4>
                                                            <pre class="grow text-[11px] bg-slate-900 text-indigo-200 p-5 rounded-xl overflow-x-auto max-h-[300px] font-mono leading-relaxed shadow-inner">{{ JSON.stringify(log.response, null, 2) }}</pre>
                                                        </div>
                                                    </div>

                                                    <!-- Payload -->
                                                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col h-full">
                                                        <div class="flex items-center justify-between mb-4">
                                                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Transmission Payload</h4>
                                                            <button 
                                                                @click="toast.success('Payload copied to clipboard')"
                                                                class="p-2 hover:bg-slate-50 rounded-lg text-slate-400 transition-colors"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                            </button>
                                                        </div>
                                                        <pre class="grow text-[11px] bg-slate-900 text-blue-200 p-5 rounded-xl overflow-x-auto max-h-[500px] font-mono leading-relaxed shadow-inner border border-slate-800">{{ JSON.stringify(log.payload, null, 2) }}</pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td colspan="7" class="text-center py-20">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="p-6 bg-slate-100 rounded-full">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-slate-600">No signals found</p>
                                            <p class="text-sm text-slate-400">Try adjusting your filters or checking the Meta implementation.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="flex items-center justify-between px-8 py-5 border-t border-slate-200 bg-slate-50/50">
                    <p class="text-sm text-slate-500 font-medium">
                        Showing page <span class="text-slate-900 font-bold">{{ logs.current_page }}</span> of <span class="text-slate-900 font-bold">{{ logs.last_page }}</span>
                    </p>
                    <div class="join shadow-sm border border-slate-200 rounded-xl overflow-hidden">
                        <Link
                            v-for="(link, k) in logs.links"
                            :key="k"
                            :href="link.url || '#'"
                            class="join-item btn btn-md bg-white border-none hover:bg-slate-100 text-slate-700 font-bold px-5"
                            :class="{'btn-active bg-blue-600 !text-white hover:bg-blue-700': link.active, 'btn-disabled opacity-50': !link.url}"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
