<!-- РљРЅРѕРїРєР° СѓРІРµРґРѕРјР»РµРЅРёР№ -->
<template>
  <div class="relative">
    <button
      @click="handleClick"
      :class="buttonClasses"
      :aria-label="buttonAriaLabel"
      :aria-expanded="false"
    >
      <!-- РРєРѕРЅРєР° РєРѕР»РѕРєРѕР»СЊС‡РёРєР° -->
      <svg
        class="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>

      <!-- РЎС‡РµС‚С‡РёРє СѓРІРµРґРѕРјР»РµРЅРёР№ -->
      <span
        v-if="count > 0"
        :class="badgeClasses"
        :aria-label="`${count} РЅРµРїСЂРѕС‡РёС‚Р°РЅРЅС‹С… СѓРІРµРґРѕРјР»РµРЅРёР№`"
      >
        {{ displayCount }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃ РґР»СЏ props
interface Props {
  count?: number
  maxCount?: number
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  count: 0,
  maxCount: 99,
  disabled: false
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'click': []
}>()

// Computed properties
const displayCount = computed(() => {
  if (props.count > props.maxCount) {
    return `${props.maxCount}+`
  }
  return props.count.toString()
})

const buttonClasses = computed(() => [
  'relative p-2 rounded-lg transition-colors duration-200 text-gray-600',
  'hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    'opacity-50 cursor-not-allowed': props.disabled,
    'text-blue-600': props.count > 0 && !props.disabled
  }
])

const badgeClasses = computed(() => [
  'absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full',
  'px-1.5 ring-2 ring-white'
])

const buttonAriaLabel = computed(() => {
  if (props.count === 0) {
    return 'РЈРІРµРґРѕРјР»РµРЅРёСЏ'
  }
  return `РЈРІРµРґРѕРјР»РµРЅРёСЏ (${props.count} РЅРµРїСЂРѕС‡РёС‚Р°РЅРЅС‹С…)`
})

// Methods
const handleClick = (): void => {
  if (!props.disabled) {
    emit('click')
  }
}
</script>

<style scoped>
/* РђРЅРёРјР°С†РёСЏ РїРѕСЏРІР»РµРЅРёСЏ Р±РµР№РґР¶Р° */
.badge-enter-active,
.badge-leave-active {
  transition: all 0.2s ease;
}

.badge-enter-from,
.badge-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

/* Pulse Р°РЅРёРјР°С†РёСЏ РґР»СЏ РЅРѕРІС‹С… СѓРІРµРґРѕРјР»РµРЅРёР№ */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.notification-pulse {
  animation: pulse 2s infinite;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}
</style>

