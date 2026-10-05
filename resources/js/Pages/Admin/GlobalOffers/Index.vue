<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ globalOfferSettings: Object });
const form = useForm({
    enabled: props.globalOfferSettings.enabled,
    tiers: props.globalOfferSettings.tiers.length ? props.globalOfferSettings.tiers.map(tier => ({ ...tier })) : [
        { id: null, minimum_subtotal: 1000, reward_type: 'percentage', reward_value: 10 },
    ],
});
const money = value => Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 });
const reward = tier => tier.reward_type === 'free_delivery' ? 'Free delivery' : tier.reward_type === 'percentage' ? `${Number(tier.reward_value)}% discount` : `৳${money(tier.reward_value)} discount`;
const sortedTiers = computed(() => [...form.tiers].sort((a, b) => Number(a.minimum_subtotal) - Number(b.minimum_subtotal)));
function addTier() {
    const highest = Math.max(0, ...form.tiers.map(tier => Number(tier.minimum_subtotal) || 0));
    form.tiers.push({ id: null, minimum_subtotal: highest + 1000, reward_type: 'percentage', reward_value: 20 });
}
function save() {
    form.transform(data => ({ ...data, tiers: data.tiers.map(tier => ({ ...tier, reward_value: tier.reward_type === 'free_delivery' ? null : tier.reward_value })) }))
        .post(route('admin.global-offers.store'), {
            preserveScroll: true,
            onSuccess: page => {
                const saved = page.props.globalOfferSettings;
                form.enabled = saved.enabled;
                form.tiers = saved.tiers.map(tier => ({ ...tier }));
                form.defaults({ enabled: saved.enabled, tiers: saved.tiers.map(tier => ({ ...tier })) });
            },
        });
}
</script>

<template>
    <Head title="Global Offer" />
    <AdminLayout>
        <div class="p-4 sm:p-8 max-w-5xl mx-auto space-y-6">
            <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Global Offer</h1><p class="text-sm text-gray-500 mt-1">Set your shopping discount tiers here. Save all changes together.</p></div>
            <form @submit.prevent="save" class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                    <div><h2 class="font-semibold text-gray-900 dark:text-white">Enable global offer</h2><p class="text-xs text-gray-500 mt-1">Shows the progress bar in the cart and checkout when enabled and saved.</p></div>
                    <button type="button" role="switch" :aria-checked="form.enabled" aria-label="Enable global offer" @click="form.enabled = !form.enabled" class="relative shrink-0 w-14 h-8 rounded-full transition-colors" :class="form.enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"><span class="absolute top-1 w-6 h-6 rounded-full bg-white shadow transition-transform" :class="form.enabled ? 'left-1 translate-x-6' : 'left-1'"></span></button>
                </div>
                <div class="p-5 sm:p-6 space-y-5">
                    <p v-if="!form.enabled" class="rounded-lg bg-amber-50 text-amber-800 p-3 text-sm">Offer is OFF. Turn it ON and save to show customers the progress bar.</p>
                    <div class="space-y-3">
                        <div v-for="(tier, index) in form.tiers" :key="tier.id ?? `new-${index}`" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 grid grid-cols-1 sm:grid-cols-[1fr_1fr_1fr_auto] gap-3 items-end">
                            <label class="text-sm text-gray-600 dark:text-gray-300">Order amount (৳)<input v-model="tier.minimum_subtotal" required type="number" min="0.01" step="0.01" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900" /></label>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Discount type<select v-model="tier.reward_type" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed amount (৳)</option><option value="free_delivery">Free delivery</option></select></label>
                            <label v-if="tier.reward_type !== 'free_delivery'" class="text-sm text-gray-600 dark:text-gray-300">{{ tier.reward_type === 'percentage' ? 'Discount (%)' : 'Discount (৳)' }}<input v-model="tier.reward_value" required type="number" min="0.01" step="0.01" :max="tier.reward_type === 'percentage' ? 100 : undefined" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900" /></label>
                            <div v-else class="text-sm text-green-700 pb-3">Delivery charge waived</div>
                            <button type="button" :disabled="form.tiers.length === 1" @click="form.tiers.splice(index, 1)" class="text-sm text-red-600 disabled:text-gray-300 py-3">Remove</button>
                        </div>
                    </div>
                    <button type="button" @click="addTier" :disabled="form.tiers.length >= 30" class="rounded-lg border border-blue-200 text-blue-600 px-4 py-2 text-sm font-medium disabled:opacity-50">+ Add discount tier</button>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-4 text-sm space-y-2"><p class="font-medium text-gray-900 dark:text-white">Your tiers</p><p v-for="(tier, index) in sortedTiers" :key="index" class="text-gray-600 dark:text-gray-300">৳{{ money(tier.minimum_subtotal) }} or more → {{ reward(tier) }}</p><p class="text-xs text-gray-500 pt-1">Customers automatically get the best eligible discount. Discount percentages do not add together. Free delivery can combine with the discount.</p></div>
                    <ul v-if="Object.keys(form.errors).length" class="text-sm text-red-600 space-y-1"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                    <div class="flex items-center gap-3"><button :disabled="form.processing" class="bg-blue-600 text-white rounded-lg px-5 py-2.5 font-medium disabled:opacity-50">{{ form.processing ? 'Saving…' : 'Save settings' }}</button><p v-if="form.recentlySuccessful" class="text-sm text-green-700">Settings saved.</p></div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
