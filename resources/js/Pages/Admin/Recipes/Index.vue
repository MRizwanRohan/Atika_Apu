<script setup>
import { ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import debounce from "lodash/debounce";
import { toast } from "@steveyuowo/vue-hot-toast";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    recipes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    section: { type: Object, required: true },
});

// Section settings
const sectionForm = useForm({
    enabled: Boolean(props.section.enabled),
    label: props.section.label,
    title: props.section.title,
    banner_link: props.section.banner_link,
    banner_file: null,
    remove_banner: false,
});
const bannerPreview = ref(props.section.banner);

const onBanner = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    sectionForm.banner_file = file;
    sectionForm.remove_banner = false;
    bannerPreview.value = URL.createObjectURL(file);
};

const removeBanner = () => {
    sectionForm.banner_file = null;
    sectionForm.remove_banner = true;
    bannerPreview.value = null;
};

const saveSection = () => {
    sectionForm.post(route("admin.recipes.section"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            sectionForm.banner_file = null;
            toast.success("Recipe section saved");
        },
        onError: () => toast.error("Please fix the highlighted fields"),
    });
};

// Recipe list
const search = ref(props.filters.search || "");
const runSearch = debounce((value) => {
    router.get(route("admin.recipes.index"), value ? { search: value } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["recipes", "filters"],
    });
}, 350);

const toggleStatus = (recipe) => {
    router.put(route("admin.recipes.status", recipe.id), {}, { preserveScroll: true, only: ["recipes"] });
};

const deleteId = ref(null);
const showDelete = ref(false);
const askDelete = (recipe) => {
    deleteId.value = recipe.id;
    showDelete.value = true;
};
</script>

<template>
    <Head title="Recipes" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recipes</h2>
        </template>

        <div class="py-8 space-y-6">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Homepage section settings -->
                <form class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5" @submit.prevent="saveSection">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Homepage recipe section</h3>
                            <p class="text-sm text-gray-500">Shown after "Find your style": banner on top, then up to 5 recipes.</p>
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="sectionForm.enabled" type="checkbox" class="toggle toggle-primary" />
                            <span class="font-medium text-gray-700">Show on homepage</span>
                        </label>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="s-label" class="block text-sm font-medium text-gray-700 mb-1">Small label</label>
                            <input id="s-label" v-model="sectionForm.label" type="text" maxlength="60" class="input input-bordered w-full" />
                            <p v-if="sectionForm.errors.label" class="mt-1 text-sm text-red-600">{{ sectionForm.errors.label }}</p>
                        </div>
                        <div>
                            <label for="s-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input id="s-title" v-model="sectionForm.title" type="text" maxlength="100" class="input input-bordered w-full" />
                            <p v-if="sectionForm.errors.title" class="mt-1 text-sm text-red-600">{{ sectionForm.errors.title }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banner (full width, height follows the image)</label>
                        <img v-if="bannerPreview" :src="bannerPreview" alt="Banner preview" class="mb-3 w-full max-h-56 object-cover rounded-lg bg-gray-100" />
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="file-input file-input-bordered w-full max-w-xs" @change="onBanner" />
                            <button v-if="bannerPreview" type="button" class="btn btn-ghost btn-sm text-red-600" @click="removeBanner">Remove banner</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP, up to 8 MB. A wide image (e.g. 1920 × 600) looks best.</p>
                        <p v-if="sectionForm.errors.banner_file" class="mt-1 text-sm text-red-600">{{ sectionForm.errors.banner_file }}</p>
                    </div>

                    <div>
                        <label for="s-link" class="block text-sm font-medium text-gray-700 mb-1">Banner link <span class="font-normal text-gray-400">(optional)</span></label>
                        <input id="s-link" v-model="sectionForm.banner_link" type="text" maxlength="255" placeholder="/recipes or https://…" class="input input-bordered w-full" />
                        <p v-if="sectionForm.errors.banner_link" class="mt-1 text-sm text-red-600">{{ sectionForm.errors.banner_link }}</p>
                    </div>

                    <button type="submit" class="btn btn-primary" :disabled="sectionForm.processing">
                        {{ sectionForm.processing ? "Saving…" : "Save section" }}
                    </button>
                </form>

                <!-- Recipes -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search recipes…"
                            class="input input-bordered w-full sm:max-w-xs"
                            @input="runSearch(search)"
                        />
                        <Link :href="route('admin.recipes.create')" class="btn btn-primary">+ Add Recipe</Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Recipe</th>
                                    <th>Order</th>
                                    <th>Visible</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="recipe in recipes.data" :key="recipe.id">
                                    <td>
                                        <img :src="recipe.image" :alt="recipe.title" class="w-20 aspect-[4/3] object-cover rounded bg-gray-100" />
                                    </td>
                                    <td>
                                        <p class="font-medium text-gray-900">{{ recipe.title }}</p>
                                        <p v-if="recipe.subtitle" class="text-sm text-gray-500">{{ recipe.subtitle }}</p>
                                    </td>
                                    <td>{{ recipe.sort_order }}</td>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="toggle toggle-sm toggle-primary"
                                            :checked="recipe.status"
                                            :aria-label="`Show ${recipe.title} on website`"
                                            @change="toggleStatus(recipe)"
                                        />
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <a :href="route('recipes.show', recipe.slug)" target="_blank" rel="noopener" class="btn btn-ghost btn-xs">View</a>
                                        <Link :href="route('admin.recipes.edit', recipe.id)" class="btn btn-ghost btn-xs">Edit</Link>
                                        <button type="button" class="btn btn-ghost btn-xs text-red-600" @click="askDelete(recipe)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="!recipes.data.length">
                                    <td colspan="5" class="py-10 text-center text-gray-500">
                                        {{ filters.search ? "No recipes match your search." : "No recipes yet. Add your first one!" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="recipes.last_page > 1" class="mt-4">
                        <Pagination :key="recipes.current_page" :links="recipes.links" />
                    </div>
                </div>
            </div>
        </div>

        <DeleteModal
            v-if="deleteId"
            v-model:visible="showDelete"
            :item-id="deleteId"
            item-name="recipe"
            route-name="admin.recipes.destroy"
            @deleted="deleteId = null"
        />
    </AdminLayout>
</template>
