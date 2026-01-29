<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { debounce } from "lodash";

const props = defineProps<{
    files: Array<{
        id: number;
        path: string;
        created_at: string;
        factura?: {
            uuid: string;
            monto: number;
            rfc: string;
            nombre: string;
            fecha_emision: string;
            conciliaciones_count?: number;
            conciliaciones?: Array<{
                id: number;
                user?: {
                    name: string;
                };
            }>;
        };
    }>;
    filters?: {
        search?: string;
    };
}>();

const search = ref(props.filters?.search || "");

watch(
    search,
    debounce((value) => {
        router.get(
            route("invoices.index"),
            { search: value },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300),
);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString("es-MX", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatSemDate = (date?: string) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-MX");
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
    }).format(amount);
};

import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { useForm } from "@inertiajs/vue3";

const confirmingFileDeletion = ref(false);
const fileIdToDelete = ref<number | null>(null);
const form = useForm({});

const confirmFileDeletion = (id: number) => {
    fileIdToDelete.value = id;
    confirmingFileDeletion.value = true;
};

const deleteFile = () => {
    if (!fileIdToDelete.value) return;

    form.delete(route("invoices.destroy", fileIdToDelete.value), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => (fileIdToDelete.value = null),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingFileDeletion.value = false;
    fileIdToDelete.value = null;
    form.reset();
};
</script>

<template>
    <Head title="Facturas" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Facturas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div
                            class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4"
                        >
                            <div>
                                <h3
                                    class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                >
                                    Facturas Cargadas
                                </h3>
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                                >
                                    Total:
                                    <span
                                        class="font-semibold text-gray-700 dark:text-gray-200"
                                        >{{ files.length }} facturas</span
                                    >
                                    <span class="mx-2">|</span>
                                    Monto Total:
                                    <span
                                        class="font-semibold text-green-600 dark:text-green-400"
                                        >{{
                                            formatCurrency(
                                                files.reduce(
                                                    (sum, file) =>
                                                        sum +
                                                        Number(
                                                            file.factura
                                                                ?.monto || 0,
                                                        ),
                                                    0,
                                                ),
                                            )
                                        }}</span
                                    >
                                </p>
                            </div>

                            <!-- Search Field -->
                            <div class="w-full md:w-1/3">
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                                    >
                                        <svg
                                            class="w-4 h-4 text-gray-500 dark:text-gray-400"
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
                                        class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="Buscar por nombre, RFC o monto..."
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="files.length === 0"
                            class="text-center py-8 text-gray-500"
                        >
                            No se han cargado facturas aún.
                        </div>

                        <div v-else class="overflow-x-auto relative">
                            <table
                                class="w-full text-sm text-left text-gray-500 dark:text-gray-400"
                            >
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
                                >
                                    <tr>
                                        <th scope="col" class="py-3 px-6">
                                            ID
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Receptor (RFC)
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Nombre
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Monto
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Fecha Emisión
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Estado
                                        </th>

                                        <th
                                            scope="col"
                                            class="py-3 px-6 text-right"
                                        >
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="file in files"
                                        :key="file.id"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <td class="py-4 px-6">{{ file.id }}</td>
                                        <td class="py-4 px-6">
                                            {{ file.factura?.rfc || "N/A" }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ file.factura?.nombre || "N/A" }}
                                        </td>
                                        <td
                                            class="py-4 px-6 font-mono font-medium"
                                        >
                                            {{
                                                file.factura?.monto
                                                    ? formatCurrency(
                                                          Number(
                                                              file.factura
                                                                  .monto,
                                                          ),
                                                      )
                                                    : "N/A"
                                            }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{
                                                formatSemDate(
                                                    file.factura?.fecha_emision,
                                                )
                                            }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <span
                                                v-if="
                                                    file.factura
                                                        ?.conciliaciones_count &&
                                                    file.factura
                                                        .conciliaciones_count >
                                                        0
                                                "
                                                class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-green-200 dark:text-green-900 border border-green-400"
                                            >
                                                Conciliado
                                            </span>

                                            <span
                                                v-else
                                                class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-yellow-200 dark:text-yellow-900 border border-yellow-400"
                                            >
                                                Pendiente
                                            </span>
                                        </td>

                                        <td class="py-4 px-6 text-right">
                                            <button
                                                @click="
                                                    confirmFileDeletion(file.id)
                                                "
                                                class="font-medium text-red-600 dark:text-red-500 hover:underline"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmationModal :show="confirmingFileDeletion" @close="closeModal">
            <template #title> Eliminar Factura </template>

            <template #content>
                ¿Estás seguro de que deseas eliminar esta factura? Esta acción
                eliminará el archivo y todos los registros asociados
                permanentemente.
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteFile"
                >
                    Eliminar
                </PrimaryButton>
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>
