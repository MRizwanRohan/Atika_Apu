<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { useForm, router } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";
import { defineProps, ref, watch, computed } from "vue";
import { Trash2Icon, SquarePen } from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";

const props = defineProps({
    attrList: Array,
    editingAttribute: Object,
});

const showDeleteModal = ref(false);
const selectedAttributeId = ref(null);

const openDeleteModal = (attributeId) => {
    selectedAttributeId.value = attributeId;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    selectedAttributeId.value = null;
};

const isEditing = ref(false);

const form = useForm({
    name: "",
    values: [],
});

watch(
    () => props.editingAttribute,
    (newValue) => {
        if (newValue) {
            isEditing.value = true;
            form.name = newValue.name;
            const nameLower = (newValue.name || "").toString().trim().toLowerCase();
            const isColor = nameLower === "color" || nameLower === "colour" || nameLower === "colours" || nameLower === "colorus";
            form.values = newValue.values.map((v) => {
                if (isColor) {
                    return {
                        id: v.id,
                        value: v.value,
                        color: v.color || "#000000",
                    };
                } else {
                    return {
                        id: v.id,
                        value: v.value,
                    };
                }
            });
        } else {
            isEditing.value = false;
            form.reset();
        }
    },
    { immediate: true }
);

const isColorAttribute = computed(() => {
    const name = (form.name || "").toString().trim().toLowerCase();
    return name === "color" || name === "colour" || name === "colours" || name === "colorus";
});

// Keep values in sync when attribute name changes
watch(isColorAttribute, (newVal) => {
    if (newVal) {
        // ensure existing values have color
        form.values = form.values.map((v) => ({
            ...v,
            color: v.color || "#000000",
        }));
    } else {
        // remove color property to avoid sending unnecessary data
        form.values = form.values.map(({ color, ...rest }) => rest);
    }
});

const addValue = () => {
    if (isColorAttribute.value) {
        form.values.push({ value: "", color: "#000000" });
    } else {
        form.values.push({ value: "" });
    }
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route("admin.attributes.update", props.editingAttribute.id), {
            onSuccess: () => {
                toast.success("Attribute Updated");
                router.reload();
                form.reset();
            },
            onError: (errors) => {
                console.log("Form submission failed", errors);
            },
        });
    } else {
        form.post(route("admin.attributes.store"), {
            onSuccess: () => {
                toast.success("Attribute Created");
                router.reload();
                form.reset();
            },
            onError: (errors) => {
                console.log("Form submission failed", errors);
            },
        });
    }
};

const editAttr = (id) => {
    router.get(route("admin.attributes.edit", id));
};

// const deleteAttr = (id) => {
//   if (confirm('Are you sure you want to delete this attribute?')) {
//     router.delete(route('admin.attributes.destroy', id), {
//       onSuccess: () => {
//         toast.success('Attribute deleted successfully!');
//         router.reload();
//       },
//       onError: (errors) => {
//         console.log(errors);
//       },
//     });
//   }
// };
</script>

