<template>
  <div class="map-states">
    <!-- Loading состояние -->
    <div v-if="loading" class="map-states__loading">
      <div class="map-states__spinner">
        <svg class="animate-spin h-8 w-8 text-blue-600" viewBox="0 0 24 24">
          <circle 
            class="opacity-25" 
            cx="12" 
            cy="12" 
            r="10" 
            stroke="currentColor" 
            stroke-width="4"
          />
          <path 
            class="opacity-75" 
            fill="currentColor" 
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
      </div>
      <p class="map-states__loading-text">
        {{ loadingText }}
      </p>
    </div>

    <!-- Error состояние -->
    <div v-else-if="error" class="map-states__error">
      <div class="map-states__error-icon">
        <svg class="w-12 h-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" 
          />
        </svg>
      </div>
      <h3 class="map-states__error-title">{{ errorTitle }}</h3>
      <p class="map-states__error-message">{{ error }}</p>
      <button 
        @click="$emit('retry')"
        class="map-states__retry-button"
      >
        Попробовать снова
      </button>
    </div>

    <!-- Content (ВСЕГДА рендерим, чтобы MapCore мог начать загрузку) -->
    <div class="map-states__content" :class="{ 'map-states__content--hidden': loading || error }">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue'

/**
 * MapStates - управление состояниями карты
 * Показывает loading, error или content
 * Размер: 40 строк
 */
interface Props {
  loading?: boolean
  error?: string | null
  loadingText?: string
  errorTitle?: string
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: null,
  loadingText: 'Загрузка карты...',
  errorTitle: 'Ошибка загрузки карты'
})

defineEmits<{
  retry: []
}>()

// Логирование состояний
onMounted(() => {
  console.log('[MapStates] 🚀 MapStates компонент смонтирован')
  console.log('[MapStates] 📋 Состояние:', {
    loading: props.loading,
    error: props.error,
    loadingText: props.loadingText
  })
})

watch(() => props.loading, (newValue) => {
  console.log('[MapStates] ⏳ loading изменился на:', newValue)
})

watch(() => props.error, (newValue) => {
  console.log('[MapStates] ❌ error изменился на:', newValue)
})
</script>

<style lang="scss">
.map-states {
  position: relative;
  width: 100%;
  height: 100%;
  
  &__loading,
  &__error {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.95);
    z-index: 1000;
  }
  
  &__spinner {
    margin-bottom: 1rem;
  }
  
  &__loading-text {
    color: #6b7280;
    font-size: 0.875rem;
  }
  
  &__error-icon {
    margin-bottom: 1rem;
  }
  
  &__error-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
  }
  
  &__error-message {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    text-align: center;
    max-width: 300px;
  }
  
  &__retry-button {
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.2s;
    
    &:hover {
      background: #2563eb;
    }
    
    &:active {
      transform: scale(0.98);
    }
  }
  
  &__content {
    width: 100%;
    height: 100%;
    
    &--hidden {
      visibility: hidden;
      position: absolute;
      z-index: -1;
    }
  }
}
</style>