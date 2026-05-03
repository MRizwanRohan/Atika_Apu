<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";
import CategoryFilterTree from "@/Components/Filters/CategoryFilterTree.vue";
import {
    ChevronDown,
    ChevronUp,
    X,
    Filter,
    SlidersHorizontal,
} from "lucide-vue-next";
import debounce from "lodash/debounce";

const props = defineProps({
    products: Object,
    filters: Object,
    appliedFilters: Object,
});

// Filter state
const selectedCategory = ref(props.appliedFilters?.category || "");
const selectedBrands = ref(props.appliedFilters?.brand_ids || []);
const selectedAttributes = ref(props.appliedFilters?.attributes || {}); // e.g., {Size: ['XL', 'L'], Color: ['Red']}
const minPrice = ref(props.appliedFilters?.min_price || "");
const maxPrice = ref(props.appliedFilters?.max_price || "");
const sortBy = ref(props.appliedFilters?.sort_by || "latest");
const searchQuery = ref(props.appliedFilters?.search || "");

// UI state
const showFilters = ref(false);
const isSortOpen = ref(false);
const showBrands = ref(true);
const showPrice = ref(true);
const showCategories = ref(true);
const showAttributes = ref({}); // Track which attribute sections are expanded

// Initialize showAttributes for all available attributes
const initializeShowAttributes = () => {
    if (props.filters?.attributes) {
        props.filters.attributes.forEach((attr) => {
            if (showAttributes.value[attr.name] === undefined) {
                showAttributes.value[attr.name] = true;
            }
        });
    }
};

// Initialize on mount
onMounted(() => {
    initializeShowAttributes();
});

// Also watch for filters changes (in case data loads after mount)
watch(
    () => props.filters?.attributes,
    () => {
        initializeShowAttributes();
    },
    { immediate: true },
);

// Check if attribute is a color type (case-insensitive)
const isColorAttribute = (attributeName) => {
    return attributeName.toLowerCase() === "color";
};

// Sort options
const sortOptions = [
    { value: "latest", label: "Latest" },
    { value: "oldest", label: "Oldest" },
    { value: "price_asc", label: "Price: Low to High" },
    { value: "price_desc", label: "Price: High to Low" },
    { value: "name_asc", label: "Name: A to Z" },
    { value: "name_desc", label: "Name: Z to A" },
];

