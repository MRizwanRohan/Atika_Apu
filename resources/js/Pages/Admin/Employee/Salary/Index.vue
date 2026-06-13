<template>
    <Head title="Employee Salaries" />
    <AdminLayout>
        <div class="px-4 pr-0 sm:px-6 lg:px-8 py-8 bg-gray-50/50 min-h-screen">
            <!-- Header Section -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8"
            >
                <div>
                    <h1
                        class="text-2xl font-semibold text-gray-900 tracking-tight"
                    >
                        Salary Disbursements
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Track and manage monthly salary payments for your staff.
                    </p>
                </div>
                <button
                    @click="showModal = true"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm shadow-blue-200 group"
                >
                    <Plus
                        size="18"
                        class="mr-2 group-hover:rotate-90 transition-transform duration-300"
                    />
                    Record Salary
                </button>
            </div>

            <!-- Stats Grid -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8"
            >
                <div
                    class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center"
                >
                    <div
                        class="w-11 h-11 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mr-4"
                    >
                        <Banknote size="22" />
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-medium text-gray-400 uppercase tracking-widest pl-0.5"
                        >
                            Total Paid
                        </p>
                        <h3 class="text-lg font-semibold text-gray-900 mt-0.5">
                            ৳{{ Number(stats.total_paid).toLocaleString() }}
                        </h3>
                    </div>
                </div>
                <div
                    class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center"
                >
                    <div
                        class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mr-4"
                    >
                        <CalendarCheck size="22" />
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-medium text-gray-400 uppercase tracking-widest pl-0.5"
                        >
                            This Month
                        </p>
                        <h3 class="text-lg font-semibold text-gray-900 mt-0.5">
                            ৳{{
                                Number(stats.this_month_paid).toLocaleString()
                            }}
                        </h3>
                    </div>
                </div>
                <div
                    class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center"
                >
                    <div
                        class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mr-4"
                    >
                        <History size="22" />
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-medium text-gray-400 uppercase tracking-widest pl-0.5"
                        >
                            Last Month
                        </p>
                        <h3 class="text-lg font-semibold text-gray-900 mt-0.5">
                            ৳{{
                                Number(stats.last_month_paid).toLocaleString()
                            }}
                        </h3>
                    </div>
                </div>
                <div
                    class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center"
                >
                    <div
                        class="w-11 h-11 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mr-4"
                    >
                        <Receipt size="22" />
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-medium text-gray-400 uppercase tracking-widest pl-0.5"
                        >
                            Total Entries
                        </p>
                        <h3 class="text-lg font-semibold text-gray-900 mt-0.5">
                            {{ stats.total_transactions }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <!-- Search and Filters -->
                <div
                    class="p-6 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/50"
                >
                    <div class="relative w-full max-w-md">
                        <span
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none"
                        >
                            <Search size="18" />
                        </span>
                        <input
                            type="text"
                            v-model="search"
                            @input="debouncedSearch"
                            placeholder="Search by employee, email, month..."
                            class="block w-full pl-11 pr-4 py-2.5 bg-gray-50/50 border border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/30">
                                <th
                                    class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest"
                                >
                                    Employee
                                </th>
                                <th
                                    class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest"
                                >
                                    Billing Month
                                </th>
                                <th
                                    class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest"
                                >
                                    Amount
                                </th>
                                <th
                                    class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest text-right"
                                >
                                    Payment Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="salary in salaries.data"
                                :key="salary.id"
                                class="hover:bg-gray-50/50 transition-colors group text-gray-900"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div
                                            class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs font-semibold overflow-hidden border-2 border-white shadow-sm"
                                        >
                                            <span>{{
                                                salary.employee.name.charAt(0)
                                            }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <p
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ salary.employee.name }}
                                            </p>
                                            <p
                                                class="text-[10px] text-gray-500"
                                            >
                                                {{
                                                    salary.employee.email ||
                                                    "No email"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <Calendar
                                            size="14"
                                            class="mr-2 text-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">{{
                                            salary.month
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p
                                        class="text-sm font-semibold text-gray-900 tracking-tight"
                                    >
                                        ৳{{
                                            Number(
                                                salary.amount,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold border',
                                            salary.paid_date
                                                ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                                : 'bg-amber-50 text-amber-600 border-amber-100',
                                        ]"
                                    >
                                        <div
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full mr-1.5 animate-pulse',
                                                salary.paid_date
                                                    ? 'bg-emerald-500'
                                                    : 'bg-amber-500',
                                            ]"
                                        ></div>
                                        {{
                                            salary.paid_date
                                                ? "Completed"
                                                : "Initiated"
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p
                                        class="text-xs text-gray-600 font-medium"
                                    >
                                        {{
                                            salary.paid_date
                                                ? new Date(
                                                      salary.paid_date,
                                                  ).toLocaleDateString(
                                                      "en-US",
                                                      {
                                                          day: "numeric",
                                                          month: "short",
                                                          year: "numeric",
                                                      },
                                                  )
                                                : "Pending"
                                        }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        {{
                                            new Date(
                                                salary.created_at,
                                            ).toLocaleTimeString([], {
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })
                                        }}
                                    </p>
                                </td>
                            </tr>
                            <tr v-if="salaries.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center bg-gray-50/20"
                                >
                                    <div
                                        class="flex flex-col items-center justify-center"
                                    >
                                        <div
                                            class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-300 mb-4"
                                        >
                                            <Banknote size="32" />
                                        </div>
                                        <h3
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            No salary records found
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Ready to pay? Record your first
                                            salary disbursement.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="salaries.links.length > 3"
                    class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between"
                >
                    <div class="hidden sm:block">
                        <p class="text-xs text-gray-500">
                            Showing
                            <span class="font-medium">{{ salaries.from }}</span>
                            to
                            <span class="font-medium">{{ salaries.to }}</span>
                            of
                            <span class="font-medium">{{
                                salaries.total
                            }}</span>
                            entries
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, k) in salaries.links" :key="k">
                            <div
                                v-if="link.url === null"
                                class="px-3 py-1.5 text-xs text-gray-400 border border-transparent pointer-events-none"
                                v-html="link.label"
                            ></div>
                            <Link
                                v-else
                                :href="link.url"
                                class="px-3 py-1.5 text-xs rounded-lg transition-all"
                                :class="
                                    link.active
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-200'
                                        : 'text-gray-600 hover:bg-white border border-transparent hover:border-gray-200'
                                "
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal for adding salary -->
            <transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="absolute inset-0 bg-gray-900/40 transition-opacity"
                        @click="showModal = false"
                    ></div>

                    <div
                        class="bg-white rounded-[2rem] shadow-2xl shadow-gray-200/50 w-full max-w-lg transform transition-all overflow-hidden relative border border-gray-100"
                    >
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mr-4"
                                    >
                                        <Wallet size="20" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-gray-900 leading-none"
                                        >
                                            Disburse Salary
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            Record a new payment transaction.
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="showModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
                                >
                                    <X size="20" />
                                </button>
                            </div>

                            <form
                                @submit.prevent="submitForm"
                                class="space-y-6"
                            >
                                <div class="space-y-2">
                                    <label
                                        class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest pl-1"
                                        >Target Employee</label
                                    >
                                    <select
                                        v-model="form.employee_id"
                                        @change="fillAmount"
                                        class="w-full px-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none appearance-none"
                                        required
                                    >
                                        <option value="">
                                            Choose an employee...
                                        </option>
                                        <option
                                            v-for="employee in employees"
                                            :key="employee.id"
                                            :value="employee.id"
                                        >
                                            {{ employee.name }} (Current Base:
                                            ৳{{
                                                Number(
                                                    employee.monthly_salary,
                                                ).toLocaleString()
                                            }})
                                        </option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label
                                            class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest pl-1"
                                            >Billing Month</label
                                        >
                                        <select
                                            v-model="form.month"
                                            class="w-full px-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none appearance-none"
                                            required
                                        >
                                            <option value="">
                                                Select Month
                                            </option>
                                            <option
                                                v-for="month in generatedMonths"
                                                :key="month.value"
                                                :value="month.value"
                                            >
                                                {{ month.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="space-y-2 text-gray-900">
                                        <label
                                            class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest pl-1"
                                            >Amount to Pay</label
                                        >
                                        <div class="relative">
                                            <span
                                                class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold text-sm"
                                                >৳</span
                                            >
                                            <input
                                                type="number"
                                                v-model.number="form.amount"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="w-full pl-8 pr-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm font-semibold focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label
                                        class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest pl-1"
                                        >Disbursement Date</label
                                    >
                                    <div class="relative">
                                        <span
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400"
                                        >
                                            <Calendar size="16" />
                                        </span>
                                        <input
                                            type="date"
                                            v-model="form.paid_date"
                                            class="w-full pl-11 pr-4 py-3 bg-gray-50/50 border border-gray-100 rounded-xl text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                                        />
                                    </div>
                                </div>

                                <div class="pt-4 flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="showModal = false"
                                        class="flex-1 px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="processing"
                                        class="flex-[2] inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-blue-200 disabled:opacity-50"
                                    >
                                        <Loader2
                                            v-if="processing"
                                            size="18"
                                            class="mr-2 animate-spin"
                                        />
                                        {{
                                            processing
                                                ? "Processing..."
                                                : "Confirm Disbursement"
                                        }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, reactive, computed } from "vue";
import { router, Head } from "@inertiajs/vue3";
import _ from "lodash";
import {
    Plus,
    Search,
    Banknote,
    CalendarCheck,
    History,
    Receipt,
    Calendar,
    Edit,
    Trash2,
    X,
    Wallet,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    salaries: Object,
    employees: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || "");
const showModal = ref(false);
const processing = ref(false);

const form = reactive({
    employee_id: "",
    month: "",
    amount: "",
    paid_date: new Date().toISOString().substr(0, 10),
});

// Auto-fill amount when employee is selected
const fillAmount = () => {
    const selectedEmployee = props.employees.find(
        (emp) => emp.id === form.employee_id,
    );
    if (selectedEmployee) {
        form.amount = selectedEmployee.monthly_salary;
    } else {
        form.amount = "";
    }
};

// Generate dynamic months (Last 12 months + next 2 months)
const generatedMonths = computed(() => {
    const months = [];
    const date = new Date();
    // Start from 2 months ahead
    date.setMonth(date.getMonth() + 2);

    for (let i = 0; i < 15; i++) {
        const monthYear = date.toLocaleString("en-US", {
            month: "long",
            year: "numeric",
        });
        months.push({ value: monthYear, label: monthYear });
        date.setMonth(date.getMonth() - 1);
    }
    return months;
});

// Set current month by default
form.month = new Date().toLocaleString("en-US", {
    month: "long",
    year: "numeric",
});

const submitForm = () => {
    processing.value = true;
    router.post(route("admin.employee.salary.store"), form, {
        onSuccess: () => {
            showModal.value = false;
            form.employee_id = "";
            // keep default month and date
            form.amount = "";
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
        preserveScroll: true,
    });
};

const debouncedSearch = _.debounce(() => {
    router.get(
        route("admin.employee.salary.index"),
        { search: search.value },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
</style>
