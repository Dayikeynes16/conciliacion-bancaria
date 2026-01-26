<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
    active?: boolean;
}>();
</script>

<template>
    <Link
        :href="href"
        class="flex items-center px-4 py-3 text-gray-300 transition-colors duration-200 transform rounded-lg hover:bg-gray-800 hover:text-white"
        :class="{ 'bg-gray-800 text-white': active }"
    >
        <slot name="icon" />
        <span class="mx-4 font-medium"><slot /></span>
    </Link>
</template>
