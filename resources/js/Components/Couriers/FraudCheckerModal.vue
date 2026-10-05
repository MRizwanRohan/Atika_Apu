<script setup>
import { computed } from 'vue';
const props = defineProps({ visible: Boolean, fraudData: Object });
defineEmits(['close']);
const data = computed(() => props.fraudData?.steadfast || {});
const ratio = computed(() => data.value.delivery_ratio ?? props.fraudData?.aggregated?.success_rate ?? null);
const cancellation = computed(() => data.value.cancellation_ratio ?? (data.value.total > 0 ? data.value.cancel / data.value.total * 100 : null));
const percent = value => value == null ? 'N/A' : `${Number(value).toLocaleString('en-US', { maximumFractionDigits: 1 })}%`;
const label = value => typeof value === 'string' ? value : value?.reason ?? value?.category ?? value?.name ?? JSON.stringify(value);
</script>
<template>
    <div v-if="visible" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[100] p-4" @click.self="$emit('close')">
        <section role="dialog" aria-modal="true" aria-labelledby="fraud-check-title" class="bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[85vh] overflow-y-auto p-5 space-y-4">
            <div class="flex justify-between items-start gap-3"><div><h2 id="fraud-check-title" class="font-bold text-gray-900">Steadfast delivery record</h2><p class="text-sm text-gray-500">{{ fraudData?.phone || data.phone || 'N/A' }}</p></div><button aria-label="Close" class="text-gray-500 px-2" @click="$emit('close')">✕</button></div>
            <p v-if="data.error" class="bg-red-50 text-red-700 p-3 rounded-lg text-sm">{{ data.error }}</p>
            <template v-else>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-green-50 p-3 rounded-lg"><p class="text-xs text-green-700">Delivered</p><p class="text-xl font-bold text-green-800">{{ percent(ratio) }}</p></div>
                    <div class="bg-red-50 p-3 rounded-lg"><p class="text-xs text-red-700">Cancelled</p><p class="text-xl font-bold text-red-800">{{ percent(cancellation) }}</p></div>
                    <div class="bg-blue-50 p-3 rounded-lg"><p class="text-xs text-blue-700">Order volume</p><p class="text-xl font-bold text-blue-800">{{ data.volume_range ?? data.total ?? 'N/A' }}</p><p v-if="data.volume_band" class="text-xs text-blue-700">{{ data.volume_band }}</p></div>
                </div>
                <div v-if="ratio != null" class="h-2 rounded-full bg-gray-100 overflow-hidden" role="progressbar" aria-label="Delivery ratio" :aria-valuenow="ratio" aria-valuemin="0" aria-valuemax="100"><div class="bg-green-500 h-full" :style="{ width: `${ratio}%` }"></div></div>
                <div class="rounded-lg border border-gray-200 p-3 text-sm space-y-2">
                    <p>Merchant reports: <strong>{{ data.total_reports ?? 'N/A' }}</strong></p>
                    <p v-if="data.scoring_disabled" class="text-gray-500">Steadfast scoring is currently disabled. Score and risk level are unavailable.</p>
                    <template v-else-if="data.schema_version === 2"><p>Score: <strong>{{ data.score ?? 'N/A' }}</strong></p><p>Risk level: <strong>{{ data.level ?? 'N/A' }}</strong></p></template>
                    <p v-if="data.doubtful_reports" class="text-amber-700">Steadfast marked reports as doubtful.</p>
                    <div v-if="data.fraud_categories?.length"><p class="font-medium">Reported categories</p><ul class="list-disc pl-5"><li v-for="(category, index) in data.fraud_categories" :key="index">{{ label(category) }}</li></ul></div>
                    <div v-if="data.reasons?.length"><p class="font-medium">Reasons</p><ul class="list-disc pl-5"><li v-for="(reason, index) in data.reasons" :key="index">{{ label(reason) }}</li></ul></div>
                </div>
                <p class="text-xs text-gray-500">Delivery and cancellation ratios come directly from Steadfast. Exact parcel counts are not supplied by the score API.</p>
            </template>
        </section>
    </div>
</template>
