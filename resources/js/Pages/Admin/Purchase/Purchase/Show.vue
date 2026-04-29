<template>
  <Head title="Purchase Details" />
  <AdminLayout>
    <!-- SCREEN ONLY CONTENT -->
    <div class="w-full mx-auto py-10 px-4 sm:px-6 lg:px-8 print:hidden">
      <!-- Screen Header -->
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="route('admin.purchases.index')" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-500 hover:text-blue-600 transition-colors">
            <MoveLeft size="20" />
          </Link>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Purchase <span class="text-blue-600">#{{ purchase.reference_no }}</span></h1>
        </div>
        <div class="flex gap-3">
           <button @click="triggerPrint" class="btn btn-secondary flex items-center bg-gray-900 text-white hover:bg-black">
             <Printer size="18" class="mr-2" /> Print Invoice
           </button>
           <button v-if="purchase.status === 'pending'" @click="receivePurchase" class="btn btn-success flex items-center">
             <CheckCircle size="18" class="mr-2" /> Receive Item
           </button>
           <button v-if="purchase.status === 'received_no_stock'" @click="updateStock" class="btn flex items-center bg-orange-500 text-white hover:bg-orange-600">
             <PackagePlus size="18" class="mr-2" /> Update Stock
           </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex justify-between items-center">
               <h3 class="font-bold text-gray-900 dark:text-white flex items-center">
                 <Package size="20" class="mr-2 text-blue-500" /> Items Summary
               </h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 dark:border-gray-700">
                    <th class="px-6 py-4">Item Details</th>
                    <th class="px-6 py-4 text-center">Qty</th>
                    <th class="px-6 py-4 text-right">Unit Rate</th>
                    <th class="px-6 py-4 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                  <tr v-for="item in purchase.items" :key="item.id">
                    <td class="px-6 py-4">
                       <div class="flex items-center">
                          <img v-if="item.product" :src="item.product?.feature_image" class="w-10 h-10 rounded-lg object-cover mr-3 bg-gray-100" />
                          <div>
                            <p class="font-bold text-gray-900 dark:text-white capitalize text-sm">{{ item.product?.name }}</p>
                            <p class="text-[9px] text-blue-500 font-bold uppercase tracking-wider">{{ item.variation ? formatVariation(item.variation) : 'Standard' }}</p>
                          </div>
                       </div>
                    </td>
                    <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{{ item.quantity }}</td>
                    <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400 text-xs font-medium">৳{{ Number(item.unit_cost).toLocaleString() }}</td>
                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">৳{{ (item.quantity * item.unit_cost).toLocaleString() }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="purchase.note" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
             <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Notes</p>
             <p class="text-gray-600 dark:text-gray-400 text-sm whitespace-pre-line border-l-4 border-blue-500 pl-4 py-1 italic">{{ purchase.note }}</p>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-6 uppercase tracking-widest text-[10px]">Purchase Info</h3>
            <div class="space-y-4">
               <div>
                  <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Status</div>
                  <span :class="[
                    'px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-tight',
                    purchase.status === 'pending' ? 'bg-blue-50 text-blue-700' :
                    purchase.status === 'received_no_stock' ? 'bg-orange-50 text-orange-700' :
                    'bg-emerald-50 text-emerald-700'
                  ]">
                    {{ statusLabels[purchase.status] || purchase.status }}
                  </span>
               </div>
               <div>
                  <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Supplier Profile</div>
                  <div class="font-bold text-gray-900 dark:text-white">{{ purchase.supplier?.name }}</div>
                  <div class="text-xs text-gray-500 font-medium">{{ purchase.supplier?.phone }}</div>
               </div>
               <div>
                  <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Issue Date</div>
                  <div class="font-bold text-gray-900 dark:text-white flex items-center text-sm">
                    <Calendar size="14" class="mr-2 text-gray-400" /> {{ formatDate(purchase.purchase_date) }}
                  </div>
               </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700 space-y-4">
               <div class="flex justify-between items-center text-sm">
                  <span class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Net Total</span>
                  <span class="font-black text-gray-900 dark:text-white">৳{{ (purchase.total_amount - purchase.shipping_cost).toLocaleString() }}</span>
               </div>
               <div class="flex justify-between items-center text-sm">
                  <span class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Shipping</span>
                  <span class="font-black text-gray-900 dark:text-white">+ ৳{{ Number(purchase.shipping_cost).toLocaleString() }}</span>
               </div>
               <div class="flex justify-between items-center pt-5 border-t border-gray-100 dark:border-gray-700">
                  <span class="text-[10px] font-black dark:text-white uppercase tracking-widest text-[#10B981]">Total Paid</span>
                  <span class="text-lg font-black text-[#10B981]">৳{{ Number(purchase.paid_amount || 0).toLocaleString() }}</span>
               </div>
               <div class="flex justify-between items-center pt-4 mt-2 border-t border-dashed border-gray-200">
                  <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Due Balance</span>
                  <span class="text-xl font-black" :class="purchase.total_amount - (purchase.paid_amount || 0) > 0 ? 'text-rose-500' : 'text-emerald-500'">
                    ৳{{ (purchase.total_amount - (purchase.paid_amount || 0)).toLocaleString() }}
                  </span>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>

  <!-- TELEPORTED PRINT ONLY CONTENT -->
  <Teleport to="body">
    <div id="print-invoice-portal" class="hidden print:block bg-white text-black p-10 min-h-screen w-full">
      <div class="mb-10 border-b-2 border-black pb-5 flex justify-between items-start">
         <div>
            <h1 class="text-3xl font-black uppercase tracking-tight">Purchase Invoice</h1>
            <p class="text-xs font-bold text-gray-600 uppercase mt-1">Reference: #{{ purchase.reference_no }}</p>
         </div>
         <div class="text-right">
            <h2 class="text-2xl font-black uppercase">XYLO STOCK</h2>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Inventory Management System</p>
         </div>
      </div>

      <div class="w-full">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b-2 border-black text-[10px] font-black uppercase tracking-widest">
              <th class="py-4">Item Details</th>
              <th class="py-4 text-center">Qty</th>
              <th class="py-4 text-right">Unit Rate</th>
              <th class="py-4 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in purchase.items" :key="item.id">
              <td class="py-4">
                 <p class="font-bold text-sm uppercase">{{ item.product?.name }}</p>
                 <p class="text-[10px] text-gray-600 uppercase font-bold">{{ item.variation ? formatVariation(item.variation) : 'Standard' }}</p>
              </td>
              <td class="py-4 text-center font-bold">{{ item.quantity }}</td>
              <td class="py-4 text-right text-gray-700 text-sm italic">৳{{ Number(item.unit_cost).toLocaleString() }}</td>
              <td class="py-4 text-right font-black">৳{{ (item.quantity * item.unit_cost).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>

        <div v-if="purchase.note" class="mt-8 pt-4 border-t border-gray-100">
           <p class="text-[10px] font-black uppercase mb-1">Additional Notes:</p>
           <p class="text-xs text-gray-700 italic leading-relaxed">{{ purchase.note }}</p>
        </div>

        <div class="mt-12 pt-8 border-t-2 border-black flex flex-wrap justify-between items-start">
           <div class="space-y-4">
              <div>
                 <p class="text-[10px] font-black uppercase mb-1">Issued Date</p>
                 <p class="font-bold text-sm">{{ formatDate(purchase.purchase_date) }}</p>
              </div>
              <div>
                 <p class="text-[10px] font-black uppercase mb-1">Vendor Contact</p>
                 <p class="font-bold text-sm">{{ purchase.supplier?.name }}</p>
                 <p class="text-xs text-gray-600">{{ purchase.supplier?.phone }}</p>
              </div>
           </div>
           <div class="w-64 space-y-3">
              <div class="flex justify-between text-xs font-bold uppercase">
                 <span>Subtotal</span>
                 <span>৳{{ (purchase.total_amount - purchase.shipping_cost).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-xs font-bold uppercase text-emerald-600">
                 <span>Amount Paid</span>
                 <span>৳{{ Number(purchase.paid_amount || 0).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between items-center pt-4 border-t-2 border-black">
                 <span class="text-sm font-black uppercase text-rose-600">Due Amount</span>
                 <span class="text-3xl font-black text-rose-600">৳{{ (purchase.total_amount - (purchase.paid_amount || 0)).toLocaleString() }}</span>
              </div>
           </div>
        </div>

        <div class="mt-24 text-center border-t border-gray-100 pt-8">
           <p class="text-[9px] text-gray-300 font-bold uppercase tracking-[0.4em]">This is an official system generated procurement document</p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { MoveLeft, Package, FileText, Calendar, CheckCircle, Printer, PackagePlus } from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";
import { onMounted } from 'vue';

const props = defineProps({
  purchase: Object,
});

const statusLabels = {
  'pending': 'Pending',
  'received': 'Received',
  'received_no_stock': 'Received (No Stock)',
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatVariation = (v) => {
  return v.attribute_values?.map(av => av.value).join(', ') || 'Standard';
};

const receivePurchase = () => {
    if (confirm('Verify stock receipt? Stock and costs will be updated.')) {
        router.post(route('admin.purchases.receive', props.purchase.id), {}, {
            onSuccess: () => toast.success('Purchase received and stock updated!')
        });
    }
};

const updateStock = () => {
    if (confirm('This will update product stock for all items in this purchase. Continue?')) {
        router.post(route('admin.purchases.receive', props.purchase.id), {}, {
            onSuccess: () => toast.success('Stock updated successfully!')
        });
    }
};

const triggerPrint = () => { window.print(); };

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('print') === 'true') {
     setTimeout(() => {
        window.print();
        router.replace(route('admin.purchases.show', props.purchase.id), { preserveScroll: true });
     }, 1500);
  }
});
</script>

<style>
@media print {
  @page { margin: 0; size: auto; }
  
  /* HIDE EVERYTHING EXCEPT THE PORTALED INVOICE */
  body > *:not(#print-invoice-portal) {
    display: none !important;
  }

  #print-invoice-portal {
    display: block !important;
    position: relative !important;
    visibility: visible !important;
    width: 100% !important;
    height: auto !important;
    margin: 0 !important;
    padding: 1.5cm !important;
    background: white !important;
    color: black !important;
  }
}
</style>
