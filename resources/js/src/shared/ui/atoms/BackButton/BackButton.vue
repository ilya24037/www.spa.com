<template>
  <button
    class="inline-flex items-center text-gray-500 hover:text-gray-500 transition-colors"
    @click="handleBack"
  >
    <svg
      class="w-5 h-5 mr-2"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M10 19l-7-7m0 0l7-7m-7 7h18"
      />
    </svg>
    <span>{{ text || 'Назад' }}</span>
  </button>
</template>

<script setup lang="ts">
defineProps<{
  text?: string
}>()

const handleBack = () => {
    if (window.history.length > 1) {
        window.history.back()
    } else {
        window.location.href = '/'
    }
}
</script>