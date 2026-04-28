<template>
    <Head title="Employee Management" />
    <AdminLayout>
        <div class="px-4 pr-0 sm:px-6 lg:px-8 py-8 bg-gray-50/50 min-h-screen">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Employee Directory</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage your team members, roles and payroll information.</p>
                </div>
                <Link 
                    :href="route('admin.employee.create')" 
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm shadow-blue-200 group"
                >
                    <Plus size="18" class="mr-2 group-hover:rotate-90 transition-transform duration-300" />
                    New Employee
                </Link>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mr-4">
                        <Users size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Staff</p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-0.5">{{ stats.total }}</h3>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mr-4">
                        <UserCheck size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active</p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-0.5">{{ stats.active }}</h3>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mr-4">
                        <UserX size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Inactive</p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-0.5">{{ stats.inactive }}</h3>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <Banknote size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Payroll</p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-0.5">৳{{ Number(stats.total_salary).toLocaleString() }}</h3>
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <!-- Search and Filters -->
                <div class="p-6 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/50">
                    <div class="relative w-full max-w-md">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                            <Search size="18" />
                        </span>
                        <input
                            type="text"
                            v-model="search"
                            @input="debouncedSearch"
                            placeholder="Search by name, role, email..."
                            class="block w-full pl-11 pr-4 py-2.5 bg-gray-50/50 border border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/30">
                                <th class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Employee</th>
                                <th class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest hidden sm:table-cell">Contact Info</th>
                                <th class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest hidden lg:table-cell">Designation</th>
                                <th class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Salary</th>
                                <th class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="employee in employees.data" :key="employee.id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-medium overflow-hidden border-2 border-white shadow-sm">
                                            <span v-if="!employee.photo">{{ employee.name.charAt(0) }}</span>
                                            <img v-else :src="employee.photo" alt="" class="w-full h-full object-cover">
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-900">{{ employee.name }}</p>
                                            <p class="text-[11px] text-gray-500 mt-0.5 sm:hidden">{{ employee.role }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell">
                                    <div class="text-xs text-gray-600 flex flex-col gap-1">
                                        <div class="flex items-center">
                                            <Mail size="12" class="mr-1.5 text-gray-400" />
                                            <span>{{ employee.email || 'No email' }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <Phone size="12" class="mr-1.5 text-gray-400" />
                                            <span>{{ employee.phone || 'No phone' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <div class="inline-flex px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-medium tracking-tight">
                                        {{ employee.role || 'Staff' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 transition-colors tracking-tight">৳{{ Number(employee.monthly_salary).toLocaleString() }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-tighter">per month</p>
                                </td>
                                <td class="px-6 py-4">
                                    <button 
                                        @click="updateEmployeeStatus(employee.id, employee.status === 'active' ? 'inactive' : 'active')"
                                        :class="[
                                            'inline-flex px-3 py-1 text-[10px] font-semibold rounded-full border transition-all duration-200',
                                            employee.status === 'active' 
                                                ? 'bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-100' 
                                                : 'bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-100'
                                        ]"
                                    >
                                        {{ employee.status === 'active' ? 'Active Member' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 outline-none">
                                        <Link 
                                            :href="route('admin.employee.edit', employee.id)" 
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            v-tooltip="'Edit Details'"
                                        >
                                            <Edit size="18" />
                                        </Link>
                                        <button 
                                            @click="deleteEmployee(employee.id)" 
                                            class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                            v-tooltip="'Remove Employee'"
                                        >
                                            <Trash2 size="18" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center bg-gray-50/20">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-300 mb-4">
                                            <Users size="32" />
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900">No team members found</h3>
                                        <p class="text-xs text-gray-500 mt-1">Try adjusting your search or add a new employee.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Corrected Pagination -->
                <div v-if="employees.links.length > 3" class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                    <div class="hidden sm:block">
                        <p class="text-xs text-gray-500">
                            Showing <span class="font-medium">{{ employees.from }}</span> to 
                            <span class="font-medium">{{ employees.to }}</span> of 
                            <span class="font-medium">{{ employees.total }}</span> entries
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, k) in employees.links" :key="k">
                            <div v-if="link.url === null" class="px-3 py-1.5 text-xs text-gray-400 border border-transparent pointer-events-none" v-html="link.label"></div>
                            <Link 
                                v-else 
                                :href="link.url" 
                                class="px-3 py-1.5 text-xs rounded-lg transition-all" 
                                :class="link.active ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-white border border-transparent hover:border-gray-200'" 
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import _ from 'lodash';
import { 
    Users, Plus, Search, Mail, Phone, Edit, Trash2, 
    UserCheck, UserX, Banknote 
} from 'lucide-vue-next';

const props = defineProps({
    employees: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const updateEmployeeStatus = (id, status) => {
    if (confirm(`Are you sure you want to ${status ? 'activate' : 'deactivate'} this employee?`)) {
        router.put(route('admin.employee.update', id), {
            status: status
        }, {
            preserveScroll: true,
        });
    }
};

const deleteEmployee = (id) => {
    if (confirm('Are you sure you want to delete this employee?')) {
        router.delete(route('admin.employee.destroy', id));
    }
};

// Debounced search function
const debouncedSearch = _.debounce(() => {
    router.get(route('admin.employee.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch for search changes (fallback if debounced search fails)
watch(search, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // This will trigger the debounced function
    }
});
</script>
