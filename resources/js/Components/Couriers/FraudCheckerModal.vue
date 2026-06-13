<template>
  <div
    v-if="visible"
    class="fixed inset-0 bg-black/60 flex items-center justify-center z-[100] p-4"
  >
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden relative animate-in fade-in zoom-in duration-200">
      <!-- Close Button -->
      <button
        class="absolute top-2 right-2 z-10 p-1 bg-gray-100/50 hover:bg-red-50 text-gray-400 hover:text-red-500 rounded-full transition-colors"
        @click="$emit('close')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <div class="flex flex-col md:flex-row max-h-[80vh] overflow-y-auto">
        <!-- Left Side - Logo and Success Rate -->
        <div class="w-full md:w-[180px] bg-gray-50/50 p-3 text-center border-b md:border-b-0 md:border-r border-gray-100 flex flex-col justify-center">
          <div class="mb-2">
            <img src="/assets/img/logo/logo.png" alt="logo" class="mx-auto h-6 object-contain">
          </div>

          <h2 class="text-[9px] font-bold mb-2 text-gray-400 uppercase tracking-widest">Success Ratio</h2>

          <div class="flex justify-center mb-2">
            <div class="relative w-20 h-20 rounded-full border-[6px] border-gray-100 flex items-center justify-center shadow-inner overflow-hidden">
              <div 
                v-if="aggregated.total_orders > 0"
                class="absolute inset-0 rounded-full border-[6px] transition-all duration-1000"
                :class="aggregated.success_rate > 70 ? 'border-green-500' : (aggregated.success_rate > 40 ? 'border-yellow-500' : 'border-red-500')"
                :style="{ clipPath: `polygon(50% 50%, -50% -50%, ${aggregated.success_rate > 50 ? '150% -50%, 150% 150%, -50% 150%' : '50% -50%'}, -50% -50%)` }"
              ></div>
              <div class="text-center">
                <span class="text-xl font-black text-gray-900 tracking-tighter">
                  {{ aggregated.total_orders > 0 ? aggregated.success_rate.toFixed(0) + '%' : 'N/A' }}
                </span>
                <div class="text-[7px] uppercase font-bold text-gray-400">Reliability</div>
              </div>
            </div>
          </div>

          <div 
            :class="[
              'inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold text-[9px] uppercase mb-2 mx-auto',
              aggregated.total_orders === 0 ? 'bg-gray-100 text-gray-600' : 
              (aggregated.success_rate > 70 ? 'bg-green-100 text-green-700' : 
              (aggregated.success_rate > 40 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'))
            ]"
          >
            {{ aggregated.total_orders === 0 ? 'No Data' : (aggregated.success_rate > 70 ? 'Excellent' : (aggregated.success_rate > 40 ? 'Average' : 'Risky')) }}
          </div>
          <p class="text-[9px] font-medium text-gray-500 leading-tight">
            {{ reliabilityDescription }}
          </p>
        </div>

        <!-- Right Side - Content -->
        <div class="flex-1 p-3">
          <!-- Phone Header -->
          <div class="bg-gray-900 rounded-lg p-2.5 text-white mb-3 flex items-center justify-between">
            <div>
              <div class="text-gray-400 text-[8px] uppercase font-black tracking-widest leading-none mb-1">Number</div>
              <div class="text-base font-black tracking-tight font-mono leading-none">{{ customerNumber }}</div>
            </div>
            <div class="bg-gray-800 px-2 py-1 rounded border border-gray-700">
               <span class="text-[9px] font-bold leading-none" :class="aggregated.success_rate > 40 ? 'text-green-400' : 'text-red-400'">
                {{ aggregated.success_rate > 40 ? 'VERIFIED' : 'FLAGGED' }}
               </span>
            </div>
          </div>

          <!-- Stats Grid -->
          <div class="grid grid-cols-3 gap-2 mb-3">
            <div class="bg-blue-50/50 rounded-lg p-2 border border-blue-100/50 text-center">
              <div class="text-[8px] font-bold text-blue-500 uppercase leading-none mb-1">Total</div>
              <div class="text-base font-black text-blue-700 leading-none">{{ aggregated.total_success + aggregated.total_cancel }}</div>
            </div>
            <div class="bg-green-50/50 rounded-lg p-2 border border-green-100/50 text-center">
              <div class="text-[8px] font-bold text-green-500 uppercase leading-none mb-1">Delivered</div>
              <div class="text-base font-black text-green-700 leading-none">{{ aggregated.total_success }}</div>
            </div>
            <div class="bg-red-50/50 rounded-lg p-2 border border-red-100/50 text-center">
              <div class="text-[8px] font-bold text-red-500 uppercase leading-none mb-1">Cancel</div>
              <div class="text-base font-black text-red-700 leading-none">{{ aggregated.total_cancel }}</div>
            </div>
          </div>

          <!-- Details Table -->
          <div class="bg-white rounded-lg border border-gray-100 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-[10px]">
                <thead>
                  <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-2 py-1.5 font-bold uppercase text-gray-400">Courier</th>
                    <th class="px-2 py-1.5 font-bold uppercase text-gray-400 text-center">Success</th>
                    <th class="px-2 py-1.5 font-bold uppercase text-gray-400 text-right">Ratio</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 font-medium">
                  <tr class="hover:bg-gray-50/30">
                    <td class="px-2 py-1.5 text-gray-700 font-bold">SteadFast</td>
                    <template v-if="steadfastData.error">
                      <td colspan="2" class="px-2 py-1.5 text-right text-[8px] text-red-400 italic">
                        {{ steadfastData.error }}
                      </td>
                    </template>
                    <template v-else>
                      <td class="px-2 py-1.5 text-center text-green-600 font-bold">{{ steadfastData.success }}/{{ steadfastData.total }}</td>
                      <td class="px-2 py-1.5 text-right">
                        <span class="bg-red-50 text-red-600 px-1 py-0.5 rounded font-bold">{{ steadfastCancelRate }}%</span>
                      </td>
                    </template>
                  </tr>
                  <tr class="opacity-50">
                    <td class="px-2 py-1.5 font-bold text-red-400">Pathao</td>
                    <td colspan="2" class="px-2 py-1.5 text-right text-[8px] text-gray-400 italic">restricted</td>
                  </tr>
                  <tr class="opacity-50">
                    <td class="px-2 py-1.5 font-bold text-red-500">REDX</td>
                    <td colspan="2" class="px-2 py-1.5 text-right text-[8px] text-gray-400 italic">restricted</td>
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