<template>
  <div class="min-h-screen bg-gray-100">
    <div class="max-w-[1400px] mx-auto bg-white min-h-screen shadow-sm">
      <!-- Шапка с защитой от ошибок -->
      <ErrorBoundary 
        error-title="Ошибка загрузки шапки"
        error-message="Навигация временно недоступна"
      >
        <header class="sticky top-0 z-50 bg-white border-b">
          <Navbar />
        </header>
      </ErrorBoundary>
      
      <!-- Основной контент -->
      <main>
        <slot />
      </main>
      
      <!-- Футер с защитой от ошибок -->
      <ErrorBoundary 
        v-if="!hideFooter"
        error-title="Ошибка загрузки футера"
        error-message="Подвал сайта временно недоступен"
        :show-reload="false"
      >
        <Footer />
      </ErrorBoundary>
    </div>
    
    <!-- Уведомления -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import Navbar from '@/Components/Header/Navbar.vue'
import Footer from '@/Components/Footer/Footer.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'
import ErrorBoundary from '@/Components/Common/ErrorBoundary.vue'

defineProps({
    hideFooter: {
        type: Boolean,
        default: false
    }
})

// Глобальная обработка ошибок
onMounted(() => {
  window.addEventListener('unhandledrejection', (event) => {
    console.error('Необработанная ошибка Promise:', event.reason)
    // Можно отправить на сервер или показать уведомление
  })
})
</script>