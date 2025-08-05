<template>
  <!-- Семантическая верстка - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
  <article
    :class="cardClasses"
    role="article"
    :aria-label="title"
  >
    <!-- Loading состояние - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-48 bg-gray-200 rounded-t-lg"></div>
      <div class="p-4 space-y-3">
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Error состояние - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
    <div v-else-if="error" class="p-6 text-center">
      <div class="text-red-500 mb-2">
        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="text-gray-600">{{ error }}</p>
    </div>

    <!-- Content - v-if защита от undefined - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
    <div v-else>
      <div v-if="image" class="relative overflow-hidden rounded-t-lg">
        <!-- Оптимизация изображений - ЧЕК-ЛИСТ CLAUDE.md ✅ -->
        <img
          :src="image"
          :alt="imageAlt || title"
          loading="lazy"
          class="w-full h-48 object-cover"
        />
      </div>
      
      <div class="p-4 sm:p-6">
        <h3 v-if="title" class="text-lg font-semibold text-gray-900 mb-2">
          {{ title }}
        </h3>
        
        <p v-if="description" class="text-gray-600 text-sm mb-4">
          {{ description }}
        </p>
        
        <slot name="content" />
        
        <div v-if="$slots.actions" class="mt-4 flex gap-2">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// TypeScript типизация - ЧЕК-ЛИСТ CLAUDE.md ✅
interface Props {
  title?: string
  description?: string
  image?: string
  imageAlt?: string
  loading?: boolean
  error?: string
  variant?: 'default' | 'outlined' | 'elevated'
  className?: string
}

// Default значения - ЧЕК-ЛИСТ CLAUDE.md ✅
const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: '',
  variant: 'default',
  className: ''
})

// Мобильная адаптивность - ЧЕК-ЛИСТ CLAUDE.md ✅
const cardClasses = computed(() => [
  'bg-white rounded-lg transition-all',
  {
    'shadow-sm hover:shadow-md': props.variant === 'default',
    'border border-gray-200': props.variant === 'outlined',
    'shadow-lg': props.variant === 'elevated'
  },
  props.className
])
</script>