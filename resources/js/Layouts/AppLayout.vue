<!-- resources/js/Layouts/AppLayout.vue -->
<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <div class="max-w-[1400px] mx-auto bg-white min-h-screen shadow-xl">
      
      <!-- Шапка сайта - всегда одинаковая -->
      <header class="sticky top-0 z-50 bg-white shadow-md rounded-b-2xl h-16">
        <ErrorBoundary 
          error-title="Навигация временно недоступна"
          :show-reload="false"
        >
          <Navbar />
        </ErrorBoundary>
      </header>
      
      <!-- Основной контент -->
      <main class="site-wrapper flex-1">
        <!-- Только основной слот - без дополнительной логики -->
        <slot />
      </main>
      
      <!-- Футер - всегда одинаковый -->
      <Footer />
    </div>
    
    <!-- Глобальные уведомления -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import { provide } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Navbar from '@/Components/Header/Navbar.vue'
import Footer from '@/Components/Footer/Footer.vue'
import ErrorBoundary from '@/Components/Common/ErrorBoundary.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'

const page = usePage()

// Глобальные данные для всего приложения
provide('auth', page.props.auth)
provide('canLogin', page.props.canLogin)
provide('canRegister', page.props.canRegister)
</script>

<style scoped>
/* Единые отступы для всего контента */
.site-wrapper {
  @apply pt-4 px-4;
}

/* Больше отступов на больших экранах */
@screen lg {
  .site-wrapper {
    @apply px-6;
  }
}

/* Кастомный скроллбар */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  @apply bg-gray-100;
}

::-webkit-scrollbar-thumb {
  @apply bg-gray-300 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400;
}
</style>