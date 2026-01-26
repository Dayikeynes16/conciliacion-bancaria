<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref } from 'vue';

const props = defineProps<{
    team: {
        id: number;
        user_id: number;
        name: string;
    };
    members: Array<{
        id: number;
        name: string;
        email: string;
        pivot: {
            role: string;
        };
    }>;
    invitations: Array<{
        id: number;
        email: string;
        role: string;
        token: string;
        created_at: string;
    }>;
}>();

const form = useForm({
    email: '',
});

const inviteUser = () => {
    form.post(route('team-members.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const updateForm = useForm({
    name: props.team.name,
});

const updateTeamName = () => {
    updateForm.put(route('teams.update', props.team.id), {
        preserveScroll: true,
    });
};

const copyLink = (token: string) => {
    const link = route('team-invitations.accept', token);
    navigator.clipboard.writeText(link);
    alert('Link copiado al portapapeles: ' + link);
};

const removeMember = (userId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar a este usuario del equipo?')) {
        router.delete(route('team-members.destroy', { team: props.team.id, user: userId }));
    }
};
</script>

<template>
    <Head title="Configuración de Equipo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Configuración del Equipo: {{ team.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Update Team Name -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg" v-if="team.user_id === $page.props.auth.user.id">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nombre del Equipo</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Actualiza el nombre de tu equipo o empresa.
                            </p>
                        </header>

                        <form @submit.prevent="updateTeamName" class="mt-6 flex items-start gap-4">
                            <div class="flex-1 max-w-md">
                                <InputLabel for="team_name" value="Nombre" class="sr-only" />
                                <TextInput
                                    id="team_name"
                                    type="text"
                                    class="block w-full"
                                    v-model="updateForm.name"
                                    required
                                />
                                <InputError class="mt-2" :message="updateForm.errors.name" />
                            </div>
                            <PrimaryButton :disabled="updateForm.processing" v-if="updateForm.isDirty">Guardar</PrimaryButton>
                        </form>
                    </section>
                </div>

                <!-- Invite Member -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Invitar Miembro</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Agrega un nuevo miembro a tu equipo para compartir el acceso.
                            </p>
                        </header>

                        <form @submit.prevent="inviteUser" class="mt-6 flex items-start gap-4">
                            <div class="flex-1 max-w-md">
                                <InputLabel for="email" value="Correo Electrónico" class="sr-only" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="block w-full"
                                    v-model="form.email"
                                    placeholder="usuario@ejemplo.com"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                            <PrimaryButton :disabled="form.processing">Invitar</PrimaryButton>
                        </form>
                    </section>
                </div>

                <!-- Pending Invitations -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg" v-if="invitations.length > 0">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Invitaciones Pendientes</h2>
                        </header>
                        
                        <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                Comparte el enlace de invitación con los usuarios para que se unan.
                            </p>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">Correo</th>
                                        <th class="px-6 py-3">Rol</th>
                                        <th class="px-6 py-3">Enviado</th>
                                        <th class="px-6 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invite in invitations" :key="invite.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4">{{ invite.email }}</td>
                                        <td class="px-6 py-4">{{ invite.role }}</td>
                                        <td class="px-6 py-4">{{ new Date(invite.created_at).toLocaleDateString() }}</td>
                                        <td class="px-6 py-4">
                                            <button @click="copyLink(invite.token)" class="text-blue-600 dark:text-blue-400 hover:underline">Copiar Enlace</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Team Members -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Miembros del Equipo</h2>
                        </header>

                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">Nombre</th>
                                        <th class="px-6 py-3">Correo</th>
                                        <th class="px-6 py-3">Rol</th>
                                        <th class="px-6 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in members" :key="user.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ user.name }}</td>
                                        <td class="px-6 py-4">{{ user.email }}</td>
                                        <td class="px-6 py-4 capitalize">{{ user.pivot.role }}</td>
                                        <td class="px-6 py-4">
                                            <button 
                                                v-if="user.pivot.role !== 'owner'" 
                                                @click="removeMember(user.id)" 
                                                class="text-red-600 dark:text-red-400 hover:underline">
                                                Eliminar
                                            </button>
                                            <span v-else class="text-gray-400 italic">Propietario</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