// Apply filters
const applyFilters = () => {
    const params = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (selectedBrands.value.length > 0)
        params.brand_ids = selectedBrands.value;
    if (minPrice.value) params.min_price = minPrice.value;
    if (maxPrice.value) params.max_price = maxPrice.value;
    if (sortBy.value !== "latest") params.sort_by = sortBy.value;

    // Add attribute filters
    if (Object.keys(selectedAttributes.value).length > 0) {
        params.attributes = selectedAttributes.value;
    }

    router.get("/products", params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Debounced apply filters
const debouncedApplyFilters = debounce(applyFilters, 500);

// Watch for filter changes
watch(
    [selectedBrands, selectedAttributes, minPrice, maxPrice],
    () => {
        debouncedApplyFilters();
    },
    { deep: true },
);

// Immediate apply for sort and category
watch([sortBy, selectedCategory], () => {
    applyFilters();
});

// Clear all filters
const clearAllFilters = () => {
    selectedCategory.value = "";
    selectedBrands.value = [];
    selectedAttributes.value = {};
    minPrice.value = "";
    maxPrice.value = "";
    sortBy.value = "latest";
    searchQuery.value = "";
    router.get("/products");
};

// Toggle brand selection
const toggleBrand = (brandId) => {
    const index = selectedBrands.value.indexOf(brandId);
    if (index === -1) {
        selectedBrands.value = [...selectedBrands.value, brandId];
    } else {
        selectedBrands.value = selectedBrands.value.filter(
            (id) => id !== brandId,
        );
    }
};

// Toggle attribute value selection
const toggleAttributeValue = (attributeName, value) => {
    // Create a new object to ensure reactivity
    const newAttributes = { ...selectedAttributes.value };

    if (!newAttributes[attributeName]) {
        newAttributes[attributeName] = [];
    }

    const index = newAttributes[attributeName].indexOf(value);
    if (index === -1) {
        newAttributes[attributeName] = [...newAttributes[attributeName], value];
    } else {
        newAttributes[attributeName] = newAttributes[attributeName].filter(
            (v) => v !== value,
        );
        // Remove attribute key if empty
        if (newAttributes[attributeName].length === 0) {
            delete newAttributes[attributeName];
        }
    }

    // Replace the entire object to trigger reactivity
    selectedAttributes.value = newAttributes;
};

// Select category
const selectCategory = (slug) => {
    selectedCategory.value = slug;
};

// Remove single filter
const removeFilter = (type, value = null, attributeName = null) => {
    switch (type) {
        case "search":
            searchQuery.value = "";
            break;
        case "category":
            selectedCategory.value = "";
            break;
        case "brand":
            selectedBrands.value = selectedBrands.value.filter(
                (id) => id !== value,
            );
            break;
        case "attribute":
            if (attributeName && selectedAttributes.value[attributeName]) {
                const newAttributes = { ...selectedAttributes.value };
                newAttributes[attributeName] = newAttributes[
                    attributeName
                ].filter((v) => v !== value);
                if (newAttributes[attributeName].length === 0) {
                    delete newAttributes[attributeName];
                }
                selectedAttributes.value = newAttributes;
            }
            break;
        case "price":
            minPrice.value = "";
            maxPrice.value = "";
            break;
    }
    applyFilters();
};

// Active filters count
const activeFiltersCount = computed(() => {
    let count = 0;
    if (searchQuery.value) count++;
    if (selectedCategory.value) count++;
    count += selectedBrands.value.length;
    // Count all selected attribute values
    Object.values(selectedAttributes.value).forEach((values) => {
        count += values.length;
    });
    if (minPrice.value || maxPrice.value) count++;
    return count;
});

// Get brand name by ID
const getBrandName = (brandId) => {
    const brand = props.filters.brands.find((b) => b.id === brandId);
    return brand ? brand.brand_name : "";
};

// Get category name by slug
const getCategoryName = (slug) => {
    const findCategory = (categories, targetSlug) => {
        for (const cat of categories) {
            if (cat.slug === targetSlug) return cat.name;
            if (cat.children?.length) {
                const found = findCategory(cat.children, targetSlug);
                if (found) return found;
            }
        }
        return null;
    };
    return findCategory(props.filters.categories, slug) || slug;
};

// Pagination
const goToPage = (page) => {
    const params = { ...props.appliedFilters, page };
    router.get("/products", params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD").format(price);
};
</script>

<template>
    <Head title="Products" />
    <FrontendLayout>
        <div class="min-h-screen bg-gray-50">
            <div class="container mx-auto px-4 py-6">
                <!-- Page Header -->
                <div class="mb-6 text-center">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
                        <span v-if="searchQuery"
                            >Search results for "{{ searchQuery }}"</span
                        >
                        <span v-else-if="selectedCategory">{{
                            getCategoryName(selectedCategory)
                        }}</span>
                        <span v-else>All Products</span>
                    </h1>
                </div>

                <!-- Top Bar: Results count, Sort, Filter Button -->
                <div class=" mb-8">
                    <div class="flex items-center justify-between">
                        <!-- Left: Filter Button -->
                        <button
                            @click="showFilters = true"
                            class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest hover:text-primary transition-colors group"
                        >
                            <SlidersHorizontal
                                class="w-4 h-4 group-hover:scale-110 transition-transform"
                            />
                            Filter
                            <span
                                v-if="activeFiltersCount > 0"
                                class="ml-1 bg-primary text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full"
                            >
                                {{ activeFiltersCount }}
                            </span>
                        </button>

                        <!-- Center: Results count -->
                        <div
                            class="hidden md:block text-[10px] font-medium uppercase tracking-[0.2em] text-gray-500"
                        >
                            Showing {{ products.from }}-{{ products.to }} of
                            {{ products.total }} products
                        </div>

                        <!-- Right: Sort Dropdown -->
                        <div class="relative">
                            <button
                                @click="isSortOpen = !isSortOpen"
                                class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest hover:text-primary transition-colors"
                            >
                                Sort By
                                <ChevronDown
                                    class="w-4 h-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': isSortOpen }"
                                />
                            </button>

                            <!-- Custom Sort Dropdown Menu -->
                            <transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="translate-y-1 opacity-0"
                                enter-to-class="translate-y-0 opacity-100"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="translate-y-0 opacity-100"
                                leave-to-class="translate-y-1 opacity-0"
                            >
                                <div
                                    v-if="isSortOpen"
                                    class="absolute right-0 mt-3 w-48 bg-white shadow-xl rounded-sm border border-gray-100 py-2 z-50"
                                >
                                    <button
                                        v-for="option in sortOptions"
                                        :key="option.value"
                                        @click="
                                            sortBy = option.value;
                                            isSortOpen = false;
                                        "
                                        class="w-full text-left px-4 py-2 text-xs uppercase tracking-wider transition-colors hover:bg-gray-50"
                                        :class="
                                            sortBy === option.value
                                                ? 'text-primary font-bold'
                                                : 'text-gray-600'
                                        "
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Chips -->


                <div class="flex gap-6">
                    <!-- Slide-out Sidebar Filters -->
                    <teleport to="body">
                        <transition
                            enter-active-class="transition duration-500 ease-in-out"
                            enter-from-class="-translate-x-full"
                            enter-to-class="translate-x-0"
                            leave-active-class="transition duration-500 ease-in-out"
                            leave-from-class="translate-x-0"
                            leave-to-class="-translate-x-full"
                        >
                            <aside
                                v-if="showFilters"
                                class="fixed inset-y-0 left-0 z-[100] w-full sm:w-[400px] bg-white shadow-2xl flex flex-col"
                            >
                                <!-- Header -->
                                <div
                                    class="flex items-center justify-between p-6 border-b border-gray-100"
                                >
                                    <h2
                                        class="text-sm font-bold uppercase tracking-[0.2em]"
                                    >
                                        Filter Options
                                    </h2>
                                    <button
                                        @click="showFilters = false"
                                        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                                    >
                                        <X class="w-5 h-5" />
                                    </button>
                                </div>

                                <!-- Filter Content -->
                                <div
                                    class="flex-1 overflow-y-auto custom-scrollbar p-6"
                                >
                                    <!-- Clear All Button -->
                                    <button
                                        v-if="activeFiltersCount > 0"
                                        @click="clearAllFilters"
                                        class="w-full mb-6 py-2.5 border border-red-200 text-red-600 text-xs font-bold uppercase tracking-widest hover:bg-red-50 transition-colors flex items-center justify-center gap-2"
                                    >
                                        Clear All Filters
                                        <X class="w-4 h-4" />
                                    </button>

                                    <div class="space-y-0">
                                        <!-- Categories -->
                                        <div class="border-b border-gray-100">
                                            <button
                                                @click="
                                                    showCategories =
                                                        !showCategories
                                                "
                                                class="w-full flex items-center justify-between py-4 group"
                                            >
                                                <span
                                                    class="text-xs font-bold uppercase tracking-widest group-hover:text-primary transition-colors text-gray-900"
                                                    >Categories</span
                                                >
                                                <ChevronDown
                                                    class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                                    :class="{
                                                        'rotate-180':
                                                            showCategories,
                                                    }"
                                                />
                                            </button>
                                            <transition
                                                enter-active-class="transition-all duration-300 ease-out"
                                                enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                                enter-to-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-active-class="transition-all duration-300 ease-in"
                                                leave-from-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                            >
                                                <div
                                                    v-show="showCategories"
                                                    class="pb-6"
                                                >
                                                    <CategoryFilterTree
                                                        :categories="
                                                            filters.categories
                                                        "
                                                        :selected="
                                                            selectedCategory
                                                        "
                                                        @select="selectCategory"
                                                    />
                                                </div>
                                            </transition>
                                        </div>

                                        <!-- Brands -->
                                        <div class="border-b border-gray-100">
                                            <button
                                                @click="
                                                    showBrands = !showBrands
                                                "
                                                class="w-full flex items-center justify-between py-4 group"
                                            >
                                                <span
                                                    class="text-xs font-bold uppercase tracking-widest group-hover:text-primary transition-colors text-gray-900"
                                                    >Brands</span
                                                >
                                                <ChevronDown
                                                    class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                                    :class="{
                                                        'rotate-180':
                                                            showBrands,
                                                    }"
                                                />
                                            </button>
                                            <transition
                                                enter-active-class="transition-all duration-300 ease-out"
                                                enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                                enter-to-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-active-class="transition-all duration-300 ease-in"
                                                leave-from-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                            >
                                                <div
                                                    v-show="showBrands"
                                                    class="pb-6"
                                                >
                                                    <div class="space-y-1">
                                                        <label
                                                            v-for="brand in filters.brands"
                                                            :key="brand.id"
                                                            class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors group"
                                                        >
                                                            <div
                                                                class="relative flex items-center"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    :checked="
                                                                        selectedBrands.includes(
                                                                            brand.id,
                                                                        )
                                                                    "
                                                                    @change="
                                                                        toggleBrand(
                                                                            brand.id,
                                                                        )
                                                                    "
                                                                    class="peer w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20 transition-all"
                                                                />
                                                            </div>
                                                            <span
                                                                class="text-xs font-medium text-gray-600 flex-1 uppercase tracking-wider group-hover:text-gray-900"
                                                                >{{
                                                                    brand.brand_name
                                                                }}</span
                                                            >
                                                            <span
                                                                class="text-[10px] font-bold text-gray-400"
                                                                >{{
                                                                    brand.products_count
                                                                }}</span
                                                            >
                                                        </label>
                                                    </div>
                                                </div>
                                            </transition>
                                        </div>

                                        <!-- Attributes -->
                                        <div
                                            v-for="attribute in filters.attributes"
                                            :key="attribute.id"
                                            class="border-b border-gray-100"
                                        >
                                            <button
                                                @click="
                                                    showAttributes[
                                                        attribute.name
                                                    ] =
                                                        !showAttributes[
                                                            attribute.name
                                                        ]
                                                "
                                                class="w-full flex items-center justify-between py-4 group"
                                            >
                                                <span
                                                    class="text-xs font-bold uppercase tracking-widest group-hover:text-primary transition-colors text-gray-900"
                                                    >{{ attribute.name }}</span
                                                >
                                                <ChevronDown
                                                    class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                                    :class="{
                                                        'rotate-180':
                                                            showAttributes[
                                                                attribute.name
                                                            ],
                                                    }"
                                                />
                                            </button>
                                            <transition
                                                enter-active-class="transition-all duration-300 ease-out"
                                                enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                                enter-to-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-active-class="transition-all duration-300 ease-in"
                                                leave-from-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                            >
                                                <div
                                                    v-show="
                                                        showAttributes[
                                                            attribute.name
                                                        ]
                                                    "
                                                    class="pb-6"
                                                >
                                                    <div class="space-y-1">
                                                        <label
                                                            v-for="attrValue in attribute.values"
                                                            :key="attrValue.id"
                                                            class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors group"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                :checked="
                                                                    selectedAttributes[
                                                                        attribute.name
                                                                    ]?.includes(
                                                                        attrValue.value,
                                                                    )
                                                                "
                                                                @change="
                                                                    toggleAttributeValue(
                                                                        attribute.name,
                                                                        attrValue.value,
                                                                    )
                                                                "
                                                                class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20"
                                                            />
                                                            <div
                                                                class="flex items-center gap-2 flex-1"
                                                            >
                                                                <span
                                                                    v-if="
                                                                        isColorAttribute(
                                                                            attribute.name,
                                                                        ) &&
                                                                        attrValue.color
                                                                    "
                                                                    class="w-3 h-3 rounded-full border border-gray-200"
                                                                    :style="{
                                                                        backgroundColor:
                                                                            attrValue.color,
                                                                    }"
                                                                ></span>
                                                                <span
                                                                    class="text-xs font-medium text-gray-600 uppercase tracking-wider group-hover:text-gray-900"
                                                                    >{{
                                                                        attrValue.value
                                                                    }}</span
                                                                >
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </transition>
                                        </div>

                                        <!-- Price Range -->
                                        <div class="border-b border-gray-100">
                                            <button
                                                @click="showPrice = !showPrice"
                                                class="w-full flex items-center justify-between py-4 group"
                                            >
                                                <span
                                                    class="text-xs font-bold uppercase tracking-widest group-hover:text-primary transition-colors text-gray-900"
                                                    >Price Range</span
                                                >
                                                <ChevronDown
                                                    class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                                    :class="{
                                                        'rotate-180': showPrice,
                                                    }"
                                                />
                                            </button>
                                            <transition
                                                enter-active-class="transition-all duration-300 ease-out"
                                                enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                                enter-to-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-active-class="transition-all duration-300 ease-in"
                                                leave-from-class="max-h-[1000px] opacity-100 overflow-hidden"
                                                leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                            >
                                                <div
                                                    v-show="showPrice"
                                                    class="pb-6"
                                                >
                                                    <div
                                                        class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4"
                                                    >
                                                        Range: ৳{{
                                                            formatPrice(
                                                                filters.priceRange
                                                                    .min,
                                                            )
                                                        }}
                                                        - ৳{{
                                                            formatPrice(
                                                                filters.priceRange
                                                                    .max,
                                                            )
                                                        }}
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-3"
                                                    >
                                                        <div class="flex-1">
                                                            <input
                                                                v-model="
                                                                    minPrice
                                                                "
                                                                type="number"
                                                                placeholder="Min"
                                                                :min="
                                                                    filters
                                                                        .priceRange
                                                                        .min
                                                                "
                                                                class="w-full px-3 py-2 border border-gray-100 rounded-sm text-xs focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all"
                                                            />
                                                        </div>
                                                        <span
                                                            class="text-gray-300"
                                                            >-</span
                                                        >
                                                        <div class="flex-1">
                                                            <input
                                                                v-model="
                                                                    maxPrice
                                                                "
                                                                type="number"
                                                                placeholder="Max"
                                                                :max="
                                                                    filters
                                                                        .priceRange
                                                                        .max
                                                                "
                                                                class="w-full px-3 py-2 border border-gray-100 rounded-sm text-xs focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </transition>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer (Mobile Apply) -->
                                <div class="p-6 border-t border-gray-100">
                                    <button
                                        @click="showFilters = false"
                                        class="w-full py-4 bg-black text-white text-xs font-bold uppercase tracking-[0.2em] hover:bg-gray-900 transition-colors"
                                    >
                                        View Products
                                    </button>
                                </div>
                            </aside>
                        </transition>

                        <!-- Overlay -->
                        <transition
                            enter-active-class="transition duration-500 ease-in-out"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition duration-500 ease-in-out"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div
                                v-if="showFilters"
                                class="fixed inset-0 bg-black/40 z-[90]"
                                @click="showFilters = false"
                            ></div>
                        </transition>
                    </teleport>

                    <div class="w-full">

                    <!-- Product Grid -->
                    <div class="flex-1">
                        <!-- Products -->
                        <div
                            v-if="products.data.length > 0"
                            class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2 md:gap-6"
                        >
                            <ProductCard
                                v-for="product in products.data"
                                :key="product.id"
                                :product="product"
                            />
                        </div>

                        <!-- No results -->
                        <div
                            v-else
                            class="bg-white rounded-lg shadow-sm p-12 text-center"
                        >
                            <div
                                class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center"
                            >
                                <SlidersHorizontal
                                    class="w-12 h-12 text-gray-400"
                                />
                            </div>
                            <h3
                                class="text-xl font-semibold text-gray-900 mb-2"
                            >
                                No products found
                            </h3>
                            <p class="text-gray-500 mb-6">
                                Try adjusting your filters or search terms
                            </p>
                            <button
                                @click="clearAllFilters"
                                class="px-6 py-2.5 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
                            >
                                Clear All Filters
                            </button>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="products.last_page > 1"
                            class="mt-8 flex justify-center"
                        >
                            <nav class="flex items-center gap-1">
                                <!-- Previous -->
                                <button
                                    @click="goToPage(products.current_page - 1)"
                                    :disabled="products.current_page === 1"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                                >
                                    Previous
                                </button>

                                <!-- Page numbers -->
                                <template
                                    v-for="page in products.last_page"
                                    :key="page"
                                >
                                    <button
                                        v-if="
                                            page === 1 ||
                                            page === products.last_page ||
                                            (page >=
                                                products.current_page - 2 &&
                                                page <=
                                                    products.current_page + 2)
                                        "
                                        @click="goToPage(page)"
                                        :class="[
                                            'w-10 h-10 rounded-lg text-sm font-medium transition-colors',
                                            page === products.current_page
                                                ? 'bg-primary text-white'
                                                : 'border border-gray-300 hover:bg-gray-50',
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                    <span
                                        v-else-if="
                                            page ===
                                                products.current_page - 3 ||
                                            page === products.current_page + 3
                                        "
                                        class="px-2 text-gray-400"
                                    >
                                        ...
                                    </span>
                                </template>

                                <!-- Next -->
                                <button
                                    @click="goToPage(products.current_page + 1)"
                                    :disabled="
                                        products.current_page ===
                                        products.last_page
                                    "
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                                >
                                    Next
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</FrontendLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>
