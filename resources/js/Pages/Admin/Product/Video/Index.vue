<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Trash2, Edit, Video } from 'lucide-vue-next';

const props = defineProps({
    videos: Array
});

const deleteVideo = (id) => {
    if (confirm('Are you sure you want to delete this video?')) {
        router.delete(route('admin.product-videos.destroy', id));
    }
};
</script>

<template>
    <Head title="Product Videos" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Product Videos</h1>
                <Link :href="route('admin.product-videos.create')" class="btn btn-primary">
                    Add Video
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Media</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="video in videos" :key="video.id">
                            <td class="px-6 py-4 whitespace-nowrap">{{ video.product?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ video.title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="video.video" class="flex items-center gap-2">
                                    <span class="badge badge-info">Uploaded</span>
                                    <a :href="video.video" target="_blank" class="text-blue-500"><Video class="w-4 h-4" /></a>
                                </div>
                                <div v-if="video.link" class="flex items-center gap-2">
                                    <span class="badge badge-success">Link</span>
                                    <a :href="video.link" target="_blank" class="text-blue-500"><Video class="w-4 h-4" /></a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                                <Link :href="route('admin.product-videos.edit', video.id)" class="text-indigo-600 hover:text-indigo-900">
                                    <Edit class="w-5 h-5" />
                                </Link>
                                <button @click="deleteVideo(video.id)" class="text-red-600 hover:text-red-900">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="videos.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No videos found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
