<template>
   
<!-- Серый фон на всю страницу -->
  <div class="min-h-screen bg-gray-100">
    
<!-- Контейнер с фиксированной шириной для всего сайта -->
    <div class="max-w-[1400px] mx-auto min-h-screen">
      
      
<!-- Шапка внутри контейнера -->
      <header class="sticky top-0 z-50 bg-white shadow-sm">
        <ErrorBoundary 
          error-title="Навигация временно недоступна"
          :show-reload="false"
        >
          <Navbar />
        </ErrorBoundary>
      </header>
      
      
<!-- Основной контент -->
      <main class="flex-1">
        <slot />
      </main>
      
      <!-- Футер -->
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