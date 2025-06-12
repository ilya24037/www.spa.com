<!-- resources/js/Layouts/AppLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Шапка сайта - sticky с закругленными углами -->
        <Navbar />

        <!-- Основной контент с небольшим отступом сверху -->
        <main class="pt-4">
            <slot />
        </main>

        <!-- Футер -->
        <Footer v-if="!hideFooter" />
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

<style>
/* Глобальные стили для правильного позиционирования */
body {
  margin: 0;
  padding: 0;
}

/* Убираем отступы у контейнеров, чтобы шапка была на всю ширину */
.container {
  max-width: 1280px;
}
</style>