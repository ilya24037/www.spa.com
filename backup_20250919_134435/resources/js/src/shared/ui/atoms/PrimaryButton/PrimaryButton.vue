<template>
  <button
    :type="type"
    class="px-4 py-2 bg-gray-700 text-white font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
interface Props {
  type?: 'submit' | 'button' | 'reset'
}

withDefaults(defineProps<Props>(), {
    type: 'submit'
})
</script>