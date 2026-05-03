<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";

const props = defineProps({
    districts: Array,
    settings: Object,
});

const searchQuery = ref("");
const filteredDistricts = computed(() => {
    return props.districts.filter((d) =>
        d.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (d.bn_name && d.bn_name.includes(searchQuery.value))
    );
});

// Global Settings Form
const settingsForm = useForm({
    shipping_charge_inside_dhaka: props.settings?.shipping_charge_inside_dhaka || 0,
    shipping_charge_outside_dhaka: props.settings?.shipping_charge_outside_dhaka || 0,
});

const saveGlobalSettings = () => {
    settingsForm.post(route('admin.settings.delivery-charges.update-settings'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Global settings updated");
        },
    });
};

// Sync form if settings change
watch(() => props.settings, (newSettings) => {
    if (newSettings) {
        settingsForm.shipping_charge_inside_dhaka = newSettings.shipping_charge_inside_dhaka;
        settingsForm.shipping_charge_outside_dhaka = newSettings.shipping_charge_outside_dhaka;
    }
}, { deep: true });

// Thana Management State
const selectedDistrict = ref(null);
const thanas = ref([]);
const loadingThanas = ref(false);
const showThanaModal = ref(false);

const openThanaModal = async (district) => {
    selectedDistrict.ref = district;
    selectedDistrict.value = district;
    thanas.value = [];
    loadingThanas.value = true;
    showThanaModal.value = true;

    try {
        const response = await axios.get(route('admin.settings.delivery-charges.thanas', district.id));
        thanas.value = response.data;
    } catch (error) {
        toast.error("Failed to load thanas");
        showThanaModal.value = false;
    } finally {
        loadingThanas.value = false;
    }
};

// Charge Update Logic
const editingZoneId = ref(null);
const editForm = useForm({
    id: null,
    charge: null,
    is_active: true,
});

const startEdit = (zone) => {
    editingZoneId.value = zone.id;
    editForm.id = zone.id;
    editForm.charge = zone.charge;
    editForm.is_active = !!zone.is_active;
};

const saveEdit = (zone) => {
    editForm.post(route('admin.settings.delivery-charges.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editingZoneId.value = null;
            toast.success("Charge updated successfully");
            // If in modal, update the local thana object
            if (showThanaModal.value) {
                const index = thanas.value.findIndex(t => t.id === zone.id);
                if (index !== -1) {
                    thanas.value[index].charge = editForm.charge;
                    thanas.value[index].is_active = editForm.is_active;
                }
            }
        },
    });
};

// Bulk Update
const bulkForm = useForm({
    district_id: null,
    charge: null,
});

const applyBulkCharge = () => {
    bulkForm.district_id = selectedDistrict.value.id;
    bulkForm.post(route('admin.settings.delivery-charges.bulk-update'), {
        preserveScroll: true,
        onSuccess: () => {
             // Refresh thanas list
             openThanaModal(selectedDistrict.value);
             toast.success("Bulk update applied");
        },
    });
};

</script>

