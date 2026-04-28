<template>
  <Head title="Purchases" />
  <AdminLayout>
    <div class="w-full mx-auto py-10 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Purchase Orders</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track and manage inventory procurement from suppliers.</p>
        </div>
        <Link :href="route('admin.purchases.create')" class="btn btn-primary inline-flex items-center group transition-all duration-200">
          <ShoppingCart size="18" class="mr-2 group-hover:scale-110 transition-transform duration-300" />
          New Purchase
        </Link>
      </div>

      <!-- Filters & Search -->
      <div class="mb-6 flex flex-wrap gap-4 items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700">
         <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-sm font-bold border border-blue-100 dark:border-blue-800">
              Total: {{ purchases.total }}
            </span>
         </div>
         <div class="relative w-full sm:w-72">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
              <Search size="16" />
            </span>
            <input 
              v-model="search"
              type="text" 
              placeholder="Search by Reference or Supplier..." 
              class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border-0 focus:ring-2 focus:ring-blue-500 rounded-xl text-sm transition-all"
            />
         </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest text-[10px]">Reference / Date</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest text-[10px]">Supplier</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest text-[10px]">Total Amount</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest text-[10px]">Status</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest text-[10px] text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="purchase in purchases.data" :key="purchase.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all duration-200 group">
                <td class="px-6 py-5">
                  <div class="font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ purchase.reference_no }}</div>
                  <div class="text-[10px] text-gray-400 flex items-center mt-1 font-bold uppercase">
                    <Calendar size="10" class="mr-1" /> {{ formatDate(purchase.purchase_date) }}
                  </div>
                </td>
                <td class="px-6 py-5">
                  <div class="flex items-center">
                    <div class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 mr-3 text-gray-400 group-hover:bg-blue-500 group-hover:text-white transition-colors duration-300">
                      <Truck size="14" />
                    </div>
                    <div>
                      <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ purchase.supplier?.name || 'Unknown' }}</span>
                      <span class="text-[10px] text-gray-400 font-medium">{{ purchase.supplier?.phone }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5">
                  <div class="font-black text-gray-900 dark:text-white text-base">৳{{ Number(purchase.total_amount).toLocaleString() }}</div>
                  <div class="flex flex-col gap-1 mt-1">
                    <div v-if="purchase.paid_amount > 0" class="text-[9px] text-[#10B981] font-black uppercase flex items-center">
                      <div class="w-1.5 h-1.5 rounded-full bg-[#10B981] mr-1.5"></div>
                      Paid: ৳{{ Number(purchase.paid_amount).toLocaleString() }}
                    </div>
                    <div v-if="purchase.total_amount - purchase.paid_amount > 0" class="text-[9px] text-rose-500 font-black uppercase flex items-center">
                      <div class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></div>
                      Due: ৳{{ (purchase.total_amount - purchase.paid_amount).toLocaleString() }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5">
                  <span :class="[
                    'inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest',
                    statusClasses[purchase.status] || 'bg-gray-100 text-gray-800'
                  ]">
                    {{ purchase.status === 'pending' ? 'Pending' : 'Received' }}
                  </span>
                </td>
                <td class="px-6 py-5 text-right space-x-1">
                  <button v-if="purchase.status === 'pending'" @click="receivePurchase(purchase)" class="px-3 py-1.5 bg-emerald-500 text-white text-[10px] font-black uppercase rounded-lg hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                    Receive Item
                  </button>
                  <Link :href="route('admin.purchases.show', purchase.id)" class="p-2.5 text-gray-400 hover:bg-blue-500 hover:text-white rounded-xl transition-all inline-block">
                    <Eye size="16" />
                  </Link>
                  <!-- <button @click="printPurchase(purchase.id)" class="p-2.5 text-gray-400 hover:bg-gray-900 hover:text-white rounded-xl transition-all inline-block">
                    <Printer size="16" />
                  </button> -->
                </td>
              </tr>
              <tr v-if="purchases.data.length === 0">
                <td colspan="5" class="px-6 py-20 text-center text-gray-500">
                  <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-gray-900 rounded-full flex items-center justify-center mb-4">
                      <History size="32" class="text-gray-300" />
                    </div>
                    <p class="font-bold text-gray-400">No purchase records found.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination Area -->
        <div v-if="purchases.links && purchases.links.length > 3" class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-center">
           <Pagination :links="purchases.links" />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { ShoppingCart, Calendar, Truck, Eye, History, Search, Printer } from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
  purchases: Object,
  filters: Object
});

const search = ref(props.filters.search || '');

const statusClasses = {
  'pending': 'bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
  'received': 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400',
  'Cancelled': 'bg-red-50 text-red-600 border border-red-100 dark:bg-red-900/30 dark:text-red-400',
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

const receivePurchase = (purchase) => {
  if (confirm('Verify receiving items? Stock and Costs will be updated automatically.')) {
    router.post(route('admin.purchases.receive', purchase.id), {}, {
      onSuccess: () => toast.success('Inventory received and updated!')
    });
  }
};

const printPurchase = (id) => {
  router.visit(route('admin.purchases.show', id) + '?print=true');
};

watch(search, debounce((value) => {
  router.get(route('admin.purchases.index'), { search: value }, { 
    preserveState: true,
    replace: true 
  });
}, 500));
</script>

<style scoped>
.animate-pulse-slow {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}
</style>
