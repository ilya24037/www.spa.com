<!-- Фильтр по местоположению -->
<template>
  <div class="space-y-3">
    <!-- Город -->
    <div>
      <label class="text-xs text-gray-600 mb-1 block">Город</label>
      <select
        :value="city"
        class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
        @change="$emit('update:city', ($event.target as HTMLSelectElement).value || null)"
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
      <label class="text-xs text-gray-600 mb-1 block">Район</label>
      <select
        :value="district"
        class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
        @change="$emit('update:district', ($event.target as HTMLSelectElement).value || null)"
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
      <label class="text-xs text-gray-600 mb-1 block">Метро</label>
      <select
        :value="metro"
        class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
        @change="$emit('update:metro', ($event.target as HTMLSelectElement).value || null)"
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
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  city?: string | null
  district?: string | null
  metro?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  city: null,
  district: null,
  metro: null
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
    const districts: Record<string, Array<{value: string, label: string}>> = {
        moscow: [
            { value: 'center', label: 'Центр' },
            { value: 'north', label: 'Север' },
            { value: 'south', label: 'Юг' },
            { value: 'east', label: 'Восток' },
            { value: 'west', label: 'Запад' }
        ],
        spb: [
            { value: 'center', label: 'Центральный' },
            { value: 'vasilievsky', label: 'Василеостровский' },
            { value: 'petrograd', label: 'Петроградский' },
            { value: 'admiralty', label: 'Адмиралтейский' }
        ]
    }
  
    return districts[props.city || ''] || []
})

const availableMetro = computed(() => {
    const metro: Record<string, Array<{value: string, label: string}>> = {
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
  
    return metro[props.city || ''] || []
})
</script>