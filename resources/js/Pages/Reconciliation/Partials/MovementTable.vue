<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

defineProps<{
    movements: {
        data: Array<{
            id: number;
            fecha: string;
            descripcion: string;
            tipo: string;
            monto: number;
            conciliaciones_count: number;
            archivo?: {
                original_name?: string;
                banco?: { nombre: string };
            };
        }>;
        links: Array<any>;
    };
}>();

const formatDateNoTime = (date?: string) => {
    if (!date) return "N/A";
    const d = new Date(date);
    const userTimezoneOffset = d.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(d.getTime() + userTimezoneOffset);
    return adjustedDate.toLocaleDateString("es-MX", {
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
</script>

<template>
    <div>
        <div v-if="movements.data.length === 0" class="text-center py-8 text-gray-500">
            {{ $t('No se encontraron movimientos en este periodo.') }}
        </div>
        <div v-else>
            <div class="overflow-x-auto relative">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="py-3 px-6">{{ $t('BANCO') }}</th>
                            <th class="py-3 px-6">{{ $t('FECHA') }}</th>
                            <th class="py-3 px-6">{{ $t('DESCRIPCIÓN') }}</th>
                            <th class="py-3 px-6">{{ $t('TIPO') }}</th>
                            <th class="py-3 px-6">{{ $t('ESTADO') }}</th>
                            <th class="py-3 px-6 text-right">{{ $t('MONTO') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="mov in movements.data" :key="mov.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="py-4 px-6">
                                <div class="flex flex-col">
                                     <span class="text-xs font-bold text-indigo-600">{{ mov.archivo?.banco?.nombre || 'N/A' }}</span>
                                     <span class="text-[10px] text-gray-400">{{ mov.archivo?.original_name || 'Archivo' }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                {{ formatDateNoTime(mov.fecha) }}
                            </td>
                            <td class="py-4 px-6 max-w-sm truncate" :title="mov.descripcion">
                                {{ mov.descripcion }}
                            </td>
                            <td class="py-4 px-6">
                                 <span :class="mov.tipo === 'abono' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="text-xs font-medium px-2.5 py-0.5 rounded">
                                    {{ mov.tipo === "abono" ? $t("Abono") : $t("Cargo") }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span v-if="mov.conciliaciones_count && mov.conciliaciones_count > 0" class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-green-200 dark:text-green-900 border border-green-400">
                                    {{ $t('CONCILIADO') }}
                                </span>
                                <span v-else class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-yellow-200 dark:text-yellow-900 border border-yellow-400">
                                    {{ $t('PENDIENTE') }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-mono" :class="mov.tipo === 'cargo' ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(Number(mov.monto)) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-4 flex justify-center space-x-1" v-if="movements.links.length > 3">
                 <template v-for="(link, key) in movements.links" :key="key">
                    <div v-if="link.url === null" class="px-3 py-1 border rounded text-sm text-gray-400 mb-1" v-html="link.label" />
                    <Link v-else :href="link.url" class="px-3 py-1 border rounded text-sm hover:bg-gray-100 mb-1" :class="{ 'bg-blue-600 text-white': link.active }" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
