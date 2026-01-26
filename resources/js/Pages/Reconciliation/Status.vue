<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { debounce } from "lodash";

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

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount);
};

const formatDate = (dateString: string) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString("es-ES", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
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
                <div
                    class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4"
                >
                    <!-- Tabs -->
                    <div
                        class="flex space-x-2 bg-gray-200 p-1 rounded-lg inline-flex"
                    >
                        <button
                            @click="activeTab = 'pending'"
                            class="px-6 py-2 rounded-md font-bold transition text-sm focus:outline-none"
                            :class="
                                activeTab === 'pending'
                                    ? 'bg-white text-indigo-600 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            "
                        >
                            Pendientes ({{
                                pendingInvoices.length +
                                pendingMovements.length
                            }})
                        </button>
                        <button
                            @click="activeTab = 'conciliated'"
                            class="px-6 py-2 rounded-md font-bold transition text-sm focus:outline-none"
                            :class="
                                activeTab === 'conciliated'
                                    ? 'bg-white text-green-600 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            "
                        >
                            Conciliados (Recientes)
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="w-full md:w-1/3">
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                            >
                                <svg
                                    class="w-4 h-4 text-gray-500"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Buscar ID, RFC, Nombre o Monto..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Global Totals Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div
                        class="bg-white p-4 rounded-lg shadow-sm border border-indigo-100 flex flex-col"
                    >
                        <span
                            class="text-indigo-900 font-semibold text-sm uppercase tracking-wide"
                        >
                            {{
                                activeTab === "pending"
                                    ? "Total Facturas Pendientes"
                                    : "Total Facturas Conciliadas"
                            }}
                        </span>
                        <span class="text-3xl font-bold text-indigo-600 mt-1">
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
                        class="bg-white p-4 rounded-lg shadow-sm border border-green-100 flex flex-col"
                    >
                        <span
                            class="text-green-900 font-semibold text-sm uppercase tracking-wide"
                        >
                            {{
                                activeTab === "pending"
                                    ? "Total Pagos Pendientes"
                                    : "Total Pagos Conciliados"
                            }}
                        </span>
                        <span class="text-3xl font-bold text-green-600 mt-1">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Invoices Column -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-[600px]"
                    >
                        <div
                            class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50"
                        >
                            <h3 class="text-lg font-bold text-gray-700">
                                Facturas
                                {{
                                    activeTab === "pending"
                                        ? "Pendientes"
                                        : "Conciliadas"
                                }}
                            </h3>
                            <span
                                class="bg-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold"
                            >
                                {{
                                    (activeTab === "pending"
                                        ? pendingInvoices
                                        : conciliatedInvoices
                                    ).length
                                }}
                                items
                            </span>
                        </div>
                        <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50">
                            <div class="space-y-3">
                                <div
                                    v-for="invoice in activeTab === 'pending'
                                        ? pendingInvoices
                                        : conciliatedInvoices"
                                    :key="invoice.id"
                                    class="p-4 border border-gray-200 rounded-lg bg-white shadow-sm hover:shadow-md transition"
                                >
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <div class="w-2/3">
                                            <div
                                                class="font-bold text-gray-800 truncate"
                                                :title="invoice.nombre"
                                            >
                                                {{ invoice.nombre }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 font-mono mt-1 truncate"
                                                :title="invoice.uuid"
                                            >
                                                {{ invoice.uuid }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-400 mt-1 flex items-center"
                                            >
                                                <svg
                                                    class="w-3 h-3 mr-1"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                    ></path>
                                                </svg>
                                                {{
                                                    formatDate(
                                                        invoice.fecha_emision,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            class="font-mono font-bold text-indigo-700 text-lg"
                                        >
                                            {{
                                                formatCurrency(
                                                    Number(invoice.monto),
                                                )
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="activeTab === 'conciliated'"
                                        class="mt-3 pt-3 border-t border-gray-100 text-xs text-green-600 flex flex-col gap-1"
                                    >
                                        <div
                                            class="flex items-center font-semibold"
                                        >
                                            <svg
                                                class="w-3 h-3 mr-1"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                ></path>
                                            </svg>
                                            Conciliado:
                                            {{
                                                formatDate(
                                                    invoice.conciliaciones[0]
                                                        ?.created_at,
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="flex items-center text-gray-500 pl-4"
                                        >
                                            Por:
                                            {{
                                                invoice.conciliaciones[0]?.user
                                                    ?.name || "Sistema"
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        (activeTab === 'pending'
                                            ? pendingInvoices
                                            : conciliatedInvoices
                                        ).length === 0
                                    "
                                    class="flex flex-col items-center justify-center h-full text-gray-500 italic text-sm py-12"
                                >
                                    <svg
                                        class="w-12 h-12 text-gray-300 mb-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                        ></path>
                                    </svg>
                                    No hay facturas en esta lista.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Movements Column -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-[600px]"
                    >
                        <div
                            class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50"
                        >
                            <h3 class="text-lg font-bold text-gray-700">
                                Movimientos
                                {{
                                    activeTab === "pending"
                                        ? "Pendientes"
                                        : "Conciliados"
                                }}
                            </h3>
                            <span
                                class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs font-bold"
                            >
                                {{
                                    (activeTab === "pending"
                                        ? pendingMovements
                                        : conciliatedMovements
                                    ).length
                                }}
                                items
                            </span>
                        </div>
                        <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50">
                            <div class="space-y-3">
                                <div
                                    v-for="movement in activeTab === 'pending'
                                        ? pendingMovements
                                        : conciliatedMovements"
                                    :key="movement.id"
                                    class="p-4 border border-gray-200 rounded-lg bg-white shadow-sm hover:shadow-md transition"
                                >
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <div class="w-2/3">
                                            <div
                                                class="font-bold text-gray-800 truncate"
                                                :title="movement.descripcion"
                                            >
                                                {{ movement.descripcion }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 mt-1 flex items-center"
                                            >
                                                <svg
                                                    class="w-3 h-3 mr-1"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                    ></path>
                                                </svg>
                                                {{ formatDate(movement.fecha) }}
                                            </div>
                                            <div class="mt-2">
                                                <span
                                                    class="text-xs badge bg-gray-100 border border-gray-200 text-gray-600 px-2 py-0.5 rounded-full inline-block"
                                                    >{{ movement.tipo }}</span
                                                >
                                            </div>
                                        </div>
                                        <div
                                            class="font-mono font-bold text-green-700 text-lg"
                                        >
                                            {{
                                                formatCurrency(
                                                    Number(movement.monto),
                                                )
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="activeTab === 'conciliated'"
                                        class="mt-3 pt-3 border-t border-gray-100 text-xs text-green-600 flex flex-col gap-1"
                                    >
                                        <div
                                            class="flex items-center font-semibold"
                                        >
                                            <svg
                                                class="w-3 h-3 mr-1"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                ></path>
                                            </svg>
                                            Conciliado:
                                            {{
                                                formatDate(
                                                    movement.conciliaciones[0]
                                                        ?.created_at,
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="flex items-center text-gray-500 pl-4"
                                        >
                                            Por:
                                            {{
                                                movement.conciliaciones[0]?.user
                                                    ?.name || "Sistema"
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        (activeTab === 'pending'
                                            ? pendingMovements
                                            : conciliatedMovements
                                        ).length === 0
                                    "
                                    class="flex flex-col items-center justify-center h-full text-gray-500 italic text-sm py-12"
                                >
                                    <svg
                                        class="w-12 h-12 text-gray-300 mb-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    No hay movimientos en esta lista.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
