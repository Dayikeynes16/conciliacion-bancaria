<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    files: [],
    statement: null,
    bank_code: 'BBVA', // Default to BBVA
});

const xmlInput = ref(null);
const xlsxInput = ref(null);

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    form.post(route('upload.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            close();
            // Optional: Show toast or success message handled by flash in Layout
        },
    });
};

const handleXmlChange = (e) => {
    // Array.from to convert FileList to Array if needed, but Inertia handles FileList usually.
    // However, useForm 'files' usually expects array of files for multiple.
    form.files = Array.from(e.target.files); 
};

const handleXlsxChange = (e) => {
    form.statement = e.target.files[0] || null;
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Subir Archivos
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Carga tus facturas (XML) y/o tu estado de cuenta (Excel) para procesar la conciliación.
            </p>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
                
                <!-- Facturas Section -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">Facturas Emitidas / Recibidas</h3>
                    <div class="flex items-center justify-center w-full">
                        <label for="xml-dropzone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 transision duration-150">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                                <svg v-if="!form.files.length" class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p v-if="!form.files.length" class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click para subir</span> o arrastra tus XML</p>
                                <p v-else class="text-sm text-green-600 dark:text-green-400 font-medium">
                                    {{ form.files.length }} archivo(s) seleccionado(s)
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">XML (CFDI 3.3 / 4.0)</p>
                            </div>
                            <input id="xml-dropzone" ref="xmlInput" type="file" class="hidden" multiple accept=".xml" @change="handleXmlChange" />
                        </label>
                    </div>
                    <p v-if="form.errors['files.0']" class="mt-2 text-sm text-red-600">{{ form.errors['files.0'] }}</p>
                    <div v-if="Object.keys(form.errors).some(k => k.startsWith('files'))" class="mt-1">
                        <p class="text-xs text-red-500">Algunos archivos tienen errores.</p>
                    </div>
                </div>

                <!-- Estado de Cuenta Section -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">Estado de Cuenta Bancario</h3>
                    
                    <!-- Bank Selection -->
                    <div class="mb-4">
                        <label for="bank" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Selecciona el Banco</label>
                        <select id="bank" v-model="form.bank_code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="BBVA">BBVA Bancomer</option>
                            <!-- Future banks can be added here -->
                        </select>
                         <p v-if="form.errors.bank_code" class="mt-2 text-sm text-red-600">{{ form.errors.bank_code }}</p>
                    </div>
                     <div class="flex items-center justify-center w-full">
                        <label for="xlsx-dropzone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 transition duration-150">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                                <svg v-if="!form.statement" class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p v-if="!form.statement" class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click para subir</span> o arrastra tu Excel</p>
                                <p v-else class="text-sm text-green-600 dark:text-green-400 font-medium">
                                    {{ form.statement.name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">XLSX, XLS, CSV (Formato BBVA)</p>
                            </div>
                            <input id="xlsx-dropzone" ref="xlsxInput" type="file" class="hidden" accept=".xlsx,.xls,.csv" @change="handleXlsxChange" />
                        </label>
                    </div>
                    <p v-if="form.errors.statement" class="mt-2 text-sm text-red-600">{{ form.errors.statement }}</p>
                </div>

                <!-- Global Error Message -->
                <div v-if="form.errors.message" class="p-3 bg-red-100 text-red-700 rounded-md text-sm">
                    {{ form.errors.message }}
                </div>

                <!-- Progress Bar -->
                <div v-if="form.processing" class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: form.progress ? form.progress.percentage + '%' : '0%' }"></div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="close" class="mr-3"> Cancelar </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Subir y Procesar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
