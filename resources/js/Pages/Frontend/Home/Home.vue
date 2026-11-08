<script setup>
import { Head, WhenVisible } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
const LatestProducts = defineAsyncComponent(() => import("@/Components/Frontend/Home/LatestProducts.vue"));
const RecipeSection = defineAsyncComponent(() => import("@/Components/Frontend/Home/RecipeSection.vue"));
import Sliders from "@/Components/Frontend/Slider/Sliders.vue";
const ProductVideoReel = defineAsyncComponent(() => import("@/Components/Frontend/Product/ProductVideoReel.vue"));
import { computed, toRef, defineAsyncComponent } from "vue";

import FeaturedCategories from "@/Components/Frontend/Home/FeaturedCategories.vue";
import FeatureHighlights from "@/Components/Frontend/Home/FeatureHighlights.vue";
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
    latestProducts: {
        type: Array,
        default: () => [],
    },
    productGroups: {
        type: Object,
        default: () => ({}),
    },
    featuredProduct: {
        type: Object,
        default: null,
    },
    recipeSection: {
        type: Object,
        default: null,
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

    <FeatureHighlights />

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

    <WhenVisible :data="['productGroups', 'featuredProduct']" :buffer="100">
        <template #fallback><div class="h-64" aria-hidden="true" /></template>
        <ProductsGroup :pagination="productGroups" :featured="featuredProduct" />
    </WhenVisible>

    <WhenVisible data="latestProducts" :buffer="100">
        <template #fallback><div class="h-64" aria-hidden="true" /></template>
        <LatestProducts :products="latestProducts" />
    </WhenVisible>

    <WhenVisible data="recipeSection" :buffer="200">
        <template #fallback><div class="h-48" aria-hidden="true" /></template>
        <RecipeSection v-if="recipeSection" :section="recipeSection" />
    </WhenVisible>
    <WhenVisible data="instagramFeeds" :buffer="100">
        <template #fallback><div class="h-48" aria-hidden="true" /></template>
        <InstagramFeed :instagramFeeds="instagramFeeds" :settings="settings" />
    </WhenVisible>

</template>
