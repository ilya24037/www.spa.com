<!-- resources/js/Components/Header/MobileMenu.vue -->
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
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="Найти мастера"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @keyup.enter="search"
          >
          <button 
            @click="search"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400"
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
          <!-- Разместить объявление -->
          <Link 
            href="/masters/create"
            class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-medium"
          >
            Разместить объявление
          </Link>

          <!-- Навигация -->
          <nav class="space-y-2">
            <Link href="/favorites" class="flex items-center justify-between py-2">
              <span>Избранное</span>
              <span v-if="favoritesCount > 0" class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                {{ favoritesCount }}
              </span>
            </Link>
            <Link href="/compare" class="flex items-center justify-between py-2">
              <span>Сравнить</span>
              <span v-if="compareCount > 0" class="text-red-500 text-sm font-bold">
                {{ compareCount }}
              </span>
            </Link>
          </nav>

          <!-- Категории -->
          <div class="border-t pt-4">
            <h4 class="font-semibold mb-2">Услуги</h4>
            <nav class="space-y-2 text-sm">
              <Link href="/services/massage" class="block py-1 text-gray-600">Массаж</Link>
              <Link href="/services/spa" class="block py-1 text-gray-600">СПА</Link>
              <Link href="/services/cosmetology" class="block py-1 text-gray-600">Косметология</Link>
              <Link href="/services/at-home" class="block py-1 text-gray-600">На дому</Link>
            </nav>
          </div>

          <!-- Авторизация -->
          <div v-if="!user" class="border-t pt-4">
            <Link href="/login" class="block text-center text-blue-600 font-medium">
              Войти или зарегистрироваться
            </Link>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import Logo from './Logo.vue'

const props = defineProps({
  currentCity: String
})

const emit = defineEmits(['toggle-catalog', 'open-city-modal'])

const page = usePage()
const showMenu = ref(false)
const searchQuery = ref('')

const user = computed(() => page.props.auth?.user)
const favoritesCount = computed(() => page.props.favoritesCount || 0)
const compareCount = computed(() => page.props.compareCount || 0)

const search = () => {
  if (searchQuery.value.trim()) {
    router.get('/search', { q: searchQuery.value })
    showMenu.value = false
  }
}
</script>