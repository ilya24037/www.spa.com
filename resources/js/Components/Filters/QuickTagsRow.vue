<!-- resources/js/Components/Filters/QuickTagsRow.vue -->
<template>
  <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <button
      v-for="tag in tags"
      :key="tag.id"
      @click="toggleTag(tag)"
      :class="[
        'rounded-xl px-3 py-1 whitespace-nowrap transition-colors text-sm',
        selectedTags.includes(tag.id) 
          ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' 
          : 'bg-gray-100 hover:bg-gray-200'
      ]"
    >
      {{ tag.label }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  tags: {
    type: Array,
    default: () => [
      { id: 'near', label: '🚶 Рядом со мной' },
      { id: 'available', label: '✅ Свободен сегодня' },
      { id: 'home', label: '🏠 Выезд на дом' },
      { id: 'verified', label: '✓ Проверенные' },
      { id: 'premium', label: '⭐ Премиум' },
      { id: 'new', label: '🆕 Новые мастера' }
    ]
  }
})

const selectedTags = ref([])

const toggleTag = (tag) => {
  const index = selectedTags.value.indexOf(tag.id)
  if (index > -1) {
    selectedTags.value.splice(index, 1)
  } else {
    selectedTags.value.push(tag.id)
  }
  
  // Применяем фильтры
  router.reload({
    data: { quick_filters: selectedTags.value },
    preserveState: true,
    preserveScroll: true
  })
}
</script>

<style scoped>
/* Скрываем скроллбар */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>