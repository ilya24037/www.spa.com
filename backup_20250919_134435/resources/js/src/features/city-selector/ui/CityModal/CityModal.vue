<!-- Модальное окно выбора города - FSD версия с Tailwind -->
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
      <div 
        v-if="isOpen" 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="modalTitleId"
      >
        <!-- Оверлей -->
        <div 
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
          aria-hidden="true"
          @click="handleClose"
        />
        
        <!-- Контейнер модального окна -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
          <div 
            :class="modalClasses"
            @click.stop
          >
            <!-- Заголовок модального окна -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
              <div>
                <h3 :id="modalTitleId" class="text-lg font-semibold text-gray-900">
                  Выберите город
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                  {{ currentCity?.name ? `Текущий: ${currentCity.name}` : 'Выберите ваш город для персонализации' }}
                </p>
              </div>
              
              <button 
                class="text-gray-500 hover:text-gray-500 transition-colors p-1 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :aria-label="closeButtonAriaLabel"
                @click="handleClose"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <!-- Поиск -->
            <div class="p-6 border-b border-gray-500">
              <div class="relative">
                <input 
                  ref="searchInput"
                  v-model="searchQuery"
                  type="text"
                  placeholder="Начните вводить название города"
                  class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors placeholder-gray-400"
                  aria-label="Поиск города по названию"
                  @input="handleSearchInput"
                  @keydown="handleSearchKeydown"
                >
                
                <!-- РРєРѕРЅРєР° РїРѕРёСЃРєР° -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg
                    class="w-5 h-5 text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                </div>

                <!-- РљРЅРѕРїРєР° РѕС‡РёСЃС‚РєРё -->
                <button
                  v-if="searchQuery"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-500 transition-colors"
                  aria-label="РћС‡РёСЃС‚РёС‚СЊ РїРѕРёСЃРє"
                  @click="clearSearch"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Контент -->
            <div :class="contentClasses">
              <!-- Loading состояние -->
              <div v-if="isLoading" class="p-6">
                <div class="animate-pulse space-y-4">
                  <div class="h-4 bg-gray-500 rounded w-1/4" />
                  <div class="grid grid-cols-3 gap-2">
                    <div v-for="i in 9" :key="i" class="h-10 bg-gray-500 rounded" />
                  </div>
                </div>
              </div>

              <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
              <div v-else-if="error" class="p-6 text-center">
                <div class="text-red-600 mb-4">
                  <svg
                    class="w-12 h-12 mx-auto mb-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"
                    />
                  </svg>
                  <p class="font-medium">
                    РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё РіРѕСЂРѕРґРѕРІ
                  </p>
                </div>
                <p class="text-gray-500 mb-4">
                  {{ error }}
                </p>
                <button
                  class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                  @click="retryLoadCities"
                >
                  Попробовать еще раз
                </button>
              </div>

              <!-- Нормальное состояние -->
              <div v-else>
                <!-- Популярные города (если нет поиска) -->
                <div v-if="!searchQuery && popularCities.length > 0" class="p-6 border-b border-gray-500">
                  <h4 class="text-sm font-semibold text-gray-500 mb-3">
                    Популярные города
                  </h4>
                  <div :class="citiesGridClasses">
                    <button
                      v-for="city in popularCities"
                      :key="`popular-${city.id}`"
                      @click="selectCity(city)"
                      class="px-4 py-2 text-left rounded-lg hover:bg-blue-50 hover:text-blue-600 transition"
                      :class="{ 'bg-blue-50 text-blue-600': city.name === currentCity?.name }"
                    >
                      {{ city.name }}
                    </button>
                  </div>
                </div>

                <!-- Результаты поиска / Все города -->
                <div class="p-6">
                  <h4 class="text-sm font-semibold text-gray-500 mb-3">
                    {{ searchQuery ? `Результаты поиска (${filteredCities.length})` : 'Все города' }}
                  </h4>
                  
                  <!-- Список городов -->
                  <div v-if="hasResults" :class="resultsContainerClasses">
                    <!-- Группировка по регионам -->
                    <template v-if="showGrouped && !searchQuery">
                      <div 
                        v-for="region in groupedCities" 
                        :key="region.name"
                        class="mb-6"
                      >
                        <h5 class="text-xs font-medium text-gray-500 mb-2 sticky top-0 bg-white py-1">
                          {{ region.name }}
                        </h5>
                        <div :class="citiesGridClasses">
                          <button
                            v-for="city in region.cities"
                            :key="`region-${city.id}`"
                            :class="[
                              'px-4 py-2 text-sm text-left rounded-lg border transition-colors',
                              city.name === currentCity 
                                ? 'border-blue-500 bg-blue-50 text-blue-700' 
                                : 'border-gray-200 hover:border-gray-300 text-gray-700 hover:bg-gray-50'
                            ]"
                            @click="selectCity(city)"
                          >
                            {{ city.name }}
                          </button>
                        </div>
                      </div>
                    </template>

                    <!-- Простой список -->
                    <template v-else>
                      <div :class="citiesGridClasses">
                        <button
                          v-for="city in displayedCities"
                          :key="`simple-${city.id}`"
                          @click="selectCity(city)"
                          class="px-4 py-2 text-left rounded-lg hover:bg-blue-50 hover:text-blue-600 transition text-sm"
                          :class="{ 'bg-blue-50 text-blue-600': city.name === currentCity?.name }"
                        >
                          {{ city.name }}
                        </button>
                      </div>
                    </template>
                  </div>

                  <!-- Нет результатов -->
                  <div v-else class="text-center py-8">
                    <svg
                      class="w-16 h-16 mx-auto mb-4 text-gray-500"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                      />
                    </svg>
                    <p class="text-gray-500 mb-2">
                      Города не найдены
                    </p>
                    <p class="text-sm text-gray-500 mb-4">
                      Попробуйте изменить поисковый запрос
                    </p>
                    <button
                      class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                      @click="clearSearch"
                    >
                      Показать все города
                    </button>
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

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
// import CityButton from '../CityButton/CityButton.vue' // Убрали, используем простые кнопки

