<template>
  <button 
    @click="$emit('toggle-catalog')"
    class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
  >
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M4 6h16M4 12h16M4 18h16" 
      />
    </svg>
    <span>Каталог</span>
  </button>
</template>

<script setup>
defineEmits(['toggle-catalog'])
</script>