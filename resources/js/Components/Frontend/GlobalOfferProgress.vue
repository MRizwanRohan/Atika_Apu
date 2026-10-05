<script setup>
import { computed } from 'vue';
const props = defineProps({ pricing: Object, loading: Boolean });
const money = value => Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 0 });
const reward = offer => offer.reward_type === 'free_delivery' ? 'free delivery' : offer.reward_type === 'percentage' ? `${Number(offer.reward_value)}% discount` : `৳${money(offer.reward_value)} discount`;
const next = computed(() => props.pricing?.next_milestone);
const applied = computed(() => props.pricing?.applied_offers || []);
</script>
<template>
    <section v-if="pricing?.qualifying_subtotal > 0 && pricing?.milestones?.length" class="rounded-xl border border-blue-100 dark:border-blue-900 bg-blue-50/70 dark:bg-blue-950/30 p-4 space-y-3" :aria-busy="loading">
        <div class="text-sm font-semibold text-gray-900 dark:text-white" aria-live="polite">
            <p v-if="next">Spend <span class="text-blue-600 dark:text-blue-400">৳{{ money(next.remaining) }}</span> more to unlock {{ reward(next) }}!</p>
            <p v-else-if="applied.length" class="text-green-700 dark:text-green-400">Your shopping rewards are unlocked!</p>
            <p v-else>Your best available saving is applied.</p>
        </div>
        <div v-if="next" role="progressbar" :aria-label="`Progress toward ${reward(next)}`" :aria-valuenow="next.percent" aria-valuemin="0" aria-valuemax="100" :aria-valuetext="`Spend ${money(next.remaining)} taka more`" class="h-2.5 rounded-full bg-blue-100 dark:bg-gray-700 overflow-hidden">
            <div class="h-full rounded-full bg-blue-600 transition-[width] duration-300 motion-reduce:transition-none" :style="{ width: `${next.percent}%` }"></div>
        </div>
        <div v-else-if="applied.length" class="h-2.5 rounded-full bg-green-500" role="progressbar" aria-label="Rewards unlocked" :aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
        <p v-for="offer in applied" :key="`applied-${offer.id}`" class="text-xs text-green-700 dark:text-green-400">✓ {{ offer.name }}: {{ offer.reward_type === 'free_delivery' ? 'Free delivery applied' : `৳${money(offer.saving)} saved` }}</p>
        <p v-if="pricing.discount_source === 'global_offer' && pricing.coupon_candidate_discount > 0" class="text-xs text-gray-500">Your automatic discount saves more than your coupon. The better saving is applied.</p>
        <p v-if="loading" class="text-xs text-gray-500">Updating rewards…</p>
    </section>
</template>
