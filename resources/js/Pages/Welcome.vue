<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();
</script>

<template>
    <Head title="Bienvenido" />

    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">
        <!-- Navigation -->
        <nav class="w-full flex items-center justify-between p-6 lg:px-12 max-w-7xl mx-auto">
            <div class="flex items-center gap-3">
                <ApplicationLogo class="w-10 h-10" />
                <span class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Conciliación</span>
            </div>
            
            <div v-if="canLogin" class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-md px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition"
                    >
                        Iniciar Sesión
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        Registrarse
                    </Link>
                </template>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="flex-1 flex flex-col items-center justify-center text-center px-6 lg:px-8 mt-10 mb-20">
            <h1 class="text-4xl lg:text-6xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-6">
                Conciliación Bancaria <br/>
                <span class="text-indigo-600 dark:text-indigo-400">Automatizada e Inteligente</span>
            </h1>
            
            <p class="mt-4 text-lg lg:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto mb-10">
                Simplifica tu contabilidad. Sube tus facturas y estados de cuenta, y deja que nuestra plataforma detecte coincidencias automáticamente.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 items-center justify-center">
                <template v-if="$page.props.auth.user">
                     <Link
                        :href="route('dashboard')"
                        class="rounded-full px-8 py-3 text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-lg hover:shadow-indigo-500/25"
                    >
                        Ir al Dashboard
                    </Link>
                </template>
                <template v-else>
                     <Link
                        :href="route('register')"
                        class="rounded-full px-8 py-3 text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-lg hover:shadow-indigo-500/25"
                    >
                        Comenzar Ahora
                    </Link>
                     <Link
                        :href="route('login')"
                         class="rounded-full px-8 py-3 text-base font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 transition"
                    >
                        Ya tengo cuenta
                    </Link>
                </template>
            </div>

            <!-- Features Grid -->
            <div class="mt-24 grid gap-8 md:grid-cols-3 max-w-5xl mx-auto">
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Carga Universal</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Compatible con XML (CFDI 3.3/4.0) y estados de cuenta en Excel de múltiples bancos (BBVA, Santander, etc).
                    </p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto text-green-600 dark:text-green-400">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Conciliación Automática</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Algoritmos inteligentes que cruzan información por RFC, montos y fechas para detectar coincidencias al instante.
                    </p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                     <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto text-purple-600 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Tolerancia Configurable</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Define márgenes de tolerancia en montos y días para adaptar la conciliación a la realidad de tu operación.
                    </p>
                </div>
            </div>
        </main>

        <footer class="py-8 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800">
            &copy; 2026 Conciliación. Todos los derechos reservados.
        </footer>
    </div>
</template>
