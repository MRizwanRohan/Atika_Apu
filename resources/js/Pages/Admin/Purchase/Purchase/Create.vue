<template>
  <Head title="Create Purchase" />
  <AdminLayout>
    <div class="w-full mx-auto py-10 px-4 sm:px-6 lg:px-8">
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="route('admin.purchases.index')" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-500 hover:text-blue-600 transition-colors">
            <MoveLeft size="20" />
          </Link>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">New Purchase <span class="text-gray-400 text-lg font-normal">Order</span></h1>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="xl:col-span-2 space-y-6">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
              <Package size="20" class="mr-2 text-blue-500" /> Items List
            </h3>

            <!-- Item Adder -->
            <div class="flex flex-col sm:flex-row gap-2 mb-6 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
              <div class="flex-1">
                <select v-model="selectedProductId" class="form-input w-full rounded-xl border-none shadow-none text-sm bg-transparent" @change="addItem">
                  <option value="" disabled>Search & Select Product to add...</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }} ({{ product.variations?.length || 0 }} variations)
                  </option>
                </select>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead class="border-b border-gray-100 dark:border-gray-700">
                  <tr>
                    <th class="py-3 px-2 text-xs font-semibold text-gray-400 uppercase">Product</th>
                    <th class="py-3 px-2 text-xs font-semibold text-gray-400 uppercase text-center w-32">Qty</th>
                    <th class="py-3 px-2 text-xs font-semibold text-gray-400 uppercase w-40">Unit Cost</th>
                    <th class="py-3 px-2 text-xs font-semibold text-gray-400 uppercase text-right">Subtotal</th>
                    <th class="py-3 px-2"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                  <tr v-for="(item, index) in items" :key="index" class="group transition-colors">
                    <td class="py-4 px-2">
                       <div class="flex items-center">
                          <img :src="item.image || '/assets/placeholder.png'" class="w-10 h-10 rounded-lg object-cover mr-3 bg-gray-100 dark:bg-gray-700 shadow-sm" />
                          <div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ item.name }}</div>
                            <div class="text-xs text-blue-500 font-medium">{{ item.variation_name || 'Standard' }}</div>
                          </div>
                       </div>
                    </td>
                    <td class="py-4 px-2">
                      <input v-model.number="item.quantity" type="number" min="1" class="w-24 mx-auto bg-gray-50 dark:bg-gray-900/50 border-gray-100 dark:border-gray-700 rounded-lg text-center text-sm font-bold focus:ring-blue-500" />
                    </td>
                    <td class="py-4 px-2">
                      <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                        <input v-model.number="item.unit_cost" type="number" step="0.01" class="w-full pl-7 bg-gray-50 dark:bg-gray-900/50 border-gray-100 dark:border-gray-700 rounded-lg text-sm font-bold focus:ring-blue-500" />
                      </div>
                    </td>
                    <td class="py-4 px-2 text-right">
                       <div class="text-sm font-black text-gray-900 dark:text-white">৳{{ (item.quantity * item.unit_cost).toFixed(2) }}</div>
                    </td>
                    <td class="py-4 px-2 text-right">
                      <button @click="removeItem(index)" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                        <XCircle size="20" />
                      </button>
                    </td>
                  </tr>
                  <tr v-if="items.length === 0">
                    <td colspan="5" class="py-12 text-center text-gray-400 italic">
                       Search and select a product to begin adding stock.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
              <FileText size="20" class="mr-2 text-blue-500" /> Additional Notes
            </h3>
            <textarea v-model="form.note" rows="3" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-100 dark:border-gray-700 rounded-xl" placeholder="Any instructions or comments..."></textarea>
          </div>
        </div>

        <!-- Sidebar Config -->
        <div class="space-y-6">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Order Summary</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Reference No (Optional)</label>
                <input v-model="form.reference_no" type="text" class="w-full rounded-xl border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:ring-blue-500" placeholder="e.g. INV-123" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Supplier *</label>
                <select v-model="form.supplier_id" class="w-full rounded-xl border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:ring-blue-500">
                  <option value="">Select Supplier</option>
                  <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Purchase Date *</label>
                <input v-model="form.purchase_date" type="date" class="w-full rounded-xl border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:ring-blue-500" />
              </div>

              <div class="pt-4 border-t border-gray-100 dark:border-gray-700 space-y-2">
                <div class="flex justify-between text-sm">
                   <span class="text-gray-500">Subtotal</span>
                   <span class="font-medium text-gray-900 dark:text-white">৳{{ subtotal.toFixed(2) }}</span>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">Shipping Cost</label>
                  <input v-model.number="form.shipping_cost" type="number" class="w-full bg-transparent border-gray-100 dark:border-gray-700 rounded-lg text-right text-sm font-bold" />
                </div>
                <div class="flex justify-between items-center pt-2">
                   <span class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-tight">Total Amount</span>
                   <span class="text-xl font-black text-gray-900 dark:text-white">৳{{ totalAmount.toFixed(2) }}</span>
                </div>
                <div class="flex flex-col pt-3 border-t border-gray-100 dark:border-gray-700">
                  <label class="block text-[10px] font-bold text-emerald-600 uppercase tracking-widest pl-1 mb-2">Amount Paid to Supplier</label>
                  <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">৳</span>
                    <input v-model.number="form.paid_amount" type="number" step="0.01" class="w-full pl-10 pr-4 py-3 bg-emerald-50/50 border-emerald-100 rounded-xl text-lg font-black text-emerald-700 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 transition-all outline-none" />
                  </div>
                </div>
                <div class="flex justify-between items-center pt-4 mt-2 border-t border-dashed border-gray-200">
                   <span class="text-sm font-bold text-gray-400">Remaining Balance</span>
                   <span class="text-xl font-black" :class="totalAmount - form.paid_amount > 0 ? 'text-rose-500' : 'text-emerald-500'">
                     ৳{{ (totalAmount - form.paid_amount).toFixed(2) }}
                   </span>
                </div>
              </div>

              <button @click="submitPurchase" :disabled="isSubmitting || items.length === 0" class="w-full btn btn-primary py-4 rounded-xl shadow-xl shadow-blue-500/20 text-lg font-bold mt-4 flex items-center justify-center">
                 <span v-if="!isSubmitting">Create Purchase Order</span>
                 <Loader2 v-else class="animate-spin" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { MoveLeft, Package, FileText, XCircle, Loader2 } from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  suppliers: Array,
  products: Array,
});

