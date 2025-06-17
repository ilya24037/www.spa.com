<!-- resources/js/Components/Filters/FiltersSidebar.vue -->
<template>
  <aside 
    class="w-60 flex-shrink-0"
    :class="{ 'lg:block': true, 'hidden': !showMobile }"
  >
    <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-20">
      <!-- Заголовок с кнопкой сброса -->
      <div class="flex items-center justify-between p-4 border-b">
        <h3 class="font-semibold text-gray-900">Фильтры</h3>
        <button
          v-if="hasActiveFilters"
          @click="$emit('reset')"
          class="text-sm text-blue-600 hover:text-blue-700 transition-colors"
        >
          Сбросить все
        </button>
      </div>

      <!-- Счетчик результатов -->
      <div class="mx-4 mt-4 p-3 bg-blue-50 rounded-lg">
        <p class="text-sm text-blue-900">
          Найдено <span class="font-semibold">{{ resultsCount }}</span> мастеров
        </p>
      </div>

      <!-- Контент фильтров -->
      <div class="p-4 space-y-6 max-h-[calc(100vh-240px)] overflow-y-auto">
        <slot />
      </div>

      <!-- Применить (для мобильной версии) -->
      <div class="p-4 border-t lg:hidden">
        <button
          @click="$emit('apply')"
          class="w-full h-12 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
        >
          Показать {{ resultsCount }} предложений
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
defineProps({
  resultsCount: {
    type: Number,
    required: true
  },
  hasActiveFilters: {
    type: Boolean,
    default: false
  },
  showMobile: {
    type: Boolean,
    default: false
  }
})

defineEmits(['reset', 'apply'])
</script>