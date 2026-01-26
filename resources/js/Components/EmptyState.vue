<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
    actionText?: string;
}>();

defineEmits(['action']);
</script>

<template>
    <div class="flex flex-col items-center justify-center p-8 text-center bg-white border border-dashed border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 h-96">
        <div class="p-4 bg-indigo-50 rounded-full dark:bg-gray-700 mb-4">
             <slot name="icon">
                <svg class="w-12 h-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
             </slot>
        </div>
        
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm">{{ description }}</p>

        <div class="mt-6" v-if="actionText">
            <button
                @click="$emit('action')"
                type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
            >
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ actionText }}
            </button>
        </div>
    </div>
</template>
