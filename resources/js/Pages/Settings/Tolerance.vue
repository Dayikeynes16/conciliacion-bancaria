<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    tolerancia: Object,
});

const form = useForm({
    monto: props.tolerancia.monto,
    monto: props.tolerancia.monto,
});

const submit = () => {
    form.post(route('settings.tolerance.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Configuración de Tolerancia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Configuración de Tolerancia
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <section>
                            <header>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Tolerancia de Conciliación
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Define el monto máximo de diferencia permitido (en centavos/pesos) para considerar conciliados automáticamente un movimiento y una factura.
                                </p>
                            </header>

                            <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-xl">
                                <div>
                                    <InputLabel for="monto" value="Monto de Tolerancia ($MXN)" />
                                    <TextInput
                                        id="monto"
                                        type="number"
                                        step="0.01"
                                        class="mt-1 block w-full"
                                        v-model="form.monto"
                                        required
                                        autofocus
                                        autocomplete="off"
                                    />
                                    <InputError class="mt-2" :message="form.errors.monto" />
                                </div>



                                <div class="flex items-center gap-4">
                                    <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>

                                    <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Guardado.</p>
                                    </Transition>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
