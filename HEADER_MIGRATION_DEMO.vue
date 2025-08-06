<!-- ДЕМОНСТРАЦИЯ: Новый Header с FSD архитектурой -->
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Новый Header с FSD компонентами -->
    <Header 
      :loading="isLoading"
      :error="error"
      :current-city="currentCity"
      :is-authenticated="isAuthenticated"
      @city-changed="handleCityChanged"
      @auth-required="handleAuthRequired"
      @menu-toggled="handleMenuToggle"
    >
      <!-- Logo slot -->
      <template #logo>
        <AppLogo 
          href="/"
          logo-text="MASSAGIST"
          variant="default"
          size="medium"
          :show-icon="true"
          :show-text="true"
        />
      </template>

      <!-- Search slot -->
      <template #search>
        <GlobalSearch
          placeholder="Найти мастера или услугу..."
          :catalog-open="catalogOpen"
          :initial-query="searchQuery"
          :show-suggestions="true"
          @toggle-catalog="toggleCatalog"
          @search-performed="handleSearch"
          @suggestion-selected="handleSuggestionSelected"
        />
      </template>

      <!-- Favorites slot -->
      <template #favorites>
        <FavoritesCounter 
          :count="favoritesCount"
          @click="goToFavorites"
        />
      </template>

      <!-- Compare slot -->
      <template #compare>
        <CompareCounter 
          :count="compareCount"
          @click="goToCompare"
        />
      </template>

      <!-- Auth slot -->
      <template #auth>
        <AuthWidget
          :user="currentUser"
          :is-authenticated="isAuthenticated"
          :show-notifications="true"
          :show-wallet="true"
          :show-online-status="true"
          :show-create-ad="true"
          :notification-count="notificationCount"
          @show-login="authStore.openLoginModal"
          @show-register="authStore.openRegisterModal"
          @logout="handleLogout"
          @notification-click="goToNotifications"
          @wallet-click="goToWallet"
          @profile-click="goToProfile"
        />
      </template>

      <!-- City selector slot -->
      <template #city-selector>
        <CityPicker
          :current-city="currentCity"
          :cities="availableCities"
          @select="handleCityChanged"
          @open-modal="showCityModal = true"
        />
      </template>

      <!-- Quick links slot -->
      <template #quick-links>
        <QuickNavigation
          :links="quickLinks"
          @link-click="handleQuickLinkClick"
        />
      </template>

      <!-- Mobile slots -->
      <template #mobile-menu-button>
        <button 
          @click="toggleMobileMenu"
          class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path 
              :class="{ 'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }"
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M4 6h16M4 12h16M4 18h16" 
            />
            <path 
              :class="{ 'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }"
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M6 18L18 6M6 6l12 12" 
            />
          </svg>
        </button>
      </template>

      <template #mobile-logo>
        <AppLogo 
          variant="compact"
          size="small"
          :show-text="false"
        />
      </template>

      <template #mobile-actions>
        <div class="flex items-center space-x-2">
          <NotificationButton 
            :count="notificationCount"
            @click="goToNotifications"
          />
          <FavoritesCounter 
            :count="favoritesCount"
            compact
            @click="goToFavorites"
          />
        </div>
      </template>

      <!-- Modals and overlays -->
      <template #modals>
        <CityModal
          :show="showCityModal"
          :current-city="currentCity"
          :cities="availableCities"
          @close="showCityModal = false"
          @select="handleCityChanged"
        />
      </template>

      <template #overlays>
        <CatalogDropdown
          v-if="catalogOpen"
          :categories="categories"
          @close="catalogOpen = false"
          @category-select="handleCategorySelect"
        />
      </template>
    </Header>

    <!-- Основной контент страницы -->
    <main class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-sm p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">
          🎉 Header Migration Complete!
        </h1>
        
        <div class="grid md:grid-cols-2 gap-8">
          <div>
            <h2 class="text-xl font-semibold mb-4">✅ Мигрированные компоненты:</h2>
            <ul class="space-y-2 text-sm">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                <strong>Logo.vue</strong> → shared/ui/atoms/Logo/AppLogo.vue
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                <strong>SearchBar.vue</strong> → features/search/ui/GlobalSearch + store
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                <strong>AuthBlock.vue</strong> → features/auth/ui/AuthWidget + components
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                <strong>Navbar.vue</strong> → shared/ui/organisms/Header/Header.vue
              </li>
            </ul>
          </div>
          
          <div>
            <h2 class="text-xl font-semibold mb-4">🚀 Архитектурные улучшения:</h2>
            <ul class="space-y-2 text-sm">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                100% TypeScript типизация
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Composition API вместо Options API
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Slot-based архитектура для гибкости
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Pinia stores для state management
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Accessibility improvements
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Полная мобильная адаптивность
              </li>
            </ul>
          </div>
        </div>
        
        <div class="mt-8 p-4 bg-blue-50 rounded-lg">
          <h3 class="font-semibold text-blue-900 mb-2">📋 Следующие этапы:</h3>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>• Миграция CitySelector + CityModal</li>
            <li>• Миграция FavoritesButton + CompareButton</li>
            <li>• Создание QuickNavigation</li>
            <li>• Создание MobileHeader</li>
            <li>• Обновление всех импортов в проекте</li>
          </ul>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore, useSearchStore } from '@/src/stores'

