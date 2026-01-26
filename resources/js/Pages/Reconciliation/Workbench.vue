<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

type Invoice = {
    id: number;
    uuid: string;
    nombre: string;
    fecha_emision: string;
    monto: number;
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
}>();

const selectedInvoices = ref<number[]>([]);
const selectedMovements = ref<number[]>([]);

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
    Math.abs(totalInvoices.value - totalMovements.value),
);
const isMatchable = computed(
    () =>
        selectedInvoices.value.length > 0 && selectedMovements.value.length > 0,
);

const reconcile = () => {
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
        },
    );
};

const autoReconcile = () => {
    router.post(route("reconciliation.auto"));
};
</script>

<template>
    <Head title="Mesa de Conciliación" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mesa de Trabajor (Workbench)
                </h2>
                <button
                    @click="autoReconcile"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                >
                    Auto Conciliar (Magia)
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Matcher Controls -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4 sticky top-0 z-10 border-b border-gray-200"
                >
                    <div class="flex justify-between items-center">
                        <div class="flex gap-8">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Selected Invoices:</span
                                >
                                <div class="text-2xl font-bold">
                                    ${{ totalInvoices.toFixed(2) }}
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Selected Movements:</span
                                >
                                <div class="text-2xl font-bold">
                                    ${{ totalMovements.toFixed(2) }}
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Difference:</span
                                >
                                <div
                                    :class="{
                                        'text-green-600': diff === 0,
                                        'text-red-500': diff > 0,
                                    }"
                                    class="text-2xl font-bold"
                                >
                                    ${{ diff.toFixed(2) }}
                                </div>
                            </div>
                        </div>
                        <button
                            @click="reconcile"
                            :disabled="!isMatchable"
                            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-2 px-6 rounded shadow-lg transition"
                        >
                            Conciliar Selection
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Invoices Column -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-bold mb-4">
                                Facturas Pendientes
                            </h3>
                            <div
                                class="space-y-2 max-h-[600px] overflow-y-auto"
                            >
                                <div
                                    v-for="invoice in invoices"
                                    :key="invoice.id"
                                    @click="toggleInvoice(invoice.id)"
                                    class="p-3 border rounded cursor-pointer hover:bg-gray-50 transition flex justify-between items-center"
                                    :class="{
                                        'ring-2 ring-indigo-500 bg-indigo-50':
                                            selectedInvoices.includes(
                                                invoice.id,
                                            ),
                                    }"
                                >
                                    <div>
                                        <div class="font-bold">
                                            {{ invoice.nombre }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ invoice.fecha_emision }}
                                        </div>
                                        <div
                                            class="text-xs text-gray-400 truncate w-32"
                                        >
                                            {{ invoice.uuid }}
                                        </div>
                                    </div>
                                    <div
                                        class="font-mono font-bold text-indigo-700"
                                    >
                                        ${{ Number(invoice.monto).toFixed(2) }}
                                    </div>
                                </div>
                                <div
                                    v-if="invoices.length === 0"
                                    class="text-center text-gray-500 py-8"
                                >
                                    No hay facturas pendientes.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Movements Column -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-bold mb-4">
                                Movimientos Bancarios
                            </h3>
                            <div
                                class="space-y-2 max-h-[600px] overflow-y-auto"
                            >
                                <div
                                    v-for="movement in movements"
                                    :key="movement.id"
                                    @click="toggleMovement(movement.id)"
                                    class="p-3 border rounded cursor-pointer hover:bg-gray-50 transition flex justify-between items-center"
                                    :class="{
                                        'ring-2 ring-green-500 bg-green-50':
                                            selectedMovements.includes(
                                                movement.id,
                                            ),
                                    }"
                                >
                                    <div>
                                        <div class="font-bold">
                                            {{ movement.descripcion }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ movement.fecha }}
                                        </div>
                                        <div
                                            class="text-xs text-gray-400 badge bg-gray-100 px-1 rounded"
                                        >
                                            {{ movement.tipo }}
                                        </div>
                                    </div>
                                    <div
                                        class="font-mono font-bold text-green-700"
                                    >
                                        ${{ Number(movement.monto).toFixed(2) }}
                                    </div>
                                </div>
                                <div
                                    v-if="movements.length === 0"
                                    class="text-center text-gray-500 py-8"
                                >
                                    No hay movimientos pendientes.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
