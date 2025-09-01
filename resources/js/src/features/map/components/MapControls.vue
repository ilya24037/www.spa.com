<template>
  <div class="map-controls">
    <!-- Поиск -->
    <div v-if="showSearch" class="map-controls__search">
      <input
        v-model="searchQuery"
        @keyup.enter="handleSearch"
        type="text"
        placeholder="Поиск места..."
        class="map-controls__search-input"
      />
      <button 
        @click="handleSearch"
        class="map-controls__search-button"
        :disabled="!searchQuery"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" 
          />
        </svg>
      </button>
    </div>

    <!-- Геолокация -->
    <button
      v-if="showGeolocation"
      @click="$emit('geolocation-click')"
      class="map-controls__button map-controls__button--geolocation"
      title="Моё местоположение"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" 
        />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" 
        />
      </svg>
    </button>

    <!-- Дополнительные контролы -->
    <slot />
  </div>
</template>

<script setup lang="ts">
/**
 * MapControls - UI контролы карты
 * Поиск, геолокация и другие кнопки управления
 * Размер: 50 строк
 */
import { ref } from 'vue'

interface Props {
  showSearch?: boolean
  showGeolocation?: boolean
}

withDefaults(defineProps<Props>(), {
  showSearch: false,
  showGeolocation: false
})

const emit = defineEmits<{
  'search': [query: string]
  'geolocation-click': []
}>()

const searchQuery = ref('')

function handleSearch() {
  if (searchQuery.value.trim()) {
    emit('search', searchQuery.value.trim())
  }
}
</script>

<style lang="scss">
.map-controls {
  display: flex;
  gap: 0.5rem;
  
  &__search {
    display: flex;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    overflow: hidden;
  }
  
  &__search-input {
    padding: 0.5rem 1rem;
    border: none;
    outline: none;
    min-width: 200px;
    font-size: 0.875rem;
    
    &::placeholder {
      color: #9ca3af;
    }
  }
  
  &__search-button {
    padding: 0.5rem;
    background: transparent;
    border: none;
    border-left: 1px solid #e5e7eb;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    
    &:hover:not(:disabled) {
      background: #f3f4f6;
      color: #1f2937;
    }
    
    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }
  
  &__button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: white;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    
    &:hover {
      background: #f3f4f6;
      color: #1f2937;
      transform: scale(1.05);
    }
    
    &:active {
      transform: scale(0.95);
    }
    
    &--geolocation {
      color: #3b82f6;
      
      &:hover {
        color: #2563eb;
      }
    }
  }
  
  // Mobile styles
  @media (max-width: 640px) {
    &__search {
      flex: 1;
    }
    
    &__search-input {
      min-width: auto;
      width: 100%;
    }
  }
}
</style>