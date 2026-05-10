<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { Lock, ShieldCheck, Save, ArrowLeft } from "lucide-vue-next";

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.put(route("user.password.update"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Security Settings" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                
                <!-- Header -->
                <header class="flex items-center justify-between py-4 px-4 md:py-5 md:px-6 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <Link :href="route('user.dashboard')" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 rounded-full transition-all text-slate-600 font-semibold text-[10px] md:text-xs uppercase tracking-tight">
                        <ArrowLeft :size="12" />
                        <span>Back</span>
                    </Link>
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 absolute left-1/2 -translate-x-1/2">Security</h1>
                    <div class="w-10"></div>
                </header>

                <form @submit.prevent="submit" class="p-4 md:p-8 space-y-4 md:space-y-6">
                    <div class="flex flex-col items-center mb-4 md:mb-8">
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-2 md:mb-4 border border-slate-100">
                            <ShieldCheck :size="32" class="md:hidden" />
                            <ShieldCheck :size="40" class="hidden md:block" />
                        </div>
                        <h2 class="text-base md:text-lg font-semibold text-slate-900">Change Password</h2>
                        <p class="text-[10px] md:text-xs text-slate-500 mt-0.5 md:mt-1">Keep your account secure.</p>
                    </div>

                    <!-- Current Password -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">Current Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                                <Lock :size="16" />
                            </div>
                            <input 
                                v-model="form.current_password"
                                type="password"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all border border-slate-100"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="form.errors.current_password" class="text-[10px] md:text-xs text-rose-500 font-medium ml-1">{{ form.errors.current_password }}</p>
                    </div>

                    <!-- New Password -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">New Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                                <Lock :size="16" />
                            </div>
                            <input 
                                v-model="form.password"
                                type="password"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all border border-slate-100"
                                placeholder="New password"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-[10px] md:text-xs text-rose-500 font-medium ml-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">Confirm Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                                <Lock :size="16" />
                            </div>
                            <input 
                                v-model="form.password_confirmation"
                                type="password"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all border border-slate-100"
                                placeholder="Repeat new password"
                            />
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 md:pt-4">
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 md:gap-3 p-3.5 md:p-4 bg-[#0a1f44] text-white rounded-xl md:rounded-2xl font-semibold text-xs md:text-sm tracking-widest hover:bg-slate-900 transition-all active:scale-[0.98] shadow-lg disabled:opacity-50"
                        >
                            <Save :size="16" />
                            <span v-if="form.processing">UPDATING...</span>
                            <span v-else>CHANGE PASSWORD</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </FrontendLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.font-sans {
    font-family: 'Montserrat', sans-serif !important;
}
</style>
