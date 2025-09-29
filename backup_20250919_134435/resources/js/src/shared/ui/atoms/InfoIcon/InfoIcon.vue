<template>
  <svg 
    xmlns="http://www.w3.org/2000/svg" 
    :width="size" 
    :height="size" 
    viewBox="0 0 24 24"
    :class="['info-icon', { 'info-icon--clickable': clickable }]"
    :style="{ color: currentColor }"
    @click="handleClick"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <path 
      fill="currentColor" 
      d="M12 21c5.584 0 9-3.416 9-9s-3.416-9-9-9-9 3.416-9 9 3.416 9 9 9m1-13a1 1 0 1 1-2 0 1 1 0 0 1 2 0m-2 4a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0z"
    />
  </svg>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  size?: number | string
  color?: string
  hoverColor?: string
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 20,
  color: 'rgba(0, 26, 52, 0.2)',
  hoverColor: 'rgba(0, 26, 52, 0.4)',
  clickable: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const isHovered = ref(false)

const currentColor = computed(() => {
  return isHovered.value && props.hoverColor ? props.hoverColor : props.color
})

const handleClick = (event: MouseEvent) => {
  if (props.clickable) {
    emit('click', event)
  }
}
</script>

<style scoped>
.info-icon {
  transition: color 0.2s ease;
  display: inline-block;
  vertical-align: middle;
}

.info-icon--clickable {
  cursor: pointer;
}
</style>