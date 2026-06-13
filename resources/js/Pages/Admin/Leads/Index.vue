<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToast } from "@/Composables/useToast";
import { 
    User, 
    Mail, 
    Phone, 
    Package, 
    Clock, 
    Eye, 
    Trash2, 
    ChevronRight,
    Search,
    Filter
} from "lucide-vue-next";

const { success, error } = useToast();

const props = defineProps({
    leads: Array,
});

const selectedStatus = ref("");
const filteredLeads = computed(() => {
    if (!selectedStatus.value) {
        return props.leads;
    }
    return props.leads.filter((lead) => lead.status === selectedStatus.value);
});

const getStatusClass = (status) => {
    const classes = {
        pending: "bg-amber-50 text-amber-600 border border-amber-100",
        contacted: "bg-blue-50 text-blue-600 border border-blue-100",
        converted: "bg-emerald-50 text-emerald-600 border border-emerald-100",
        rejected: "bg-rose-50 text-rose-600 border border-rose-100",
    };
    return classes[status] || "bg-gray-50 text-gray-600 border border-gray-100";
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const updateStatus = (leadId, newStatus) => {
    fetch(`/admin/leads/${leadId}/status`, {
        method: "PATCH",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({ status: newStatus }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                success("Lead status updated successfully!");
                const lead = props.leads.find((l) => l.id === leadId);
                if (lead) lead.status = newStatus;
            } else {
                error(data.message || "Failed to update lead status.");
            }
        })
        .catch((err) => {
            console.error("Error:", err);
            error("An error occurred while updating the status.");
        });
};

const deleteLead = (leadId) => {
    if (
        !confirm(
            "Are you sure you want to delete this lead? This action cannot be undone."
        )
    ) {
        return;
    }

    fetch(`/admin/leads/${leadId}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                success("Lead deleted successfully!");
                const index = props.leads.findIndex((l) => l.id === leadId);
                if (index > -1) {
                    props.leads.splice(index, 1);
                }
            } else {
                error(data.message || "Failed to delete lead.");
            }
        })
        .catch((err) => {
            console.error("Error:", err);
            error("An error occurred while deleting the lead.");
        });
};
</script>

<template>
    <Head title="Pre-Order Leads" />
    <AdminLayout>
        <div class="space-y-4">
            <!-- Slim Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-3 rounded-xl border border-gray-200 shadow-sm"
            >
                <div>
                    <h1 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <Package class="w-4 h-4 text-gray-500" />
                        Pre-Order Leads
                    </h1>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-[0.1em] mt-1 leading-none">
                        Manage pre-order submissions
                    </p>
                </div>
                <!-- Mini Filter -->
                <div class="flex items-center gap-2">
                    <div class="relative group">
                        <Filter class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                        <select
                            v-model="selectedStatus"
                            class="pl-8 pr-4 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-[10px] font-bold uppercase tracking-widest outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none cursor-pointer"
                        >
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="contacted">Contacted</option>
                            <option value="converted">Converted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Mobile: Minimal Leads Cards -->
            <div class="md:hidden space-y-2">
                <div
                    v-for="lead in filteredLeads"
                    :key="lead.id"
                    class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm relative active:scale-[0.98] transition-transform"
                >
                    <div class="flex justify-between items-start mb-2.5">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-[10px] font-bold text-gray-400 border border-gray-100 shrink-0"
                            >
                                {{ lead.name ? lead.name.charAt(0).toUpperCase() : '?' }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold text-gray-900 leading-none truncate">
                                    {{ lead.name || 'Anonymous' }}
                                </p>
                                <p class="text-[9px] text-gray-400 font-medium tracking-tight mt-1">
                                    Submitted {{ formatDate(lead.created_at) }}
                                </p>
                            </div>
                        </div>
                        <span
                            :class="getStatusClass(lead.status)"
                            class="px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-tight"
                        >
                            {{ lead.status }}
                        </span>
                    </div>

                    <div class="space-y-2.5 mb-3">
                        <!-- <div class="flex items-center gap-2">
                            <Mail class="w-3 h-3 text-gray-400" />
                            <p class="text-[10px] text-indigo-600 font-medium truncate">{{ lead.email }}</p>
                        </div> -->
                        <div class="flex items-center gap-2" v-if="lead.phone">
                            <Phone class="w-3 h-3 text-gray-400" />
                            <p class="text-[10px] text-gray-700 font-medium">{{ lead.phone }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Package class="w-3 h-3 text-gray-400" />
                            <p class="text-[10px] text-gray-700 font-semibold italic">{{ lead.product_interest }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2.5 border-t border-gray-50">
                        <div class="flex items-center gap-1.5">
                            <select
                                :value="lead.status"
                                @change="updateStatus(lead.id, $event.target.value)"
                                class="bg-gray-50 border border-gray-100 rounded px-2 py-1 text-[9px] font-bold uppercase tracking-tighter text-gray-500 outline-none"
                            >
                                <option value="pending">Pending</option>
                                <option value="contacted">Contacted</option>
                                <option value="converted">Converted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Link
                                :href="route('admin.leads.show', lead.id)"
                                class="p-1.5 bg-gray-50 text-gray-400 rounded-lg border border-gray-100 active:bg-white"
                            >
                                <Eye class="w-3.5 h-3.5" />
                            </Link>
                            <button
                                @click="deleteLead(lead.id)"
                                class="p-1.5 bg-gray-50 text-gray-400 rounded-lg border border-gray-100 active:bg-white active:text-red-500"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredLeads.length === 0" class="py-12 bg-white rounded-xl border-2 border-dashed border-gray-100 text-center">
                    <FilterX class="w-10 h-10 text-gray-100 mx-auto mb-2" />
                    <p class="text-[9px] font-bold text-gray-300 uppercase tracking-widest">No inquiries found</p>
                </div>
            </div>

            <!-- Desktop: High-Density Table -->
            <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100 font-bold text-gray-400 uppercase tracking-widest text-[9px]">
                        <tr>
                            <th class="p-4">Lead Info</th>
                            <th class="p-4">Contact Details</th>
                            <th class="p-4">Product Interest</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Submission</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-[11px]">
                        <tr v-for="lead in filteredLeads" :key="lead.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-[11px] font-bold text-gray-400 border border-gray-100 shrink-0">
                                        {{ lead.name ? lead.name.charAt(0).toUpperCase() : '?' }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 leading-none truncate">{{ lead.name || 'Anonymous' }}</p>
                                        <p class="text-[9px] text-gray-400 font-medium uppercase mt-1 leading-none tracking-tighter">ID: #{{ lead.id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-gray-900">
                                <p class="text-[10px] text-gray-400 mt-1 leading-none">{{ lead.phone || 'No phone' }}</p>
                            </td>
                            <td class="p-4 font-semibold text-gray-800 italic underline decoration-indigo-100 underline-offset-4">
                                {{ lead.product_interest }}
                            </td>
                            <td class="p-4">
                                <span :class="getStatusClass(lead.status)" class="px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-tight">
                                    {{ lead.status }}
                                </span>
                            </td>
                            <td class="p-4 text-gray-400 font-medium">{{ formatDate(lead.created_at) }}</td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <select
                                        :value="lead.status"
                                        @change="updateStatus(lead.id, $event.target.value)"
                                        class="text-[9px] border border-gray-200 bg-gray-50 rounded px-1.5 py-1 font-bold uppercase tracking-tighter text-gray-500 outline-none focus:border-indigo-300"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="contacted">Contacted</option>
                                        <option value="converted">Converted</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                    <Link
                                        :href="route('admin.leads.show', lead.id)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-600 transition-all rounded hover:bg-white"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="deleteLead(lead.id)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 transition-all rounded hover:bg-white"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>