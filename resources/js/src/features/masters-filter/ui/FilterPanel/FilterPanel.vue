<!-- Панель фильтров как на скриншоте -->
<template>
  <div class="filter-panel bg-white rounded-lg shadow-sm">
    <!-- Заголовок -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Фильтры</h2>
        <span 
          v-if="filterStore.filterCounts?.total" 
          class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded-full"
        >
          {{ filterStore.filterCounts.total }} мастеров
        </span>
      </div>
    </div>

    <div class="p-6 space-y-8">
      <!-- 1. Стоимость -->
      <div>
        <h3 class="text-base font-semibold mb-4">Цена, ₽</h3>
        <div class="flex items-center gap-2">
          <BaseInput
            v-model="filters.priceFrom"
            name="price_from"
            type="number"
            placeholder="58"
            :min="0"
            class="flex-1"
          />
          <span class="text-gray-400 flex-shrink-0">—</span>
          <BaseInput
            v-model="filters.priceTo"
            name="price_to"
            type="number"
            placeholder="167037"
            :min="0"
            class="flex-1"
          />
        </div>
      </div>

      <!-- 2. Вид массажа -->
      <div>
        <h3 class="text-base font-semibold mb-4">Вид массажа</h3>
        <div class="space-y-3">
          <BaseCheckbox
            v-for="type in massageTypes"
            :key="type.value"
            :name="`massage_type_${type.value}`"
            :model-value="filters.massageTypes.includes(type.value)"
            @update:modelValue="toggleMassageType(type.value, $event)"
          >
            <template #label>
              <span class="flex items-center justify-between w-full">
                <span>{{ type.label }}</span>
                <span 
                  v-if="getServiceCount(type.value)" 
                  class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full ml-2"
                >
                  {{ getServiceCount(type.value) }}
                </span>
              </span>
            </template>
          </BaseCheckbox>
        </div>
      </div>

      <!-- 3. Дополнительно -->
      <div>
        <h3 class="text-base font-semibold mb-4">Дополнительно</h3>
        <div class="space-y-3">
          <BaseCheckbox
            v-for="option in additionalOptions"
            :key="option.value"
            :name="`additional_${option.value}`"
            :model-value="filters.additional.includes(option.value)"
            @update:modelValue="toggleAdditional(option.value, $event)"
          >
            <template #label>
              <span class="flex items-center justify-between w-full">
                <span>{{ option.label }}</span>
                <span 
                  v-if="getAdditionalCount(option.value)" 
                  class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full ml-2"
                >
                  {{ getAdditionalCount(option.value) }}
                </span>
              </span>
            </template>
          </BaseCheckbox>
        </div>
      </div>

      <!-- 4. Рейтинг -->
      <div>
        <h3 class="text-base font-semibold mb-4">Рейтинг</h3>
        <div class="space-y-3">
          <BaseRadio
            v-for="rating in ratingOptions"
            :key="rating.value"
            name="rating"
            :model-value="filters.rating"
            :value="rating.value"
            @update:modelValue="filters.rating = $event"
          >
            <div class="flex items-center gap-1">
              <!-- Звезды -->
              <span v-for="i in rating.stars" :key="i" class="text-yellow-400">★</span>
              <span v-for="i in (5 - rating.stars)" :key="`empty-${i}`" class="text-gray-300">★</span>
              <span class="ml-2 text-sm text-gray-700">{{ rating.label }}</span>
            </div>
          </BaseRadio>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="pt-4 space-y-3">
        <PrimaryButton
          @click="applyFilters"
          class="w-full"
        >
          Показать результаты
        </PrimaryButton>
        <SecondaryButton
          @click="resetFilters"
          class="w-full"
        >
          Сбросить фильтры
        </SecondaryButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'
import { useFilterStore } from '../../model/filter.store'

interface Filters {
  priceFrom: number | null
  priceTo: number | null
  massageTypes: string[]
  additional: string[]
  rating: number | null
}

const props = defineProps<{
  modelValue?: Filters
}>()

// Подключаем store для получения счётчиков
const filterStore = useFilterStore()

const emit = defineEmits<{
  'update:modelValue': [filters: Filters]
  'apply': [filters: Filters]
  'reset': []
}>()

// Состояние фильтров
const filters = reactive<Filters>({
  priceFrom: null,
  priceTo: null,
  massageTypes: [],
  additional: [],
  rating: null,
  ...props.modelValue
})

// Опции для фильтров
const massageTypes = [
  { value: 'classic', label: 'Классический массаж' },
  { value: 'sport', label: 'Спортивный массаж' },
  { value: 'thai', label: 'Тайский массаж' },
  { value: 'medical', label: 'Лечебный массаж' },
  { value: 'anti-cellulite', label: 'Антицеллюлитный' },
  { value: 'relaxing', label: 'Расслабляющий' },
  { value: 'facial', label: 'Массаж лица' },
  { value: 'lymphatic', label: 'Лимфодренажный' }
]

const additionalOptions = [
  { value: 'home', label: 'Выезд на дом' },
  { value: 'online', label: 'Онлайн-запись' },
  { value: 'certificate', label: 'Есть сертификаты' }
]

const ratingOptions = [
  { value: 4, stars: 4, label: 'и выше' },
  { value: 3, stars: 3, label: 'и выше' }
]

// Методы для работы с массивами чекбоксов
const toggleMassageType = (value: string, checked: boolean) => {
  if (checked) {
    if (!filters.massageTypes.includes(value)) {
      filters.massageTypes.push(value)
    }
  } else {
    const index = filters.massageTypes.indexOf(value)
    if (index > -1) {
      filters.massageTypes.splice(index, 1)
    }
  }
}

const toggleAdditional = (value: string, checked: boolean) => {
  if (checked) {
    if (!filters.additional.includes(value)) {
      filters.additional.push(value)
    }
  } else {
    const index = filters.additional.indexOf(value)
    if (index > -1) {
      filters.additional.splice(index, 1)
    }
  }
}

// Методы
const applyFilters = () => {
  emit('update:modelValue', { ...filters })
  emit('apply', { ...filters })
}

const resetFilters = () => {
  filters.priceFrom = null
  filters.priceTo = null
  filters.massageTypes = []
  filters.additional = []
  filters.rating = null
  emit('reset')
  applyFilters()
}

// Функции для получения счётчиков
const getServiceCount = (serviceValue: string): number => {
  // Маппинг служб на ID
  const serviceIdMap: Record<string, string> = {
    'classic': '1',
    'sport': '4', 
    'thai': '2',
    'medical': '3',
    'anti-cellulite': '5',
    'relaxing': '6',
    'facial': '7',
    'lymphatic': '8'
  }
  
  const serviceId = serviceIdMap[serviceValue]
  return filterStore.filterCounts?.services?.[serviceId] || 0
}

const getAdditionalCount = (optionValue: string): number => {
  const countMap: Record<string, number> = {
    'home': filterStore.filterCounts?.serviceLocation?.home || 0,
    'online': 0, // Нет данных в моке
    'certificate': filterStore.filterCounts?.rating?.withReviews || 0
  }
  
  return countMap[optionValue] || 0
}
</script>

<style scoped>
.filter-panel {
  width: 320px;
  min-width: 320px;
  max-width: 320px;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
  overflow-x: hidden;
}

/* Предотвращаем выход контента за границы */
.filter-panel * {
  max-width: 100%;
  box-sizing: border-box;
}

/* Скроллбар стилизация */
.filter-panel::-webkit-scrollbar {
  width: 6px;
}

.filter-panel::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 3px;
}

.filter-panel::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.filter-panel::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>