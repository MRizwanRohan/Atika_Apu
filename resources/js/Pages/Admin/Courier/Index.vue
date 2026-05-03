<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  settings: Object,
  pathaoWebhookUrl: String,
  steadfastWebhookUrl: String,
});

const showSteadfastSecret = ref(false);

// ── Pathao API Settings Form ──
const pathaoForm = useForm({
  client_id: props.settings?.client_id || '',
  client_secret: props.settings?.client_secret || '',
  username: props.settings?.username || '',
  password: props.settings?.password || '',
  is_enabled: props.settings?.is_enabled || 'no',
  StoreId: props.settings?.StoreId || '',
});

// ── Steadfast API Settings Form ──
const steadfastForm = useForm({
  steadfast_api_key: props.settings?.steadfast_api_key || '',
  steadfast_secret_key: props.settings?.steadfast_secret_key || '',
  steadfast_is_enabled: props.settings?.steadfast_is_enabled || 'no',
});

// ── Webhook Settings ──
const pathaoWebhookEnabled = ref(props.settings?.pathao_webhook_enabled === 'yes');
const steadfastWebhookEnabled = ref(props.settings?.steadfast_webhook_enabled === 'yes');

// ── Status Mappings ──
const orderStatuses = [
  { value: 'no_action', label: '— No Action —' },
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'returned', label: 'Returned' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'partially_delivered', label: 'Partially Delivered' },
  { value: 'incomplete', label: 'Incomplete' },
];

const pathaoEvents = [
  'Delivered', 'Pickup Cancelled', 'Returned', 'Order Created',
  'Assigned For Pickup', 'Pickup', 'Pickup Failed', 'In Transit',
  'Assigned for Delivery', 'Partial Delivery', 'Return',
  'Delivery Failed', 'On Hold', 'Payment Invoice', 'Paid Return',
  'Pickup Requested', 'At the Sorting Hub', 'Received at Last Mile Hub',
  'Order Updated', 'Exchange',
];

const steadfastStatuses = [
  'pending', 'delivered', 'partial_delivered', 'cancelled', 'unknown',
];

// Default mappings
const defaultPathaoMapping = {
  'Delivered': 'delivered',
  'Pickup Cancelled': 'cancelled',
  'Returned': 'returned',
  'Order Created': 'processing',
  'Assigned For Pickup': 'shipped',
  'Pickup': 'shipped',
  'Pickup Failed': 'on_hold',
  'In Transit': 'shipped',
  'Assigned for Delivery': 'shipped',
  'Partial Delivery': 'partially_delivered',
  'Return': 'returned',
  'Delivery Failed': 'on_hold',
  'On Hold': 'on_hold',
  'Payment Invoice': 'no_action',
  'Paid Return': 'returned',
  'Pickup Requested': 'processing',
  'At the Sorting Hub': 'shipped',
  'Received at Last Mile Hub': 'shipped',
  'Order Updated': 'no_action',
  'Exchange': 'no_action',
};

const defaultSteadfastMapping = {
  'pending': 'processing',
  'delivered': 'delivered',
  'partial_delivered': 'partially_delivered',
  'cancelled': 'cancelled',
  'unknown': 'no_action',
};

const pathaoMapping = ref({ ...defaultPathaoMapping, ...(props.settings?.pathao_status_mapping || {}) });
const steadfastMapping = ref({ ...defaultSteadfastMapping, ...(props.settings?.steadfast_status_mapping || {}) });

const pathaoMappingSaving = ref(false);
const steadfastMappingSaving = ref(false);

// ── Submit Handlers ──
const submitPathao = () => {
  pathaoForm.post(route('admin.courier.settings.store'), {
    onSuccess: () => toast.success('Pathao settings saved successfully!'),
    onError: () => toast.error('Failed to save Pathao settings.'),
  });
};

const submitSteadfast = () => {
  steadfastForm.post(route('admin.courier.settings.store'), {
    onSuccess: () => toast.success('Steadfast settings saved successfully!'),
    onError: () => toast.error('Failed to save Steadfast settings.'),
  });
};

// ── Webhook Toggle ──
const toggleWebhook = (courier) => {
  const field = courier === 'pathao' ? 'pathao_webhook_enabled' : 'steadfast_webhook_enabled';
  const current = courier === 'pathao' ? pathaoWebhookEnabled.value : steadfastWebhookEnabled.value;
  const newValue = current ? 'no' : 'yes';

  router.post(route('admin.courier.settings.store'), {
    [field]: newValue,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      if (courier === 'pathao') pathaoWebhookEnabled.value = !current;
      else steadfastWebhookEnabled.value = !current;
      toast.success(`${courier === 'pathao' ? 'Pathao' : 'Steadfast'} webhook ${newValue === 'yes' ? 'enabled' : 'disabled'}`);
    },
    onError: () => toast.error('Failed to update webhook status'),
  });
};

