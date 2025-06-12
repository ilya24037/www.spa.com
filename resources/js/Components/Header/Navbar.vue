<!-- resources/js/Components/Header/Navbar.vue -->
<template>
  <header class="sticky top-0 z-50 bg-white shadow-md rounded-b-2xl">
    <!-- Основная навигация -->
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex items-center h-16 gap-4">
        <!-- Логотип -->
        <Logo />

        <!-- Кнопка каталога -->
        <CatalogButton @toggle-catalog="showCatalog = !showCatalog" />

        <!-- Выбор города -->
        <CitySelector />

        <!-- Поиск -->
        <SearchBar />

        <!-- Действия пользователя -->
        <div class="flex items-center gap-2">
          <FavoritesButton />
          <CompareButton />
          <AuthBlock />
        </div>
      </div>
    </div>

    <!-- Дополнительная навигация -->
    <div class="bg-gray-50 border-t border-gray-200 rounded-b-2xl">
      <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between py-2">
          <!-- Быстрые ссылки -->
          <div class="flex items-center gap-6 text-sm">
            <a href="/promo" class="text-pink-600 font-medium hover:text-pink-700 flex items-center gap-1">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z" />
              </svg>
              Акции
            </a>
            <a href="/premium" class="text-gray-600 hover:text-gray-900">Премиум мастера</a>
            <a href="/top-rated" class="text-gray-600 hover:text-gray-900">Топ рейтинга</a>
            <a href="/new" class="text-gray-600 hover:text-gray-900">Новые</a>
            <a href="/certificates" class="text-gray-600 hover:text-gray-900">Сертификаты</a>
            <span class="text-gray-300">•</span>
            <a href="/business" class="text-gray-600 hover:text-gray-900">Для бизнеса</a>
          </div>

          <!-- Текущий адрес -->
          <button 
            @click="openAddressModal"
            class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" 
              />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" 
              />
            </svg>
            {{ currentAddress }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Выпадающий каталог -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="showCatalog" class="absolute left-0 right-0 top-full bg-white shadow-xl z-50 rounded-b-2xl mt-px">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
          <div class="grid grid-cols-4 gap-6">
            <div v-for="category in categories" :key="category.id">
              <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <span class="text-2xl">{{ category.icon }}</span>
                {{ category.name }}
              </h3>
              <ul class="space-y-2">
                <li v-for="subcat in category.children || []" :key="subcat.id">
                  <a 
                    :href="`/category/${subcat.slug}`"
                    class="text-sm text-gray-600 hover:text-blue-600 transition-colors flex items-center justify-between group"
                  >
                    <span>{{ subcat.name }}</span>
                    <span class="text-xs text-gray-400 group-hover:text-blue-600">
                      {{ subcat.services_count }}
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          
          <!-- Популярные услуги -->
          <div class="mt-6 pt-6 border-t border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Популярные услуги</h4>
            <div class="flex flex-wrap gap-2">
              <a 
                v-for="service in popularServices"
                :key="service"
                href="#"
                class="px-3 py-1 bg-gray-100 text-sm rounded-full hover:bg-gray-200 transition-colors"
              >
                {{ service }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Импорт компонентов
import Logo from './Logo.vue'
import CitySelector from './CitySelector.vue'
import CatalogButton from './CatalogButton.vue'
import SearchBar from './SearchBar.vue'
import FavoritesButton from './FavoritesButton.vue'
import CompareButton from './CompareButton.vue'
import AuthBlock from './AuthBlock.vue'

const page = usePage()

// Состояние
const showCatalog = ref(false)
const currentAddress = ref('Укажите адрес для поиска мастеров')

// Данные
const categories = computed(() => page.props.categories || [])
const popularServices = [
  'Классический массаж',
  'Тайский массаж',
  'Антицеллюлитный',
  'Спортивный массаж',
  'Релакс массаж',
  'Массаж спины'
]

// Методы
const openAddressModal = () => {
  console.log('Открыть выбор адреса')
}

// Закрытие каталога при клике вне
const handleClickOutside = (e) => {
  if (showCatalog.value && !e.target.closest('header')) {
    showCatalog.value = false
  }
}

if (typeof window !== 'undefined') {
  document.addEventListener('click', handleClickOutside)
}
</script>

<style scoped>
/* Дополнительные стили для точности как на Ozon */
header {
  /* Убираем стандартные радиусы у вложенных элементов сверху */
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}

/* Плавная тень */
header {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: box-shadow 0.3s ease;
}

/* Убираем лишние отступы на мобильных */
@media (max-width: 640px) {
  header {
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
  }
}
</style>