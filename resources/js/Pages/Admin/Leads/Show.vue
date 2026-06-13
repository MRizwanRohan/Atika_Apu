<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, Head } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "@/Composables/useToast";
import { 
    ChevronLeft, 
    Calendar, 
    User, 
    Mail, 
    Phone, 
    Package, 
    MessageCircle, 
    Camera,
    Info,
    ArrowUpRight,
    ExternalLink
} from "lucide-vue-next";

const { success, error } = useToast();

const props = defineProps({
    lead: Object,
});

const lead = ref({ ...props.lead });

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const updateStatus = (newStatus) => {
    fetch(`/admin/leads/${lead.value.id}/status`, {
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
                lead.value.status = newStatus;
            } else {
                error(data.message || "Failed to update lead status.");
            }
        })
        .catch((err) => {
            console.error("Error:", err);
            error("An error occurred while updating the status.");
        });
};
</script>

<template>
    <Head title="Lead Details" />
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Slim Header -->
            <div
                class="flex items-center justify-between bg-white px-4 py-3 rounded-xl border border-gray-200 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/leads"
                        class="p-1.5 bg-gray-50 text-gray-400 hover:text-gray-900 rounded-lg border border-gray-100 transition-all shadow-sm"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            Lead Overview #{{ lead.id }}
                        </h1>
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest leading-none mt-1">
                            Submission Details
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        :value="lead.status"
                        @change="updateStatus($event.target.value)"
                        class="px-2.5 py-1.5 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-bold uppercase tracking-widest outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all shadow-sm cursor-pointer"
                    >
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="converted">Converted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Main Info -->
                <div class="md:col-span-2 space-y-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm overflow-hidden relative">
                         <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                         
                         <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-1.5">
                                    <User class="w-2.5 h-2.5" /> Full Name
                                </p>
                                <p class="text-[13px] font-semibold text-gray-900">{{ lead.name || 'Anonymous' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-1.5">
                                    <Package class="w-2.5 h-2.5" /> Interested In
                                </p>
                                <div class="inline-flex items-center gap-1.5 bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded text-[11px] underline underline-offset-2 decoration-2 decoration-indigo-200">
                                    {{ lead.product_interest }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-1.5">
                                    <Mail class="w-2.5 h-2.5" /> Email
                                </p>
                                <a :href="`mailto:${lead.email}`" class="text-[12px] font-medium text-blue-600 hover:underline flex items-center gap-1">
                                    {{ lead.email }} <ArrowUpRight class="w-3 h-3" />
                                </a>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-1.5">
                                    <Phone class="w-2.5 h-2.5" /> Contact Number
                                </p>
                                <p class="text-[12px] font-semibold text-gray-900">{{ lead.phone || 'N/A' }}</p>
                            </div>
                            <div class="space-y-1 col-span-1 sm:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-1.5">
                                    <ExternalLink class="w-2.5 h-2.5" /> Reference Link
                                </p>
                                <a v-if="lead.reference_link" :href="lead.reference_link" target="_blank" class="text-[12px] font-semibold text-blue-600 hover:underline inline-flex items-center gap-1 max-w-full break-all">
                                    {{ lead.reference_link }} <ArrowUpRight class="w-3.5 h-3.5 shrink-0" />
                                </a>
                                <p v-else class="text-[11px] text-gray-400 italic">No reference link provided.</p>
                            </div>
                         </div>
                    </div>

                    <!-- Message Block -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-3">
                            <MessageCircle class="w-2.5 h-2.5" /> Submission Message
                        </p>
                        <div class="bg-gray-50/50 rounded-xl p-4 border border-gray-100 shadow-inner">
                            <p v-if="lead.message" class="text-[12px] text-gray-600 leading-relaxed font-normal whitespace-pre-wrap">
                                {{ lead.message }}
                            </p>
                            <p v-else class="text-[11px] text-gray-300 italic font-medium">No special message provided by lead.</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-4">
                    <!-- Uploaded Screenshot -->
                    <div v-if="lead.image_path" class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm overflow-hidden group">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.15em] flex items-center gap-1.5 mb-3">
                            <Camera class="w-2.5 h-2.5" /> Proof/Screenshot
                        </p>
                        <div class="aspect-square rounded-xl overflow-hidden border border-gray-100 relative group">
                            <img
                                :src="lead.image_path"
                                alt="Screenshot"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                            />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <a :href="lead.image_path" target="_blank" class="p-2 bg-white rounded-full shadow-lg text-gray-800 scale-75 group-hover:scale-100 transition-transform">
                                    <Eye class="w-5 h-5" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="bg-indigo-600 p-5 rounded-2xl text-white shadow-xl shadow-indigo-100">
                         <p class="text-[9px] font-bold text-indigo-300 uppercase tracking-[0.2em] mb-4">Tracking Meta</p>
                         <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center border border-white/10">
                                    <Clock class="w-4 h-4 text-indigo-200" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[8px] font-bold text-indigo-200 uppercase leading-none mb-1">Created At</p>
                                    <p class="text-[11px] font-semibold truncate">{{ formatDate(lead.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center border border-white/10">
                                    <Info class="w-4 h-4 text-indigo-200" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[8px] font-bold text-indigo-200 uppercase leading-none mb-1">Status Reference</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider">{{ lead.status }}</p>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
