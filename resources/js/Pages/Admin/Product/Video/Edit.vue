<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { Search, ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps({
    video: Object,
    products: Array
});

const form = useForm({
    _method: 'PUT',
    product_id: props.video.product_id,
    title: props.video.title,
    video_file: null,
});

const searchQuery = ref('');
const isDropdownOpen = ref(false);

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    return props.products.filter(product =>
        product.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const selectedProduct = computed(() => {
    return props.products.find(p => p.id === form.product_id);
});

const selectProduct = (product) => {
    form.product_id = product.id;
    searchQuery.value = product.name;
    isDropdownOpen.value = false;
};

const submit = () => {
    form.post(route('admin.product-videos.update', props.video.id));
};
</script>

<template>
    <Head title="Edit Product Video" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold font-montserrat">Edit Product Video</h1>
                <Link :href="route('admin.product-videos.index')" class="btn btn-ghost btn-sm">
                    Back
                </Link>
            </div>

            <div class="max-w-2xl bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Product Selection with Search and Image -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Select Product</label>
                        <div class="relative">
                            <div
                                @click="isDropdownOpen = !isDropdownOpen"
                                class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-primary-400 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <template v-if="selectedProduct">
                                        <img :src="selectedProduct.feature_image" class="w-8 h-8 rounded-lg object-cover" />
                                        <span class="text-sm font-medium">{{ selectedProduct.name }}</span>
                                    </template>
                                    <span v-else class="text-sm text-gray-500">Choose a product...</span>
                                </div>
                                <ChevronDown class="w-4 h-4 text-gray-400" :class="{ 'rotate-180': isDropdownOpen }" />
                            </div>

                            <div v-if="isDropdownOpen" class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Search products..."
                                            class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border-none rounded-lg focus:ring-1 focus:ring-primary-500"
                                            @click.stop
                                        />
                                    </div>
                                </div>
                                <ul class="max-h-64 overflow-y-auto">
                                    <li
                                        v-for="product in filteredProducts"
                                        :key="product.id"
                                        @click="selectProduct(product)"
                                        class="flex items-center justify-between px-4 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/20 cursor-pointer transition-colors"
                                    >
                                        <div class="flex items-center gap-3">
                                            <img :src="product.feature_image" class="w-10 h-10 rounded-lg object-cover" />
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ product.name }}</p>
                                            </div>
                                        </div>
                                        <Check v-if="form.product_id === product.id" class="w-4 h-4 text-primary-600" />
                                    </li>
                                    <li v-if="filteredProducts.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 italic">
                                        No products found
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div v-if="form.errors.product_id" class="text-red-500 text-xs mt-1">{{ form.errors.product_id }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Video Title</label>
                        <input v-model="form.title" type="text" class="input input-bordered w-full rounded-xl" required />
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Upload New Video (Leave blank to keep current)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-xl hover:border-primary-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="video-upload" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                        <span>Upload a file</span>
                                        <input id="video-upload" name="video-upload" type="file" class="sr-only" @input="form.video_file = $event.target.files[0]" accept="video/*">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">MP4, MOV up to 20MB</p>
                                <div v-if="form.video_file" class="mt-2 text-sm font-medium text-primary-600 truncate">
                                    {{ form.video_file.name }}
                                </div>
                                <div v-else-if="video.video" class="mt-2 text-sm text-gray-500 italic">
                                    Current: {{ video.video.split('/').pop() }}
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.video_file" class="text-red-500 text-xs mt-1">{{ form.errors.video_file }}</div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="btn btn-primary px-8 rounded-xl" :disabled="form.processing">
                            <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2"></span>
                            Update Video
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
