<script setup>
import { ref, watch, computed } from "vue";
import { X, PackageCheck, PackageX, AlertCircle, ChevronRight } from "lucide-vue-next";
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
                const unitPrice = item.unit_price
                    ? parseFloat(item.unit_price)
                    : parseFloat(item.price || 0) / maxQty;

                return {
                    id: item.id,
                    product_name: item.product?.name || "Unknown",
                    unit_price: unitPrice,
                    image: item.product?.image || item.product?.thumbnail || null,
                    variation: item.variation || null,
                    max_qty: maxQty,
                    delivered_qty: maxQty,
                    returned_qty: 0,
                };
            });
            collectedAmount.value = Math.max(0, props.order.total);
            returnCharge.value = 0;
            finalStatus.value = "partially_delivered";
        }
    }
);

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

    const returnDeduction = items.value.reduce((sum, currentItem) => {
        return sum + currentItem.returned_qty * currentItem.unit_price;
    }, 0);

    collectedAmount.value = Math.max(0, props.order.total - returnDeduction);
};

const totalReturnedItems = computed(() =>
    items.value.reduce((sum, item) => sum + parseInt(item.returned_qty || 0), 0)
);

const totalDeliveredItems = computed(() =>
    items.value.reduce((sum, item) => sum + parseInt(item.delivered_qty || 0), 0)
);

watch([totalReturnedItems, totalDeliveredItems], ([returned, delivered]) => {
    if (returned === 0 && delivered > 0) finalStatus.value = "delivered";
    else if (delivered === 0 && returned > 0) finalStatus.value = "returned";
    else finalStatus.value = "partially_delivered";
});

const variationAttributes = (variation) => {
    if (!variation || !variation.attributes) return [];
    return Object.entries(variation.attributes).map(([key, val]) => ({ key, val }));
};

const deliveryProgress = computed(() => {
    const total = items.value.reduce((s, i) => s + i.max_qty, 0);
    if (!total) return 0;
    return Math.round((totalDeliveredItems.value / total) * 100);
});

