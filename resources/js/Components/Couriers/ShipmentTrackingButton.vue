<script setup>
import { ref } from 'vue';
import axios from 'axios';
const props = defineProps({ order: Object });
const loading = ref(false);
const status = ref(null);
const error = ref('');
async function track() {
    if (loading.value) return;
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.post(route('admin.orders.courier-status', props.order.id));
        status.value = response.data.delivery_status;
    } catch (failure) {
        error.value = failure.response?.data?.message || 'Could not fetch shipment status. Please try again.';
    } finally {
        loading.value = false;
    }
}
</script>
<template>
    <div class="mt-1 space-y-1">
        <button type="button" @click.stop="track" :disabled="loading" class="w-full flex items-center justify-center gap-1 px-2 py-1.5 bg-blue-500 text-white text-[10px] font-bold rounded hover:bg-blue-600 disabled:opacity-50">{{ loading ? 'Checking…' : 'Track shipment' }}</button>
        <p v-if="status" aria-live="polite" class="text-[11px] font-semibold text-blue-700 dark:text-blue-300 break-words">Status: {{ status.replaceAll('_', ' ') }}</p>
        <p v-if="error" role="alert" class="text-[10px] text-red-600 break-words">{{ error }}</p>
    </div>
</template>
