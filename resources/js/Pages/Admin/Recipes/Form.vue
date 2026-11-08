<script setup>
import { computed, onMounted, ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";
import { Ckeditor } from "@ckeditor/ckeditor5-vue";
import "ckeditor5/ckeditor5.css";
import { ClassicEditor, editorConfig } from "@/Helpers/ckeditor";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    recipe: { type: Object, default: null },
});

const isEdit = computed(() => Boolean(props.recipe));
const editorReady = ref(false);
onMounted(() => (editorReady.value = true));

const form = useForm({
    title: props.recipe?.title ?? "",
    subtitle: props.recipe?.subtitle ?? "",
    content: props.recipe?.content ?? "",
    image: null,
    status: props.recipe?.status ?? true,
    sort_order: props.recipe?.sort_order ?? 0,
    ...(props.recipe ? { _method: "PUT" } : {}),
});

const preview = ref(props.recipe?.image ?? null);

const onImage = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    form.image = file;
    preview.value = URL.createObjectURL(file);
};

const submit = () => {
    const url = isEdit.value
        ? route("admin.recipes.update", props.recipe.id)
        : route("admin.recipes.store");

    form.post(url, {
        forceFormData: true,
        onSuccess: () => toast.success(isEdit.value ? "Recipe updated" : "Recipe created"),
        onError: () => toast.error("Please fix the highlighted fields"),
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Recipe' : 'Add Recipe'" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? "Edit Recipe" : "Add Recipe" }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6" @submit.prevent="submit">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="r-title" class="block text-sm font-medium text-gray-700 mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input id="r-title" v-model="form.title" type="text" maxlength="120" class="input input-bordered w-full" placeholder="e.g. Grilled Chicken" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label for="r-sub" class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                            <input id="r-sub" v-model="form.subtitle" type="text" maxlength="120" class="input input-bordered w-full" placeholder="e.g. with Mango Mustard" />
                            <p v-if="form.errors.subtitle" class="mt-1 text-sm text-red-600">{{ form.errors.subtitle }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Image <span v-if="!isEdit" class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <img v-if="preview" :src="preview" alt="Recipe image preview" class="w-40 aspect-[4/3] object-cover rounded-lg bg-gray-100" />
                            <div>
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="file-input file-input-bordered w-full max-w-xs" @change="onImage" />
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP, up to 6 MB. Landscape (4:3) works best.</p>
                            </div>
                        </div>
                        <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipe (ingredients &amp; steps)</label>
                        <div class="ckeditor-container">
                            <Ckeditor v-if="editorReady" v-model="form.content" :editor="ClassicEditor" :config="editorConfig" />
                        </div>
                        <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="r-order" class="block text-sm font-medium text-gray-700 mb-1">Sort order</label>
                            <input id="r-order" v-model.number="form.sort_order" type="number" min="0" max="9999" class="input input-bordered w-full max-w-xs" />
                            <p class="mt-1 text-xs text-gray-500">Lower numbers show first.</p>
                            <p v-if="form.errors.sort_order" class="mt-1 text-sm text-red-600">{{ form.errors.sort_order }}</p>
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer w-fit md:mt-7">
                            <input v-model="form.status" type="checkbox" class="toggle toggle-primary" />
                            <span class="font-medium text-gray-700">Visible on website</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? "Saving…" : isEdit ? "Update Recipe" : "Create Recipe" }}
                        </button>
                        <Link :href="route('admin.recipes.index')" class="btn btn-ghost">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
