<template>
  <div class="min-h-screen bg-gray-50">
    <!-- БЕЗ Container, как на Ozon -->
    <div class="max-w-[1400px] mx-auto bg-white min-h-screen">
      
      <!-- Шапка с защитой -->
      <header class="sticky top-0 z-50 bg-white shadow-sm rounded-b-2xl">
        <ErrorBoundary 
          error-title="Навигация временно недоступна"
          :show-reload="false"
        >
          <Navbar />
        </ErrorBoundary>
      </header>
      
      <!-- Основной контент -->
      <main class="pt-4 px-4">
        <slot />
      </main>
      
      <!-- Футер -->
      <footer class="bg-white rounded-t-2xl mt-8 py-4 text-center text-sm text-gray-500">
        © {{ new Date().getFullYear() }} SPA Platform
      </footer>
    </div>
    
    <!-- Уведомления вне контейнера -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Header/Navbar.vue'
// УБИРАЕМ импорт Container - как на Ozon!
import ErrorBoundary from '@/Components/Common/ErrorBoundary.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'
</script>