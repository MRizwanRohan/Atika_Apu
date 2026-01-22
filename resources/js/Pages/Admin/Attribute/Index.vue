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
            const isColor = nameLower === "color" || nameLower === "colorus";
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
    return name === "color" || name === "colorus";
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
    <Head title="Create Attribute" />
    <AdminLayout>
        <div class="w-full mx-auto p-4">
            <div class="grid lg:grid-cols-2 grid-cols-1 gap-5">
                <div class="create_attr">
                    <form
                        @submit.prevent="submitForm"
                        class="mx-auto bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4"
                    >
                        <h1 class="text-2xl font-bold mb-4">
                            {{
                                isEditing
                                    ? "Edit Attribute"
                                    : "Create Attribute"
                            }}
                        </h1>
                        <div class="mb-4">
                            <label
                                for="name"
                                class="block text-gray-700 text-sm font-bold mb-2"
                                >Attribute Name</label
                            >
                            <input
                                v-model="form.name"
                                id="name"
                                type="text"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                placeholder="Enter attribute name"
                                required
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-red-500 text-xs mt-1"
                                >{{ form.errors.name }}</span
                            >
                        </div>

                        <div
                            v-for="(value, index) in form.values"
                            :key="index"
                            class="mb-4 relative"
                        >
                            <label
                                :for="'value_' + index"
                                class="block text-gray-700 text-sm font-bold mb-2"
                                >Value {{ index + 1 }}</label
                            >
                            <div class="flex gap-2">
                                <input
                                    v-model="form.values[index].value"
                                    :id="'value_' + index"
                                    type="text"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="Enter value"
                                    required
                                />
                                <input
                                    v-if="isColorAttribute"
                                    v-model="form.values[index].color"
                                    type="color"
                                    class="h-[38px] w-[50px] p-1 border rounded cursor-pointer"
                                    title="Choose Color"
                                />
                            </div>
                            <span
                                v-if="form.errors[`values.${index}.value`]"
                                class="text-red-500 text-xs mt-1 block"
                            >
                                {{ form.errors[`values.${index}.value`] }}
                            </span>
                            <div class="absolute top-[26px] -right-[38px]">
                                <button
                                    type="button"
                                    @click="form.values.splice(index, 1)"
                                    class="text-red-500 hover:text-red-700 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="size-6"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18 18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div
                            class="flex flex-col lg:flex-row items-center justify-between"
                        >
                            <button
                                type="button"
                                @click="addValue"
                                class="text-blue-500 hover:text-blue-700 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                + Add More Values
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="btn btn-primary"
                            >
                                {{ isEditing ? "Update" : "Submit" }}
                            </button>
                        </div>
                    </form>
                </div>
                <div class="attr_list_wrapper">
                    <div class="attr_list p-5 bg-white shadow-md rounded">
                        <h1 class="text-2xl font-bold mb-4">Attribute List</h1>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Values</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="attr in attrList" :key="attr.id">
                                        <td>{{ attr.name }}</td>
                                        <td>
                                            <ul class="flex items-center gap-2">
                                                <li
                                                    v-for="value in attr.values"
                                                    :key="value.id"
                                                    class="px-2 py-1 text-xs rounded-full bg-blue-500 text-white"
                                                >
                                                    <span
                                                        v-if="value.color"
                                                        class="w-3 h-3 rounded-full inline-block mr-1 border border-white"
                                                        :style="{
                                                            backgroundColor:
                                                                value.color,
                                                        }"
                                                    ></span>
                                                    {{ value.value }}
                                                </li>
                                            </ul>
                                        </td>
                                        <td>
                                            <div
                                                class="flex justify-end space-x-2 relative"
                                            >
                                                <!-- Delete Button -->
                                                <div class="relative group">
                                                    <button
                                                        @click="
                                                            openDeleteModal(
                                                                attr.id
                                                            )
                                                        "
                                                        class="table_delete_action hover:bg-red-500 hover:text-white p-2 rounded"
                                                    >
                                                        <Trash2Icon size="20" />
                                                    </button>
                                                    <span
                                                        class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                                                    >
                                                        Delete
                                                    </span>
                                                </div>

                                                <!-- Edit Link -->
                                                <div class="relative group">
                                                    <button
                                                        @click="
                                                            editAttr(attr.id)
                                                        "
                                                        class="table_edit_action hover:bg-green-500 hover:text-white p-2 rounded"
                                                    >
                                                        <SquarePen size="20" />
                                                    </button>
                                                    <span
                                                        class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                                                    >
                                                        Edit
                                                    </span>
                                                </div>
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
        <DeleteModal
            :item-id="selectedAttributeId"
            item-name="Attribute"
            route-name="admin.attributes.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </AdminLayout>
</template>
