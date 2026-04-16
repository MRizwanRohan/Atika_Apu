<script setup>
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, 
    ChevronDown, 
    ChevronUp, 
    Package, 
    TrendingUp, 
    Activity,
    Layers,
    ArrowLeft,
    ArrowRight,
    Filter,
    Boxes,
    BarChart3
} from 'lucide-vue-next';

const props = defineProps({
  products: Object,
  filters: Object,
});

// Search & Filtering
const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || 10);
const currentFilter = ref(props.filters.filter || 'all');
const isLoading = ref(false);

const updateData = debounce(() => {
    isLoading.value = true;
    router.get(route('admin.inventory.getAllProductsStock'), { 
        search: search.value,
        per_page: perPage.value,
        filter: currentFilter.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    });
}, 400);

watch([search, perPage, currentFilter], () => {
    updateData();
});

// Expand/collapse product variations
const expandedProducts = ref({});

const toggleExpand = (productId) => {
  expandedProducts.value[productId] = !expandedProducts.value[productId];
};

// ... existing helper functions (omitted for brevity in replacement but kept in file) ...
const getPercentage = (current, total) => {
  if (!total || total === 0) return 0;
  return Math.min(Math.round((current / total) * 100), 100);
};

const getStatusColor = (ratio) => {
    if (ratio >= 80) return 'bg-emerald-500';
    if (ratio >= 40) return 'bg-amber-400';
    return 'bg-rose-500';
};

const getStatusBorder = (ratio) => {
    if (ratio >= 80) return 'border-emerald-100 bg-emerald-50/50 text-emerald-700';
    if (ratio >= 40) return 'border-amber-100 bg-amber-50/50 text-amber-700';
    return 'border-rose-100 bg-rose-50/50 text-rose-700';
};
</script>

