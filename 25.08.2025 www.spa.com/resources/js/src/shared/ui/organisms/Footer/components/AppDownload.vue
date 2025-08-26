<template>
  <div class="bg-white rounded-lg p-4 text-center shadow-sm border border-gray-500">
    <!-- QR код -->
    <div class="mb-4">
      <img 
        src="/images/qr-code.svg" 
        alt="QR код для скачивания мобильного приложения" 
        class="w-24 h-24 mx-auto"
        loading="lazy"
        @error="handleImageError"
      >
      <p class="text-sm text-gray-500 mt-3">
        Наведите камеру и скачайте приложение
      </p>
    </div>
    
    <!-- Ссылки на сторы -->
    <nav class="space-y-2" aria-label="Скачать приложение">
      <a
        v-for="store in appStores"
        :key="store.id"
        :href="store.href"
        target="_blank"
        rel="noopener noreferrer"
        class="block focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg transition-transform hover:scale-105"
        :aria-label="`${store.alt} - откроется в новой вкладке`"
      >
        <img 
          :src="store.image" 
          :alt="store.alt"
          class="h-10 mx-auto"
          loading="lazy"
          @error="handleStoreImageError($event, store)"
        >
      </a>
    </nav>

    <!-- Fallback для случая отсутствия QR кода -->
    <div 
      v-if="showFallback"
      class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg"
    >
      <p class="text-sm text-blue-700 font-medium mb-2">
        Скачайте наше приложение
      </p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <a
          v-for="store in appStores"
          :key="`fallback-${store.id}`"
          :href="store.href"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
        >
          {{ getStoreName(store.name) }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { AppStore } from '../model/footer.config'

interface Props {
  appStores: AppStore[]
}

defineProps<Props>()

const showFallback = ref(false)

// Обработчик ошибки загрузки QR кода
const handleImageError = () => {
    showFallback.value = true
}

// Обработчик ошибки загрузки иконок сторов
const handleStoreImageError = (event: Event, store: AppStore) => {
    const target = event.target as HTMLImageElement
    target.style.display = 'none'
  
    // Можно добавить fallback текст или заменить на дефолтную иконку
    const parent = target.parentNode as HTMLElement
    if (parent && !parent.querySelector('.store-fallback')) {
        const fallback = document.createElement('div')
        fallback.className = 'store-fallback bg-gray-500 h-10 flex items-center justify-center rounded text-sm text-gray-500'
        fallback.textContent = getStoreName(store.name)
        parent.appendChild(fallback)
    }
}

// Получение читаемого названия стора
const getStoreName = (storeName: 'appStore' | 'googlePlay'): string => {
    return storeName === 'appStore' ? 'App Store' : 'Google Play'
}
</script>

<style scoped>
/* Анимация hover эффекта */
.hover\:scale-105:hover {
  transform: scale(1.05);
}

.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Focus состояния для accessibility */
.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

/* Адаптивность для мобильных */
@media (max-width: 640px) {
  .w-24 {
    width: 5rem;
    height: 5rem;
  }
  
  .h-10 {
    height: 2rem;
  }
}
</style>