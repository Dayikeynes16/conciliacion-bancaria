<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    matches: Array<{
        invoice: any;
        movement: any;
        score: number;
        difference: number;
    }>;
    tolerance: {
        amount: number;
    };
}>();

const selectedMatches = ref<number[]>([]);

// Initialize all selected by default? Maybe better to let user choose, or select high confidence ones.
// Let's select all by default for "Auto" feel, user deselects bad ones.
selectedMatches.value = props.matches.map((_, index) => index);

const form = useForm({
    matches: [] as Array<{ invoice_id: number; movement_id: number }>
});

const submit = () => {
    // Transform indices to objects
    form.matches = selectedMatches.value.map(index => {
        const match = props.matches[index];
        return {
            invoice_id: match.invoice.id,
            movement_id: match.movement.id
        };
    });
    
    form.post(route('reconciliation.batch'));
};

const toggleAll = (e: Event) => {
    if ((e.target as HTMLInputElement).checked) {
        selectedMatches.value = props.matches.map((_, index) => index);
    } else {
        selectedMatches.value = [];
    }
};

const totalSelectedAmount = computed(() => {
    return selectedMatches.value.reduce((sum, index) => {
        return sum + parseFloat(props.matches[index].invoice.monto);
    }, 0);
});

// Format currency
const currency = (amount: number | string) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(amount));
};

// Format date (UTC safe)
const date = (dateString: string) => {
    if (!dateString) return '';
    // Dates from backend are 'YYYY-MM-DD' or ISO. 
    // We want to treat them as specific day, ignoring browser timezone.
    // Create date object, access UTC parts or split string if simple YMD.
    
    // Simple robust generic:
    const d = new Date(dateString);
    // Add timezone offset to force it to be treated as local time
    const userTimezoneOffset = d.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(d.getTime() + userTimezoneOffset);
    
    return adjustedDate.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};
</script>

<template>
    <Head title="Confirmar Coincidencias" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Confirmar Coincidencias Automáticas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <div class="mb-6 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium">Propuesta de Conciliación</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    Se encontraron {{ matches.length }} coincidencias del <span class="font-bold">Mismo Mes</span> basándose en una tolerancia de 
                                    <span class="font-bold">{{ currency(tolerance.amount) }}</span>.
                                </p>
                            </div>
                            <div class="flex gap-4 items-center">
                                <div class="text-right mr-4">
                                    <div class="text-sm text-gray-500">Total a Conciliar</div>
                                    <div class="text-xl font-bold text-green-600">{{ currency(totalSelectedAmount) }}</div>
                                </div>
                                <button 
                                    @click="submit" 
                                    :disabled="form.processing || selectedMatches.length === 0"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Procesando...' : 'Confirmar y Aplicar' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="matches.length === 0" class="text-center py-12 text-gray-500">
                            No se encontraron coincidencias con la configuración actual.
                            <div class="mt-4">
                                <Link :href="route('reconciliation.index')" class="text-blue-600 hover:underline">
                                    Volver a la Mesa de Trabajo
                                </Link>
                            </div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100/50 border-b border-gray-200">
                                        <th class="p-4 w-10">
                                            <input 
                                                type="checkbox" 
                                                :checked="selectedMatches.length === matches.length"
                                                @change="toggleAll"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                        </th>
                                        <th class="p-4 font-semibold text-gray-600">Factura</th>
                                        <th class="p-4 font-semibold text-gray-600">Movimiento Bancario</th>
                                        <th class="p-4 font-semibold text-gray-600 text-center">Diferencia</th>
                                        <th class="p-4 font-semibold text-gray-600 text-center">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(match, index) in matches" 
                                        :key="index"
                                        class="border-b border-gray-100 hover:bg-blue-50 transition-colors cursor-pointer"
                                        :class="{'bg-blue-50': selectedMatches.includes(index)}"
                                        @click="selectedMatches.includes(index) ? selectedMatches = selectedMatches.filter(i => i !== index) : selectedMatches.push(index)"
                                    >
                                        <td class="p-4" @click.stop>
                                            <input 
                                                type="checkbox" 
                                                :value="index" 
                                                v-model="selectedMatches"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                        </td>
                                        <td class="p-4">
                                            <div class="font-medium text-gray-900">{{ match.invoice.nombre || match.invoice.rfc }}</div>
                                            <div class="text-xs text-gray-500">{{ match.invoice.uuid }}</div>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ date(match.invoice.fecha_emision) }}</span>
                                                <span class="font-bold text-gray-800">{{ currency(match.invoice.monto) }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4 border-l border-gray-100">
                                            <div class="font-medium text-gray-900 truncate max-w-xs" :title="match.movement.descripcion">{{ match.movement.descripcion }}</div>
                                            <div class="text-xs text-gray-500">{{ match.movement.referencia || 'Sin Referencia' }}</div>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ date(match.movement.fecha) }}</span>
                                                <span class="font-bold text-green-700">{{ currency(match.movement.monto) }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div 
                                                class="font-mono font-bold"
                                                :class="match.difference === 0 ? 'text-gray-400' : 'text-amber-600'"
                                            >
                                                {{ match.difference === 0 ? '--' : currency(match.difference) }}
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ Math.round(match.score) }}%
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200 flex justify-between">
                             <Link :href="route('reconciliation.index')" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancelar
                             </Link>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
