<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-2xl font-bold mb-4">Тест Яндекс.Карт</h1>
      
      <!-- Тестовая карта -->
      <YandexMapNative
        :height="500"
        :show-search="true"
        :masters="testMasters"
        @address-select="handleAddressSelect"
        @ready="handleMapReady"
      />
      
      <div v-if="selectedAddress" class="mt-4 p-4 bg-white rounded-lg shadow">
        <h2 class="font-bold mb-2">Выбранный адрес:</h2>
        <pre>{{ JSON.stringify(selectedAddress, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import YandexMapNative from '@/src/features/map/components/YandexMapNative.vue'

const selectedAddress = ref(null)

const testMasters = ref([
  {
    id: 1,
    name: 'Мастер 1',
    lat: 55.7558,
    lng: 37.6173
  },
  {
    id: 2,
    name: 'Мастер 2',
    lat: 55.7522,
    lng: 37.6156
  }
])

const handleAddressSelect = (location: any) => {
  console.log('📍 Адрес выбран:', location)
  selectedAddress.value = location
}

const handleMapReady = () => {
  console.log('✅ Карта готова к работе')
}
</script>