<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { debounce } from "lodash";
import StatusTabs from "./Partials/StatusTabs.vue";
import StatusSummary from "./Partials/StatusSummary.vue";
import TransactionList from "./Partials/TransactionList.vue";
import AdvancedFilters from "@/Components/AdvancedFilters.vue";

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
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
        invoice_sort?: string;
        invoice_direction?: string;
        movement_sort?: string;
        movement_direction?: string;
    };
}>();

const activeTab = ref("pending"); // pending | conciliated
const search = ref(props.filters?.search || "");

// Independent Sort States
const invoiceSort = ref(props.filters?.invoice_sort || "date");
const invoiceDirection = ref(props.filters?.invoice_direction || "desc");
const movementSort = ref(props.filters?.movement_sort || "date");
const movementDirection = ref(props.filters?.movement_direction || "desc");

const updateParams = (newFilters: any = {}) => {
    // If newFilters has keys (from AdvancedFilters emit), use it.
    // Otherwise (from sort watchers), fallback to current props.filters.
    const currentFilters =
        Object.keys(newFilters).length > 0 ? newFilters : props.filters || {};

    const params = {
        search: currentFilters.search,
        date_from: currentFilters.date_from,
        date_to: currentFilters.date_to,
        amount_min: currentFilters.amount_min,
        amount_max: currentFilters.amount_max,
        invoice_sort: invoiceSort.value,
        invoice_direction: invoiceDirection.value,
        movement_sort: movementSort.value,
        movement_direction: movementDirection.value,
    };

    router.get(route("reconciliation.status"), params, {
        preserveState: true,
        replace: true,
    });
};

// Remove search watcher since component handles it
// watch(search, debounce(updateParams, 300));
watch([invoiceSort, invoiceDirection, movementSort, movementDirection], () =>
    updateParams(),
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

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top Bar: Filters (Tabs inside) -->
                <div class="mb-6">
                    <AdvancedFilters
                        :filters="filters"
                        :placeholder="$t('Buscar ID, RFC, Nombre o Monto...')"
                        @update="updateParams"
                    >
                        <template #footer>
                            <StatusTabs
                                v-model="activeTab"
                                :pending-count="
                                    pendingInvoices.length +
                                    pendingMovements.length
                                "
                                :conciliated-count="
                                    conciliatedInvoices.length +
                                    conciliatedMovements.length
                                "
                            />
                        </template>
                    </AdvancedFilters>
                </div>

                <StatusSummary
                    :active-tab="activeTab"
                    :total-pending-invoices="totalPendingInvoices"
                    :total-conciliated-invoices="totalConciliatedInvoices"
                    :total-pending-movements="totalPendingMovements"
                    :total-conciliated-movements="totalConciliatedMovements"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Invoices Column -->
                    <TransactionList
                        title="Facturas"
                        type="invoice"
                        :items="
                            activeTab === 'pending'
                                ? pendingInvoices
                                : conciliatedInvoices
                        "
                        :is-conciliated="activeTab === 'conciliated'"
                        :current-sort="invoiceSort"
                        :current-direction="invoiceDirection"
                        @toggle-sort="
                            (s) => {
                                if (invoiceSort === s) {
                                    invoiceDirection =
                                        invoiceDirection === 'asc'
                                            ? 'desc'
                                            : 'asc';
                                } else {
                                    invoiceSort = s;
                                    invoiceDirection = 'desc';
                                }
                            }
                        "
                    />

                    <!-- Movements Column -->
                    <TransactionList
                        title="Movimientos"
                        type="movement"
                        :items="
                            activeTab === 'pending'
                                ? pendingMovements
                                : conciliatedMovements
                        "
                        :is-conciliated="activeTab === 'conciliated'"
                        :current-sort="movementSort"
                        :current-direction="movementDirection"
                        @toggle-sort="
                            (s) => {
                                if (movementSort === s) {
                                    movementDirection =
                                        movementDirection === 'asc'
                                            ? 'desc'
                                            : 'asc';
                                } else {
                                    movementSort = s;
                                    movementDirection = 'desc';
                                }
                            }
                        "
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