<template>
  <Head title="Inventory | Enterprise Dashboard" />
  <AdminLayout>
    <div class="inventory-enterprise min-h-screen bg-[#FBFBFC] text-[#1A1C20] font-sans selection:bg-blue-100 uppercase-none">
      <!-- Top Navigation / Search Bar -->
      <div class="sticky top-0 z-10 bg-white/80 backdrop-blur-md border-b border-gray-100 px-8 py-4 mb-8">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-center gap-3">
              <div class="p-2.5 bg-gray-900 text-white rounded-xl shadow-lg shadow-gray-200">
                  <Boxes :size="20" />
              </div>
              <div>
                <h1 class="text-xl font-semibold tracking-tight">Inventory Engine</h1>
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none">Global Asset Control</p>
              </div>
          </div>
          
          <div class="flex items-center gap-4">
              <div class="relative group">
                  <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-600 transition-colors" :size="16" />
                  <input
                      v-model="search"
                      type="text"
                      placeholder="Find assets by SKU, Name, or ID..."
                      class="pl-10 pr-12 py-2.5 bg-gray-50 border border-transparent rounded-xl w-full md:w-[360px] outline-none focus:bg-white focus:border-gray-200 focus:ring-4 focus:ring-blue-500/5 transition-all text-sm font-medium placeholder:text-gray-400"
                  />
                  <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1.5 opacity-40">
                      <kbd class="border border-gray-300 px-1.5 py-0.5 rounded text-[10px] font-medium">⌘</kbd>
                      <kbd class="border border-gray-300 px-1.5 py-0.5 rounded text-[10px] font-medium">K</kbd>
                  </div>
              </div>
              
              <div class="h-8 w-px bg-gray-100 mx-2"></div>
              
              <button class="p-2.5 text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-colors">
                  <Filter :size="18" />
              </button>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-8">
          <!-- Hero Section -->
          <!-- ...hero section content kept the same... -->
          <div class="mb-12">
              <div class="flex items-end justify-between mb-8">
                  <div class="space-y-1">
                      <p class="text-[10px] font-semibold text-blue-600 uppercase tracking-[0.2em]">Operational Overview</p>
                      <h2 class="text-4xl font-semibold tracking-tight">Stock Analytics</h2>
                  </div>
                  <div class="flex items-center gap-2">
                      <span class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-medium border border-emerald-100">
                          <Activity :size="12" />
                          Live Status: Optimised
                      </span>
                  </div>
              </div>

              <!-- Metrics Grid -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="group bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-100 transition-all cursor-default">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <Package :size="18" />
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Indexed Assets</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight">{{ products.total }}</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-1">+2.4% from last week</p>
                    </div>
                </div>
                
                <div class="group bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-100 transition-all cursor-default">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-amber-50 text-amber-600 rounded-lg group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <Activity :size="18" />
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Velocity Score</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight">84.2</h3>
                        <p class="text-[11px] text-gray-400 font-medium mt-1">Efficiency: <span class="text-blue-600">High</span></p>
                    </div>
                </div>

                <div class="group bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-100 transition-all cursor-default">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-purple-50 text-purple-600 rounded-lg group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <BarChart3 :size="18" />
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Market Reach</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight">Omni</h3>
                        <div class="flex gap-1 mt-1.5">
                            <div v-for="i in 5" :key="i" class="w-4 h-1 rounded-full bg-purple-600" :style="`opacity: ${0.2 * i}`"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800 shadow-xl flex flex-col justify-between">
                    <span class="text-[10px] font-medium text-gray-500 uppercase tracking-[0.2em] mb-4">Pro Insight</span>
                    <p class="text-gray-300 text-xs font-normal leading-relaxed">
                        Total stock value has increased by <span class="text-white font-semibold">12%</span> with new arrivals.
                    </p>
                    <button class="mt-4 text-[10px] font-semibold text-white uppercase tracking-widest hover:text-blue-400 transition-colors flex items-center gap-1">
                        View Report <ArrowRight :size="10" />
                    </button>
                </div>
              </div>
          </div>

          <!-- Assets Table Layout -->
          <div class="mb-12">
              <div class="flex items-center justify-between mb-6 px-2 text-none-uppercase">
                  <div class="flex items-center gap-6">
                      <button 
                        @click="currentFilter = 'all'"
                        class="text-sm font-semibold pb-1.5 transition-all"
                        :class="currentFilter === 'all' ? 'border-b-2 border-gray-900 text-gray-900' : 'text-gray-400 hover:text-gray-900'"
                      >
                        All Assets
                      </button>
                      <button 
                        @click="currentFilter = 'low_stock'"
                        class="text-sm font-semibold pb-1.5 transition-all"
                        :class="currentFilter === 'low_stock' ? 'border-b-2 border-gray-900 text-gray-900' : 'text-gray-400 hover:text-gray-900'"
                      >
                        Low Stock
                      </button>
                  </div>
                  
                  <div class="flex items-center gap-3">
                      <select v-model="perPage" class="bg-transparent text-xs font-medium text-gray-500 outline-none cursor-pointer hover:text-gray-900 transition-colors">
                          <option :value="10">Display 10 items</option>
                          <option :value="25">Display 25 items</option>
                          <option :value="50">Display 50 items</option>
                      </select>
                  </div>
              </div>

              <!-- Entity Container -->
              <div class="space-y-4">
                  <div v-if="products.data.length === 0" class="bg-white rounded-3xl border border-dashed border-gray-200 p-20 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                        <Package class="text-gray-300" :size="24" />
                    </div>
                    <h3 class="text-lg font-semibold">No assets found</h3>
                    <p class="text-gray-400 text-sm max-w-[240px] mx-auto">Try refining your search or adding new products to the engine.</p>
                  </div>

                  <!-- Product Entity Block -->
                  <div v-for="product in products.data" :key="product.product_id" 
                       class="group bg-white border border-gray-100 rounded-[24px] overflow-hidden transition-all duration-300 hover:shadow-2xl hover:shadow-gray-200/50 hover:border-gray-200">
                      
                      <div class="p-6 relative">
                          <div class="flex flex-col lg:flex-row gap-8">
                             <!-- Asset Presentation -->
                             <div class="w-full lg:w-[120px] aspect-square flex-shrink-0 bg-gray-50 rounded-2xl overflow-hidden border border-gray-50 p-2">
                                 <img v-if="product.product_image" :src="product.product_image" class="w-full h-full object-contain mix-blend-multiply" />
                                 <div v-else class="w-full h-full flex items-center justify-center text-gray-200">
                                     <Package :size="32" />
                                 </div>
                             </div>

                             <!-- Analytical Profile -->
                             <div class="flex-1 min-w-0">
                                 <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">
                                     <div>
                                         <div class="flex items-center gap-2 mb-2">
                                             <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded font-medium text-[9px] uppercase tracking-wider border border-blue-100">
                                                 {{ product.category }}
                                             </span>
                                             <span class="text-[10px] font-medium text-gray-400">#{{ product.product_id }}</span>
                                         </div>
                                         <h2 class="text-xl font-semibold tracking-tight text-gray-900 group-hover:text-blue-600 transition-colors truncate">
                                             {{ product.product_name }}
                                         </h2>
                                     </div>

                                     <div v-if="product.variations && product.variations.length > 0" class="flex items-center gap-2">
                                         <button @click="toggleExpand(product.product_id)" 
                                                 class="flex items-center gap-2 px-5 py-2 bg-gray-900 text-white rounded-xl text-[11px] font-medium uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg shadow-gray-200">
                                             <component :size="14" :is="expandedProducts[product.product_id] ? ChevronUp : ChevronDown" />
                                             {{ expandedProducts[product.product_id] ? 'Hide' : 'Variations' }} ({{ product.variations.length }})
                                         </button>
                                     </div>
                                 </div>

                                 <!-- Triple Analytics Grid -->
                                 <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                     <!-- Metric 1: Initial Stock -->
                                     <div class="space-y-2">
                                         <div class="flex justify-between items-end">
                                             <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest leading-none">Initial Stock</p>
                                             <div class="flex items-baseline gap-1">
                                                 <span class="text-lg font-semibold leading-none tabular-nums">{{ product.initial_stock }}</span>
                                                 <span class="text-[9px] font-medium text-gray-400">UNITS</span>
                                             </div>
                                         </div>
                                         <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                                             <div class="h-full bg-slate-300 rounded-full transition-all duration-1000" 
                                                  style="width: 100%"
                                             ></div>
                                         </div>
                                         <p class="text-[9px] font-medium text-gray-400 text-right">Base Allocation</p>
                                     </div>

                                     <!-- Metric 2: Total Sold -->
                                     <div class="space-y-2">
                                         <div class="flex justify-between items-end">
                                             <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest leading-none">Total Sold</p>
                                             <div class="flex items-baseline gap-1">
                                                 <span class="text-lg font-semibold leading-none tabular-nums">{{ product.total_sold }}</span>
                                                 <span class="text-[9px] font-medium text-gray-400">SOLD</span>
                                             </div>
                                         </div>
                                         <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" 
                                                 :style="`width: ${getPercentage(product.total_sold, product.initial_stock)}%`"
                                            ></div>
                                         </div>
                                         <p class="text-[9px] font-medium text-gray-400 text-right">{{ getPercentage(product.total_sold, product.initial_stock) }}% Performance</p>
                                     </div>

                                     <!-- Metric 3: Total Stock -->
                                     <div class="space-y-2">
                                         <div class="flex justify-between items-end">
                                             <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest leading-none">Total Stock</p>
                                             <div class="flex items-baseline gap-1">
                                                 <span class="text-lg font-semibold leading-none tabular-nums">{{ product.total_stock }}</span>
                                                 <span class="text-[9px] font-medium text-gray-400">STOCK</span>
                                             </div>
                                         </div>
                                         <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full transition-all duration-1000" 
                                                 :class="getStatusColor(getPercentage(product.total_stock, product.initial_stock))" 
                                                 :style="`width: ${getPercentage(product.total_stock, product.initial_stock)}%`"
                                            ></div>
                                         </div>
                                         <p class="text-[9px] font-medium text-gray-400 text-right">{{ getPercentage(product.total_stock, product.initial_stock) }}% Remaining</p>
                                     </div>
                                 </div>
                             </div>
                          </div>
                      </div>

                      <!-- Sub-Entity View (Variations) -->
                      <transition name="expand">
                          <div v-if="expandedProducts[product.product_id]" class="bg-[#F9FAFB] border-t border-gray-100 p-8 shadow-inner shadow-gray-100/50">
                             <div class="max-w-5xl mx-auto">
                                <div class="flex items-center gap-3 mb-6">
                                    <h3 class="text-xs font-medium uppercase tracking-[0.3em] text-gray-900">Variation Manifest</h3>
                                    <div class="h-px flex-1 bg-gray-200"></div>
                                </div>

                                <div class="bg-white rounded-[20px] border border-gray-100 overflow-hidden shadow-sm">
                                    <table class="w-full text-[13px] border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50/50 text-gray-400 font-medium uppercase text-[9px] tracking-widest border-b border-gray-100">
                                                <th class="px-6 py-4 text-left">Internal ID</th>
                                                <th class="px-6 py-4 text-left">Configuration</th>
                                                <th class="px-6 py-4 text-left">Unit Value</th>
                                                <th class="px-6 py-4 text-center">Net Core</th>
                                                <th class="px-6 py-4 text-center">Outbound</th>
                                                <th class="px-6 py-4 text-right">Stock Health</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <tr v-for="v in product.variations" :key="v.variation_id" class="hover:bg-blue-50/50 transition-colors cursor-pointer group/row">
                                                <td class="px-6 py-4 font-medium text-gray-400 text-xs">#{{ v.variation_id }}</td>
                                                <td class="px-6 py-4">
                                                    <div class="flex gap-1">
                                                        <span v-for="(val, k) in v.attributes" :key="k" class="bg-gray-100 px-1.5 py-0.5 rounded text-[10px] font-medium text-gray-500 uppercase tracking-tight">
                                                            {{ k }}: {{ val }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 font-semibold text-gray-900 tabular-nums">BDT {{ v.price }}</td>
                                                <td class="px-6 py-4 text-center">
                                                    <span class="px-2 py-0.5 rounded-lg text-[11px] font-medium" 
                                                          :class="v.current_stock > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                                                        {{ v.current_stock }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-center text-gray-500 font-medium tabular-nums">{{ v.sold_stock }}</td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex flex-col items-end gap-1">
                                                        <div class="w-20 bg-gray-100 rounded-full h-1 overflow-hidden">
                                                            <div class="h-full transition-all duration-700" 
                                                                 :class="getStatusColor(getPercentage(v.current_stock, v.initial_stock))" 
                                                                 :style="`width: ${getPercentage(v.current_stock, v.initial_stock)}%`"
                                                            ></div>
                                                        </div>
                                                        <span class="text-[9px] font-medium text-gray-300">{{ getPercentage(v.current_stock, v.initial_stock) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                             </div>
                          </div>
                      </transition>
                  </div>
              </div>
          </div>

          <!-- Enterprise Pagination Controller -->
          <div v-if="products.last_page > 1" class="py-12 flex flex-col items-center gap-8 border-t border-gray-100">
              <div class="flex items-center gap-2">
                  <button 
                  @click="router.get(products.prev_page_url, { search, per_page: perPage }, { preserveState: true, preserveScroll: true })" 
                  :disabled="!products.prev_page_url"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-medium uppercase tracking-widest text-[#1A1C20] hover:bg-gray-50 transition-all disabled:opacity-30 disabled:hover:bg-white shadow-sm"
                  >
                  <ArrowLeft :size="14" /> Previous
                  </button>

                  <div class="flex items-center gap-2 px-6">
                      <template v-for="link in products.links">
                          <button 
                            v-if="!isNaN(link.label)"
                            :key="link.label"
                            @click="router.get(link.url, { search, per_page: perPage }, { preserveState: true, preserveScroll: true })"
                            class="w-10 h-10 rounded-xl text-xs font-medium transition-all border tabular-nums"
                            :class="link.active 
                                ? 'bg-gray-900 text-white border-gray-900 shadow-xl shadow-gray-200' 
                                : 'bg-white border-gray-200 text-gray-400 hover:border-gray-900 hover:text-gray-900'"
                          >
                            {{ link.label }}
                          </button>
                      </template>
                  </div>

                  <button 
                  @click="router.get(products.next_page_url, { search, per_page: perPage }, { preserveState: true, preserveScroll: true })" 
                  :disabled="!products.next_page_url"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-medium uppercase tracking-widest text-[#1A1C20] hover:bg-gray-50 transition-all disabled:opacity-30 disabled:hover:border-gray-200 shadow-sm"
                  >
                  Next <ArrowRight :size="14" />
                  </button>
              </div>

              <div class="flex items-center gap-4 text-[10px] font-medium text-gray-400 uppercase tracking-widest">
                  <span>Showing page {{ products.current_page }} of {{ products.last_page }}</span>
                  <div class="w-1 h-1 bg-gray-300 rounded-full"></div>
                  <span>Cluster: Global-Region-1</span>
              </div>
          </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.inventory-enterprise {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    -webkit-font-smoothing: antialiased;
}

/* Linear-style Expand Transition */
.expand-enter-active, .expand-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    max-height: 2000px;
    opacity: 1;
}
.expand-enter-from, .expand-leave-to {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
}

/* Enterprise Custom Scrollbar */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}
.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 4px;
}
.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* Selection Highlight */
::selection {
    background: #3B82F620;
    color: #1a1c20;
}
</style>
