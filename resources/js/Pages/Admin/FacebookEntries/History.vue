<template>
    <Head title="Facebook Entry History" />

    <AdminLayout>
        <div class="mx-auto max-w-6xl space-y-4 pb-10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Entry History</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Search and filter Facebook entries.</p>
                </div>

                <Link
                    :href="route('admin.facebook-entries.index')"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    New Entry
                </Link>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1.6fr)_auto_auto]">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size="16" />
                        <input
                            v-model="form.search"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by name, phone or tracking ID"
                            class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-900 outline-none transition focus:border-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div class="flex rounded-lg border border-gray-200 p-1 dark:border-gray-700">
                        <button
                            v-for="option in areaOptions"
                            :key="option.value"
                            type="button"
                            @click="setArea(option.value)"
                            :class="[
                                'rounded-md px-3 py-2 text-xs font-medium transition',
                                form.area === option.value
                                    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                    : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <select
                        v-model="form.date_filter"
                        @change="applyFilters"
                        class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
                    >
                        <option v-for="option in dateOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div v-if="form.date_filter === 'custom'" class="mt-3 grid gap-3 sm:grid-cols-2">
                    <input
                        v-model="form.start_date"
                        @change="applyFilters"
                        type="date"
                        class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
                    />
                    <input
                        v-model="form.end_date"
                        @change="applyFilters"
                        type="date"
                        class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
                    />
                </div>
            </div>

            <div v-if="history.data.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="entry in history.data"
                    :key="entry.id"
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ entry.customer_name || 'N/A' }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ entry.customer_phone || 'No phone' }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ entry.customer_address || 'No address' }}
                            </p>
                        </div>

                        <span class="shrink-0 text-xs text-gray-400">#{{ entry.id }}</span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Area</span>
                            <span
                                :class="areaBadgeClass(entry.area)"
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                            >
                                {{ areaLabel(entry.area) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Amount</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">৳{{ entry.bill_amount }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Tracking</span>
                            <span class="text-sm text-right text-gray-900 dark:text-white">
                                {{ entry.tracking_id || 'Processing' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                        <span class="text-xs uppercase tracking-wide text-gray-400">{{ entry.status }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(entry.created_at) }}</span>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-gray-200 bg-white px-4 py-16 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">No entries found</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try another search or filter.</p>
            </div>

            <div v-if="history.links?.length" class="flex justify-center">
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <Link
                        v-for="link in history.links"
                        :key="`${link.label}-${link.url}`"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'rounded-lg border px-3 py-2 text-sm transition',
                            link.active
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-white dark:bg-white dark:text-gray-900'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800',
                            !link.url ? 'pointer-events-none opacity-50' : ''
                        ]"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Search } from 'lucide-vue-next';
import debounce from 'lodash/debounce';

const props = defineProps({
    history: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});

const form = reactive({
    search: props.filters.search || '',
    area: props.filters.area || 'all',
    date_filter: props.filters.date_filter || 'all',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const areaOptions = [
    { label: 'All', value: 'all' },
    { label: 'Dhaka', value: 'inside_dhaka' },
    { label: 'Outside Dhaka', value: 'outside_dhaka' },
];

const dateOptions = [
    { label: 'All Time', value: 'all' },
    { label: 'Today', value: 'today' },
    { label: 'This Month', value: 'this_month' },
    { label: 'Custom', value: 'custom' },
];

const buildQuery = () => {
    const query = {};

    if (form.search) {
        query.search = form.search;
    }

    if (form.area && form.area !== 'all') {
        query.area = form.area;
    }

    if (form.date_filter && form.date_filter !== 'all') {
        query.date_filter = form.date_filter;
    }

    if (form.date_filter === 'custom') {
        if (form.start_date) {
            query.start_date = form.start_date;
        }

        if (form.end_date) {
            query.end_date = form.end_date;
        }
    }

    return query;
};

const applyFilters = () => {
    router.get(route('admin.facebook-entries.history'), buildQuery(), {
        preserveState: true,
        replace: true,
    });
};

const handleSearch = debounce(() => {
    applyFilters();
}, 400);

const setArea = (area) => {
    form.area = area;
    applyFilters();
};

const areaLabel = (area) => {
    if (area === 'inside_dhaka') {
        return 'Inside Dhaka';
    }

    if (area === 'outside_dhaka') {
        return 'Outside Dhaka';
    }

    return 'Not set';
};

const areaBadgeClass = (area) => {
    if (area === 'inside_dhaka') {
        return 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
    }

    if (area === 'outside_dhaka') {
        return 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300';
    }

    return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
