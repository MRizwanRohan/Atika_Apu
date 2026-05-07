<script setup>
import { ref } from "vue";
import { useForm, Head } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ track_input: "" }),
    },
    searched: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    track_input: props.filters.track_input || "",
});

const submit = () => {
    form.get(route("order.track"), {
        preserveState: true,
        preserveScroll: true,
    });
};

const statuses = ["pending", "processing", "shipped", "delivered"];

const cancellationStatuses = ["cancelled", "returned"];

const getStatusIndex = (status) => {
    return statuses.indexOf(status);
};

const isStatusCompleted = (currentStatus, stepStatus) => {
    if (currentStatus === "cancelled" || currentStatus === "returned") {
        return currentStatus === stepStatus;
    }
    const currentIndex = getStatusIndex(currentStatus);
    const stepIndex = getStatusIndex(stepStatus);
    return stepIndex <= currentIndex;
};
</script>

<template>
    <Head title="Track Order" />
    <FrontendLayout>
        <div class="min-h-screen bg-gray-50/50 py-12 px-4 sm:px-6 lg:px-8 relative">
            <div class="max-w-4xl mx-auto relative z-10">
                <!-- Search Card -->
                <div
                    class="bg-white rounded-[2rem] p-8 mb-12 shadow-xl shadow-gray-200/50 border border-gray-100 max-w-md mx-auto"
                >
                    <!-- Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-900" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 7.5L12 3L3 7.5M21 7.5V16.5L12 21M21 7.5L12 12M3 7.5V16.5L12 21M3 7.5L12 12M12 12V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h1
                            class="text-2xl font-bold text-gray-900 mb-2"
                        >
                            Track Your Order
                        </h1>
                        <p class="text-gray-500 text-sm">
                            Enter your Order ID or Phone Number to check the delivery status.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label
                                for="track_input"
                                class="block text-sm font-bold text-gray-900 mb-2"
                            >
                                Order ID or Phone Number
                            </label>
                            <input
                                id="track_input"
                                v-model="form.track_input"
                                type="text"
                                placeholder="e.g. TNM-10294 or 017XXXXXX"
                                class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white placeholder:text-gray-300"
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-[#0a1d37] text-white font-bold py-4 rounded-xl hover:opacity-90 transition-all disabled:opacity-50 flex items-center justify-center gap-2 uppercase tracking-wider text-sm"
                        >
                            <svg
                                v-if="form.processing"
                                class="animate-spin h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <template v-else>
                                TRACK ORDER
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </template>
                        </button>

                        <div class="text-center">
                            <p class="text-sm text-gray-500">
                                Having trouble? <a href="#" class="text-primary font-bold">Contact Support</a>
                            </p>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                <div v-if="searched">
                    <div v-if="orders.length > 0" class="space-y-8">
                        <div
                            v-for="order in orders"
                            :key="order.id"
                            class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100"
                        >
                            <!-- Order Header -->
                            <div
                                class="flex flex-col md:flex-row justify-between md:items-start mb-8 gap-4"
                            >
                                <div>
                                    <h3
                                        class="text-2xl font-bold text-gray-900"
                                    >
                                        Order {{ order.order_number }}
                                        <span
                                            :class="{
                                                'bg-[#FEF9C3] text-[#854D0E]':
                                                    order.status === 'pending',
                                                'bg-blue-100 text-blue-800':
                                                    order.status ===
                                                    'processing',
                                                'bg-indigo-100 text-indigo-800':
                                                    order.status === 'shipped',
                                                'bg-green-100 text-green-800':
                                                    order.status ===
                                                        'delivered' ||
                                                    order.status ===
                                                        'confirmed',
                                                'bg-red-100 text-red-800':
                                                    order.status ===
                                                        'cancelled' ||
                                                    order.status === 'returned',
                                                'bg-gray-100 text-gray-800':
                                                    order.status ===
                                                        'on_hold' ||
                                                    order.status ===
                                                        'incomplete',
                                            }"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide inline-block align-middle"
                                        >
                                            {{ order.status }}
                                        </span>
                                    </h3>
                                    <p class="text-gray-400 text-sm mt-1">
                                        Date:
                                        {{
                                            new Date(
                                                order.created_at
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                </div>
                                <div class="text-left md:text-right">
                                    <p class="text-sm text-gray-400">
                                        Total Amount
                                    </p>
                                    <p class="text-3xl font-bold text-primary">
                                        {{ order.total }}
                                    </p>
                                </div>
                            </div>
                            <div class="h-px bg-gray-100 w-full mb-8"></div>

                            <!-- Progress Bar (Desktop) -->
                            <div
                                v-if="
                                    [
                                        'pending',
                                        'processing',
                                        'shipped',
                                        'delivered',
                                        'cancelled',
                                        'returned',
                                    ].includes(order.status)
                                "
                                class="mb-12 px-2 hidden md:block"
                            >
                                <div class="relative">
                                    <div
                                        class="overflow-hidden h-2.5 mb-6 text-xs flex rounded-full bg-gray-100"
                                    >
                                        <div
                                            v-if="
                                                !cancellationStatuses.includes(
                                                    order.status
                                                )
                                            "
                                            :class="{
                                                'w-[12.5%]':
                                                    order.status === 'pending',
                                                'w-[37.5%]':
                                                    order.status ===
                                                    'processing',
                                                'w-[62.5%]':
                                                    order.status === 'shipped',
                                                'w-[100%]':
                                                    order.status ===
                                                    'delivered',
                                            }"
                                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary transition-all duration-500"
                                        ></div>
                                        <div
                                            v-else
                                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-red-500 w-full transition-all duration-500"
                                        ></div>
                                    </div>
                                    <div
                                        class="flex justify-between text-sm font-medium text-gray-400"
                                    >
                                        <div
                                            class="w-[25%] text-left"
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'pending'
                                                    ),
                                            }"
                                        >
                                            Pending
                                        </div>
                                        <div
                                            class="w-[25%] text-center"
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'processing'
                                                    ),
                                            }"
                                        >
                                            Processing
                                        </div>
                                        <div
                                            class="w-[25%] text-center"
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'shipped'
                                                    ),
                                            }"
                                        >
                                            Shipped
                                        </div>
                                        <div
                                            class="w-[25%] text-right"
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'delivered'
                                                    ),
                                            }"
                                        >
                                            Delivered
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar (Mobile Vertical) -->
                            <div
                                v-if="
                                    [
                                        'pending',
                                        'processing',
                                        'shipped',
                                        'delivered',
                                        'cancelled',
                                        'returned',
                                    ].includes(order.status)
                                "
                                class="mb-8 px-4 md:hidden"
                            >
                                <div
                                    class="relative pl-6 border-l-2 border-gray-200 ml-2 space-y-8"
                                >
                                    <!-- Pending -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[21px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'pending'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'pending'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Pending
                                        </p>
                                    </div>
                                    <!-- Processing -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[21px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'processing'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'processing'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Processing
                                        </p>
                                    </div>
                                    <!-- Shipped -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[31px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'shipped'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'shipped'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Shipped
                                        </p>
                                    </div>
                                    <!-- Delivered -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[31px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'delivered'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'delivered'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Delivered
                                        </p>
                                    </div>

                                    <!-- Internal Cancellation Step -->
                                    <div
                                        v-if="order.status === 'cancelled'"
                                        class="relative"
                                    >
                                        <div
                                            class="absolute -left-[21px] bg-red-500 border-2 border-red-500 rounded-full w-4 h-4 mt-0.5"
                                        ></div>
                                        <p
                                            class="text-sm font-bold text-red-600 -mt-1"
                                        >
                                            Cancelled
                                        </p>
                                    </div>

                                    <!-- Internal Return Step -->
                                    <div
                                        v-if="order.status === 'returned'"
                                        class="relative"
                                    >
                                        <div
                                            class="absolute -left-[21px] bg-red-500 border-2 border-red-500 rounded-full w-4 h-4 mt-0.5"
                                        ></div>
                                        <p
                                            class="text-sm font-bold text-red-600 -mt-1"
                                        >
                                            Returned
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Summary -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4
                                    class="text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wider"
                                >
                                    Items in Order
                                </h4>
                                <div class="space-y-3">
                                    <div
                                        v-for="item in order.items"
                                        :key="item.id"
                                        class="flex gap-4 items-center bg-white p-3 rounded border border-gray-100"
                                    >
                                        <div
                                            class="h-12 w-12 bg-gray-100 rounded flex-shrink-0 flex items-center justify-center overflow-hidden"
                                        >
                                            <img
                                                v-if="
                                                    item.product &&
                                                    item.product
                                                        .feature_image_url
                                                "
                                                :src="
                                                    item.product
                                                        .feature_image_url
                                                "
                                                class="h-full w-full object-cover"
                                            />
                                            <svg
                                                v-else
                                                class="w-6 h-6 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                ></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-sm font-medium text-gray-900 truncate"
                                            >
                                                {{
                                                    item.product
                                                        ? item.product.name
                                                        : item.product_name ||
                                                          "Product"
                                                }}
                                            </p>
                                            <div
                                                v-if="
                                                    item.product_variation &&
                                                    item.product_variation
                                                        .attributes
                                                "
                                                class="flex flex-wrap gap-2 mt-1"
                                            >
                                                <span
                                                    v-for="attr in item
                                                        .product_variation
                                                        .attributes"
                                                    :key="attr.id"
                                                    class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"
                                                >
                                                    {{
                                                        attr.value.attribute
                                                            .name
                                                    }}: {{ attr.value.value }}
                                                </span>
                                            </div>
                                            <p
                                                class="text-xs text-gray-500 mt-1"
                                            >
                                                Qty: {{ item.quantity }}
                                            </p>
                                        </div>
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ item.total }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="text-center py-12 bg-gray-50 rounded-2xl border border-gray-100"
                    >
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-200 mb-4"
                        >
                            <svg
                                class="w-8 h-8 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">
                            No Orders Found
                        </h3>
                        <p class="text-gray-500 max-w-sm mx-auto">
                            We couldn't find any orders matching "<strong>{{
                                form.track_input
                            }}</strong
                            >". Please try again.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
