<!-- resources/js/Layouts/AppLayout.vue -->
<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <div class="max-w-[1400px] mx-auto min-h-screen">
      
      <!-- Шапка -->
      <header class="sticky top-0 z-50 h-28">
        <ErrorBoundary 
          error-title="Навигация временно недоступна"
          :show-reload="false"
        >
          <Navbar />
        </ErrorBoundary>
      </header>
      
      <!-- Основной контент - УПРОЩЁННЫЙ ПОДХОД -->
      <main class="pt-6 px-4 lg:pt-8 lg:px-6 flex-1">
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
</script>