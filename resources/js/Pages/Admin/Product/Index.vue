
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
        <div class="flex items-center gap-4">
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

          <div class="flex items-center">
            <label for="categoryFilter" class="mr-2 text-sm font-medium text-gray-600 dark:text-gray-300">Category:</label>
            <select id="categoryFilter" v-model="selectedCategory"
              class="rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm px-3 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              @change="handleCategoryChange">
              <option value="">All Categories</option>
              <template v-for="category in categories" :key="category.id">
                <option :value="category.id">{{ category.name }}</option>
                <template v-if="category.children && category.children.length > 0">
                  <option v-for="child in category.children" :key="child.id" :value="child.id">
                    &nbsp;&nbsp;&mdash; {{ child.name }}
                  </option>
                </template>
              </template>
            </select>
          </div>
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
              <th>In Stock</th>
              <th>Free Shipping</th>
              <th>Status</th>
              <th>Updated</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          
          <!-- Skeleton Loader -->
          <tbody v-if="loading">
            <tr v-for="i in perPage" :key="`skeleton-${i}`" class="animate-pulse">
              <td class="px-3 py-4 whitespace-nowrap">
                <div class="h-4 w-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
              </td>
              <td class="px-3 py-4 whitespace-nowrap">
                <div class="h-4 w-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
              </td>
              <td class="px-3 py-4 whitespace-nowrap">
                <div class="h-10 w-10 bg-gray-200 dark:bg-gray-600 rounded-md"></div>
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
                <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
              </td>
              <td class="px-3 py-4 whitespace-nowrap text-end">
                <div class="h-4 w-20 bg-gray-200 dark:bg-gray-600 rounded ml-auto"></div>
              </td>
            </tr>
          </tbody>

          <!-- Actual Data -->
          <draggable
            v-else
            v-model="productList"
            tag="tbody"
            item-key="id"
            handle=".drag-handle"
            @end="onDragEnd"
          >
            <template #item="{ element: item, index }">
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                      <GripVertical size="18" />
                    </div>
                    <input type="checkbox"
                      class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                      :checked="isSelected(item.id)" @change="toggleSelection(item.id)" />
                  </div>
                </td>
                <td>{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <img class="w-15 rounded-md object-cover bg-gray-100 dark:bg-gray-600"
                    :src="item.feature_image" alt="main image" />
                </td>
                <td>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100 w-[160px] whitespace-normal">
                    {{ item.name }}
                  </h3>
                </td>
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

                    <!-- Detail View Modal Trigger -->
                    <div class="relative group">
                      <button @click="openDetailModal(item)"
                        class="table_expand_action hover:bg-gray-500 hover:text-white p-2 rounded">
                        <ChevronRight size="20" />
                      </button>
                      <span
                        class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                        View Details
                      </span>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </draggable>

          <!-- Empty State -->
          <tbody v-if="products.data.length === 0 && !loading">
            <tr>
              <td colspan="11" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                <div class="flex flex-col items-center">
                  <Package size="48" class="text-gray-300 dark:text-gray-600 mb-3" />
                  <p>No products found</p>
                </div>
              </td>
            </tr>
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

      <!-- Delete Confirmation Modal -->
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
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
              <select v-model="quickEditForm.status"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option value="Published">Published</option>
                <option value="Unpublished">Unpublished</option>
              </select>
            </div>
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" v-model="quickEditForm.is_free_delivery"
                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
              <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Free Delivery</span>
            </label>
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

      <!-- Detailed View Modal -->
      <div v-if="showDetailModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Product Details</h3>
            <button @click="closeDetailModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
               <ChevronRight class="rotate-180" size="24" />
            </button>
          </div>
          <div class="p-6" v-if="selectedProduct">
            <div class="flex flex-col md:flex-row gap-8">
              <div class="md:w-1/3">
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-700 sticky top-24">
                  <img :src="selectedProduct.feature_image" alt="" class="w-full h-auto object-contain rounded-lg" />
                </div>
              </div>
              <div class="md:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Model Name</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ selectedProduct.name }}</div>
                </div>
                <div>
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Brand</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ selectedProduct.brand?.brand_name || 'N/A' }}</div>
                </div>
                <div>
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Price</div>
                  <div class="text-lg font-bold text-primary">৳{{ selectedProduct.price }}</div>
                </div>
                <div>
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Stock</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ selectedProduct.stock }}</div>
                </div>
                <div>
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">ID</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">#{{ selectedProduct.id }}</div>
                </div>
                <div>
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Type</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100 capitalize">{{ selectedProduct.type }}</div>
                </div>
                
                <div v-if="selectedProduct.variations && selectedProduct.variations.length > 0" class="col-span-1 sm:col-span-2 mt-4">
                  <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Variations</div>
                  <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                      <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                          <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Image</th>
                          <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Details</th>
                          <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Price</th>
                          <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Stock</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="variation in selectedProduct.variations" :key="variation.id">
                          <td class="px-4 py-3">
                            <img v-if="variation.image_path" class="h-10 w-10 rounded shadow-sm object-cover" :src="variation.image_path" />
                          </td>
                          <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                              <span v-for="attr in variation.attributes" :key="attr.id" class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">
                                {{ attr.value?.attribute?.name }}: {{ attr.value?.value }}
                              </span>
                            </div>
                          </td>
                          <td class="px-4 py-3 font-bold text-gray-900 dark:text-gray-100">৳{{ variation.price }}</td>
                          <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ variation.stock }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end">
             <button @click="closeDetailModal" class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 transition">Close</button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, watch } from 'vue';
