<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { 
    MessageSquare, 
    Settings, 
    Plus, 
    Trash2, 
    Save, 
    Smartphone, 
    FileText,
    HelpCircle,
    CheckCircle2,
    XCircle,
    RefreshCw,
    ShieldCheck
} from "lucide-vue-next";

const props = defineProps({
    settings: Object,
    templates: Array,
    balance: [String, Number],
});

const settingsForm = useForm({
    api_key: props.settings?.api_key || "",
    sender_id: props.settings?.sender_id || "",
    is_enabled: props.settings?.is_enabled ?? false,
    order_create_sms_enabled: props.settings?.order_create_sms_enabled ?? false,
    order_create_sms_template: props.settings?.order_create_sms_template || "",
});

const templateForm = useForm({
    name: "",
    content: "",
});

const editTemplateForm = useForm({
    id: null,
    name: "",
    content: "",
});

const showAddTemplate = ref(false);
const editingTemplateId = ref(null);

const saveSettings = () => {
    settingsForm.post(route('admin.settings.sms.update'), {
        preserveScroll: true,
    });
};

const addTemplate = () => {
    templateForm.post(route('admin.settings.sms.templates.store'), {
        onSuccess: () => {
            showAddTemplate.value = false;
            templateForm.reset();
        },
    });
};

const startEditTemplate = (template) => {
    editingTemplateId.value = template.id;
    editTemplateForm.id = template.id;
    editTemplateForm.name = template.name;
    editTemplateForm.content = template.content;
};

const updateTemplate = () => {
    editTemplateForm.put(route('admin.settings.sms.templates.update', editTemplateForm.id), {
        onSuccess: () => {
            editingTemplateId.value = null;
        },
    });
};

const deleteTemplate = (id) => {
    if (confirm("Are you sure you want to delete this template?")) {
        useForm({}).delete(route('admin.settings.sms.templates.destroy', id), {
            preserveScroll: true,
        });
    }
};

const refreshBalance = () => {
    window.location.reload();
};
</script>

