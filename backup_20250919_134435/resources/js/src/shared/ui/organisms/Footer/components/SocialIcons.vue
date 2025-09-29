<template>
  <div class="flex gap-2" role="list" aria-label="Социальные сети">
    <a
      v-for="social in socialLinks"
      :key="social.id"
      :href="social.href"
      target="_blank"
      rel="noopener noreferrer"
      class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-500 focus:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors border border-gray-500"
      :aria-label="`Перейти в ${social.name}`"
      :title="social.name"
      role="listitem"
    >
      <SocialIcon
        :name="social.icon"
        :color="social.color"
        class="w-5 h-5"
        :aria-hidden="true"
      />
    </a>
  </div>
</template>

<script setup lang="ts">
import type { SocialLink } from '../model/footer.config'
import SocialIcon from './SocialIcon.vue'

interface Props {
  socialLinks: SocialLink[]
}

defineProps<Props>()
</script>

<style scoped>
/* Анимации для социальных иконок */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Hover эффекты */
.hover\:bg-gray-500:hover {
  background-color: #f3f4f6;
}

/* Focus эффекты для доступности */
.focus\:bg-gray-500:focus {
  background-color: #f3f4f6;
}

.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

/* Адаптивность */
@media (max-width: 640px) {
  .w-10 {
    width: 2.75rem;
    height: 2.75rem;
  }
}
</style>