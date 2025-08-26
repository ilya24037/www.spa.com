<!-- resources/js/src/widgets/footer/FooterCopyright.vue -->
<template>
  <div class="footer-copyright">
    <div class="flex flex-col md:flex-row items-center justify-between py-4 border-t border-gray-500">
      <!-- Копирайт -->
      <div class="text-sm text-gray-500 mb-2 md:mb-0">
        © {{ currentYear }} SPA.COM. Все права защищены.
      </div>
      
      <!-- Политики -->
      <div class="flex items-center space-x-6 text-sm">
        <Link href="/privacy" class="text-gray-500 hover:text-blue-600">
          Политика конфиденциальности
        </Link>
        <Link href="/terms" class="text-gray-500 hover:text-blue-600">
          Условия использования
        </Link>
        <Link href="/cookies" class="text-gray-500 hover:text-blue-600">
          Файлы cookie
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const currentYear = computed(() => new Date().getFullYear())
</script> 