<!-- resources/js/Components/Header/Navbar.vue -->
<template>
  <nav class="h-full bg-white shadow-md rounded-b-2xl overflow-hidden">
    <!-- Десктопная версия -->
    <div class="hidden lg:block h-full">
      <div>
        <!-- Основная навигация -->
        <div class="px-4 lg:px-6">
          <div class="flex items-center justify-between h-16">
            <!-- Левая часть -->
            <div class="flex items-center flex-1">
              <Logo />
              <div class="ml-8 flex-1 max-w-3xl">
                <SearchBar @toggle-catalog="showCatalog = !showCatalog" />
              </div>
            </div>

            <!-- Правая часть -->
            <div class="flex items-center ml-8 gap-4">
              <FavoritesButton />
              <CompareButton />
              
              <!-- Для авторизованных пользователей -->
              <template v-if="isAuthenticated">
                <Link 
                  href="/masters/create"
                  class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium whitespace-nowrap"
                >
                  Разместить объявление
                </Link>
                
                <!-- Меню пользователя -->
              
<UserMenu 
  :show-wallet="true"
  :show-profile-switcher="false"
  :show-online-status="true"
/>



              </template>
              
              <!-- Для неавторизованных -->
              <template v-else>
                <Link 
                  href="/login"
                  class="text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-100 transition font-medium"
                >
                  Войти
                </Link>
                <Link 
                  href="/register"
                  class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium"
                >
                  Регистрация
                </Link>
              </template>
            </div>
          </div>
        </div>

        <!-- Вторая строка -->
        <div class="bg-gray-50 border-t border-gray-200">
          <div class="px-4 lg:px-6">
            <div class="flex items-center justify-between h-12">
              <CitySelector 
                :current-city="currentCity" 
                @open-modal="showCityModal = true" 
              />
              <QuickLinks />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Мобильная версия -->
    <MobileMenu 
      :current-city="currentCity"
      :is-authenticated="isAuthenticated"
      @toggle-catalog="showCatalog = !showCatalog"
      @open-city-modal="showCityModal = true"
    />

    <!-- Общие компоненты -->
    <!-- Каталог -->
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
          <div class="absolute inset-0 bg-black bg-opacity-25" @click="showCatalog = false"></div>
          <CatalogDropdown 
            class="absolute top-16 lg:top-28 left-0 right-0"
            @close="showCatalog = false" 
          />
        </div>
      </Transition>
    </Teleport>

    <!-- Модальное окно города -->
    <CityModal 
      :show="showCityModal"
      :current-city="currentCity"
      @close="showCityModal = false"
      @select="handleCitySelect"
    />
  </nav>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Компоненты
import Logo from './Logo.vue'
import SearchBar from './SearchBar.vue'
import CitySelector from './CitySelector.vue'
import FavoritesButton from './FavoritesButton.vue'
import CompareButton from './CompareButton.vue'
import QuickLinks from './QuickLinks.vue'
import CatalogDropdown from './CatalogDropdown.vue'
import CityModal from './CityModal.vue'
import MobileMenu from './MobileMenu.vue'
import UserMenu from './UserMenu.vue'

// Данные страницы
const page = usePage()

// Проверка авторизации
const isAuthenticated = computed(() => !!page.props.auth?.user)

// Состояние
const showCatalog = ref(false)
const showCityModal = ref(false)
const currentCity = ref('Москва')

// Методы
const handleCitySelect = (city) => {
  currentCity.value = city
  localStorage.setItem('selectedCity', city)
}

// При загрузке
onMounted(() => {
  const savedCity = localStorage.getItem('selectedCity')
  if (savedCity) {
    currentCity.value = savedCity
  }
})
</script>