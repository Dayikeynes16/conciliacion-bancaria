<script setup lang="ts">
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

defineProps<{
    show: boolean;
    title: string;
    message: string;
    processing: boolean;
    isError?: boolean;
}>();

const emit = defineEmits(["close", "confirm"]);
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2
                class="text-lg font-medium"
                :class="
                    isError
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-gray-900 dark:text-gray-100'
                "
            >
                {{ title }}
            </h2>

            <p
                class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line"
            >
                {{ message }}
            </p>

            <div class="mt-4">
                <slot name="content" />
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="$emit('close')">
                    {{ isError ? "Entendido" : "Cancelar" }}
                </SecondaryButton>

                <DangerButton
                    v-if="!isError"
                    class="ml-3"
                    :class="{ 'opacity-25': processing }"
                    :disabled="processing"
                    @click="$emit('confirm')"
                >
                    Confirmar
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
