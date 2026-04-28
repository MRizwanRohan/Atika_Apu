<template>
  <Head title="Expense Categories" />
  <AdminLayout>
    <div class="w-full mx-auto py-10 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Expense Categories</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Classify your operational spending for better reporting.</p>
        </div>
        <button @click="openCreateModal" class="btn btn-primary inline-flex items-center group transition-all duration-200">
          <Layers size="18" class="mr-2" />
          New Category
        </button>
      </div>

      <div class="max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Category Name</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900 dark:text-white">{{ category.name }}</div>
              </td>
              <td class="px-6 py-4">
                  <span :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    category.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ category.is_active ? 'Active' : 'Inactive' }}
                  </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openEditModal(category)" class="p-1.5 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/10 rounded-md">
                   <Pencil size="16" />
                </button>
                <button @click="deleteCategory(category)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 rounded-md">
                   <Trash2 size="16" />
                </button>
              </td>
            </tr>
            <tr v-if="categories.length === 0">
               <td colspan="3" class="px-6 py-10 text-center text-gray-400">No categories defined yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <AdminModal v-model="showModal" :title="isEditing ? 'Edit Category' : 'Create Category'" @confirm="submitForm">
      <div class="py-4 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category Name *</label>
          <input v-model="form.name" type="text" class="form-input mt-1 block w-full rounded-xl" placeholder="e.g. Marketing, Office Rent..." required />
        </div>
        <div class="flex items-center">
            <input id="cat_active" type="checkbox" v-model="form.is_active" class="h-4 w-4 text-blue-600 rounded-md focus:ring-blue-500" />
            <label for="cat_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Active Status</label>
        </div>
      </div>
      
      <template #footer>
        <button @click="showModal = false" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</button>
        <button @click="submitForm" class="btn btn-primary px-8">Save Category</button>
      </template>
    </AdminModal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminModal from '@/Components/Modal/AdminModal.vue';
import { Layers, Pencil, Trash2 } from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  categories: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = reactive({
  name: '',
  is_active: true
});

const openCreateModal = () => {
    isEditing.value = false;
    form.name = '';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (cat) => {
    isEditing.value = true;
    editingId.value = cat.id;
    form.name = cat.name;
    form.is_active = !!cat.is_active;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        router.put(route('admin.expense-categories.update', editingId.value), form, {
            onSuccess: () => { showModal.value = false; toast.success('Category updated'); }
        });
    } else {
        router.post(route('admin.expense-categories.store'), form, {
            onSuccess: () => { showModal.value = false; toast.success('Category created'); }
        });
    }
};

const deleteCategory = (cat) => {
    if (confirm(`Delete ${cat.name}?`)) {
        router.delete(route('admin.expense-categories.destroy', cat.id), {
            onSuccess: () => toast.success('Category deleted')
        });
    }
};
</script>
