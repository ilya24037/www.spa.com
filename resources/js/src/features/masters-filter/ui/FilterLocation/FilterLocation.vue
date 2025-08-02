<!-- resources/js/src/features/masters-filter/ui/FilterLocation/FilterLocation.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h4 :class="TITLE_CLASSES">Местоположение</h4>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Город -->
      <div>
        <label :class="LABEL_CLASSES">Город</label>
        <select
          :value="city"
          @change="$emit('update:city', $event.target.value || null)"
          :class="SELECT_CLASSES"
        >
          <option value="">Любой город</option>
          <option
            v-for="cityOption in availableCities"
            :key="cityOption.value"
            :value="cityOption.value"
          >
            {{ cityOption.label }}
          </option>
        </select>
      </div>

      <!-- Район -->
      <div v-if="city">
        <label :class="LABEL_CLASSES">Район</label>
        <select
          :value="district"
          @change="$emit('update:district', $event.target.value || null)"
          :class="SELECT_CLASSES"
        >
          <option value="">Любой район</option>
          <option
            v-for="districtOption in availableDistricts"
            :key="districtOption.value"
            :value="districtOption.value"
          >
            {{ districtOption.label }}
          </option>
        </select>
      </div>

      <!-- Метро -->
      <div v-if="city === 'moscow' || city === 'spb'">
        <label :class="LABEL_CLASSES">Метро</label>
        <select
          :value="metro"
          @change="$emit('update:metro', $event.target.value || null)"
          :class="SELECT_CLASSES"
        >
          <option value="">Любое метро</option>
          <option
            v-for="metroOption in availableMetro"
            :key="metroOption.value"
            :value="metroOption.value"
          >
            {{ metroOption.label }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-3'
const TITLE_CLASSES = 'font-medium text-gray-900'
const FIELDS_CONTAINER_CLASSES = 'space-y-3'
const LABEL_CLASSES = 'text-xs text-gray-600 mb-1 block'
const SELECT_CLASSES = 'w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm'

const props = defineProps({
  city: {
    type: String,
    default: null
  },
  district: {
    type: String,
    default: null
  },
  metro: {
    type: String,
    default: null
  }
})

defineEmits(['update:city', 'update:district', 'update:metro'])

// Статичные данные (в реальном проекте можно загружать с API)
const availableCities = [
  { value: 'moscow', label: 'Москва' },
  { value: 'spb', label: 'Санкт-Петербург' },
  { value: 'ekaterinburg', label: 'Екатеринбург' },
  { value: 'novosibirsk', label: 'Новосибирск' },
  { value: 'kazan', label: 'Казань' }
]

const availableDistricts = computed(() => {
  const districts = {
    moscow: [
      { value: 'center', label: 'Центр' },
      { value: 'north', label: 'Север' },
      { value: 'south', label: 'Юг' },
      { value: 'east', label: 'Восток' },
      { value: 'west', label: 'Запад' }
    ],
    spb: [
      { value: 'center', label: 'Центральный' },
      { value: 'vasilievsky', label: 'Васильевский остров' },
      { value: 'petrograd', label: 'Петроградский' },
      { value: 'admiralty', label: 'Адмиралтейский' }
    ]
  }
  
  return districts[props.city] || []
})

const availableMetro = computed(() => {
  const metro = {
    moscow: [
      { value: 'sokolnicheskaya', label: 'Сокольническая линия' },
      { value: 'zamoskvoretskaya', label: 'Замоскворецкая линия' },
      { value: 'arbatsko-pokrovskaya', label: 'Арбатско-Покровская линия' }
    ],
    spb: [
      { value: 'kirovsko-vyborgskaya', label: 'Кировско-Выборгская линия' },
      { value: 'moskovsko-petrogradskaya', label: 'Московско-Петроградская линия' },
      { value: 'nevsko-vasileostrovskaya', label: 'Невско-Василеостровская линия' }
    ]
  }
  
  return metro[props.city] || []
})
</script>