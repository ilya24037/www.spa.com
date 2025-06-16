<template>
  <div class="min-h-screen bg-gray-50">
    <Container>
      <!-- Шапка с защитой (может содержать сложную логику) -->
      <header class="sticky top-0 z-50 bg-white shadow-sm rounded-b-2xl">
        <ErrorBoundary 
          error-title="Навигация временно недоступна"
          :show-reload="false"
        >
          <Navbar />
        </ErrorBoundary>
      </header>
      
      <!-- Основной контент БЕЗ защиты -->
      <main class="pt-4">
        <slot />
      </main>
      
      <!-- Простой футер БЕЗ защиты -->
      <footer class="bg-white rounded-t-2xl mt-8 py-4 text-center text-sm text-gray-500">
        © {{ new Date().getFullYear() }} SPA Platform
      </footer>
    </Container>
    
    <!-- Уведомления -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Header/Navbar.vue'
import Container from '@/Components/Container.vue'
import ErrorBoundary from '@/Components/Common/ErrorBoundary.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'
</script>