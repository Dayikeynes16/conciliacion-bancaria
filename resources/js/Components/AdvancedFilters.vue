<script setup lang="ts">
import { ref, reactive, watch } from "vue";
import { debounce } from "lodash";
import DatePicker from "@/Components/DatePicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps<{
    filters?: {
        search?: string;
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
    };
    placeholder?: string;
}>();

const emit = defineEmits(["update"]);

const search = ref(props.filters?.search || "");

// Detailed filters
const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    amount_min: props.filters?.amount_min || "",
    amount_max: props.filters?.amount_max || "",
});

const emitUpdate = () => {
    emit("update", {
        search: search.value,
        ...filterForm,
    });
};

const updateSearch = debounce(() => {
    emitUpdate();
}, 500);

const updateFilters = debounce(() => {
    emitUpdate();
}, 800);

const clearFilters = () => {
    search.value = "";
    filterForm.date_from = "";
    filterForm.date_to = "";
    filterForm.amount_min = "";
    filterForm.amount_max = "";
    emitUpdate();
};

watch(search, updateSearch);
watch(filterForm, updateFilters, { deep: true });
</script>

<template>
    <div class="flex flex-col gap-4 w-full">
        <!-- Search Field (Always Visible and prominent) -->
        <div
            class="flex justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700"
        >
            <div class="relative w-full md:w-96">
                <div
                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                >
                    <svg
                        class="w-4 h-4 text-gray-500 dark:text-gray-400"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 20 20"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                        />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    :placeholder="
                        props.placeholder ||
                        $t('Buscar por nombre, RFC o monto...')
                    "
                />
            </div>
        </div>

        <!-- Advanced Filters -->
        <div
            class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700"
        >
            <h3
                class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3"
            >
                {{ $t("FILTROS AVANZADOS") }}
            </h3>
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
                        @keyup.enter="emitUpdate"
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
                        @keyup.enter="emitUpdate"
                        placeholder="0.00"
                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <div
                class="mt-4 flex flex-col md:flex-row justify-between items-end gap-4"
            >
                <div class="w-full md:w-auto">
                    <slot name="footer" />
                </div>
                <div class="flex space-x-3 w-full md:w-auto justify-end items-center">
                    <slot name="actions" />
                    <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-1" v-if="$slots.actions"></div>
                    <SecondaryButton @click="clearFilters" size="sm">{{
                        $t("LIMPIAR")
                    }}</SecondaryButton>
                    <PrimaryButton @click="emitUpdate" size="sm">{{
                        $t("APLICAR FILTROS")
                    }}</PrimaryButton>
                </div>
            </div>
        </div>
    </div>
</template>
