<!-- resources/js/Components/Header/QuickLinks.vue -->
<template>
  <nav class="flex items-center gap-6 text-sm">
    <Link 
      v-for="link in links"
      :key="link.href"
      :href="link.href" 
      class="text-gray-700 hover:text-blue-600 transition"
    >
      {{ link.text }}
    </Link>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const links = [
  { href: '/masters', text: 'Все мастера' },
  { href: '/services/massage', text: 'Массаж' },
  { href: '/services/spa', text: 'СПА' },
  { href: '/services/cosmetology', text: 'Косметология' },
  { href: '/services/at-home', text: 'На дому' },
  { href: '/gift-certificates', text: 'Сертификаты' }
]
</script>