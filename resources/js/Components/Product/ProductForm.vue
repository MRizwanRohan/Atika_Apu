<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";
import { Ckeditor } from "@ckeditor/ckeditor5-vue";
import "ckeditor5/ckeditor5.css";
import { ClassicEditor, editorConfig } from "@/Helpers/ckeditor";
import {
    PlusIcon,
    XIcon,
    TagIcon,
    BoxIcon,
    TruckIcon,
    DollarSignIcon,
    Image as ImageIcon,
    Video as VideoIcon,
    Layers,
    Save,
    ArrowLeft,
    Package,
    Sparkles,
    Check,
    ChevronRight,
    Upload,
    Grip,
    Settings,
    Eye,
    FileText,
    Palette,
    Zap,
    LayoutDashboard,
    Search,
    ChevronDown,
    Trash,
} from "lucide-vue-next";

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
    attributeValues: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    brands: {
        type: Array,
        default: () => [],
    },
});

const isEditMode = computed(() => !!props.product);
const isLayoutReady = ref(false);
const editor = ClassicEditor;

const editorConfigWithProductId = computed(() => {
    return {
        ...editorConfig,
        simpleUpload: {
            ...editorConfig.simpleUpload,
            uploadUrl: `/admin/upload?product_id=${props.product?.id || ""}`,
        },
    };
});

onMounted(() => {
    isLayoutReady.value = true;
    initializeForm();

    document.addEventListener("click", closeCategoryDropdown);

    nextTick(() => {
        if (page.props.flash?.success) {
            toast.success(page.props.flash.success);
        }
        if (page.props.flash?.error) {
            toast.error(page.props.flash.error);
        }
    });
});

onUnmounted(() => {
    document.removeEventListener("click", closeCategoryDropdown);
});

const defaultImageName = ref("");
const galleryImageItems = ref([]);
const variationImageNames = ref([]);
const isDragging = ref(false);
const uploadProgress = ref({});
const newTag = ref("");
const globalVariationPrice = ref("");
const globalVariationStock = ref("");
const globalVariationPreviousPrice = ref("");
const variationPriceErrors = ref({});
const additionalInfoOpen = ref(false);

const parseJsonField = (field) => {
    if (typeof field === "string") {
        try {
            return JSON.parse(field);
        } catch (e) {
            return [];
        }
    }
    return field || [];
};

const formatCategories = (categories, level = 0) => {
    let formatted = [];
    categories.forEach((category) => {
        formatted.push({
            id: category.id,
            name: category.name,
            level: level,
        });
        if (category.children && category.children.length > 0) {
            formatted = formatted.concat(
                formatCategories(category.children, level + 1),
            );
        }
    });
    return formatted;
};

const formattedCategories = computed(() => formatCategories(props.categories));

const isCategoryDropdownOpen = ref(false);
const categorySearchQuery = ref("");
const categoryDropdownRef = ref(null);

const toggleCategoryDropdown = () => {
    isCategoryDropdownOpen.value = !isCategoryDropdownOpen.value;
};

const closeCategoryDropdown = (e) => {
    if (
        categoryDropdownRef.value &&
        !categoryDropdownRef.value.contains(e.target)
    ) {
        isCategoryDropdownOpen.value = false;
    }
};

const toggleCategorySelection = (id) => {
    const index = form.category_ids.indexOf(id);
    if (index > -1) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(id);
    }
};

const getCategoryName = (id) => {
    const category = formattedCategories.value.find((c) => c.id === id);
    return category ? category.name : "";
};

const filteredCategories = computed(() => {
    if (!categorySearchQuery.value) return formattedCategories.value;
    const q = categorySearchQuery.value.toLowerCase();
    return formattedCategories.value.filter((cat) =>
        cat.name.toLowerCase().includes(q),
    );
});

const removedVariations = ref(new Set());
const selectedAttributesMap = ref({});
const allAttributes = ref([]);

const selectedAttributes = computed(() => {
    return allAttributes.value.map((attr) => ({
        attribute_name: attr.attribute_name,
        values: selectedAttributesMap.value[attr.attribute_name] || [],
    }));
});

watch(
    () => props.attributeValues,
    (newVal) => {
        // We merge with our local custom attributes (those having product_id or temp_id)
        const customAttrs = allAttributes.value.filter(
            (attr) => attr.product_id !== null && attr.product_id !== undefined
        );
        
        const merged = JSON.parse(JSON.stringify(newVal || []));
        customAttrs.forEach((customAttr) => {
            if (!merged.some((a) => a.attribute_name === customAttr.attribute_name)) {
                merged.push(customAttr);
            }
        });

        allAttributes.value = merged;

        allAttributes.value.forEach((attr) => {
            if (!selectedAttributesMap.value[attr.attribute_name]) {
                selectedAttributesMap.value[attr.attribute_name] = [];
            }
        });
    },
    { immediate: true, deep: true },
);

// Custom Attributes state and functions
const showAddCustomAttributeModal = ref(false);
const newCustomAttrName = ref("");
const newCustomAttrValuesText = ref("");
const newValueInputs = ref({});

const addCustomAttribute = () => {
    const name = newCustomAttrName.value.trim();
    if (!name) {
        toast.error("Attribute name cannot be empty");
        return;
    }
    
    const nameUpper = name.toUpperCase();
    const exists = allAttributes.value.some(
        (attr) => attr.attribute_name.toUpperCase() === nameUpper
    );
    if (exists) {
        toast.error("An attribute with this name already exists");
        return;
    }

    const vals = newCustomAttrValuesText.value
        .split(",")
        .map((v) => v.trim())
        .filter((v) => v.length > 0);
    
    if (vals.length === 0) {
        toast.error("Please provide at least one value");
        return;
    }

    const tempAttrId = "temp_attr_" + Date.now();
    const newAttrValues = vals.map((v, index) => ({
        id: null,
        temp_id: `temp_val_${tempAttrId}_${index}`,
        value: v,
        color: null,
    }));

    const newAttr = {
        id: null,
        temp_id: tempAttrId,
        attribute_name: name,
        product_id: props.product?.id || "new_product",
        values: newAttrValues,
    };

    allAttributes.value.push(newAttr);
    newValueInputs.value[name] = "";
    
    // Automatically select all values
    selectedAttributesMap.value[name] = newAttrValues.map((v) => v.temp_id);

    newCustomAttrName.value = "";
    newCustomAttrValuesText.value = "";
    showAddCustomAttributeModal.value = false;
    toast.success(`Custom attribute "${name}" added`);
};

const removeCustomAttribute = (attr) => {
    if (confirm(`Are you sure you want to remove "${attr.attribute_name}"?`)) {
        allAttributes.value = allAttributes.value.filter(
            (a) => a.attribute_name !== attr.attribute_name
        );
        delete selectedAttributesMap.value[attr.attribute_name];
        delete newValueInputs.value[attr.attribute_name];
        toast.success(`Removed "${attr.attribute_name}"`);
    }
};

