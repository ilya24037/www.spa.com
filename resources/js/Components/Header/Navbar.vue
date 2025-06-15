<template>
  <nav class="bg-white">
    <div class="px-4">
      <div class="flex items-center h-16 gap-4">
        <!-- Логотип -->
        <Logo />

        <!-- Кнопка каталога в стиле Ozon -->
        <button 
          @click="toggleCatalog"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex-shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <span class="hidden md:block">Каталог</span>
        </button>

        <!-- Поиск -->
        <div class="flex-1 max-w-2xl">
          <SearchBar />
        </div>

        <!-- Правая часть -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <!-- Выбор города -->
          <CitySelector class="hidden lg:block" />

          <!-- Избранное -->
          <FavoritesButton />

          <!-- Сравнение -->
          <CompareButton class="hidden sm:block" />

          <!-- Авторизация -->
          <AuthBlock />

          <!-- Кнопка "Разместить объявление" -->
          <Link 
            v-if="canCreateAd"
            :href="route('masters.create')"
            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition hidden lg:block whitespace-nowrap"
          >
            Разместить объявление
          </Link>
        </div>
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
      <div v-if="showCatalog" class="absolute left-0 right-0 bg-white shadow-lg border-t z-40">
        <div class="max-w-[1400px] mx-auto px-4 py-6">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div v-for="category in categories" :key="category.id">
              <h3 class="font-semibold mb-3 text-gray-900">{{ category.name }}</h3>
              <ul class="space-y-2">
                <li v-for="subcat in category.children || []" :key="subcat.id">
                  <Link 
                    :href="`/category/${subcat.slug}`"
                    class="text-sm text-gray-600 hover:text-blue-600 transition"
                    @click="showCatalog = false"
                  >
                    {{ subcat.name }}
                  </Link>
                </li>
              </ul>
            </div>
          </div>
          
          <!-- Популярные услуги -->
          <div class="mt-6 pt-6 border-t">
            <p class="text-sm text-gray-500 mb-3">Популярные услуги:</p>
            <div class="flex flex-wrap gap-2">
              <Link
                v-for="service in popularServices"
                :key="service"
                href="#"
                class="px-3 py-1 bg-gray-100 text-sm rounded-full hover:bg-gray-200 transition"
                @click="showCatalog = false"
              >
                {{ service }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </nav>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Импорт существующих компонентов
import Logo from './Logo.vue'
import CitySelector from './CitySelector.vue'
import SearchBar from './SearchBar.vue'
import FavoritesButton from './FavoritesButton.vue'
import CompareButton from './CompareButton.vue'
import AuthBlock from './AuthBlock.vue'

const page = usePage()

// Состояние
const showCatalog = ref(false)

// Данные
const categories = computed(() => page.props.categories || [
  {
    id: 1,
    name: 'Массаж',
    children: [
      { id: 11, name: 'Классический', slug: 'klassicheskiy' },
      { id: 12, name: 'Тайский', slug: 'tayskiy' },
      { id: 13, name: 'Спортивный', slug: 'sportivnyy' },
      { id: 14, name: 'Лечебный', slug: 'lechebnyy' }
    ]
  },
  {
    id: 2,
    name: 'СПА процедуры',
    children: [
      { id: 21, name: 'Обертывания', slug: 'obertyvaniya' },
      { id: 22, name: 'Пилинги', slug: 'pilingi' },
      { id: 23, name: 'Ароматерапия', slug: 'aromaterapiya' }
    ]
  },
  {
    id: 3,
    name: 'Косметология',
    children: [
      { id: 31, name: 'Уход за лицом', slug: 'uhod-za-licom' },
      { id: 32, name: 'Чистка лица', slug: 'chistka-lica' },
      { id: 33, name: 'Пилинги', slug: 'pilingi-lica' }
    ]
  },
  {
    id: 4,
    name: 'Дополнительно',
    children: [
      { id: 41, name: 'Йога', slug: 'yoga' },
      { id: 42, name: 'Медитация', slug: 'meditaciya' },
      { id: 43, name: 'Ароматерапия', slug: 'aromaterapiya' }
    ]
  }
])

const popularServices = [
  'Классический массаж',
  'Тайский массаж',
  'Антицеллюлитный',
  'Спортивный массаж',
  'Релакс массаж',
  'Массаж спины'
]

// Проверка прав на создание объявления
const canCreateAd = computed(() => {
  const user = page.props.auth?.user
  return !user || user.role !== 'admin' // Все кроме админа могут создавать объявления
})

// Методы
const toggleCatalog = () => {
  showCatalog.value = !showCatalog.value
}

// Закрытие каталога при клике вне
const handleClickOutside = (e) => {
  if (showCatalog.value && !e.target.closest('nav')) {
    showCatalog.value = false
  }
}

if (typeof window !== 'undefined') {
  document.addEventListener('click', handleClickOutside)
}
</script>

<style scoped>
/* Убираем дополнительную навигацию, если она была */
.bg-gray-50 {
  display: none;
}
</style>