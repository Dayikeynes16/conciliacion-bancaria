<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { debounce } from "lodash";

const props = defineProps<{
    files: {
        data: Array<{
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
        links: Array<{
            url?: string;
            label: string;
            active: boolean;
        }>;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters?: {
        search?: string;
        date?: string;
        sort?: string;
        direction?: string;
    };
}>();

const search = ref(props.filters?.search || "");
const dateFilter = ref(props.filters?.date || "");
const sortColumn = ref(props.filters?.sort || "created_at");
const sortDirection = ref(props.filters?.direction || "desc");

const updateParams = debounce(() => {
    router.get(
        route("invoices.index"),
        {
            search: search.value,
            date: dateFilter.value,
            sort: sortColumn.value,
            direction: sortDirection.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(search, updateParams);
watch(dateFilter, updateParams);

const sort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortColumn.value = column;
        sortDirection.value = "desc";
    }
    updateParams();
};

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
import Checkbox from "@/Components/Checkbox.vue";
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const confirmingFileDeletion = ref(false);
const fileIdToDelete = ref<number | null>(null);
const confirmingBatchDeletion = ref(false);
const selectedIds = ref<number[]>([]);
const form = useForm({});
const batchForm = useForm({
    ids: [] as number[],
});

const selectAll = computed({
    get: () => props.files.data.length > 0 && selectedIds.value.length === props.files.data.length,
    set: (val) => {
        if (val) {
            selectedIds.value = props.files.data.map((f) => f.id);
        } else {
            selectedIds.value = [];
        }
    },
});

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
};

const confirmFileDeletion = (id: number) => {
    fileIdToDelete.value = id;
    confirmingFileDeletion.value = true;
};

const confirmBatchDeletion = () => {
    confirmingBatchDeletion.value = true;
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

const deleteBatch = () => {
    batchForm.ids = selectedIds.value;
    batchForm.post(route("invoices.batch-destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            selectedIds.value = [];
        },
        onFinish: () => batchForm.reset(),
    });
};

const closeModal = () => {
    confirmingFileDeletion.value = false;
    confirmingBatchDeletion.value = false;
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
                                        >{{ files.total }} facturas</span
                                    >
                                    <span class="mx-2">|</span>
                                    Monto Total (Página):
                                    <span
                                        class="font-semibold text-green-600 dark:text-green-400"
                                        >{{
                                            formatCurrency(
                                                files.data.reduce(
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

                            <!-- Actions -->
                            <div class="flex items-center gap-4 w-full md:w-auto">
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <button
                                        v-if="selectedIds.length > 0"
                                        @click="confirmBatchDeletion"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                    >
                                        Eliminar ({{ selectedIds.length }})
                                    </button>
                                </Transition>

                                <!-- Search Field -->
                                <div class="relative w-full md:w-64">
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
                                <div class="w-full md:w-auto">
                                    <input
                                        type="date"
                                        v-model="dateFilter"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="files.data.length === 0"
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
                                        <th scope="col" class="p-4 w-4">
                                            <div class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    v-model="selectAll"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                                />
                                            </div>
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            ID
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Receptor (RFC)
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Nombre
                                        </th>
                                        <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sort('total')">
                                            <div class="flex items-center gap-1">
                                                Total
                                                <span v-if="sortColumn === 'total'">
                                                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                                </span>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sort('fecha_emision')">
                                            <div class="flex items-center gap-1">
                                                Fecha Emisión
                                                <span v-if="sortColumn === 'fecha_emision'">
                                                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                                </span>
                                            </div>
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
                                        v-for="file in files.data"
                                        :key="file.id"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        <td class="p-4 w-4">
                                            <div class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    :value="file.id"
                                                    v-model="selectedIds"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                                />
                                            </div>
                                        </td>
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
                        <!-- Pagination -->
                        <div class="mt-4" v-if="files.links.length > 3">
                            <div class="flex flex-wrap -mb-1">
                                <template v-for="(link, key) in files.links" :key="key">
                                    <div
                                        v-if="link.url === null"
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                                        v-html="link.label"
                                    />
                                    <Link
                                        v-else
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                                        :class="{ 'bg-blue-700 text-white dark:bg-blue-600': link.active }"
                                        :href="link.url"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
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

        <ConfirmationModal :show="confirmingBatchDeletion" @close="closeModal">
            <template #title> Eliminar Facturas Seleccionadas </template>

            <template #content>
                ¿Estás seguro de que deseas eliminar las
                {{ selectedIds.length }} facturas seleccionadas? Esta acción
                no se puede deshacer.
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500"
                    :class="{ 'opacity-25': batchForm.processing }"
                    :disabled="batchForm.processing"
                    @click="deleteBatch"
                >
                    Eliminar Todo
                </PrimaryButton>
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>
