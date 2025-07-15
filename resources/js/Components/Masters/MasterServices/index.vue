<template>
  <div class="master-services">
    <h2 class="section-title">Услуги и цены</h2>
    
    <div class="services-grid">
      <div 
        v-for="service in services" 
        :key="service.id"
        class="service-card"
      >
        <div class="service-header">
          <h3 class="service-name">{{ service.name }}</h3>
          <div class="service-price">
            {{ formatPrice(service.price) }} ₽
          </div>
        </div>
        
        <div class="service-duration">
          {{ service.duration }} мин
        </div>
        
        <div v-if="service.description" class="service-description">
          {{ service.description }}
        </div>
        
        <button 
          @click="bookService(service)"
          class="book-btn"
        >
          Записаться
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  services: {
    type: Array,
    default: () => []
  }
})

const formatPrice = (price) => {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

const bookService = (service) => {
  // Логика бронирования
  console.log('Бронируем услугу:', service.name)
}
</script>

<style scoped>
.master-services {
  @apply bg-white rounded-lg p-6 shadow-sm;
}

.section-title {
  @apply text-xl font-bold text-gray-900 mb-6;
}

.services-grid {
  @apply space-y-4;
}

.service-card {
  @apply p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors;
}

.service-header {
  @apply flex justify-between items-start mb-2;
}

.service-name {
  @apply text-lg font-medium text-gray-900;
}

.service-price {
  @apply text-xl font-bold text-blue-600;
}

.service-duration {
  @apply text-sm text-gray-500 mb-2;
}

.service-description {
  @apply text-gray-600 mb-4;
}

.book-btn {
  @apply w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors;
}
</style> 