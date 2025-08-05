<template>
  <div class="master-profile-widget">
    <div class="space-y-6">
      <!-- Master Info -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ master.name }}</h1>
        <p class="text-gray-600 mb-4">{{ master.description }}</p>
      </div>
      
      <!-- Services -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Услуги</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="service in master.services" :key="service.id" class="p-4 border rounded-lg">
            <h3 class="font-medium text-gray-900">{{ service.name }}</h3>
            <p class="text-sm text-gray-600">{{ service.price }}</p>
          </div>
        </div>
      </div>
      
      <!-- Gallery -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Фотографии</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="photo in master.photos" :key="photo.id" class="aspect-square">
            <img :src="photo.url" :alt="photo.alt" class="w-full h-full object-cover rounded-lg" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Master {
  id: number
  name: string
  description?: string
  services?: any[]
  photos?: any[]
}

defineProps<{
  master: Master
}>()
</script>