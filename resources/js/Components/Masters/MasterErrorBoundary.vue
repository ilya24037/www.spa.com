<template>
  <div v-if="hasError" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
    <div class="flex items-center justify-center mb-4">
      <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
    </div>
    <h3 class="text-lg font-semibold text-red-800 mb-2">{{ errorTitle }}</h3>
    <p class="text-red-600 mb-4">{{ errorMessage }}</p>
    <div class="flex justify-center gap-4">
      <button 
        @click="retry" 
        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
      >
        Попробовать ещё раз
      </button>
      <Link href="/masters" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
        Вернуться к каталогу
      </Link>
    </div>
  </div>
  
  <slot v-else />
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  errorTitle: {
    type: String,
    default: 'Произошла ошибка'
  },
  errorMessage: {
    type: String,
    default: 'Не удалось загрузить информацию о мастере'
  }
})

const hasError = ref(false)
const emit = defineEmits(['retry'])

const showError = () => {
  hasError.value = true
}

const retry = () => {
  hasError.value = false
  emit('retry')
}

defineExpose({ showError })
</script>