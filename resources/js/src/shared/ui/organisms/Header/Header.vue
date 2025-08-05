<template>
  <header class="header w-full">
    <!-- Loading состояние - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
    <div v-if="loading" class="animate-pulse p-4">
      <div class="h-4 bg-gray-200 rounded w-1/2"></div>
    </div>
    
    <!-- Error состояние - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
    <div v-else-if="error" class="p-4 text-red-500">
      {{ error }}
    </div>
    
    <!-- Content -->
    <div v-else class="p-4">
      <slot />
    </div>
  </header>
</template>

<script setup lang="ts">
// TypeScript типизация - ЧЕК-ЛИСТ CLAUDE.md ✅
interface Props {
  loading?: boolean
  error?: string
}

// Default значения - ЧЕК-ЛИСТ CLAUDE.md ✅
withDefaults(defineProps<Props>(), {
  loading: false,
  error: ''
})
</script>