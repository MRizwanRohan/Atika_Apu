<template>
  <div
    v-if="visible"
    class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4"
  >
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden relative animate-in fade-in zoom-in duration-200">
      <!-- Close Button -->
      <button
        class="absolute top-4 right-4 z-10 p-2 bg-gray-100 hover:bg-red-50 text-gray-400 hover:text-red-500 rounded-full transition-colors"
        @click="$emit('close')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <div class="flex flex-col md:flex-row max-h-[90vh] overflow-y-auto">
        <!-- Left Side - Logo and Success Rate -->
        <div class="w-full md:w-[350px] bg-gray-50/50 p-8 text-center border-b md:border-b-0 md:border-r border-gray-100">
          <div class="mb-8">
            <img src="/assets/img/logo/logo.png" alt="logo" class="mx-auto h-12 object-contain">
          </div>

          <h2 class="text-xl font-bold mb-8 text-gray-900 tracking-tight">Delivery Success Ratio</h2>

          <div class="flex justify-center mb-8">
            <div class="relative w-40 h-40 rounded-full border-[12px] border-gray-100 flex items-center justify-center shadow-inner overflow-hidden">
              <div 
                v-if="aggregated.total_orders > 0"
                class="absolute inset-0 rounded-full border-[12px] transition-all duration-1000"
                :class="aggregated.success_rate > 70 ? 'border-green-500' : (aggregated.success_rate > 40 ? 'border-yellow-500' : 'border-red-500')"
                :style="{ clipPath: `polygon(50% 50%, -50% -50%, ${aggregated.success_rate > 50 ? '150% -50%, 150% 150%, -50% 150%' : '50% -50%'}, -50% -50%)` }"
              ></div>
              <div class="text-center">
                <span class="text-4xl font-black text-gray-900 tracking-tighter">
                  {{ aggregated.total_orders > 0 ? aggregated.success_rate.toFixed(1) + '%' : 'N/A' }}
                </span>
                <div class="text-[10px] uppercase font-bold text-gray-500 mt-1">Reliability</div>
              </div>
            </div>
          </div>

          <div 
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-full font-black text-sm uppercase mb-4',
              aggregated.total_orders === 0 ? 'bg-gray-100 text-gray-600' : 
              (aggregated.success_rate > 70 ? 'bg-green-100 text-green-700' : 
              (aggregated.success_rate > 40 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'))
            ]"
          >
            <span 
              :class="[
                'w-2 h-2 rounded-full animate-pulse',
                aggregated.total_orders === 0 ? 'bg-gray-400' : 
                (aggregated.success_rate > 70 ? 'bg-green-500' : 
                (aggregated.success_rate > 40 ? 'bg-yellow-500' : 'bg-red-500'))
              ]"
            ></span>
            {{ aggregated.total_orders === 0 ? 'No Data' : (aggregated.success_rate > 70 ? 'Excellent' : (aggregated.success_rate > 40 ? 'Average' : 'Risky')) }}
          </div>
          <p class="text-sm font-medium text-gray-600 leading-relaxed px-4">
            {{ reliabilityDescription }}
          </p>
        </div>

        <!-- Right Side - Content -->
        <div class="flex-1 p-6 md:p-10">
          <!-- Phone Header -->
          <div class="bg-gray-900 rounded-2xl p-6 text-white mb-8 shadow-xl shadow-gray-200">
            <div class="text-gray-400 text-[10px] uppercase font-black tracking-widest mb-1">Checking Number</div>
            <div class="text-2xl font-black tracking-tight font-mono">{{ customerNumber }}</div>
          </div>

          <!-- Stats Grid -->
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100">
              <div class="text-2xl font-black text-blue-700">{{ aggregated.total_success + aggregated.total_cancel }}</div>
              <div class="text-[10px] font-bold text-blue-500 uppercase">মোট অর্ডার</div>
            </div>
            <div class="bg-green-50 rounded-2xl p-4 border border-green-100">
              <div class="text-2xl font-black text-green-700">{{ aggregated.total_success }}</div>
              <div class="text-[10px] font-bold text-green-500 uppercase">ডেলিভারি</div>
            </div>
            <div class="bg-red-50 rounded-2xl p-4 border border-red-100">
              <div class="text-2xl font-black text-red-700">{{ aggregated.total_cancel }}</div>
              <div class="text-[10px] font-bold text-red-500 uppercase">বাতিল</div>
            </div>
          </div>

          <!-- Details Table -->
          <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-gray-500">Courier</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-gray-500 text-center">Orders</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-gray-500 text-center">Delivered</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-gray-500 text-center">Cancelled</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-gray-500 text-right">Cancel Rate</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-4"><span class="font-bold text-gray-900">SteadFast</span></td>
                    <template v-if="steadfastData.error">
                      <td colspan="4" class="px-4 py-4 text-right text-[10px] font-bold text-red-400 italic">
                        {{ steadfastData.error }}
                      </td>
                    </template>
                    <template v-else>
                      <td class="px-4 py-4 text-center font-bold">{{ steadfastData.total }}</td>
                      <td class="px-4 py-4 text-center font-bold text-green-600">{{ steadfastData.success }}</td>
                      <td class="px-4 py-4 text-center font-bold text-red-600">{{ steadfastData.cancel }}</td>
                      <td class="px-4 py-4 text-right">
                        <span class="bg-red-50 text-red-600 px-2 py-1 rounded-lg text-xs font-black">{{ steadfastCancelRate }}%</span>
                      </td>
                    </template>
                  </tr>
                  <tr>
                    <td class="px-4 py-4"><span class="font-bold text-red-500">Pathao</span></td>
                    <td colspan="4" class="px-4 py-4 text-right text-[10px] font-bold text-gray-400 italic">
                      Private API - Data restricted
                    </td>
                  </tr>
                  <tr>
                    <td class="px-4 py-4"><span class="font-black text-red-600">REDX</span></td>
                    <td colspan="4" class="px-4 py-4 text-right text-[10px] font-bold text-gray-400 italic">
                      Private API - Data restricted
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const { visible, fraudData } = defineProps({
  visible: Boolean,
  fraudData: Object,
});

