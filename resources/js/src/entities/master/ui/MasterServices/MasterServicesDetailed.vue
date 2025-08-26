<!-- Enhanced Master Services component inspired by Ozon product page -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h3 :class="TITLE_CLASSES">
      Услуги и цены
    </h3>
    
    <!-- Enhanced services list inspired by Ozon pricing -->
    <div :class="SERVICES_LIST_CLASSES">
      <div
        v-for="service in displayServices"
        :key="service.id"
        :class="SERVICE_ITEM_CLASSES"
      >
        <div :class="SERVICE_INFO_CLASSES">
          <h4 :class="SERVICE_NAME_CLASSES">
            {{ service.name }}
          </h4>
          <p v-if="service.description" :class="SERVICE_DESCRIPTION_CLASSES">
            {{ service.description }}
          </p>
          
          <!-- Service details row -->
          <div class="flex items-center gap-4 mt-2">
            <div v-if="service.duration" :class="SERVICE_DURATION_CLASSES">
              <svg
                :class="DURATION_ICON_CLASSES"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              {{ formatDuration(service.duration) }}
            </div>
            
            <!-- Popular badge -->
            <div v-if="service.is_popular" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
              Популярно
            </div>
            
            <!-- Discount badge -->
            <div v-if="service.discount" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              -{{ service.discount }}%
            </div>
          </div>
        </div>
        
        <!-- Enhanced pricing section -->
        <div :class="SERVICE_PRICE_CLASSES">
          <!-- Old price if discount -->
          <div v-if="service.old_price && service.old_price > service.price" class="text-sm text-gray-400 line-through">
            {{ formatPrice(service.old_price) }} ₽
          </div>
          
          <!-- Current price -->
          <div class="flex items-baseline gap-1">
            <span :class="PRICE_AMOUNT_CLASSES">{{ formatPrice(service.price) }} ₽</span>
            <span v-if="service.price_unit" :class="PRICE_UNIT_CLASSES">
              /{{ getPriceUnitLabel(service.price_unit) }}
            </span>
          </div>
          
          <!-- Price per unit calculation -->
          <div v-if="service.duration && service.price_unit !== 'hour'" class="text-xs text-gray-500 mt-1">
            ≈ {{ Math.round(service.price / (service.duration / 60)) }} ₽/час
          </div>
        </div>
      </div>
    </div>

    <!-- Service packages -->
    <div v-if="servicePackages.length" class="mt-6">
      <h4 class="text-base font-medium text-gray-900 mb-3">Пакеты услуг</h4>
      <div class="grid gap-3">
        <div 
          v-for="package_item in servicePackages" 
          :key="package_item.id"
          class="border border-blue-200 rounded-lg p-4 bg-blue-50"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <h5 class="font-medium text-gray-900">{{ package_item.name }}</h5>
              <p class="text-sm text-gray-600 mt-1">{{ package_item.description }}</p>
              <div class="text-xs text-green-600 font-medium mt-2">
                Экономия {{ formatPrice(package_item.savings) }} ₽
              </div>
            </div>
            <div class="text-right">
              <div class="text-xl font-bold text-gray-900">
                {{ formatPrice(package_item.price) }} ₽
              </div>
              <div class="text-sm text-gray-500 line-through">
                {{ formatPrice(package_item.original_price) }} ₽
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Additional service info -->
    <div v-if="master.services_additional_info" :class="ADDITIONAL_INFO_CLASSES">
      <h4 :class="ADDITIONAL_TITLE_CLASSES">
        Дополнительная информация
      </h4>
      <p :class="ADDITIONAL_TEXT_CLASSES">
        {{ master.services_additional_info }}
      </p>
    </div>

    <!-- Payment and booking info -->
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
      <div class="flex items-start gap-3 text-sm text-gray-600">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <div>
          <div class="font-medium text-gray-900">Условия оплаты:</div>
          <ul class="mt-1 space-y-1">
            <li>• Предоплата не требуется</li>
            <li>• Оплата наличными или картой</li>
            <li>• Возможна отмена за 2 часа до сеанса</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// 🎨 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-4'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const SERVICES_LIST_CLASSES = 'space-y-3'
const SERVICE_ITEM_CLASSES = 'flex items-start justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors'
const SERVICE_INFO_CLASSES = 'flex-1'
const SERVICE_NAME_CLASSES = 'font-medium text-gray-900'
const SERVICE_DESCRIPTION_CLASSES = 'text-sm text-gray-600 mt-1'
const SERVICE_DURATION_CLASSES = 'flex items-center gap-1 text-xs text-gray-500 mt-2'
const DURATION_ICON_CLASSES = 'w-3 h-3'
const SERVICE_PRICE_CLASSES = 'text-right ml-4'
const PRICE_AMOUNT_CLASSES = 'text-lg font-bold text-blue-600'
const PRICE_UNIT_CLASSES = 'text-sm text-gray-500'
const ADDITIONAL_INFO_CLASSES = 'mt-6 p-4 bg-blue-50 rounded-lg'
const ADDITIONAL_TITLE_CLASSES = 'font-medium text-gray-900 mb-2'
const ADDITIONAL_TEXT_CLASSES = 'text-sm text-gray-700'

interface Service {
  id: string | number
  name: string
  description?: string
  price: number
  old_price?: number
  duration?: number
  price_unit?: string
  is_popular?: boolean
  discount?: number
}

interface ServicePackage {
  id: string | number
  name: string
  description: string
  price: number
  original_price: number
  savings: number
}

interface Master {
  services?: Service[]
  services_additional_info?: string
  service_packages?: ServicePackage[]
}

interface Props {
  master: Master
}

const props = defineProps<Props>()

// Вычисляемые свойства
const displayServices = computed(() => {
  return props.master.services || []
})

const servicePackages = computed(() => {
  return props.master.service_packages || []
})

// Методы
const formatPrice = (price: number): string => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDuration = (duration: number): string => {
  if (!duration) return ''
  
  if (duration < 60) return `${duration} мин`
  
  const hours = Math.floor(duration / 60)
  const minutes = duration % 60
  
  if (minutes === 0) return `${hours} ч`
  return `${hours} ч ${minutes} мин`
}

const getPriceUnitLabel = (unit: string): string => {
  const units: Record<string, string> = {
    hour: 'час',
    service: 'услуга',
    session: 'сеанс',
    minute: 'мин',
    day: 'день'
  }
  
  return units[unit] || unit
}
</script>

<style scoped>
/* Enhanced hover effects */
.transition-colors {
  transition: background-color 0.2s ease-in-out;
}

/* Price animation */
.price-highlight {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}
</style>