<template>
    <Head title="Add Instagram Feed" />
    <AdminLayout>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Add New Instagram Feed
                    </h2>
                    <Link
                        :href="route('admin.instagram-feeds.index')"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 active:bg-gray-700 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-md"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to List
                    </Link>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                    <form @submit.prevent="submit" class="p-8">
                        <div class="space-y-6">
                            <!-- Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Feed Image</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-xl transition-all duration-200"
                                     :class="[form.errors.image ? 'border-red-300 bg-red-50 dark:bg-red-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400']">
                                    <div class="space-y-1 text-center">
                                        <div v-if="previewUrl" class="mb-4">
                                            <img :src="previewUrl" class="mx-auto h-48 w-48 object-cover rounded-lg shadow-md ring-1 ring-gray-200 dark:ring-gray-700" />
                                            <button 
                                                type="button" 
                                                @click="removeImage"
                                                class="mt-2 text-xs text-red-500 font-medium hover:underline flex items-center justify-center mx-auto"
                                            >
                                                <X class="w-3 h-3 mr-1" /> Remove
                                            </button>
                                        </div>
                                        <div v-else class="flex flex-col items-center">
                                            <ImageIcon class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                                <label class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                                    <span>Upload a file</span>
                                                    <input type="file" @change="handleFileChange" class="sr-only" accept="image/*">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">PNG, JPG, WEBP up to 2MB</p>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                            </div>

                            <!-- Link -->
                            <div>
                                <label for="link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instagram Link</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Instagram class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input 
                                        type="url" 
                                        id="link" 
                                        v-model="form.link"
                                        class="block w-full pl-10 sm:text-sm border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        placeholder="https://www.instagram.com/p/..."
                                    >
                                </div>
                                <p v-if="form.errors.link" class="mt-1 text-sm text-red-600">{{ form.errors.link }}</p>
                            </div>

                            <!-- Sort Order & Status Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                                    <input 
                                        type="number" 
                                        id="sort_order" 
                                        v-model="form.sort_order"
                                        class="block w-full sm:text-sm border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    >
                                    <p v-if="form.errors.sort_order" class="mt-1 text-sm text-red-600">{{ form.errors.sort_order }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Status</label>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" value="1" v-model="form.status" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" value="0" v-model="form.status" class="w-4 h-4 text-gray-300 border-gray-300 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Inactive</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl shadow-lg text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-all duration-200 shadow-blue-500/20"
                                >
                                    <span v-if="form.processing">Saving...</span>
                                    <span v-else class="flex items-center"><Save class="w-5 h-5 mr-2" /> Save Instagram Feed</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ImageIcon, X, Instagram, Save, ArrowLeft } from 'lucide-vue-next';

const form = useForm({
    image: null,
    link: '',
    status: 1,
    sort_order: 0,
});

const previewUrl = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    previewUrl.value = null;
};

const submit = () => {
    form.post(route('admin.instagram-feeds.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>
