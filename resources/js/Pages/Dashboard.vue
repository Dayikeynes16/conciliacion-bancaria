<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UploadModal from "@/Components/UploadModal.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { getActiveLanguage } from "laravel-vue-i18n";

const props = defineProps<{
    stats: {
        pendingInvoices: number;
        pendingInvoicesAmount: number;
        pendingMovements: number;
        pendingMovementsAmount: number;
        conciliatedThisMonth: number;
        conciliatedLastMonth: number;
        paymentsLastMonth: number;
    };
    recentActivity: Array<{
        id: number;
        invoice: string;
        user: string;
        date: string;
        amount: number;
    }>;
}>();

const page = usePage();
const activeLang = getActiveLanguage() || 'es';

const periodName = computed(() => {
    const month = Number(page.props.filters?.month) || new Date().getMonth() + 1;
    const year = Number(page.props.filters?.year) || new Date().getFullYear();
    
    // Capitalize first letter
    const date = new Date(year, month - 1);
    const formatter = new Intl.DateTimeFormat(activeLang === 'es' ? 'es-MX' : 'en-US', { month: 'long', year: 'numeric' });
    const formatted = formatter.format(date);
    
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
});

const showUploadModal = ref(false);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                {{ $t('Resumen de') }} {{ periodName }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $t('Visualización de datos y conciliación bancaria mensual.') }}
            </p>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Flash Message -->
                <div
                    v-if="$page.props.flash.success"
                    class="mb-4 p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800"
                    role="alert"
                >
                    <span class="font-medium">Éxito!</span>
                    {{ $page.props.flash.success }}
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Pending Invoices -->
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500"
                    >
                        <div
                            class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase"
                        >
                            {{ $t('FACTURAS PENDIENTES') }}
                        </div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{
                                stats.pendingInvoices
                            }}</span>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400"
                                >({{
                                    formatCurrency(stats.pendingInvoicesAmount)
                                }})</span
                            >
                        </div>
                        <p v-if="stats.pendingInvoices === 0" class="mt-2 text-xs text-green-600 dark:text-green-400">
                            {{ $t('No hay facturas pendientes este mes.') }}
                        </p>
                    </div>

                    <!-- Pending Movements -->
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500"
                    >
                        <div
                            class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase"
                        >
                            {{ $t('PAGOS POR CONCILIAR') }}
                        </div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{
                                stats.pendingMovements
                            }}</span>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400"
                                >({{
                                    formatCurrency(
                                        stats.pendingMovementsAmount,
                                    )
                                }})</span
                            >
                        </div>
                        <p v-if="stats.pendingMovements === 0" class="mt-2 text-xs text-green-600 dark:text-green-400">
                            {{ $t('No hay pagos por conciliar este mes.') }}
                        </p>
                    </div>

                    <!-- Conciliated This Month -->
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500"
                    >
                        <div
                            class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase"
                        >
                            {{ $t('CONCILIADOS (MES ACTUAL)') }}
                        </div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{
                                stats.conciliatedThisMonth
                            }}</span>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400"
                                >{{ $t('registros') }}</span
                            >
                        </div>
                        <!-- Last Month Stats -->
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                             <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('Mes Anterior:') }}
                                <span class="font-bold">{{ stats.conciliatedLastMonth }}</span> {{ $t('conciliaciones') }} /
                                <span class="font-bold">{{ stats.paymentsLastMonth }}</span> {{ $t('pagos') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Actions -->
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                    >
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            {{ $t('Acciones Rápidas') }}
                        </h3>
                        <div class="flex flex-col space-y-3">
                            <button
                                @click="showUploadModal = true"
                                class="w-full h-12 flex justify-center items-center px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                    ></path>
                                </svg>
                                {{ $t('Cargar Archivos (XML / Excel)') }}
                            </button>
                            <Link
                                :href="route('reconciliation.index')"
                                class="w-full h-12 flex justify-center items-center px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
                                    ></path>
                                </svg>
                                {{ $t('Ir a Mesa de Trabajo') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Activity List -->
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                    >
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            {{ $t('Actividad Reciente') }}
                        </h3>
                        <ul
                            class="divide-y divide-gray-200 dark:divide-gray-700"
                            v-if="recentActivity.length > 0"
                        >
                            <li
                                v-for="activity in recentActivity"
                                :key="activity.id"
                                class="py-3 flex justify-between items-center text-sm"
                            >
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ activity.invoice }}
                                    </p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">
                                        por {{ activity.user }} •
                                        {{ activity.date }}
                                    </p>
                                </div>
                                <div class="font-bold text-green-600 dark:text-green-400">
                                    {{ formatCurrency(activity.amount) }}
                                </div>
                            </li>
                        </ul>
                        <div v-else class="text-gray-500 text-sm italic">
                            No hay actividad reciente.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UploadModal :show="showUploadModal" @close="showUploadModal = false" />
    </AuthenticatedLayout>
</template>
