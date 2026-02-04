<script setup lang="ts">
import { computed } from 'vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps<{
    title: string;
    items: Array<any>;
    type: 'invoice' | 'movement'; // invoice or movement
    isConciliated: boolean;
    currentSort?: string;
    currentDirection?: string;
}>();

const emit = defineEmits(['toggleSort']);

const localizedTitle = computed(() => {
    if (props.type === 'invoice') {
        return props.isConciliated ? wTrans('Facturas Conciliadas') : wTrans('Facturas Pendientes');
    } else {
        return props.isConciliated ? wTrans('Movimientos Conciliados') : wTrans('Movimientos Pendientes');
    }
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount);
};

const formatDate = (dateString: string) => {
    if (!dateString) return "";
    const d = new Date(dateString);
    const userTimezoneOffset = d.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(d.getTime() + userTimezoneOffset);
    return adjustedDate.toLocaleDateString("es-ES", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-[600px]"
    >
        <div
            class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-700"
        >
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">
                    {{ localizedTitle }}
                </h3>
                <span
                    :class="[
                        'py-1 px-3 rounded-full text-xs font-bold',
                        type === 'invoice' 
                            ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-200' 
                            : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200'
                    ]"
                >
                    {{ items.length }} items
                </span>
            </div>

            <!-- Sort Button -->
            <button
                @click="emit('toggleSort', 'amount')"
                class="text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 uppercase flex items-center gap-1 transition-colors"
            >
                ORDENAR MONTO
                <svg v-if="currentSort === 'amount'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path v-if="currentDirection === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                     <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h5m4 0v12m0 0l-4-4m4 4l4-4"></path>
                </svg>
                <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50">
            <div class="space-y-3">
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition"
                >
                    <div class="flex justify-between items-start">
                        <div class="w-2/3">
                            <div
                                class="font-bold text-gray-800 dark:text-gray-200 truncate"
                                :title="type === 'invoice' ? item.nombre : item.descripcion"
                            >
                                {{ type === 'invoice' ? item.nombre : item.descripcion }}
                            </div>
                            <!-- Invoice Specifics -->
                            <template v-if="type === 'invoice'">
                                <div
                                    class="text-xs text-gray-500 font-mono mt-1 truncate"
                                    :title="item.uuid"
                                >
                                    {{ item.uuid }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ formatDate(item.fecha_emision) }}
                                </div>
                            </template>
                            
                            <!-- Movement Specifics -->
                            <template v-else>
                                <div class="text-xs text-gray-500 mt-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ formatDate(item.fecha) }}
                                </div>
                                <div class="mt-2">
                                    <span class="text-xs badge bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full inline-block">{{ item.tipo }}</span>
                                </div>
                            </template>
                        </div>
                        <div
                            class="font-mono font-bold text-lg"
                            :class="type === 'invoice' ? 'text-indigo-700 dark:text-indigo-400' : 'text-green-700 dark:text-green-400'"
                        >
                            {{ formatCurrency(Number(item.monto)) }}
                        </div>
                    </div>
                    
                    <!-- Conciliation Info -->
                    <div
                        v-if="isConciliated"
                        class="mt-3 pt-3 border-t border-gray-100 text-xs text-green-600 flex flex-col gap-1"
                    >
                        <div class="flex items-center font-semibold">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Conciliado:
                            {{ formatDate(item.conciliaciones[0]?.created_at) }}
                        </div>
                        <div class="flex items-center text-gray-500 pl-4">
                            Por: {{ item.conciliaciones[0]?.user?.name || "Sistema" }}
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div v-if="items.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500">
                    <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    <p>{{ $t('Sin resultados') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