<template>
    <Head title="Attributes" />
    <AdminLayout>
        <div class="w-full mx-auto p-4 md:p-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Product Attributes
                        <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-indigo-100 dark:border-indigo-800">
                            {{ attrList.length }} Total
                        </span>
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">Manage product variations like size, color, etc.</p>
                </div>
                <div v-if="isEditing" class="flex gap-2">
                    <button 
                        @click="router.get(route('admin.attributes.index'))"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors uppercase tracking-tight"
                    >
                        Back to List
                    </button>
                </div>
            </div>

            <div class="grid lg:grid-cols-12 grid-cols-1 gap-6">
                <!-- Form Section -->
                <div class="lg:col-span-5">
                    <div class="sticky top-6">
                        <form
                            @submit.prevent="submitForm"
                            class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm rounded-2xl p-6"
                        >
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-6 uppercase tracking-wider flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                {{ isEditing ? "Update Attribute" : "Create New Attribute" }}
                            </h2>
                            
                            <div class="space-y-5">
                                <div>
                                    <label for="name" class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Attribute Name</label>
                                    <input
                                        v-model="form.name"
                                        id="name"
                                        type="text"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-gray-50/50 dark:bg-gray-900 text-gray-900 dark:text-white outline-none transition-all text-sm font-medium"
                                        placeholder="e.g. Size or Color"
                                        required
                                    />
                                    <span v-if="form.errors.name" class="text-red-500 text-[10px] font-bold mt-1.5 ml-1 block uppercase">{{ form.errors.name }}</span>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-center justify-between ml-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Attribute Values</label>
                                        <button
                                            type="button"
                                            @click="addValue"
                                            class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors uppercase tracking-widest"
                                        >
                                            + Add Value
                                        </button>
                                    </div>
                                    
                                    <div class="grid gap-3">
                                        <div
                                            v-for="(value, index) in form.values"
                                            :key="index"
                                            class="flex items-center gap-2 group relative"
                                        >
                                            <div class="relative flex-1">
                                                <input
                                                    v-model="form.values[index].value"
                                                    :id="'value_' + index"
                                                    type="text"
                                                    class="w-full pl-4 pr-10 py-2 rounded-xl border border-gray-200 dark:border-gray-700 focus:border-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white outline-none transition-all text-sm font-medium"
                                                    :placeholder="'Value ' + (index + 1)"
                                                    required
                                                />
                                                <div v-if="isColorAttribute" class="absolute right-2 top-1/2 -translate-y-1/2">
                                                    <input
                                                        v-model="form.values[index].color"
                                                        type="color"
                                                        class="w-6 h-6 p-0 border-none rounded-lg cursor-pointer bg-transparent"
                                                        title="Choose Color"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <button
                                                type="button"
                                                @click="form.values.splice(index, 1)"
                                                class="p-2 text-gray-300 hover:text-red-500 transition-colors"
                                            >
                                                <Trash2Icon size="18" />
                                            </button>

                                            <span
                                                v-if="form.errors[`values.${index}.value`]"
                                                class="absolute -bottom-4 left-4 text-red-500 text-[9px] font-bold uppercase"
                                            >
                                                {{ form.errors[`values.${index}.value`] }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div v-if="form.values.length === 0" class="py-10 border-2 border-dashed border-gray-100 dark:border-gray-700 rounded-2xl flex flex-col items-center justify-center text-gray-400">
                                        <p class="text-xs font-medium">No values added yet</p>
                                        <button type="button" @click="addValue" class="mt-2 text-[10px] font-bold text-indigo-500 uppercase tracking-widest hover:underline">Click to add</button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-3 bg-gray-900 dark:bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-black dark:hover:bg-indigo-700 transition-all shadow-lg shadow-gray-200 dark:shadow-none disabled:opacity-50 uppercase tracking-widest"
                                >
                                    {{ isEditing ? "Update Attribute" : "Create Attribute" }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- List Section -->
                <div class="lg:col-span-7">
                    <div class="flex items-center justify-between mb-4 px-2">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            Existing Attributes
                        </h2>
                    </div>
                    
                    <div class="grid gap-4">
                        <div 
                            v-for="attr in attrList" 
                            :key="attr.id" 
                            class="group bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-5 hover:border-indigo-200 dark:hover:border-indigo-900 transition-all hover:shadow-sm"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 flex items-center justify-center text-indigo-500 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/30 transition-colors">
                                        <div class="text-lg font-bold">{{ attr.name.charAt(0).toUpperCase() }}</div>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors">{{ attr.name }}</h3>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">#{{ attr.id }}</span>
                                            <div class="w-1 h-1 rounded-full bg-gray-300"></div>
                                            <span class="text-[10px] text-indigo-500/70 font-bold uppercase tracking-widest">{{ attr.values.length }} Values</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button
                                        @click="editAttr(attr.id)"
                                        class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all"
                                        title="Edit"
                                    >
                                        <SquarePen size="18" />
                                    </button>
                                    <button
                                        @click="openDeleteModal(attr.id)"
                                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-all"
                                        title="Delete"
                                    >
                                        <Trash2Icon size="18" />
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="value in attr.values"
                                    :key="value.id"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-xl text-[11px] font-bold border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 text-gray-600 dark:text-gray-300 transition-all hover:bg-white dark:hover:bg-gray-800 hover:border-indigo-200"
                                >
                                    <span
                                        v-if="value.color"
                                        class="w-3 h-3 rounded-full inline-block mr-2 border border-white shadow-sm"
                                        :style="{ backgroundColor: value.color }"
                                    ></span>
                                    {{ value.value }}
                                </div>
                                <div v-if="attr.values.length === 0" class="text-[10px] text-gray-400 font-medium italic">No values defined</div>
                            </div>
                        </div>

                        <div v-if="attrList.length === 0" class="py-16 bg-white dark:bg-gray-800 border border-dashed border-gray-200 dark:border-gray-700 rounded-2xl flex flex-col items-center justify-center">
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-3xl mb-4 text-gray-300">
                                <div class="w-12 h-12 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-2xl uppercase font-bold text-xs">?</div>
                            </div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">No attributes found</p>
                            <p class="text-[10px] text-gray-400 mt-1">Start by creating your first attribute on the left</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <DeleteModal
            :item-id="selectedAttributeId"
            item-name="Attribute"
            route-name="admin.attributes.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </AdminLayout>
</template>
