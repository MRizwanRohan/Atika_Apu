<template>
  <Head title="Business Dashboard" />
  <AdminLayout>
    <div class="px-4 pr-0 sm:px-6 lg:px-8 py-8 bg-gray-50/50 min-h-screen">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Business Intel</h1>
          <p class="text-sm text-gray-500 mt-1">Real-time performance metrics and financial analysis.</p>
        </div>
        
        <!-- Quick Filters -->
        <div class="flex items-center gap-3 bg-white p-1.5 rounded-2xl border border-gray-100 shadow-sm">
          <button 
            v-for="t in ['weekly', 'monthly', 'yearly', 'custom']" 
            :key="t"
            @click="filter = t; (t !== 'custom' && applyFilter())"
            :class="[
              'px-4 py-2 text-xs font-medium rounded-xl transition-all',
              filter === t ? 'bg-blue-600 text-white shadow-md shadow-blue-100' : 'text-gray-500 hover:bg-gray-50'
            ]"
          >
            {{ t.charAt(0).toUpperCase() + t.slice(1) }}
          </button>
        </div>
      </div>

      <!-- Custom Date Range Picker (Conditional) -->
      <transition name="fade">
        <div v-if="filter === 'custom'" class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm mb-8 animate-in slide-in-from-top-4 duration-300">
          <div class="flex flex-wrap items-end gap-6">
            <div class="flex-1 min-w-[200px]">
              <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest pl-1 mb-2 block">Start Date</label>
              <div class="relative">
                <Calendar size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                <input type="date" v-model="startDate" class="w-full pl-11 pr-4 py-3 bg-gray-50 border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 transition-all outline-none" />
              </div>
            </div>
            <div class="flex-1 min-w-[200px]">
              <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest pl-1 mb-2 block">End Date</label>
              <div class="relative">
                <Calendar size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                <input type="date" v-model="endDate" class="w-full pl-11 pr-4 py-3 bg-gray-50 border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 transition-all outline-none" />
              </div>
            </div>
            <button @click="applyFilter" class="px-8 py-3 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-black transition-all shadow-lg active:scale-95">
              Refine Results
            </button>
          </div>
        </div>
      </transition>

      <!-- Main Metrics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
        <!-- Revenue -->
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-gray-100 transition-all group">
          <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110">
            <TrendingUp size="20" />
          </div>
          <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest">Total Revenue</p>
          <h3 class="text-xl font-semibold text-gray-900 mt-1">৳{{ Number(revenue).toLocaleString() }}</h3>
        </div>

        <!-- COGS -->
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-gray-100 transition-all group">
          <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110">
            <Box size="20" />
          </div>
          <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest">Cost of Goods</p>
          <h3 class="text-xl font-semibold text-gray-900 mt-1">৳{{ Number(cogs).toLocaleString() }}</h3>
        </div>

        <!-- Gross Profit -->
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-gray-100 transition-all group">
          <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110">
            <Target size="20" />
          </div>
          <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest">Gross Profit</p>
          <h3 class="text-xl font-semibold text-gray-900 mt-1">৳{{ Number(gross_profit).toLocaleString() }}</h3>
        </div>



        <!-- Net Profit -->
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-gray-100 transition-all group overflow-hidden relative">
          <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50/50 rounded-full -mr-8 -mt-8 blur-2xl"></div>
          <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110 relative">
            <Zap size="20" />
          </div>
          <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest relative">Net Profit</p>
          <h3 class="text-xl font-semibold text-indigo-600 mt-1 relative">৳{{ Number(profit).toLocaleString() }}</h3>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <!-- Trend Chart -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-semibold text-gray-900">Revenue & Expense Flow</h3>
            <div class="flex items-center gap-4">
              <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Revenue</span>
              </div>
            </div>
          </div>
          <VueApexCharts
            type="area"
            height="320"
            :options="chartOptions"
            :series="chartSeries"
          />
        </div>

        <!-- Revenue Breakdown -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 mb-8">Category Share</h3>
          <VueApexCharts
            type="donut"
            height="320"
            :options="pieChartOptions"
            :series="pieChartSeries"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <!-- Orders Trend -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 mb-8">Volume Trend (Orders)</h3>
          <VueApexCharts
            type="line"
            height="320"
            :options="ordersChartOptions"
            :series="ordersChartSeries"
          />
        </div>

        <!-- Monthly Overview -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 mb-8">Component Overview</h3>
          <VueApexCharts
            type="bar"
            height="320"
            :options="monthlyOverviewChartOptions"
            :series="monthlyOverviewChartSeries"
          />
        </div>
      </div>

      <!-- Analysis Table -->
      <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Product Profit Analysis</h3>
            <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Net gain after weighted average COGS</p>
          </div>
          <div class="px-4 py-1.5 bg-white border border-gray-100 rounded-full text-[10px] font-semibold text-blue-600 shadow-sm uppercase tracking-widest">
            Top 20 Performers
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-gray-50/50">
                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Product Intel</th>
                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Volume</th>
                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Revenue</th>
                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Direct Cost</th>
                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Net Profit</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="item in productProfitReport" :key="item.product_id" class="hover:bg-gray-50/50 transition-colors group">
                <td class="px-8 py-5">
                  <p class="text-sm font-medium text-gray-900">{{ item.product_name }}</p>
                  <p class="text-[10px] text-gray-400 mt-0.5">SKU: {{ item.product_id }}</p>
                </td>
                <td class="px-8 py-5 text-center text-sm text-gray-600 font-medium font-mono">
                  {{ item.sold_quantity }}
                </td>
                <td class="px-8 py-5 text-right text-sm font-medium text-gray-900">
                  ৳{{ Number(item.revenue).toLocaleString() }}
                </td>
                <td class="px-8 py-5 text-right text-sm text-rose-500 font-medium">
                  ৳{{ Number(item.cost).toLocaleString() }}
                </td>
                <td class="px-8 py-5 text-right">
                  <span class="text-sm font-semibold text-emerald-600 tracking-tight">৳{{ Number(item.profit).toLocaleString() }}</span>
                </td>
              </tr>
              <tr v-if="productProfitReport.length === 0">
                <td colspan="5" class="px-8 py-20 text-center text-gray-400 italic text-sm">
                  No analytical data found for this period. Try adjusting your filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted, watch, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import VueApexCharts from "vue3-apexcharts";
