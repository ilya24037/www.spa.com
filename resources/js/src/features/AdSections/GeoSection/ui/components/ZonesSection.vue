<template>
  <div class="zones-section">
    <!-- Секция показывается только когда выбран выезд "В выбранные зоны" -->
    <div v-if="shouldShow" class="transition-all duration-200">
      <p class="text-sm text-gray-600 mb-3">
        Выберите районы, в которые вы готовы выезжать:
      </p>
      
      <!-- Индикатор загрузки районов -->
      <div v-if="isLoadingZones" class="mb-3">
        <div class="flex items-center gap-2 text-sm text-gray-500">
          <div class="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-blue-500"></div>
          <span>Загружаем районы города...</span>
        </div>
      </div>
      
      <!-- Использую существующий ZoneSelector -->
      <ZoneSelector 
        v-else
        :model-value="currentZones"
        :zones="availableZones"
        @update:modelValue="handleZonesChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * ZonesSection - компонент выбора зон выезда
 * 
 * Ответственность (Single Responsibility):
 * - Показ/скрытие секции зон в зависимости от типа выезда
 * - Передача списка доступных зон в ZoneSelector
 * - Обработка изменений выбранных зон
 * 
 * НЕ содержит:
 * - Логику радиокнопок (OutcallSection.vue)
 * - Логику метро (MetroSection.vue)
 * - Логику карты (AddressMapSection.vue)
 * - Внутреннюю логику селектора зон (делегируется ZoneSelector)
 */

import { ref, computed, watch, onMounted } from 'vue'
import ZoneSelector from '@/src/shared/ui/molecules/ZoneSelector/ZoneSelector.vue'
import { DEFAULT_CITY_DISTRICTS } from '@/src/shared/config/cities'

// Типы
type OutcallType = 'none' | 'city' | 'zones'

// Интерфейсы
interface Props {
  outcallType?: OutcallType
  initialZones?: string[]
  currentCity?: string
}

interface Emits {
  'update:zones': [zones: string[]]
  'zones-changed': [data: { zones: string[] }]
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<Props>(), {
  outcallType: 'none',
  initialZones: () => [],
  currentCity: ''
})

// Emits
const emit = defineEmits<Emits>()

// Реактивные доступные зоны (загружаются динамически для каждого города)
const availableZones = ref<string[]>([])
const isLoadingZones = ref(false)

// 🛡️ Флаг инициализации для предотвращения очистки данных при первой загрузке
const isInitializing = ref(true)

// Получение районов города через Yandex Geocoder API
const fetchCityDistricts = async (cityName: string): Promise<string[]> => {
  if (!cityName) return []
  
  try {
    isLoadingZones.value = true
    
    // Ищем районы города через Geocoder API (тот же что используется в AddressMapSection)
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?format=json&geocode=${encodeURIComponent(cityName + ' район')}&results=20&lang=ru_RU&apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18`
    )
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    const found = data?.response?.GeoObjectCollection?.featureMember || []
    
    // Извлекаем названия районов и фильтруем
    const districts = found
      .map((item: any) => {
        const geoObject = item.GeoObject
        const fullText = geoObject.metaDataProperty?.GeocoderMetaData?.text || ''
        // Извлекаем название района из полного адреса
        const parts = fullText.split(',')
        return parts.find(part => part.trim().includes('район'))?.trim()
      })
      .filter((district: string | undefined) => {
        return district && 
               district.includes('район') && 
               !district.includes('округ') &&
               !district.includes('область')
      })
      .slice(0, 10) // Ограничиваем 10 районами для UI
    
    return [...new Set(districts)] // Убираем дубли
    
  } catch (error) {
    console.warn('Ошибка загрузки районов для', cityName, ':', error)
    // В случае ошибки возвращаем дефолтные районы для крупных городов
    return getDefaultDistricts(cityName)
  } finally {
    isLoadingZones.value = false
  }
}

// Дефолтные районы для крупных городов (расширенный fallback)
const getDefaultDistricts = (cityName: string): string[] => {
  // Используем импортированные дефолтные районы из конфига
  return DEFAULT_CITY_DISTRICTS[cityName as keyof typeof DEFAULT_CITY_DISTRICTS] || []
}

// Реактивное состояние
const currentZones = ref<string[]>([...props.initialZones])

// Показывать секцию только если выбран тип "zones"
const shouldShow = computed(() => props.outcallType === 'zones')

// Обработка изменения выбранных зон
const handleZonesChange = (zones: string[]) => {
  currentZones.value = [...zones]
  
  // Отправляем события родителю
  emit('update:zones', zones)
  emit('zones-changed', { zones })
}

// Следим за изменениями props
watch(() => props.initialZones, (newZones) => {
  if (newZones) {
    currentZones.value = [...newZones]
  }
}, { deep: true })

// Очищаем зоны если outcallType изменился с 'zones' на другой
watch(() => props.outcallType, (newType, oldType) => {
  if (oldType === 'zones' && newType !== 'zones') {
    currentZones.value = []
    emit('update:zones', [])
    emit('zones-changed', { zones: [] })
  }
})

// Загружаем районы при изменении города
watch(() => props.currentCity, async (newCity, oldCity) => {
  if (newCity) {
    // Всегда загружаем районы для города, если он их поддерживает
    // (не только когда выбрана опция "zones")
    
    // 1. СНАЧАЛА показываем fallback данные мгновенно
    const fallbackDistricts = getDefaultDistricts(newCity)
    if (fallbackDistricts.length > 0) {
      availableZones.value = fallbackDistricts
      isLoadingZones.value = false // Останавливаем индикатор загрузки
    } else if (props.outcallType === 'zones') {
      // Показываем загрузку только если выбрана опция "zones" и нет fallback
      isLoadingZones.value = true
    }
    
    // 2. ОПЦИОНАЛЬНО пытаемся загрузить через API (в фоне) только если выбрана опция zones
    if (props.outcallType === 'zones' && fallbackDistricts.length === 0) {
      try {
        const apiDistricts = await fetchCityDistricts(newCity)
        if (apiDistricts.length > 0) {
          availableZones.value = apiDistricts // Заменяем на API данные если получились
        }
      } catch (error) {
        console.warn('Не удалось загрузить районы через API, используем fallback:', error)
        // Оставляем fallback данные
      } finally {
        isLoadingZones.value = false
      }
    }
    
    // 🛡️ НЕ очищаем зоны при первой инициализации
    if (isInitializing.value) {
      console.log('🔒 [ZonesSection] Инициализация - сохраняем существующие зоны:', currentZones.value)
      return
    }
    
    // Очищаем выбранные зоны ТОЛЬКО при реальной смене города
    if (oldCity && oldCity !== newCity && props.outcallType === 'zones') {
      console.log('🔄 [ZonesSection] Смена города с', oldCity, 'на', newCity, '- очищаем зоны')
      currentZones.value = []
      emit('update:zones', [])
      emit('zones-changed', { zones: [] })
    }
  }
}, { immediate: true })

// 🛡️ Снимаем флаг инициализации после монтирования компонента
onMounted(() => {
  setTimeout(() => {
    isInitializing.value = false
    console.log('🔓 [ZonesSection] Инициализация завершена')
  }, 100)
})
</script>

<style scoped>
/**
 * Стили ZonesSection - минимальные, основная стилизация в ZoneSelector
 */

.zones-section {
  @apply w-full;
}

/* Плавная анимация показа/скрытия */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>