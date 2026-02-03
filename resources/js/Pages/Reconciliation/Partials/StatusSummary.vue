<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    activeTab: string;
    totalPendingInvoices: number;
    totalConciliatedInvoices: number;
    totalPendingMovements: number;
    totalConciliatedMovements: number;
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount);
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div
            class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-indigo-100 dark:border-indigo-900/50 flex flex-col"
        >
            <span
                class="text-indigo-900 dark:text-indigo-200 font-semibold text-sm uppercase tracking-wide"
            >
                {{
                    activeTab === "pending"
                        ? $t("Total Facturas Pendientes")
                        : $t("Total Facturas Conciliadas")
                }}
            </span>
            <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                {{
                    formatCurrency(
                        activeTab === "pending"
                            ? totalPendingInvoices
                            : totalConciliatedInvoices,
                    )
                }}
            </span>
        </div>
        <div
            class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-green-100 dark:border-green-900/50 flex flex-col"
        >
            <span
                class="text-green-900 dark:text-green-200 font-semibold text-sm uppercase tracking-wide"
            >
                {{
                    activeTab === "pending"
                        ? $t("Total Pagos Pendientes")
                        : $t("Total Pagos Conciliados")
                }}
            </span>
            <span class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">
                {{
                    formatCurrency(
                        activeTab === "pending"
                            ? totalPendingMovements
                            : totalConciliatedMovements,
                    )
                }}
            </span>
        </div>
    </div>
</template>