const addValueToCustomAttribute = (attr) => {
    const valText = (newValueInputs.value[attr.attribute_name] || "").trim();
    if (!valText) return;

    const exists = attr.values.some(
        (v) => v.value.toUpperCase() === valText.toUpperCase()
    );
    if (exists) {
        toast.error("This value already exists");
        return;
    }

    const tempValId = `temp_val_${attr.id || attr.temp_id}_${Date.now()}`;
    const newValue = {
        id: null,
        temp_id: tempValId,
        value: valText,
        color: null,
    };

    attr.values.push(newValue);
    newValueInputs.value[attr.attribute_name] = "";

    const current = selectedAttributesMap.value[attr.attribute_name] || [];
    selectedAttributesMap.value[attr.attribute_name] = [...current, tempValId];
    toast.success(`Added value "${valText}"`);
};

const deleteValueFromCustomAttribute = (attr, val) => {
    if (confirm(`Remove option "${val.value}"?`)) {
        const valIdToCompare = val.id || val.temp_id;
        attr.values = attr.values.filter(
            (v) => (v.id || v.temp_id) !== valIdToCompare
        );
        const current = selectedAttributesMap.value[attr.attribute_name] || [];
        selectedAttributesMap.value[attr.attribute_name] = current.filter(
            (id) => id !== valIdToCompare
        );
        toast.success(`Removed option "${val.value}"`);
    }
};


const form = useForm({
    _method: isEditMode.value ? "PUT" : "POST",
    name: "",
    product_code: "",
    category_ids: [],
    brand_id: "",
    short_description: "",
    description: "",
    status: "Published",
    type: "simple",
    is_daily_product: false,
    is_pre_order: false,
    price: "",
    purchase_price: "",
    previous_price: "",
    total_purchase_price: "",
    feature_image: null,
    feature_image_preview: null,
    gallery_images: [],
    existing_gallery_images: [],
    deleted_gallery_images: [],
    youtube_video: "",
    product_tags: [],
    specification: [],
    stock: "",
    fake_sold_count: 0,
    remarks: "",
    meta_title: "",
    meta_description: "",
    variations: [],
});

const page = usePage();

watch(
    () => page.props.flash?.success,
    (val) => {
        if (val) toast.success(val);
    },
);

watch(
    () => page.props.flash?.error,
    (val) => {
        if (val) toast.error(val);
    },
);

const initializeForm = () => {
    if (isEditMode.value) {
        form.name = props.product.name;
        form.product_code = props.product.product_code;
        form.category_ids = props.product.categories?.length
            ? props.product.categories.map((c) => c.id)
            : props.product.category_id
              ? [props.product.category_id]
              : [];
        form.brand_id = props.product.brand_id;
        form.short_description = props.product.short_description || "";
        form.description = props.product.description || "";
        form.status = props.product.status;
        form.type = props.product.type;
        form.is_daily_product = Boolean(props.product.is_daily_product == 1);
        form.is_pre_order = Boolean(props.product.is_pre_order == 1);
        form.price = props.product.price;
        form.purchase_price = props.product.purchase_price || "";
        form.previous_price = props.product.previous_price || "";
        form.remarks = props.product.remarks || "";
        form.meta_title = props.product.meta_title || "";
        form.meta_description = props.product.meta_description || "";
        form.stock = props.product.stock || "";
        form.fake_sold_count = props.product.fake_sold_count || 0;

        form.product_tags = parseJsonField(props.product.product_tags);
        form.specification = parseJsonField(props.product.specification);

        if (props.product.feature_image) {
            form.feature_image_preview = props.product.feature_image;
            defaultImageName.value = props.product.feature_image
                .split("/")
                .pop();
        }

        const gallery = parseJsonField(props.product.gallery_images);
        if (Array.isArray(gallery) && gallery.length > 0) {
            form.existing_gallery_images = [...gallery];
            galleryImageItems.value = gallery.map((path) => ({
                type: "existing",
                path,
                preview: path,
                name: path.split("/").pop(),
            }));
        }

        form.youtube_video = props.product.youtube_video || "";

        if (
            props.product.type === "variable" &&
            props.product.variations?.length > 0
        ) {
            initializeVariationsFromProduct();
        }
    }
};

const initializeVariationsFromProduct = () => {
    const attributeValuesByName = {};
    props.product.variations.forEach((variation) => {
        variation.attributes.forEach((attr) => {
            if (attr.value && attr.value.attribute) {
                const attributeName = attr.value.attribute.name;
                const attributeValueId = attr.attribute_value_id;
                if (!attributeValuesByName[attributeName])
                    attributeValuesByName[attributeName] = [];
                if (
                    !attributeValuesByName[attributeName].includes(
                        attributeValueId,
                    )
                ) {
                    attributeValuesByName[attributeName].push(attributeValueId);
                }
            }
        });
    });

    allAttributes.value.forEach((attr) => {
        selectedAttributesMap.value[attr.attribute_name] =
            attributeValuesByName[attr.attribute_name] || [];
    });

    form.variations = props.product.variations.map((variation) => {
        const normalizedAttributes = variation.attributes.map((attr) => {
            const attributeName = attr.value?.attribute?.name || "Unknown";
            const attributeValueId = attr.attribute_value_id;
            const attributeGroup = allAttributes.value.find(
                (a) => a.attribute_name === attributeName,
            );
            const attributeValueLabel =
                attributeGroup?.values.find((v) => (v.id === attributeValueId || (v.temp_id && v.temp_id === attributeValueId)))
                    ?.value ||
                attr.value?.value ||
                "Unknown";

            return {
                attribute_name: attributeName,
                attribute_value_id: attributeValueId,
                attribute_value_label: attributeValueLabel,
            };
        });

        return {
            id: variation.id,
            price: variation.price,
            previous_price: variation.previous_price || "",
            purchase_price: variation.purchase_price || "",
            stock: variation.stock || "0",
            image_path: null,
            image_preview: variation.image_path,
            attributes: normalizedAttributes,
        };
    });
};

const hasValidationErrors = computed(() => {
    if (form.type === "simple" && priceError.value) return true;
    const values = Object.values(variationPriceErrors.value || {});
    return values.some((v) => v && v.toString().trim().length > 0);
});

const calculateTotalStock = () => {
    if (form.type === "variable") {
        if (!form.variations || form.variations.length === 0) return 0;
        return form.variations.reduce((sum, variation) => {
            const stock = variation.stock === "" ? 0 : Number(variation.stock);
            return sum + (isNaN(stock) ? 0 : stock);
        }, 0);
    }
    const simpleStock = form.stock === "" ? 0 : Number(form.stock);
    return isNaN(simpleStock) ? 0 : simpleStock;
};

watch(
    () => form.type,
    (newType) => {
        if (newType === "variable") {
            form.price = 0;
            form.previous_price = 0;
            priceError.value = "";
        }
    },
);

const priceError = ref("");
watch(
    [() => form.price, () => form.previous_price],
    ([regularPrice, previousPrice]) => {
        priceError.value = "";
        if (previousPrice && regularPrice) {
            const regular = parseFloat(regularPrice);
            const previous = parseFloat(previousPrice);
            if (!isNaN(regular) && !isNaN(previous) && previous <= regular) {
                priceError.value =
                    "Previous price must be greater than regular price";
            }
        }
    },
    { immediate: true },
);

