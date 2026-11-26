<script setup>
import { Head, WhenVisible } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import RecipeSection from "@/Components/Frontend/Home/RecipeSection.vue";
import Sliders from "@/Components/Frontend/Slider/Sliders.vue";
const ProductVideoReel = defineAsyncComponent(() => import("@/Components/Frontend/Product/ProductVideoReel.vue"));
import { computed, toRef, defineAsyncComponent } from "vue";

import NewsletterCta from "@/Components/Frontend/Home/NewsletterCta.vue";
import SauceSection from "@/Components/Frontend/Home/SauceSection.vue";
import StorySection from "@/Components/Frontend/Home/StorySection.vue";
import FeatureHighlights from "@/Components/Frontend/Home/FeatureHighlights.vue";
const ProductsGroup = defineAsyncComponent(() => import("@/Components/Frontend/Home/ProductsGroup.vue"));
const InstagramFeed = defineAsyncComponent(() => import("@/Components/Frontend/Home/InstagramFeed.vue"));
const PromoPopup = defineAsyncComponent(() => import("@/Components/Frontend/Home/PromoPopup.vue"));

defineOptions({
    layout: FrontendLayout,
});

const props = defineProps({
    sauces: {
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
    homeSections: {
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

    <SauceSection :products="sauces" />

    <StorySection />

    <!--
        Lazy sections: a placeholder is only rendered when the server says the section has content,
        it is sized close to the real section, and loading starts well before it scrolls into view.
        Together this keeps the page from jumping as sections arrive.
    -->
    <WhenVisible v-if="homeSections.videos" data="productswithVideo" :buffer="800">
        <template #fallback><div class="h-[460px] sm:h-[540px] md:h-[600px]" aria-hidden="true" /></template>
        <ProductVideoReel :productswithVideo="productswithVideo" />
    </WhenVisible>

    <WhenVisible v-if="homeSections.productGroups" :data="['productGroups', 'featuredProduct']" :buffer="800">
        <template #fallback><div class="h-[600px] md:h-[640px]" aria-hidden="true" /></template>
        <ProductsGroup :pagination="productGroups" :featured="featuredProduct" />
    </WhenVisible>

    <RecipeSection v-if="recipeSection" :section="recipeSection" />

    <NewsletterCta />

    <WhenVisible v-if="homeSections.instagram" data="instagramFeeds" :buffer="800">
        <template #fallback><div class="h-[440px]" aria-hidden="true" /></template>
        <InstagramFeed :instagramFeeds="instagramFeeds" :settings="settings" />
    </WhenVisible>

</template>
