<!-- resources/js/Layouts/AppLayout.vue -->
<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <div class="max-w-[1400px] mx-auto bg-white min-h-screen shadow-xl">
      
      <!-- Шапка - только позиционирование -->
      <header class="sticky top-0 z-50 h-28">
        <ErrorBoundary 
          error-title="Навигация временно недоступна"
          :show-reload="false"
        >
          <Navbar />
        </ErrorBoundary>
      </header>
      
      <!-- Основной контент с динамическими классами -->
      <main 
        class="site-wrapper flex-1"
        :class="contentClasses"
      >
        <slot />
      </main>
      
      <!-- Футер -->
      <Footer />
    </div>
    
    <!-- Глобальные уведомления -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import { provide, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Navbar from '@/Components/Header/Navbar.vue'
import Footer from '@/Components/Footer/Footer.vue'
import ErrorBoundary from '@/Components/Common/ErrorBoundary.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'

const page = usePage()

// Глобальные данные
provide('auth', page.props.auth)
provide('canLogin', page.props.canLogin)
provide('canRegister', page.props.canRegister)

// Вычисляемые классы для контента
const contentClasses = computed(() => {
  // Получаем настройки layout из props страницы
  const layoutConfig = page.props.layoutConfig || {}
  
  return {
    // Модификаторы отступов
    'no-top-padding': layoutConfig.noTopPadding,
    'small-top-padding': layoutConfig.smallTopPadding,
    'large-top-padding': layoutConfig.largeTopPadding,
    'full-width': layoutConfig.fullWidth,
    'no-padding': layoutConfig.noPadding
  }
})
</script>

<style scoped>
/* Базовые отступы (по умолчанию) */
.site-wrapper {
  @apply pt-6 px-4; /* 24px сверху, 16px по бокам */
}

/* Адаптивные отступы для больших экранов */
@screen lg {
  .site-wrapper {
    @apply pt-8 px-6; /* 32px сверху, 24px по бокам */
  }
}

/* Модификаторы отступов */
.site-wrapper.no-top-padding {
  @apply pt-0;
}

.site-wrapper.small-top-padding {
  @apply pt-4 lg:pt-6; /* 16px → 24px */
}

.site-wrapper.large-top-padding {
  @apply pt-10 lg:pt-12; /* 40px → 48px */
}

.site-wrapper.full-width {
  @apply px-0;
}

.site-wrapper.no-padding {
  @apply p-0;
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