<script setup lang="ts">
import { ref, computed } from "vue";
import { wTrans } from "laravel-vue-i18n";

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
    min?: string;
    max?: string;
}>();

const emit = defineEmits(["update:modelValue"]);

const inputRef = ref<HTMLInputElement | null>(null);

const formattedDate = computed(() => {
    if (!props.modelValue) return null;
    const [year, month, day] = props.modelValue.split("-");
    return `${day}/${month}/${year}`;
});

const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit("update:modelValue", target.value);
};

const openPicker = () => {
    if (inputRef.value) {
        if (typeof inputRef.value.showPicker === "function") {
            try {
                inputRef.value.showPicker();
            } catch (error) {
                console.error("Error showing picker:", error);
                // Fallback if strict security context blocks it (shouldn't happen on click)
            }
        } else {
            // Fallback for older browsers
            inputRef.value.focus();
            inputRef.value.click();
        }
    }
};
</script>

<template>
    <div class="relative group">
        <!-- Visible Button -->
        <button
            type="button"
            @click="openPicker"
            class="w-full flex items-center justify-between px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
            :class="{
                'text-gray-500 dark:text-gray-400': !modelValue,
                'text-gray-900 dark:text-white': modelValue,
            }"
        >
            <span class="truncate">
                {{ formattedDate || placeholder || $t('Seleccionar Fecha') }}
            </span>
            <svg
                class="w-4 h-4 ml-2 text-gray-500 dark:text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                ></path>
            </svg>
        </button>

        <!-- Hidden Native Input -->
        <!-- Visually hidden but present in DOM for showPicker API -->
        <input
            ref="inputRef"
            type="date"
            :value="modelValue"
            :min="min"
            :max="max"
            @input="onInput"
            class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 pointer-events-none -z-10"
            tabindex="-1"
        />
    </div>
</template>
