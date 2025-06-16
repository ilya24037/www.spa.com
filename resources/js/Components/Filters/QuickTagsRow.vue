<!-- resources/js/Components/Filters/QuickTagsRow.vue -->
<template>
  <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <button
      v-for="tag in quickTags"
      :key="tag.id"
      @click="toggleTag(tag)"
      :class="[
        'rounded-xl px-3 py-1 whitespace-nowrap transition-colors text-sm',
        isActive(tag.id) 
          ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' 
          : 'bg-gray-100 hover:bg-gray-200'
      ]"
    >
      <span v-if="tag.icon" class="mr-1">{{ tag.icon }}</span>
      {{ tag.label }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const quickTags = [
  { id: 'near', label: 'Рядом со мной', icon: '🚶' },
  { id: 'today', label: 'Свободен сегодня', icon: '✅' },
  { id: 'home', label: 'Выезд на дом', icon: '🏠' },
  { id: 'verified', label: 'Проверенные', icon: '✓' },
  { id: 'premium', label: 'Премиум', icon: '⭐' },
  { id: 'new', label: 'Новые мастера', icon: '🆕' }
]

const activeTags = ref([])

const isActive = (tagId) => {
  return activeTags.value.includes(tagId)
}

const toggleTag = (tag) => {
  const index = activeTags.value.indexOf(tag.id)
  
  if (index > -1) {
    activeTags.value.splice(index, 1)
  } else {
    activeTags.value.push(tag.id)
  }
  
  // Применяем фильтры
  router.reload({
    data: { 
      quick_filters: activeTags.value 
    },
    preserveState: true,
    preserveScroll: true
  })
}
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>