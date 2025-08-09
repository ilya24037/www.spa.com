<template>
  <!-- РЎРµРјР°РЅС‚РёС‡РµСЃРєР°СЏ РІРµСЂСЃС‚РєР° - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ… -->
  <article
    :class="cardClasses"
    role="article"
    :aria-label="title"
  >
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ… -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-48 bg-gray-500 rounded-t-lg" />
      <div class="p-4 space-y-3">
        <div class="h-4 bg-gray-500 rounded w-3/4" />
        <div class="h-4 bg-gray-500 rounded w-1/2" />
      </div>
    </div>

    <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ… -->
    <div v-else-if="error" class="p-6 text-center">
      <div class="text-red-500 mb-2">
        <svg
          class="w-12 h-12 mx-auto"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2" 
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>
      <p class="text-gray-500">
        {{ error }}
      </p>
    </div>

    <!-- Content - v-if Р·Р°С‰РёС‚Р° РѕС‚ undefined - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ… -->
    <div v-else>
      <div v-if="image" class="relative overflow-hidden rounded-t-lg">
        <!-- РћРїС‚РёРјРёР·Р°С†РёСЏ РёР·РѕР±СЂР°Р¶РµРЅРёР№ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ… -->
        <img
          :src="image"
          :alt="imageAlt || title"
          loading="lazy"
          class="w-full h-48 object-cover"
        >
      </div>
      
      <div class="p-4 sm:p-6">
        <h3 v-if="title" class="text-lg font-semibold text-gray-500 mb-2">
          {{ title }}
        </h3>
        
        <p v-if="description" class="text-gray-500 text-sm mb-4">
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

// TypeScript С‚РёРїРёР·Р°С†РёСЏ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
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

// Default Р·РЅР°С‡РµРЅРёСЏ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
const props = withDefaults(defineProps<Props>(), {
    loading: false,
    error: '',
    variant: 'default',
    className: ''
})

// РњРѕР±РёР»СЊРЅР°СЏ Р°РґР°РїС‚РёРІРЅРѕСЃС‚СЊ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
const cardClasses = computed(() => [
    'bg-white rounded-lg transition-all',
    {
        'shadow-sm hover:shadow-md': props.variant === 'default',
        'border border-gray-500': props.variant === 'outlined',
        'shadow-lg': props.variant === 'elevated'
    },
    props.className
])
</script>
