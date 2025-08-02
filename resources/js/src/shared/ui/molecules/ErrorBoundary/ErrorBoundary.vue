<template>
  <div>
    <!-- Если есть ошибка, показываем заглушку -->
    <div v-if="hasError" class="p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex items-center gap-2 text-red-600 mb-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="font-medium">{{ errorTitle }}</span>
      </div>
      <p class="text-sm text-gray-600">{{ errorMessage }}</p>
      
      <!-- Кнопка перезагрузки -->
      <button 
        v-if="showReload"
        @click="reload"
        class="mt-3 text-sm text-blue-600 hover:text-blue-700"
      >
        Попробовать снова
      </button>
    </div>
    
    <!-- Если ошибки нет, показываем контент -->
    <div v-else>
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue'

const props = defineProps({
  errorTitle: {
    type: String,
    default: 'Произошла ошибка'
  },
  errorMessage: {
    type: String,
    default: 'Не удалось загрузить компонент. Попробуйте обновить страницу.'
  },
  showReload: {
    type: Boolean,
    default: true
  },
  fallback: {
    type: Object,
    default: null
  }
})

const hasError = ref(false)

// Перехватываем ошибки в дочерних компонентах
onErrorCaptured((err, instance, info) => {
  console.error('Ошибка в компоненте:', err, info)
  hasError.value = true
  
  // Останавливаем распространение ошибки
  return false
})

// Перезагрузка компонента
const reload = () => {
  hasError.value = false
}
</script>