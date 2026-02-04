<script setup lang="ts">
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DatePicker from "@/Components/DatePicker.vue";
import { reactive } from "vue";

const props = defineProps<{
    filters?: {
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
    };
}>();

const emit = defineEmits(["apply", "clear"]);

const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    amount_min: props.filters?.amount_min || "",
    amount_max: props.filters?.amount_max || "",
});

const applyFilters = () => {
    emit("apply", { ...filterForm });
};

const clearFilters = () => {
    filterForm.date_from = "";
    filterForm.date_to = "";
    filterForm.amount_min = "";
    filterForm.amount_max = "";
    emit("clear");
};
</script>

<template>
    <div
        class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700"
    >
        <div class="flex justify-between items-center mb-3">
            <h3
                class="text-xs font-semibold text-gray-400 uppercase tracking-widest"
            >
                {{ $t("FILTROS DE BÚSQUEDA") }}
            </h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Date Range -->
            <div>
                <label
                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                    >{{ $t("Desde") }}</label
                >
                <DatePicker
                    v-model="filterForm.date_from"
                    :placeholder="$t('dd/mm/aaaa')"
                />
            </div>
            <div>
                <label
                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                    >{{ $t("Hasta") }}</label
                >
                <DatePicker
                    v-model="filterForm.date_to"
                    :placeholder="$t('dd/mm/aaaa')"
                />
            </div>

            <!-- Amount Range -->
            <div>
                <label
                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                    >{{ $t("Monto Mín ($)") }}</label
                >
                <input
                    type="number"
                    step="0.01"
                    v-model="filterForm.amount_min"
                    placeholder="0.00"
                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>
            <div>
                <label
                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                    >{{ $t("Monto Máx ($)") }}</label
                >
                <input
                    type="number"
                    step="0.01"
                    v-model="filterForm.amount_max"
                    placeholder="0.00"
                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>
        </div>
        <div class="mt-4 flex justify-end space-x-3">
            <SecondaryButton @click="clearFilters" size="sm">{{
                $t("LIMPIAR")
            }}</SecondaryButton>
            <PrimaryButton @click="applyFilters" size="sm">{{
                $t("APLICAR FILTROS")
            }}</PrimaryButton>
        </div>
    </div>
</template>