const selectedProductId = ref("");
const items = ref([]);
const isSubmitting = ref(false);

const form = reactive({
  supplier_id: '',
  reference_no: '',
  purchase_date: new Date().toISOString().substr(0, 10),
  shipping_cost: 0,
  paid_amount: 0,
  note: '',
});

const formatVariation = (v) => {
  const attrs = v.variation_attributes || v.attribute_values || [];
  if (attrs.length === 0) return '';
  return attrs.map(a => a.value?.value || a.value || '').filter(v => v !== '').join(', ');
};

const addItem = () => {
    if (!selectedProductId.value) return;
    
    const product = props.products.find(p => p.id === selectedProductId.value);
    if (!product) return;

    if (product.variations && product.variations.length > 0) {
        // Add all variations for bulk restocking
        product.variations.forEach(v => {
            const existing = items.value.find(i => i.product_id === product.id && i.variation_id === v.id);
            if (!existing) {
                items.value.push({
                    product_id: product.id,
                    variation_id: v.id,
                    name: product.name,
                    variation_name: formatVariation(v) || 'Size Unknown',
                    image: v.image_path || product.feature_image || product.feature_image_url,
                    quantity: 1,
                    unit_cost: parseFloat(v.unit_cost) || 0
                });
            }
        });
        toast.success(`Batch added: ${product.name} variations`);
    } else {
        // Add standard product
        const existing = items.value.find(i => i.product_id === product.id && !i.variation_id);
        if (existing) {
            existing.quantity++;
        } else {
            items.value.push({
                product_id: product.id,
                variation_id: null,
                name: product.name,
                variation_name: 'Standard',
                image: product.feature_image || product.feature_image_url,
                quantity: 1,
                unit_cost: parseFloat(product.unit_cost) || 0
            });
        }
    }
    
    selectedProductId.value = "";
};

const removeItem = (index) => {
    items.value.splice(index, 1);
};

const subtotal = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
});

const totalAmount = computed(() => {
    return subtotal.value + (parseFloat(form.shipping_cost) || 0);
});

const submitPurchase = () => {
    if (!form.supplier_id) return toast.error('Please select a supplier');
    if (items.value.length === 0) return toast.error('Add at least one item');

    isSubmitting.value = true;
    const data = {
        ...form,
        items: items.value.map(i => ({
            product_id: i.product_id,
            product_variation_id: i.variation_id,
            quantity: i.quantity,
            unit_cost: i.unit_cost
        }))
    };

    router.post(route('admin.purchases.store'), data, {
        onSuccess: () => {
            isSubmitting.value = false;
            toast.success('Purchase order created successfully');
        },
        onError: () => {
             isSubmitting.value = false;
        }
    });
};
</script>
