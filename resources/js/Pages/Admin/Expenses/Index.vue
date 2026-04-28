<template>
  <Head title="Expense Management" />
  <AdminLayout>
    <div class="w-full mx-auto py-10 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Business <span class="text-blue-600">Expenses</span></h1>
          <p class="text-gray-500 mt-1">Track overhead costs, bills, and office expenses.</p>
        </div>
        <button @click="openCreateModal" class="btn btn-primary flex items-center shadow-lg shadow-blue-500/20">
          <Plus size="18" class="mr-2" /> Record New Expense
        </button>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-xs font-bold text-gray-400 uppercase tracking-wider">
                <th class="px-6 py-5">Date</th>
                <th class="px-6 py-5">Category</th>
                <th class="px-6 py-5">Description</th>
                <th class="px-6 py-5 text-right">Amount</th>
                <th class="px-6 py-5"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors">
                <td class="px-6 py-5 font-medium text-gray-900 dark:text-white">{{ expense.date }}</td>
                <td class="px-6 py-5">
                   <div class="font-bold text-gray-900 dark:text-white">{{ expense.name || 'General Expense' }}</div>
                   <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                     {{ expense.category?.name }}
                   </span>
                </td>
                <td class="px-6 py-5 text-sm text-gray-600 dark:text-gray-400">{{ expense.note }}</td>
                <td class="px-6 py-5 text-right font-black text-gray-900 dark:text-white">৳{{ expense.amount }}</td>
                <td class="px-6 py-5 text-right space-x-2">
                  <button @click="openEditModal(expense)" class="p-2 text-gray-400 hover:text-blue-500 transition-colors">
                    <Pencil size="18" />
                  </button>
                  <button @click="deleteExpense(expense.id)" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                    <Trash2 size="18" />
                  </button>
                </td>
              </tr>
              <tr v-if="expenses.data.length === 0">
                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">No expenses recorded yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <AdminModal v-model="showModal" :title="isEditing ? 'Edit Expense Record' : 'Record New Expense'">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Date</label>
              <input v-model="form.date" type="date" class="form-input" required />
            </div>
            <div>
              <label class="label">Category</label>
              <select v-model="form.expense_category_id" class="form-input" required>
                <option value="">Select Category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
        </div>
        <div>
          <label class="label">Expense Name / Title</label>
          <input v-model="form.name" type="text" class="form-input" placeholder="e.g. Office Electricity Bill" />
        </div>
        <div>
          <label class="label">Amount</label>
          <div class="relative">
             <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">৳</span>
             <input v-model="form.amount" type="number" step="0.01" class="form-input pl-10" required />
          </div>
        </div>
        <div>
          <label class="label">Note</label>
          <textarea v-model="form.note" class="form-input" rows="3" placeholder="Additional details..."></textarea>
        </div>
        <div class="flex justify-end gap-3 mt-8">
          <button type="button" @click="showModal = false" class="btn btn-secondary">Cancel</button>
          <button type="submit" :disabled="form.processing" class="btn btn-primary px-8">Save Transaction</button>
        </div>
      </form>
    </AdminModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminModal from '@/Components/Modal/AdminModal.vue';
import { Plus, Trash2, Pencil } from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  expenses: Object,
  categories: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
  date: new Date().toISOString().substr(0, 10),
  expense_category_id: '',
  amount: '',
  name: '',
  note: '',
  month: '',
});

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  showModal.value = true;
};

const openEditModal = (expense) => {
  isEditing.value = true;
  editingId.value = expense.id;
  form.date = expense.date;
  form.expense_category_id = expense.expense_category_id;
  form.amount = expense.amount;
  form.name = expense.name;
  form.note = expense.note;
  showModal.value = true;
};

const submit = () => {
  const date = new Date(form.date);
  form.month = date.toLocaleString('default', { month: 'long' });
  
  if (isEditing.value) {
    form.put(route('admin.expenses.update', editingId.value), {
      onSuccess: () => {
        showModal.value = false;
        toast.success('Expense updated successfully!');
      }
    });
  } else {
    form.post(route('admin.expenses.store'), {
      onSuccess: () => {
        showModal.value = false;
        toast.success('Expense recorded successfully!');
      }
    });
  }
};

const deleteExpense = (id) => {
  if (confirm('Delete this expense?')) {
    form.delete(route('admin.expenses.destroy', id), {
      onSuccess: () => toast.success('Expense deleted.')
    });
  }
};
</script>
