<!-- resources/js/src/widgets/header/LanguageSelector.vue -->
<template>
  <div class="language-selector relative">
    <button class="flex items-center space-x-1 text-sm text-gray-500 hover:text-blue-600" @click="showLanguageMenu = !showLanguageMenu">
      <span>{{ currentLanguage }}</span>
      <svg
        class="w-3 h-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>
    
    <div v-if="showLanguageMenu" class="absolute right-0 mt-2 w-24 bg-white border border-gray-500 rounded-lg shadow-lg z-50">
      <div class="py-2">
        <button class="block w-full text-left px-4 py-2 text-sm text-gray-500 hover:bg-gray-500" @click="selectLanguage('ru')">
          Русский
        </button>
        <button class="block w-full text-left px-4 py-2 text-sm text-gray-500 hover:bg-gray-500" @click="selectLanguage('en')">
          English
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const showLanguageMenu = ref(false)
const currentLanguage = ref('RU')

const selectLanguage = (lang: string) => {
    currentLanguage.value = lang.toUpperCase()
    showLanguageMenu.value = false
}
</script> 