<template>
    <Head title="SMS Settings" />

    <AdminLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header & Balance -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <MessageSquare class="w-6 h-6 text-blue-600" />
                        SMS Configuration
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage SMS gateway and notification templates</p>
                </div>
                
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-4 rounded-2xl shadow-lg text-white flex items-center gap-4 min-w-[240px]">
                    <div class="p-3 bg-white/10 rounded-xl">
                        <Smartphone class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-blue-100 font-medium uppercase tracking-wider">SMS Balance</p>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-bold">৳ {{ balance }}</h2>
                            <button @click="refreshBalance" class="p-1 hover:bg-white/20 rounded-full transition-colors">
                                <RefreshCw class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Global Settings -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                            <Settings class="w-5 h-5 text-gray-400" />
                            <h3 class="font-bold text-gray-900 dark:text-white">Gateway Settings</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700">
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">BulkSMSBD Service</p>
                                    <p class="text-xs text-gray-500">Enable or disable all SMS features</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="settingsForm.is_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">SMS API Key</label>
                                <div class="relative">
                                    <ShieldCheck class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input 
                                        type="password" 
                                        v-model="settingsForm.api_key"
                                        placeholder="Enter your API Key"
                                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Sender ID</label>
                                <div class="relative">
                                    <Smartphone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input 
                                        type="text" 
                                        v-model="settingsForm.sender_id"
                                        placeholder="e.g. 8809612..."
                                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <button 
                                @click="saveSettings"
                                :disabled="settingsForm.processing"
                                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-lg shadow-blue-500/20 disabled:opacity-50"
                            >
                                <Save class="w-4 h-4" />
                                Save Gateway Configuration
                            </button>
                        </div>
                    </div>

                    <!-- Order Notification Toggle -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="w-5 h-5 text-emerald-500" />
                                <h3 class="font-bold text-gray-900 dark:text-white">Order Creation SMS</h3>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="settingsForm.order_create_sms_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Automatically send an SMS to customers when they place a new order.</p>
                    </div>
                </div>

                <!-- Template Management -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Notification Template -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <FileText class="w-5 h-5 text-blue-600" />
                                <h3 class="font-bold text-gray-900 dark:text-white">Order SMS Template</h3>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="space-y-1.5">
                                <textarea 
                                    v-model="settingsForm.order_create_sms_template"
                                    rows="4" 
                                    class="w-full p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none font-medium"
                                    placeholder="Enter message template..."
                                ></textarea>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span class="text-[10px] px-2 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-md border border-blue-100 dark:border-blue-800 font-bold uppercase tracking-tighter"> {name} </span>
                                    <span class="text-[10px] px-2 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-md border border-blue-100 dark:border-blue-800 font-bold uppercase tracking-tighter"> {order_number} </span>
                                    <span class="text-[10px] px-2 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-md border border-blue-100 dark:border-blue-800 font-bold uppercase tracking-tighter"> {total} </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk SMS Templates -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <MessageSquare class="w-5 h-5 text-indigo-600" />
                                <h3 class="font-bold text-gray-900 dark:text-white">Bulk SMS Templates</h3>
                            </div>
                            <button 
                                @click="showAddTemplate = !showAddTemplate"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-xs font-bold border border-indigo-100 dark:border-indigo-800 hover:bg-indigo-600 hover:text-white transition-all"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                Add New
                            </button>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <!-- Add Template Form -->
                            <div v-if="showAddTemplate" class="p-5 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-900/50 rounded-2xl space-y-4 animate-in fade-in zoom-in-95">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5 md:col-span-2">
                                        <label class="text-xs font-bold text-gray-500 uppercase">Template Name</label>
                                        <input v-model="templateForm.name" type="text" placeholder="e.g. Special Offer" class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm outline-none" />
                                    </div>
                                    <div class="space-y-1.5 md:col-span-2">
                                        <label class="text-xs font-bold text-gray-500 uppercase">Message Content</label>
                                        <textarea v-model="templateForm.content" rows="3" class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm outline-none resize-none"></textarea>
                                        <p class="text-[10px] text-gray-400 font-medium">Use {name}, {orders_count}, {total_spent} for dynamic values.</p>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button @click="showAddTemplate = false" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700">Cancel</button>
                                    <button @click="addTemplate" :disabled="templateForm.processing" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold shadow-lg shadow-indigo-500/20 disabled:opacity-50">Create Template</button>
                                </div>
                            </div>

                            <!-- Template List -->
                            <div v-if="templates.length > 0" class="space-y-3">
                                <div v-for="template in templates" :key="template.id" class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:border-blue-200 dark:hover:border-blue-900/50 transition-all">
                                    <div v-if="editingTemplateId !== template.id">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-bold text-gray-900 dark:text-white">{{ template.name }}</h4>
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button @click="startEditTemplate(template)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-all">
                                                    <Settings class="w-4 h-4" />
                                                </button>
                                                <button @click="deleteTemplate(template.id)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-all">
                                                    <Trash2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ template.content }}</p>
                                    </div>

                                    <!-- Edit Mode -->
                                    <div v-else class="space-y-4">
                                        <input v-model="editTemplateForm.name" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm outline-none" />
                                        <textarea v-model="editTemplateForm.content" rows="3" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm outline-none resize-none"></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button @click="editingTemplateId = null" class="px-3 py-1.5 text-xs font-bold text-gray-500">Cancel</button>
                                            <button @click="updateTemplate" :disabled="editTemplateForm.processing" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold">Update</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else-if="!showAddTemplate" class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <FileText class="w-12 h-12 mb-3 opacity-20" />
                                <p class="text-sm font-medium">No templates created yet</p>
                                <p class="text-xs opacity-60">Add templates for quick bulk messaging</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
