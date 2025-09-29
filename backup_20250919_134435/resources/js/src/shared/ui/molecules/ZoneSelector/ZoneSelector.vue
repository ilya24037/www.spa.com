<template>
  <div class="zone-selector">
    <button 
      @click="openModal"
      type="button"
      class="w-full p-3 text-left bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-colors"
    >
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <div v-if="selectedZones.length === 0" class="text-gray-500">
            Выберите районы
          </div>
          <div v-else class="text-gray-900">
            {{ selectedZones.join(', ') }}
          </div>
        </div>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
    </button>

    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal"></div>
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Районы</h3>
          <button @click="closeModal" class="p-1 text-gray-400 hover:text-gray-600 rounded">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="p-4 border-b border-gray-100">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input v-model="searchQuery" type="text" placeholder="Поиск" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" />
          </div>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
          <div class="space-y-2">
            <label v-for="zone in filteredZones" :key="zone" class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded cursor-pointer group">
              <input type="checkbox" :value="zone" :checked="tempSelectedZones.includes(zone)" @change="toggleZone(zone)" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
              <span class="text-sm text-gray-900 group-hover:text-blue-600">{{ zone }}</span>
            </label>
          </div>
        </div>
        <div class="flex items-center justify-between gap-3 p-4 border-t border-gray-200">
          <button @click="saveSelection" :disabled="tempSelectedZones.length === 0" class="px-6 py-2 bg-black text-white text-sm rounded hover:bg-gray-800 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
            Сохранить {{ tempSelectedZones.length > 0 ? `${tempSelectedZones.length} район${getZoneSuffix(tempSelectedZones.length)}` : '' }}
          </button>
          <button @click="resetSelection" class="px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition-colors">Сбросить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  zones: {
    type: Array,
    default: () => ['Академический', 'Алексеевский', 'Алтуфьевский', 'Арбат', 'Аэропорт', 'Бабушкинский', 'Басманный']
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const searchQuery = ref('')
const selectedZones = ref([...props.modelValue])
const tempSelectedZones = ref([])

const filteredZones = computed(() => {
  if (!searchQuery.value) return props.zones
  return props.zones.filter(zone => 
    zone.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

watch(() => props.modelValue, (newValue) => {
  selectedZones.value = [...newValue]
})

const openModal = () => {
  isOpen.value = true
  tempSelectedZones.value = [...selectedZones.value]
  searchQuery.value = ''
}

const closeModal = () => {
  isOpen.value = false
  searchQuery.value = ''
}

const toggleZone = (zone) => {
  const index = tempSelectedZones.value.indexOf(zone)
  if (index > -1) {
    tempSelectedZones.value.splice(index, 1)
  } else {
    tempSelectedZones.value.push(zone)
  }
}

const resetSelection = () => {
  tempSelectedZones.value = []
}

const saveSelection = () => {
  selectedZones.value = [...tempSelectedZones.value]
  emit('update:modelValue', selectedZones.value)
  closeModal()
}

const getZoneSuffix = (count) => {
  if (count === 1) return ''
  if (count >= 2 && count <= 4) return 'а'
  return 'ов'
}
</script>
