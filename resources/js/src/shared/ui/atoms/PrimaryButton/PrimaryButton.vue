<template>
  <button
    :type="type"
    class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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