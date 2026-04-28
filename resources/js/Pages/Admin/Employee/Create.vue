<template>
    <Head title="Create Employee" />
    <AdminLayout>
        <div class="px-4 pr-0 sm:px-6 lg:px-8 py-8 bg-gray-50/50 min-h-screen">
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Add New Employee</h1>
                    <p class="text-sm text-gray-500 mt-1">Fill in the details to register a new team member.</p>
                </div>
                <Link 
                    :href="route('admin.employee.index')" 
                    class="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 transition-colors bg-white px-4 py-2 rounded-xl border border-gray-100 shadow-sm"
                >
                    <ArrowLeft size="16" class="mr-2" />
                    Back to Staff List
                </Link>
            </div>

            <form @submit.prevent="submit" class="max-w-5xl mx-auto">
                <div class="space-y-6">
                    <!-- Section: General Information -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden text-gray-900">
                        <div class="p-6 border-b border-gray-50 bg-gray-50/30 flex items-center">
                            <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <User size="18" />
                            </div>
                            <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">General Information</h2>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="name" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Full Name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Enter employee's full name"
                                    class="w-full px-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                    :class="{ 'border-rose-400': form.errors?.name }"
                                    required
                                />
                                <p v-if="form.errors?.name" class="text-[11px] text-rose-500 pl-1">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <label for="role" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Job Designation
                                </label>
                                <input
                                    type="text"
                                    id="role"
                                    v-model="form.role"
                                    placeholder="e.g. Senior Manager, Sales Associate"
                                    class="w-full px-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                    :class="{ 'border-rose-400': form.errors?.role }"
                                />
                                <p v-if="form.errors?.role" class="text-[11px] text-rose-500 pl-1">{{ form.errors.role }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Contact Details -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden text-gray-900">
                        <div class="p-6 border-b border-gray-50 bg-gray-50/30 flex items-center">
                            <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center mr-3">
                                <Contact size="18" />
                            </div>
                            <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Contact & Address</h2>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="email" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                                        <Mail size="16" />
                                    </span>
                                    <input
                                        type="email"
                                        id="email"
                                        v-model="form.email"
                                        placeholder="email@company.com"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                        :class="{ 'border-rose-400': form.errors?.email }"
                                    />
                                </div>
                                <p v-if="form.errors?.email" class="text-[11px] text-rose-500 pl-1">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-2">
                                <label for="phone" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Contact Number
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                                        <Phone size="16" />
                                    </span>
                                    <input
                                        type="text"
                                        id="phone"
                                        v-model="form.phone"
                                        placeholder="+880 1234 567890"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                        :class="{ 'border-rose-400': form.errors?.phone }"
                                    />
                                </div>
                                <p v-if="form.errors?.phone" class="text-[11px] text-rose-500 pl-1">{{ form.errors.phone }}</p>
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label for="address" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Residential Address
                                </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    placeholder="Enter detailed home address..."
                                    class="w-full px-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                    :class="{ 'border-rose-400': form.errors?.address }"
                                ></textarea>
                                <p v-if="form.errors?.address" class="text-[11px] text-rose-500 pl-1">{{ form.errors.address }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Employment & Payroll -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden text-gray-900">
                        <div class="p-6 border-b border-gray-50 bg-gray-50/30 flex items-center">
                            <div class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                <Banknote size="18" />
                            </div>
                            <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Payroll & Status</h2>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="monthly_salary" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Monthly Gross Salary
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">
                                        ৳
                                    </span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        id="monthly_salary"
                                        v-model.number="form.monthly_salary"
                                        class="w-full pl-8 pr-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm font-medium focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                        :class="{ 'border-rose-400': form.errors?.monthly_salary }"
                                    />
                                </div>
                                <p v-if="form.errors?.monthly_salary" class="text-[11px] text-rose-500 pl-1">{{ form.errors.monthly_salary }}</p>
                            </div>

                            <div class="space-y-2">
                                <label for="status" class="text-[12px] font-medium text-gray-500 uppercase tracking-widest pl-1">
                                    Employment Status
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="w-full px-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none appearance-none"
                                    :class="{ 'border-rose-400': form.errors?.status }"
                                >
                                    <option value="active">Active Staff</option>
                                    <option value="inactive">Inactive / On Hold</option>
                                </select>
                                <p v-if="form.errors?.status" class="text-[11px] text-rose-500 pl-1">{{ form.errors.status }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end pt-4 pb-12 gap-x-4">
                        <Link 
                            :href="route('admin.employee.index')" 
                            class="px-6 py-2.5 text-sm text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all"
                        >
                            Discard
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center px-8 py-2.5 bg-gray-900 hover:bg-black text-white text-sm font-medium rounded-xl transition-all shadow-lg shadow-gray-200 disabled:opacity-50"
                        >
                            <Save v-if="!form.processing" size="18" class="mr-2" />
                            <Loader2 v-else size="18" class="mr-2 animate-spin" />
                            {{ form.processing ? 'Saving Changes...' : 'Confirm Registration' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { 
    ArrowLeft, User, Contact, Mail, Phone, 
    Banknote, Save, Loader2 
} from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    role: '',
    monthly_salary: 0,
    status: 'active',
});

const submit = () => {
    form.post(route('admin.employee.store'));
};
</script>
