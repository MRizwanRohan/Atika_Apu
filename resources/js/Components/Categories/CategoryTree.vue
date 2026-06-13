<script setup>
import { defineProps, ref, defineEmits } from "vue";
import { Link } from "@inertiajs/vue3";
import {
    Trash2,
    Edit3,
    ChevronRight,
    ChevronDown,
    Plus,
    Image as ImageIcon,
    MoreVertical,
    LayoutGrid,
} from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import axios from "axios";
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
    category: Object,
    level: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["status-updated", "order-updated"]);

const isExpanded = ref(true);
const showDeleteModal = ref(false);
const orderValue = ref(props.category.order);
const isHovered = ref(false);

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
};

const updateStatus = async (categoryId, newStatus) => {
    try {
        const response = await axios.put(
            route("admin.categories.updateStatus", categoryId),
            {
                status: newStatus,
            }
        );

        if (response.data.success) {
            emit("status-updated", { id: categoryId, status: newStatus });
            toast.success(response.data.message);
        }
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error(error.response?.data?.message || "Failed to update status");
    }
};

const updateOrder = async (categoryId) => {
    try {
        const response = await axios.put(
            route("admin.categories.updateOrder", categoryId),
            {
                order: orderValue.value,
            }
        );

        if (response.data.success) {
            emit("order-updated", { id: categoryId, order: orderValue.value });
            toast.success(response.data.message);
        }
    } catch (error) {
        console.error("Failed to update order:", error);
        toast.error(error.response?.data?.message || "Failed to update order");
        orderValue.value = props.category.order;
    }
};
</script>

<template>
    <div class="category-node" :class="{ 'is-root': level === 0 }">
        <div
            class="category-row group"
            :class="{ 'bg-slate-50': isHovered }"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
        >
            <div
                class="flex min-w-0 flex-1 items-start"
                :style="{ paddingLeft: `${level * 18}px` }"
            >
                <div
                    v-if="level > 0"
                    class="hierarchy-line"
                    v-for="i in level"
                    :key="i"
                    :style="{ left: `${(i - 1) * 18 + 9}px` }"
                ></div>

                <button
                    @click="toggleExpand"
                    class="mr-2 mt-1 flex h-6 w-6 items-center justify-center rounded-md transition-all duration-200 hover:bg-gray-200/60"
                    :class="category.children?.length ? 'visible' : 'invisible'"
                >
                    <component
                        :is="isExpanded ? ChevronDown : ChevronRight"
                        class="w-4 h-4 text-gray-500"
                    />
                </button>

                <div class="relative mr-3 shrink-0">
                    <div
                        class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white transition-all duration-200"
                    >
                        <img
                            v-if="category.image"
                            :src="category.image"
                            class="h-full w-full object-cover"
                            @error="(e) => (e.target.src = '')"
                        />
                        <ImageIcon v-else class="w-5 h-5 text-gray-300" />
                    </div>
                    <div
                        v-if="category.icon"
                        class="absolute -bottom-1 -right-1 h-4 w-4 overflow-hidden rounded-full border border-white bg-white"
                    >
                        <img
                            :src="category.icon"
                            class="w-full h-full object-cover"
                        />
                    </div>
                </div>

                <div class="min-w-0 flex-1">
                    <span
                        class="block truncate text-xs font-semibold text-gray-800 sm:text-sm"
                    >
                        {{ category.name }}
                    </span>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-2 md:hidden"
                    >
                        <button
                            @click="
                                updateStatus(
                                    category.id,
                                    category.status === 'active'
                                        ? 'deactive'
                                        : 'active'
                                )
                            "
                            class="status-pill transition-all duration-200"
                            :class="
                                category.status === 'active'
                                    ? 'is-active'
                                    : 'is-inactive'
                            "
                        >
                            <span
                                class="mr-1.5 h-1.5 w-1.5 rounded-full"
                                :class="
                                    category.status === 'active'
                                        ? 'bg-green-500'
                                        : 'bg-gray-400'
                                "
                            ></span>
                            {{
                                category.status === "active"
                                    ? "Active"
                                    : "Inactive"
                            }}
                        </button>

                        <div class="relative">
                            <input
                                v-model.number="orderValue"
                                type="number"
                                min="0"
                                class="order-input"
                                @change="updateOrder(category.id)"
                            />
                            <LayoutGrid
                                class="absolute left-2 top-1/2 h-3 w-3 -translate-y-1/2 text-gray-300"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="hidden md:flex items-center justify-center w-32 shrink-0"
            >
                <button
                    @click="
                        updateStatus(
                            category.id,
                            category.status === 'active' ? 'deactive' : 'active'
                        )
                    "
                    class="status-pill transition-all duration-200"
                    :class="
                        category.status === 'active'
                            ? 'is-active'
                            : 'is-inactive'
                    "
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full mr-2"
                        :class="
                            category.status === 'active'
                                ? 'bg-green-500'
                                : 'bg-gray-400'
                        "
                    ></span>
                    {{ category.status === "active" ? "Active" : "Inactive" }}
                </button>
            </div>

            <div
                class="hidden md:flex items-center justify-center w-24 shrink-0"
            >
                <div class="relative group/order">
                    <input
                        v-model.number="orderValue"
                        type="number"
                        min="0"
                        class="order-input"
                        @change="updateOrder(category.id)"
                    />
                    <LayoutGrid
                        class="absolute left-2 top-1/2 -translate-y-1/2 w-3 h-3 text-gray-300 group-focus-within/order:text-blue-400 transition-colors"
                    />
                </div>
            </div>

            <div
                class="ml-2 flex shrink-0 items-start justify-end gap-1 self-start md:w-40 md:px-4"
            >
                <Link
                    :href="
                        route('admin.categories.create', {
                            parent_id: category.id,
                        })
                    "
                    class="action-btn text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                    title="Add Subcategory"
                >
                    <Plus class="w-4 h-4" />
                </Link>
                <Link
                    :href="route('admin.categories.edit', category.id)"
                    class="action-btn text-gray-400 hover:text-green-600 hover:bg-green-50"
                    title="Edit Category"
                >
                    <Edit3 class="w-4 h-4" />
                </Link>
                <button
                    @click="openDeleteModal"
                    class="action-btn text-gray-400 hover:text-red-600 hover:bg-red-50"
                    title="Delete Category"
                >
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Recursive Children with Transition -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="
                    isExpanded && category.children && category.children.length
                "
                class="category-children"
            >
                <CategoryTree
                    v-for="child in category.children"
                    :key="child.id"
                    :category="child"
                    :level="level + 1"
                    @status-updated="$emit('status-updated', $event)"
                    @order-updated="$emit('order-updated', $event)"
                />
            </div>
        </transition>

        <!-- Delete Modal -->
        <DeleteModal
            :item-id="category.id"
            item-name="category"
            route-name="admin.categories.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </div>
