<script setup lang="ts">
defineProps<{
    modelValue: string;
    pendingCount: number;
    conciliatedCount: number;
}>();

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex space-x-2 bg-gray-200 dark:bg-gray-700 p-1 rounded-lg inline-flex">
        <button
            @click="$emit('update:modelValue', 'pending')"
            :class="['px-4 py-2 rounded-md text-sm font-medium transition-colors', modelValue === 'pending' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']"
        >
            {{ $t('Pendientes') }}
        </button>
        <button
            @click="$emit('update:modelValue', 'conciliated')"
            :class="['px-4 py-2 rounded-md text-sm font-medium transition-colors', modelValue === 'conciliated' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']"
        >
            {{ $t('Conciliados') }}
        </button>
    </div>
</template>
