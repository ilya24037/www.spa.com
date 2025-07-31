<template>
  <FormField
    label="Популярные районы"
    hint="Выберите из часто запрашиваемых районов"
  >
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <button
        v-for="district in popularDistricts"
        :key="district.name"
        @click="selectDistrict(district)"
        type="button"
        class="flex flex-col items-center p-4 bg-white border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 group"
      >
        <div class="text-sm font-medium text-gray-900 group-hover:text-blue-700 mb-1">
          {{ district.name }}
        </div>
        <div class="text-xs text-gray-500 group-hover:text-blue-600 text-center">
          {{ district.metro }}
        </div>
      </button>
    </div>
    
    <!-- Подсказка -->
    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center space-x-2">
        <span class="text-blue-600">💡</span>
        <span class="text-sm text-blue-800">
          Клик на район автоматически заполнит адрес и установит координаты
        </span>
      </div>
    </div>
  </FormField>
</template>

<script setup>
import FormField from '@/Components/UI/Forms/FormField.vue'

const emit = defineEmits(['district-selected'])

// Популярные районы Москвы
const popularDistricts = [
  { 
    name: 'Центральный', 
    metro: 'м. Охотный Ряд', 
    lat: 55.7558, 
    lng: 37.6176,
    address: 'Москва, Центральный район'
  },
  { 
    name: 'Арбат', 
    metro: 'м. Арбатская', 
    lat: 55.7520, 
    lng: 37.5925,
    address: 'Москва, Арбат'
  },
  { 
    name: 'Тверской', 
    metro: 'м. Тверская', 
    lat: 55.7664, 
    lng: 37.6156,
    address: 'Москва, Тверской район'
  },
  { 
    name: 'Хамовники', 
    metro: 'м. Сокольники', 
    lat: 55.7342, 
    lng: 37.5970,
    address: 'Москва, Хамовники'
  },
  { 
    name: 'Измайлово', 
    metro: 'м. Измайловская', 
    lat: 55.7882, 
    lng: 37.7536,
    address: 'Москва, Измайлово'
  },
  { 
    name: 'Сокольники', 
    metro: 'м. Сокольники', 
    lat: 55.7887, 
    lng: 37.6707,
    address: 'Москва, Сокольники'
  },
  { 
    name: 'Китай-город', 
    metro: 'м. Китай-Город', 
    lat: 55.7558, 
    lng: 37.6295,
    address: 'Москва, Китай-город'
  },
  { 
    name: 'Замоскворечье', 
    metro: 'м. Новокузнецкая', 
    lat: 55.7423, 
    lng: 37.6298,
    address: 'Москва, Замоскворечье'
  }
]

// Методы
const selectDistrict = (district) => {
  emit('district-selected', {
    address: district.address,
    district: district.name,
    metro: district.metro,
    lat: district.lat,
    lng: district.lng
  })
}
</script>