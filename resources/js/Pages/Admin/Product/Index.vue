
<template>
  <Head title="Products" />
  <AdminLayout>
    <div class="w-full mx-auto py-10">
      <div class="flex justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Products</h1>
        <div class="flex gap-2">
          <div v-if="selectedProductIds.length > 0" class="flex items-center gap-2">
            <input
              v-model="confirmationInput"
              type="text"
              placeholder="Type 'confirm' to delete"
              class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            />
            <button
              @click="deleteSelectedProducts"
              :disabled="confirmationInput !== 'confirm' || isDeleting"
              class="btn btn-error"
              :class="{ 'opacity-50 cursor-not-allowed': confirmationInput !== 'confirm' || isDeleting }"
            >
              {{ isDeleting ? 'Deleting...' : `Delete Selected (${selectedProductIds.length})` }}
            </button>
          </div>
          <Link :href="route('admin.products.create')" class="btn btn-primary">
            Add Product
          </Link>
        </div>
      </div>

      <!-- Table Controls -->
      <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <div class="flex items-center">
          <label for="perPage" class="mr-2 text-sm font-medium text-gray-600 dark:text-gray-300">Show:</label>
          <select id="perPage" v-model="perPage"
            class="rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm px-2 w-[60px] py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            @change="handlePerPageChange">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>

        <div class="relative w-full md:w-64">
          <input type="text" v-model="searchQuery" placeholder="Search products..."
            class="w-full pl-10 pr-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            @input="debouncedSearch" />
          <div class="absolute left-3 top-2.5 text-gray-400 dark:text-gray-500">
            <Search size="18" />
          </div>
        </div>
      </div>

      <!-- Main Table -->
      <div class="main-table">
        <table>
          <thead>
            <tr>
              <th class="balk-select-check">
                <input type="checkbox"
                  class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                  :checked="selectedProductIds.length === products.data.length && products.data.length > 0"
                  @change="toggleSelectAll" />
              </th>
              <th>#</th>
              <th>Image</th>
              <th>Name</th>
              <th>Price</th>
              <th>In Stock</th>
              <th>Free Shipping</th>
              <th>Status</th>
              <th>Updated</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Skeleton Loader -->
            <template v-if="loading">
              <tr v-for="i in perPage" :key="`skeleton-${i}`" class="animate-pulse">
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-10 w-10 bg-gray-200 dark:bg-gray-600 rounded-md"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-32 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-10 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
              </tr>
            </template>

            <!-- Actual Data -->
            <template v-else>
              <template v-for="(item, index) in products.data" :key="item.id">
                <!-- Main Row -->
                <tr>
                  <td class="px-3 py-4 whitespace-nowrap">
                    <input type="checkbox"
                      class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                      :checked="isSelected(item.id)" @change="toggleSelection(item.id)" />
                  </td>
                  <td>{{ index + 1 }}</td>
                  <td class="px-3 py-4 whitespace-nowrap">
                    <img class="w-15 rounded-md object-cover bg-gray-100 dark:bg-gray-600"
                      :src="item.feature_image" alt="main image" />
                  </td>
                  <td>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 w-[160px] whitespace-normal">
                      {{ item.name }}
                    </h3>
                  </td>
                  <td>৳{{ item.price }}</td>
                  <td>
                    <span
                      class="px-2 py-1 dark:text-gray-100"
                      :class="{
                        'bg-blue-100 text-blue-500 dark:bg-blue-700': item.stock > 0,
                        'bg-primary/10 text-primary dark:bg-primary/80': item.is_pre_order && item.stock === 0,
                        'bg-red-100 text-red-500 dark:bg-red-700': item.stock === 0 && !item.is_pre_order
                      }"
                    >
                      {{ item.stock > 0 ? 'In Stock' : (item.is_pre_order ? 'Pre Order' : 'Out of Stock') }}{{ item.stock > 0 ? ` (${item.stock})` : '' }}
                    </span>
                  </td>
                  <td>
                    <span
                      class="px-2 py-1 text-gray-900 dark:text-gray-100"
                      :class="{
                        'bg-green-100 dark:bg-green-700': item.is_free_delivery,
                        'bg-red-100 dark:bg-red-700': !item.is_free_delivery
                      }"
                    >
                      {{ item.is_free_delivery ? 'Yes' : 'No' }}
                    </span>
                  </td>
                  <td>
                    <span
                      class="px-2 py-1 text-gray-900 dark:text-gray-100"
                      :class="{
                        'bg-green-100 dark:bg-green-700': item.status === 'Published',
                        'bg-red-100 dark:bg-red-700': item.status === 'Unpublished'
                      }"
                    >
                      {{ item.status }}
                    </span>
                  </td>
                  <td>{{ formatDate(item.updated_at) }}</td>
                  <td class="actions">
                    <div class="flex justify-end space-x-2 relative">
                      <!-- Quick Edit Button -->
                      <div class="relative group">
                        <button @click="openQuickEditModal(item)"
                          class="table_quick_edit_action hover:bg-blue-500 hover:text-white p-2 rounded">
                          <PencilIcon size="20" />
                        </button>
                        <span
                          class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                          Quick Edit
                        </span>
                      </div>

                      <!-- Delete Button -->
                      <div class="relative group">
                        <button @click="openDeleteModal(item.id)"
                          class="table_delete_action hover:bg-red-500 hover:text-white p-2 rounded">
                          <Trash2Icon size="20" />
                        </button>
                        <span
                          class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                          Delete
                        </span>
                      </div>

                      <!-- Edit Link -->
                      <div class="relative group">
                        <Link :href="route('admin.products.edit', item.id)"
                          class="table_edit_action hover:bg-green-500 hover:text-white p-2 rounded">
                          <SquarePen size="20" />
                        </Link>
                        <span
                          class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                          Edit
                        </span>
                      </div>

                      <!-- Expand/Collapse Button -->
                      <div class="relative group">
                        <button @click="toggleExpanded(item.id)"
                          class="table_expand_action hover:bg-gray-500 hover:text-white p-2 rounded">
                          <ChevronDown v-if="expandedRows[item.id]" size="20" />
                          <ChevronRight v-else size="20" />
                        </button>
                        <span
                          class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                          View
                        </span>
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- Expanded Row for Details -->
                <tr v-if="expandedRows[item.id]">
                  <td colspan="11" class="px-0 py-0 border-t-0">
                    <div class="bg-gray-50 dark:bg-gray-900 p-6">
                      <div class="flex flex-col md:flex-row gap-6">
                        <div class="md:w-1/5">
                          <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                            <img :src="item.feature_image" alt="" class="w-full h-auto object-contain" />
                          </div>
                        </div>
                        <div class="md:w-3/4 grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-4">
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Model Name</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Brand</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.brand?.brand_name || 'N/A' }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Price</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">৳{{ item.price }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Created At</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(item.created_at) }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">ID</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.id }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Stock</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.stock }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.status }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Type</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.type }}</div>
                          </div>
                          <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Variations</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.variations?.length || 0 }}</div>
                          </div>

                          <!-- Product Variations -->
                          <div v-if="item.variations && item.variations.length > 0" class="col-span-2 md:col-span-3 mt-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Product Variations</div>
                            <div class="overflow-x-auto">
                              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                  <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Image</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Attributes</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Price</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Stock</th>
                                  </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                  <tr v-for="variation in item.variations" :key="variation.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                      <img v-if="variation.image_path" class="h-8 w-8 rounded-md object-cover bg-gray-100 dark:bg-gray-600"
                                        :src="variation.image_path" alt="" />
                                    </td>
                                    <td class="px-3 py-2">
                                      <div class="flex flex-wrap gap-1">
                                        <span v-for="attr in variation.attributes" :key="attr.id"
                                          class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                          {{ attr.value?.attribute?.name }}: {{ attr.value?.value }}
                                        </span>
                                      </div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">৳{{ variation.price }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ variation.stock }}</td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-if="products.data.length === 0 && !loading">
                <td colspan="11" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                  <div class="flex flex-col items-center">
                    <Package size="48" class="text-gray-300 dark:text-gray-600 mb-3" />
                    <p>No products found</p>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Laravel Pagination -->
      <div class="flex items-center justify-end mt-4">
        <div class="flex space-x-1">
          <button @click="goToPage(1)" :disabled="currentPage === 1 || loading" class="pagination-button">«</button>
          <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1 || loading" class="pagination-button">‹</button>
          <template v-if="lastPage <= 7">
            <button v-for="page in lastPage" :key="page" @click="goToPage(page)" :disabled="loading" class="pagination-button" :class="{ 'active': currentPage === page }">
              {{ page }}
            </button>
          </template>
          <template v-else>
            <button v-if="currentPage > 3" @click="goToPage(1)" class="pagination-button">1</button>
            <span v-if="currentPage > 4" class="pagination-ellipsis">...</span>
            <template v-for="page in lastPage" :key="page">
              <button v-if="page >= currentPage - 1 && page <= currentPage + 1" @click="goToPage(page)" class="pagination-button" :class="{ 'active': currentPage === page }">
                {{ page }}
              </button>
            </template>
            <span v-if="currentPage < lastPage - 3" class="pagination-ellipsis">...</span>
            <button v-if="currentPage < lastPage - 2" @click="goToPage(lastPage)" class="pagination-button">{{ lastPage }}</button>
          </template>
          <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage || loading" class="pagination-button">›</button>
          <button @click="goToPage(lastPage)" :disabled="currentPage === lastPage || loading" class="pagination-button">»</button>
        </div>
      </div>

      <!-- Delete Confirmation Modal using Reusable Component -->
      <DeleteModal
        :item-id="productToDelete"
        item-name="product"
        route-name="admin.products.destroy"
        v-model:visible="showDeleteModal"
        @deleted="handleDeleteSuccess"
      />

      <!-- Quick Edit Modal -->
      <div v-if="showQuickEditModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Quick Edit Product</h3>

          <form @submit.prevent="submitQuickEdit" class="space-y-4">
            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
              <select v-model="quickEditForm.status"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option value="Published">Published</option>
                <option value="Unpublished">Unpublished</option>
              </select>
            </div>

            <!-- Free Delivery -->
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" v-model="quickEditForm.is_free_delivery"
                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
              <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Free Delivery</span>
            </label>

            <!-- Remarks -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
              <select v-model="quickEditForm.remarks"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option value="">None</option>
                <option value="New">New</option>
                <option value="Popular">Popular</option>
                <option value="Trending">Trending</option>
                <option value="Hot">Flash Sale</option>
                <option value="Special">Special</option>
              </select>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4">
              <button type="button" @click="closeQuickEditModal"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                Cancel
              </button>
              <button type="submit"
                class="px-4 py-2 btn-primary text-white rounded-md hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-800">
                Update
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { toast } from "@steveyuowo/vue-hot-toast";
import DeleteModal from '@/Components/Modal/DeleteModal.vue';
import {
  ChevronDown,
  ChevronRight,
  Search,
  Package,
  ChevronLeft,
  ChevronLast,
  ChevronFirst,
  Trash2Icon,
  SquarePen,
  PencilIcon
} from 'lucide-vue-next';

