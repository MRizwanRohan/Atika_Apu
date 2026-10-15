<script setup>
import { Head, Link, WhenVisible } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
const ProductCard = defineAsyncComponent(() => import("@/Components/Frontend/Product/ProductCard.vue"));
import Sliders from "@/Components/Frontend/Slider/Sliders.vue";
const ProductVideoReel = defineAsyncComponent(() => import("@/Components/Frontend/Product/ProductVideoReel.vue"));
import { computed, toRef, defineAsyncComponent } from "vue";

import FeaturedCategories from "@/Components/Frontend/Home/FeaturedCategories.vue";
const ProductsGroup = defineAsyncComponent(() => import("@/Components/Frontend/Home/ProductsGroup.vue"));
const InstagramFeed = defineAsyncComponent(() => import("@/Components/Frontend/Home/InstagramFeed.vue"));
const PromoPopup = defineAsyncComponent(() => import("@/Components/Frontend/Home/PromoPopup.vue"));

defineOptions({
    layout: FrontendLayout,
});

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    groupedProducts: {
        type: Array,
        default: () => [],
    },
    productGroups: {
        type: Object,
        default: () => ({}),
    },
    sliders: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    productswithVideo: {
        type: Array,
        default: () => [],
    },
    instagramFeeds: {
        type: Array,
        default: () => [],
    },
});

// Expose settings as a ref so it's accessible in the template and reactive in script
const settings = toRef(props, "settings");

// Expose other props as refs so template bindings remain available
const categories = toRef(props, "categories");
const groupedProducts = toRef(props, "groupedProducts");
const productGroups = toRef(props, "productGroups");
const sliders = toRef(props, "sliders");

const pageTitle = computed(
    () => settings.value?.generalSettings?.home_page_title || "Home",
);
</script>

<template>
    <Head :title="pageTitle">

        <meta name="description" :content="settings?.generalSettings?.site_description || 'E-commerce store'">
    </Head>

      <Sliders :sliders="sliders" />

    <PromoPopup
        v-if="settings?.generalSettings?.popup_enabled"
        :enabled="settings?.generalSettings?.popup_enabled == 1"
        :image="settings?.generalSettings?.popup_image"
        :link="settings?.generalSettings?.popup_link"
    />

    <FeaturedCategories :categories="categories" />

    <WhenVisible data="productswithVideo" :buffer="100">
        <template #fallback><div class="h-48" aria-hidden="true" /></template>
        <ProductVideoReel :productswithVideo="productswithVideo" />
    </WhenVisible>

    <WhenVisible data="productGroups" :buffer="100">
        <template #fallback><div class="h-64" aria-hidden="true" /></template>
        <ProductsGroup :pagination="productGroups" />
    </WhenVisible>

    <WhenVisible data="groupedProducts" :buffer="100">
    <template #fallback><div class="h-64" aria-hidden="true" /></template>
    <section
        v-for="group in groupedProducts"
        :key="group.name"
        class="py-8 bg-white"
    >
        <div
            v-if="group.products && group.products.length > 0"
            class="container mx-auto px-4"
        >

            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl md:text-2xl font-bold text-gray-900 mb-2"
                    >
                        {{ group.name }}
                    </h2>
                </div>
                <Link
                    :href="`/category/${group.slug}`"
                    class="flex text-sm items-center gap-2 text-primary font-semibold hover:text-primary/90 transition-colors"
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
                            stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                        />
                    </svg>
                </Link>
            </div>
            <hr class="border-gray-200 my-4" />

            <div
                class="pt-5 grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-6"
            >
                <ProductCard
                    v-for="product in group.products.slice(0, 8)"
                    :key="product.id"
                    :product="product"
                />
            </div>

        </div>
    </section>

    </WhenVisible>
    <WhenVisible data="instagramFeeds" :buffer="100">
        <template #fallback><div class="h-48" aria-hidden="true" /></template>
        <InstagramFeed :instagramFeeds="instagramFeeds" :settings="settings" />
    </WhenVisible>

</template>