import { 
  TrendingUp, Box, Coins, Wallet, BarChart3, 
  Calendar, Zap, Target, Search, X 
} from 'lucide-vue-next'

const props = defineProps({
  revenue: Number,
  cogs: Number,
  gross_profit: Number,
  expenses: Number,
  profit: Number,
  totalOrders: Number,
  filter: String,
  startDate: String,
  endDate: String,
  chartData: Object,
  ordersChartData: Object,
  monthlyOverviewData: Object,
  productProfitReport: Array
})

const filter = ref(props.filter)
const startDate = ref(props.startDate)
const endDate = ref(props.endDate)

watch(() => props.filter, (v) => filter.value = v)
watch(() => props.startDate, (v) => startDate.value = v)
watch(() => props.endDate, (v) => endDate.value = v)

const applyFilter = () => {
  const params = { filter: filter.value }
  if (filter.value === 'custom') {
    params.start_date = startDate.value
    params.end_date = endDate.value
  }
  router.get(route('admin.business-dashboard.index'), params, {
    preserveState: true,
    replace: true
  })
}

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  colors: ['#10B981'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.05,
      stops: [50, 100]
    }
  },
  xaxis: {
    categories: props.chartData.categories || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#94A3B8', fontSize: '10px', fontWeight: 500 } }
  },
  yaxis: {
    labels: {
      style: { colors: '#94A3B8', fontSize: '10px', fontWeight: 500 },
      formatter: (v) => '৳' + Number(v).toLocaleString()
    }
  },
  grid: { borderColor: '#F1F5F9', strokeDashArray: 4 },
  tooltip: { theme: 'light', x: { show: false } }
}))

const chartSeries = computed(() => [
  { name: 'Revenue', data: props.chartData.revenue || [] }
])

const pieChartOptions = computed(() => ({
  chart: { type: 'donut' },
  colors: ['#10B981', '#6366F1', '#F59E0B'],
  labels: ['Revenue', 'Profit', 'Other'],
  plotOptions: {
    pie: {
      donut: {
        size: '75%',
        labels: {
          show: true,
          name: { fontSize: '12px', color: '#64748B', fontWeight: 600 },
          value: { fontSize: '18px', fontWeight: 700, formatter: (v) => '৳' + Number(v).toLocaleString() },
          total: { show: true, label: 'Performance', color: '#94A3B8' }
        }
      }
    }
  },
  legend: { position: 'bottom', fontSize: '12px', fontWeight: 500 },
  stroke: { show: false }
}))

const pieChartSeries = computed(() => {
  const rev = Number(props.revenue) || 0
  const prf = Number(props.profit) || 0
  const oth = Math.max(0, rev - prf)
  return [rev, prf, oth]
})

const ordersChartOptions = computed(() => ({
  chart: { type: 'line', toolbar: { show: false } },
  colors: ['#6366F1'],
  stroke: { curve: 'smooth', width: 4 },
  markers: { size: 6, colors: ['#6366F1'], strokeWidth: 3, strokeColors: '#fff' },
  xaxis: {
    categories: props.ordersChartData.categories || [],
    labels: { style: { colors: '#94A3B8', fontSize: '10px' } }
  },
  grid: { borderColor: '#F1F5F9' },
  yaxis: { labels: { style: { colors: '#94A3B8', fontSize: '10px' } } }
}))

const ordersChartSeries = computed(() => [{ name: 'Orders', data: props.ordersChartData.orders || [] }])

const monthlyOverviewChartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, stacked: true },
  colors: ['#3B82F6', '#F59E0B', '#10B981'],
  plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
  xaxis: {
    categories: props.monthlyOverviewData.categories || [],
    labels: { style: { colors: '#94A3B8', fontSize: '10px' } }
  },
  grid: { show: false },
  legend: { show: true, position: 'top', horizontalAlign: 'right' },
  dataLabels: { enabled: false }
}))

const monthlyOverviewChartSeries = computed(() => [
  { name: 'Salary', data: props.monthlyOverviewData.salary || [] },
  { name: 'Purchases', data: props.monthlyOverviewData.purchases || [] },
  { name: 'Revenue', data: props.monthlyOverviewData.revenue || [] }
])
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>

<style scoped>
/* Additional styles if needed */
</style>