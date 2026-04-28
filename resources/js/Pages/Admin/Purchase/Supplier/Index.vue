<template>
  <Head title="Suppliers" />
  <AdminLayout>
    <div class="w-full mx-auto py-10 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Suppliers</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your product suppliers and their contact information.</p>
        </div>
        <div class="flex gap-2">
          <Link :href="route('admin.suppliers.ledger-report')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
            <FileText size="18" class="mr-2" />
            Supplier Ledger
          </Link>
          <button @click="openCreateModal" class="btn btn-primary inline-flex items-center group transition-all duration-200">
            <Plus size="18" class="mr-2 group-hover:rotate-90 transition-transform duration-300" />
            Add Supplier
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center">
            <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 mr-4">
              <Users size="24" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Suppliers</p>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ data.total }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="mb-6">
        <div class="relative max-w-md">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Search :size="18" class="text-gray-400" />
          </span>
          <input 
            v-model="searchQuery" 
            type="text" 
            @input="handleSearch"
            class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" 
            placeholder="Search by name or phone..."
          />
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier Details</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Balance</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="supplier in data.data" :key="supplier.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors group">
                <td class="px-6 py-5">
                  <Link :href="route('admin.suppliers.show', supplier.id)" class="flex items-center hover:opacity-80 transition-opacity">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold mr-3 shadow-md">
                      {{ supplier.name.charAt(0) }}
                    </div>
                    <div>
                      <div class="font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ supplier.name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ supplier.id }}</div>
                    </div>
                  </Link>
                </td>
                <td class="px-6 py-5">
                  <div class="text-sm text-gray-900 dark:text-white flex items-center">
                    <Phone size="14" class="mr-2 text-gray-400" /> {{ supplier.phone }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center mt-1">
                    <Mail size="14" class="mr-2 text-gray-400" /> {{ supplier.email || 'No Email' }}
                  </div>
                </td>
                <td class="px-6 py-5">
                  <div class="flex flex-col">
                    <div class="text-sm font-black tabular-nums" :class="Number(supplier.dynamic_balance) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                      ৳{{ Number(supplier.dynamic_balance).toLocaleString() }}
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Bill: ৳{{ Number(supplier.total_bill).toLocaleString() }}</span>
                      <span class="text-[10px] text-emerald-500 uppercase font-bold tracking-tighter">Paid: ৳{{ Number(supplier.total_paid).toLocaleString() }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    supplier.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                  ]">
                    {{ supplier.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-5 text-right space-x-2">
                  <Link :href="route('admin.suppliers.show', supplier.id)" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors inline-block">
                    <Eye size="18" />
                  </Link>
                  <button @click="openEditModal(supplier)" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                    <Pencil size="18" />
                  </button>
                  <button @click="confirmDelete(supplier)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                    <Trash2 size="18" />
                  </button>
                </td>
              </tr>
              <tr v-if="data.data.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                  <div class="flex flex-col items-center">
                    <PackageOpen size="48" class="text-gray-300 mb-4" />
                    <p>No suppliers found. Let's add your first merchant partner!</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Placeholder (Could be separate component) -->
    <AdminModal v-model="showModal" :title="isEditing ? 'Edit Supplier' : 'Add New Supplier'" @confirm="submitForm">
      <form class="space-y-4 py-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supplier Name *</label>
            <input v-model="form.name" type="text" class="form-input mt-1 block w-full rounded-xl" placeholder="e.g. Acme Wholesale" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number *</label>
            <input v-model="form.phone" type="text" class="form-input mt-1 block w-full rounded-xl" placeholder="017xxxxxxxx" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
            <input v-model="form.email" type="email" class="form-input mt-1 block w-full rounded-xl" placeholder="supplier@example.com" />
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
            <textarea v-model="form.address" rows="2" class="form-input mt-1 block w-full rounded-xl" placeholder="Street address, city, etc."></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Opening Balance</label>
            <input v-model="form.opening_balance" type="number" step="0.01" class="form-input mt-1 block w-full rounded-xl" />
          </div>
          <div class="flex items-center pt-6">
            <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4 text-blue-600 rounded-md" />
            <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Active Status</label>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
          Cancel
        </button>
        <button @click="submitForm" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
          {{ isEditing ? 'Update Supplier' : 'Save Supplier' }}
        </button>
      </template>
    </AdminModal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminModal from '@/Components/Modal/AdminModal.vue';
import { Plus, Users, Phone, Mail, Pencil, Trash2, PackageOpen, FileText, Search, Eye } from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";
import { debounce } from 'lodash';

const props = defineProps({
  data: Object,
  filters: Object,
});

const searchQuery = ref(props.filters.search || '');

const handleSearch = debounce(() => {
  router.get(route('admin.suppliers.index'), { search: searchQuery.value }, {
    preserveState: true,
    replace: true
  });
}, 300);

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
  contact_person: '',
  opening_balance: 0,
  is_active: true
});

const resetForm = () => {
  form.name = '';
  form.phone = '';
  form.email = '';
  form.address = '';
  form.contact_person = '';
  form.opening_balance = 0;
  form.is_active = true;
};

const openCreateModal = () => {
  isEditing.value = false;
  resetForm();
  showModal.value = true;
};

const openEditModal = (supplier) => {
  isEditing.value = true;
  editingId.value = supplier.id;
  Object.assign(form, supplier);
  showModal.value = true;
};

const submitForm = () => {
  if (isEditing.value) {
    router.put(route('admin.suppliers.update', editingId.value), form, {
      onSuccess: () => {
        showModal.value = false;
        toast.success('Supplier updated successfully');
      }
    });
  } else {
    router.post(route('admin.suppliers.store'), form, {
      onSuccess: () => {
        showModal.value = false;
        toast.success('Supplier added successfully');
      }
    });
  }
};

const confirmDelete = (supplier) => {
  if (confirm(`Are you sure you want to delete ${supplier.name}?`)) {
    router.delete(route('admin.suppliers.destroy', supplier.id), {
      onSuccess: () => toast.success('Supplier deleted successfully')
    });
  }
};
</script>
