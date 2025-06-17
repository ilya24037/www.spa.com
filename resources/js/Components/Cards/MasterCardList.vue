<!-- resources/js/Components/Cards/MasterCardList.vue -->
<template>
  <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-4">
    <div class="flex gap-4">
      <!-- Фото -->
      <div class="w-24 h-24 flex-shrink-0">
        <img
          :src="master.avatar || '/images/placeholder-avatar.jpg'"
          :alt="master.name"
          class="w-full h-full object-cover rounded-lg"
        >
      </div>

      <!-- Информация -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-semibold text-lg">{{ master.name }}</h3>
            <div class="flex items-center gap-2 mt-1">
              <div class="flex items-center">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-sm font-medium ml-1">{{ master.rating || '4.8' }}</span>
                <span class="text-sm text-gray-500 ml-1">({{ master.reviews_count || 0 }})</span>
              </div>
            </div>
          </div>
          
          <!-- Цена -->
          <div class="text-right">
            <div class="font-bold text-xl">от {{ master.min_price }} ₽</div>
            <div class="text-sm text-gray-500">за сеанс</div>
          </div>
        </div>

        <!-- Услуги -->
        <div class="mt-2 flex flex-wrap gap-1">
          <span
            v-for="(service, index) in master.services?.slice(0, 3)"
            :key="service.id"
            class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"
          >
            {{ service.name }}
          </span>
          <span
            v-if="master.services?.length > 3"
            class="text-xs text-gray-500 px-2 py-1"
          >
            +{{ master.services.length - 3 }}
          </span>
        </div>

        <!-- Действия -->
        <div class="mt-3 flex items-center gap-3">
          <button class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
            Записаться
          </button>
          <button class="p-2 text-gray-400 hover:text-red-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  master: {
    type: Object,
    required: true
  }
})
</script>