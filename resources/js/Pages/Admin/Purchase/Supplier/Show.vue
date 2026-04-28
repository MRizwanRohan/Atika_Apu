<template>
  <AdminLayout>
    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start">
          <div>
            <Link :href="route('admin.suppliers.index')" class="text-sm text-gray-500 hover:text-gray-900 flex items-center gap-1 mb-4 group transition-colors">
              <ArrowLeft :size="16" class="group-hover:-translate-x-1 transition-transform" /> Back to Suppliers
            </Link>
            <h1 class="text-3xl font-black text-gray-900">{{ supplier.name }}</h1>
            <div class="flex items-center gap-4 mt-2">
              <span class="flex items-center gap-1 text-sm text-gray-600">
                <Phone :size="14" class="text-gray-400" /> {{ supplier.phone }}
              </span>
              <span v-if="supplier.email" class="flex items-center gap-1 text-sm text-gray-600">
                <Mail :size="14" class="text-gray-400" /> {{ supplier.email }}
              </span>
            </div>
          </div>
          <div class="flex gap-3 mt-10">
             <button @click="openPaymentModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 rounded-xl text-sm font-black text-white hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">
                <CreditCard :size="18" /> Pay Due
             </button>
             <Link :href="route('admin.purchases.create', { supplier_id: supplier.id })" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 rounded-xl text-sm font-black text-white hover:bg-gray-800 transition-all shadow-lg shadow-gray-200">
                <Plus :size="18" /> New Purchase
             </Link>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
             <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Opening Balance</div>
             <h3 class="text-xl font-bold text-gray-900 font-mono">৳{{ Number(supplier.opening_balance).toLocaleString() }}</h3>
          </div>
          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
             <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Total Billing</div>
             <h3 class="text-xl font-bold text-gray-900 font-mono">৳{{ summary.total_bill.toLocaleString() }}</h3>
          </div>
          <div class="bg-white p-6 rounded-2xl border-emerald-100 shadow-sm bg-emerald-50/20">
             <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-2">Total Paid</div>
             <h3 class="text-xl font-bold text-emerald-600 font-mono">৳{{ summary.total_paid.toLocaleString() }}</h3>
          </div>
          <div class="bg-white p-6 rounded-2xl border-rose-100 shadow-sm bg-rose-50/20">
             <div class="text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-2">Current Due</div>
             <h3 class="text-xl font-bold text-rose-600 font-mono">৳{{ summary.due.toLocaleString() }}</h3>
          </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="flex border-b border-gray-50 bg-gray-50/50">
            <button 
              @click="activeTab = 'purchases'" 
              :class="activeTab === 'purchases' ? 'bg-white text-gray-900 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
              class="px-6 py-4 text-xs font-bold uppercase tracking-widest transition-all"
            >
              Purchase History
            </button>
            <button 
              @click="activeTab = 'payments'" 
              :class="activeTab === 'payments' ? 'bg-white text-gray-900 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
              class="px-6 py-4 text-xs font-bold uppercase tracking-widest transition-all"
            >
              Payment Records
            </button>
          </div>

          <!-- Purchase History Tab -->
          <div v-show="activeTab === 'purchases'" class="p-0">
             <div class="overflow-x-auto">
               <table class="w-full text-left">
                 <thead>
                    <tr class="bg-gray-50/30">
                       <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date</th>
                       <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reference</th>
                       <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                       <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total</th>
                       <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Paid</th>
                       <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Due</th>
                    </tr>
                 </thead>
                 <tbody class="divide-y divide-gray-50">
                    <tr v-for="item in supplier.purchases" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                       <td class="px-6 py-4 text-sm text-gray-600 tabular-nums">{{ new Date(item.purchase_date).toLocaleDateString() }}</td>
                       <td class="px-6 py-4 text-sm font-bold text-gray-900">#{{ item.reference_no }}</td>
                       <td class="px-6 py-4">
                          <span :class="item.status === 'received' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
                             {{ item.status }}
                          </span>
                       </td>
                       <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">৳{{ Number(item.total_amount).toLocaleString() }}</td>
                       <td class="px-6 py-4 text-right text-sm font-bold text-emerald-600">৳{{ Number(item.paid_amount).toLocaleString() }}</td>
                       <td class="px-6 py-4 text-right text-sm font-bold text-rose-600">৳{{ (item.total_amount - item.paid_amount).toLocaleString() }}</td>
                    </tr>
                    <tr v-if="supplier.purchases.length === 0">
                       <td colspan="6" class="px-6 py-20 text-center text-gray-400 italic">No purchase records found.</td>
                    </tr>
                 </tbody>
               </table>
             </div>
          </div>

          <!-- Payment History Tab -->
          <div v-show="activeTab === 'payments'" class="p-0">
             <div class="overflow-x-auto">
               <table class="w-full text-left">
                 <thead>
                    <tr class="bg-gray-50/30">
                       <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date</th>
                       <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Method</th>
                       <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reference / Note</th>
                       <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Amount Paid</th>
                    </tr>
                 </thead>
                 <tbody class="divide-y divide-gray-50">
                    <tr v-for="item in supplier.payments" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                       <td class="px-6 py-4 text-sm text-gray-600 tabular-nums">{{ new Date(item.payment_date).toLocaleDateString() }}</td>
                       <td class="px-6 py-4">
                          <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-[10px] font-bold uppercase tracking-wider">{{ item.payment_method }}</span>
                       </td>
                       <td class="px-6 py-4 text-sm text-gray-600">{{ item.note || '-' }}</td>
                       <td class="px-6 py-4 text-right text-lg font-black text-emerald-600">৳{{ Number(item.amount).toLocaleString() }}</td>
                    </tr>
                    <tr v-if="supplier.payments.length === 0">
                       <td colspan="4" class="px-6 py-20 text-center text-gray-400 italic">No payment records found.</td>
                    </tr>
                 </tbody>
               </table>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <AdminModal v-model="showPaymentModal" title="Record Supplier Payment" @confirm="submitPayment">
       <div class="space-y-4 py-2">
          <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
             <div class="text-[10px] font-bold text-gray-400 uppercase">Paying To</div>
             <div class="text-lg font-black text-gray-900">{{ supplier.name }}</div>
             <div class="mt-1 text-sm text-rose-600 font-bold">Current Due: ৳{{ summary.due.toLocaleString() }}</div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
             <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Amount to Pay *</label>
                <input v-model="paymentForm.amount" type="number" step="0.01" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold" placeholder="0.00" />
             </div>
             <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Payment Date *</label>
                <input v-model="paymentForm.payment_date" type="date" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500" />
             </div>
          </div>

          <div>
             <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Payment Method</label>
             <select v-model="paymentForm.payment_method" class="w-full bg-gray-50 border-gray-200 rounded-xl">
                <option value="cash">Cash</option>
                <option value="bank">Bank Transfer</option>
                <option value="bkash">bKash / MFS</option>
                <option value="cheque">Cheque</option>
             </select>
          </div>

          <div>
             <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Reference / Note</label>
             <textarea v-model="paymentForm.note" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl" placeholder="Check number, transaction ID, etc."></textarea>
          </div>
       </div>
       <template #footer>
          <button @click="showPaymentModal = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-900 transition-colors">Cancel</button>
          <button @click="submitPayment" class="px-6 py-2 bg-emerald-600 text-white rounded-xl text-sm font-black hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">Confirm Payment</button>
       </template>
    </AdminModal>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AdminModal from '@/Components/Modal/AdminModal.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { ArrowLeft, Phone, Mail, Plus, CreditCard } from 'lucide-vue-next'
import { toast } from "@steveyuowo/vue-hot-toast"

const props = defineProps({
  supplier: Object,
  summary: Object
})

const activeTab = ref('purchases')

// Payment Modal State
const showPaymentModal = ref(false)
const paymentForm = useForm({
  supplier_id: props.supplier.id,
  amount: props.summary.due,
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'cash',
  note: ''
})

const openPaymentModal = () => {
  paymentForm.amount = props.summary.due
  showPaymentModal.value = true
}

const submitPayment = () => {
  paymentForm.post(route('admin.suppliers.record-payment'), {
    onSuccess: () => {
       showPaymentModal.value = false
       toast.success('Payment recorded successfully!')
    }
  })
}
</script>
