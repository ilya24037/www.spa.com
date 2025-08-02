<!-- resources/js/src/features/masters-filter/ui/FilterSearch/FilterSearch.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <label :class="LABEL_CLASSES">Поиск</label>
    <div :class="INPUT_CONTAINER_CLASSES">
      <svg :class="SEARCH_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <input
        :value="value"
        @input="$emit('update', $event.target.value)"
        type="text"
        :placeholder="placeholder"
        :class="INPUT_CLASSES"
      >
      <button
        v-if="value"
        @click="$emit('update', '')"
        :class="CLEAR_BUTTON_CLASSES"
      >
        <svg :class="CLEAR_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-2'
const LABEL_CLASSES = 'text-sm font-medium text-gray-700'
const INPUT_CONTAINER_CLASSES = 'relative'
const SEARCH_ICON_CLASSES = 'absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400'
const INPUT_CLASSES = 'w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
const CLEAR_BUTTON_CLASSES = 'absolute right-3 top-1/2 -translate-y-1/2 p-0.5 hover:bg-gray-100 rounded'
const CLEAR_ICON_CLASSES = 'w-4 h-4 text-gray-400 hover:text-gray-600'

const props = defineProps({
  value: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Поиск мастеров...'
  }
})

defineEmits(['update'])
</script>