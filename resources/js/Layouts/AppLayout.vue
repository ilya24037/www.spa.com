<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Центрированный контейнер для всего сайта -->
    <div class="max-w-[1400px] mx-auto bg-white min-h-screen shadow-sm flex flex-col">
      <!-- Шапка внутри контейнера -->
      <header class="sticky top-0 z-50 bg-white border-b">
        <Navbar />
      </header>
      
      <!-- Основной контент -->
      <main class="flex-1 px-4 py-6">
        <slot />
      </main>
      
      <!-- Футер -->
      <Footer v-if="!hideFooter" />
    </div>
    
    <!-- Компонент уведомлений вне контейнера -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Header/Navbar.vue'
import Footer from '@/Components/Footer/Footer.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'

defineProps({
    hideFooter: {
        type: Boolean,
        default: false
    }
})
</script>

<style>
/* Убираем лишние отступы у body */
body {
  margin: 0;
  padding: 0;
}

/* На очень больших экранах добавляем отступы по бокам */
@media (min-width: 1440px) {
  .max-w-\[1400px\] {
    margin-left: auto;
    margin-right: auto;
  }
}
</style>