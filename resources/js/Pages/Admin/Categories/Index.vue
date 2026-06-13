<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { defineProps, ref, watch } from "vue";
import { Link, Head } from "@inertiajs/vue3";
import CategoryTree from "@/Components/Categories/CategoryTree.vue";

const props = defineProps({
    categories: Array,
});

// Local reactive state for categories
const localCategories = ref([...props.categories]);

// Watch for changes in props.categories (refreshes after Inertia requests/deletions)
watch(
    () => props.categories,
    (newCategories) => {
        localCategories.value = [...newCategories];
    },
    { deep: true }
);

// Function to update category status recursively
const updateCategoryStatus = (categoryArray, id, status) => {
    return categoryArray.map((category) => {
        if (category.id === id) {
            return { ...category, status };
        }
        if (category.children && category.children.length) {
            return {
                ...category,
                children: updateCategoryStatus(category.children, id, status),
            };
        }
        return category;
    });
};

// Function to update category order recursively
const updateCategoryOrder = (categoryArray, id, order) => {
    return categoryArray.map((category) => {
        if (category.id === id) {
            return { ...category, order };
        }
        if (category.children && category.children.length) {
            return {
                ...category,
                children: updateCategoryOrder(category.children, id, order),
            };
        }
        return category;
    });
};

// Handle status update event from CategoryTree
const handleStatusUpdate = ({ id, status }) => {
    localCategories.value = updateCategoryStatus(
        localCategories.value,
        id,
        status
    );
};

// Handle order update event from CategoryTree
const handleOrderUpdate = ({ id, order }) => {
    localCategories.value = updateCategoryOrder(
        localCategories.value,
        id,
        order
    );
};
</script>

<template>
    <Head title="Categories" />
    <AdminLayout>
        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-5">
            <div
                class="mb-4 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 sm:text-xl">
                        Categories
                    </h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Simple mobile-friendly category tree
                    </p>
                </div>
                <Link
                    :href="route('admin.categories.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Add Category
                </Link>
            </div>

            <div v-if="localCategories.length" class="space-y-3">
                <div
                    class="mb-2 hidden items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-600 md:flex"
                >
                    <div class="flex-1">Category Details</div>
                    <div class="w-32 text-center">Status</div>
                    <div class="w-24 text-center">Order</div>
                    <div class="w-40 text-right">Actions</div>
                </div>

                <div class="category-tree-container">
                    <CategoryTree
                        v-for="category in localCategories"
                        :key="category.id"
                        :category="category"
                        :level="0"
                        @status-updated="handleStatusUpdate"
                        @order-updated="handleOrderUpdate"
                    />
                </div>
            </div>
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50 py-16"
            >
                <div class="text-gray-400 mb-4">
                    <svg
                        class="w-16 h-16"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        />
                    </svg>
                </div>
                <p class="text-center text-base font-medium text-gray-500">
                    No categories found yet.
                </p>
                <Link
                    :href="route('admin.categories.create')"
                    class="mt-4 text-sm font-semibold text-slate-900 transition hover:text-slate-700"
                >
                    Create your first category →
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.category-tree-container {
    @apply overflow-hidden rounded-xl border border-gray-200 bg-white;
}
</style>
