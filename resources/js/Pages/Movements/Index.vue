<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import axios from "axios";



const selectedFile = ref<any>(null);
const fileMovements = ref<any[]>([]);
const showModal = ref(false);
const isLoading = ref(false);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString("es-MX", {
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

const activeTab = ref("all"); // 'all', 'abono', 'cargo'

const viewDetails = async (file: any) => {
    selectedFile.value = file;
    showModal.value = true;
    isLoading.value = true;
    fileMovements.value = [];
    activeTab.value = "all";

    try {
        const response = await axios.get(route("movements.show", file.id));
        fileMovements.value = response.data;
    } catch (error) {
        console.error("Error fetching movements", error);
    } finally {
        isLoading.value = false;
    }
};

// closeModal moved to bottom to handle both states



const filteredMovements = computed(() => {
    if (activeTab.value === "all") return fileMovements.value;
    return fileMovements.value.filter((m) => m.tipo === activeTab.value);
});

const totalAbonos = computed(() => {
    return fileMovements.value
        .filter((m) => m.tipo === "abono")
        .reduce((sum, m) => sum + Number(m.monto), 0);
});

const totalCargos = computed(() => {
    return fileMovements.value
        .filter((m) => m.tipo === "cargo")
        .reduce((sum, m) => sum + Number(m.monto), 0);
});

const confirmingFileDeletion = ref(false);
const fileIdToDelete = ref<number | null>(null);
const confirmingBatchDeletion = ref(false);
const selectedIds = ref<number[]>([]);
const form = useForm({});
const batchForm = useForm({ ids: [] as number[] });

// Date Filter Logic
const props = defineProps<{
    files: Array<{
        id: number;
        path: string;
        original_name?: string;
        created_at: string;
        banco?: { nombre: string };
        movimientos_count: number;
    }>;
    filters?: {
        month?: string;
        year?: string;
        date?: string;
    };
}>();

const dateFilter = ref(props.filters?.date || "");
import { debounce } from "lodash";

watch(
    dateFilter,
    debounce((value) => {
        router.get(
            route("movements.index"),
            { date: value }, // Filter by exact date
            { preserveState: true, replace: true }
        );
    }, 300)
);

import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const selectAll = computed({
    get: () => props.files.length > 0 && selectedIds.value.length === props.files.length,
    set: (val) => {
        if (val) {
            selectedIds.value = props.files.map((f) => f.id);
        } else {
            selectedIds.value = [];
        }
    },
});

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
};

const confirmDeleteFile = (file: { id: number }) => {
    fileIdToDelete.value = file.id;
    confirmingFileDeletion.value = true;
};

const confirmBatchDeletion = () => {
    confirmingBatchDeletion.value = true;
};

const deleteBatch = () => {
    batchForm.ids = selectedIds.value;
    batchForm.post(route("movements.batch-destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            selectedIds.value = [];
        },
        onFinish: () => batchForm.reset(),
    });
};

const deleteFileConfirmed = () => {
    if (!fileIdToDelete.value) return;

    form.delete(route("movements.destroy", fileIdToDelete.value), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => (fileIdToDelete.value = null),
        onFinish: () => form.reset(),
    });
};

// Override existing closeModal to also handle deletion modal
const closeModal = () => {
    showModal.value = false;
    selectedFile.value = null;
    fileMovements.value = [];
    confirmingFileDeletion.value = false;
    confirmingBatchDeletion.value = false;
    fileIdToDelete.value = null;
    form.reset();
};
</script>

