<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductForm from '@/Components/Product/ProductForm.vue';

defineProps({
  attributeValues: Array,
  categories: Array,
  brands: Array,
  returnTo: String,
  defaultCategoryIds: { type: Array, default: () => [] },
});
</script>

<template>
  <Head title="Create Product" />
  <AdminLayout>
    <ProductForm :attribute-values="attributeValues" :categories="categories" :brands="brands" :return-to="returnTo" :default-category-ids="defaultCategoryIds" />
  </AdminLayout>
</template>
