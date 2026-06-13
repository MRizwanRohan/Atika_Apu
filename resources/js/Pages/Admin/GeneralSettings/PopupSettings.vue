<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    popup_enabled: props.settings.popup_enabled === 1 || props.settings.popup_enabled === true,
    popup_image: props.settings.popup_image || '',
    popup_link: props.settings.popup_link || '',
    popup_image_file: null, // New field for file upload
});

const previewImage = ref(props.settings.popup_image || '');

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.popup_image_file = file;
        previewImage.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.settings.popup.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification handled by global toast if configured
        },
    });
};
</script>

<template>
    <Head title="Popup Settings" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Popup Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="flex items-center space-x-4">
                            <label class="font-medium text-gray-700">Enable Popup</label>
                            <input
                                type="checkbox"
                                v-model="form.popup_enabled"
                                class="toggle toggle-primary"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block font-medium text-gray-700">Popup Image</label>
                            <div class="flex flex-col gap-4">
                                <input
                                    type="file"
                                    @change="handleImageChange"
                                    accept="image/*"
                                    class="file-input file-input-bordered w-full"
                                />
                                <div class="flex gap-2 items-center">
                                    <span class="text-sm text-gray-500">Or Image URL:</span>
                                    <input
                                        v-model="form.popup_image"
                                        type="text"
                                        placeholder="Enter image URL"
                                        class="input input-bordered flex-1"
                                    />
                                </div>
                            </div>
                            <div v-if="previewImage || form.popup_image" class="mt-4">
                                <p class="text-sm text-gray-500 mb-2">Preview:</p>
                                <img :src="previewImage || form.popup_image" class="w-48 h-auto rounded border" alt="Popup Preview" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block font-medium text-gray-700">Popup Redirect Link</label>
                            <input
                                v-model="form.popup_link"
                                type="text"
                                placeholder="https://example.com/promo"
                                class="input input-bordered w-full"
                            />
                            <p class="text-sm text-gray-500">The link where users should be redirected when they click the popup image.</p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="loading loading-spinner"></span>
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