const generateCombinations = (arrays) => {
    const nonEmptyArrays = arrays.filter((arr) => arr && arr.length > 0);
    if (nonEmptyArrays.length === 0) return [];
    let results = nonEmptyArrays[0].map((item) => [item]);
    for (let i = 1; i < nonEmptyArrays.length; i++) {
        const newResults = [];
        for (const existingCombo of results) {
            for (const item of nonEmptyArrays[i]) {
                newResults.push([...existingCombo, item]);
            }
        }
        results = newResults;
    }
    return results;
};

const isValidVariation = (variation) => {
    if (
        !variation ||
        !variation.attributes ||
        !Array.isArray(variation.attributes)
    )
        return false;
    return variation.attributes.every(
        (attr) =>
            attr && attr.attribute_name && attr.attribute_value_id != null,
    );
};

const attributeCombinations = computed(() => {
    const selectedArrays = selectedAttributes.value
        .filter((attr) => attr.values && attr.values.length > 0)
        .map((attr) => {
            return attr.values.map((valueId) => ({
                attribute_name: attr.attribute_name,
                attribute_value_id: valueId,
                attribute_value_label:
                    allAttributes.value
                        .find((a) => a.attribute_name === attr.attribute_name)
                        ?.values.find((v) => (v.id === valueId || (v.temp_id && v.temp_id === valueId)))?.value || "",
            }));
        });
    const combinations = generateCombinations(selectedArrays);
    return combinations.filter((combo) => combo.length > 0);
});

const getVariationKey = (attributes) => {
    if (!attributes || attributes.length === 0) return "";
    return attributes
        .map((attr) => `${attr.attribute_name}:${attr.attribute_value_id}`)
        .sort()
        .join("|");
};

watch(
    [attributeCombinations, removedVariations],
    ([newCombinations, removed]) => {
        if (isEditMode.value && !isLayoutReady.value) return;

        // Get keys of currently existing variations in the form
        const existingKeys = new Set();
        form.variations.forEach((v) => {
            const key = getVariationKey(v.attributes);
            if (key) existingKeys.add(key);
        });

        // Only add NEW combinations that are not already present and weren't manually removed
        newCombinations.forEach((combination) => {
            const key = getVariationKey(combination);
            if (!existingKeys.has(key) && !removed.has(key)) {
                form.variations.push({
                    id: null,
                    price: "0",
                    previous_price: "",
                    purchase_price: "",
                    stock: "0",
                    image_path: null,
                    image_preview: null,
                    attributes: combination,
                });
            }
        });
    },
    { deep: true },
);

const handleFeatureImageUpload = (event) => {
    const file = event.target?.files ? event.target.files[0] : event[0];
    if (file) {
        form.feature_image = file;
        form.feature_image_preview = URL.createObjectURL(file);
        defaultImageName.value = file.name;
    }
};

const handleGalleryImageUpload = (event) => {
    const files = event.target?.files ? Array.from(event.target.files) : event;
    files.forEach((file) => {
        form.gallery_images.push(file);
        galleryImageItems.value.push({
            type: "new",
            file,
            preview: URL.createObjectURL(file),
            name: file.name,
        });
    });
};

const handleVariationImageUpload = (event, index) => {
    const file = event.target?.files ? event.target.files[0] : event[0];
    if (file) {
        form.variations[index].image_path = file;
        form.variations[index].image_preview = URL.createObjectURL(file);
    }
};

const removeFeatureImage = () => {
    form.feature_image = null;
    form.feature_image_preview = null;
    defaultImageName.value = "";
};

const removeGalleryImage = (index) => {
    const item = galleryImageItems.value[index];
    if (item.type === "new") {
        const fileIndex = form.gallery_images.indexOf(item.file);
        if (fileIndex !== -1) form.gallery_images.splice(fileIndex, 1);
    } else {
        form.existing_gallery_images = form.existing_gallery_images.filter(
            (p) => p !== item.path,
        );
        form.deleted_gallery_images.push(item.path);
    }
    galleryImageItems.value.splice(index, 1);
};

const removeVariation = (index) => {
    const variation = form.variations[index];
    const key = getVariationKey(variation.attributes);
    removedVariations.value.add(key);
    form.variations.splice(index, 1);
};

const applyGlobalPrice = () => {
    if (globalVariationPrice.value !== "") {
        form.variations.forEach((v) => (v.price = globalVariationPrice.value));
    }
};

const applyGlobalStock = () => {
    if (globalVariationStock.value !== "") {
        form.variations.forEach((v) => (v.stock = globalVariationStock.value));
    }
};

const applyGlobalPreviousPrice = () => {
    if (globalVariationPreviousPrice.value !== "") {
        form.variations.forEach(
            (v) => (v.previous_price = globalVariationPreviousPrice.value),
        );
    }
};

const validateVariationPrice = (index) => {
    const v = form.variations[index];
    if (!v) return;
    const p = parseFloat(v.price);
    const prev = parseFloat(v.previous_price);
    if (!isNaN(prev) && !isNaN(p) && prev <= p) {
        variationPriceErrors.value[index] =
            "Previous price must be greater than price";
    } else {
        delete variationPriceErrors.value[index];
    }
};

const addTag = () => {
    if (newTag.value.trim()) {
        form.product_tags.push(newTag.value.trim());
        newTag.value = "";
    }
};

const removeTag = (index) => form.product_tags.splice(index, 1);

const addSpecification = () =>
    form.specification.push({ title: "", value: "" });
const removeSpecification = (index) => form.specification.splice(index, 1);

const submit = () => {
    if (form.type === "variable") {
        if (form.variations.length === 0) {
            toast.error("Variable product requires at least one variation.");
            return;
        }
    }

    form.transform((data) => {
        const normalizedVariations = Array.isArray(data.variations)
            ? data.variations.map((variation) => ({
                  ...variation,
                  stock:
                      variation.stock === "" || variation.stock == null
                          ? 0
                          : Number(variation.stock),
              }))
            : [];

        const normalizedStock =
            data.type === "variable"
                ? normalizedVariations.reduce((sum, variation) => {
                      const stock = Number(variation.stock);
                      return sum + (isNaN(stock) ? 0 : stock);
                  }, 0)
                : data.is_pre_order
                  ? 0
                  : data.stock === "" || data.stock == null
                    ? 0
                    : Number(data.stock);

        const custom_attributes = allAttributes.value
            .filter((attr) => attr.product_id !== null && attr.product_id !== undefined)
            .map((attr) => ({
                id: attr.id || null,
                name: attr.attribute_name,
                values: attr.values.map((val) => ({
                    id: val.id || null,
                    temp_id: val.temp_id || null,
                    value: val.value,
                    color: val.color || null,
                })),
            }));

        return {
            ...data,
            stock: isNaN(normalizedStock) ? 0 : normalizedStock,
            fake_sold_count:
                data.fake_sold_count === "" || data.fake_sold_count == null
                    ? 0
                    : Number(data.fake_sold_count),
            variations: normalizedVariations,
            custom_attributes,
        };
    });

    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                isEditMode.value ? "Product updated!" : "Product created!",
            );
            router.get(route("admin.products.index"));
        },
        onError: (errors) => {
            if (Object.keys(errors).length > 0) {
                toast.error(Object.values(errors)[0]);
            }
        },
    };

    if (isEditMode.value) {
        form.post(route("admin.products.update", props.product.id), options);
    } else {
        form.post(route("admin.products.store"), options);
    }
};

