<template>
  <!-- Серый фон на всю страницу -->
  <div class="min-h-screen bg-gray-100 flex flex-col">
    
    <!-- Контейнер с фиксированной шириной для всего сайта -->
    <div class="max-w-[1400px] mx-auto min-h-screen flex flex-col w-full">
      
      <!-- НОВОЕ: Единая обертка с отступами для ВСЕГО контента -->
      <div class="site-padding flex-1">
        
        <!-- Шапка с компенсацией отступов -->
        <header class="sticky top-0 z-50 negative-margin">
          <div class="site-padding">
            <ErrorBoundary 
              error-title="Навигация временно недоступна"
              :show-reload="false"
            >
              <Navbar />
            </ErrorBoundary>
          </div>
        </header>
        
        <!-- Основной контент уже имеет отступы от обертки -->
        <main class="flex-1">
          <slot />
        </main>
      </div>
      
      <!-- НОВОЕ: Подвал сайта -->
      <Footer />
    </div>
         
    <!-- Глобальные уведомления (вне контейнера) -->
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

// Глобальные данные
provide('auth', page.props.auth)
provide('canLogin', page.props.canLogin)
provide('canRegister', page.props.canRegister)

// Provide для sticky top (высота шапки)
provide('stickyTop', 112) // высота двухуровневой шапки
</script>

<style scoped>
/* Единые отступы для всего сайта */
.site-padding {
  @apply px-4 lg:px-6;
}

/* Компенсация отступов для элементов на всю ширину */
.negative-margin {
  margin-left: -1rem;
  margin-right: -1rem;
}

@media (min-width: 1024px) {
  .negative-margin {
    margin-left: -1.5rem;
    margin-right: -1.5rem;
  }
}
</style>