<template>
    <Head title="Movimientos Bancarios" />

    <AuthenticatedLayout>
        <!-- ... existing content ... -->
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Movimientos Bancarios
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <h3 class="text-lg font-medium">
                                Archivos de Movimientos Cargados
                            </h3>

                            <div class="flex items-center gap-4">
                                <!-- Batch Delete Button -->
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

                                <!-- Date Filter -->
                                <div class="flex items-center gap-2">
                                    <label for="date-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Filtrar por Fecha:
                                    </label>
                                    <input
                                        id="date-filter"
                                        type="date"
                                        v-model="dateFilter"
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    />
                                    <button
                                        v-if="dateFilter"
                                        @click="dateFilter = ''"
                                        class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    >
                                        Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="files.length === 0"
                            class="text-center py-8 text-gray-500"
                        >
                            No se han cargado archivos de movimientos aún.
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
                                            Banco
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Archivo
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Movimientos
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Fecha de Carga
                                        </th>
                                        <th scope="col" class="py-3 px-6">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="file in files"
                                        :key="file.id"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                        @click="viewDetails(file)"
                                    >
                                        <td class="p-4 w-4" @click.stop>
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
                                            <span
                                                class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 border border-blue-400"
                                            >
                                                {{
                                                    file.banco?.nombre ||
                                                    "Desconocido"
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-4 px-6 truncate max-w-xs"
                                            :title="file.original_name || file.path"
                                        >
                                            {{ file.original_name || file.path.split("/").pop() }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <span
                                                class="bg-gray-100 text-gray-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ file.movimientos_count }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ formatDate(file.created_at) }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div
                                                class="flex items-center gap-4"
                                            >
                                                <button
                                                    @click.stop="
                                                        viewDetails(file)
                                                    "
                                                    class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                                >
                                                    Ver Detalle
                                                </button>
                                                <button
                                                    @click.stop="
                                                        confirmDeleteFile(file)
                                                    "
                                                    class="font-medium text-red-600 dark:text-red-500 hover:underline"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2
                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                        >
                            Detalle de Movimientos
                        </h2>
                        <p
                            class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                            v-if="selectedFile"
                        >
                            Archivo: {{ selectedFile.original_name || selectedFile.path.split("/").pop() }}
                        </p>
                    </div>
                    <div
                        class="text-right text-sm"
                        v-if="!isLoading && fileMovements.length > 0"
                    >
                        <div class="text-green-600">
                            Abonos: {{ formatCurrency(totalAbonos) }}
                        </div>
                        <div class="text-red-600">
                            Cargos: {{ formatCurrency(totalCargos) }}
                        </div>
                    </div>
                </div>

                <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                    <ul
                        class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400"
                    >
                        <li class="mr-2">
                            <a
                                href="#"
                                @click.prevent="activeTab = 'all'"
                                :class="
                                    activeTab === 'all'
                                        ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-500 dark:border-blue-500'
                                        : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                                "
                                class="inline-block p-4 rounded-t-lg"
                            >
                                Todos
                            </a>
                        </li>
                        <li class="mr-2">
                            <a
                                href="#"
                                @click.prevent="activeTab = 'abono'"
                                :class="
                                    activeTab === 'abono'
                                        ? 'text-green-600 border-b-2 border-green-600'
                                        : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                                "
                                class="inline-block p-4 rounded-t-lg"
                            >
                                Abonos
                            </a>
                        </li>
                        <li class="mr-2">
                            <a
                                href="#"
                                @click.prevent="activeTab = 'cargo'"
                                :class="
                                    activeTab === 'cargo'
                                        ? 'text-red-600 border-b-2 border-red-600'
                                        : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                                "
                                class="inline-block p-4 rounded-t-lg"
                            >
                                Cargos
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="mt-6">
                    <div v-if="isLoading" class="text-center py-4">
                        <svg
                            class="animate-spin h-5 w-5 mx-auto text-gray-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span class="mt-2 block text-sm text-gray-500"
                            >Cargando movimientos...</span
                        >
                    </div>

                    <div
                        v-else-if="filteredMovements.length === 0"
                        class="text-center py-4 text-gray-500"
                    >
                        No hay movimientos de este tipo para mostrar.
                    </div>

                    <div
                        v-else
                        class="overflow-x-auto max-h-[60vh] overflow-y-auto"
                    >
                        <table
                            class="w-full text-sm text-left text-gray-500 dark:text-gray-400"
                        >
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0"
                            >
                                <tr>
                                    <th class="py-2 px-4">Fecha</th>
                                    <th class="py-2 px-4">Descripción</th>
                                    <th class="py-2 px-4">Tipo</th>
                                    <th class="py-2 px-4">Estado</th>
                                    <th class="py-2 px-4 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="mov in filteredMovements"
                                    :key="mov.id"
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                                >
                                    <td class="py-2 px-4 whitespace-nowrap">
                                        {{
                                            new Date(
                                                mov.fecha,
                                            ).toLocaleDateString("es-MX")
                                        }}
                                    </td>
                                    <td class="py-2 px-4">
                                        {{ mov.descripcion }}
                                    </td>
                                    <td class="py-2 px-4">
                                        <span
                                            :class="
                                                mov.tipo === 'abono'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            "
                                            class="text-xs font-medium px-2.5 py-0.5 rounded"
                                        >
                                            {{
                                                mov.tipo === "abono"
                                                    ? "Abono"
                                                    : "Cargo"
                                            }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4">
                                        <span
                                            v-if="
                                                mov.conciliaciones_count &&
                                                mov.conciliaciones_count > 0
                                            "
                                            class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-green-200 dark:text-green-900 border border-green-400"
                                        >
                                            Conciliado
                                        </span>
                                        <span
                                            v-else
                                            class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-gray-200 dark:text-gray-900 border border-gray-400"
                                        >
                                            Pendiente
                                        </span>
                                    </td>
                                    <td
                                        class="py-2 px-4 text-right font-mono"
                                        :class="
                                            mov.tipo === 'cargo'
                                                ? 'text-red-600'
                                                : 'text-green-600'
                                        "
                                    >
                                        {{ formatCurrency(Number(mov.monto)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Cerrar
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
        <ConfirmationModal :show="confirmingFileDeletion" @close="closeModal">
            <template #title> Eliminar Archivo de Movimientos </template>

            <template #content>
                ¿Estás seguro de que deseas eliminar este archivo? Se eliminarán
                todos los movimientos bancarios asociados permanentemente. Esta
                acción no se puede deshacer.
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteFileConfirmed"
                >
                    Eliminar
                </PrimaryButton>
            </template>
        </ConfirmationModal>

        <ConfirmationModal :show="confirmingBatchDeletion" @close="closeModal">
            <template #title> Eliminar Archivos Seleccionados </template>

            <template #content>
                ¿Estás seguro de que deseas eliminar los
                {{ selectedIds.length }} archivos seleccionados? Se eliminarán
                todos los movimientos asociados permanentemente.
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
