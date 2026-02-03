<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { ref } from "vue";
import { BankFormat } from "@/types";

defineProps<{
    formats: Array<{
        id: number;
        name: string;
        start_row: number;
        date_column: string;
        description_column: string;
        amount_column: string;
        reference_column: string | null;
        type_column: string | null;
        color?: string;
        updated_at: string;
    }>;
}>();

const confirmingFormatDeletion = ref(false);
const formatToDelete = ref<any>(null);

const confirmFormatDeletion = (format: any) => {
    formatToDelete.value = format;
    confirmingFormatDeletion.value = true;
};

const deleteFormat = () => {
    if (!formatToDelete.value) return;

    router.delete(route("bank-formats.destroy", formatToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingFormatDeletion.value = false;
    formatToDelete.value = null;
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("es-MX", { dateStyle: "medium" }).format(date);
};
</script>

<template>
    <Head title="Formatos Bancarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Formatos Bancarios
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        {{ $t('Gestiona los formatos de importación para tus extractos bancarios.') }}
                    </p>
                </div>

                <!-- Grid Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Formats Cards -->
                    <div 
                        v-for="format in formats" 
                        :key="format.id" 
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200"
                    >
                        <div class="p-6">
                            <!-- Header: Color + Name + Actions -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <span 
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: format.color || '#3b82f6' }"
                                    ></span>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg truncate" :title="format.name">
                                        {{ format.name }}
                                    </h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link 
                                        :href="route('bank-formats.edit', format.id)"
                                        class="text-gray-400 hover:text-indigo-500 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                        </svg>
                                    </Link>
                                    <button 
                                        @click="confirmFormatDeletion(format)"
                                        class="text-gray-400 hover:text-red-500 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Details Grid -->
                            <div class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm mt-4">
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('Fila Inicio') }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 font-mono">{{ format.start_row }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('Fecha') }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 font-mono">{{ format.date_column }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('Descripción') }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 font-mono">{{ format.description_column }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('Monto') }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 font-mono">{{ format.amount_column }}</span>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1 text-xs text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                {{ $t('Actualizado') }} {{ formatDate(format.updated_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Dashed "New Format" Card -->
                    <Link
                        :href="route('bank-formats.create')"
                        class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg flex flex-col items-center justify-center p-6 text-gray-400 hover:border-blue-500 hover:text-blue-500 hover:bg-blue-50/50 dark:hover:bg-gray-800/50 transition-all duration-200 min-h-[240px] group"
                    >
                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 flex items-center justify-center mb-3 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <span class="font-medium text-sm">{{ $t('Agregar nuevo formato') }}</span>
                    </Link>

                </div>
            </div>
        </div>

        <!-- Deletion Confirmation Modal -->
        <Modal :show="confirmingFormatDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $t('¿Estás seguro de que quieres eliminar este formato?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('Una vez eliminado, no podrás importar archivos usando este formato. Esta acción es irreversible.') }}
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> {{ $t('Cancelar') }} </SecondaryButton>

                    <DangerButton
                        class="ml-3"
                        @click="deleteFormat"
                    >
                        {{ $t('Eliminar Formato') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
