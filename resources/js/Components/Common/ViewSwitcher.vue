<template>
  <div class="flex items-center border rounded-lg bg-white">
    <button 
      @click="updateValue(false)"
      :class="[
        'p-2 transition-colors',
        !modelValue ? 'bg-gray-100' : 'hover:bg-gray-50'
      ]"
      title="Сетка"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
      </svg>
    </button>
    <div class="w-px h-6 bg-gray-200"></div>
    <button 
      @click="updateValue(true)"
      :class="[
        'p-2 transition-colors',
        modelValue ? 'bg-gray-100' : 'hover:bg-gray-50'
      ]"
      title="На карте"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
      </svg>
    </button>
  </div>
</template>

<script setup>
const props = defineProps(['modelValue'])
const emit = defineEmits(['update:modelValue'])

const updateValue = (value) => {
  try {
    emit('update:modelValue', value)
  } catch (error) {
    console.error('Ошибка при переключении вида:', error)
  }
}
</script>