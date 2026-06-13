<template>
    <Head title="Products" />
    <AdminLayout>
        <div class="w-full mx-auto">
            <div class="flex justify-between items-center mb-2 px-1">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-green-50 dark:bg-gray-800 text-green-700 dark:text-gray-400 rounded text-[10px] font-[600] tracking-tight border border-green-100 dark:border-gray-700">
                        {{ products.total }} PRODUCTS
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="exportCsv"
                        :disabled="isExporting"
                        class="h-8 px-3 flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-[11px] font-[600] tracking-tight hover:bg-gray-50 transition-all shadow-sm disabled:opacity-60"
                    >
                        <Download size="14" stroke-width="2.5" />
                        {{ isExporting ? "EXPORTING..." : "EXPORT CSV" }}
                    </button>
                    <Link
                        :href="route('admin.products.create')"
                        class="h-8 px-3 flex items-center gap-1.5 bg-gray-900 border border-gray-800 text-white rounded-lg text-[11px] font-[600] tracking-tight hover:bg-black transition-all shadow-sm"
                    >
                        <Plus size="14" stroke-width="3" />
                        CREATE
                    </Link>
                </div>
            </div>

            <!-- Controls: High Density -->
            <div class="bg-white dark:bg-gray-800 p-2 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mb-2 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <!-- Left: Filters -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-[600] text-gray-800 dark:text-gray-300 uppercase tracking-tighter">Show:</span>
                            <select
                                id="perPage"
                                v-model="perPage"
                                class="h-7 rounded border border-gray-200 dark:border-gray-600 pl-1.5 pr-6 py-0 text-[11px] font-bold bg-gray-50/50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-0 focus:border-blue-500 outline-none transition-all"
                                @change="handlePerPageChange"
                            >
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div> -->

                        <div class="h-4 w-px bg-gray-100 dark:bg-gray-600 mx-1 hidden sm:block"></div>

                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-[600] text-gray-800 dark:text-gray-300 uppercase tracking-tighter">Category:</span>
                            <select
                                id="categoryFilter"
                                v-model="selectedCategory"
                                class="h-7 rounded border border-gray-200 dark:border-gray-600 pl-2 pr-7 py-0 text-[11px] font-bold bg-gray-50/50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-0 focus:border-blue-500 outline-none transition-all"
                                @change="handleCategoryChange"
                            >
                                <option value="">ALL CATEGORIES</option>
                                <template v-for="category in categories" :key="category.id">
                                    <option :value="category.id">{{ category.name.toUpperCase() }}</option>
                                    <template v-if="category.children && category.children.length > 0">
                                        <option v-for="child in category.children" :key="child.id" :value="child.id">
                                            &nbsp;&nbsp; {{ child.name.toUpperCase() }}
                                        </option>
                                    </template>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Right: Search -->
                    <div class="relative flex-1 sm:max-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                            <Search class="h-3 w-3 text-gray-800 dark:text-gray-400" />
                        </div>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="SEARCH PRODUCTS..."
                            class="w-full h-7 pl-7 pr-3 rounded border border-gray-200 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-700 text-[11px] font-[600] text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition-all outline-none"
                            @input="debouncedSearch"
                        />
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div
                v-if="selectedProductIds.length > 0"
                class="mb-6 p-3 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl border border-indigo-100 dark:border-indigo-800 flex flex-col md:flex-row items-center justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="text-xs font-semibold text-indigo-600 tracking-tight"
                        >{{ selectedProductIds.length }} Selected</span
                    >
                    <input
                        v-model="confirmationInput"
                        type="text"
                        placeholder="Type 'confirm'"
                        class="px-2 py-1 text-xs rounded border border-indigo-200 outline-none focus:border-indigo-500 w-24"
                    />
                </div>
                <button
                    @click="deleteSelectedProducts"
                    :disabled="confirmationInput !== 'confirm' || isDeleting"
                    class="w-full md:w-auto px-4 py-1.5 bg-red-600 text-white rounded-lg font-medium text-xs disabled:opacity-50 transition-colors"
                >
                    {{ isDeleting ? "Wait..." : "Delete Selected" }}
                </button>
            </div>

            <!-- Card Grid -->
            <div class="space-y-2">
                <draggable
                    v-model="productList"
                    item-key="id"
                    handle=".drag-handle"
                    @end="onDragEnd"
                    ghost-class="opacity-50"
                >
                    <template #item="{ element: item }">
                        <div
                            @click="router.visit(route('admin.products.edit', item.id))"
                            class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-300 transition-all shadow-sm mb-1 cursor-pointer active:scale-[0.99]"
                        >
                            <div class="py-2 px-2 md:p-4 relative">
                                <div class="flex gap-2">
                                    <!-- Image & Drag (Hidden on mobile if needed, but keeping for now) -->
                                    <!-- <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <div class="relative">
                                            <input
                                                type="checkbox"
                                                :checked="isSelected(item.id)"
                                                @change="toggleSelection(item.id)"
                                                @click.stop
                                                class="w-4 h-4 rounded border-gray-200 text-indigo-600 focus:ring-indigo-500"
                                            />
                                        </div>
                                    </div> -->

                                    <!-- Image -->
                                    <img
                                        :src="item.feature_image"
                                        class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-lg bg-gray-50 flex-shrink-0"
                                    />

                                    <!-- Info Section -->
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex flex-col md:flex-row md:justify-between md:items-start gap-2"
                                        >
                                            <div
                                                class="overflow-hidden pr-[60px]"
                                            >
                                                <h3
                                                    class="text-[12px] font-medium text-gray-900 dark:text-white truncate"
                                                    :title="item.name"
                                                >
                                                    {{ item.name }}
                                                </h3>
                                                <span
                                                    :class="[
                                                        item.stock > 0
                                                            ? 'text-emerald-500 bg-emerald-50'
                                                            : 'text-red-500 bg-red-50',
                                                    ]"
                                                    class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-tight"
                                                >
                                                    {{ item.stock }} IN STOCK
                                                </span>
                                                <span
                                                    :class="[
                                                        item.status ===
                                                        'Published'
                                                            ? 'text-indigo-600 bg-indigo-50'
                                                            : 'text-amber-600 bg-amber-50',
                                                    ]"
                                                    class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-tight"
                                                >
                                                    {{ item.status }}
                                                </span>
                                            </div>

                                            <div
                                                class="absolute top-1 left-auto right-1 flex items-center gap-1 self-end md:self-auto"
                                            >
                                                <button
                                                    @click.stop="openQuickEditModal(item)"
                                                    class="p-1.5 text-gray-400 hover:text-indigo-600 rounded transition-colors"
                                                >
                                                    <Settings size="16" />
                                                </button>
                                                <Link
                                                    :href="route('admin.products.edit', item.id)"
                                                    @click.stop
                                                    class="p-1.5 text-gray-400 hover:text-indigo-600 rounded transition-colors"
                                                >
                                                    <SquarePen size="16" />
                                                </Link>
                                            </div>
                                        </div>

                                        <div
                                            class="flex flex-wrap items-center gap-3 mt-4"
                                        >
                                            <div class="flex-1 md:text-right">
                                                <button
                                                    @click.stop="toggleStockEdit(item)"
                                                    class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors uppercase tracking-tight"
                                                >
                                                    STOCK UPDATE
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stock Edit Section (Inline) -->
                                <div
                                    v-if="editingStockId === item.id"
                                    @click.stop
                                    class="mt-4 pt-4 border-t border-gray-50"
                                >
                                    <div
                                        class="bg-gray-50 rounded-lg p-3 max-w-sm ml-auto"
                                    >
                                        <div
                                            v-if="item.type === 'simple'"
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <span
                                                class="text-[10px] font-bold text-gray-400 uppercase"
                                                >Input Stock</span
                                            >
                                            <div
                                                class="flex items-center bg-white rounded border border-gray-200 overflow-hidden"
                                            >
                                                <button
                                                    @click="
                                                        decrementStock('simple')
                                                    "
                                                    class="px-2 py-1 text-gray-400 hover:text-indigo-600"
                                                >
                                                    <Minus size="14" />
                                                </button>
                                                <input
                                                    v-model="stockForm.stock"
                                                    type="number"
                                                    class="w-12 text-center border-none outline-none font-bold text-xs p-0"
                                                />
                                                <button
                                                    @click="
                                                        incrementStock('simple')
                                                    "
                                                    class="px-2 py-1 text-gray-400 hover:text-indigo-600"
                                                >
                                                    <Plus size="14" />
                                                </button>
                                            </div>
                                            <button
                                                @click="updateStock(item)"
                                                class="px-3 py-1 bg-gray-900 text-white rounded text-[10px] font-bold"
                                            >
                                                SAVE
                                            </button>
                                        </div>

                                        <div v-else class="space-y-2" @click.stop>
                                            <div
                                                v-for="(
                                                    v, vIdx
                                                ) in item.variations"
                                                :key="v.id"
                                                class="flex items-center justify-between gap-4"
                                            >
                                                <span
                                                    class="text-[10px] font-medium text-gray-500 truncate w-32"
                                                >
                                                    {{
                                                        v.attributes
                                                            .map(
                                                                (a) =>
                                                                    a.value
                                                                        ?.value,
                                                            )
                                                            .join("/")
                                                    }}
                                                </span>
                                                <div
                                                    class="flex items-center bg-white rounded border border-gray-200 overflow-hidden"
                                                >
                                                    <button
                                                        @click="
                                                            decrementStock(
                                                                'variable',
                                                                vIdx,
                                                            )
                                                        "
                                                        class="px-2 py-1 text-gray-400"
                                                    >
                                                        <Minus size="12" />
                                                    </button>
                                                    <input
                                                        v-model="
                                                            stockForm
                                                                .variations[
                                                                vIdx
                                                            ].stock
                                                        "
                                                        type="number"
                                                        class="w-10 text-center border-none p-0 text-xs font-bold"
                                                    />
                                                    <button
                                                        @click="
                                                            incrementStock(
                                                                'variable',
                                                                vIdx,
                                                            )
                                                        "
                                                        class="px-2 py-1 text-gray-400"
                                                    >
                                                        <Plus size="12" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex justify-end pt-2">
                                                <button
                                                    @click="updateStock(item)"
                                                    class="px-4 py-1.5 bg-gray-900 text-white rounded text-[10px] font-bold"
                                                >
                                                    SAVE ALL
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>

                <div
                    v-if="products.data.length === 0 && !loading"
                    class="py-10 text-center border border-dashed rounded-xl grayscale opacity-50"
                >
                    <Package size="40" class="mx-auto mb-2" />
                    <p class="text-xs font-medium">No results found</p>
                </div>
            </div>

            <!-- Laravel Pagination -->
            <div class="flex items-center justify-end mt-4">
                <div class="flex space-x-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        «
                    </button>
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        ‹
                    </button>
                    <template v-if="lastPage <= 7">
                        <button
                            v-for="page in lastPage"
                            :key="page"
                            @click="goToPage(page)"
                            :disabled="loading"
                            class="pagination-button"
                            :class="{ active: currentPage === page }"
                        >
                            {{ page }}
                        </button>
                    </template>
                    <template v-else>
                        <button
                            v-if="currentPage > 3"
                            @click="goToPage(1)"
                            class="pagination-button"
                        >
                            1
                        </button>
                        <span v-if="currentPage > 4" class="pagination-ellipsis"
                            >...</span
                        >
                        <template v-for="page in lastPage" :key="page">
                            <button
                                v-if="
                                    page >= currentPage - 1 &&
                                    page <= currentPage + 1
                                "
                                @click="goToPage(page)"
                                class="pagination-button"
                                :class="{ active: currentPage === page }"
                            >
                                {{ page }}
                            </button>
                        </template>
                        <span
                            v-if="currentPage < lastPage - 3"
                            class="pagination-ellipsis"
                            >...</span
                        >
                        <button
                            v-if="currentPage < lastPage - 2"
                            @click="goToPage(lastPage)"
                            class="pagination-button"
                        >
                            {{ lastPage }}
                        </button>
                    </template>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        ›
                    </button>
                    <button
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        »
                    </button>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <DeleteModal
                :item-id="productToDelete"
                item-name="product"
                route-name="admin.products.destroy"
                v-model:visible="showDeleteModal"
                @deleted="handleDeleteSuccess"
            />

            <!-- Quick Edit Modal -->
            <div
                v-if="showQuickEditModal"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md"
                >
                    <h3
                        class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4"
                    >
                        Quick Edit Product
                    </h3>
                    <form @submit.prevent="submitQuickEdit" class="space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >Status</label
                            >
                            <select
                                v-model="quickEditForm.status"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="Published">Published</option>
                                <option value="Unpublished">Unpublished</option>
                            </select>
                        </div>
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="quickEditForm.is_free_delivery"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                            />
                            <span
                                class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                                >Free Delivery</span
                            >
                        </label>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >Remarks</label
                            >
                            <select
                                v-model="quickEditForm.remarks"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">None</option>
                                <option value="New">New</option>
                                <option value="Popular">Popular</option>
                                <option value="Trending">Trending</option>
                                <option value="Hot">Flash Sale</option>
                                <option value="Special">Special</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-4">
                            <button
                                type="button"
                                @click="closeQuickEditModal"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 btn-primary text-white rounded-md hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-800"
                            >
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Detailed View Modal -->
            <div
                v-if="showDetailModal"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50 p-4"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto"
                >
                    <div
                        class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 z-10"
                    >
                        <h3
                            class="text-xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Product Details
                        </h3>
                        <button
                            @click="closeDetailModal"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ChevronRight class="rotate-180" size="24" />
                        </button>
                    </div>
                    <div class="p-6" v-if="selectedProduct">
                        <div class="flex flex-col md:flex-row gap-8">
                            <div class="md:w-1/3">
                                <div
                                    class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-700 sticky top-24"
                                >
                                    <img
                                        :src="selectedProduct.feature_image"
                                        alt=""
                                        class="w-full h-auto object-contain rounded-lg"
                                    />
                                </div>
                            </div>
                            <div
                                class="md:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-6"
                            >
                                <div>
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1"
                                    >
                                        Model Name
                                    </div>
                                    <div
                                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                                    >
                                        {{ selectedProduct.name }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1"
                                    >
                                        Brand
                                    </div>
                                    <div
                                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                                    >
                                        {{
                                            selectedProduct.brand?.brand_name ||
                                            "N/A"
                                        }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1"
                                    >
                                        Price
                                    </div>
                                    <div class="text-lg font-bold text-primary">
                                        ৳{{ selectedProduct.price }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1"
                                    >
                                        Stock
                                    </div>
                                    <div
                                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                                    >
                                        {{ selectedProduct.stock }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1"
                                    >
                                        ID
                                    </div>
                                    <div
                                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                                    >
                                        #{{ selectedProduct.id }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1"
                                    >
                                        Type
                                    </div>
                                    <div
                                        class="text-lg font-bold text-gray-900 dark:text-gray-100 capitalize"
                                    >
                                        {{ selectedProduct.type }}
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        selectedProduct.variations &&
                                        selectedProduct.variations.length > 0
                                    "
                                    class="col-span-1 sm:col-span-2 mt-4"
                                >
                                    <div
                                        class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3"
                                    >
                                        Variations
                                    </div>
                                    <div
                                        class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700"
                                    >
                                        <table
                                            class="min-w-full divide-y divide-gray-100 dark:divide-gray-700"
                                        >
                                            <thead
                                                class="bg-gray-50 dark:bg-gray-900"
                                            >
                                                <tr>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase"
                                                    >
                                                        Image
                                                    </th>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase"
                                                    >
                                                        Details
                                                    </th>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase"
                                                    >
                                                        Price
                                                    </th>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase"
                                                    >
                                                        Stock
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="divide-y divide-gray-100 dark:divide-gray-700"
                                            >
                                                <tr
                                                    v-for="variation in selectedProduct.variations"
                                                    :key="variation.id"
                                                >
                                                    <td class="px-4 py-3">
                                                        <img
                                                            v-if="
                                                                variation.image_path
                                                            "
                                                            class="h-10 w-10 rounded shadow-sm object-cover"
                                                            :src="
                                                                variation.image_path
                                                            "
                                                        />
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div
                                                            class="flex flex-wrap gap-1"
                                                        >
                                                            <span
                                                                v-for="attr in variation.attributes"
                                                                :key="attr.id"
                                                                class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300"
                                                            >
                                                                {{
                                                                    attr.value
                                                                        ?.attribute
                                                                        ?.name
                                                                }}:
                                                                {{
                                                                    attr.value
                                                                        ?.value
                                                                }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 font-bold text-gray-900 dark:text-gray-100"
                                                    >
                                                        ৳{{ variation.price }}
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                                    >
                                                        {{ variation.stock }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end"
                    >
                        <button
                            @click="closeDetailModal"
                            class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 transition"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, computed, watch } from "vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import draggable from "vuedraggable";
import {
    ChevronDown,
    ChevronRight,
    Search,
    Download,
    Package,
    Trash2Icon,
    SquarePen,
    PencilIcon,
    GripVertical,
    Settings,
    Zap,
    Check,
    Plus,
    Minus,
} from "lucide-vue-next";

const props = defineProps({
    products: Object,
    categories: Array,
});

const urlParams = new URLSearchParams(window.location.search);

// State
const searchQuery = ref(urlParams.get("search") || "");
const perPage = ref(parseInt(urlParams.get("per_page")) || 50);
const selectedCategory = ref(urlParams.get("category_id") || "");
const selectedProductIds = ref([]);
const loading = ref(false);
const isExporting = ref(false);
const showDeleteModal = ref(false);
const productToDelete = ref(null);
const isDeleting = ref(false);
const showQuickEditModal = ref(false);
const productToEdit = ref(null);
const quickEditForm = ref({ status: "", is_free_delivery: false, remarks: "" });
const confirmationInput = ref("");
const editingStockId = ref(null);
const stockForm = ref({
    type: "simple",
    stock: 0,
    variations: [],
});

const productList = ref([...props.products.data]);

// Modal State
const showDetailModal = ref(false);
const selectedProduct = ref(null);

watch(
    () => props.products.data,
    (newData) => {
        productList.value = [...newData];
    },
    { deep: true },
);

// Computed
const currentPage = computed(() => props.products.current_page);
const lastPage = computed(() => props.products.last_page);

// Methods
const openDeleteModal = (id) => {
    productToDelete.value = id;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    productToDelete.value = null;
};

const openQuickEditModal = (product) => {
    productToEdit.value = product;
    quickEditForm.value = {
        status: product.status,
        is_free_delivery: product.is_free_delivery,
        remarks: product.remarks || "",
    };
    showQuickEditModal.value = true;
};

const closeQuickEditModal = () => {
    showQuickEditModal.value = false;
};

const submitQuickEdit = () => {
    if (!productToEdit.value) return;
    router.post(
        route("admin.products.quick-edit", productToEdit.value.id),
        quickEditForm.value,
        {
            preserveState: true,
            onSuccess: () => {
                toast.success("Product updated!");
                closeQuickEditModal();
            },
            onError: () => toast.error("Update failed"),
        },
    );
};

const openDetailModal = (product) => {
    selectedProduct.value = product;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
};

const toggleStockEdit = (product) => {
    if (editingStockId.value === product.id) {
        editingStockId.value = null;
        return;
    }

    editingStockId.value = product.id;
    stockForm.value = {
        type: product.type,
        stock: product.stock,
        variations: product.variations
            ? product.variations.map((v) => ({ id: v.id, stock: v.stock }))
            : [],
    };
};

const incrementStock = (target, vIdx = null) => {
    if (target === "simple") stockForm.value.stock++;
    else stockForm.value.variations[vIdx].stock++;
};

const decrementStock = (target, vIdx = null) => {
    if (target === "simple") {
        if (stockForm.value.stock > 0) stockForm.value.stock--;
    } else {
        if (stockForm.value.variations[vIdx].stock > 0)
            stockForm.value.variations[vIdx].stock--;
    }
};

const updateStock = (product) => {
    router.post(
        route("admin.products.update-stock", product.id),
        stockForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Inventory updated successfully");
                editingStockId.value = null;
            },
            onError: (errors) => {
                toast.error(
                    Object.values(errors)[0] || "Failed to update stock",
                );
            },
        },
    );
};

const handleSearch = () => {
    loading.value = true;
    router.get(
        route("admin.products.index"),
        {
            search: searchQuery.value,
            page: 1,
            per_page: perPage.value,
            category_id: selectedCategory.value,
        },
        {
            preserveState: true,
            onSuccess: () => (loading.value = false),
            onError: () => (loading.value = false),
        },
    );
};

const handleCategoryChange = () => handleSearch();
const handlePerPageChange = () => handleSearch();

const exportCsv = () => {
    isExporting.value = true;
    const url = new URL(
        route("admin.products.export-csv"),
        window.location.origin,
    );

    if (searchQuery.value) {
        url.searchParams.set("search", searchQuery.value);
    }

    if (perPage.value) {
        url.searchParams.set("per_page", perPage.value);
    }

    if (selectedCategory.value) {
        url.searchParams.set("category_id", selectedCategory.value);
    }

    window.location.href = url.toString();
    setTimeout(() => {
        isExporting.value = false;
    }, 1200);
};

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    loading.value = true;
    router.get(
        route("admin.products.index"),
        {
            search: searchQuery.value,
            page: page,
            per_page: perPage.value,
            category_id: selectedCategory.value,
        },
        {
            preserveState: true,
            onSuccess: () => (loading.value = false),
        },
    );
};

const toggleSelectAll = () => {
    if (selectedProductIds.value.length === props.products.data.length) {
        selectedProductIds.value = [];
    } else {
        selectedProductIds.value = props.products.data.map((item) => item.id);
    }
};

const isSelected = (id) => selectedProductIds.value.includes(id);

const toggleSelection = (id) => {
    const index = selectedProductIds.value.indexOf(id);
    if (index === -1) selectedProductIds.value.push(id);
    else selectedProductIds.value.splice(index, 1);
};

const deleteSelectedProducts = () => {
    if (confirmationInput.value !== "confirm") return;
    isDeleting.value = true;
    router.post(
        route("admin.products.bulk-delete"),
        {
            product_ids: selectedProductIds.value,
            confirmation: confirmationInput.value,
        },
        {
            onSuccess: () => {
                toast.success("Deleted!");
                selectedProductIds.value = [];
                confirmationInput.value = "";
                isDeleting.value = false;
            },
            onError: () => (isDeleting.value = false),
        },
    );
};

const onDragEnd = () => {
    const orders = productList.value.map((p, i) => ({
        id: p.id,
        order: i + 1 + (currentPage.value - 1) * perPage.value,
    }));
    router.post(
        route("admin.products.update-order"),
        { orders },
        {
            preserveState: true,
            onSuccess: () => toast.success("Order saved!"),
        },
    );
};

function formatDate(isoDate) {
    const date = new Date(isoDate);
    const diff = Math.floor((new Date() - date) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

const searchTimeout = ref(null);
const debouncedSearch = () => {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(handleSearch, 500);
};
</script>

<style scoped>
.pagination-button {
    @apply px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold hover:bg-gray-50 transition-all;
}
.active {
    @apply bg-indigo-600 text-white border-indigo-600;
}
.pagination-ellipsis {
    @apply px-2 py-1 text-xs text-gray-400 font-medium;
}
</style>
