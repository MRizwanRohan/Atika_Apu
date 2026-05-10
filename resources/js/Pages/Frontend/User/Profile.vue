<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { User, Mail, Phone, MapPin, Save, ArrowLeft, LayoutDashboard } from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone_number: props.user.phone_number || "",
    address: props.user.address || "",
});

const submit = () => {
    form.put(route("user.profile.update"));
};
</script>

<template>
    <Head title="Edit Profile" />

    <FrontendLayout>
        <div class="min-h-screen bg-[#f4f6f8] pt-4 md:pt-10 pb-20 font-sans">
            <div class="container px-2 md:px-4 mx-auto bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                
                <!-- Header -->
                <header class="flex items-center justify-between py-4 px-4 md:py-5 md:px-6 bg-white border-b border-slate-100 sticky top-0 z-20">
                    <Link :href="route('user.dashboard')" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 rounded-full transition-all text-slate-600 font-semibold text-[10px] md:text-xs uppercase tracking-tight">
                        <ArrowLeft :size="12" />
                        <span>Back</span>
                    </Link>
                    <h1 class="text-sm md:text-base font-semibold text-slate-900 absolute left-1/2 -translate-x-1/2">Edit Profile</h1>
                    <div class="w-10"></div>
                </header>

                <form @submit.prevent="submit" class="p-4 md:p-8 space-y-4 md:space-y-6">
                    <!-- Avatar placeholder -->
                    <div class="flex justify-center mb-4 md:mb-8">
                        <div class="w-16 h-16 md:w-24 md:h-24 bg-[#0a1f44] rounded-full flex items-center justify-center shadow-lg">
                            <User :size="32" class="text-white md:hidden" />
                            <User :size="40" class="text-white hidden md:block" />
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">Full Name</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                                <User :size="16" />
                            </div>
                            <input 
                                v-model="form.name"
                                type="text"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all border border-slate-100"
                                placeholder="Enter your name"
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-[10px] md:text-xs text-rose-500 font-medium ml-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">Email Address</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                                <Mail :size="16" />
                            </div>
                            <input 
                                v-model="form.email"
                                type="email"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all border border-slate-100"
                                placeholder="Email address"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-[10px] md:text-xs text-rose-500 font-medium ml-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Phone -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">Phone Number</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                                <Phone :size="16" />
                            </div>
                            <input 
                                v-model="form.phone_number"
                                type="tel"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all border border-slate-100"
                                placeholder="017xxxxxxxx"
                            />
                        </div>
                        <p v-if="form.errors.phone_number" class="text-[10px] md:text-xs text-rose-500 font-medium ml-1">{{ form.errors.phone_number }}</p>
                    </div>

                    <!-- Address -->
                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-[9px] md:text-[11px] font-semibold text-slate-400 uppercase tracking-wider ml-1">Shipping Address</label>
                        <div class="relative group">
                            <div class="absolute top-3 md:top-4 left-3 md:left-4 text-slate-400 group-focus-within:text-primary transition-colors">
                                <MapPin :size="16" />
                            </div>
                            <textarea 
                                v-model="form.address"
                                rows="3"
                                class="block w-full pl-10 md:pl-11 pr-4 py-2.5 md:py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-xl text-xs md:text-sm font-semibold text-slate-700 transition-all resize-none border border-slate-100"
                                placeholder="House no, Road no, Area..."
                            ></textarea>
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
                            <span v-if="form.processing">SAVING...</span>
                            <span v-else>UPDATE PROFILE</span>
                        </button>
                    </div>

                    <Link :href="route('user.dashboard')" class="flex items-center justify-center gap-2 text-[10px] md:text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                        <LayoutDashboard :size="12" />
                        Back to Dashboard
                    </Link>
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
