<template>
  <div class="min-h-screen flex flex-col bg-[#f5f7fa] text-gray-800">
    <Container>
      <!-- Белая шапка с закруглением снизу -->
      <div class="sticky top-0 z-50 bg-white shadow rounded-b-2xl">
        <Navbar />
      </div>
      
      <!-- Белый фон контента, совпадает по ширине с шапкой -->
      <main class="pt-6 pb-8">
        <slot />
      </main>
      
      <footer class="bg-gray-200 py-6 text-sm text-gray-600 text-center rounded-t-xl mt-12">
        © {{ new Date().getFullYear() }} Animatorr — Все права защищены
      </footer>
    </Container>
    
    <!-- Компонент уведомлений -->
    <ToastNotifications ref="toastRef" />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Header/Navbar.vue'
import Footer from '@/Components/Footer/Footer.vue'

defineProps({
    hideFooter: {
        type: Boolean,
        default: false
    }
})
</script>

<style scoped>
.site-wrapper {
    padding: 20px;
}

.site-container {
    max-width: 1400px;
    margin: 0 auto;
    min-height: calc(100vh - 40px);
}

@media (max-width: 1240px) {
    .site-wrapper {
        padding: 10px;
    }
}

@media (max-width: 768px) {
    .site-wrapper {
        padding: 8px;
    }
    
    .site-container header .bg-white,
    .site-container main .bg-white,
    .site-container footer .bg-white {
        border-radius: 12px;
    }
}
</style>