<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref } from 'vue';
import { trans } from 'laravel-vue-i18n';

import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps<{
    team: {
        id: number;
        user_id: number;
        name: string;
        rfc: string | null;
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
    rfc: props.team.rfc || '',
});

const updateTeamName = () => {
    updateForm.put(route('teams.update', props.team.id), {
        preserveScroll: true,
    });
};

const copyLink = (token: string) => {
    const link = route('team-invitations.accept', token);
    navigator.clipboard.writeText(link);
    alert(trans('Link copiado al portapapeles: ') + link);
};

const userBeingRemoved = ref<number | null>(null);
const userLeavingElement = ref<boolean>(false);
const removalForm = useForm({});

const confirmUserRemoval = (userId: number) => {
    userBeingRemoved.value = userId;
    userLeavingElement.value = false;
};

const confirmUserLeavning = (userId: number) => {
    userBeingRemoved.value = userId;
    userLeavingElement.value = true;
};

const removeMember = () => {
    if (!userBeingRemoved.value) return;
    
    removalForm.delete(route('team-members.destroy', { team: props.team.id, user: userBeingRemoved.value }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            userBeingRemoved.value = null;
            userLeavingElement.value = false;
        },
    });
};

const cancelInvitation = (id: number) => {
    if (confirm(trans('¿Estás seguro de que deseas cancelar esta invitación?'))) {
        router.delete(route('team-invitations.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="$t('Configuración de Equipo')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $t('Configuración del Equipo') }}: {{ team.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Update Team Name -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg" v-if="team.user_id === $page.props.auth.user.id">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $t('Nombre del Equipo') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $t('Actualiza el nombre de tu equipo o empresa.') }}
                            </p>
                        </header>

                        <form @submit.prevent="updateTeamName" class="mt-6 flex items-start gap-4">
                            <div class="flex-1 max-w-md">
                                <InputLabel for="team_name" :value="$t('Nombre')" class="sr-only" />
                                <TextInput
                                    id="team_name"
                                    type="text"
                                    class="block w-full"
                                    v-model="updateForm.name"
                                    required
                                />
                                <InputError class="mt-2" :message="updateForm.errors.name" />
                            </div>
                            
                            <div class="flex-1 max-w-md">
                                <InputLabel for="team_rfc" :value="$t('RFC')" class="sr-only" />
                                <TextInput
                                    id="team_rfc"
                                    type="text"
                                    class="block w-full"
                                    v-model="updateForm.rfc"
                                    :placeholder="$t('RFC (Opcional)')"
                                    maxlength="13"
                                />
                                <InputError class="mt-2" :message="updateForm.errors.rfc" />
                            </div>
                            <PrimaryButton :disabled="updateForm.processing" v-if="updateForm.isDirty">{{ $t('Guardar') }}</PrimaryButton>
                        </form>
                    </section>
                </div>

                <!-- Invite Member -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $t('Invitar Miembro') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $t('Agrega un nuevo miembro a tu equipo para compartir el acceso.') }}
                            </p>
                        </header>

                        <div v-if="team.user_id !== $page.props.auth.user.id" class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-md">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                {{ $t('Solo el propietario del equipo puede invitar a nuevos miembros.') }}
                            </p>
                        </div>

                        <form @submit.prevent="inviteUser" class="mt-6 flex items-start gap-4" :class="{ 'opacity-50': team.user_id !== $page.props.auth.user.id }">
                            <div class="flex-1 max-w-md">
                                <InputLabel for="email" :value="$t('Correo Electrónico')" class="sr-only" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="block w-full"
                                    v-model="form.email"
                                    placeholder="usuario@ejemplo.com"
                                    required
                                    :disabled="team.user_id !== $page.props.auth.user.id"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                            <PrimaryButton :disabled="form.processing || team.user_id !== $page.props.auth.user.id">{{ $t('Invitar') }}</PrimaryButton>
                        </form>
                    </section>
                </div>

                <!-- Pending Invitations -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg" v-if="invitations.length > 0">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $t('Invitaciones Pendientes') }}</h2>
                        </header>
                        
                        <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                {{ $t('Comparte el enlace de invitación con los usuarios para que se unan.') }}
                            </p>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">{{ $t('Correo') }}</th>
                                        <th class="px-6 py-3">{{ $t('Rol') }}</th>
                                        <th class="px-6 py-3">{{ $t('Enviado') }}</th>
                                        <th class="px-6 py-3">{{ $t('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invite in invitations" :key="invite.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4">{{ invite.email }}</td>
                                        <td class="px-6 py-4">{{ invite.role }}</td>
                                        <td class="px-6 py-4">{{ new Date(invite.created_at).toLocaleDateString() }}</td>
                                        <td class="px-6 py-4">
                                            <button @click="copyLink(invite.token)" class="text-blue-600 dark:text-blue-400 hover:underline mr-4">{{ $t('Copiar Enlace') }}</button>
                                            <button v-if="team.user_id === $page.props.auth.user.id" @click="cancelInvitation(invite.id)" class="text-red-600 dark:text-red-400 hover:underline">{{ $t('Eliminar') }}</button>
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
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $t('Miembros del Equipo') }}</h2>
                        </header>

                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">{{ $t('Nombre') }}</th>
                                        <th class="px-6 py-3">{{ $t('Correo') }}</th>
                                        <th class="px-6 py-3">{{ $t('Rol') }}</th>
                                        <th class="px-6 py-3">{{ $t('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in members" :key="user.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ user.name }}</td>
                                        <td class="px-6 py-4">{{ user.email }}</td>
                                        <td class="px-6 py-4 capitalize">{{ user.pivot.role }}</td>
                                        <td class="px-6 py-4">
                                            <button 
                                                v-if="user.pivot.role !== 'owner' && team.user_id === $page.props.auth.user.id" 
                                                @click="confirmUserRemoval(user.id)" 
                                                class="text-red-600 dark:text-red-400 hover:underline">
                                                {{ $t('Eliminar') }}
                                            </button>
                                            <button 
                                                v-if="user.id === $page.props.auth.user.id && user.pivot.role !== 'owner'" 
                                                @click="confirmUserLeavning(user.id)" 
                                                class="text-red-600 dark:text-red-400 hover:underline">
                                                {{ $t('Salir') }}
                                            </button>
                                            <span v-if="user.pivot.role === 'owner'" class="text-gray-400 italic">{{ $t('Propietario') }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Leave Team Section -->
                 <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg border-l-4 border-red-500" v-if="team.user_id !== $page.props.auth.user.id">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $t('Salir del Equipo') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $t('Renuncia a tu acceso a este equipo.') }}
                            </p>
                        </header>

                        <div class="mt-6">
                            <DangerButton @click="confirmUserLeavning($page.props.auth.user.id)">
                                {{ $t('Salir del Equipo') }}
                            </DangerButton>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <ConfirmationModal :show="userBeingRemoved !== null" @close="userBeingRemoved = null">
            <template #title>
                {{ userLeavingElement ? $t('Salir del Equipo') : $t('Eliminar Miembro del Equipo') }}
            </template>

            <template #content>
                <span v-if="userLeavingElement">
                    {{ $t('¿Estás seguro de que deseas salir de este equipo? Perderás acceso a todos los recursos.') }}
                </span>
                <span v-else>
                    {{ $t('¿Estás seguro de que deseas eliminar a este usuario del equipo? Esta acción no se puede deshacer.') }}
                </span>
            </template>

            <template #footer>
                <SecondaryButton @click="userBeingRemoved = null">
                    {{ $t('Cancelar') }}
                </SecondaryButton>

                <DangerButton
                    class="ml-2"
                    @click="removeMember"
                    :class="{ 'opacity-25': removalForm.processing }"
                    :disabled="removalForm.processing"
                >
                    {{ userLeavingElement ? $t('Salir') : $t('Eliminar') }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>
