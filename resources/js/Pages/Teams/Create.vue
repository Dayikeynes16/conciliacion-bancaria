<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

const form = useForm({
    name: '',
    rfc: '',
});

const submit = () => {
    form.post(route('teams.store'), {
        onFinish: () => form.reset('name'),
    });
};
</script>

<template>
    <Head title="Crear Nuevo Equipo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Crear Nuevo Equipo
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Detalles del Equipo</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Crea un nuevo espacio de trabajo para aislar facturas y movimientos.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Nombre del Equipo / Empresa" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="Ej. Mi Empresa S.A."
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="rfc" value="RFC (Opcional)" />
                                <TextInput
                                    id="rfc"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.rfc"
                                    placeholder="RFC del Equipo"
                                    maxlength="13"
                                />
                                <InputError class="mt-2" :message="form.errors.rfc" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Crear Equipo</PrimaryButton>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