</template>

<style scoped>
.category-node {
    @apply relative;
}

.category-row {
    @apply flex items-start gap-2 border-b border-gray-100 bg-white px-3 py-3 transition-all duration-200 sm:px-4;
}

.category-row:last-child {
    @apply border-b-0;
}

.is-root > .category-row {
    @apply font-semibold;
}

.hierarchy-line {
    @apply pointer-events-none absolute top-0 bottom-0 w-px bg-gray-200 transition-colors duration-300;
}

.hierarchy-line:last-of-type::after {
    content: "";
    @apply absolute left-0 top-1/2 h-px w-2 -translate-y-1/2 rounded-r-full bg-gray-200;
}

.category-row:hover .hierarchy-line {
    @apply bg-slate-300;
}

.category-row:hover .hierarchy-line:last-of-type::after {
    @apply bg-slate-400;
}

.status-pill {
    @apply inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium transition-all;
}

.status-pill.is-active {
    @apply bg-green-50 text-green-700 border-green-100 hover:bg-green-100;
}

.status-pill.is-inactive {
    @apply bg-gray-50 text-gray-600 border-gray-100 hover:bg-gray-100;
}

.order-input {
    @apply w-14 rounded-md border border-gray-200 py-1 pl-6 pr-2 text-center text-[11px] font-medium text-gray-600 transition-all focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100;
}

.action-btn {
    @apply rounded-lg p-2 transition-all duration-200;
}

.category-children {
    @apply bg-white;
}

.category-row:hover {
    z-index: 5;
    box-shadow: 0 3px 10px -6px rgba(15, 23, 42, 0.18);
}
</style>
