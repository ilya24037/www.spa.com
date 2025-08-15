<!-- resources/js/src/widgets/header/MobileMenu.vue (точная копия из Backap) -->
<template>
  <div class="lg:hidden">
    <!-- Мобильная шапка -->
    <div class="bg-white border-b border-gray-200 px-4 py-3">
      <div class="flex items-center justify-between">
        <!-- Логотип и город -->
        <div class="flex items-center gap-3">
          <Logo />
          <button 
            @click="$emit('open-city-modal')"
            class="text-sm text-gray-600 flex items-center gap-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            </svg>
            {{ currentCity }}
          </button>
        </div>

        <!-- Правая часть: кнопка меню -->
        <div class="flex items-center gap-2">
          <!-- Меню бургер -->
          <button 
            @click="showMenu = !showMenu"
            class="p-2 rounded-lg hover:bg-gray-100"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="!showMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Поиск -->
      <div class="mt-3 flex gap-2">
        <button 
          @click="$emit('toggle-catalog')"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          Каталог
        </button>
        
        <div class="flex-1 relative">
          <label :for="searchInputId" class="sr-only">Поиск мастеров</label>
          <input 
            :id="searchInputId"
            v-model="searchQuery"
            type="text"
            name="mobile-search"
            placeholder="Найти мастера"
            aria-label="Найти мастера"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @keyup.enter="search"
          >
          <button 
            @click="search"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400"
            aria-label="Выполнить поиск"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Выпадающее меню -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="showMenu" class="absolute top-full left-0 right-0 bg-white shadow-lg z-50">
        <div class="p-4 space-y-4">
          <!-- Для авторизованных -->
          <template v-if="isAuthenticated">
            <!-- Разместить объявление -->
            <Link 
              href="/additem"
              class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-medium"
            >
              Разместить объявление
            </Link>
          </template>
          
          <!-- Для неавторизованных -->
          <template v-else>
            <button 
              @click="$emit('show-login-modal')"
              class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-medium"
            >
              Войти
            </button>
            <button 
              @click="$emit('show-register-modal')"
              class="block w-full border-2 border-blue-600 text-blue-600 text-center py-3 rounded-lg font-medium"
            >
              Регистрация
            </button>
          </template>

          <!-- Избранное и сравнить -->
          <div class="grid grid-cols-2 gap-3">
            <Link href="/favorites" class="flex items-center justify-center gap-2 p-3 border rounded-lg">
              <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
              </svg>
              <span class="text-sm">Избранное</span>
            </Link>
            <Link href="/compare" class="flex items-center justify-center gap-2 p-3 border rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              <span class="text-sm">Сравнить</span>
            </Link>
          </div>

          <!-- Быстрые ссылки -->
          <div class="border-t pt-4">
            <QuickLinks />
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useId } from '@/src/shared/composables/useId'
import Logo from './Logo.vue'
import QuickLinks from './QuickLinks.vue'

// Props
const props = defineProps({
  currentCity: String,
  isAuthenticated: Boolean
})

// Emits
defineEmits([
  'toggle-catalog',
  'open-city-modal', 
  'show-login-modal',
  'show-register-modal'
])

// State
const showMenu = ref(false)
const searchQuery = ref('')
const searchInputId = useId('mobile-search')

// Methods
const search = () => {
  if (searchQuery.value.trim()) {
    router.get('/search', { q: searchQuery.value })
  }
}
</script>

<style scoped>
/* Скрытие элементов визуально, но оставляя доступными для скринридеров */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
