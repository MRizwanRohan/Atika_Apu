<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Search,
    MessageSquare,
    Phone,
    Calendar,
    ChevronLeft,
    ChevronRight,
    ArrowLeft,
    CheckCircle2,
    XCircle,
    Info
} from "lucide-vue-next";

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters.search || "");

let searchTimeout;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route("admin.customers.sms-logs"), { search: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 500);
});

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <Head title="SMS Logs" />

    <AdminLayout>
        <div class="w-full mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.customers.index')" class="p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl transition-all">
                        <ArrowLeft class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            SMS History Logs
                            <span class="bg-gray-50 dark:bg-gray-900 text-gray-500 text-xs px-2.5 py-1 rounded-full font-semibold border border-gray-100 dark:border-gray-800">
                                {{ logs.total }} Total
                            </span>
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Audit trail for all outgoing SMS communications</p>
                    </div>
                </div>

                <div class="relative min-w-[300px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search phone or message content..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none"
                    />
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Recipient</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Sent At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/40 transition-colors">
                                <!-- Recipient -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
                                            <Phone class="w-4 h-4 text-gray-400" />
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ log.phone }}</div>
                                            <div v-if="log.customer" class="text-[10px] text-blue-500 font-medium">{{ log.customer.name }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Message -->
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-600 dark:text-gray-300 max-w-md line-clamp-2 leading-relaxed" :title="log.message">
                                        {{ log.message }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <div v-if="log.status === 'success'" class="flex items-center gap-1.5 text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-lg w-fit border border-emerald-100 dark:border-emerald-800/50">
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        <span class="text-[10px] font-bold uppercase tracking-wider">Success</span>
                                    </div>
                                    <div v-else class="space-y-1">
                                        <div class="flex items-center gap-1.5 text-rose-600 bg-rose-50 dark:bg-rose-900/20 px-2 py-1 rounded-lg w-fit border border-rose-100 dark:border-rose-800/50">
                                            <XCircle class="w-3.5 h-3.5" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider">Failed</span>
                                        </div>
                                        <div v-if="log.error_message" class="text-[9px] text-rose-400 font-medium flex items-center gap-1">
                                            <Info class="w-2.5 h-2.5" />
                                            {{ log.error_message }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Sent At -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <Calendar class="w-3.5 h-3.5" />
                                        {{ formatDate(log.created_at) }}
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="logs.data.length === 0">
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <MessageSquare class="w-12 h-12 text-gray-200 mx-auto mb-4" />
                                    <p class="text-gray-500 font-medium text-sm">No SMS logs found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-xs text-gray-500 font-medium">
                        Page {{ logs.current_page }} of {{ logs.last_page }}
                    </div>
                    <div class="flex items-center gap-2">
                        <Link v-if="logs.prev_page_url" :href="logs.prev_page_url" class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white transition-all">
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <Link v-if="logs.next_page_url" :href="logs.next_page_url" class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white transition-all">
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
