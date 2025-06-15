<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Обёртка для отступов на очень больших экранах -->
    <div class="2xl:px-4">
      <!-- Основной контейнер -->
      <div class="max-w-[1400px] mx-auto bg-white min-h-screen shadow-sm">
        <!-- Шапка -->
        <header class="sticky top-0 z-50 bg-white border-b">
          <Navbar />
        </header>
        
        <!-- Основной контент -->
        <main>
          <slot />
        </main>
        
        <!-- Футер -->
        <footer v-if="!hideFooter" class="bg-gray-50 border-t mt-auto">
          <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
              <!-- Колонка 1 -->
              <div>
                <h3 class="font-semibold mb-4">О компании</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                  <li><a href="#" class="hover:text-gray-900">О нас</a></li>
                  <li><a href="#" class="hover:text-gray-900">Контакты</a></li>
                  <li><a href="#" class="hover:text-gray-900">Вакансии</a></li>
                </ul>
              </div>
              
              <!-- Колонка 2 -->
              <div>
                <h3 class="font-semibold mb-4">Клиентам</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                  <li><a href="#" class="hover:text-gray-900">Как заказать</a></li>
                  <li><a href="#" class="hover:text-gray-900">Оплата</a></li>
                  <li><a href="#" class="hover:text-gray-900">Гарантии</a></li>
                </ul>
              </div>
              
              <!-- Колонка 3 -->
              <div>
                <h3 class="font-semibold mb-4">Мастерам</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                  <li><a href="#" class="hover:text-gray-900">Стать мастером</a></li>
                  <li><a href="#" class="hover:text-gray-900">Правила</a></li>
                  <li><a href="#" class="hover:text-gray-900">Тарифы</a></li>
                </ul>
              </div>
              
              <!-- Колонка 4 -->
              <div>
                <h3 class="font-semibold mb-4">Поддержка</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                  <li><a href="#" class="hover:text-gray-900">Помощь</a></li>
                  <li><a href="#" class="hover:text-gray-900">FAQ</a></li>
                  <li><a href="#" class="hover:text-gray-900">Обратная связь</a></li>
                </ul>
              </div>
            </div>
            
            <div class="border-t pt-8 text-center text-sm text-gray-600">
              © {{ new Date().getFullYear() }} SPA.COM — Все права защищены
            </div>
          </div>
        </footer>
      </div>
    </div>
    
    <!-- Компонент уведомлений вне контейнера -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Header/Navbar.vue'
import ToastNotifications from '@/Components/Common/ToastNotifications.vue'

defineProps({
    hideFooter: {
        type: Boolean,
        default: false
    }
})
</script>