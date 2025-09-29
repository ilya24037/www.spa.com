<!-- Кнопка каталога -->
<template>
  <button 
    @click="$emit('toggle')"
    class="flex items-center gap-2 h-10 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-colors duration-200 font-medium"
    :class="{ 'bg-blue-800': isOpen }"
  >
    <svg v-if="!isOpen"
         class="w-5 h-5 flex-shrink-0"
         fill="none"
         stroke="currentColor"
         viewBox="0 0 24 24">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
    <svg v-else
         class="w-5 h-5 flex-shrink-0"
         fill="none"
         stroke="currentColor"
         viewBox="0 0 24 24">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"/>
    </svg>
    <span class="text-sm whitespace-nowrap">Каталог</span>
  </button>
</template>

<script setup lang="ts">
interface Props {
  isOpen?: boolean
}

withDefaults(defineProps<Props>(), {
  isOpen: false
})

defineEmits<{
  toggle: []
}>()
</script>