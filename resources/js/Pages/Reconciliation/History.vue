<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { ref, watch, reactive } from "vue";
import debounce from "lodash/debounce";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps<{
    reconciledGroups: {
        data: Array<{
            id: string; // Group UUID
            created_at: string;
            user: { name: string };
            invoices: Array<{
                id: number;
                uuid: string;
                nombre: string;
                rfc: string;
                monto: number;
                fecha_emision: string;
            }>;
            movements: Array<{
                id: number;
                descripcion: string;
                referencia: string;
                fecha: string;
                monto: number;
                banco?: { nombre: string } | null;
            }>;
            total_invoices: number;
            total_movements: number;
            total_applied: number;
        }>;
        links: Array<any>;
    };
    filters?: {
        search?: string;
        month?: string;
        year?: string;
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
    };
}>();

const search = ref(props.filters?.search || "");

// Advanced Filters State
const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    amount_min: props.filters?.amount_min || "",
    amount_max: props.filters?.amount_max || "",
});

const applyFilters = () => {
    router.get(
        route("reconciliation.history"),
        {
            search: search.value,
            date_from: filterForm.date_from,
            date_to: filterForm.date_to,
            amount_min: filterForm.amount_min,
            amount_max: filterForm.amount_max,
            // Preserve Month/Year global filters if needed, but Date Range usually overrides
            month: props.filters?.month,
            year: props.filters?.year,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filterForm.date_from = "";
    filterForm.date_to = "";
    filterForm.amount_min = "";
    filterForm.amount_max = "";
    applyFilters();
};

watch(
    search,
    debounce((value: string) => {
        // Reuse applyFilters logic but just for search? 
        // Or keep separate. Let's redirect to applyFilters
        applyFilters();
    }, 500),
);

const formatDate = (dateString: string) => {
    if (!dateString) return "";
    const d = new Date(dateString);
    const userTimezoneOffset = d.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(d.getTime() + userTimezoneOffset);
    return adjustedDate.toLocaleDateString("es-ES", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
    }).format(amount);
};

const getDifference = (group: any) => {
    return Number(group.total_movements) - Number(group.total_applied);
};

const confirmingUnreconcile = ref(false);
const groupIdToUnlink = ref<string | null>(null);
const form = useForm({});

const confirmUnreconcile = (groupId: string) => {
    groupIdToUnlink.value = groupId;
    confirmingUnreconcile.value = true;
};

const unreconcile = () => {
    if (!groupIdToUnlink.value) return;

    form.delete(route("reconciliation.group.destroy", groupIdToUnlink.value), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => (groupIdToUnlink.value = null),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUnreconcile.value = false;
    groupIdToUnlink.value = null;
    form.reset();
};
</script>

<template>
    <Head title="Historial de Conciliaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Historial de Conciliaciones
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters Section -->
                <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Filtros de Búsqueda</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Date Range -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Desde</label>
                            <input type="date" v-model="filterForm.date_from" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
                            <input type="date" v-model="filterForm.date_to" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Amount Range -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Monto Mín ($)</label>
                            <input type="number" step="0.01" v-model="filterForm.amount_min" placeholder="0.00" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Monto Máx ($)</label>
                            <input type="number" step="0.01" v-model="filterForm.amount_max" placeholder="0.00" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-3">
                        <SecondaryButton @click="clearFilters" size="sm">Limpiar</SecondaryButton>
                        <PrimaryButton @click="applyFilters" size="sm">Aplicar Filtros</PrimaryButton>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="flex justify-between items-center mb-6">
                    <div class="relative w-full max-w-md">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <svg
                                class="h-5 w-5 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd"
                                    />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 dark:focus:placeholder-gray-300 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Buscar por factura, RFC o monto..."
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="reconciledGroups.data.length === 0"
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500 dark:text-gray-400"
                >
                    No hay conciliaciones registradas (ajusta los filtros si es necesario).
                </div>

                <!-- Groups List -->
                <div v-else class="space-y-8">
                    <div
                        v-for="group in reconciledGroups.data"
                        :key="group.id"
                        class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"
                    >
                        <!-- Group Header -->
                        <div
                            class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between md:items-center gap-4"
                        >
                            <!-- ... Header Content (Unchanged) ... -->
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                                    Conciliación
                                </h3>
                                <div class="text-sm text-gray-400 mt-1">
                                    {{ formatDate(group.created_at) }} • {{ group.user.name }}
                                </div>
                            </div>
                            
                            <div class="flex gap-8 text-right">
                                <div>
                                    <div class="text-xs text-gray-500">Total Facturas</div>
                                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">
                                        {{ formatCurrency(group.total_invoices) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Total Pagos</div>
                                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">
                                        {{ formatCurrency(group.total_movements) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Diferencia</div>
                                    <div
                                        class="text-xl font-bold"
                                        :class="{
                                            'text-gray-400': Math.abs(getDifference(group)) < 0.01,
                                            'text-green-600 dark:text-green-400': getDifference(group) >= 0.01,
                                            'text-red-500 dark:text-red-400': getDifference(group) <= -0.01
                                        }"
                                    >
                                        {{ formatCurrency(getDifference(group)) }}
                                    </div>
                                </div>
                                <div class="flex items-center">
                                     <button
                                        @click="confirmUnreconcile(group.id)"
                                        class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium underline"
                                    >
                                        Desvincular Grupo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Content Grid: Invoices vs Payments -->
                        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
                            
                            <!-- Left: Invoices -->
                            <div class="p-6 bg-indigo-50/10">
                                <h4 class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-4">
                                    Facturas ({{ group.invoices.length }})
                                </h4>
                                <div class="space-y-3">
                                    <div 
                                        v-for="invoice in group.invoices" 
                                        :key="invoice.id"
                                        class="flex justify-between items-start text-sm p-3 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700"
                                    >
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ invoice.nombre || 'Factura Eliminada' }}</div>
                                            <!-- NEW: RFC Display -->
                                            <div class="text-xs text-gray-500 font-mono mt-0.5">{{ invoice.rfc }}</div>
                                            <div class="text-xs text-gray-400 mt-1">{{ formatDate(invoice.fecha_emision) }}</div>
                                        </div>
                                        <div class="font-bold text-gray-700 dark:text-gray-300">
                                            {{ formatCurrency(Number(invoice.monto)) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Payments -->
                            <div class="p-6 bg-green-50/10">
                                <h4 class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-widest mb-4">
                                    Pagos ({{ group.movements.length }})
                                </h4>
                                <div class="space-y-3">
                                    <div 
                                        v-for="movement in group.movements" 
                                        :key="movement.id"
                                        class="flex justify-between items-start text-sm p-3 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700"
                                    >
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ movement.descripcion || 'Sin Descripción' }}</div>
                                            <!-- NEW: Bank Name Display -->
                                            <div class="text-xs font-semibold text-indigo-500 mt-0.5" v-if="movement.banco">
                                                {{ movement.banco.nombre }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ movement.referencia || 'Ref: N/A' }}</div>
                                            <div class="text-xs text-gray-400">{{ formatDate(movement.fecha) }}</div>
                                        </div>
                                        <div class="font-bold text-gray-700 dark:text-gray-300">
                                            {{ formatCurrency(Number(movement.monto)) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    class="mt-6 flex justify-center space-x-1 flex-wrap"
                    v-if="reconciledGroups.links.length > 3"
                >
                    <template
                        v-for="(link, key) in reconciledGroups.links"
                        :key="key"
                    >
                        <div
                            v-if="link.url === null"
                            class="px-3 py-1 border rounded text-sm text-gray-400 mb-1"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            class="px-3 py-1 border rounded text-sm hover:bg-gray-100 mb-1"
                            :class="{
                                'bg-indigo-50 border-indigo-500 text-indigo-700':
                                    link.active,
                            }"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="confirmingUnreconcile" @close="closeModal">
            <template #title> Desvincular Grupo de Conciliación </template>

            <template #content>
                ¿Estás seguro de que deseas desvincular este grupo completo? 
                Se eliminarán las relaciones entre {{ reconciledGroups.data.find(g => g.id === groupIdToUnlink)?.invoices.length }} facturas y 
                {{ reconciledGroups.data.find(g => g.id === groupIdToUnlink)?.movements.length }} pagos.
                El saldo volverá a estar pendiente.
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="unreconcile"
                >
                    Desvincular Grupo
                </PrimaryButton>
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>