// Простой интерфейс City (без store)
export interface City {
  id: number
  name: string
  region: string
}

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  show?: boolean
  currentCity?: string
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
  showGrouped?: boolean
  searchPlaceholder?: string
  closeOnSelect?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    currentCity: '',
    maxWidth: '2xl',
    showGrouped: true,
    searchPlaceholder: 'Начните вводить название города...',
    closeOnSelect: true
})

// TypeScript типизация emits  
const emit = defineEmits<{
  'city-selected': [city: City]
  'close': []
  'select': [cityName: string]
}>()

// Refs
const searchInput = ref<HTMLInputElement>()
const modalTitleId = ref(`city-modal-title-${Date.now()}`)
const searchQuery = ref('')
const isLoading = ref(false)
const error = ref('')

// Статичные данные (как в бэкапе)
const popularCitiesData = [
  'Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 
  'Нижний Новгород', 'Казань', 'Челябинск', 'Омск', 'Самара'
]

const allCitiesData = [
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

// Computed
const isOpen = computed(() => props.show)
const currentCity = computed(() => ({ name: props.currentCity || 'Москва' }))

const popularCities = computed(() => 
  popularCitiesData.map((name, index) => ({ 
    id: index + 1, 
    name, 
    region: 'Россия' 
  }))
)

const filteredCities = computed(() => {
  if (!searchQuery.value) {
    return allCitiesData.map((name, index) => ({ 
      id: index + 100, 
      name, 
      region: 'Россия' 
    }))
  }
  
  return allCitiesData
    .filter(city => city.toLowerCase().includes(searchQuery.value.toLowerCase()))
    .map((name, index) => ({ 
      id: index + 200, 
      name, 
      region: 'Россия' 
    }))
})

const groupedCities = computed(() => [])
const hasResults = computed(() => filteredCities.value.length > 0)

const displayedCities = computed(() => {
    // РћРіСЂР°РЅРёС‡РёРІР°РµРј РєРѕР»РёС‡РµСЃС‚РІРѕ РґР»СЏ РїСЂРѕРёР·РІРѕРґРёС‚РµР»СЊРЅРѕСЃС‚Рё
    return filteredCities.value.slice(0, 100)
})

const modalClasses = computed(() => [
    'relative bg-white rounded-lg shadow-xl w-full',
    {
        'max-w-sm': props.maxWidth === 'sm',
        'max-w-md': props.maxWidth === 'md',
        'max-w-lg': props.maxWidth === 'lg',
        'max-w-xl': props.maxWidth === 'xl',
        'max-w-2xl': props.maxWidth === '2xl'
    }
])

const searchInputClasses = computed(() => [
    'w-full pl-10 pr-10 py-3 border border-gray-500 rounded-lg text-sm',
    'focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
    'placeholder-gray-500'
])

const contentClasses = computed(() => [
    'max-h-96 overflow-y-auto'
])

const resultsContainerClasses = computed(() => [
    'max-h-80 overflow-y-auto'
])

const citiesGridClasses = computed(() => [
    'grid gap-2',
    {
        'grid-cols-2 sm:grid-cols-3': props.maxWidth === 'sm' || props.maxWidth === 'md',
        'grid-cols-3 sm:grid-cols-4': props.maxWidth === 'lg' || props.maxWidth === 'xl' || props.maxWidth === '2xl'
    }
])

const closeButtonAriaLabel = computed(() => 'Закрыть окно выбора города')
const searchAriaLabel = computed(() => 'Поиск города по названию')

// Methods  
const handleClose = (): void => {
    emit('close')
}

const selectCity = (city: City): void => {
    emit('city-selected', city)
  
    if (props.closeOnSelect) {
        handleClose()
    }
}

// Простая версия выбора города по строке (для совместимости с бэкапом)
const selectCityByName = (cityName: string): void => {
    const cityObj: City = {
        id: Date.now(),
        name: cityName,
        region: 'Россия'
    }
    // Emit оба события для совместимости
    emit('select', cityName)
    selectCity(cityObj)
}

const handleSearchInput = (): void => {
    // Р”РµР±Р°СѓРЅСЃ СѓР¶Рµ СЂРµР°Р»РёР·РѕРІР°РЅ РІ store С‡РµСЂРµР· computed
}

const handleSearchKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        if (searchQuery.value) {
            clearSearch()
        } else {
            handleClose()
        }
    } else if (event.key === 'Enter') {
    // Р’С‹Р±СЂР°С‚СЊ РїРµСЂРІС‹Р№ СЂРµР·СѓР»СЊС‚Р°С‚
        if (displayedCities.value.length > 0) {
            if (displayedCities.value[0]) {
                selectCity(displayedCities.value[0])
            }
        }
    }
}

const clearSearch = (): void => {
    searchQuery.value = ''
    searchInput.value?.focus()
}

const retryLoadCities = async (): Promise<void> => {
    // Данные уже загружены статично, ничего не делаем
}

// Р—Р°РєСЂС‹С‚РёРµ РїСЂРё РЅР°Р¶Р°С‚РёРё Escape
const handleGlobalKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape' && isOpen.value) {
        handleClose()
    }
}

// Р¤РѕРєСѓСЃ РЅР° РїРѕР»Рµ РїРѕРёСЃРєР° РїСЂРё РѕС‚РєСЂС‹С‚РёРё
watch(isOpen, async (newValue) => {
    if (newValue) {
        // Событие opened не используется
        await nextTick()
        searchInput.value?.focus()
    }
})

// Lifecycle
onMounted(() => {
    document.addEventListener('keydown', handleGlobalKeydown)
    // Данные уже загружены статично
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<!-- Используем только Tailwind классы, кастомных стилей нет -->

