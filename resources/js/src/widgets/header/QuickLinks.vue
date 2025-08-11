<!-- resources/js/src/widgets/header/QuickLinks.vue -->
<template>
  <nav class="flex flex-col gap-3">
    <Link 
      v-for="link in links"
      :key="link.href"
      :href="link.href" 
      class="text-gray-700 hover:text-blue-600 transition px-4 py-2 hover:bg-gray-50 rounded-lg"
    >
      {{ link.text }}
    </Link>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface QuickLink {
  href: string
  text: string
}

const links: QuickLink[] = [
  { href: '/masters', text: 'Все мастера' },
  { href: '/services/massage', text: 'Массаж' },
  { href: '/services/spa', text: 'СПА' },
  { href: '/services/cosmetology', text: 'Косметология' },
  { href: '/services/at-home', text: 'На дому' },
  { href: '/gift-certificates', text: 'Подарочные сертификаты' }
]
</script>