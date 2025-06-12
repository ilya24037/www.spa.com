<!-- resources/js/Layouts/AppLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-100 site-wrapper">
        <!-- Единый контейнер для всего сайта -->
        <div class="site-container">
            <!-- Шапка с закругленными нижними углами -->
            <header class="sticky top-0 z-50 bg-white shadow-md header-rounded">
                <Navbar />
            </header>

            <!-- Основной контент -->
            <main class="flex-1 bg-white">
                <slot />
            </main>

            <!-- Футер -->
            <Footer v-if="!hideFooter" />
        </div>
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
/* Обертка для отступов */
.site-wrapper {
    padding: 0 20px; /* Отступы от краев окна */
    overflow-x: auto; /* Горизонтальный скролл при необходимости */
}

.site-container {
    /* Фиксированная ширина */
    width: 100%;
    max-width: 1400px; /* Максимальная ширина как на Ozon */
    min-width: 1200px; /* Минимальная ширина */
    margin: 0 auto;
    background-color: white;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Закругленные нижние углы у шапки */
.header-rounded {
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    overflow: hidden;
}

/* Для мобильных устройств */
@media (max-width: 1240px) {
    .site-wrapper {
        padding: 0 10px; /* Меньше отступы на маленьких экранах */
    }
}

@media (max-width: 1200px) {
    .site-wrapper {
        padding: 0; /* Убираем отступы */
        overflow-x: visible;
    }
    
    .site-container {
        min-width: 100%;
        max-width: 100%;
        box-shadow: none;
    }
    
    .header-rounded {
        border-radius: 0; /* Убираем закругление на мобильных */
    }
}

/* Стили для горизонтального скролла */
.site-wrapper::-webkit-scrollbar {
    height: 8px;
}

.site-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.site-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.site-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>