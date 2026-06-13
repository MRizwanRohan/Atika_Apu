<template>
  <AdminLayout>
    <div class="p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-xl sm:text-2xl font-semibold">Role Management</h1>
        <button 
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          Create New Role
        </button>
      </div>

      <!-- Roles Table -->
      <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 sm:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Role Name</th>
              <th class="px-4 py-3 sm:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Permissions</th>
              <th class="px-4 py-3 sm:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="role in roles" :key="role.id">
              <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">{{ role.name }}</td>
              <td class="px-4 py-3 sm:px-6 sm:py-4 min-w-[200px]">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="permission in role.permissions" 
                    :key="permission.id"
                    class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs whitespace-nowrap"
                    :title="permission.name"
                  >
                    {{ formatPermissionName(permission.name) }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">
                <button 
                  v-if="role.name !== 'admin'"
                  @click="openEditModal(role)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  Edit
                </button>
                <button 
                  v-if="role.name !== 'admin'"
                  @click="deleteRole(role)"
                  class="text-red-600 hover:text-red-900"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create/Edit Modal -->
      <Modal :show="showModal" @close="closeModal">
        <div class="p-4 sm:p-6">
          <h2 class="text-lg font-medium mb-4">{{ isEditing ? 'Edit Role' : 'Create New Role' }}</h2>
          <form @submit.prevent="submitForm">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Role Name</label>
              <input 
                type="text" 
                v-model="form.name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :disabled="form.name === 'admin'"
              >
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div v-for="permission in permissions" :key="permission.id" class="flex items-center" :title="permission.name">
                  <input 
                    type="checkbox" 
                    :id="'perm_' + permission.id"
                    :value="permission.name"
                    v-model="form.permissions"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                  >
                  <label :for="'perm_' + permission.id" class="ml-2 text-sm text-gray-700 cursor-pointer">{{ formatPermissionName(permission.name) }}</label>
                </div>
              </div>
            </div>

            <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
              <button 
                type="button"
                @click="closeModal"
                class="w-full sm:w-auto bg-gray-50 px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100 flex justify-center items-center"
              >
                Cancel
              </button>
              <button 
                type="submit"
                class="w-full sm:w-auto bg-blue-600 px-4 py-2 rounded-md text-white hover:bg-blue-700 flex justify-center items-center"
                :disabled="form.processing"
              >
                {{ isEditing ? 'Update' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </Modal>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  roles: Array,
  permissions: Array
})

const formatPermissionName = (name) => {
  const mapping = {
    'admin.dashboard.index': 'Dashboard View',
    'admin.orders.index': 'Orders Management',
    'admin.pos.index': 'POS (Point of Sale)',
    'admin.facebook-entries.index': 'Facebook Entry Orders',
    'admin.products.index': 'Products View/Edit',
    'admin.products.create': 'Add/Create Products',
    'admin.attributes.index': 'Manage Product Attributes',
    'admin.categories.index': 'Manage Categories',
    'admin.brands.index': 'Manage Brands',
    'admin.inventory.getAllProductsStock': 'View Inventory/Stock',
    'admin.reports.generateReport': 'Generate Business Reports',
    'admin.employee.index': 'Manage Employees',
    'admin.employee.salary.index': 'Manage Employee Salaries',
    'admin.team-member.index': 'Manage Team Members',
    'admin.business-dashboard.index': 'View Business Dashboard',
    'admin.expenses.index': 'Manage Expenses',
    'admin.product-purchase-costs.index': 'Manage Purchase Costs',
    'admin.transaction-history.index': 'View Transaction History',
    'admin.users.index': 'Manage Users',
    'admin.roles.index': 'Manage Roles & Permissions',
    'manage_user_roles': 'Assign Roles/Permissions',
    'admin.coupons.index': 'Manage Coupons',
    'admin.campaigns.index': 'Manage Campaigns/Offers',
    'admin.corporate-clients.index': 'Manage Corporate Clients',
    'admin.landing-pages.index': 'Manage Landing Pages',
    'admin.site-pages.index': 'Manage Dynamic Site Pages',
    'admin.blogs.index': 'Manage Blogs',
    'admin.reviews.index': 'Manage Product Reviews',
    'admin.courier.settings.index': 'Manage Courier & Delivery Settings',
    'admin.courier.dashboard': 'View Courier Dashboard',
    'admin.settings.basicInformation': 'Manage Site Basic Information',
    'admin.media.index': 'Media Library Access',
    'admin.sliders.index': 'Manage Main Sliders',
    'admin.promotional-sliders.index': 'Manage Promo Sliders',
    'admin.settings.socialLinks': 'Manage Social Media Links',
    'admin.marketing-tools.index': 'Manage Marketing Tools',
    'admin.leads.index': 'Manage Product Requests/Leads',
  };

  if (mapping[name]) {
    return mapping[name];
  }

  // Fallback: convert dots/hyphens to capitalized words
  return name
    .replace('admin.', '')
    .replace('.index', '')
    .replace(/[\._-]/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase());
};

const showModal = ref(false)
const isEditing = ref(false)
const editingRole = ref(null)

const form = reactive({
  name: '',
  permissions: [],
  processing: false
})

const openCreateModal = () => {
  isEditing.value = false
  form.name = ''
  form.permissions = []
  showModal.value = true
}

const openEditModal = (role) => {
  isEditing.value = true
  editingRole.value = role
  form.name = role.name
  form.permissions = role.permissions.map(p => p.name)
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  form.name = ''
  form.permissions = []
  editingRole.value = null
}

const submitForm = () => {
  form.processing = true;
  
  if (isEditing.value) {
    router.put(`/admin/roles/${editingRole.value.id}`, form, {
      onSuccess: () => {
        closeModal();
        router.reload(); // Force page reload to refresh the table
        form.processing = false;
      },
      onError: () => {
        form.processing = false;
      }
    });
  } else {
    router.post('/admin/roles', form, {
      onSuccess: () => {
        closeModal();
        router.reload(); // Force page reload to refresh the table
        form.processing = false;
      },
      onError: () => {
        form.processing = false;
      }
    });
  }
}

const deleteRole = (role) => {
  if (confirm('Are you sure you want to delete this role?')) {
    router.delete(`/admin/roles/${role.id}`, {
      onSuccess: () => {
        router.reload(); // Force page reload to refresh the table
      }
    });
  }
}
</script>