<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { PhMagnifyingGlass, PhX, PhCircleNotch } from "@phosphor-icons/vue";
import debounce from "lodash/debounce";

const props = defineProps({
    autoFocus: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: "light", // 'light' or 'dark'
    },
    placeholder: {
        type: String,
        default: "Search products, models, brands...",
    },
});

const emit = defineEmits(["close"]);

const searchQuery = ref("");
const suggestions = ref([]);
const showSuggestions = ref(false);
const loading = ref(false);
const searchContainerRef = ref(null);
const inputRef = ref(null);

// Debounced search function
const fetchSuggestions = debounce(async () => {
    if (searchQuery.value.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get("/products/search-suggestions", {
            params: { q: searchQuery.value },
        });
        suggestions.value = response.data.suggestions;
        showSuggestions.value = true;
    } catch (error) {
        console.error("Search error:", error);
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
}, 300);

watch(searchQuery, () => {
    fetchSuggestions();
});

// Handle search submit (Enter or button click)
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get("/products", { search: searchQuery.value.trim() });
        showSuggestions.value = false;
        emit("close");
    }
};

// Navigate to product
const goToProduct = (slug) => {
    router.get(`/product/${slug}`);
    showSuggestions.value = false;
    searchQuery.value = "";
    emit("close");
};

// Clear search
const clearSearch = () => {
    searchQuery.value = "";
    suggestions.value = [];
    showSuggestions.value = false;
};

// Handle click outside to close suggestions
const handleClickOutside = (event) => {
    if (
        searchContainerRef.value &&
        !searchContainerRef.value.contains(event.target)
    ) {
        showSuggestions.value = false;
    }
};

// Handle escape key to close suggestions
const handleEscapeKey = (event) => {
    if (event.key === "Escape") {
        showSuggestions.value = false;
    }
};

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

// Add event listeners on mount
onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    document.addEventListener("keydown", handleEscapeKey);

    if (props.autoFocus && inputRef.value) {
        inputRef.value.focus();
    }
});

// Remove event listeners on unmount
onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
    document.removeEventListener("keydown", handleEscapeKey);
});
</script>

<template>
    <div ref="searchContainerRef" class="relative flex-1 w-full">
        <div class="flex items-stretch border border-gray-200 rounded-lg bg-gray-50 focus-within:bg-white focus-within:border-primary/40 transition-all overflow-hidden">
            <input
                ref="inputRef"
                v-model="searchQuery"
                @keyup.enter="handleSearch"
                @focus="searchQuery.length >= 2 && (showSuggestions = true)"
                type="text"
                placeholder="Search in..."
                class="flex-1 pl-4 pr-2 py-2 outline-none border-none text-xs bg-transparent text-gray-900 focus:ring-0"
            />

            <div class="flex items-center gap-1 pr-2">
                <!-- Loading indicator -->
                <PhCircleNotch v-if="loading" class="w-4 h-4 text-gray-400 animate-spin mr-1"  />

                <!-- Clear button -->
                <button
                    v-if="searchQuery && !loading"
                    @click="clearSearch"
                    class="p-1.5 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <PhX class="w-3.5 h-3.5"  />
                </button>

                <!-- Search Icon/Button -->
                <button
                    @click="handleSearch"
                    class="p-2 ml-1 text-primary hover:bg-primary/5 rounded-md transition-all active:scale-95"
                    aria-label="Search"
                >
                    <PhMagnifyingGlass class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Suggestions dropdown -->
        <div
            v-if="showSuggestions && suggestions.length > 0"
            class="absolute z-[60] w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-2xl max-h-[450px] overflow-y-auto overflow-x-hidden"
        >
            <div
                v-for="product in suggestions"
                :key="product.id"
                @click="goToProduct(product.slug)"
                class="flex items-center gap-4 p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-b-0 transition-all"
            >
                <div
                    class="w-16 h-16 flex-shrink-0 bg-gray-50 rounded-xl overflow-hidden border border-gray-100"
                >
                    <img
                        :src="product.feature_image"
                        :alt="product.name"
                        class="w-full h-full object-contain p-2 hover:scale-110 transition-transform duration-300"
                    />
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-semibold text-gray-900 truncate">
                        {{ product.name }}
                    </h4>
                    <p
                        v-if="product.category"
                        class="text-xs text-gray-500 mt-1"
                    >
                        {{ product.category.name }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-bold text-primary">
                        ৳{{ formatPrice(product.price) }}
                    </p>
                    <p
                        v-if="
                            product.previous_price &&
                            product.previous_price > product.price
                        "
                        class="text-xs text-gray-400 line-through"
                    >
                        ৳{{ formatPrice(product.previous_price) }}
                    </p>
                </div>
            </div>

            <!-- View all results link -->
            <div
                @click="handleSearch"
                class="p-4 text-center text-primary font-semibold hover:bg-primary/5 cursor-pointer transition-colors border-t border-gray-50 text-sm"
            >
                View all results for "{{ searchQuery }}" →
            </div>
        </div>

        <!-- No results message -->
        <div
            v-if="
                showSuggestions &&
                suggestions.length === 0 &&
                searchQuery.length >= 2 &&
                !loading
            "
            class="absolute z-50 w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-2xl p-10 text-center"
        >
            <div class="flex flex-col items-center gap-4">
                <div class="p-4 bg-gray-50 rounded-full">
                    <PhMagnifyingGlass class="w-10 h-10 text-gray-200"  />
                </div>
                <div>
                    <p class="text-gray-900 font-semibold">No results found</p>
                    <p class="text-gray-500 text-sm mt-1">
                        Try different keywords OR filter by category
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
