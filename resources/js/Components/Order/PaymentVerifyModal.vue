<template>
    <div v-if="visible" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all p-6">
                
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Verify Payment
                </h3>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">TrxID</span>
                        <span class="text-sm font-black text-indigo-600 truncate">{{ order?.transaction_id || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">Order Total</span>
                        <span class="text-sm font-black text-gray-900">৳{{ order?.total }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-indigo-50 p-2 rounded border border-indigo-100">
                        <span class="text-xs font-bold text-indigo-800 uppercase">Suggested 50% Advance</span>
                        <span class="text-sm font-black text-indigo-800">৳{{ ((order?.total || 0) * 0.5).toFixed(2) }}</span>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Enter Paid Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold">৳</span>
                        <input 
                            type="number" 
                            v-model.number="paidAmount"
                            class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-black text-lg" 
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        @click="handleAction('reject')"
                        :disabled="processing"
                        class="flex-1 px-4 py-2.5 bg-rose-50 text-rose-600 font-bold rounded-lg border border-rose-200 hover:bg-rose-100 transition-colors"
                    >
                        Reject
                    </button>
                    <button 
                        @click="handleAction('approve')"
                        :disabled="processing"
                        class="flex-[2] px-4 py-2.5 bg-indigo-600 text-white font-bold rounded-lg border border-indigo-700 hover:bg-indigo-700 transition-colors shadow-sm"
                    >
                        {{ processing ? 'Processing...' : 'Approve Payment' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from '@steveyuowo/vue-hot-toast';

const props = defineProps({
    visible: Boolean,
    order: Object,
});

const emit = defineEmits(['close']);
const paidAmount = ref(0);
const processing = ref(false);

// Auto-fill suggested 50% amount
watch(() => props.visible, (val) => {
    if (val && props.order) {
        // If order already has paid amount, use that, else use 50% of total
        paidAmount.value = props.order.paid_amount > 0 
            ? props.order.paid_amount 
            : Math.round(props.order.total * 0.5);
    }
});

const handleAction = async (action) => {
    if (!props.order?.id) return;
    
    // Safety check for empty amount on approve
    if (action === 'approve' && (paidAmount.value === null || paidAmount.value === '')) {
        paidAmount.value = 0;
    }
    
    processing.value = true;
    try {
        await router.put(
            route('admin.orders.verify-advance-payment', { orderId: props.order.id }),
            { 
                action, 
                paid_amount: action === 'approve' ? paidAmount.value : 0 
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success(action === 'approve' ? 'Payment approved and added!' : 'Payment rejected!');
                    emit('close');
                },
                onError: () => toast.error('Failed to process payment'),
                onFinish: () => processing.value = false
            }
        );
    } catch (e) {
        toast.error('An error occurred');
        processing.value = false;
    }
};
</script>
