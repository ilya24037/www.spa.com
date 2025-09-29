<!-- Шапка сайта MASSAGIST как на скриншоте -->
<template>
  <header class="overflow-visible relative app-header">
    <!-- Десктопная версия -->
    <div class="hidden lg:block">
      <!-- Уровень 1: Основная навигация -->
      <div class="border-b border-gray-200 px-6">
        <div class="flex items-center justify-between h-16">
          <!-- Левая часть -->
          <div class="flex items-center flex-1">
            <Logo />
            <div class="ml-8 flex-1 max-w-3xl">
              <SearchBar @toggle-catalog="toggleCatalog" />
            </div>
          </div>

          <!-- Правая часть -->
          <div class="flex items-center ml-8 gap-4">
            <!-- Избранное (из оригинального FavoritesButton) -->
            <Link href="/favorites" class="flex items-center gap-1 text-gray-700 hover:text-gray-900 transition">
              <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
              </svg>
              <span v-if="favoritesCount > 0" class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                {{ favoritesCount }}
              </span>
              <span class="text-sm">Избранное</span>
            </Link>

            <!-- Сравнить (из оригинального CompareButton) -->
            <Link href="/compare" class="flex items-center gap-1 text-gray-700 hover:text-gray-900 transition">
              <svg class="w-5 h-5"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              <span v-if="compareCount > 0" class="text-red-500 text-xs font-bold">
                {{ compareCount }}
              </span>
              <span class="text-sm">Сравнить</span>
            </Link>

            <!-- Для авторизованных пользователей -->
            <template v-if="isAuthenticated">
              <!-- Меню пользователя (аватар без дублей избранного) -->
              <UserActions 
                :user="user"
                :is-authenticated="isAuthenticated"
              />

              <!-- Кнопка разместить объявление -->
              <Link 
                href="/additem"
                class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium whitespace-nowrap"
              >
                Разместить объявление
              </Link>
            </template>

            <!-- Для неавторизованных -->
            <template v-else>
              <button 
                @click="showLoginModal = true"
                class="text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-100 transition font-medium"
              >
                Войти
              </button>
              <button 
                @click="showRegisterModal = true"
                class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium"
              >
                Регистрация
              </button>
            </template>
          </div>
        </div>
      </div>
      
      <!-- Вторая строка -->
      <div class="bg-gray-50 border-t border-gray-200">
        <div class="px-6">
          <div class="flex items-center justify-between h-12">
            <button 
              class="flex items-center space-x-1 text-sm text-gray-600 hover:text-blue-600" 
              @click="showCityModal = true"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              <span>{{ currentCity }}</span>
              <svg
                class="w-3 h-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </button>
            <CategoryNav />
          </div>
        </div>
      </div>
    </div>

    <!-- Мобильная версия показывается только на мобильных устройствах -->
    <div class="lg:hidden">
      <MobileMenu 
        :current-city="currentCity"
        :is-authenticated="isAuthenticated"
        @toggle-catalog="toggleCatalog"
        @open-city-modal="showCityModal = true"
        @show-login-modal="showLoginModal = true"
        @show-register-modal="showRegisterModal = true"
      />
    </div>
    
    <!-- Общие компоненты -->
    <!-- Каталог с Teleport -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showCatalog" class="fixed inset-0 z-50">
          <div class="absolute inset-0 bg-black bg-opacity-25" @click="closeCatalog"></div>
          <CatalogDropdown 
            class="absolute top-16 lg:top-28 left-0 right-0"
            @close="closeCatalog"
            @category-select="handleCategorySelect"
          />
        </div>
      </Transition>
    </Teleport>

    <!-- Модальные окна авторизации -->
    <AuthModal 
      :show="showLoginModal"
      @close="showLoginModal = false"
    />
    
    <RegisterModal 
      :show="showRegisterModal"
      @close="showRegisterModal = false"
    />

    <!-- Модальное окно города -->
    <CityModal 
      :show="showCityModal"
      :current-city="currentCity"
      @close="showCityModal = false"
      @city-selected="handleCityChange"
    />
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'

// Виджеты и компоненты из проекта
import Logo from './Logo.vue'
import SearchBar from '@/src/features/search/ui/SearchBar/SearchBar.vue'
import CategoryNav from './CategoryNav.vue'
import UserActions from './UserActions.vue'
import MobileMenu from './MobileMenu.vue'
import CityModal from '@/src/features/city-selector/ui/CityModal/CityModal.vue'

// Features
import { CatalogDropdown } from '@/src/features/catalog'

// Модальные окна авторизации
import AuthModal from '@/src/features/auth/ui/AuthModal/AuthModal.vue'
import RegisterModal from '@/src/features/auth/ui/RegisterModal/RegisterModal.vue'

// Данные страницы
const page = usePage()

// TypeScript интерфейсы
interface User {
  id: number
  name: string
  email: string
  avatar?: string
}

// Props
interface Props {
  user?: User | null
  isAuthenticated?: boolean
  currentCity?: string
  favoritesCount?: number
  compareCount?: number
}

const props = withDefaults(defineProps<Props>(), {
    user: null,
    isAuthenticated: false,
    currentCity: 'Москва',
    favoritesCount: 0,
    compareCount: 0
})

// State
const showCatalog = ref(false)
const showLoginModal = ref(false)
const showRegisterModal = ref(false)
const showCityModal = ref(false)
const currentCity = ref(props.currentCity)

// Computed
const isAuthenticated = computed(() => props.isAuthenticated || !!page.props.auth?.user)
const user = computed(() => props.user || page.props.auth?.user)
const favoritesCount = computed(() => page.props.favoritesCount || 0)
const compareCount = computed(() => page.props.compareCount || 0)

// Methods
const toggleCatalog = () => {
    showCatalog.value = !showCatalog.value
}

const closeCatalog = () => {
    showCatalog.value = false
}

const handleCategorySelect = (category: any) => {
    showCatalog.value = false
    router.visit(category.href)
}

const handleCityChange = (city: any) => {
    const cityName = city.name || city
    currentCity.value = cityName
    showCityModal.value = false
    localStorage.setItem('selectedCity', cityName)
}

// Close catalog on click outside
const handleClickOutside = (e: MouseEvent) => {
    const target = e.target as HTMLElement
    if (!target.closest('.app-header') && !target.closest('.catalog-dropdown')) {
        showCatalog.value = false
    }
}

// При загрузке восстанавливаем сохраненный город
onMounted(() => {
    const savedCity = localStorage.getItem('selectedCity')
    if (savedCity) {
        currentCity.value = savedCity
    }
    
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>