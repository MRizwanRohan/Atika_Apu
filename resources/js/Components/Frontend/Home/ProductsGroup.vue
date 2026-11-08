<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import axios from "axios";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";
import FeaturedProduct from "./FeaturedProduct.vue";

const props = defineProps({
    pagination: {
        type: Object,
        required: true,
    },
    featured: {
        type: Object,
        default: null,
    },
});
const productGroups = ref([]);
const nextPage = ref(null);
const loading = ref(false);
const error = ref("");
let generation = 0;
watch(() => props.pagination, (pagination) => {
    generation++;
    productGroups.value = pagination.data || [];
    nextPage.value = pagination.next_page_url;
    loading.value = false;
    error.value = "";
}, { immediate: true });

const loadMore = async () => {
    if (loading.value || !nextPage.value) return;
    const currentGeneration = generation;
    loading.value = true;
    error.value = "";
    try {
        const { data } = await axios.get(nextPage.value);
        if (currentGeneration !== generation) return;
        const ids = new Set(productGroups.value.map(group => group.id));
        productGroups.value.push(...data.data.filter(group => !ids.has(group.id)));
        nextPage.value = data.next_page_url;
    } catch {
        if (currentGeneration === generation) error.value = "Unable to load collections. Please try again.";
    } finally {
        if (currentGeneration === generation) loading.value = false;
    }
};
</script>

<template>
    <FeaturedProduct v-if="featured && !productGroups.length" :featured="featured" />

    <template v-for="(group, index) in productGroups" :key="group.id">
    <!-- Product Groups Section -->
    <section
        class="py-2 md:py-16 bg-white border-b border-gray-100 last:border-0"
    >
        <div class="container mx-auto px-4">
            <!-- Banner Section -->
            <div
                v-if="group.banner"
                class="mb-8 md:mb-12 overflow-hidden rounded-xl md:rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-300"
            >
                <Link
                    :href="route('product-group.show', group.slug)"
                    class="block relative"
                >
                    <img loading="lazy" decoding="async"
                        :src="group.banner"
                        :srcset="group.responsive_banner?.srcset"
                        sizes="(min-width: 1280px) 1200px, 100vw"
                        :width="group.responsive_banner?.width"
                        :height="group.responsive_banner?.height"
                        :alt="group.name"
                        class="w-full h-full object-cover transition-transform duration-700 hover:scale-105"
                    />
                </Link>
            </div>

            <!-- Header Section -->
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <div class="relative">
                    <h2
                        class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight"
                    >
                        {{ group.name }}
                    </h2>
                    <div
                        class="absolute -bottom-2 left-0 w-12 h-1 bg-primary rounded-full"
                    ></div>
                </div>

                <Link
                    :href="route('product-group.show', group.slug)"
                    class="hidden text-sm md:flex items-center gap-2 text-primary font-semibold hover:gap-3 transition-all duration-300"
                >
                    View All
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                    </svg>
                </Link>
            </div>

            <!-- Product Grid -->
            <div
                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-6"
            >
                <div
                    v-for="product in group.products"
                    :key="product.id"
                    class=""
                >
                    <ProductCard :product="product" />
                </div>
            </div>

            <!-- Mobile Show All Button -->
            <div class="mt-8 text-center md:hidden">
                <Link
                    :href="route('product-group.show', group.slug)"
                    class="inline-flex text-[12px] items-center justify-center px-8 py-3 bg-gray-50 text-gray-900 font-bold rounded-lg border border-gray-200 active:bg-gray-100 transition-colors w-full sm:w-auto"
                >
                    View All Collection
                </Link>
            </div>
        </div>
    </section>

    <FeaturedProduct v-if="featured && index === 0" :featured="featured" />
    </template>
    <div v-if="nextPage" class="container py-6 text-center">
        <p v-if="error" role="alert" class="mb-3 text-red-600">{{ error }}</p>
        <button type="button" :disabled="loading" @click="loadMore" class="rounded-lg bg-primary px-6 py-3 text-white disabled:opacity-50">
            {{ loading ? "Loading…" : "View More Collections" }}
        </button>
    </div>
</template>

<style scoped>
/* Any specific scoped styles if needed */
</style>