const props = defineProps({
  products: Object
});

// State for search, filters, and bulk delete
const searchQuery = ref('');
const perPage = ref(10);
const selectedProductIds = ref([]);
const expandedRows = ref({});
const loading = ref(false);
const showDeleteModal = ref(false);
const productToDelete = ref(null);
const showQuickEditModal = ref(false);
const productToEdit = ref(null);
const quickEditForm = ref({
  status: '',
  is_free_delivery: false,
  remarks: ''
});
const confirmationInput = ref('');
const isDeleting = ref(false);

// Computed properties for pagination
const paginationData = computed(() => props.products);
const currentPage = computed(() => props.products.current_page);
const lastPage = computed(() => props.products.last_page);
const from = computed(() => props.products.from || 0);
const to = computed(() => props.products.to || 0);
const total = computed(() => props.products.total || 0);
const links = computed(() => props.products.links || []);

// Methods
const openDeleteModal = (id) => {
  productToDelete.value = id;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  productToDelete.value = null;
};

const openQuickEditModal = (product) => {
  productToEdit.value = product;
  quickEditForm.value = {
    status: product.status,
    is_free_delivery: product.is_free_delivery,
    remarks: product.remarks || ''
  };
  showQuickEditModal.value = true;
};

const closeQuickEditModal = () => {
  showQuickEditModal.value = false;
  productToEdit.value = null;
  quickEditForm.value = {
    status: '',
    is_free_delivery: false,
    remarks: ''
  };
};

