<template>
  <!-- Динамический фон в зависимости от типа страницы -->
  <div :class="[
    'min-h-screen flex flex-col',
    isAuthPage ? 'bg-gray-50' : 'bg-gray-100'
  ]">
    
    <!-- Контейнер с фиксированной шириной для всего сайта -->
    <div class="max-w-[1400px] mx-auto min-h-screen flex flex-col w-full">
      
      <!-- НОВОЕ: Единая обертка с отступами для ВСЕГО контента -->
      <div class="site-padding flex-1 flex flex-col">
        
        <!-- Шапка с компенсацией отступов (скрыта для авторизации) -->
        <header v-if="!isAuthPage" class="sticky top-0 z-50 negative-margin">
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
        
        <!-- Footer (скрыт для авторизации) -->
        <Footer v-if="!isAuthPage" />
      </div>
    </div>
         
    <!-- Глобальные уведомления (вне контейнера) -->
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

// Определяем, является ли это страницей авторизации
const isAuthPage = computed(() => {
  return window.isAuthPage || false
})

// Глобальные данные
provide('auth', page.props.auth)
provide('canLogin', page.props.canLogin)
provide('canRegister', page.props.canRegister)

// Provide для sticky top (высота шапки)
provide('stickyTop', 112) // высота двухуровневой шапки
</script>

