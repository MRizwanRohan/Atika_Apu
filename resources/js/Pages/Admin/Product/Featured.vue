<script setup>
import { ref, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import axios from "axios";
import debounce from "lodash/debounce";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    setting: { type: Object, required: true },
    selectedProduct: { type: Object, default: null },
});

const form = useForm({
    enabled: Boolean(props.setting.enabled),
    product_id: props.setting.product_id,
    badge: props.setting.badge,
    description: props.setting.description,
    highlights: [...props.setting.highlights],
    bg_color: props.setting.bg_color,
});

const selected = ref(props.selectedProduct);
const query = ref("");
const results = ref([]);
const searching = ref(false);
let searchToken = 0;

const runSearch = debounce(async (text) => {
    const token = ++searchToken;
    try {
        const { data } = await axios.get(route("admin.products.featured.search"), { params: { query: text } });
        if (token === searchToken) results.value = data;
    } catch {
        if (token === searchToken) results.value = [];
    } finally {
        if (token === searchToken) searching.value = false;
    }
}, 300);

watch(query, (text) => {
    const trimmed = text.trim();
    if (trimmed.length < 2) {
        runSearch.cancel();
        searchToken++;
        results.value = [];
        searching.value = false;
        return;
    }
    searching.value = true;
    runSearch(trimmed);
});

const choose = (product) => {
    selected.value = product;
    form.product_id = product.id;
    query.value = "";
};

const clearProduct = () => {
    selected.value = null;
    form.product_id = null;
};

const submit = () => {
    form.put(route("admin.products.featured.update"), { preserveScroll: true });
};
</script>

<template>
    <Head title="Featured Product" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Featured Product</h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form class="bg-white shadow-sm sm:rounded-lg p-6 space-y-8" @submit.prevent="submit">
                    <p class="text-sm text-gray-500">
                        Spotlight one product on the homepage, right after the first product group (e.g. "Top Sell").
                    </p>

                    <!-- Enable -->
                    <label class="flex items-center gap-4 cursor-pointer w-fit">
                        <input v-model="form.enabled" type="checkbox" class="toggle toggle-primary" />
                        <span class="font-medium text-gray-700">Show on homepage</span>
                    </label>

                    <!-- Product picker -->
                    <div class="space-y-2">
                        <label class="block font-medium text-gray-700">Product</label>

                        <div v-if="selected" class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg">
                            <img
                                v-if="selected.feature_image"
                                :src="selected.feature_image"
                                :alt="selected.name"
                                class="w-16 h-16 rounded-md object-cover bg-gray-100"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 truncate">{{ selected.name }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ selected.product_code }} · ৳{{ selected.price }}
                                    <span v-if="selected.status && selected.status !== 'Published'" class="ml-2 text-red-600">
                                        ({{ selected.status }}: hidden on homepage)
                                    </span>
                                </p>
                            </div>
                            <button type="button" class="btn btn-ghost btn-sm" @click="clearProduct">Change</button>
                        </div>

                        <div v-else class="relative">
                            <input
                                v-model="query"
                                type="search"
                                autocomplete="off"
                                placeholder="Search published products by name or code…"
                                class="input input-bordered w-full"
                            />
                            <div
                                v-if="query.trim().length >= 2"
                                class="absolute z-20 mt-1 w-full max-h-80 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
                            >
                                <p v-if="searching" class="p-3 text-sm text-gray-500">Searching…</p>
                                <p v-else-if="!results.length" class="p-3 text-sm text-gray-500">No published products found.</p>
                                <template v-else>
                                    <button
                                        v-for="product in results"
                                        :key="product.id"
                                        type="button"
                                        class="flex w-full items-center gap-3 p-2 text-left hover:bg-gray-50"
                                        @click="choose(product)"
                                    >
                                        <img
                                            v-if="product.feature_image"
                                            :src="product.feature_image"
                                            :alt="product.name"
                                            class="w-10 h-10 rounded object-cover bg-gray-100"
                                        />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-gray-900">{{ product.name }}</span>
                                            <span class="block text-xs text-gray-500">{{ product.product_code }} · ৳{{ product.price }}</span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p v-if="form.errors.product_id" class="text-sm text-red-600">{{ form.errors.product_id }}</p>
                    </div>

                    <!-- Badge -->
                    <div class="space-y-2">
                        <label for="fp-badge" class="block font-medium text-gray-700">Small label</label>
                        <input id="fp-badge" v-model="form.badge" type="text" maxlength="40" class="input input-bordered w-full max-w-sm" />
                        <p v-if="form.errors.badge" class="text-sm text-red-600">{{ form.errors.badge }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="fp-desc" class="block font-medium text-gray-700">Custom description <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea
                            id="fp-desc"
                            v-model="form.description"
                            rows="3"
                            maxlength="300"
                            class="textarea textarea-bordered w-full"
                            placeholder="Leave empty to use the product's own description."
                        ></textarea>
                        <p class="text-xs text-gray-400">{{ (form.description || "").length }}/300</p>
                        <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Highlights -->
                    <div class="space-y-2">
                        <label class="block font-medium text-gray-700">Highlight points <span class="font-normal text-gray-400">(up to 3, optional)</span></label>
                        <div class="grid sm:grid-cols-3 gap-3">
                            <input
                                v-for="(_, i) in form.highlights"
                                :key="i"
                                v-model="form.highlights[i]"
                                type="text"
                                maxlength="30"
                                :placeholder="['e.g. 100% Cotton', 'e.g. Premium Print', 'e.g. Free Delivery'][i]"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <p v-if="form.errors.highlights" class="text-sm text-red-600">{{ form.errors.highlights }}</p>
                    </div>

                    <!-- Arch color -->
                    <div class="space-y-2">
                        <label for="fp-color" class="block font-medium text-gray-700">Arch background color</label>
                        <div class="flex items-center gap-3">
                            <input id="fp-color" v-model="form.bg_color" type="color" class="w-12 h-10 p-0 border border-gray-300 rounded cursor-pointer" />
                            <span class="text-sm font-mono text-gray-600">{{ form.bg_color }}</span>
                        </div>
                        <p v-if="form.errors.bg_color" class="text-sm text-red-600">{{ form.errors.bg_color }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? "Saving…" : "Save" }}
                        </button>
                        <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Saved.</span>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
