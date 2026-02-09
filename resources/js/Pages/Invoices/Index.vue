<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InvoiceFilters from "@/Pages/Reconciliation/Partials/InvoiceFilters.vue";
import InvoiceTable from "@/Pages/Reconciliation/Partials/InvoiceTable.vue";

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
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
        sort?: string;
        direction?: string;
        per_page?: string | number;
    };
}>();

const sortColumn = ref(props.filters?.sort || "created_at");
const sortDirection = ref(props.filters?.direction || "desc");
const perPage = ref(props.filters?.per_page || 10);

const updateParams = (filters: any) => {
    router.get(
        route("invoices.index"),
        {
            search: filters.search,
            date: filters.date,
            date_from: filters.date_from,
            date_to: filters.date_to,
            amount_min: filters.amount_min,
            amount_max: filters.amount_max,
            sort: sortColumn.value,
            direction: sortDirection.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortColumn.value = column;
        sortDirection.value = "desc";
    }
    // Trigger update with current search/date values would be ideal,
    // but here we might need to access the child component state or keep state lifted.
    // For simplicity, we just reload with current url params + new sort,
    // but the filters component handles the search/date state.
    // To fix this properly, we should probably keep search/date state in parent like before
    // OR emit event from filters component on every change.
    // Let's rely on inertia existing params or props.
    // Actually, `updateParams` function above expects filters object.

    // Quick fix: we need current search/date.
    // In strict component design, parent should hold state.
    // Let's move state back to parent for filters to ensure sort works with current filters.
    // BUT I already extracted filters.
    // I will modify `updateParams` to use current props or local state if I sync it.

    // Let's re-implement `updateParams` to merge.
    router.visit(route("invoices.index"), {
        data: {
            ...route().params, // Keep existing params
            sort: sortColumn.value,
            direction: sortDirection.value,
        },
        preserveState: true,
        replace: true,
    });
};

const handlePerPage = (newPerPage: string | number) => {
    perPage.value = newPerPage;
    router.visit(route("invoices.index"), {
        data: {
            ...route().params,
            per_page: newPerPage,
        },
        preserveState: true,
        replace: true,
    });
};

const confirmingFileDeletion = ref(false);
const fileIdToDelete = ref<number | null>(null);
const confirmingBatchDeletion = ref(false);
const selectedIds = ref<number[]>([]);
const form = useForm({});
const batchForm = useForm({
    ids: [] as number[],
});

const toggleSelect = (id: number) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((i) => i !== id);
    } else {
        selectedIds.value.push(id);
    }
};

const toggleAll = (val: boolean) => {
    if (val) {
        selectedIds.value = props.files.data.map((f) => f.id);
    } else {
        selectedIds.value = [];
    }
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

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
    }).format(amount);
};
</script>

<template>
    <Head title="Facturas" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                {{ $t("Facturas") }}
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
                                    {{ $t("Facturas Cargadas") }}
                                </h3>
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                                >
                                    <span
                                        class="font-semibold text-gray-700 dark:text-gray-200"
                                    >
                                        {{
                                            $t(
                                                "Total: :count facturas | Monto Total (Página): :amount",
                                                {
                                                    count: String(files.total),
                                                    amount: formatCurrency(
                                                        files.data.reduce(
                                                            (sum, file) =>
                                                                sum +
                                                                Number(
                                                                    file.factura
                                                                        ?.monto ||
                                                                        0,
                                                                ),
                                                            0,
                                                        ),
                                                    ),
                                                },
                                            )
                                        }}
                                    </span>
                                </p>
                            </div>

                            <!-- Batch Actions (Delete) -->
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
                                    {{ $t("Eliminar") }} ({{
                                        selectedIds.length
                                    }})
                                </button>
                            </Transition>
                        </div>

                        <!-- Filters -->
                        <div class="mb-6">
                            <InvoiceFilters
                                :filters="filters"
                                @update="updateParams"
                            />
                        </div>

                        <InvoiceTable
                            :files="files"
                            :selected-ids="selectedIds"
                            :sort-column="sortColumn"
                            :sort-direction="sortDirection"
                            :per-page="perPage"
                            @sort="handleSort"
                            @toggle-select="toggleSelect"
                            @toggle-all="toggleAll"
                            @delete="confirmFileDeletion"
                            @update-per-page="handlePerPage"
                        />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="confirmingFileDeletion" @close="closeModal">
            <template #title> {{ $t("Eliminar Factura") }} </template>

            <template #content>
                {{
                    $t(
                        "¿Estás seguro de que deseas eliminar esta factura? Esta acción eliminará el archivo y todos los registros asociados permanentemente.",
                    )
                }}
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    {{ $t("Cancelar") }}
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteFile"
                >
                    {{ $t("Eliminar") }}
                </PrimaryButton>
            </template>
        </ConfirmationModal>

        <ConfirmationModal :show="confirmingBatchDeletion" @close="closeModal">
            <template #title>
                {{ $t("Eliminar Facturas Seleccionadas") }}
            </template>

            <template #content>
                {{
                    $t(
                        "¿Estás seguro de que deseas eliminar las :count facturas seleccionadas? Esta acción no se puede deshacer.",
                        { count: String(selectedIds.length) },
                    )
                }}
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    {{ $t("Cancelar") }}
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3 bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 border-red-600 focus:ring-red-500"
                    :class="{ 'opacity-25': batchForm.processing }"
                    :disabled="batchForm.processing"
                    @click="deleteBatch"
                >
                    {{ $t("Eliminar Todo") }}
                </PrimaryButton>
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>
