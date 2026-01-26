<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
        <!-- Left Side: Hero/Branding -->
        <div class="relative hidden lg:flex flex-col justify-center items-center bg-gray-900 overflow-hidden">
            <!-- Decorative Blobs -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute -top-[20%] -left-[10%] w-[70%] h-[70%] bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
                <div class="absolute -bottom-[20%] -right-[10%] w-[70%] h-[70%] bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
                <div class="absolute top-[30%] left-[30%] w-[60%] h-[60%] bg-pink-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
            </div>

            <div class="relative z-10 text-center px-10">
                <Link href="/" class="mb-8 inline-block transform transition hover:scale-105 duration-300">
                    <ApplicationLogo class="h-32 w-32 fill-current text-white drop-shadow-2xl" />
                </Link>
                <h2 class="text-4xl font-extrabold text-white tracking-tight mb-4">
                    Conciliación Bancaria
                </h2>
                <p class="text-lg text-indigo-200 font-light max-w-md mx-auto">
                    Gestión inteligente de tus finanzas. Automatiza la conciliación de facturas y movimientos bancarios.
                </p>
            </div>
            
             <!-- Glassmorphism overlay for texture -->
            <div class="absolute inset-0 bg-white/5 backdrop-blur-[1px]"></div>
        </div>

        <!-- Right Side: Auth Form -->
        <div class="flex flex-col justify-center items-center bg-gray-50 dark:bg-gray-950 px-6 py-12 lg:px-12 relative">
             <!-- Mobile Logo (Visible only on small screens) -->
             <div class="lg:hidden mb-8">
                <Link href="/">
                    <ApplicationLogo class="h-20 w-20 fill-current text-indigo-600 dark:text-indigo-400" />
                </Link>
            </div>

            <div class="w-full max-w-md space-y-8">
                <div
                    class="w-full bg-white dark:bg-gray-900 shadow-xl rounded-2xl p-8 border border-gray-100 dark:border-gray-800 backdrop-blur-sm transition-all duration-300 hover:shadow-2xl"
                >
                    <slot />
                </div>
            </div>
            
            <div class="absolute bottom-4 text-center w-full text-xs text-gray-400 dark:text-gray-600">
                &copy; {{ new Date().getFullYear() }} Conciliación Bancaria app. Todos los derechos reservados.
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
</style>
