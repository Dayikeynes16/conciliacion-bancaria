<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, router, useForm, Link } from "@inertiajs/vue3";
import { ref, computed, watch, reactive } from "vue";
import axios from "axios";
import { debounce } from "lodash";

const props = defineProps<{
    files: Array<{
        id: number;
        path: string;
        original_name?: string;
        created_at: string;
        banco?: { nombre: string };
        movimientos_count: number;
    }>;
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
    filters?: {
        month?: string;
        year?: string;
        date?: string;
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
    };
}>();

const viewMode = ref("files"); // 'files' | 'movements'
const showModal = ref(false);
const selectedFile = ref<any>(null);
const dragging = ref(false);
const fileMovements = ref<any[]>([]);
const isLoading = ref(false);


const form = useForm({
     file: null as File | null,
     bank_format_id: null as number | null,
});

// Filters Logic for Movements Tab
const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    amount_min: props.filters?.amount_min || "",
    amount_max: props.filters?.amount_max || "",
});

const activeTab = ref("all"); // 'all', 'abono', 'cargo'

const applyFilters = () => {
    router.get(
        route("movements.index"),
        {
            date_from: filterForm.date_from,
            date_to: filterForm.date_to,
            amount_min: filterForm.amount_min,
            amount_max: filterForm.amount_max,
            month: props.filters?.month, // Keep sidebar context
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

// Format with timezone fix but NO TIME
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
// Removed duplicate form declaration
const batchForm = useForm({ ids: [] as number[] });

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
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Movimientos Bancarios
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Page Tabs -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                        <li class="mr-2">
                            <a href="#" 
                               @click.prevent="viewMode = 'files'"
                               :class="viewMode === 'files' ? 'inline-block p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active dark:text-blue-500 dark:border-blue-500' : 'inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'">
                                Archivos Cargados
                            </a>
                        </li>
                        <li class="mr-2">
                            <a href="#" 
                               @click.prevent="viewMode = 'movements'"
                               :class="viewMode === 'movements' ? 'inline-block p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active dark:text-blue-500 dark:border-blue-500' : 'inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'">
                                Todos los Movimientos
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        
                        <!-- Header & Actions -->
                        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <h3 class="text-lg font-medium">
                                {{ viewMode === 'files' ? 'Archivos de Movimientos Cargados' : 'Listado General de Movimientos' }}
                            </h3>

                            <div class="flex items-center gap-4">
                                <!-- Batch Delete Button (Only for Files view) -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <button
                                        v-if="viewMode === 'files' && selectedIds.length > 0"
                                        @click="confirmBatchDeletion"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Eliminar ({{ selectedIds.length }})
                                    </button>
                                </Transition>
                            </div>
                        </div>
                    
                    <!-- Filters (Only for Movements Tab) -->
                    <div v-if="viewMode === 'movements'" class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Filtros de Búsqueda</h3>
                        </div>
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

                    <!-- Files View -->
                    <div v-if="viewMode === 'files'">


                        <div v-if="files.length === 0" class="text-center py-8 text-gray-500">
                             No se han cargado archivos de movimientos aún.
                        </div>

                            <div v-else class="overflow-x-auto relative">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="p-4 w-4">
                                                <div class="flex items-center">
                                                    <input type="checkbox" v-model="selectAll" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" />
                                                </div>
                                            </th>
                                            <th scope="col" class="py-3 px-6">ID</th>
                                            <th scope="col" class="py-3 px-6">Banco</th>
                                            <th scope="col" class="py-3 px-6">Archivo</th>
                                            <th scope="col" class="py-3 px-6">Movimientos</th>
                                            <th scope="col" class="py-3 px-6">Fecha de Carga</th>
                                            <th scope="col" class="py-3 px-6">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="file in files" :key="file.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" @click="viewDetails(file)">
                                            <td class="p-4 w-4" @click.stop>
                                                <div class="flex items-center">
                                                    <input type="checkbox" :value="file.id" v-model="selectedIds" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" />
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">{{ file.id }}</td>
                                            <td class="py-4 px-6">
                                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 border border-blue-400">
                                                    {{ file.banco?.nombre || "Desconocido" }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-6 truncate max-w-xs" :title="file.original_name || file.path">
                                                {{ file.original_name || file.path.split("/").pop() }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="bg-gray-100 text-gray-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                                                    {{ file.movimientos_count }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">{{ formatDate(file.created_at) }}</td>
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-4">
                                                    <button @click.stop="viewDetails(file)" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Ver Detalle</button>
                                                    <button @click.stop="confirmDeleteFile(file)" class="font-medium text-red-600 dark:text-red-500 hover:underline">Eliminar</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- MOVEMENTS VIEW -->
                        <div v-else-if="viewMode === 'movements'">
                             <div v-if="movements.data.length === 0" class="text-center py-8 text-gray-500">
                                No se encontraron movimientos en este periodo.
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto relative">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th class="py-3 px-6">Banco</th>
                                                <th class="py-3 px-6">Fecha</th>
                                                <th class="py-3 px-6">Descripción</th>
                                                <th class="py-3 px-6">Tipo</th>
                                                <th class="py-3 px-6">Estado</th>
                                                <th class="py-3 px-6 text-right">Monto</th>
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
                                                        {{ mov.tipo === "abono" ? "Abono" : "Cargo" }}
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span v-if="mov.conciliaciones_count && mov.conciliaciones_count > 0" class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-green-200 dark:text-green-900 border border-green-400">
                                                        Conciliado
                                                    </span>
                                                    <span v-else class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-yellow-200 dark:text-yellow-900 border border-yellow-400">
                                                        Pendiente
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

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
             <!-- Modal Content Unchanged -->
             <div class="p-6">
                <!-- ... (Modal content for viewing file details) ... -->
                <!-- Copying existing modal content structure roughly to ensure it works, 
                     but since use replace_file_content I need to be careful not to delete it unless I include it.
                     Wait, I am replacing the whole template. I should keep the modal content.
                     I will copy the Modal content from the previous `view_file` output.
                -->
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Detalle de Movimientos</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" v-if="selectedFile">
                            Archivo: {{ selectedFile.original_name || selectedFile.path.split("/").pop() }}
                        </p>
                    </div>
                    <div class="text-right text-sm" v-if="!isLoading && fileMovements.length > 0">
                        <div class="text-green-600">Abonos: {{ formatCurrency(totalAbonos) }}</div>
                        <div class="text-red-600">Cargos: {{ formatCurrency(totalCargos) }}</div>
                    </div>
                </div>

                <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                        <li class="mr-2"><a href="#" @click.prevent="activeTab = 'all'" :class="activeTab === 'all' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'" class="inline-block p-4 rounded-t-lg">Todos</a></li>
                        <li class="mr-2"><a href="#" @click.prevent="activeTab = 'abono'" :class="activeTab === 'abono' ? 'text-green-600 border-b-2 border-green-600' : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'" class="inline-block p-4 rounded-t-lg">Abonos</a></li>
                        <li class="mr-2"><a href="#" @click.prevent="activeTab = 'cargo'" :class="activeTab === 'cargo' ? 'text-red-600 border-b-2 border-red-600' : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'" class="inline-block p-4 rounded-t-lg">Cargos</a></li>
                    </ul>
                </div>

                <div class="mt-6">
                    <div v-if="isLoading" class="text-center py-4">
                        <!-- Spinner -->
                         <svg class="animate-spin h-5 w-5 mx-auto text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="mt-2 block text-sm text-gray-500">Cargando movimientos...</span>
                    </div>
                    <div v-else-if="filteredMovements.length === 0" class="text-center py-4 text-gray-500">No hay movimientos de este tipo para mostrar.</div>
                    <div v-else class="overflow-x-auto max-h-[60vh] overflow-y-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                             <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                                <tr>
                                    <th class="py-2 px-4">Fecha</th>
                                    <th class="py-2 px-4">Descripción</th>
                                    <th class="py-2 px-4">Tipo</th>
                                    <th class="py-2 px-4">Estado</th>
                                    <th class="py-2 px-4 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="mov in filteredMovements" :key="mov.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="py-2 px-4 whitespace-nowrap">{{ formatDateNoTime(mov.fecha) }}</td>
                                    <td class="py-2 px-4">{{ mov.descripcion }}</td>
                                     <td class="py-2 px-4">
                                        <span :class="mov.tipo === 'abono' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="text-xs font-medium px-2.5 py-0.5 rounded">{{ mov.tipo === "abono" ? "Abono" : "Cargo" }}</span>
                                    </td>
                                    <td class="py-2 px-4">
                                        <span v-if="mov.conciliaciones_count > 0" class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-400">Conciliado</span>
                                        <span v-else class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-gray-400">Pendiente</span>
                                    </td>
                                    <td class="py-2 px-4 text-right font-mono" :class="mov.tipo === 'cargo' ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(Number(mov.monto)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                 <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cerrar</SecondaryButton>
                </div>
            </div>
        </Modal>

        <ConfirmationModal :show="confirmingFileDeletion" @close="closeModal">
             <template #title> Eliminar Archivo de Movimientos </template>
            <template #content>
                ¿Estás seguro de que deseas eliminar este archivo? Se eliminarán todos los movimientos bancarios asociados permanentemente. Esta acción no se puede deshacer.
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteFileConfirmed">Eliminar</PrimaryButton>
            </template>
        </ConfirmationModal>

        <ConfirmationModal :show="confirmingBatchDeletion" @close="closeModal">
            <template #title> Eliminar Archivos Seleccionados </template>
            <template #content>
                 ¿Estás seguro de que deseas eliminar los {{ selectedIds.length }} archivos seleccionados? Se eliminarán todos los movimientos asociados permanentemente.
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500" :class="{ 'opacity-25': batchForm.processing }" :disabled="batchForm.processing" @click="deleteBatch">Eliminar Todo</PrimaryButton>
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>