const aggregated = computed(() => fraudData?.aggregated ?? {
  success_rate: 0,
  total_success: 0,
  total_cancel: 0,
  total_orders: 0,
});

const pathaoData = computed(() => fraudData?.pathao ?? {
  total_delivery: 0,
  successful_delivery: 0,
});

const steadfastData = computed(() => fraudData?.steadfast ?? {
  total: 0,
  success: 0,
  cancel: 0,
});

const customerNumber = computed(() => fraudData?.phone ?? 'N/A');

const pathaoCancelRate = computed(() => {
  const total = pathaoData.value.total_delivery;
  const successful = pathaoData.value.successful_delivery;
  const cancelled = total - successful;
  return total > 0 ? ((cancelled / total) * 100).toFixed(1) : '0.0';
});

const steadfastCancelRate = computed(() => {
  const total = steadfastData.value.total;
  const cancelled = steadfastData.value.cancel;
  return total > 0 ? ((cancelled / total) * 100).toFixed(1) : '0.0';
});

const reliabilityDescription = computed(() => {
  if (aggregated.value.total_orders === 0) {
    return 'এই নাম্বারে কোনো ডেলিভারি তথ্য পাওয়া যায়নি।';
  }
  if (aggregated.value.success_rate > 70) {
    return 'এটি একটি নির্ভরযোগ্য ডেলিভারি। গ্রাহকের সফল ডেলিভারির হার অনেক বেশি।';
  } else if (aggregated.value.success_rate > 40) {
    return 'এই গ্রাহকের ডেলিভারি রেকর্ড সন্তোষজনক। তবে কিছুটা সতর্কতা অবলম্বন করা যেতে পারে।';
  } else {
    return 'সতর্কবার্তা! এই গ্রাহকের ডেলিভারি রেকর্ড বেশ ঝুঁকিপূর্ণ। অর্ডারটি কনফার্ম করার আগে কথা বলে নিন।';
  }
});
</script>