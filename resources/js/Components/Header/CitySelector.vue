<template>
  <div class="relative">
    <button 
      @click="toggleDropdown"
      class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors group"
    >
      <svg class="w-5 h-5 text-gray-500 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" 
        />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" 
        />
      </svg>
      <span class="text-sm font-medium text-gray-700">{{ currentCity }}</span>
      <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Выпадающее меню городов -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div 
        v-if="showDropdown"
        class="absolute top-full left-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
      >
        <!-- Поиск города -->
        <div class="p-3 border-b border-gray-100">
          <input 
            v-model="citySearch"
            type="text"
            placeholder="Найти город..."
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
        </div>

        <!-- Популярные города -->
        <div class="p-3">
          <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Популярные</h4>
          <div class="grid grid-cols-2 gap-2">
            <button 
              v-for="city in popularCities"
              :key="city"
              @click="selectCity(city)"
              class="text-left px-3 py-2 text-sm rounded hover:bg-blue-50 hover:text-blue-600 transition-colors"
              :class="{ 'bg-blue-50 text-blue-600': city === currentCity }"
            >
              {{ city }}
            </button>
          </div>
        </div>

        <!-- Все города -->
        <div class="max-h-60 overflow-y-auto p-3 border-t border-gray-100">
          <button 
            v-for="city in filteredCities"
            :key="city"
            @click="selectCity(city)"
            class="block w-full text-left px-3 py-2 text-sm rounded hover:bg-blue-50 hover:text-blue-600 transition-colors"
          >
            {{ city }}
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const showDropdown = ref(false)
const citySearch = ref('')
const currentCity = ref('Москва')

const popularCities = ['Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 'Нижний Новгород', 'Казань']

const allCities = [
  'Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 
  'Нижний Новгород', 'Казань', 'Челябинск', 'Омск', 'Самара', 
  'Ростов-на-Дону', 'Уфа', 'Красноярск', 'Воронеж', 'Пермь'
]

const filteredCities = computed(() => {
  if (!citySearch.value) return allCities
  return allCities.filter(city => 
    city.toLowerCase().includes(citySearch.value.toLowerCase())
  )
})

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

const selectCity = (city) => {
  currentCity.value = city
  showDropdown.value = false
  // Сохранить в localStorage или отправить на сервер
  localStorage.setItem('selectedCity', city)
  // Перезагрузить страницу с новым городом
  router.reload({ only: ['masters'] })
}

// Закрытие при клике вне
const handleClickOutside = (e) => {
  if (!e.target.closest('.relative')) {
    showDropdown.value = false
  }
}

if (typeof window !== 'undefined') {
  document.addEventListener('click', handleClickOutside)
}
</script>