const submitQuickEdit = () => {
  if (!productToEdit.value) return;

  router.post(
    route('admin.products.quick-edit', productToEdit.value.id),
    quickEditForm.value,
    {
      preserveState: true,
      onSuccess: () => {
        toast.success('Product updated successfully!');
        closeQuickEditModal();
      },
      onError: (errors) => {
        toast.error('Failed to update product');
        Object.values(errors).forEach(error => toast.error(error));
      }
    }
  );
};

const handleSearch = () => {
  loading.value = true;
  router.get(
    route('admin.products.index'),
    { search: searchQuery.value, page: 1, per_page: perPage.value },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const handlePerPageChange = (e) => {
  perPage.value = parseInt(e.target.value);
  loading.value = true;
  router.get(
    route('admin.products.index'),
    { search: searchQuery.value, page: 1, per_page: perPage.value },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const goToPage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return;

  loading.value = true;
  router.get(
    route('admin.products.index'),
    { search: searchQuery.value, page: page, per_page: perPage.value },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const toggleExpanded = (id) => {
  expandedRows.value = {
    ...expandedRows.value,
    [id]: !expandedRows.value[id]
  };
};

const toggleSelectAll = () => {
  if (selectedProductIds.value.length === props.products.data.length) {
    selectedProductIds.value = [];
  } else {
    selectedProductIds.value = props.products.data.map(item => item.id);
  }
};

const isSelected = (id) => {
  return selectedProductIds.value.includes(id);
};

const toggleSelection = (id) => {
  const index = selectedProductIds.value.indexOf(id);
  if (index === -1) {
    selectedProductIds.value.push(id);
  } else {
    selectedProductIds.value.splice(index, 1);
  }
};

const deleteSelectedProducts = () => {
  if (selectedProductIds.value.length === 0) {
    toast.error('No products selected');
    return;
  }

  if (confirmationInput.value !== 'confirm') {
    toast.error('Please type "confirm" to delete selected products');
    return;
  }

  isDeleting.value = true;
  router.post(
    route('admin.products.bulk-delete'),
    { product_ids: selectedProductIds.value, confirmation: confirmationInput.value },
    {
      preserveState: true,
      onSuccess: () => {
        toast.success(`${selectedProductIds.value.length} products deleted successfully!`);
        selectedProductIds.value = [];
        confirmationInput.value = '';
        isDeleting.value = false;
      },
      onError: (errors) => {
        toast.error('Failed to delete selected products');
        Object.values(errors).forEach(error => toast.error(error));
        isDeleting.value = false;
      }
    }
  );
};

function formatDate(isoDate) {
  const date = new Date(isoDate);
  const now = new Date();
  const diffInMs = now - date; // Difference in milliseconds

  const seconds = Math.floor(diffInMs / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days > 0) {
    return `${days} day${days === 1 ? '' : 's'} ago`;
  } else if (hours > 0) {
    return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  } else if (minutes > 0) {
    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
  } else {
    return `${seconds} second${seconds === 1 ? '' : 's'} ago`;
  }
}

// Debounce search input
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleSearch();
  }, 500);
};
</script>

<style>
@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 0.8; }
}

.animate-pulse {
  animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.pagination-button {
  @apply px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition;
}

.active {
  @apply bg-green-500 text-white border-green-500 hover:bg-green-600 dark:bg-green-600 dark:border-green-600 dark:hover:bg-green-700;
}

.pagination-ellipsis {
  @apply px-5 py-2 text-sm text-gray-500 dark:text-gray-400;
}
</style>

