<template>
  <Modal :show="show" @close="handleCancel" :max-width="maxWidth">
    <div class="p-6">
      <!-- Заголовок -->
      <div class="flex items-center mb-4">
        <div v-if="type === 'danger'" class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <div v-else-if="type === 'warning'" class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <div v-else class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ title }}
          </h3>
        </div>
      </div>

      <!-- Содержимое -->
      <div class="text-sm text-gray-600 mb-6">
        <slot>
          {{ message }}
        </slot>
      </div>

      <!-- Кнопки -->
      <div class="flex justify-end space-x-3">
        <button
          @click="handleCancel"
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
        >
          {{ cancelText }}
        </button>
        <button
          @click="handleConfirm"
          type="button"
          :class="confirmButtonClasses"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed } from 'vue'
import Modal from '../Modal/Modal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Подтверждение'
  },
  message: {
    type: String,
    default: 'Вы уверены?'
  },
  confirmText: {
    type: String,
    default: 'Подтвердить'
  },
  cancelText: {
    type: String,
    default: 'Отмена'
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'warning', 'danger'].includes(value)
  },
  maxWidth: {
    type: String,
    default: 'md'
  }
})

const emit = defineEmits(['confirm', 'cancel', 'close'])

const confirmButtonClasses = computed(() => {
  const base = 'px-4 py-2 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2'
  
  if (props.type === 'danger') {
    return `${base} text-white bg-red-600 hover:bg-red-700 focus:ring-red-500`
  }
  
  if (props.type === 'warning') {
    return `${base} text-white bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500`
  }
  
  return `${base} text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500`
})

const handleConfirm = () => {
  emit('confirm')
  emit('close')
}

const handleCancel = () => {
  emit('cancel')
  emit('close')
}
</script>