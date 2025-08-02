<template>
  <button 
    type="button" 
    :class="BUTTON_CLASSES"
    @click="handleClick"
    :disabled="disabled"
  >
    <svg :class="ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    <span v-if="text">{{ text }}</span>
  </button>
</template>

<script setup>
// 🎯 Стили согласно дизайн-системе
const BUTTON_CLASSES = 'inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed'
const ICON_CLASSES = 'w-4 h-4'

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false
  },
  to: {
    type: String,
    default: null
  },
  text: {
    type: String,
    default: 'Назад'
  }
})

const emit = defineEmits(['click'])

const handleClick = () => {
  if (props.disabled) return
  
  emit('click')
  
  // Простой подход как на Avito
  if (props.to) {
    window.location.href = props.to
  } else {
    // Простая проверка истории
    if (window.history.length > 1) {
      window.history.back()
    } else {
      // Fallback на главную
      window.location.href = '/'
    }
  }
}
</script>