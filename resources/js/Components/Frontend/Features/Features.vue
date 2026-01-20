<script setup>
import { Truck, RefreshCcw, Tag, Headphones } from "lucide-vue-next";
</script>

<template>
    <!-- Features Section -->
    <div class="container hidden lg:block mx-auto px-4 mt-8">
        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-8 md:px-10"
        >
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 items-center"
            >
                <div
                    class="flex items-center gap-4 group justify-center lg:justify-start"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-xl bg-amber-50 text-amber-500 transition-transform group-hover:scale-110"
                    >
                        <Truck class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">
                            Fastest Home Delivery
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 group justify-center lg:justify-start"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 transition-transform group-hover:scale-110"
                    >
                        <RefreshCcw class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">
                            Exchange Facility
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 group justify-center lg:justify-start"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-xl bg-rose-50 text-rose-500 transition-transform group-hover:scale-110"
                    >
                        <Tag class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">
                            Best Price Deals
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 group justify-center lg:justify-start"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-xl bg-orange-50 text-orange-500 transition-transform group-hover:scale-110"
                    >
                        <Headphones class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">
                            After Sell Service
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
