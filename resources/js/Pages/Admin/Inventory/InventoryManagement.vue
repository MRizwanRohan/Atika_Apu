<script setup>
import { defineProps, ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  allProductsStock: Array,
});

// Expand/collapse product variations
const expandedProducts = ref({});

const toggleExpand = (productId) => {
  expandedProducts.value[productId] = !expandedProducts.value[productId];
};

// Search functionality
const searchQuery = ref('');
const filteredProducts = computed(() => {
  if (!searchQuery.value.trim()) return props.allProductsStock;
  
  const query = searchQuery.value.toLowerCase().trim();
  return props.allProductsStock.filter(product => 
    product.product_name.toLowerCase().includes(query) || 
    product.product_id.toString().includes(query)
  );
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(5);
const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage.value));

const paginatedProducts = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const endIndex = startIndex + itemsPerPage.value;
  return filteredProducts.value.slice(startIndex, endIndex);
});

const goToPage = (page) => {
  currentPage.value = page;
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

// Reset to first page when search query changes
watch(searchQuery, () => {
  currentPage.value = 1;
});

// Helper functions
const getStockPercentage = (current, initial) => {
  return (current / initial) * 100;
};

const getSoldPercentage = (sold, initial) => {
  return (sold / initial) * 100;
};
</script>

<template>
  <Head title="Inventory Management" />
  <AdminLayout>
    <div class="container mx-auto py-6 px-4">
      <h1 class="text-2xl font-bold mb-6">Product Inventory Dashboard</h1>
      
      <!-- Search Bar -->
      <div class="mb-6">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by product name or ID..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition"
          />
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>
      
      <!-- Legend -->
      <div class="mb-6 flex flex-wrap gap-4">
        <div class="p-4 bg-white rounded-lg shadow flex items-center gap-3">
          <div class="w-4 h-4 rounded-full bg-emerald-500"></div>
          <span>Total Stock</span>
        </div>
        <div class="p-4 bg-white rounded-lg shadow flex items-center gap-3">
          <div class="w-4 h-4 rounded-full bg-rose-500"></div>
          <span>Total Sold</span>
        </div>
        <div class="p-4 bg-white rounded-lg shadow flex items-center gap-3">
          <div class="w-4 h-4 rounded-full bg-blue-500"></div>
          <span>Initial Stock</span>
        </div>
      </div>
      
      <!-- No Results Message -->
      <div v-if="filteredProducts.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center">
        <p class="text-gray-500">No products found matching your search.</p>
      </div>
      
      <!-- Product List -->
      <div v-else class="grid gap-6">
        <div v-for="product in paginatedProducts" :key="product.product_id" class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-6">
            <div class="flex flex-col md:flex-row gap-6">
              <!-- Product Image -->
              <div v-if="product.product_image" class="w-[100px] h-[100px] flex-shrink-0">
                <img 
                  :src="product.product_image" 
                  :alt="product.product_name"
                  class="w-full h-auto object-cover rounded-lg border border-gray-200"
                  
                />
              </div>
              
              <!-- Product Details -->
              <div class="flex-1">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h2 class="text-xl font-semibold">{{ product.product_name }}</h2>
                    <p class="text-gray-500">Product ID: {{ product.product_id }}</p>
                  </div>
                  <button 
                    v-if="product.variations && product.variations.length > 0"
                    @click="toggleExpand(product.product_id)" 
                    class="px-3 py-1 bg-green-100 text-green-500 hover:bg-green-200 rounded-md text-sm transition"
                  >
                    {{ expandedProducts[product.product_id] ? 'Hide' : 'Show' }} Variations ({{ product.variations.length }})
                  </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                  <div class="bg-emerald-50 p-2 rounded-lg border border-emerald-200">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm text-gray-600">Total Stock</span>
                      <span class="text-lg font-bold text-emerald-600">{{ product.total_stock }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                      <div 
                        class="bg-emerald-500 h-2.5 rounded-full" 
                        :style="`width: ${getStockPercentage(product.total_stock, product.initial_stock)}%`"
                      ></div>
                    </div>
                  </div>
                  
                  <div class="bg-rose-50 p-4 rounded-lg border border-rose-200">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm text-gray-600">Total Sold</span>
                      <span class="text-lg font-bold text-rose-600">{{ product.total_sold }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                      <div 
                        class="bg-rose-500 h-2.5 rounded-full" 
                        :style="`width: ${getSoldPercentage(product.total_sold, product.initial_stock)}%`"
                      ></div>
                    </div>
                  </div>
                  
                  <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm text-gray-600">Initial Stock</span>
                      <span class="text-lg font-bold text-blue-600">{{ product.initial_stock }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                      <div 
                        class="bg-blue-500 h-2.5 rounded-full" 
                        :style="`width: 100%`"
                      ></div>
                    </div>
                  </div>
                </div>
                
                <div class="text-sm text-gray-600">
                  <span class="font-medium">Stock Ratio:</span> {{ product.stock_ratio.toFixed(2) }}%
                </div>
              </div>
            </div>
          </div>
          
          <!-- Variations Section -->
          <div v-if="expandedProducts[product.product_id] && product.variations && product.variations.length > 0" class="border-t border-gray-200 bg-gray-50">
            <div class="p-6">
              <h3 class="text-lg font-medium mb-4">Product Variations</h3>
              
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-100">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variation</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attributes</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sold</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Initial Stock</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="variation in product.variations" :key="variation.variation_id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #{{ variation.variation_id }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div v-for="(value, key) in variation.attributes" :key="key" class="flex gap-1 items-center">
                          <span class="font-medium">{{ key }}:</span> 
                          {{ value }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${{ variation.price }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                          {{ variation.current_stock }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">
                          {{ variation.sold_stock }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                          {{ variation.initial_stock }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                          <div 
                            class="bg-emerald-500 h-2 rounded-full" 
                            :style="`width: ${(variation.current_stock / variation.initial_stock) * 100}%`"
                          ></div>
                        </div>
                        <div class="text-xs text-gray-500">
                          {{ ((variation.current_stock / variation.initial_stock) * 100).toFixed(0) }}% in stock
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex justify-center">
        <nav class="flex items-center gap-1">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1"
            class="px-3 py-1 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          
          <button 
            v-for="page in totalPages" 
            :key="page" 
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded-md text-sm font-medium',
              currentPage === page 
                ? 'bg-green-500 text-white' 
                : 'text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
          
          <button 
            @click="nextPage" 
            :disabled="currentPage === totalPages"
            class="px-3 py-1 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
      
      <!-- Results Summary -->
      <div class="mt-4 text-center text-sm text-gray-500">
        Showing {{ paginatedProducts.length }} of {{ filteredProducts.length }} products
      </div>
    </div>
  </AdminLayout>
</template>
