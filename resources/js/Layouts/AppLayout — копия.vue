<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 site-wrapper">
        <!-- Контейнер с тенью -->
        <div class="site-container">
            <!-- Шапка как отдельная карточка -->
            <header class="sticky top-4 z-50 mb-6">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50">
                    <Navbar />
                </div>
            </header>

            <!-- Основной контент как карточка -->
            <main class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                    <slot />
                </div>
            </main>

            <!-- Футер как карточка -->
            <footer v-if="!hideFooter" class="mt-6">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50">
                    <Footer />
                </div>
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