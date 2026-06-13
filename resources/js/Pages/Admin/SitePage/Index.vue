<template>
    <Head>
        <title>Site Pages</title>
    </Head>
    <AdminLayout>
        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">Site Pages</h1>

                <Link
                    :href="route('admin.site-pages.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full sm:w-auto text-center"
                >
                    Create New Page
                </Link>
            </div>

            <div
                v-if="$page.props.flash && $page.props.flash.success"
                class="mb-4 p-4 bg-green-100 text-green-700"
            >
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 sm:px-6 text-left whitespace-nowrap text-sm sm:text-base">Name</th>
                            <th class="px-4 py-3 sm:px-6 text-left whitespace-nowrap text-sm sm:text-base">Slug</th>
                            <th class="px-4 py-3 sm:px-6 text-left whitespace-nowrap text-sm sm:text-base">Title</th>
                            <th class="px-4 py-3 sm:px-6 text-left whitespace-nowrap text-sm sm:text-base">Status</th>
                            <th class="px-4 py-3 sm:px-6 text-left whitespace-nowrap text-sm sm:text-base">Created At</th>
                            <th class="px-4 py-3 sm:px-6 text-right whitespace-nowrap text-sm sm:text-base">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="page in pages.data"
                            :key="page.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">{{ page.name }}</td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">{{ page.slug }}</td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">{{ page.title || "-" }}</td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">
                                <select
                                    name="status"
                                    :id="'status-' + page.id"
                                    :value="page.status"
                                    @change="updateStatus(page.id, $event.target.value)"
                                    class="text-sm sm:text-base p-1 border rounded"
                                    :class="page.status == 1 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'"
                                >
                                    <option :value="1">Active</option>
                                    <option :value="0">Inactive</option>
                                </select>
                            </td>

                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm sm:text-base">
                                {{ new Date(page.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-sm sm:text-base">
                                <Link
                                    :href="route('admin.site-pages.edit', page.id)"
                                    class="text-blue-600 hover:text-blue-900 mr-4"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="confirmDelete(page.id)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                <template v-for="(link, index) in pages.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-4 py-2 mx-1 rounded"
                        :class="{ 'bg-blue-500 text-white': link.active }"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="px-4 py-2 mx-1 rounded text-gray-400 cursor-not-allowed"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";

defineProps({
    pages: Object,
});

const confirmDelete = (id) => {
    if (confirm("Are you sure you want to delete this page?")) {
        router.delete(route("admin.site-pages.destroy", id), {
            onSuccess: () => {
                toast.success("Page deleted successfully!");
            },
            onError: () => {
                toast.error("Failed to delete page.");
            },
        });
    }
};

const updateStatus = (id, status) => {
    router.put(
        route("admin.site-pages.updateStatus", id),
        { status: parseInt(status) },
        {
            onSuccess: () => {
                toast.success("Page status updated successfully!");
            },
            onError: () => {
                toast.error("Failed to update page status.");
            },
        }
    );
};
</script>