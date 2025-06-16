<!-- resources/js/Components/Header/CityModal.vue -->
<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Оверлей -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>
        
        <!-- Модальное окно -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
          <div class="relative bg-white rounded-lg max-w-2xl w-full shadow-xl">
            <!-- Заголовок -->
            <div class="flex items-center justify-between p-6 border-b">
              <h3 class="text-lg font-semibold">Выберите город</h3>
              <button 
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 transition"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Поиск -->
            <div class="p-6 border-b">
              <input 
                v-model="searchQuery"
                type="text"
                placeholder="Начните вводить название города"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @input="filterCities"
              >
            </div>

            <!-- Контент -->
            <div class="p-6">
              <!-- Популярные города -->
              <div v-if="!searchQuery" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Популярные города</h4>
                <div class="grid grid-cols-3 gap-2">
                  <button 
                    v-for="city in popularCities"
                    :key="city"
                    @click="selectCity(city)"
                    class="px-4 py-2 text-left rounded-lg hover:bg-blue-50 hover:text-blue-600 transition"
                    :class="{ 'bg-blue-50 text-blue-600': city === currentCity }"
                  >
                    {{ city }}
                  </button>
                </div>
              </div>

              <!-- Все города / Результаты поиска -->
              <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                  {{ searchQuery ? 'Результаты поиска' : 'Все города' }}
                </h4>
                <div class="max-h-64 overflow-y-auto">
                  <div class="grid grid-cols-3 gap-2">
                    <button 
                      v-for="city in filteredCities"
                      :key="city"
                      @click="selectCity(city)"
                      class="px-4 py-2 text-left rounded-lg hover:bg-blue-50 hover:text-blue-600 transition text-sm"
                      :class="{ 'bg-blue-50 text-blue-600': city === currentCity }"
                    >
                      {{ city }}
                    </button>
                  </div>
                  <div v-if="filteredCities.length === 0" class="text-gray-500 text-center py-4">
                    Города не найдены
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  show: Boolean,
  currentCity: String
})

const emit = defineEmits(['close', 'select'])

const searchQuery = ref('')

const popularCities = [
  'Москва',
  'Санкт-Петербург',
  'Новосибирск',
  'Екатеринбург',
  'Нижний Новгород',
  'Казань',
  'Челябинск',
  'Омск',
  'Самара'
]

const allCities = [
  'Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 
  'Нижний Новгород', 'Казань', 'Челябинск', 'Омск', 'Самара', 
  'Ростов-на-Дону', 'Уфа', 'Красноярск', 'Воронеж', 'Пермь',
  'Волгоград', 'Краснодар', 'Саратов', 'Тюмень', 'Тольятти',
  'Ижевск', 'Барнаул', 'Ульяновск', 'Иркутск', 'Хабаровск',
  'Ярославль', 'Владивосток', 'Махачкала', 'Томск', 'Оренбург',
  'Кемерово', 'Новокузнецк', 'Рязань', 'Астрахань', 'Набережные Челны',
  'Пенза', 'Киров', 'Липецк', 'Чебоксары', 'Балашиха',
  'Калининград', 'Тула', 'Курск', 'Севастополь', 'Сочи',
  'Ставрополь', 'Улан-Удэ', 'Тверь', 'Магнитогорск', 'Иваново',
  'Брянск', 'Белгород', 'Сургут', 'Владимир', 'Нижний Тагил'
].sort()

const filteredCities = ref(allCities)

const filterCities = () => {
  if (!searchQuery.value) {
    filteredCities.value = allCities
  } else {
    filteredCities.value = allCities.filter(city => 
      city.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
}

const selectCity = (city) => {
  localStorage.setItem('selectedCity', city)
  emit('select', city)
  emit('close')
  
  // Перезагружаем страницу с новым городом
  router.reload({ only: ['masters', 'categories'] })
}
</script>