// ── Generate Token ──
const generateToken = (courier) => {
  router.post(route('admin.courier.settings.generate-token', courier), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success(`${courier === 'pathao' ? 'Pathao' : 'Steadfast'} token regenerated!`),
    onError: () => toast.error('Failed to generate token'),
  });
};

// ── Save Status Mapping ──
const saveMapping = (courier) => {
  const mapping = courier === 'pathao' ? pathaoMapping.value : steadfastMapping.value;
  const saving = courier === 'pathao' ? pathaoMappingSaving : steadfastMappingSaving;
  saving.value = true;

  router.post(route('admin.courier.settings.status-mapping', courier), { mapping }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`${courier === 'pathao' ? 'Pathao' : 'Steadfast'} mapping saved!`);
      saving.value = false;
    },
    onError: () => {
      toast.error('Failed to save mapping');
      saving.value = false;
    },
  });
};

// ── Copy to Clipboard ──
const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    toast.success('Copied to clipboard!');
  }).catch(() => {
    toast.error('Failed to copy');
  });
};
</script>

<template>
  <Head title="Courier Settings" />
  <AdminLayout>
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8 pb-12">

      <!-- ══════════════════════════════════════════════ -->
      <!-- ═══════════ PATHAO SECTION ══════════════════ -->
      <!-- ══════════════════════════════════════════════ -->

      <!-- Pathao API Settings -->
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <form @submit.prevent="submitPathao" class="p-6 bg-white border-b border-gray-200">
          <div class="flex flex-col items-center mb-6">
            <img src="/assets/img/logo/Pathao.png" alt="Pathao" class="h-16 mb-1" />
            <div class="flex items-center mt-3">
              <span class="text-sm mr-2">Enable Pathao</span>
              <div class="relative inline-block w-12 align-middle select-none transition duration-200 ease-in">
                <input
                  type="checkbox"
                  :checked="pathaoForm.is_enabled === 'yes'"
                  @change="pathaoForm.is_enabled = pathaoForm.is_enabled === 'yes' ? 'no' : 'yes'"
                  class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                />
                <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="client_id" class="block text-sm text-gray-600">Client ID</label>
              <input id="client_id" v-model="pathaoForm.client_id" type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': pathaoForm.errors.client_id }" />
              <p v-if="pathaoForm.errors.client_id" class="text-red-500 text-xs mt-1">{{ pathaoForm.errors.client_id }}</p>
            </div>
            <div>
              <label for="username" class="block text-sm text-gray-600">Username</label>
              <input id="username" v-model="pathaoForm.username" type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': pathaoForm.errors.username }" />
              <p v-if="pathaoForm.errors.username" class="text-red-500 text-xs mt-1">{{ pathaoForm.errors.username }}</p>
            </div>
            <div>
              <label for="client_secret" class="block text-sm text-gray-600">Client Secret</label>
              <input id="client_secret" v-model="pathaoForm.client_secret" type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': pathaoForm.errors.client_secret }" />
              <p v-if="pathaoForm.errors.client_secret" class="text-red-500 text-xs mt-1">{{ pathaoForm.errors.client_secret }}</p>
            </div>
            <div>
              <label for="password" class="block text-sm text-gray-600">Password</label>
              <input id="password" v-model="pathaoForm.password" type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': pathaoForm.errors.password }" />
              <p v-if="pathaoForm.errors.password" class="text-red-500 text-xs mt-1">{{ pathaoForm.errors.password }}</p>
            </div>
          </div>

          <div class="mt-4">
            <label for="StoreId" class="block text-sm text-gray-600">Store ID</label>
            <input id="StoreId" v-model.number="pathaoForm.StoreId" type="number"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-500': pathaoForm.errors.StoreId }" />
            <p v-if="pathaoForm.errors.StoreId" class="text-red-500 text-xs mt-1">{{ pathaoForm.errors.StoreId }}</p>
          </div>

          <div class="mt-6 flex justify-end">
            <button type="submit"
              class="px-6 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none transition ease-in-out duration-150"
              :disabled="pathaoForm.processing">
              <span v-if="pathaoForm.processing">Saving...</span>
              <span v-else>Save Pathao Settings</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Pathao Webhook Integration -->
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Pathao Webhook Integration</h3>
                <p class="text-sm text-gray-500">Auto-update order status from Pathao delivery events</p>
              </div>
            </div>
            <button @click="toggleWebhook('pathao')"
              :class="pathaoWebhookEnabled ? 'bg-indigo-600' : 'bg-gray-300'"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
              <span :class="pathaoWebhookEnabled ? 'translate-x-5' : 'translate-x-0'"
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
            </button>
          </div>

          <div v-if="pathaoWebhookEnabled" class="space-y-4 mt-4">
            <!-- Callback URL -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Pathao Webhook URL</label>
              <div class="flex items-center gap-2">
                <input type="text" :value="pathaoWebhookUrl" readonly
                  class="flex-1 bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" />
                <button @click="copyToClipboard(pathaoWebhookUrl)"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition">
                  Copy
                </button>
              </div>
              <p class="text-xs text-gray-400 mt-1">Copy this to your Pathao webhook configuration</p>
            </div>

            <!-- Token -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Pathao Token</label>
              <div class="flex items-center gap-2">
                <input type="text" :value="settings?.pathao_webhook_token || 'Not generated yet'" readonly
                  class="flex-1 bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" />
                <button @click="copyToClipboard(settings?.pathao_webhook_token || '')"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition"
                  :disabled="!settings?.pathao_webhook_token">
                  Copy
                </button>
              </div>
              <p class="text-xs text-gray-400 mt-1">Copy this to your Pathao webhook configuration</p>
            </div>

            <button @click="generateToken('pathao')"
              class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-md text-sm font-medium transition">
              {{ settings?.pathao_webhook_token ? 'Regenerate Token' : 'Generate Token' }}
            </button>

            <!-- Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <div class="text-sm text-blue-700">
                  <p class="font-medium">How to setup:</p>
                  <ol class="list-decimal ml-4 mt-1 space-y-1">
                    <li>Copy the <strong>Webhook URL</strong> above</li>
                    <li>Go to Pathao Merchant Panel → Webhook Integration</li>
                    <li>Paste the URL in <strong>Callback URL</strong> field</li>
                    <li>Copy the <strong>Token</strong> above and paste in <strong>Secret</strong> field</li>
                    <li>Select the events you want to receive</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pathao Status Mapping -->
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-800">Pathao Status Mapping</h3>
              <p class="text-sm text-gray-500">Map Pathao events to your order statuses</p>
            </div>
          </div>

          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pathao Event</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Your Order Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="event in pathaoEvents" :key="event" class="hover:bg-gray-50 transition">
                  <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ event }}</td>
                  <td class="px-4 py-3">
                    <select v-model="pathaoMapping[event]"
                      class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                      <option v-for="s in orderStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-end">
            <button @click="saveMapping('pathao')"
              class="px-6 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition"
              :disabled="pathaoMappingSaving">
              <span v-if="pathaoMappingSaving">Saving...</span>
              <span v-else>Save Pathao Mapping</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════ -->
      <!-- ═══════════ STEADFAST SECTION ═══════════════ -->
      <!-- ══════════════════════════════════════════════ -->

      <!-- Steadfast API Settings -->
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <form @submit.prevent="submitSteadfast" class="p-6 bg-white border-b border-gray-200">
          <div class="flex flex-col items-center mb-6">
            <img src="/assets/img/logo/stastfast.png" alt="Steadfast" class="h-16 mb-1" />
            <div class="flex items-center mt-3">
              <span class="text-sm mr-2">Enable Steadfast</span>
              <div class="relative inline-block w-12 align-middle select-none transition duration-200 ease-in">
                <input
                  type="checkbox"
                  :checked="steadfastForm.steadfast_is_enabled === 'yes'"
                  @change="steadfastForm.steadfast_is_enabled = steadfastForm.steadfast_is_enabled === 'yes' ? 'no' : 'yes'"
                  class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                />
                <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="steadfast_api_key" class="block text-sm text-gray-600">API Key</label>
              <input id="steadfast_api_key" v-model="steadfastForm.steadfast_api_key" type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': steadfastForm.errors.steadfast_api_key }" />
              <p v-if="steadfastForm.errors.steadfast_api_key" class="text-red-500 text-xs mt-1">{{ steadfastForm.errors.steadfast_api_key }}</p>
            </div>
            <div>
              <label for="steadfast_secret_key" class="block text-sm text-gray-600">Secret Key</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <input
                  id="steadfast_secret_key"
                  v-model="steadfastForm.steadfast_secret_key"
                  :type="showSteadfastSecret ? 'text' : 'password'"
                  class="block w-full rounded-md border-gray-300 pr-10 focus:border-indigo-500 focus:ring-indigo-500"
                  :class="{ 'border-red-500': steadfastForm.errors.steadfast_secret_key }"
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" @click="showSteadfastSecret = !showSteadfastSecret">
                  <svg v-if="!showSteadfastSecret" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 014.288-4.437M9.172 9.172a3 3 0 014.243 4.243M9.828 10.172l3.859 3.86M17.614 17.614l-4.11-4.11M21.542 12c-1.274 4.057-5.064 7-9.542 7-1.002 0-1.964-.149-2.868-.425M19.071 4.929l-14.142 14.142" />
                  </svg>
                </div>
              </div>
              <p v-if="steadfastForm.errors.steadfast_secret_key" class="text-red-500 text-xs mt-1">{{ steadfastForm.errors.steadfast_secret_key }}</p>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button type="submit"
              class="px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none transition ease-in-out duration-150"
              :disabled="steadfastForm.processing">
              <span v-if="steadfastForm.processing">Saving...</span>
              <span v-else>Save Steadfast Settings</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Steadfast Webhook Integration -->
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Steadfast Webhook Integration</h3>
                <p class="text-sm text-gray-500">Auto-update order status from Steadfast delivery events</p>
              </div>
            </div>
            <button @click="toggleWebhook('steadfast')"
              :class="steadfastWebhookEnabled ? 'bg-blue-600' : 'bg-gray-300'"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
              <span :class="steadfastWebhookEnabled ? 'translate-x-5' : 'translate-x-0'"
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
            </button>
          </div>

          <div v-if="steadfastWebhookEnabled" class="space-y-4 mt-4">
            <!-- Callback URL -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Steadfast Webhook URL</label>
              <div class="flex items-center gap-2">
                <input type="text" :value="steadfastWebhookUrl" readonly
                  class="flex-1 bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" />
                <button @click="copyToClipboard(steadfastWebhookUrl)"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition">
                  Copy
                </button>
              </div>
              <p class="text-xs text-gray-400 mt-1">Copy this to your Steadfast webhook configuration</p>
            </div>

            <!-- Token -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Steadfast Token</label>
              <div class="flex items-center gap-2">
                <input type="text" :value="settings?.steadfast_webhook_token || 'Not generated yet'" readonly
                  class="flex-1 bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" />
                <button @click="copyToClipboard(settings?.steadfast_webhook_token || '')"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition"
                  :disabled="!settings?.steadfast_webhook_token">
                  Copy
                </button>
              </div>
              <p class="text-xs text-gray-400 mt-1">Copy this to the "Auth Token" field in Steadfast dashboard</p>
            </div>

            <button @click="generateToken('steadfast')"
              class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-md text-sm font-medium transition">
              {{ settings?.steadfast_webhook_token ? 'Regenerate Token' : 'Generate Token' }}
            </button>

            <!-- Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <div class="text-sm text-blue-700">
                  <p class="font-medium">How to setup:</p>
                  <ol class="list-decimal ml-4 mt-1 space-y-1">
                    <li>Copy the <strong>Webhook URL</strong> above</li>
                    <li>Go to Steadfast Dashboard → Webhook Settings</li>
                    <li>Paste the URL in <strong>Callback URL</strong> field</li>
                    <li>Copy the <strong>Token</strong> above and paste in <strong>Auth Token</strong> field</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Steadfast Status Mapping -->
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-800">Steadfast Status Mapping</h3>
              <p class="text-sm text-gray-500">Map Steadfast statuses to your order statuses</p>
            </div>
          </div>

          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Steadfast Status</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Your Order Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="status in steadfastStatuses" :key="status" class="hover:bg-gray-50 transition">
                  <td class="px-4 py-3 text-sm font-medium text-gray-700 capitalize">{{ status.replace('_', ' ') }}</td>
                  <td class="px-4 py-3">
                    <select v-model="steadfastMapping[status]"
                      class="w-full border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-blue-500">
                      <option v-for="s in orderStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-end">
            <button @click="saveMapping('steadfast')"
              class="px-6 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 transition"
              :disabled="steadfastMappingSaving">
              <span v-if="steadfastMappingSaving">Saving...</span>
              <span v-else>Save Steadfast Mapping</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
.toggle-checkbox:checked {
  right: 0;
  border-color: #4f46e5;
}
.toggle-checkbox:checked + .toggle-label {
  background-color: #4f46e5;
}
.toggle-label {
  transition: background-color 0.2s ease-in;
}
</style>