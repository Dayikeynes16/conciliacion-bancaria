<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, computed, reactive } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

type Invoice = {
    id: number;
    uuid: string;
    nombre: string;
    fecha_emision: string;
    monto: number;
    rfc: string;
};

type Movement = {
    id: number;
    descripcion: string;
    fecha: string;
    monto: number;
    tipo: string;
};

const props = defineProps<{
    invoices: Invoice[];
    movements: Movement[];
    tolerance: number;
    filters?: {
        month?: string;
        year?: string;
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
    };
}>();

const selectedInvoices = ref<number[]>([]);
const selectedMovements = ref<number[]>([]);
const showConfirmationModal = ref(false);
const confirmationMessage = ref("");
const confirmationTitle = ref("");

const showErrorModal = ref(false);
const errorMessage = ref("");
const errorTitle = ref("");

// Filters Logic
const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    amount_min: props.filters?.amount_min || "",
    amount_max: props.filters?.amount_max || "",
});

const applyFilters = () => {
    router.get(
        route("reconciliation.index"),
        {
            date_from: filterForm.date_from,
            date_to: filterForm.date_to,
            amount_min: filterForm.amount_min,
            amount_max: filterForm.amount_max,
            month: props.filters?.month,
            year: props.filters?.year,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
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

const toggleInvoice = (id: number) => {
    if (selectedInvoices.value.includes(id)) {
        selectedInvoices.value = selectedInvoices.value.filter((i) => i !== id);
    } else {
        selectedInvoices.value.push(id);
    }
};

const toggleMovement = (id: number) => {
    if (selectedMovements.value.includes(id)) {
        selectedMovements.value = selectedMovements.value.filter(
            (i) => i !== id,
        );
    } else {
        selectedMovements.value.push(id);
    }
};

const sortedInvoices = computed(() => {
    return [...props.invoices].sort((a, b) => {
        const aSelected = selectedInvoices.value.includes(a.id);
        const bSelected = selectedInvoices.value.includes(b.id);
        if (aSelected && !bSelected) return -1;
        if (!aSelected && bSelected) return 1;
        return 0;
    });
});

const sortedMovements = computed(() => {
    return [...props.movements].sort((a, b) => {
        const aSelected = selectedMovements.value.includes(a.id);
        const bSelected = selectedMovements.value.includes(b.id);
        if (aSelected && !bSelected) return -1;
        if (!aSelected && bSelected) return 1;
        return 0;
    });
});

const processing = ref(false);
const autoReconciling = ref(false);

const totalInvoices = computed(() => {
    return props.invoices
        .filter((i) => selectedInvoices.value.includes(i.id))
        .reduce((sum, i) => sum + Number(i.monto), 0);
});

const totalMovements = computed(() => {
    return props.movements
        .filter((m) => selectedMovements.value.includes(m.id))
        .reduce((sum, m) => sum + Number(m.monto), 0);
});

const diff = computed(() =>
    totalMovements.value - totalInvoices.value,
);

const validateAndReconcile = () => {
    let warnings: string[] = [];
    let title = "Confirmar Conciliación";

    // 1. RFC Consistency Check
    const selectedInvoiceObjects = props.invoices.filter(i => selectedInvoices.value.includes(i.id));
    if (selectedInvoiceObjects.length > 1) {
        const firstRFC = selectedInvoiceObjects[0].rfc;
        const hasMismatch = selectedInvoiceObjects.some(i => i.rfc !== firstRFC);
        
        if (hasMismatch) {
            errorTitle.value = "Error de RFC";
            errorMessage.value = "Las facturas seleccionadas deben pertenecer al mismo RFC receptor.";
            showErrorModal.value = true;
            return;
        }
    }

    // 2. Tolerance Check
    const absDiff = Math.abs(diff.value);
    const tolerance = props.tolerance || 0.00;

    if (absDiff > (tolerance + 0.001)) {
        title = warnings.length > 0 ? "Advertencias de Conciliación" : "Diferencia Excede Tolerancia";
        warnings.push(`⚠ Diferencia de Monto:\nLa diferencia ($${absDiff.toFixed(2)}) es mayor que la tolerancia permitida ($${tolerance.toFixed(2)}).`);
    }

    if (warnings.length > 0) {
        confirmationTitle.value = title;
        confirmationMessage.value = warnings.join("\n\n") + "\n\n¿Estás seguro de que deseas continuar?";
        showConfirmationModal.value = true;
        return;
    }

    // If validations pass, proceed directly
    submitReconciliation();
};

const submitReconciliation = () => {
    showConfirmationModal.value = false;
    processing.value = true;
    router.post(
        route("reconciliation.store"),
        {
            invoice_ids: selectedInvoices.value,
            movement_ids: selectedMovements.value,
        },
        {
            onSuccess: () => {
                selectedInvoices.value = [];
                selectedMovements.value = [];
            },
            onFinish: () => {
                processing.value = false;
            }
        },
    );
};

const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    const userTimezoneOffset = d.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(d.getTime() + userTimezoneOffset);
    return adjustedDate.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(amount);
};

const autoReconcile = () => {
    autoReconciling.value = true;
    router.get(route("reconciliation.auto"), {}, {
        onFinish: () => {
            autoReconciling.value = false;
        }
    });
};
</script>

<template>
    <Head title="Mesa de Trabajo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Mesa de Trabajo</h2>
                


                <div class="flex items-center gap-4">
                    <button 
                        @click="autoReconcile"
                        :disabled="autoReconciling"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow flex items-center gap-2 disabled:opacity-50"
                    >
                        <svg v-if="autoReconciling" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="autoReconciling">Conciliando...</span>
                        <!-- Basic wand icon -->
                        <span v-else>Auto Conciliar (Magia)</span>
                    </button>
                </div>
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

                <!-- Selection Summary Bar -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 sticky top-0 z-10 p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div class="flex gap-8">
                        <div>
                            <span class="block text-xs text-gray-500 uppercase">Facturas Seleccionadas:</span>
                            <span class="text-xl font-bold font-mono dark:text-white">{{ formatCurrency(totalInvoices) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 uppercase">Movimientos Seleccionados:</span>
                            <span class="text-xl font-bold font-mono dark:text-white">{{ formatCurrency(totalMovements) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 uppercase">Diferencia:</span>
                            <span 
                                class="text-xl font-bold font-mono"
                                :class="{
                                    'text-green-600': diff > -0.01,
                                    'text-red-600': diff <= -0.01
                                }"
                            >
                                {{ formatCurrency(diff) }}
                            </span>
                        </div>
                    </div>

                    <PrimaryButton 
                        @click="validateAndReconcile" 
                        :disabled="selectedInvoices.length === 0 || selectedMovements.length === 0 || processing"
                    >
                        Conciliar Selección
                    </PrimaryButton>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Left Column: Invoices -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-[700px]">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-semibold text-gray-700 dark:text-gray-300">
                            Facturas Pendientes
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                           <div v-if="invoices.length === 0" class="text-center text-gray-500 py-10">
                                No hay facturas pendientes.
                           </div> 
                           
                           <div 
                                v-for="invoice in sortedInvoices" 
                                :key="invoice.id"
                                @click="toggleInvoice(invoice.id)"
                                class="p-3 border rounded-lg cursor-pointer transition-colors relative hover:shadow-md"
                                :class="{
                                    'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-500': selectedInvoices.includes(invoice.id),
                                    'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800': !selectedInvoices.includes(invoice.id)
                                }"
                           >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold text-gray-800 dark:text-gray-200 text-sm">{{ invoice.nombre }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ formatDate(invoice.fecha_emision) }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5 truncate w-48" :title="invoice.uuid">
                                            {{ invoice.uuid.substring(0, 18) }}..
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(invoice.monto) }}</div>
                                    </div>
                                </div>
                                
                                <!-- Checkmark indicator -->
                                <div v-if="selectedInvoices.includes(invoice.id)" class="absolute top-2 right-2 h-4 w-4 bg-indigo-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                           </div>
                        </div>
                    </div>

                    <!-- Right Column: Movements -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-[700px]">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-semibold text-gray-700 dark:text-gray-300">
                            Movimientos Bancarios
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                           <div v-if="movements.length === 0" class="text-center text-gray-500 py-10">
                                No hay movimientos pendientes.
                           </div> 

                           <div 
                                v-for="movement in sortedMovements" 
                                :key="movement.id"
                                @click="toggleMovement(movement.id)"
                                class="p-3 border rounded-lg cursor-pointer transition-colors relative hover:shadow-md"
                                :class="{
                                    'border-green-500 bg-green-50 dark:bg-green-900/20 ring-1 ring-green-500': selectedMovements.includes(movement.id),
                                    'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800': !selectedMovements.includes(movement.id)
                                }"
                           >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1 mr-2">
                                        <div class="font-bold text-gray-800 dark:text-gray-200 text-sm break-words leading-tight">{{ movement.descripcion }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ formatDate(movement.fecha) }}</div>
                                        <div class="text-[10px] text-gray-400 uppercase mt-0.5 tracking-wide bg-gray-100 dark:bg-gray-700 inline-block px-1 rounded">
                                            {{ movement.tipo }}
                                        </div>
                                    </div>
                                    <div class="text-right whitespace-nowrap">
                                        <div class="font-bold text-green-600 dark:text-green-400">{{ formatCurrency(movement.monto) }}</div>
                                    </div>
                                </div>

                                <!-- Checkmark indicator -->
                                <div v-if="selectedMovements.includes(movement.id)" class="absolute top-2 right-2 h-4 w-4 bg-green-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                           </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        <!-- Confirmation Modal -->
        <Modal :show="showConfirmationModal" @close="showConfirmationModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ confirmationTitle }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">
                    {{ confirmationMessage }}
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showConfirmationModal = false">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing"
                        @click="submitReconciliation"
                    >
                        Confirmar
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Error Modal -->
        <Modal :show="showErrorModal" @close="showErrorModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-red-600 dark:text-red-400">
                    {{ errorTitle }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ errorMessage }}
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showErrorModal = false">
                        Entendido
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
