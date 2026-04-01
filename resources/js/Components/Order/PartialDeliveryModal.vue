<script setup>
import { ref, watch, computed } from "vue";
import { X } from "lucide-vue-next";
import { router } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";

const props = defineProps({
    visible: Boolean,
    order: Object,
});

const emit = defineEmits(["close", "saved"]);

const loading = ref(false);
const items = ref([]);
const collectedAmount = ref(0);
const returnCharge = ref(0);
const finalStatus = ref("partially_delivered");

const statusOptions = [
    { value: "delivered", label: "Fully Delivered" },
    { value: "partially_delivered", label: "Partially Delivered" },
    { value: "returned", label: "Fully Returned" },
];

watch(
    () => props.visible,
    (isVis) => {
        if (isVis && props.order) {
            items.value = (props.order.items || []).map((item) => {
                const maxQty = item.quantity || 1;
                // If item.price represents the line total, divide by maxQty to get actual unit price
                const unitPrice = item.unit_price 
                    ? parseFloat(item.unit_price) 
                    : (parseFloat(item.price || 0) / maxQty);
                    
                return {
                    id: item.id,
                    product_name: item.product?.name || "Unknown",
                    unit_price: unitPrice,
                    image: item.product?.image || item.product?.thumbnail || null,
                    max_qty: maxQty,
                    delivered_qty: maxQty, // Default to fully delivered
                    returned_qty: 0,
                };
            });
            // Initialize financials
            collectedAmount.value = Math.max(0, props.order.total);
            returnCharge.value = 0;
            finalStatus.value = "partially_delivered";
        }
    }
);

// Auto-adjust quantities
const updateQty = (item, type) => {
    if (type === "delivered") {
        if (item.delivered_qty > item.max_qty) item.delivered_qty = item.max_qty;
        if (item.delivered_qty < 0) item.delivered_qty = 0;
        item.returned_qty = item.max_qty - item.delivered_qty;
    } else {
        if (item.returned_qty > item.max_qty) item.returned_qty = item.max_qty;
        if (item.returned_qty < 0) item.returned_qty = 0;
        item.delivered_qty = item.max_qty - item.returned_qty;
    }
    
    // Automatically adjust the collected amount based on returned items
    const returnDeduction = items.value.reduce((sum, currentItem) => {
        return sum + (currentItem.returned_qty * currentItem.unit_price);
    }, 0);
    
    collectedAmount.value = Math.max(0, props.order.total - returnDeduction);
};

const totalReturnedItems = computed(() => {
    return items.value.reduce((sum, item) => sum + parseInt(item.returned_qty || 0), 0);
});

const totalDeliveredItems = computed(() => {
    return items.value.reduce((sum, item) => sum + parseInt(item.delivered_qty || 0), 0);
});

watch([totalReturnedItems, totalDeliveredItems], ([returned, delivered]) => {
    if (returned === 0 && delivered > 0) finalStatus.value = "delivered";
    else if (delivered === 0 && returned > 0) finalStatus.value = "returned";
    else finalStatus.value = "partially_delivered";
});

const submit = async () => {
    loading.value = true;
    try {
        const payload = {
            items: items.value.map(i => ({
                order_item_id: i.id,
                delivered_qty: i.delivered_qty,
                returned_qty: i.returned_qty
            })),
            collected_amount: collectedAmount.value,
            return_charge: returnCharge.value,
            final_status: finalStatus.value
        };

        const { data } = await axios.put(`/orders/${props.order.id}/partial-delivery`, payload);

        if (data.success) {
            toast.success("Partial delivery processed!");
            emit("saved");
            emit("close");
            router.reload();
        } else {
            toast.error(data.message || "Something went wrong.");
        }
    } catch (err) {
        toast.error(err.response?.data?.message || "Failed to process.");
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity"
        @click.self="$emit('close')"
    >
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">
                        Partial Delivery & Return
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Order #{{ order?.order_number }}</p>
                </div>
                <button
                    @click="$emit('close')"
                    class="p-2 min-h-12 min-w-12 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-gray-200"
                >
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- <pre>{{ order }}</pre> -->

            <!-- Content -->
            <div class="p-6 overflow-y-auto flex-1">
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        Select how many quantities of each item were delivered to the customer and how many were returned. The stock for returned items will be automatically restored.
                    </p>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm mb-6">
                    <table class="min-w-full divide-y divide-gray-200 table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Qty</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Delivered Qty</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Returned Qty</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(item, index) in items" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex-shrink-0">
                                            <img v-if="item.image" :src="item.image" :alt="item.product_name" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">No Img</div>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-800">{{ item.product_name }}</span>
                                            <span class="text-xs text-gray-500">Unit Price: ৳{{ item.unit_price }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        {{ item.max_qty }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <input 
                                        type="number" 
                                        v-model="item.delivered_qty" 
                                        @input="updateQty(item, 'delivered')"
                                        min="0" :max="item.max_qty"
                                        class="w-20 text-center rounded-md border-gray-300 border px-2 py-1 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900"
                                    />
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <input 
                                        type="number" 
                                        v-model="item.returned_qty" 
                                        @input="updateQty(item, 'returned')"
                                        min="0" :max="item.max_qty"
                                        class="w-20 text-center rounded-md border-gray-300 border px-2 py-1 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm text-gray-900"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-5 rounded-lg border border-gray-200">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-4 border-b pb-2">Financial Breakdown</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Original Total:</span>
                                <span class="font-medium text-gray-900 font-mono">৳{{ order?.total ?? 0 }}</span>
                            </div>
                            
                            <div class="pt-3 border-t">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Actual Collected Amount (৳)
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">৳</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        v-model="collectedAmount" 
                                        class="block w-full rounded-md border-gray-300 border py-2 pl-7 pr-3 focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono text-gray-900"
                                        placeholder="0.00"
                                        min="0"
                                    >
                                </div>
                                <p class="text-xs text-gray-500 mt-1">E.g., enter only delivery charge if full return.</p>
                            </div>

                            <!-- <div class="pt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Return Charge Deduction (৳) <span class="text-xs font-normal text-gray-500">(Optional)</span>
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">৳</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        v-model="returnCharge" 
                                        class="block w-full rounded-md border-gray-300 border py-2 pl-7 pr-3 focus:border-red-500 focus:ring-red-500 sm:text-sm font-mono text-gray-900"
                                        placeholder="0.00"
                                        min="0"
                                    >
                                </div>
                            </div> -->
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-4 border-b pb-2">Final Status</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order Status</label>
                            <select 
                                v-model="finalStatus" 
                                class="block w-full rounded-md border-gray-300 border py-2 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            
                            <div class="mt-4 p-3 bg-white rounded-md border border-gray-200">
                                <p class="text-xs text-gray-600 mb-2">Summary:</p>
                                <ul class="text-sm space-y-1">
                                    <li class="flex items-center text-green-700">
                                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                                        {{ totalDeliveredItems }} items delivered
                                    </li>
                                    <li class="flex items-center text-red-700">
                                        <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                                        {{ totalReturnedItems }} items returned (stock restored)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-end gap-3 rounded-b-xl">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 min-h-12 min-w-24 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="submit"
                    :disabled="loading"
                    class="px-6 py-2 min-h-12 min-w-32 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center gap-2 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <span v-if="loading" class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>
                    {{ loading ? 'Saving...' : 'Apply Partial Delivery' }}
                </button>
            </div>
        </div>
    </div>
</template>