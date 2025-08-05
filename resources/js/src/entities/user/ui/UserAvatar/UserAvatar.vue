<template>
  <div
    :class="avatarClasses"
    :style="avatarStyles"
    role="img"
    :aria-label="ariaLabel"
  >
    <!-- Image avatar -->
    <img
      v-if="src && !imageError"
      :src="src"
      :alt="name || 'Avatar'"
      @error="handleImageError"
      class="w-full h-full object-cover"
    />
    
    <!-- Fallback: initials -->
    <span v-else class="font-semibold text-white">
      {{ initials }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  src?: string
  name?: string
  size?: number | string
  color?: string
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 40,
  color: '#3B82F6',
  ariaLabel: 'User avatar'
})

const imageError = ref(false)

const handleImageError = () => {
  imageError.value = true
}

const initials = computed(() => {
  if (!props.name) return '?'
  const names = props.name.trim().split(' ')
  if (names.length >= 2) {
    return names[0][0] + names[names.length - 1][0]
  }
  return props.name.substring(0, 2).toUpperCase()
})

const avatarClasses = computed(() => [
  'rounded-full overflow-hidden flex items-center justify-center',
  'bg-gray-300'
])

const avatarStyles = computed(() => ({
  width: typeof props.size === 'number' ? `${props.size}px` : props.size,
  height: typeof props.size === 'number' ? `${props.size}px` : props.size,
  backgroundColor: !props.src || imageError.value ? props.color : undefined
}))
</script>