<template>
  <div v-if="section" class="footer-section">
    <!-- Заголовок секции -->
    <h3 class="font-semibold mb-4 text-gray-900">
      {{ section.title }}
    </h3>
    
    <!-- Список ссылок -->
    <nav role="navigation" :aria-label="`Навигация по разделу ${section.title}`">
      <ul class="space-y-2 text-sm">
        <li
          v-for="link in visibleLinks"
          :key="link.id"
        >
          <Link
            v-if="!isExternalLink(link.href)"
            :href="link.href"
            class="text-gray-600 hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md px-1 py-0.5 -mx-1"
            :title="link.description || link.text"
          >
            {{ link.text }}
          </Link>
          
          <a
            v-else
            :href="link.href"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1 text-gray-600 hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md px-1 py-0.5 -mx-1"
            :title="link.description || link.text"
          >
            {{ link.text }}
            <svg
              v-if="link.external"
              class="w-3 h-3 ml-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              />
            </svg>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { FooterSection as FooterSectionType } from '../model/footer.config'
import { filterVisibleLinks, isExternalLink } from '../model/footer.config'

interface Props {
  section: FooterSectionType
}

const props = defineProps<Props>()

// Фильтруем только видимые ссылки
const visibleLinks = computed(() => {
  return filterVisibleLinks(props.section.links)
})
</script>

<style scoped>
.footer-section {
  @apply w-full;
}

/* Анимации для ссылок */
.transition-colors {
  transition-property: color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Фокус состояния */
.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

/* Улучшение доступности для мобильных */
@media (max-width: 640px) {
  .footer-section h3 {
    @apply text-base;
  }
  
  .footer-section ul {
    @apply space-y-3;
  }
}
</style>