import { toast } from "@steveyuowo/vue-hot-toast";
import DeleteModal from '@/Components/Modal/DeleteModal.vue';
import draggable from 'vuedraggable';
import {
  ChevronDown,
  ChevronRight,
  Search,
  Package,
  Trash2Icon,
  SquarePen,
  PencilIcon,
  GripVertical
} from 'lucide-vue-next';

const props = defineProps({
  products: Object,
  categories: Array
});

const urlParams = new URLSearchParams(window.location.search);

// State
const searchQuery = ref(urlParams.get('search') || '');
const perPage = ref(parseInt(urlParams.get('per_page')) || 10);
const selectedCategory = ref(urlParams.get('category_id') || '');
const selectedProductIds = ref([]);
const loading = ref(false);
const showDeleteModal = ref(false);
const productToDelete = ref(null);
const showQuickEditModal = ref(false);
const productToEdit = ref(null);
const quickEditForm = ref({ status: '', is_free_delivery: false, remarks: '' });
const confirmationInput = ref('');
const isDeleting = ref(false);

const productList = ref([...props.products.data]);

// Modal State
const showDetailModal = ref(false);
const selectedProduct = ref(null);

watch(() => props.products.data, (newData) => {
  productList.value = [...newData];
}, { deep: true });

// Computed
const currentPage = computed(() => props.products.current_page);
const lastPage = computed(() => props.products.last_page);

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
};

const submitQuickEdit = () => {
  if (!productToEdit.value) return;
  router.post(route('admin.products.quick-edit', productToEdit.value.id), quickEditForm.value, {
    preserveState: true,
    onSuccess: () => {
      toast.success('Product updated!');
      closeQuickEditModal();
    },
    onError: () => toast.error('Update failed')
  });
};

const openDetailModal = (product) => {
  selectedProduct.value = product;
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
};

const handleSearch = () => {
  loading.value = true;
  router.get(route('admin.products.index'), {
    search: searchQuery.value,
    page: 1,
    per_page: perPage.value,
    category_id: selectedCategory.value
  }, {
    preserveState: true,
    onSuccess: () => loading.value = false,
    onError: () => loading.value = false
  });
};

const handleCategoryChange = () => handleSearch();
const handlePerPageChange = () => handleSearch();

const goToPage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return;
  loading.value = true;
  router.get(route('admin.products.index'), {
    search: searchQuery.value,
    page: page,
    per_page: perPage.value,
    category_id: selectedCategory.value
  }, {
    preserveState: true,
    onSuccess: () => loading.value = false
  });
};

const toggleSelectAll = () => {
  if (selectedProductIds.value.length === props.products.data.length) {
    selectedProductIds.value = [];
  } else {
    selectedProductIds.value = props.products.data.map(item => item.id);
  }
};

const isSelected = (id) => selectedProductIds.value.includes(id);

const toggleSelection = (id) => {
  const index = selectedProductIds.value.indexOf(id);
  if (index === -1) selectedProductIds.value.push(id);
  else selectedProductIds.value.splice(index, 1);
};

const deleteSelectedProducts = () => {
  if (confirmationInput.value !== 'confirm') return;
  isDeleting.value = true;
  router.post(route('admin.products.bulk-delete'), {
    product_ids: selectedProductIds.value,
    confirmation: confirmationInput.value
  }, {
    onSuccess: () => {
      toast.success('Deleted!');
      selectedProductIds.value = [];
      confirmationInput.value = '';
      isDeleting.value = false;
    },
    onError: () => isDeleting.value = false
  });
};

const onDragEnd = () => {
  const orders = productList.value.map((p, i) => ({
    id: p.id,
    order: i + 1 + (currentPage.value - 1) * perPage.value
  }));
  router.post(route('admin.products.update-order'), { orders }, {
    preserveState: true,
    onSuccess: () => toast.success('Order saved!')
  });
};

function formatDate(isoDate) {
  const date = new Date(isoDate);
  const diff = Math.floor((new Date() - date) / 1000);
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
}

const searchTimeout = ref(null);
const debouncedSearch = () => {
  clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(handleSearch, 500);
};
</script>

<style>
@keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 0.8; } }
.animate-pulse { animation: pulse 1.5s infinite; }
.pagination-button { @apply px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition; }
.active { @apply bg-green-500 text-white border-green-500; }
.pagination-ellipsis { @apply px-5 py-2 text-sm text-gray-500; }
</style>
