<template>
  <div class="mb-3 border rounded-xl overflow-visible" :class="[
    isRequired ? 'border-blue-500' : 'border-gray-200',
    isFilled ? 'border-green-500' : '',
    hasErrors ? 'border-red-500' : ''
  ]">
    <div 
      class="px-5 py-4 bg-gray-50/80 cursor-pointer flex justify-between items-center select-none transition-colors duration-200 rounded-t-xl hover:bg-gray-100/90"
      :class="{ 'bg-blue-50/90': isOpen }"
      @click="$emit('toggle')"
    >
      <div class="flex items-center">
        <span class="text-xl font-bold text-gray-900 sm:text-2xl">
          {{ title }}
          <span v-if="isRequired" class="text-red-500 ml-1 text-2xl">*</span>
        </span>
      </div>
      
      <div class="flex items-center gap-3">
        <span v-if="filledCount !== undefined" class="text-sm text-gray-500 px-2 py-1 rounded">
          {{ filledCount }}/{{ totalCount }}
        </span>
        <span class="w-6 h-6 flex items-center justify-center">
          <span v-if="isFilled" class="text-green-500 text-lg font-bold">✓</span>
          <span v-else-if="hasErrors" class="text-amber-500 text-lg">⚠</span>
          <span v-else class="text-gray-300 text-lg">○</span>
        </span>
        <span class="text-xs text-gray-500 transition-transform duration-300 w-4 text-center ml-2">
          {{ isOpen ? '▼' : '▶' }}
        </span>
      </div>
    </div>
    
    <transition name="collapse">
      <div v-show="isOpen" class="p-0 rounded-b-xl relative overflow-visible min-h-[100px] bg-white">
        <slot></slot>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  isOpen?: boolean
  isRequired?: boolean
  isFilled?: boolean
  hasErrors?: boolean
  filledCount?: number
  totalCount?: number | string
}

withDefaults(defineProps<Props>(), {
  isOpen: false,
  isRequired: false,
  isFilled: false,
  hasErrors: false
})

defineEmits<{
  toggle: []
}>()
</script>

<style scoped>
/* Анимация сворачивания/разворачивания секций */
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.2s ease;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
}

/* Белый фон для секций */
.collapse-enter-active,
.collapse-leave-active {
  background: white !important;
}
</style>