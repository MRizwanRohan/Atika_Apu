<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useProductPricing } from "@/Composables/useProductPricing";

const props = defineProps({
    product: { type: Object, required: true },
});

const { pricing, isOutOfStock, isPreOrderMode, formatPrice } = useProductPricing(() => props.product);

const statusLabel = computed(() => {
    if (isOutOfStock.value) return "Out of stock";
    if (isPreOrderMode.value) return "Pre-order";
    return "";
});
</script>

<template>
    <Link
        :href="route('product.details', product.slug)"
        class="group block h-full bg-transparent transition duration-300 hover:-translate-y-1"
    >
        <div class="relative aspect-[4/5] overflow-hidden rounded-2xl">
            <img
                :src="product.feature_image"
                :srcset="product.responsive_image?.srcset"
                sizes="(min-width: 1280px) 260px, (min-width: 768px) 28vw, 44vw"
                :width="product.responsive_image?.width"
                :height="product.responsive_image?.height"
                :alt="product.name"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                :class="{ 'opacity-60 grayscale': isOutOfStock }"
            />
            <span
                v-if="statusLabel"
                class="absolute left-2 top-2 rounded-full bg-gray-900/85 px-2 py-0.5 text-[10px] font-semibold text-white"
            >
                {{ statusLabel }}
            </span>
        </div>

        <div class="px-2 pb-4 pt-3 text-center md:px-3 md:pb-5">
            <h3 class="min-h-[2.5rem] font-serif text-base font-semibold leading-snug md:text-xl" style="color: color-mix(in srgb, var(--primary-color) 45%, black)">
                {{ product.name }}
            </h3>
            <p class="mt-1.5 text-sm font-semibold text-gray-800 md:text-base">
                ৳{{ formatPrice(pricing.main) }}
                <span v-if="pricing.cross" class="ml-1 text-xs font-normal text-gray-400 line-through">
                    ৳{{ formatPrice(pricing.cross) }}
                </span>
            </p>
        </div>
    </Link>
</template>
