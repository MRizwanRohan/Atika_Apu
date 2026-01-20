<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

const isExpanded = ref(false);
const parentCategories = computed(() =>
    props.categories.filter((c) => !c.parent_id),
);

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};
</script>

<template>
    <section class="py-8 md:py-12 bg-white">
        <div class="container mx-auto px-4">
            <!-- Section Title -->
            <div class="text-center mb-8 md:mb-12">
                <h2 class="text-xl md:text-2xl font-semibold text-[#2D3A4B]">
                    Featured Categories
                </h2>
            </div>

            <!-- Categories Grid -->
            <div
                class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-7 xl:grid-cols-9 gap-y-8 md:gap-y-10 gap-x-4 md:gap-x-6"
            >
                <Link
                    v-for="(category, index) in parentCategories"
                    :key="category.id"
                    :href="route('category.show', category.slug)"
                    class="flex-col items-center group"
                    :class="[
                        index >= 6 ? 'hidden md:flex' : 'flex',
                        isExpanded ? '!flex' : '',
                    ]"
                >
                    <!-- Image Container -->
                    <div
                        class="relative w-full aspect-square mb-3 flex items-center justify-center transition-all duration-300"
                    >
                        <img
                            v-if="category.image"
                            :src="category.image"
                            :alt="category.name"
                            class="w-[80%] h-[80%] object-contain transition-transform duration-500 group-hover:scale-110"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center bg-gray-50 rounded-lg"
                        >
                            <span class="text-[10px] text-gray-400"
                                >No Image</span
                            >
                        </div>
                    </div>

                    <!-- Category Name -->
                    <span
                        class="text-[11px] md:text-[13px] font-medium text-[#000] group-hover:text-primary transition-colors text-center leading-tight"
                    >
                        {{ category.name }}
                    </span>
                </Link>
            </div>

            <!-- Toggle Expand Button -->
            <div
                v-if="parentCategories.length > 6"
                class="text-center mt-10 md:hidden"
            >
                <button
                    @click="toggleExpand"
                    class="text-[12px] font-bold text-[#2D3A4B] underline decoration-2 underline-offset-4 hover:text-primary transition-colors cursor-pointer"
                >
                    {{
                        isExpanded
                            ? "Show Less Categories"
                            : "See All Categories"
                    }}
                </button>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Mix blend for white background images to blend in well */
img {
    mix-blend-mode: multiply;
}
</style>