const sections = [
    { id: "general", name: "General Information", icon: TagIcon },
    { id: "pricing", name: "Pricing & Type", icon: DollarSignIcon },
    { id: "media", name: "Media & Gallery", icon: ImageIcon },
    { id: "inventory", name: "Inventory Control", icon: BoxIcon },
    {
        id: "attributes",
        name: "Product Attributes",
        icon: Layers,
        condition: () => form.type === "variable",
    },
    {
        id: "variations",
        name: "Price Variations",
        icon: Palette,
        condition: () => form.type === "variable",
    },
    { id: "seo", name: "Additional Info", icon: Eye },
];

const scrollTo = (id) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "start" });
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#F9FAFB] pb-20">
        <header
            class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200 px-4 md:px-6 py-3 md:py-4"
        >
            <div class="w-full mx-auto flex items-center justify-between">
                <div class="flex items-center gap-2 md:gap-4">
                    <button
                        @click="router.visit(route('admin.products.index'))"
                        class="p-2 hover:bg-gray-100 rounded-xl transition-colors"
                    >
                        <ArrowLeft class="w-5 h-5 text-gray-700" />
                    </button>
                    <div>
                        <h1
                            class="text-lg md:text-xl font-[600] text-gray-900 tracking-tight leading-none"
                        >
                            {{ isEditMode ? "Edit Product" : "New Product" }}
                        </h1>
                        <p
                            class="hidden sm:block text-[10px] md:text-xs text-gray-600 font-semibold mt-1 uppercase tracking-wider"
                        >
                            {{
                                isEditMode
                                    ? props.product.name
                                    : "Catalog Setup"
                            }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 md:gap-3">
                    <button
                        @click="submit"
                        :disabled="form.processing || hasValidationErrors"
                        class="flex items-center gap-2 px-4 md:px-6 py-2 md:py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-600/30 transition-all font-[600] text-xs md:text-sm disabled:opacity-50"
                    >
                        <Save class="w-4 h-4" />
                        <span class="hidden xs:inline">{{
                            form.processing
                                ? "Saving..."
                                : isEditMode
                                  ? "Update"
                                  : "Publish"
                        }}</span>
                        <span class="xs:hidden">{{
                            form.processing ? "..." : "Save"
                        }}</span>
                    </button>
                </div>
            </div>
        </header>

        <main
            class="w-full mx-auto px-1 sm:px-4 md:px-6 mt-3 md:mt-6 flex flex-col gap-4 md:gap-6"
        >
            <!-- Form Content -->
            <div class="flex-1 space-y-3 md:space-y-5 w-full max-w-4xl mx-auto">


                                <!-- Section: Media -->
                <section id="media" class="scroll-mt-28">
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <div class="flex items-center gap-2 mb-4 md:mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-orange-600 flex items-center justify-center shadow-lg shadow-orange-100"
                            >
                                <ImageIcon class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                >
                                    Media Assets
                                </h2>
                                <p
                                    class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                >
                                    Visuals
                                </p>
                            </div>
                        </div>

                        <div class="space-y-5 md:space-y-6">
                            <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-400 uppercase tracking-[0.1em] mb-2"
                                    >Feature Image</label
                                >
                                <div
                                    @click="$refs.featureInput.click()"
                                    class="relative h-48 md:h-56 w-full bg-gray-50 rounded-xl border border-dashed border-gray-200 hover:border-indigo-500 hover:bg-indigo-50/10 cursor-pointer transition-all flex flex-col items-center justify-center group overflow-hidden"
                                >
                                    <img
                                        v-if="form.feature_image_preview"
                                        :src="form.feature_image_preview"
                                        class="absolute inset-0 w-full h-full object-contain"
                                    />
                                    <div v-else class="text-center">
                                        <Upload
                                            class="w-8 h-8 text-gray-300 mx-auto mb-1 group-hover:scale-110 transition-transform"
                                        />
                                        <p
                                            class="text-[9px] font-[600] text-gray-400 uppercase"
                                        >
                                            Upload Main
                                        </p>
                                    </div>
                                    <div
                                        v-if="form.feature_image_preview"
                                        @click.stop="removeFeatureImage"
                                        class="absolute top-2 right-2 p-1.5 bg-white/80 backdrop-blur shadow-sm rounded-lg text-red-500 hover:bg-red-50 transition-all"
                                    >
                                        <XIcon class="w-3.5 h-3.5" />
                                    </div>
                                    <input
                                        ref="featureInput"
                                        type="file"
                                        @change="handleFeatureImageUpload"
                                        accept="image/*"
                                        class="hidden"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-400 uppercase tracking-[0.1em] mb-2"
                                    >Product Gallery</label
                                >
                                <div
                                    class="grid grid-cols-5 md:grid-cols-5 lg:grid-cols-7 gap-2 md:gap-3"
                                >
                                    <div
                                        v-for="(item, idx) in galleryImageItems"
                                        :key="idx"
                                        class="relative aspect-square rounded-lg bg-gray-50 border border-gray-100 overflow-hidden group"
                                    >
                                        <img
                                            :src="item.preview"
                                            class="w-full h-full object-cover"
                                        />
                                        <button
                                            @click.prevent="
                                                removeGalleryImage(idx)
                                            "
                                            class="absolute top-1.5 right-1.5 p-1 bg-black/40 text-white rounded-md opacity-0 group-hover:opacity-100 transition-all"
                                        >
                                            <XIcon class="w-3 h-3" />
                                        </button>
                                    </div>
                                    <button
                                        @click.prevent="
                                            $refs.galleryInput.click()
                                        "
                                        class="aspect-square rounded-lg border border-dashed border-gray-200 hover:border-indigo-500 hover:bg-indigo-50/10 transition-all flex flex-col items-center justify-center gap-1 group"
                                    >
                                        <PlusIcon
                                            class="w-5 h-5 text-gray-300 group-hover:text-indigo-400 transition-colors"
                                        />
                                        <span
                                            class="text-[8px] font-[600] text-gray-400 group-hover:text-indigo-400 uppercase"
                                            >Add</span
                                        >
                                    </button>
                                    <input
                                        ref="galleryInput"
                                        type="file"
                                        multiple
                                        @change="handleGalleryImageUpload"
                                        accept="image/*"
                                        class="hidden"
                                    />
                                </div>
                            </div>

                        </div>
                    </div>
                </section>


                <!-- Section: General -->
                <section
                    id="general"
                    class="scroll-mt-28 space-y-3 md:space-y-4"
                >
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <div class="flex items-center gap-2 mb-4 md:mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-100"
                            >
                                <FileText class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                >
                                    General Information
                                </h2>
                                <p
                                    class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                >
                                    Basic Details
                                </p>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-x-3 md:gap-x-6 gap-y-3 md:gap-y-4"
                        >
                            <div class="col-span-2">
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                    >Product Name *</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] placeholder:text-gray-300 text-sm"
                                    placeholder="e.g. Ultra Soft Cotton T-Shirt"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="mt-1 text-[10px] text-red-600 font-[600]"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                    >Product Code (SKU)</label
                                >
                                <input
                                    v-model="form.product_code"
                                    type="text"
                                    class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                    placeholder="SKU-8822"
                                />
                            </div>

                            <div class="relative" ref="categoryDropdownRef">
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                    >Categories *</label
                                >
                                
                                <!-- Dropdown Trigger Button -->
                                <div
                                    @click="toggleCategoryDropdown"
                                    class="min-h-[42px] w-full px-3 md:px-4 py-2 bg-gray-50 border border-gray-100 focus-within:bg-white focus-within:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all cursor-pointer flex items-center justify-between text-gray-900 text-sm font-[600]"
                                >
                                    <!-- Selected Tags or Placeholder -->
                                    <div class="flex flex-wrap gap-1 max-w-[90%]">
                                        <span v-if="form.category_ids.length === 0" class="text-gray-400">
                                            Select Categories
                                        </span>
                                        <span
                                            v-for="catId in form.category_ids"
                                            :key="catId"
                                            class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 text-xs px-2 py-0.5 rounded-md border border-indigo-100"
                                        >
                                            {{ getCategoryName(catId) }}
                                            <button
                                                type="button"
                                                @click.stop="toggleCategorySelection(catId)"
                                                class="text-indigo-400 hover:text-indigo-600 focus:outline-none"
                                            >
                                                <XIcon class="w-3 h-3" />
                                            </button>
                                        </span>
                                    </div>
                                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': isCategoryDropdownOpen }" />
                                </div>

                                <!-- Dropdown Panel -->
                                <div
                                    v-show="isCategoryDropdownOpen"
                                    class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-xl max-h-80 flex flex-col overflow-hidden animate-fadeIn"
                                >
                                    <!-- Search Input -->
                                    <div class="p-2 border-b border-gray-50 flex items-center gap-2 bg-gray-50/50">
                                        <Search class="w-4 h-4 text-gray-400 ml-1" />
                                        <input
                                            v-model="categorySearchQuery"
                                            type="text"
                                            class="w-full bg-transparent border-none outline-none text-xs text-gray-800 placeholder-gray-400 focus:ring-0 py-1"
                                            placeholder="Search categories..."
                                        />
                                    </div>

                                    <!-- Option List -->
                                    <div class="overflow-y-auto py-1 flex-1 max-h-60 custom-scrollbar">
                                        <div
                                            v-for="cat in filteredCategories"
                                            :key="cat.id"
                                            @click="toggleCategorySelection(cat.id)"
                                            class="px-4 py-2 hover:bg-indigo-50/50 flex items-center justify-between cursor-pointer transition-colors"
                                            :style="{ paddingLeft: `${16 + cat.level * 16}px` }"
                                        >
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    :checked="form.category_ids.includes(cat.id)"
                                                    @click.stop="toggleCategorySelection(cat.id)"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                                                />
                                                <span 
                                                    class="text-sm text-gray-700 font-[500]"
                                                    :class="{ 'font-[600] text-gray-900': form.category_ids.includes(cat.id) }"
                                                >
                                                    {{ cat.name }}
                                                </span>
                                            </div>
                                            <!-- Sub-category indicator -->
                                            <span 
                                                v-if="cat.level > 0" 
                                                class="text-[10px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded"
                                            >
                                                Sub
                                            </span>
                                        </div>
                                        <div v-if="filteredCategories.length === 0" class="p-4 text-center text-xs text-gray-400">
                                            No categories found
                                        </div>
                                    </div>
                                </div>
                                <p
                                    v-if="form.errors.category_ids"
                                    class="text-red-500 text-[11px] mt-1 font-[500]"
                                >
                                    {{ form.errors.category_ids }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                    >Brand</label
                                >
                                <select
                                    v-model="form.brand_id"
                                    class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all appearance-none cursor-pointer font-[600] text-gray-900 text-sm"
                                >
                                    <option value="">No Brand</option>
                                    <option
                                        v-for="brand in brands"
                                        :key="brand.id"
                                        :value="brand.id"
                                    >
                                        {{ brand.brand_name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                    >Status</label
                                >
                                <div class="relative group">
                                    <select
                                        v-model="form.status"
                                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all appearance-none cursor-pointer font-[600] text-gray-900 pr-10 text-sm"
                                    >
                                        <option value="Published">
                                            Published
                                        </option>
                                        <option value="Unpublished">
                                            Unpublished
                                        </option>
                                    </select>
                                    <div
                                        class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 group-focus-within:text-indigo-600 transition-colors"
                                    >
                                        <ChevronRight
                                            class="w-4 h-4 rotate-90"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-2">
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                    >Description</label
                                >
                                <div class="ddes">
                                    <textarea
                                        v-model="form.description"
                                        placeholder="Product Description"
                                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                        rows="5"
                                    ></textarea>
                                </div>
                                <p
                                    v-if="form.errors.description"
                                    class="mt-1 text-[10px] text-red-600 font-[600]"
                                >
                                    {{ form.errors.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section: Pricing -->
                <section id="pricing" class="scroll-mt-28">
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <div class="flex items-center gap-2 mb-4 md:mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-100"
                            >
                                <DollarSignIcon class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                >
                                    Pricing & Configuration
                                </h2>
                                <p
                                    class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                >
                                    Financials
                                </p>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-4 md:gap-6"
                        >
                            <div class="col-span-2">
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-2"
                                    >Product Architecture</label
                                >
                                <div class="grid grid-cols-2 gap-2 sm:gap-4">
                                    <button
                                        @click.prevent="form.type = 'simple'"
                                        :class="[
                                            form.type === 'simple'
                                                ? 'border-indigo-600 ring-2 ring-indigo-50 bg-indigo-50/10'
                                                : 'border-gray-100 bg-gray-50 hover:bg-gray-100',
                                        ]"
                                        class="p-3 rounded-xl border text-left transition-all"
                                    >
                                        <Package
                                            class="w-4 h-4 mb-1"
                                            :class="
                                                form.type === 'simple'
                                                    ? 'text-indigo-600'
                                                    : 'text-gray-400'
                                            "
                                        />
                                        <p
                                            class="text-xs font-[600] text-gray-900"
                                        >
                                            Simple
                                        </p>
                                        <p
                                            class="text-[9px] text-gray-500 mt-0.5 uppercase tracking-tighter"
                                        >
                                            Single variation
                                        </p>
                                    </button>
                                    <button
                                        @click.prevent="form.type = 'variable'"
                                        :class="[
                                            form.type === 'variable'
                                                ? 'border-indigo-600 ring-2 ring-indigo-50 bg-indigo-50/10'
                                                : 'border-gray-100 bg-gray-50 hover:bg-gray-100',
                                        ]"
                                        class="p-3 rounded-xl border text-left transition-all"
                                    >
                                        <Layers
                                            class="w-4 h-4 mb-1"
                                            :class="
                                                form.type === 'variable'
                                                    ? 'text-indigo-600'
                                                    : 'text-gray-400'
                                            "
                                        />
                                        <p
                                            class="text-xs font-[600] text-gray-900"
                                        >
                                            Variable
                                        </p>
                                        <p
                                            class="text-[9px] text-gray-500 mt-0.5 uppercase tracking-tighter"
                                        >
                                            Multiple options
                                        </p>
                                    </button>
                                </div>
                            </div>

                            <template v-if="form.type === 'simple'">
                                <div>
                                    <label
                                        class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                        >Regular Price (৳) *</label
                                    >
                                    <div class="relative group">
                                        <input
                                            v-model="form.price"
                                            type="number"
                                            class="w-full pl-10 pr-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-500 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                            placeholder="0.00"
                                        />
                                        <span
                                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-[600] text-sm"
                                            >৳</span
                                        >
                                    </div>
                                    <p
                                        v-if="form.errors.price"
                                        class="mt-1 text-[10px] text-red-500 font-semibold"
                                    >
                                        {{ form.errors.price }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                        >Previous Price (৳)</label
                                    >
                                    <div class="relative">
                                        <input
                                            v-model="form.previous_price"
                                            type="number"
                                            class="w-full pl-10 pr-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-500 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                            placeholder="0.00"
                                        />
                                        <span
                                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-[600] text-sm"
                                            >৳</span
                                        >
                                    </div>
                                    <p
                                        v-if="priceError"
                                        class="mt-1 text-[10px] text-red-500 font-semibold md:max-w-[200px]"
                                    >
                                        {{ priceError }}
                                    </p>
                                </div>
                            </template>

                            <div
                                v-else
                                class="col-span-2 p-6 rounded-2xl border-2 border-dashed border-indigo-100 bg-indigo-50/20"
                            >
                                <div
                                    class="flex items-center gap-4 text-indigo-700"
                                >
                                    <Zap class="w-6 h-6" />
                                    <div>
                                        <p class="text-sm font-[600]">
                                            Dynamic Pricing Enabled
                                        </p>
                                        <p
                                            class="text-xs opacity-80 font-medium"
                                        >
                                            Prices will be defined per variation
                                            in the dedicated section below.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- Section: Inventory -->
                <section id="inventory" class="scroll-mt-28">
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <div class="flex items-center gap-2 mb-4 md:mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-100"
                            >
                                <BoxIcon class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                >
                                    Inventory Control
                                </h2>
                                <p
                                    class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                >
                                    Stock
                                </p>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 md:gap-5"
                        >
                            <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-2"
                                    >Stock Policy</label
                                >
                                <label
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg md:rounded-xl border border-transparent hover:border-indigo-100 cursor-pointer transition-all"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="p-1.5 bg-indigo-600 rounded-md"
                                        >
                                            <TruckIcon
                                                class="w-3.5 h-3.5 text-white"
                                            />
                                        </div>
                                        <span
                                            class="text-xs font-[600] text-gray-900 uppercase tracking-tight"
                                            >Pre-Order</span
                                        >
                                    </div>
                                    <input
                                        type="checkbox"
                                        v-model="form.is_pre_order"
                                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </label>
                            </div>

                            <div class="pt-5 border-t border-gray-100">
                                <div
                                    class="flex items-center gap-2 mb-3 md:mb-4"
                                >
                                    <Sparkles class="w-4 h-4 text-indigo-600" />
                                    <h3
                                        class="text-[11px] font-[600] text-gray-900 uppercase tracking-tight"
                                    >
                                        Conversion Boosters
                                    </h3>
                                </div>
                                <div class="grid grid-cols-2 gap-3 md:gap-4">
                                    <div>
                                        <label
                                            class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-2"
                                            >Total Quantity</label
                                        >
                                        <div
                                            v-if="
                                                form.type === 'simple' &&
                                                !form.is_pre_order
                                            "
                                        >
                                            <input
                                                v-model="form.stock"
                                                type="number"
                                                class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                                placeholder="0"
                                            />
                                        </div>
                                        <div
                                            v-else-if="form.is_pre_order"
                                            class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-emerald-50 text-emerald-700 rounded-lg md:rounded-xl font-[600] flex items-center gap-2 border border-emerald-100 text-xs uppercase tracking-tight"
                                        >
                                            <Zap class="w-3.5 h-3.5" />
                                            <span>Infinite (Pre-Order)</span>
                                        </div>
                                        <div
                                            v-else
                                            class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-100 text-gray-700 rounded-lg md:rounded-xl font-[600] flex items-center gap-2 border border-gray-200 text-xs uppercase tracking-tight"
                                        >
                                            <Layers class="w-3.5 h-3.5" />
                                            <span
                                                >{{
                                                    calculateTotalStock()
                                                }}
                                                (Synced)</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                            >Fake Sold Count</label
                                        >
                                        <input
                                            v-model="form.fake_sold_count"
                                            type="number"
                                            class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                            placeholder="e.g. 1500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section: Attributes -->
                <section
                    v-if="form.type === 'variable'"
                    id="attributes"
                    class="scroll-mt-28"
                >
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <div class="flex items-center gap-2 mb-4 md:mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center shadow-lg shadow-purple-100"
                            >
                                <Layers class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                >
                                    Product Attributes
                                </h2>
                                <p
                                    class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                >
                                    Options
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="allAttributes.length === 0"
                            class="p-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200"
                        >
                            <p
                                class="text-gray-500 text-xs font-[600] uppercase tracking-widest leading-none"
                            >
                                No system attributes defined.
                            </p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="attr in allAttributes"
                                :key="attr.attribute_name"
                                class="p-3 bg-gray-50 rounded-xl border border-gray-100"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <h3
                                        class="text-[9px] font-[600] text-gray-500 uppercase tracking-widest flex items-center gap-2"
                                    >
                                        <Grip class="w-3 h-3" />
                                        {{ attr.attribute_name }}
                                        <span v-if="attr.product_id" class="text-[8px] bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded font-[700] uppercase">Product Specific</span>
                                    </h3>
                                    <button 
                                        v-if="attr.product_id" 
                                        type="button" 
                                        @click="removeCustomAttribute(attr)"
                                        class="text-gray-400 hover:text-red-500 transition-colors p-1"
                                        title="Remove Custom Attribute"
                                    >
                                        <Trash class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    <div
                                        v-for="val in attr.values"
                                        :key="val.id || val.temp_id"
                                        class="relative group"
                                    >
                                        <button
                                            @click.prevent="
                                                () => {
                                                    const valId = val.id || val.temp_id;
                                                    const current =
                                                        selectedAttributesMap[
                                                            attr.attribute_name
                                                        ] || [];
                                                    if (current.includes(valId)) {
                                                        selectedAttributesMap[
                                                            attr.attribute_name
                                                        ] = current.filter(
                                                            (id) => id !== valId,
                                                        );
                                                    } else {
                                                        selectedAttributesMap[
                                                            attr.attribute_name
                                                        ] = [...current, valId];
                                                    }
                                                }
                                            "
                                            :class="[
                                                selectedAttributesMap[
                                                    attr.attribute_name
                                                ]?.includes(val.id || val.temp_id)
                                                    ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100'
                                                    : 'bg-white text-gray-700 border border-gray-200 hover:border-indigo-600',
                                                attr.product_id ? 'pr-6' : ''
                                            ]"
                                            class="px-2.5 py-1.5 rounded-lg text-[10px] font-[600] uppercase tracking-tight transition-all flex items-center gap-1.5"
                                        >
                                            <Check
                                                v-if="
                                                    selectedAttributesMap[
                                                        attr.attribute_name
                                                    ]?.includes(val.id || val.temp_id)
                                                "
                                                class="w-3 h-3"
                                            />
                                            {{ val.value }}
                                        </button>
                                        <!-- Inline value delete button for custom attributes -->
                                        <button 
                                            v-if="attr.product_id"
                                            type="button" 
                                            @click.stop="deleteValueFromCustomAttribute(attr, val)"
                                            class="absolute right-1 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-red-500 transition-colors hover:scale-110 px-0.5"
                                            title="Delete Value"
                                        >
                                            <XIcon class="w-2.5 h-2.5" />
                                        </button>
                                    </div>
                                    
                                    <!-- Inline Value Add input for custom attributes -->
                                    <div v-if="attr.product_id" class="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-0.5 shadow-sm">
                                        <input 
                                            v-model="newValueInputs[attr.attribute_name]"
                                            type="text" 
                                            placeholder="Add Option" 
                                            class="px-2 py-1 bg-transparent text-[9px] font-semibold outline-none w-20 uppercase"
                                            @keyup.enter.prevent="addValueToCustomAttribute(attr)"
                                        />
                                        <button 
                                            type="button"
                                            @click.prevent="addValueToCustomAttribute(attr)"
                                            class="px-1.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded text-[9px] font-bold transition-all"
                                        >
                                            <PlusIcon class="w-2.5 h-2.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Custom Attribute UI Control -->
                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                            <div v-if="showAddCustomAttributeModal" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 space-y-3">
                                <h4 class="text-[10px] font-bold text-gray-900 uppercase tracking-wider">New Custom Attribute</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Name</label>
                                        <input 
                                            v-model="newCustomAttrName" 
                                            type="text" 
                                            placeholder="e.g. Length, Bundle" 
                                            class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:border-indigo-600 outline-none uppercase font-semibold"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Values (comma separated)</label>
                                        <input 
                                            v-model="newCustomAttrValuesText" 
                                            type="text" 
                                            placeholder="e.g. 1m, 2m, 3m" 
                                            class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:border-indigo-600 outline-none uppercase font-semibold"
                                            @keyup.enter.prevent="addCustomAttribute"
                                        />
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button 
                                        type="button" 
                                        @click="showAddCustomAttributeModal = false" 
                                        class="px-3 py-1.5 text-[10px] font-semibold text-gray-500 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all"
                                    >
                                        Cancel
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="addCustomAttribute" 
                                        class="px-3 py-1.5 text-[10px] font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-md shadow-indigo-100 transition-all"
                                    >
                                        Add Attribute
                                    </button>
                                </div>
                            </div>
                            <button 
                                v-else
                                type="button" 
                                @click="showAddCustomAttributeModal = true" 
                                class="flex items-center gap-1.5 px-3 py-2 text-xs font-[600] text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors border border-indigo-100"
                            >
                                <PlusIcon class="w-3.5 h-3.5" />
                                Add Custom Attribute
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Section: Variations -->
                <section
                    v-if="form.type === 'variable'"
                    id="variations"
                    class="scroll-mt-28"
                >
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <div class="flex items-center gap-2 mb-4 md:mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-fuchsia-600 flex items-center justify-center shadow-lg shadow-fuchsia-100"
                            >
                                <Palette class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                >
                                    Price Variations
                                </h2>
                                <p
                                    class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                >
                                    SKU Management
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="form.variations.length === 0"
                            class="p-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200"
                        >
                            <Zap class="w-12 h-12 text-gray-200 mx-auto mb-4" />
                            <p
                                class="text-gray-500 text-xs font-[600] uppercase tracking-widest leading-relaxed"
                            >
                                No variations generated.<br />Select attributes
                                above to start.
                            </p>
                        </div>

                        <div v-else class="space-y-4 md:space-y-5">
                            <!-- Global Actions -->
                            <div
                                class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-3 md:p-4"
                            >
                                <div class="flex items-center gap-2 mb-3">
                                    <Sparkles
                                        class="w-3.5 h-3.5 text-indigo-600"
                                    />
                                    <h3
                                        class="text-[10px] font-[600] text-indigo-700 uppercase tracking-widest"
                                    >
                                        Bulk Edit All
                                    </h3>
                                </div>
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-2"
                                >
                                    <div class="relative">
                                        <input
                                            v-model="globalVariationPrice"
                                            type="number"
                                            placeholder="Price"
                                            class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-[11px] font-[600] text-gray-800 outline-none transition-all placeholder:text-gray-300 focus:border-indigo-500"
                                        />
                                        <button
                                            @click.prevent="applyGlobalPrice"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded bg-indigo-600 p-1 text-white transition-transform hover:scale-105"
                                        >
                                            <Check class="w-3 h-3" />
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input
                                            v-model="
                                                globalVariationPreviousPrice
                                            "
                                            type="number"
                                            placeholder="Prev Price"
                                            class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-[11px] font-[600] text-gray-800 outline-none transition-all placeholder:text-gray-300 focus:border-indigo-500"
                                        />
                                        <button
                                            @click.prevent="
                                                applyGlobalPreviousPrice
                                            "
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded bg-indigo-600 p-1 text-white transition-transform hover:scale-105"
                                        >
                                            <Check class="w-3 h-3" />
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input
                                            v-model="globalVariationStock"
                                            type="number"
                                            placeholder="Stock"
                                            class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-[11px] font-[600] text-gray-800 outline-none transition-all placeholder:text-gray-300 focus:border-indigo-500"
                                        />
                                        <button
                                            @click.prevent="applyGlobalStock"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded bg-indigo-600 p-1 text-white transition-transform hover:scale-105"
                                        >
                                            <Check class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Variations List -->
                            <div class="space-y-3">
                                <div
                                    v-for="(v, index) in form.variations"
                                    :key="index"
                                    class="relative rounded-xl border border-gray-200 bg-gray-50/50 p-3 md:p-4 transition-all"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div
                                            class="flex min-w-0 flex-1 gap-3"
                                        >
                                            <div
                                                @click="
                                                    $refs[
                                                        `variationInput${index}`
                                                    ][0].click()
                                                "
                                                class="relative h-16 w-16 shrink-0 cursor-pointer overflow-hidden rounded-lg border border-dashed border-gray-200 bg-white transition-all hover:border-indigo-500 hover:bg-indigo-50/30"
                                            >
                                                <img
                                                    v-if="v.image_preview"
                                                    :src="v.image_preview"
                                                    class="h-full w-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-full flex-col items-center justify-center text-center"
                                                >
                                                    <Upload
                                                        class="mx-auto w-4 h-4 text-gray-300"
                                                    />
                                                    <p
                                                        class="mt-0.5 text-[7px] font-[600] uppercase text-gray-300"
                                                    >
                                                        Media
                                                    </p>
                                                </div>
                                                <input
                                                    :ref="`variationInput${index}`"
                                                    type="file"
                                                    accept="image/*"
                                                    @change="
                                                        (e) =>
                                                            handleVariationImageUpload(
                                                                e,
                                                                index,
                                                            )
                                                    "
                                                    class="hidden"
                                                />
                                            </div>

                                            <div class="min-w-0 flex-1 space-y-3">
                                                <div
                                                    class="flex flex-wrap gap-1.5 pr-2"
                                                >
                                                    <span
                                                        v-for="attr in v.attributes"
                                                        :key="attr.attribute_name"
                                                        class="rounded-full border border-gray-200 bg-white px-2 py-1 text-[9px] font-[600] uppercase tracking-tight text-gray-600"
                                                    >
                                                        {{ attr.attribute_name }}:
                                                        <span
                                                            class="text-indigo-600"
                                                            >{{
                                                                attr.attribute_value_label
                                                            }}</span
                                                        >
                                                    </span>
                                                </div>

                                                <div
                                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5"
                                                >
                                                    <div>
                                                        <label
                                                            class="mb-1 block text-[9px] font-[600] uppercase tracking-[0.08em] text-gray-400"
                                                            >Price (৳)</label
                                                        >
                                                        <input
                                                            v-model="v.price"
                                                            @input="
                                                                validateVariationPrice(
                                                                    index,
                                                                )
                                                            "
                                                            type="number"
                                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-[600] text-gray-900 outline-none transition-all focus:border-indigo-500"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="mb-1 block text-[9px] font-[600] uppercase tracking-[0.08em] text-gray-400"
                                                            >Prev Price</label
                                                        >
                                                        <input
                                                            v-model="
                                                                v.previous_price
                                                            "
                                                            @input="
                                                                validateVariationPrice(
                                                                    index,
                                                                )
                                                            "
                                                            type="number"
                                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-[600] text-gray-900 outline-none transition-all focus:border-indigo-500"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="mb-1 block text-[9px] font-[600] uppercase tracking-[0.08em] text-gray-400"
                                                            >Stock</label
                                                        >
                                                        <input
                                                            v-model="v.stock"
                                                            type="number"
                                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-[600] text-gray-900 outline-none transition-all focus:border-indigo-500"
                                                        />
                                                    </div>
                                                </div>

                                                <p
                                                    v-if="
                                                        variationPriceErrors[index]
                                                    "
                                                    class="text-[10px] font-[600] uppercase tracking-wide text-red-600"
                                                >
                                                    {{
                                                        variationPriceErrors[index]
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <button
                                            @click.prevent="
                                                removeVariation(index)
                                            "
                                            class="shrink-0 rounded-lg border border-gray-200 bg-white p-1.5 text-gray-400 transition-all hover:border-red-100 hover:bg-red-50 hover:text-red-500"
                                        >
                                            <XIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section: Additional Info -->
                <section id="seo" class="scroll-mt-28">
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-5"
                    >
                        <button
                            type="button"
                            @click="additionalInfoOpen = !additionalInfoOpen"
                            class="w-full flex items-center justify-between gap-3 text-left"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center shadow-lg shadow-slate-100"
                                >
                                    <Eye class="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <h2
                                        class="text-sm md:text-base font-[600] text-gray-900 uppercase tracking-tight"
                                    >
                                        Additional Info
                                    </h2>
                                    <p
                                        class="text-[9px] md:text-[10px] text-gray-500 font-[600] uppercase tracking-widest leading-none"
                                    >
                                        Metadata & Tags
                                    </p>
                                </div>
                            </div>
                            <ChevronRight
                                :class="additionalInfoOpen ? 'rotate-90' : ''"
                                class="w-4 h-4 text-gray-400 transition-transform"
                            />
                        </button>

                        <div
                            v-if="additionalInfoOpen"
                            class="grid grid-cols-1 gap-4 md:gap-6 mt-4 md:mt-5"
                        >
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                        >Meta Title Tag</label
                                    >
                                    <input
                                        v-model="form.meta_title"
                                        type="text"
                                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm"
                                        placeholder="Max 60 chars"
                                    />
                                    <div class="flex justify-end mt-1">
                                        <span
                                            :class="[
                                                form.meta_title.length > 60
                                                    ? 'text-orange-600'
                                                    : 'text-gray-400',
                                            ]"
                                            class="text-[8px] font-[600] uppercase"
                                            >{{ form.meta_title.length }} /
                                            60</span
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-1"
                                        >Meta Description</label
                                    >
                                    <textarea
                                        v-model="form.meta_description"
                                        rows="3"
                                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] text-sm resize-none"
                                        placeholder="Search result summary..."
                                    ></textarea>
                                    <div class="flex justify-end mt-1">
                                        <span
                                            :class="[
                                                form.meta_description.length >
                                                160
                                                    ? 'text-orange-600'
                                                    : 'text-gray-400',
                                            ]"
                                            class="text-[8px] font-[600] uppercase"
                                            >{{
                                                form.meta_description.length
                                            }}
                                            / 160</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="pt-5 border-t border-gray-100">
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-500 uppercase tracking-[0.1em] mb-3"
                                    >Product Tags</label
                                >
                                <div
                                    class="flex flex-wrap gap-1.5 mb-2 p-3 bg-gray-50 rounded-xl border border-gray-100 min-h-[48px]"
                                >
                                    <span
                                        v-for="(tag, idx) in form.product_tags"
                                        :key="idx"
                                        class="flex items-center gap-1.5 px-2.5 py-1 bg-white text-indigo-700 rounded-lg text-[9px] font-[600] uppercase tracking-widest border border-indigo-100 shadow-sm transition-all"
                                    >
                                        {{ tag }}
                                        <button
                                            @click.prevent="removeTag(idx)"
                                            class="p-0.5 hover:bg-red-50 hover:text-red-600 rounded transition-colors"
                                        >
                                            <XIcon class="w-3 h-3" />
                                        </button>
                                    </span>
                                    <input
                                        v-model="newTag"
                                        @keydown.enter.prevent="addTag"
                                        type="text"
                                        placeholder="Add + Enter"
                                        class="bg-transparent border-none outline-none text-[9px] font-[600] uppercase tracking-widest text-gray-700 px-1 min-w-[100px]"
                                    />
                                </div>
                            </div>


                                                        <div>
                                <label
                                    class="block text-[9px] md:text-[10px] font-[600] text-gray-400 uppercase tracking-[0.1em] mb-2"
                                    >YouTube Video Link</label
                                >
                                <div class="relative group">
                                    <div
                                        class="absolute left-4 top-1/2 -translate-y-1/2 text-red-600 transition-colors group-focus-within:text-red-700"
                                    >
                                        <VideoIcon class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.youtube_video"
                                        type="text"
                                        class="w-full pl-10 pr-4 py-2 md:py-2.5 bg-gray-50 border border-gray-100 focus:bg-white focus:border-indigo-600 rounded-lg md:rounded-xl outline-none transition-all text-gray-900 font-[600] placeholder:text-gray-300 text-sm"
                                        placeholder="URL..."
                                    />
                                </div>
                            </div>


                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Premium styling for CKEditor to match the design system */
:deep(.ck-editor__editable_inline) {
    min-height: 250px;
    background-color: #F9FAFB !important;
    transition: all 0.2s ease-in-out;
    font-family: inherit;
    font-size: 0.875rem;
    color: #111827;
}

:deep(.ck-focused) {
    background-color: #ffffff !important;
    border-color: #4f46e5 !important;
    outline: none !important;
    box-shadow: none !important;
}

:deep(.ck.ck-editor) {
    border-radius: 0.75rem;
    overflow: hidden;
}

:deep(.ck.ck-toolbar) {
    border: 1px solid #F3F4F6 !important;
    background-color: #ffffff !important;
}

:deep(.ck.ck-content) {
    border: 1px solid #F3F4F6 !important;
    border-top: none !important;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.scroll-mt-28 {
    scroll-margin-top: 112px;
}

/* Custom Scrollbar for better UI */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

.animate-in {
    animation: fadeIn 0.4s ease-out backwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
