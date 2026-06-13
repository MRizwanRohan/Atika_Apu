<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60" @click="close"></div>

                <!-- Modal Content -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div
                        v-if="modelValue"
                        class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
                    >
                        <!-- Header -->
                        <div
                            class="px-6 py-4 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between"
                        >
                            <h3
                                class="text-xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ title }}
                            </h3>
                            <button
                                @click="close"
                                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                            >
                                <X size="20" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-6 overflow-y-auto max-h-[75vh]">
                            <slot />
                        </div>

                        <!-- Footer (Optional) -->
                        <div
                            v-if="$slots.footer"
                            class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-50 dark:border-gray-700 flex justify-end gap-3"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { X } from "lucide-vue-next";

const props = defineProps({
    modelValue: Boolean,
    title: {
        type: String,
        default: "Modal Title",
    },
    confirmText: {
        type: String,
        default: "Save Changes",
    },
});

const emit = defineEmits(["update:modelValue", "confirm"]);

const close = () => {
    emit("update:modelValue", false);
};
</script>
