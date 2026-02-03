<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { debounce } from "lodash";
import StatusTabs from "./Partials/StatusTabs.vue";
import StatusSummary from "./Partials/StatusSummary.vue";
import TransactionList from "./Partials/TransactionList.vue";

const props = defineProps<{
    conciliatedInvoices: Array<any>;
    conciliatedMovements: Array<any>;
    pendingInvoices: Array<any>;
    pendingMovements: Array<any>;
    totalPendingInvoices: number;
    totalPendingMovements: number;
    totalConciliatedInvoices: number;
    totalConciliatedMovements: number;
    filters?: {
        search?: string;
    };
}>();

const activeTab = ref("pending"); // pending | conciliated
const search = ref(props.filters?.search || "");

watch(
    search,
    debounce((value) => {
        router.get(
            route("reconciliation.status"),
            { search: value },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300),
);
</script>

<template>
    <Head title="Reporte de Estatus" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Reporte de Estatus de Conciliación
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top Bar: Tabs & Search -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <!-- Tabs Component -->
                    <StatusTabs 
                        v-model="activeTab"
                        :pending-count="pendingInvoices.length + pendingMovements.length"
                        :conciliated-count="conciliatedInvoices.length + conciliatedMovements.length" 
                    />

                    <!-- Search -->
                    <div class="w-full md:w-1/3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                class="block w-full p-2 pl-10 text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Buscar ID, RFC, Nombre o Monto..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Global Totals Summary Cards -->
                <StatusSummary
                    :active-tab="activeTab"
                    :total-pending-invoices="totalPendingInvoices"
                    :total-conciliated-invoices="totalConciliatedInvoices"
                    :total-pending-movements="totalPendingMovements"
                    :total-conciliated-movements="totalConciliatedMovements"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Invoices Column -->
                    <TransactionList
                        title="Facturas"
                        type="invoice"
                        :items="activeTab === 'pending' ? pendingInvoices : conciliatedInvoices"
                        :is-conciliated="activeTab === 'conciliated'"
                    />

                    <!-- Movements Column -->
                    <TransactionList
                        title="Movimientos"
                        type="movement"
                        :items="activeTab === 'pending' ? pendingMovements : conciliatedMovements"
                        :is-conciliated="activeTab === 'conciliated'"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