const submit = async () => {
    loading.value = true;
    try {
        const payload = {
            items: items.value.map((i) => ({
                order_item_id: i.id,
                delivered_qty: i.delivered_qty,
                returned_qty: i.returned_qty,
            })),
            collected_amount: collectedAmount.value,
            return_charge: returnCharge.value,
            final_status: finalStatus.value,
        };

        const { data } = await axios.put(
            route("admin.orders.partial-delivery", { orderId: props.order.id }),
            payload
        );

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
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="visible"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="$emit('close')"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                >
                    <div
                        v-if="visible"
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden ring-1 ring-black/5"
                    >
                        <!-- Header -->
                        <div class="relative flex items-center justify-between px-6 py-5 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-50">
                                    <PackageCheck class="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 leading-tight">
                                        Partial Delivery & Return
                                    </h3>
                                    <p class="text-xs text-gray-400 mt-0.5 font-mono tracking-wide">
                                        Order #{{ order?.order_number }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="$emit('close')"
                                class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Progress bar -->
                        <div class="h-1 bg-gray-100">
                            <div
                                class="h-full bg-gradient-to-r from-blue-500 to-emerald-500 transition-all duration-500"
                                :style="{ width: deliveryProgress + '%' }"
                            />
                        </div>

                        <!-- Content -->
                        <div class="p-6 overflow-y-auto flex-1 space-y-5">

                            <!-- Info Banner -->
                            <div class="flex items-start gap-3 bg-blue-50/70 border border-blue-100 rounded-xl p-4">
                                <AlertCircle class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                                <p class="text-xs text-blue-700 leading-relaxed">
                                    Adjust delivered and returned quantities per item. Stock for returned items will be automatically restored. The collected amount updates based on returned items.
                                </p>
                            </div>

                            <!-- Items: Desktop Table -->
                            <div class="hidden sm:block rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead>
                                        <tr class="bg-gray-50/80">
                                            <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-1/2">Product</th>
                                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Delivered</th>
                                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-red-500 uppercase tracking-wider">Returned</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-50">
                                        <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50/60 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex-shrink-0 shadow-sm">
                                                        <img v-if="item.image" :src="item.image" :alt="item.product_name" class="w-full h-full object-cover" />
                                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-xs">N/A</div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-800 truncate max-w-[220px]">{{ item.product_name }}</p>
                                                        <div v-if="variationAttributes(item.variation).length" class="flex flex-wrap gap-1 mt-1">
                                                            <span v-for="attr in variationAttributes(item.variation)" :key="attr.key"
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-[10px] font-semibold text-indigo-600 uppercase tracking-wide">
                                                                {{ attr.key }}: {{ attr.val }}
                                                            </span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-400 mt-0.5 font-mono">৳{{ item.unit_price.toFixed(2) }} / unit</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-center align-middle">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-sm font-semibold text-gray-700">{{ item.max_qty }}</span>
                                            </td>
                                            <td class="px-4 py-4 text-center align-middle">
                                                <input type="number" v-model="item.delivered_qty" @input="updateQty(item, 'delivered')"
                                                    min="0" :max="item.max_qty"
                                                    class="w-16 text-center text-sm font-semibold rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-2 py-1.5 focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition-all" />
                                            </td>
                                            <td class="px-4 py-4 text-center align-middle">
                                                <input type="number" v-model="item.returned_qty" @input="updateQty(item, 'returned')"
                                                    min="0" :max="item.max_qty"
                                                    class="w-16 text-center text-sm font-semibold rounded-lg border border-red-200 bg-red-50 text-red-700 px-2 py-1.5 focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100 transition-all" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Items: Mobile Cards -->
                            <div class="sm:hidden space-y-3">
                                <div v-for="item in items" :key="item.id"
                                    class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                                    <!-- Product Info -->
                                    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50/60">
                                        <div class="w-11 h-11 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex-shrink-0">
                                            <img v-if="item.image" :src="item.image" :alt="item.product_name" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-xs">N/A</div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-800 leading-tight line-clamp-2">{{ item.product_name }}</p>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                <span v-for="attr in variationAttributes(item.variation)" :key="attr.key"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-[10px] font-semibold text-indigo-600 uppercase tracking-wide">
                                                    {{ attr.key }}: {{ attr.val }}
                                                </span>
                                                <span class="text-[11px] text-gray-400 font-mono">৳{{ item.unit_price.toFixed(2) }}/unit</span>
                                            </div>
                                        </div>
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-sm font-bold text-gray-600">
                                            {{ item.max_qty }}
                                        </span>
                                    </div>
                                    <!-- Qty Controls -->
                                    <div class="grid grid-cols-2 divide-x divide-gray-100">
                                        <div class="flex flex-col items-center gap-1.5 px-3 py-3">
                                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Delivered</span>
                                            <input type="number" v-model="item.delivered_qty" @input="updateQty(item, 'delivered')"
                                                min="0" :max="item.max_qty"
                                                class="w-full text-center text-base font-bold rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-2 py-2 focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition-all" />
                                        </div>
                                        <div class="flex flex-col items-center gap-1.5 px-3 py-3">
                                            <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Returned</span>
                                            <input type="number" v-model="item.returned_qty" @input="updateQty(item, 'returned')"
                                                min="0" :max="item.max_qty"
                                                class="w-full text-center text-base font-bold rounded-lg border border-red-200 bg-red-50 text-red-700 px-2 py-2 focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100 transition-all" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Financials & Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <!-- Financial Breakdown -->
                                <div class="rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                                        <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">Financial Breakdown</h4>
                                    </div>
                                    <div class="p-5 space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-500">Original Total</span>
                                            <span class="text-sm font-bold text-gray-800 font-mono">৳{{ Number(order?.total ?? 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="border-t border-dashed border-gray-200 pt-4">
                                            <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">
                                                Collected Amount (৳)
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-semibold">৳</span>
                                                <input
                                                    type="number"
                                                    v-model="collectedAmount"
                                                    class="block w-full rounded-lg border border-gray-200 py-2.5 pl-7 pr-3 text-sm font-mono font-semibold text-gray-800 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-50 transition-all"
                                                    placeholder="0.00"
                                                    min="0"
                                                />
                                            </div>
                                            <p class="text-[11px] text-gray-400 mt-1.5">Enter only delivery charge if full return.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Final Status -->
                                <div class="rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                                        <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">Order Status</h4>
                                    </div>
                                    <div class="p-5 space-y-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Final Status</label>
                                            <select
                                                v-model="finalStatus"
                                                class="block w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm text-gray-800 bg-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-50 transition-all cursor-pointer"
                                            >
                                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                                    {{ opt.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Summary Pills -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-100">
                                                <div class="flex items-center gap-2">
                                                    <PackageCheck class="w-3.5 h-3.5 text-emerald-600" />
                                                    <span class="text-xs font-medium text-emerald-700">Delivered</span>
                                                </div>
                                                <span class="text-sm font-bold text-emerald-700 font-mono">{{ totalDeliveredItems }} items</span>
                                            </div>
                                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-red-50 border border-red-100">
                                                <div class="flex items-center gap-2">
                                                    <PackageX class="w-3.5 h-3.5 text-red-500" />
                                                    <span class="text-xs font-medium text-red-600">Returned</span>
                                                </div>
                                                <span class="text-sm font-bold text-red-600 font-mono">{{ totalReturnedItems }} items</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50/60 border-t border-gray-100 flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-400">
                                Delivery progress:
                                <span class="font-semibold text-gray-600">{{ deliveryProgress }}%</span>
                            </p>
                            <div class="flex items-center gap-2.5">
                                <button
                                    @click="$emit('close')"
                                    class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <span
                                        v-if="loading"
                                        class="w-3.5 h-3.5 rounded-full border-2 border-white/30 border-t-white animate-spin"
                                    />
                                    <PackageCheck v-else class="w-4 h-4" />
                                    {{ loading ? "Saving..." : "Apply Delivery" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>