<template>
  <svg
    v-if="name"
    :class="iconClasses"
    :width="size"
    :height="size"
    fill="currentColor"
    viewBox="0 0 24 24"
    :aria-label="ariaLabel"
    role="img"
  >
    <!-- Р—Р°С‰РёС‚Р° РѕС‚ undefined -->
    <use v-if="iconPath" :href="iconPath" />
    <path v-else d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z" />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// TypeScript С‚РёРїРёР·Р°С†РёСЏ props - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
interface Props {
  name?: string
  size?: number | string
  color?: string
  className?: string
  ariaLabel?: string
}

// Default Р·РЅР°С‡РµРЅРёСЏ РґР»СЏ РІСЃРµС… РѕРїС†РёРѕРЅР°Р»СЊРЅС‹С… props - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
const props = withDefaults(defineProps<Props>(), {
  size: 24,
  color: 'currentColor',
  className: '',
  ariaLabel: 'Icon'
})

// Computed РґР»СЏ Р·Р°С‰РёС‚С‹ РѕС‚ undefined - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
const iconClasses = computed(() => [
  'inline-block',
  props.className
].filter(Boolean).join(' '))

const iconPath = computed(() => props.name ? `#icon-${props.name}` : null)
</script>
