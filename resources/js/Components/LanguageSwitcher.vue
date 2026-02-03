<script setup lang="ts">
import { computed } from 'vue';
import { getActiveLanguage, loadLanguageAsync } from 'laravel-vue-i18n';

const activeLang = computed(() => getActiveLanguage() || 'es');

const toggleLanguage = () => {
    const newLang = activeLang.value === 'es' ? 'en' : 'es';
    localStorage.setItem('locale', newLang);
    window.location.reload();
};
</script>

<template>
    <button 
        @click="toggleLanguage"
        class="p-2 text-gray-400 hover:text-white transition-colors rounded-full hover:bg-gray-800 font-bold text-xs uppercase"
        title="Switch Language"
    >
        {{ activeLang }}
    </button>
</template>
