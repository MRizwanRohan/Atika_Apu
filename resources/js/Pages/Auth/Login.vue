<script setup>
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import InputError from "@/Components/InputError.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <FrontendLayout>
        <Head title="Sign In" />

        <div
            class="flex items-center justify-center bg-[#f8fafc] pb-12 px-4 sm:px-6 lg:px-8 font-sans"
        >
            <div class="max-w-[500px] w-full">
                <!-- Main Card -->
                <div
                    class="bg-white rounded-xl shadow-[0_8px_24px_rgba(10,31,68,0.06)] overflow-hidden py-[35px] px-[30px] relative border border-[#e1e4e8]"
                >
                    <!-- Top Navigation Tab -->
                    <div class="flex justify-center mb-6">
                        <div class="inline-flex p-1 bg-[#f1f5f9] rounded-full border border-slate-100 w-full max-w-[280px]">
                            <Link
                                :href="route('login')"
                                class="flex-1 text-center py-2.5 rounded-full text-sm font-bold transition-all duration-200"
                                :class="route().current('login') ? 'bg-white text-[#6c5dd3] shadow-[0_2px_6px_rgba(0,0,0,0.06)]' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Login
                            </Link>
                            <Link
                                :href="route('register')"
                                class="flex-1 text-center py-2.5 rounded-full text-sm font-bold transition-all duration-200"
                                :class="route().current('register') ? 'bg-white text-[#6c5dd3] shadow-[0_2px_6px_rgba(0,0,0,0.06)]' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Register
                            </Link>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="text-center mt-4 mb-8">
                        <h1 class="text-3xl font-extrabold text-slate-900 mb-2">
                            Welcome Back!
                        </h1>
                        <p class="text-slate-500 text-sm">
                            Please sign in to your account to continue.
                        </p>
                    </div>

                    <!-- Alert for Status -->
                    <div
                        v-if="status"
                        class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl text-sm font-medium border border-green-100"
                    >
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Email Input -->
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-bold text-slate-700 block ml-1">
                                Email Address
                            </label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 text-slate-400 group-focus-within:text-primary transition-colors"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="Enter your email"
                                    class="block w-full pl-12 pr-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-primary/10 focus:border-primary transition-all text-sm"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>

                        <!-- Password Input -->
                        <div class="space-y-2">
                            <label for="password" class="text-sm font-bold text-slate-700 block ml-1">
                                Password
                            </label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 text-slate-400 group-focus-within:text-primary transition-colors"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="block w-full pl-12 pr-12 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-primary/10 focus:border-primary transition-all text-sm"
                                />
                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                                    tabindex="-1"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <!-- Secondary Actions -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center group cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary/20 transition-all cursor-pointer"
                                />
                                <span class="ml-2 text-sm font-medium text-slate-500 group-hover:text-slate-700 transition-colors">
                                    Remember me
                                </span>
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-bold text-slate-900 hover:text-primary transition-colors"
                            >
                                Forgot Password?
                            </Link>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button
                                type="submit"
                                class="w-full flex items-center justify-center py-4 px-4 bg-primary hover:bg-primary/90 text-white rounded-xl text-md font-bold shadow-lg transition-all duration-200 group"
                                :class="{
                                    'opacity-75 cursor-not-allowed':
                                        form.processing,
                                }"
                                :disabled="form.processing"
                            >
                                <span v-if="!form.processing" class="flex items-center gap-2">
                                    Sign In
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg
                                        class="animate-spin h-5 w-5 text-white"
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
                                    Signing in...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>

<style scoped>
/* Ensure Montserrat is used as configured in tailwind.config.js */
.font-sans {
    font-family: 'Montserrat', sans-serif;
}
</style>