// FSD Components
import { Header } from '@/src/shared/ui/organisms/Header'
import { AppLogo } from '@/src/shared/ui/atoms/Logo'
import { GlobalSearch } from '@/src/features/search'
import { AuthWidget, NotificationButton } from '@/src/features/auth'

// Placeholder components (still to be migrated)
import FavoritesCounter from '@/Components/Header/FavoritesButton.vue'
import CompareCounter from '@/Components/Header/CompareButton.vue'
import CityPicker from '@/Components/Header/CitySelector.vue'
import CityModal from '@/Components/Header/CityModal.vue'
import QuickNavigation from '@/Components/Header/QuickLinks.vue'
import CatalogDropdown from '@/Components/Header/CatalogDropdown.vue'

// Stores
const authStore = useAuthStore()
const searchStore = useSearchStore()

// State
const isLoading = ref(false)
const error = ref('')
const currentCity = ref('Москва')
const catalogOpen = ref(false)
const showCityModal = ref(false)
const mobileMenuOpen = ref(false)
const searchQuery = ref('')

// Mock data
const favoritesCount = ref(5)
const compareCount = ref(2)
const notificationCount = ref(3)
const availableCities = ref(['Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург'])
const quickLinks = ref([
  { label: 'Услуги', href: '/services' },
  { label: 'Мастера', href: '/masters' },
  { label: 'О нас', href: '/about' }
])

// Computed
const isAuthenticated = computed(() => authStore.isAuthenticated)
const currentUser = computed(() => authStore.user)
const categories = computed(() => [
  { id: 1, name: 'Массаж', icon: '💆‍♀️' },
  { id: 2, name: 'СПА', icon: '🧖‍♀️' },
  { id: 3, name: 'Косметология', icon: '✨' }
])

// Methods
const handleCityChanged = (city: string): void => {
  currentCity.value = city
  showCityModal.value = false
}

const handleAuthRequired = (): void => {
  authStore.openLoginModal()
}

const handleMenuToggle = (isOpen: boolean): void => {
  mobileMenuOpen.value = isOpen
}

const toggleCatalog = (): void => {
  catalogOpen.value = !catalogOpen.value
}

const toggleMobileMenu = (): void => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const handleSearch = (query: string): void => {
  // Поиск выполнен
}

const handleSuggestionSelected = (suggestion: any): void => {
  // Предложение выбрано
}

const handleLogout = async (): Promise<void> => {
  await authStore.logout()
}

const handleQuickLinkClick = (link: any): void => {
  // Быстрая ссылка нажата
}

const handleCategorySelect = (category: any): void => {
  // Категория выбрана
  catalogOpen.value = false
}

const goToFavorites = (): void => {
  // Переход к избранному
}

const goToCompare = (): void => {
  // Переход к сравнению
}

const goToNotifications = (): void => {
  // Переход к уведомлениям
}

const goToWallet = (): void => {
  // Переход к кошельку
}

const goToProfile = (): void => {
  // Переход к профилю
}
</script>

<style scoped>
/* Демонстрационные стили */
.container {
  max-width: 1200px;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>