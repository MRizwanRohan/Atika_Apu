<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    logs: Object,
});

const filters = ref({
    courier: new URLSearchParams(window.location.search).get('courier') || '',
    status: new URLSearchParams(window.location.search).get('status') || '',
});

watch(filters, debounce(() => {
    router.get(route('admin.courier.webhook-logs'), {
        courier: filters.value.courier,
        status: filters.value.status,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });

const getStatusClass = (status) => {
    switch (status) {
        case 'processed': return 'bg-green-100 text-green-800';
        case 'failed': return 'bg-red-100 text-red-800';
        default: return 'bg-blue-100 text-blue-800';
    }
};

const formatPayload = (payload) => {
    try {
        return JSON.stringify(payload, null, 2);
    } catch (e) {
        return payload;
    }
};

const activeLog = ref(null);
</script>

<template>
    <Head title="Webhook Logs" />
    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">Courier Webhook Logs</h2>
                            <Link :href="route('admin.courier.settings.index')" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                Back to Settings
                            </Link>
                        </div>

                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-gray-50 p-4 rounded-lg">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Courier</label>
                                <select v-model="filters.courier" class="w-full rounded-md border-gray-300">
                                    <option value="">All Couriers</option>
                                    <option value="pathao">Pathao</option>
                                    <option value="steadfast">Steadfast</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="filters.status" class="w-full rounded-md border-gray-300">
                                    <option value="">All Statuses</option>
                                    <option value="received">Received</option>
                                    <option value="processed">Processed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Courier</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event/Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(log.created_at).toLocaleString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full capitalize" 
                                                :class="log.courier === 'pathao' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'">
                                                {{ log.courier }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            #{{ log.order_id || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ log.event_type }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(log.status)">
                                                {{ log.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button @click="activeLog = log" class="text-indigo-600 hover:text-indigo-900">View Payload</button>
                                        </td>
                                    </tr>
                                    <tr v-if="logs.data.length === 0">
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">
                                            No webhook logs found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            <Pagination :links="logs.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payload Modal -->
        <div v-if="activeLog" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="activeLog = null"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Webhook Payload - #{{ activeLog.id }}
                                </h3>
                                <div class="mt-4">
                                    <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-xs max-h-96"><code>{{ formatPayload(activeLog.payload) }}</code></pre>
                                </div>
                                <div v-if="activeLog.error_message" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm italic">
                                    <strong>Error:</strong> {{ activeLog.error_message }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="activeLog = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
