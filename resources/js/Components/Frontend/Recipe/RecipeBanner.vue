<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    banner: { type: String, required: true },
    responsiveBanner: { type: [Object, Array], default: () => ({}) },
    link: { type: String, default: "" },
    alt: { type: String, default: "Recipes" },
});

// Inertia links only for same-site paths; full URLs get a normal anchor.
const wrapper = computed(() => {
    if (!props.link) return { is: "div", attrs: {} };
    return props.link.startsWith("/")
        ? { is: Link, attrs: { href: props.link } }
        : { is: "a", attrs: { href: props.link, rel: "noopener" } };
});
</script>

<template>
    <component :is="wrapper.is" v-bind="wrapper.attrs" class="block w-full bg-gray-100">
        <img
            :src="banner"
            :srcset="responsiveBanner?.srcset"
            sizes="100vw"
            :width="responsiveBanner?.width"
            :height="responsiveBanner?.height"
            :alt="alt"
            loading="lazy"
            decoding="async"
            class="block w-full h-auto"
        />
    </component>
</template>
