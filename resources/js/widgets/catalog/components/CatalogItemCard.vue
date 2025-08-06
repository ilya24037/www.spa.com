<!-- 
  Карточка товара для каталога
  Компонент внутри изолированного виджета
-->
<template>
  <div 
    :class="cardClasses"
    @click="$emit('click')"
    role="button"
    tabindex="0"
    @keydown.enter="$emit('click')"
    @keydown.space.prevent="$emit('click')"
  >
    <!-- Изображение -->
    <div :class="imageClasses">
      <img 
        v-if="item.image"
        :src="item.image" 
        :alt="item.title"
        class="w-full h-full object-cover"
        loading="lazy"
      />
      <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center">
        <span class="text-gray-400 text-sm">Нет фото</span>
      </div>
      
      <!-- Промо значок -->
      <div v-if="isPromoted" class="absolute top-2 left-2">
        <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-medium">
          ⭐ Топ
        </span>
      </div>
      
      <!-- Рейтинг -->
      <div v-if="item.rating" class="absolute top-2 right-2">
        <span class="bg-black/50 text-white text-xs px-2 py-1 rounded-full">
          ★ {{ item.rating }}
        </span>
      </div>
    </div>

    <!-- Контент -->
    <div :class="contentClasses">
      <h3 :class="titleClasses">
        {{ item.title }}
      </h3>
      
      <p v-if="item.description" :class="descriptionClasses">
        {{ item.description }}
      </p>
      
      <!-- Мастер -->
      <button
        @click.stop="$emit('master-click')"
        class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-2 block"
      >
        {{ item.masterName }}
      </button>
      
      <!-- Локация -->
      <div v-if="item.location" class="flex items-center text-xs text-gray-500 mb-2">
        <span class="mr-1">📍</span>
        {{ item.location }}
      </div>
      
      <!-- Цена -->
      <div class="flex items-center justify-between">
        <span class="text-lg font-bold text-blue-600">
          {{ formatPrice(item.price) }}
        </span>
        
        <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors">
          Выбрать
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Карточка элемента каталога
 * Изолированный компонент внутри виджета
 */

import { computed } from 'vue'
import type { CatalogItem } from '../types/catalog.types'

interface Props {
  item: CatalogItem
  layout?: 'grid' | 'list'
  isPromoted?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  layout: 'grid',
  isPromoted: false
})

defineEmits<{
  'click': []
  'master-click': []
}>()

// === COMPUTED STYLES ===

const cardClasses = computed(() => [
  'bg-white rounded-lg border transition-all duration-200 cursor-pointer',
  'hover:shadow-lg hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500',
  {
    'flex': props.layout === 'list',
    'flex-col': props.layout === 'grid',
    'border-yellow-300 shadow-md': props.isPromoted
  }
])

const imageClasses = computed(() => [
  'relative overflow-hidden',
  {
    'w-32 h-32 flex-shrink-0 rounded-l-lg': props.layout === 'list',
    'w-full h-48 rounded-t-lg': props.layout === 'grid'
  }
])

const contentClasses = computed(() => [
  'p-4 flex-1',
  {
    'flex flex-col justify-between': props.layout === 'list'
  }
])

const titleClasses = computed(() => [
  'font-medium text-gray-900 mb-2',
  {
    'text-sm': props.layout === 'list',
    'text-base': props.layout === 'grid'
  }
])

const descriptionClasses = computed(() => [
  'text-gray-600 mb-3 line-clamp-2',
  {
    'text-xs': props.layout === 'list',
    'text-sm': props.layout === 'grid'
  }
])

// === UTILS ===

function formatPrice(price: number): string {
  return `${price.toLocaleString('ru-RU')} ₽`
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Анимации для карточек */
@keyframes pulse-promotion {
  0%, 100% { 
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
  }
  50% { 
    box-shadow: 0 0 0 8px rgba(245, 158, 11, 0);
  }
}

.border-yellow-300 {
  animation: pulse-promotion 2s infinite;
}
</style>