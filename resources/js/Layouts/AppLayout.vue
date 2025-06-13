<!-- resources/js/Layouts/AppLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-100 site-wrapper">
        <!-- Единый контейнер -->
        <div class="site-container">
            <!-- Шапка -->
            <header class="sticky top-0 z-50 bg-white shadow-md rounded-b-2xl">
                <Navbar />
            </header>

            <!-- Основной контент -->
            <main class="flex-1 bg-white rounded-lg mt-4">
                <slot />
            </main>

            <!-- Футер -->
            <footer v-if="!hideFooter" class="bg-white rounded-lg mt-4 shadow-sm">
                <Footer />
            </footer>
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
/* Обертка с отступами */
.site-wrapper {
    padding: 0 20px;
    overflow-x: auto;
}

.site-container {
    width: 100%;
    max-width: 1400px;
    min-width: 1200px;
    margin: 0 auto;
    /* Убираем белый фон и тень */
    /* background-color: white; */
    /* box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); */
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Добавляем отступы между блоками */
main {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Мобильная адаптация */
@media (max-width: 1240px) {
    .site-wrapper {
        padding: 0 10px;
    }
}

@media (max-width: 1200px) {
    .site-wrapper {
        padding: 0;
        overflow-x: visible;
    }
    
    .site-container {
        min-width: 100%;
    }
    
    main {
        border-radius: 0;
        margin-top: 0;
    }
}

/* Стилизация скроллбара */
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