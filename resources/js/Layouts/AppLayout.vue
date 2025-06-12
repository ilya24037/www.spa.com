<!-- resources/js/Layouts/AppLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Шапка сайта с фиксированной высотой -->
        <div class="header-wrapper">
            <Navbar />
        </div>

        <!-- Основной контент с правильным отступом -->
        <main class="main-content">
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

<style scoped>
.header-wrapper {
    position: sticky;
    top: 0;
    z-index: 50;
    /* Фиксированная высота шапки */
    height: auto;
}

.main-content {
    /* Отступ сверху равен примерной высоте шапки + небольшой зазор */
    padding-top: 0;
    min-height: calc(100vh - 100px);
}
</style>