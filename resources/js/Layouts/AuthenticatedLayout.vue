<script setup lang="ts">
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import SidebarLink from "@/Components/SidebarLink.vue";
import { Link, router } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);

const updateDateFilter = (key: 'month' | 'year', value: string) => {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set(key, value.toString());
    router.visit(currentUrl.toString(), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div class="flex h-screen bg-gray-100 dark:bg-gray-900 font-sans">
        <!-- Sidebar (Desktop & Mobile Wrapper) -->
        <aside
            :class="
                showingNavigationDropdown
                    ? 'translate-x-0'
                    : '-translate-x-full'
            "
            class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-900 text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
        >
            <!-- Logo Area -->
            <div
                class="flex items-center justify-center h-16 bg-gray-900 border-b border-gray-800 shadow-md"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex items-center space-x-2"
                >
                    <ApplicationLogo class="w-8 h-8" />
                    <span class="text-xl font-bold tracking-wide"
                        >Conciliación</span
                    >
                </Link>
            </div>

            <!-- Navigation Links -->
            <nav class="mt-5 px-2 space-y-1">
                <SidebarLink
                    :href="route('dashboard')"
                    :active="route().current('dashboard')"
                >
                    <template #icon>
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                            ></path>
                        </svg>
                    </template>
                    Dashboard
                </SidebarLink>

                <!-- Placeholder for future links -->
                <SidebarLink
                    :href="route('reconciliation.index')"
                    :active="route().current('reconciliation.index')"
                >
                    <template #icon>
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </template>
                    Mesa de Trabajo
                </SidebarLink>

                <SidebarLink
                    :href="route('reconciliation.history')"
                    :active="route().current('reconciliation.history')"
                >
                    <template #icon>
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </template>
                    Historial
                </SidebarLink>

                <SidebarLink
                    :href="route('reconciliation.status')"
                    :active="route().current('reconciliation.status')"
                >
                    <template #icon>
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            ></path>
                        </svg>
                    </template>
                    Reporte Estatus
                </SidebarLink>

                <SidebarLink
                    :href="route('movements.index')"
                    :active="route().current('movements.*')"
                >
                    <template #icon>
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </template>
                    Movimientos
                </SidebarLink>

                <SidebarLink
                    :href="route('invoices.index')"
                    :active="route().current('invoices.*')"
                >
                    <template #icon>
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            ></path>
                        </svg>
                    </template>
                    Facturas
                </SidebarLink>

                <!-- Settings (Owner Only) -->
                <div
                    v-if="
                        $page.props.auth.user.current_team &&
                        $page.props.auth.user.current_team.user_id ==
                            $page.props.auth.user.id
                    "
                    class="pt-4 border-t border-gray-800 mt-4"
                >
                    <div
                        class="px-2 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider"
                    >
                        Configuración
                    </div>
                    <SidebarLink
                        :href="route('settings.tolerance')"
                        :active="route().current('settings.tolerance')"
                    >
                        <template #icon>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                ></path>
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                ></path>
                            </svg>
                        </template>
                        Tolerancia
                    </SidebarLink>
                </div>
            </nav>

            <!-- User Profile (Bottom Sidebar) -->
            <div class="absolute bottom-0 w-full border-t border-gray-800 p-4">
                <div class="flex items-center">
                    <div
                        class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold"
                    >
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-xs text-gray-400 hover:text-white"
                            >Cerrar Sesión</Link
                        >
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="showingNavigationDropdown"
            @click="showingNavigationDropdown = false"
            class="fixed inset-0 z-20 bg-black opacity-50 lg:hidden"
        ></div>

        <!-- Global Toast Notifications -->
        <div class="fixed top-4 right-4 z-50 space-y-2 pointer-events-none">
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="$page.props.flash.success" class="pointer-events-auto w-full max-w-md overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-medium text-gray-900">Exitoso</p>
                                <p class="mt-1 text-sm text-gray-500 leading-relaxed">{{ $page.props.flash.success }}</p>
                            </div>
                            <div class="ml-4 flex flex-shrink-0">
                                <button type="button" @click="$page.props.flash.success = undefined" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <span class="sr-only">Close</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="$page.props.flash.error" class="pointer-events-auto w-full max-w-md overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-medium text-gray-900">Error</p>
                                <p class="mt-1 text-sm text-gray-500 leading-relaxed">{{ $page.props.flash.error }}</p>
                            </div>
                            <div class="ml-4 flex flex-shrink-0">
                                <button type="button" @click="$page.props.flash.error = undefined" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <span class="sr-only">Close</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header
                class="flex items-center justify-between h-16 px-6 bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center">
                    <!-- Hamburger Button (Mobile) -->
                    <button
                        @click="
                            showingNavigationDropdown =
                                !showingNavigationDropdown
                        "
                        class="text-gray-500 focus:outline-none lg:hidden"
                    >
                        <svg
                            class="w-6 h-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M4 6H20M4 12H20M4 18H11"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                    <!-- Page Header Slot -->
                    <div class="ml-4 lg:ml-0">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Global Date Filter -->
                    <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700 p-1.5 rounded-lg border border-gray-200 dark:border-gray-600">
                        <select 
                            :value="$page.props.filters.month"
                            @change="updateDateFilter('month', ($event.target as HTMLSelectElement).value)"
                            class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 dark:text-gray-300 font-medium py-1 pr-8 pl-2 cursor-pointer"
                        >
                            <option v-for="m in 12" :key="m" :value="m">
                                {{ new Date(0, m - 1).toLocaleString('es-MX', { month: 'long' }).charAt(0).toUpperCase() + new Date(0, m - 1).toLocaleString('es-MX', { month: 'long' }).slice(1) }}
                            </option>
                        </select>
                        <select 
                            :value="$page.props.filters.year"
                            @change="updateDateFilter('year', ($event.target as HTMLSelectElement).value)"
                            class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 dark:text-gray-300 font-bold py-1 pr-8 pl-2 cursor-pointer"
                        >
                            <option v-for="y in $page.props.available_years" :key="y" :value="y">
                                {{ y }}
                            </option>
                        </select>
                    </div>

                    <!-- Team Switcher -->
                    <Dropdown
                        align="right"
                        width="60"
                        v-if="$page.props.auth.user.current_team"
                    >
                        <template #trigger>
                            <span class="inline-flex rounded-md">
                                <button
                                    type="button"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                >
                                    {{
                                        $page.props.auth.user.current_team.name
                                    }}

                                    <svg
                                        class="ml-2 -mr-0.5 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </span>
                        </template>

                        <template #content>
                            <div class="w-60">
                                <!-- Team Management -->
                                <div
                                    class="block px-4 py-2 text-xs text-gray-400"
                                >
                                    Administrar Equipo
                                </div>

                                <DropdownLink :href="route('teams.show')">
                                    Configuración del Equipo
                                </DropdownLink>

                                <DropdownLink :href="route('teams.create')">
                                    + Crear Nuevo Equipo
                                </DropdownLink>

                                <!-- Team Switcher -->
                                <div
                                    class="border-t border-gray-200 dark:border-gray-600"
                                ></div>

                                <div
                                    class="block px-4 py-2 text-xs text-gray-400"
                                >
                                    Cambiar Equipo
                                </div>

                                <div
                                    v-for="team in $page.props.auth.user.all_teams"
                                    :key="team.id"
                                >
                                    <form
                                        @submit.prevent="
                                            router.put(
                                                route('current-team.update'),
                                                { team_id: team.id },
                                            )
                                        "
                                    >
                                        <DropdownLink as="button">
                                            <div class="flex items-center">
                                                <svg
                                                    v-if="
                                                        team.id ==
                                                        $page.props.auth.user
                                                            .current_team_id
                                                    "
                                                    class="mr-2 h-5 w-5 text-green-400"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="1.5"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                <div class="truncate">
                                                    {{ team.name }}
                                                </div>
                                            </div>
                                        </DropdownLink>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </Dropdown>

                    <!-- User Settings Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none transition duration-150 ease-in-out"
                            >
                                <span>{{ $page.props.auth.user.name }}</span>
                                <svg
                                    class="ml-2 -mr-0.5 h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                Profile
                            </DropdownLink>
                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Main Content Area -->
            <main
                class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 p-6"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
