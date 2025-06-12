<template>
  <div class="flex items-center">
    <Link :href="route('home')" class="flex items-center group">
      <!-- Логотип с анимацией -->
      <div class="relative">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center transform group-hover:scale-105 transition-transform">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" 
            />
          </svg>
        </div>
      </div>
      <!-- Название -->
      <div class="ml-3 hidden lg:block">
        <div class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
          SPA.COM
        </div>
        <div class="text-xs text-gray-500">
          Массаж и спа-услуги
        </div>
      </div>
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>