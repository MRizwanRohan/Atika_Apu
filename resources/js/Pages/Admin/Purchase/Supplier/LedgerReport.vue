<template>
  <AdminLayout>
    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Supplier Ledger</h1>
            <p class="text-gray-600 mt-2">Track payments, bills, and outstanding dues per supplier</p>
          </div>
          <div class="flex gap-3">
             <button @click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                <Download :size="16" /> Export CSV
             </button>
             <Link :href="route('admin.purchases.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 rounded-lg text-sm font-bold text-white hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                <Plus :size="16" /> New Purchase
             </Link>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
             <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                   <FileText :size="20" />
                </div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Billing</span>
             </div>
             <h3 class="text-2xl font-black text-gray-900">৳{{ totalBill.toLocaleString() }}</h3>
          </div>

          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
             <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                   <CreditCard :size="20" />
                </div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Paid</span>
             </div>
             <h3 class="text-2xl font-black text-gray-900">৳{{ totalPaid.toLocaleString() }}</h3>
          </div>

          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
             <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
                   <AlertCircle :size="20" />
                </div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Outstanding Due</span>
             </div>
             <h3 class="text-2xl font-black text-rose-600">৳{{ totalDue.toLocaleString() }}</h3>
          </div>
        </div>

        <!-- Desktop Ledger Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
             <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight">Active Suppliers Ledger</h3>
             <div class="text-[10px] font-medium text-gray-400 uppercase">Values include Opening Balances</div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50">
                  <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Supplier</th>
                  <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Opening Bal.</th>
                  <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Bill</th>
                  <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Paid</th>
                  <th class="px-6 py-4 text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">Net Due</th>
                  <th class="px-6 py-4"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="item in report" :key="item.id" class="hover:bg-gray-50/50 transition-colors group">
                  <td class="px-6 py-5">
                    <div class="flex items-center gap-3">
                       <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold text-xs uppercase">
                          {{ item.name.substring(0, 2) }}
                       </div>
                       <div>
                          <div class="text-sm font-bold text-gray-900">{{ item.name }}</div>
                          <div class="text-[10px] text-gray-400">ID: #{{ item.id }}</div>
                       </div>
                    </div>
                  </td>
                  <td class="px-6 py-5 text-right tabular-nums text-sm text-gray-600">
                    ৳{{ Number(item.opening_balance).toLocaleString() }}
                  </td>
                  <td class="px-6 py-5 text-right tabular-nums text-sm text-gray-900 font-medium">
                    ৳{{ Number(item.total_bill).toLocaleString() }}
                  </td>
                  <td class="px-6 py-5 text-right tabular-nums text-sm text-emerald-600 font-medium">
                    ৳{{ Number(item.total_paid).toLocaleString() }}
                  </td>
                  <td class="px-6 py-5 text-right tabular-nums">
                    <span class="text-sm font-black" :class="item.total_due > 0 ? 'text-rose-600' : 'text-emerald-600'">
                       ৳{{ Math.abs(item.total_due).toLocaleString() }}
                       <small v-if="item.total_due < 0" class="text-[10px] block opacity-50 uppercase">Advance</small>
                       <small v-else-if="item.total_due > 0" class="text-[10px] block opacity-50 uppercase">Payable</small>
                    </span>
                  </td>
                  <td class="px-6 py-5 text-right">
                     <div class="flex items-center justify-end gap-2">
                        <button 
                           v-if="item.total_due > 0"
                           @click="openPaymentModal(item)" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all"
                        >
                           <CreditCard :size="12" /> Pay Due
                        </button>
                        <Link :href="route('admin.suppliers.show', item.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 text-gray-600 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-gray-900 hover:text-white transition-all opacity-0 group-hover:opacity-100">
                           <Eye :size="12" /> View
                        </Link>
                     </div>
                  </td>
                </tr>
                <tr v-if="report.length === 0">
                  <td colspan="6" class="px-6 py-20 text-center text-gray-400 italic">
                     No supplier data found.
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50/50 border-t border-gray-100">
                 <tr>
                    <th class="px-6 py-4 text-xs font-black text-gray-900">GRAND TOTAL</th>
                    <th class="px-6 py-4 text-right tabular-nums text-sm font-black text-gray-900">৳{{ totalOpening.toLocaleString() }}</th>
                    <th class="px-6 py-4 text-right tabular-nums text-sm font-black text-gray-900">৳{{ totalBill.toLocaleString() }}</th>
                    <th class="px-6 py-4 text-right tabular-nums text-sm font-black text-emerald-600">৳{{ totalPaid.toLocaleString() }}</th>
                    <th class="px-6 py-4 text-right tabular-nums text-sm font-black text-rose-600">৳{{ totalDue.toLocaleString() }}</th>
                    <th></th>
                 </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <AdminModal v-model="showPaymentModal" title="Record Supplier Payment" @confirm="submitPayment">
       <div class="space-y-4 py-2">
          <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
             <div class="text-[10px] font-bold text-gray-400 uppercase">Paying To</div>
             <div class="text-lg font-black text-gray-900">{{ selectedSupplier?.name }}</div>
             <div class="mt-1 text-sm text-rose-600 font-bold">Current Due: ৳{{ Number(selectedSupplier?.total_due).toLocaleString() }}</div>
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
import { ref, computed } from 'vue'
import { FileText, CreditCard, AlertCircle, Download, Plus, Eye } from 'lucide-vue-next'
import { toast } from "@steveyuowo/vue-hot-toast"

const props = defineProps({
  report: {
    type: Array,
    default: () => []
  }
})

// Payment Modal State
const showPaymentModal = ref(false)
const selectedSupplier = ref(null)
const paymentForm = useForm({
  supplier_id: null,
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'cash',
  note: ''
})

const openPaymentModal = (supplier) => {
  selectedSupplier.value = supplier
  paymentForm.supplier_id = supplier.id
  paymentForm.amount = supplier.total_due
  showPaymentModal.value = true
}

const submitPayment = () => {
  paymentForm.post(route('admin.suppliers.record-payment'), {
    onSuccess: () => {
       showPaymentModal.value = false
       toast.success('Payment recorded successfully!')
    },
    onError: () => {
       toast.error('Failed to record payment. Please check your input.')
    }
  })
}

const totalOpening = computed(() => props.report.reduce((sum, item) => sum + Number(item.opening_balance), 0))
const totalBill = computed(() => props.report.reduce((sum, item) => sum + Number(item.total_bill), 0))
const totalPaid = computed(() => props.report.reduce((sum, item) => sum + Number(item.total_paid), 0))
const totalDue = computed(() => props.report.reduce((sum, item) => sum + Number(item.total_due), 0))

const exportCSV = () => {
  const headers = ['Supplier', 'Opening Balance', 'Total Bill', 'Total Paid', 'Total Due']
  const rows = props.report.map(item => [
    item.name,
    item.opening_balance,
    item.total_bill,
    item.total_paid,
    item.total_due
  ])
  
  let csvContent = "data:text/csv;charset=utf-8," 
    + headers.join(",") + "\n"
    + rows.map(e => e.join(",")).join("\n")

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement("a")
  link.setAttribute("href", encodedUri)
  link.setAttribute("download", `supplier_ledger_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>
