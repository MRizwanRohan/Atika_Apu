<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  settings: Object,
});

const showSteadfastSecret = ref(false);

const pathaoForm = useForm({
  client_id: props.settings?.client_id || '',
  client_secret: props.settings?.client_secret || '',
  username: props.settings?.username || '',
  password: props.settings?.password || '',
  is_enabled: props.settings?.is_enabled || 'no',
  StoreId: props.settings?.StoreId || '',
});

const steadfastForm = useForm({
  steadfast_api_key: props.settings?.steadfast_api_key || '',
  steadfast_secret_key: props.settings?.steadfast_secret_key || '',
  steadfast_is_enabled: props.settings?.steadfast_is_enabled || 'no',
});

const submitPathao = () => {
  pathaoForm.post(route('admin.courier.settings.store'), {
    onSuccess: () => {
      toast.success('Pathao settings saved successfully!');
    },
    onError: (errors) => {
      toast.error('Failed to save Pathao settings.');
      console.error('Pathao errors:', errors);
    },
  });
};

const submitSteadfast = () => {
  steadfastForm.post(route('admin.courier.settings.store'), {
    onSuccess: () => {
      toast.success('Steadfast settings saved successfully!');
    },
    onError: (errors) => {
      toast.error('Failed to save Steadfast settings.');
      console.error('Steadfast errors:', errors);
    },
  });
};
</script>

<template>
  <Head title="Courier Settings" />
  <AdminLayout>
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
      <!-- Pathao Settings -->
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

      <!-- Steadfast Settings -->
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