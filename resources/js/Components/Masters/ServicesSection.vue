<!-- resources/js/Components/Masters/ServicesSection.vue -->
<template>
  <ContentCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Услуги и цены</h2>
        <span class="text-sm text-gray-500">
          {{ services.length }} {{ servicesText }}
        </span>
      </div>
    </template>
    
    <!-- Фильтр по категориям -->
    <div v-if="categories.length > 1" class="mb-6">
      <div class="flex flex-wrap gap-2">
        <button
          @click="selectedCategory = null"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
          :class="selectedCategory === null 
            ? 'bg-indigo-600 text-white' 
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
        >
          Все услуги
        </button>
        <button
          v-for="category in categories"
          :key="category"
          @click="selectedCategory = category"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
          :class="selectedCategory === category 
            ? 'bg-indigo-600 text-white' 
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
        >
          {{ category }}
        </button>
      </div>
    </div>
    
    <!-- Список услуг -->
    <div class="space-y-3">
      <TransitionGroup
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 transform translate-y-2"
        leave-active-class="transition-all duration-200"
        leave-to-class="opacity-0 transform scale-95"
      >
        <div 
          v-for="service in filteredServices"
          :key="service.id"
          class="service-item group"
          @click="selectService(service)"
        >
          <!-- Основная информация -->
          <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-all cursor-pointer">
            <div class="flex-1 pr-4">
              <!-- Название и категория -->
              <div class="flex items-start gap-3 mb-2">
                <h3 class="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">
                  {{ service.name }}
                </h3>
                <!-- Популярная услуга -->
                <span 
                  v-if="service.bookings_count > 10"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
                >
                  Популярно
                </span>
              </div>
              
              <!-- Категория и длительность -->
              <div class="flex items-center gap-4 text-sm text-gray-600 mb-2">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  {{ service.category }}
                </span>
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ service.duration }} мин
                </span>
              </div>
              
              <!-- Описание -->
              <p v-if="service.description" class="text-sm text-gray-500 line-clamp-2">
                {{ service.description }}
              </p>
              
              <!-- Дополнительные опции -->
              <div v-if="service.features?.length" class="mt-3 flex flex-wrap gap-2">
                <span 
                  v-for="feature in service.features"
                  :key="feature"
                  class="inline-flex items-center px-2 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-medium"
                >
                  {{ feature }}
                </span>
              </div>
            </div>
            
            <!-- Цена и действие -->
            <div class="text-right flex-shrink-0">
              <div class="mb-2">
                <!-- Старая цена со скидкой -->
                <div v-if="service.old_price" class="text-sm text-gray-500 line-through">
                  {{ formatPrice(service.old_price) }} ₽
                </div>
                <!-- Текущая цена -->
                <div class="text-xl font-bold text-indigo-600">
                  {{ formatPrice(service.price) }} ₽
                </div>
                <!-- Скидка -->
                <div v-if="service.discount_percent" class="text-xs text-green-600 font-medium">
                  -{{ service.discount_percent }}%
                </div>
              </div>
              
              <button 
                @click.stop="$emit('book-service', service)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
              >
                Записаться
              </button>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>
    
    <!-- Пустое состояние -->
    <div v-if="filteredServices.length === 0" class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-gray-500">Услуги в этой категории не найдены</p>
    </div>
  </ContentCard>
</template>

<script setup>
import { ref, computed } from 'vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'

const props = defineProps({
  services: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['book-service', 'select-service'])

// Состояние
const selectedCategory = ref(null)

// Вычисляемые свойства
const categories = computed(() => {
  const cats = [...new Set(props.services.map(s => s.category))]
  return cats.filter(Boolean)
})

const filteredServices = computed(() => {
  if (!selectedCategory.value) {
    return props.services
  }
  return props.services.filter(s => s.category === selectedCategory.value)
})

const servicesText = computed(() => {
  const count = props.services.length
  const lastDigit = count % 10
  const lastTwoDigits = count % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'услуг'
  if (lastDigit === 1) return 'услуга'
  if (lastDigit >= 2 && lastDigit <= 4) return 'услуги'
  return 'услуг'
})

// Методы
const selectService = (service) => {
  emit('select-service', service)
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price || 0)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>