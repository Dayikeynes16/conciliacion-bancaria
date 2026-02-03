<script setup lang="ts">
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps<{
    totalInvoices: number;
    totalMovements: number;
    diff: number;
    hasSelection: boolean;
    processing: boolean;
    autoReconciling: boolean;
}>();

defineEmits(['validate', 'auto-reconcile']);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(amount);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 sticky top-0 z-10 p-6 border-b border-gray-200 dark:border-gray-700">
        <!-- Totals Row -->
        <div class="flex flex-wrap gap-8 mb-6">
            <div>
                <span class="block text-xs text-gray-500 uppercase font-bold tracking-widest mb-1">{{ $t('FACTURAS SELECCIONADAS') }}</span>
                <span class="text-2xl font-bold font-mono dark:text-white">{{ formatCurrency(totalInvoices) }}</span>
            </div>
            <div>
                <span class="block text-xs text-gray-500 uppercase font-bold tracking-widest mb-1">{{ $t('MOVIMIENTOS SELECCIONADOS') }}</span>
                <span class="text-2xl font-bold font-mono dark:text-white">{{ formatCurrency(totalMovements) }}</span>
            </div>
            <div>
                <span class="block text-xs text-gray-500 uppercase font-bold tracking-widest mb-1">{{ $t('DIFERENCIA') }}</span>
                <span 
                    class="text-2xl font-bold font-mono"
                    :class="{
                        'text-green-500': diff > -0.01 && diff < 0.01,
                        'text-green-600': diff >= 0.01,
                        'text-red-600': diff <= -0.01
                    }"
                >
                    {{ formatCurrency(diff) }}
                </span>
            </div>
        </div>

        <!-- Actions Row -->
        <div class="flex gap-4">
            <button 
                @click="$emit('auto-reconcile')" 
                :disabled="autoReconciling"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-indigo-500 dark:border-indigo-400 rounded-md font-semibold text-xs text-indigo-700 dark:text-indigo-300 uppercase tracking-widest shadow-sm hover:bg-indigo-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
            >
                <svg v-if="autoReconciling" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                {{ $t('AUTO CONCILIAR') }}
            </button>

            <PrimaryButton 
                @click="$emit('validate')" 
                :disabled="!hasSelection || processing"
            >
                {{ $t('CONCILIAR SELECCIÓN') }}
            </PrimaryButton>
        </div>
    </div>
</template>
