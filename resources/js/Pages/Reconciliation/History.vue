<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";

const props = defineProps<{
    reconciledGroups: {
        data: Array<{
            id: number;
            uuid: string;
            nombre: string;
            rfc: string;
            monto: number;
            fecha_emision: string;
            conciliaciones: Array<{
                id: number;
                monto_aplicado: number;
                created_at: string;
                user: { name: string };
                movimiento: {
                    id: number;
                    descripcion: string;
                    fecha: string;
                    tipo: string;
                    monto: number;
                };
            }>;
        }>;
        links: Array<any>;
    };
    filters?: {
        search?: string;
    };
}>();

const search = ref(props.filters?.search || "");

watch(
    search,
    debounce((value: string) => {
        router.get(
            route("reconciliation.history"),
            { search: value },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300),
);

const formatDate = (dateString: string) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString("es-ES", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
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
    <Head title="Historial de Conciliaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Historial de Conciliaciones
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <div class="relative w-full max-w-md">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <svg
                                class="h-5 w-5 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Buscar por factura, RFC o monto..."
                        />
                    </div>
                </div>

                <div
                    v-if="reconciledGroups.data.length === 0"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500"
                >
                    No hay conciliaciones registradas aún.
                </div>

                <div v-else class="space-y-8">
                    <div
                        v-for="invoice in reconciledGroups.data"
                        :key="invoice.id"
                        class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200"
                    >
                        <!-- Invoice Header -->
                        <div
                            class="bg-indigo-50/50 px-6 py-4 border-b border-indigo-100 flex flex-col md:flex-row justify-between md:items-center gap-4"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="bg-indigo-600 text-white px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wide"
                                        >Factura</span
                                    >
                                    <h3
                                        class="text-lg font-bold text-gray-900 border-b border-dashed border-gray-400 inline-block"
                                        :title="invoice.nombre"
                                    >
                                        {{
                                            invoice.nombre ||
                                            "Factura Eliminada"
                                        }}
                                    </h3>
                                </div>
                                <div
                                    class="text-sm text-gray-500 mt-1 font-mono"
                                >
                                    {{ invoice.uuid }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">
                                    Monto Factura
                                </div>
                                <div class="text-xl font-bold text-indigo-700">
                                    {{ formatCurrency(Number(invoice.monto)) }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ formatDate(invoice.fecha_emision) }}
                                </div>
                            </div>
                        </div>

                        <!-- Associated Payments -->
                        <div class="p-6">
                            <h4
                                class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2"
                            >
                                Pagos Asociados ({{
                                    invoice.conciliaciones.length
                                }})
                            </h4>

                            <div class="space-y-4">
                                <div
                                    v-for="conciliacion in invoice.conciliaciones"
                                    :key="conciliacion.id"
                                    class="flex flex-col sm:flex-row items-center justify-between p-4 rounded-lg bg-gray-50 border border-gray-100 hover:border-gray-300 transition-colors"
                                >
                                    <div
                                        class="flex items-start gap-4 w-full sm:w-auto"
                                    >
                                        <div
                                            class="p-2 bg-green-100 text-green-600 rounded-full shrink-0"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    conciliacion.movimiento
                                                        ?.descripcion ||
                                                    "Movimiento Eliminado"
                                                }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 mt-0.5"
                                            >
                                                Conciliado por:
                                                <span
                                                    class="font-medium text-gray-700"
                                                    >{{
                                                        conciliacion.user
                                                            ?.name ||
                                                        "Desconocido"
                                                    }}</span
                                                >
                                                •
                                                {{
                                                    formatDate(
                                                        conciliacion.created_at,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-4 sm:mt-0 text-right w-full sm:w-auto pl-14 sm:pl-0"
                                    >
                                        <div
                                            class="text-xs text-gray-500 mb-0.5"
                                        >
                                            Monto Conciliado
                                        </div>
                                        <div
                                            class="font-bold text-green-600 text-lg"
                                        >
                                            {{
                                                formatCurrency(
                                                    Number(
                                                        conciliacion.monto_aplicado,
                                                    ),
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    class="mt-6 flex justify-center space-x-1 flex-wrap"
                    v-if="reconciledGroups.links.length > 3"
                >
                    <template
                        v-for="(link, key) in reconciledGroups.links"
                        :key="key"
                    >
                        <div
                            v-if="link.url === null"
                            class="px-3 py-1 border rounded text-sm text-gray-400 mb-1"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            class="px-3 py-1 border rounded text-sm hover:bg-gray-100 mb-1"
                            :class="{
                                'bg-indigo-50 border-indigo-500 text-indigo-700':
                                    link.active,
                            }"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