<template>
    <Head title="Delivery Charges" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Location-Based Delivery Charges</h1>
                    <p class="text-gray-500 mt-1">Set specific delivery rates for Districts and Thanas across Bangladesh.</p>
                </div>
                
                <div class="relative max-w-sm w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search district..."
                        class="pl-10 w-full rounded-xl border-gray-200 focus:border-primary focus:ring-primary transition-all shadow-sm"
                    />
                </div>
            </div>

            <!-- Global Default Charges Section -->
            <div class="bg-gradient-to-br from-white to-gray-50/50 p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 animate-in fade-in slide-in-from-top-4 duration-500">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-lg text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">Global Default Charges</h2>
                </div>
                
                <form @submit.prevent="saveGlobalSettings" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-600 block">Inside Dhaka (Default)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">৳</span>
                            <input 
                                v-model="settingsForm.shipping_charge_inside_dhaka" 
                                type="number" 
                                class="pl-8 w-full rounded-xl border-gray-200 focus:border-primary focus:ring-primary transition-all shadow-sm text-sm py-2.5"
                                placeholder="e.g. 60"
                            />
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-600 block">Outside Dhaka (Default)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">৳</span>
                            <input 
                                v-model="settingsForm.shipping_charge_outside_dhaka" 
                                type="number" 
                                class="pl-8 w-full rounded-xl border-gray-200 focus:border-primary focus:ring-primary transition-all shadow-sm text-sm py-2.5"
                                placeholder="e.g. 120"
                            />
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="settingsForm.processing"
                            class="w-full bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <svg v-if="settingsForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Save Global Defaults
                        </button>
                    </div>
                </form>
                
                <p class="mt-4 text-xs text-info flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Note: These values are used as fallbacks when no specific rate is set for a District or Thana below.</span>
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-700">District</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-700">Default Charge (BDT)</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-700 text-center">Custom Thanas</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-700 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="district in filteredDistricts" :key="district.id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900">{{ district.name }}</span>
                                        <span class="text-xs text-gray-400">{{ district.bn_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="editingZoneId === district.id">
                                        <div class="flex items-center gap-2">
                                            <input 
                                                v-model="editForm.charge" 
                                                type="number" 
                                                class="w-24 rounded-lg border-gray-200 text-sm py-1"
                                                @keyup.enter="saveEdit(district)"
                                            />
                                            <button @click="saveEdit(district)" class="text-primary hover:text-primary/80">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <button @click="editingZoneId = null" class="text-gray-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="flex items-center gap-2 group-hover:scale-105 transition-transform origin-left cursor-pointer" @click="startEdit(district)">
                                            <span class="text-gray-900 font-semibold">{{ district.charge || 'Global Default' }}</span>
                                            <svg class="w-4 h-4 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </div>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-50 text-blue-700 text-xs px-2.5 py-0.5 rounded-full font-medium">
                                        {{ district.custom_thanas_count }} Thanas
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                     <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="district.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ district.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="openThanaModal(district)"
                                        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-all shadow-sm"
                                    >
                                        Manage Thanas
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Thana Management Modal -->
        <div v-if="showThanaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-300">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-300">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-primary/5">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Thanas in {{ selectedDistrict?.name }}</h2>
                        <p class="text-sm text-gray-500">Override delivery charges for specific areas.</p>
                    </div>
                    <button @click="showThanaModal = false" class="p-2 rounded-full hover:bg-gray-100 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 bg-gray-50">
                    <div class="bg-white p-4 rounded-xl border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            <strong>Bulk Update:</strong> Apply a single rate to all thanas in this district.
                        </div>
                        <div class="flex items-center gap-2">
                             <input 
                                v-model="bulkForm.charge" 
                                type="number" 
                                placeholder="Enter amount"
                                class="w-32 rounded-lg border-gray-200 text-sm"
                            />
                            <button 
                                @click="applyBulkCharge"
                                :disabled="bulkForm.processing || !bulkForm.charge"
                                class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-all shadow-md"
                            >
                                Apply to All
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 pt-0">
                    <div v-if="loadingThanas" class="flex flex-col items-center justify-center py-20 gap-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                        <p class="text-gray-500 animate-pulse">Loading thanas hierarchy...</p>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="thana in thanas" 
                            :key="thana.id"
                            class="bg-white p-4 rounded-xl border border-gray-100 hover:border-primary/30 transition-all group"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ thana.name }}</div>
                                    <div class="text-xs text-gray-400">{{ thana.bn_name }}</div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <input 
                                        type="checkbox" 
                                        :checked="thana.is_active" 
                                        @change="startEdit(thana); editForm.is_active = $event.target.checked; saveEdit(thana)"
                                        class="rounded text-primary focus:ring-primary h-4 w-4"
                                    />
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Live</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <template v-if="editingZoneId === thana.id">
                                     <div class="flex items-center gap-1 w-full">
                                        <input 
                                            v-model="editForm.charge" 
                                            type="number" 
                                            class="w-full rounded-lg border-gray-200 text-sm py-1"
                                            @keyup.enter="saveEdit(thana)"
                                        />
                                        <button @click="saveEdit(thana)" class="p-1.5 bg-primary text-white rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                     </div>
                                </template>
                                <template v-else>
                                    <div 
                                        @click="startEdit(thana)"
                                        class="flex items-center justify-between w-full px-3 py-1.5 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
                                    >
                                        <span class="text-sm" :class="thana.charge ? 'text-gray-900 font-bold' : 'text-gray-400 italic font-normal'">
                                            {{ thana.charge ? thana.charge + ' BDT' : 'Inherit District' }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 text-right bg-gray-50">
                    <button @click="showThanaModal = false" class="px-6 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 font-semibold hover:bg-gray-50 transition-all shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.origin-left {
    transform-origin: left center;
}
</style>
