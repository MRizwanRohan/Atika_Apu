<script setup>
import artwork from "@/../images/product-section-bg.webp";

// The paper artwork used behind homepage sections. It is decorated on both edges with plain
// paper in the middle, so each half of the block shows the image at full height pinned to its
// own edge: both decorated edges stay whole at any width instead of being cropped by "cover".
const props = defineProps({
    tag: { type: String, default: "div" },
    // Any wide artwork decorated on both edges; defaults to the yellow paper artwork.
    image: { type: String, default: artwork },
});

const half = (side) => ({ background: `url(${props.image}) ${side} center / auto 100% no-repeat` });
</script>

<template>
    <component :is="tag" class="relative isolate" :class="image === artwork ? 'bg-[#f6d999]' : ''">
        <div class="absolute inset-y-0 left-0 -z-10 w-1/2" :style="half('left')" aria-hidden="true" />
        <div class="absolute inset-y-0 right-0 -z-10 w-1/2" :style="half('right')" aria-hidden="true" />
        <slot />
    </component>
</template>
