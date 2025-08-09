<!-- resources/js/src/entities/master/ui/MasterServices/MasterServices.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h3 :class="TITLE_CLASSES">
      РЈСЃР»СѓРіРё Рё С†РµРЅС‹
    </h3>
    
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
        </div>
        
        <div :class="SERVICE_PRICE_CLASSES">
          <span :class="PRICE_AMOUNT_CLASSES">{{ formatPrice(service.price) }} в‚Ѕ</span>
          <span v-if="service.price_unit" :class="PRICE_UNIT_CLASSES">
            /{{ getPriceUnitLabel(service.price_unit) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
    <div v-if="master.services_additional_info" :class="ADDITIONAL_INFO_CLASSES">
      <h4 :class="ADDITIONAL_TITLE_CLASSES">
        Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ
      </h4>
      <p :class="ADDITIONAL_TEXT_CLASSES">
        {{ master.services_additional_info }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-4'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-500'
const SERVICES_LIST_CLASSES = 'space-y-3'
const SERVICE_ITEM_CLASSES = 'flex items-start justify-between p-4 bg-gray-500 rounded-lg'
const SERVICE_INFO_CLASSES = 'flex-1'
const SERVICE_NAME_CLASSES = 'font-medium text-gray-500'
const SERVICE_DESCRIPTION_CLASSES = 'text-sm text-gray-500 mt-1'
const SERVICE_DURATION_CLASSES = 'flex items-center gap-1 text-xs text-gray-500 mt-2'
const DURATION_ICON_CLASSES = 'w-3 h-3'
const SERVICE_PRICE_CLASSES = 'text-right'
const PRICE_AMOUNT_CLASSES = 'text-lg font-bold text-blue-600'
const PRICE_UNIT_CLASSES = 'text-sm text-gray-500'
const ADDITIONAL_INFO_CLASSES = 'mt-6 p-4 bg-blue-50 rounded-lg'
const ADDITIONAL_TITLE_CLASSES = 'font-medium text-gray-500 mb-2'
const ADDITIONAL_TEXT_CLASSES = 'text-sm text-gray-500'

const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const displayServices = computed(() => {
    return props.master.services || []
})

// РњРµС‚РѕРґС‹
const formatPrice = (price) => {
    if (!price) return '0'
    return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDuration = (duration) => {
    if (!duration) return ''
  
    if (duration < 60) return `${duration} РјРёРЅ`
  
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60
  
    if (minutes === 0) return `${hours} С‡`
    return `${hours} С‡ ${minutes} РјРёРЅ`
}

const getPriceUnitLabel = (unit) => {
    const units = {
        hour: 'С‡Р°СЃ',
        service: 'СѓСЃР»СѓРіР°',
        session: 'СЃРµР°РЅСЃ',
        minute: 'РјРёРЅ',
        day: 'РґРµРЅСЊ'
    }
  
    return units[unit] || unit
}
</script>
