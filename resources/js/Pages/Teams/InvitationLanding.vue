<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps<{
    invitation: {
        token: string;
        role: string;
        team: {
            name: string;
            owner: {
                name: string;
            };
        };
    };
}>();
</script>

<template>
    <Head :title="$t('Invitación de Equipo')" />

    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg text-center"
        >
            <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white">
                {{ $t('Invitación a Equipo') }}
            </h2>

            <p class="mb-6 text-gray-600 dark:text-gray-400">
                <span class="font-bold text-gray-800 dark:text-gray-200">{{ invitation.team.owner.name }}</span>
                {{ $t('te ha invitado a unirte y colaborar en el equipo') }}
                <span class="font-bold text-indigo-600">{{ invitation.team.name }}</span>.
            </p>

            <div class="space-y-4">
                <p class="text-sm text-gray-500">
                    {{ $t('Si ya tienes una cuenta, inicia sesión para unirte.') }}
                </p>
                <Link
                    :href="route('login')"
                    class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition"
                >
                    {{ $t('Iniciar Sesión') }}
                </Link>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-gray-300 dark:border-gray-700"></div>
                    <span class="flex-shrink-0 mx-4 text-xs text-gray-400">{{ $t('O') }}</span>
                    <div class="flex-grow border-t border-gray-300 dark:border-gray-700"></div>
                </div>

                <p class="text-sm text-gray-500">
                    {{ $t('¿No tienes cuenta? ¡Regístrate gratis!') }}
                </p>
                <Link
                    :href="route('register')"
                    class="block w-full text-center bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-2 px-4 rounded transition dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                >
                    {{ $t('Registrarse') }}
                </Link>
            </div>
        </div>
    </div>
</template>
