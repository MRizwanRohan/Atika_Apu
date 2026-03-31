<template>
    <Head title="Instagram Feed" />
    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header Component inside main slot -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Instagram Feed Management
                    </h2>
                    <Link
                        :href="route('admin.instagram-feeds.create')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg shadow-blue-500/30"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        Add New Feed
                    </Link>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-gray-400 dark:text-gray-500 text-sm uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                                        <th class="px-4 py-4 font-medium w-10"></th>
                                        <th class="px-4 py-4 font-medium">Image</th>
                                        <th class="px-4 py-4 font-medium">Link</th>
                                        <th class="px-4 py-4 font-medium">Sort Order</th>
                                        <th class="px-4 py-4 font-medium text-center">Status</th>
                                        <th class="px-4 py-4 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <draggable 
                                    v-model="localFeeds" 
                                    tag="tbody" 
                                    item-key="id" 
                                    handle=".drag-handle"
                                    @end="onDragEnd"
                                    class="divide-y divide-gray-100 dark:divide-gray-700"
                                >
                                    <template #item="{ element }">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                                            <td class="px-4 py-4">
                                                <div class="drag-handle cursor-move text-gray-300 group-hover:text-gray-500 transition-colors">
                                                    <GripVertical class="w-5 h-5" />
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center">
                                                    <div class="h-16 w-16 flex-shrink-0">
                                                        <img v-if="element.image" :src="element.image" class="h-16 w-16 rounded-lg object-cover shadow-sm ring-1 ring-gray-100 dark:ring-gray-700" alt="" />
                                                        <div v-else class="h-16 w-16 bg-gray-100 dark:bg-gray-700 flex items-center justify-center rounded-lg border border-gray-100 dark:border-gray-600">
                                                            <Instagram class="w-8 h-8 text-gray-300 dark:text-gray-500" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <a :href="element.link" target="_blank" class="text-blue-500 hover:text-blue-600 text-sm truncate max-w-xs block font-medium">
                                                    {{ element.link || '---' }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="inline-flex items-center px-2 py-1 bg-gray-50 dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800 font-mono text-xs">
                                                    {{ element.sort_order }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <button 
                                                    @click="toggleStatus(element.id)"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-all duration-200"
                                                    :class="element.status ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="element.status ? 'bg-green-500' : 'bg-red-500'"></span>
                                                    {{ element.status ? 'Active' : 'Inactive' }}
                                                </button>
                                            </td>
                                            <td class="px-4 py-4 text-right">
                                                <div class="flex items-center justify-end space-x-1">
                                                    <Link
                                                        :href="route('admin.instagram-feeds.edit', element.id)"
                                                        class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                        title="Edit"
                                                    >
                                                        <Edit class="w-5 h-5" />
                                                    </Link>
                                                    <button
                                                        @click="openDeleteModal(element.id)"
                                                        class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                        title="Delete"
                                                    >
                                                        <Trash2 class="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </draggable>
                                <tbody v-if="localFeeds.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col items-center">
                                                <Instagram class="w-12 h-12 mb-3 opacity-20" />
                                                <p>No instagram feeds found. Start by adding one!</p>
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

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-if="selectedId !== null"
            :item-id="selectedId"
            item-name="Instagram Feed"
            route-name="admin.instagram-feeds.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleted"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteModal from '@/Components/Modal/DeleteModal.vue';
import { Plus, Edit, Trash2, Instagram, GripVertical } from 'lucide-vue-next';
import draggable from 'vuedraggable';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
    feeds: Array,
});

const localFeeds = ref([...props.feeds]);

watch(() => props.feeds, (newVal) => {
    localFeeds.value = [...newVal];
}, { deep: true });

const onDragEnd = () => {
    // Update local order indices visually for immediate feedback
    localFeeds.value.forEach((item, i) => {
        item.sort_order = i;
    });
    
    // Save new order
    saveOrder();
};

const saveOrder = () => {
    const ids = localFeeds.value.map(item => item.id);
    
    router.post(route('admin.instagram-feeds.reorder'), { ids }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Order updated successfully');
        },
        onError: () => {
            toast.error('Failed to update order');
        }
    });
};

const showDeleteModal = ref(false);
const selectedId = ref(null);

watch(() => showDeleteModal.value, (isVisible) => {
    if (!isVisible) {
        setTimeout(() => {
            selectedId.value = null;
        }, 300);
    }
});

const openDeleteModal = (id) => {
    selectedId.value = id;
    showDeleteModal.value = true;
};

const handleDeleted = () => {
    showDeleteModal.value = false;
    selectedId.value = null;
};

const toggleStatus = (id) => {
    router.put(route('instagram-feeds.toggleStatus', id), {}, {
        preserveScroll: true,
    });
};
</script>
