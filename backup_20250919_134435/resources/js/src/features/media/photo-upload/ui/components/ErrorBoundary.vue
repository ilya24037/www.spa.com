<!-- Error Boundary для PhotoUpload -->
<template>
  <div 
    v-if="hasError" 
    class="error-boundary rounded-lg border-2 border-red-200 bg-red-50 p-6"
    role="alert"
    aria-live="assertive"
  >
    <h3 class="text-red-600 font-medium mb-2">
      Произошла ошибка при загрузке фотографий
    </h3>
    <p class="text-red-500 text-sm mb-4">
      {{ errorMessage }}
    </p>
    <button 
      @click="reset"
      class="px-4 py-2 bg-white border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
      aria-label="Попробовать снова загрузить фотографии"
    >
      Попробовать снова
    </button>
  </div>
  <slot v-else />
</template>

<script setup lang="ts">
import { ref, onErrorCaptured } from 'vue'

const hasError = ref(false)
const errorMessage = ref('')

const reset = () => {
  hasError.value = false
  errorMessage.value = ''
}

onErrorCaptured((error) => {
  hasError.value = true
  errorMessage.value = error.message || 'Неизвестная ошибка'
  console.error('PhotoUpload Error:', error)
  return false // предотвращаем всплытие
})
</script>