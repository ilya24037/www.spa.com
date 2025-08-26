import { ref, computed, watch } from 'vue'
import { useFilterStore } from '@/src/features/masters-filter/model/filter.store'
import type { MapMarker } from '@/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue'

interface Master {
  id: number | string
  name: string
  rating?: number
  reviews_count?: number
  price?: number
  price_from?: number
  address?: string
  lat?: number
  lng?: number
  geo?: {
    lat: number
    lng: number
    address?: string
    district?: string
    city?: string
  }
  coordinates?: {
    lat: number
    lng: number
  }
  photo?: string
  services?: Array<{ id: number; name: string } | null>
  district?: string | null
  city?: string | null
  is_online?: boolean
  is_available_today?: boolean
  is_premium?: boolean
  is_verified?: boolean
}

// Константы координат для Перми
const PERM_CENTER = { lat: 58.0105, lng: 56.2502 }
const DEFAULT_ZOOM = 12

export function useMapWithMasters(initialMasters?: Master[]) {
  // Store для фильтров
  const filterStore = useFilterStore()
  
  // Состояние - используем начальные данные если переданы
  const masters = ref<Master[]>(initialMasters || [])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const selectedMaster = ref<Master | null>(null)
  const mapCenter = ref({ lat: PERM_CENTER.lat, lng: PERM_CENTER.lng })
  const mapZoom = ref(DEFAULT_ZOOM)
  
  // Преобразование мастеров в маркеры для карты
  const mapMarkers = computed<MapMarker[]>(() => {
    return masters.value.map(master => {
      // Получаем координаты из разных возможных полей
      let lat: number | undefined
      let lng: number | undefined
      
      // Приоритет: geo -> coordinates -> отдельные поля lat/lng
      if (master.geo && typeof master.geo.lat === 'number' && typeof master.geo.lng === 'number') {
        lat = master.geo.lat
        lng = master.geo.lng
      } else if (master.coordinates && typeof master.coordinates.lat === 'number' && typeof master.coordinates.lng === 'number') {
        lat = master.coordinates.lat
        lng = master.coordinates.lng
      } else if (typeof master.lat === 'number' && typeof master.lng === 'number') {
        lat = master.lat
        lng = master.lng
      }
      
      // Пропускаем мастеров без координат
      if (!lat || !lng) {
        return null
      }
      
      return {
        id: master.id,
        lat,
        lng,
        title: master.name,
        description: formatMasterDescription(master),
        icon: getMarkerIcon(master),
        data: master
      }
    }).filter(marker => marker !== null) as MapMarker[]
  })
  
  // Форматирование описания мастера для балуна
  const formatMasterDescription = (master: Master): string => {
    const parts = []
    
    if (master.rating) {
      parts.push(`⭐ ${master.rating} (${master.reviews_count || 0} отзывов)`)
    }
    
    // Используем price_from или price
    const price = master.price_from || master.price
    if (price) {
      parts.push(`💰 от ${price} ₽`)
    }
    
    // Используем адрес из geo или из отдельного поля
    const address = master.geo?.address || master.address
    if (address) {
      parts.push(`📍 ${address}`)
    }
    
    // Добавляем район если есть
    const district = master.geo?.district || master.district
    if (district) {
      parts.push(`🏘️ ${district}`)
    }
    
    // Фильтруем services от null значений
    if (master.services && master.services.length > 0) {
      const serviceNames = master.services
        .filter(s => s !== null && s !== undefined && s.name) // Фильтруем null/undefined
        .slice(0, 3)
        .map(s => s!.name)
        .join(', ')
      if (serviceNames) {
        parts.push(`💆 ${serviceNames}`)
      }
    }
    
    return parts.join('<br>')
  }
  
  // Получение иконки маркера в зависимости от рейтинга
  const getMarkerIcon = (master: Master): string => {
    if (master.rating && master.rating >= 4.5) {
      return 'islands#goldIcon' // Золотой для топовых
    } else if (master.rating && master.rating >= 4) {
      return 'islands#greenIcon' // Зеленый для хороших
    } else if (master.is_available_today) {
      return 'islands#blueIcon' // Синий для доступных сегодня
    }
    return 'islands#grayIcon' // Серый для остальных
  }
  
  // Загрузка мастеров с учетом фильтров
  const loadMasters = async (skipInitial: boolean = false) => {
    // Если есть начальные данные и это первая загрузка - пропускаем
    if (skipInitial && initialMasters && initialMasters.length > 0) {
      return
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      // Получаем параметры фильтров
      const queryParams = filterStore.getQueryParams()
      
      // Строим URL с параметрами
      const params = new URLSearchParams(queryParams)
      const url = `/api/masters?${params.toString()}`
      
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      if (!response.ok) {
        // Если API не доступен и есть начальные данные - используем их
        if (initialMasters && initialMasters.length > 0) {
          console.warn('API не доступен, используем начальные данные')
          return
        }
        throw new Error('Не удалось загрузить мастеров')
      }
      
      const data = await response.json()
      masters.value = data.data || []
      
      // Обновляем центр карты если есть локация в фильтрах
      if (filterStore.filters.location.lat && filterStore.filters.location.lng) {
        mapCenter.value = {
          lat: filterStore.filters.location.lat,
          lng: filterStore.filters.location.lng
        }
      }
      
    } catch (err) {
      console.error('Ошибка загрузки мастеров:', err)
      error.value = err instanceof Error ? err.message : 'Произошла ошибка'
      
      // Если есть начальные данные - используем их
      if (initialMasters && initialMasters.length > 0) {
        console.warn('Используем начальные данные из-за ошибки загрузки')
        masters.value = initialMasters
      } else {
        masters.value = []
      }
    } finally {
      isLoading.value = false
    }
  }
  
  // Обработчик клика по маркеру
  const handleMarkerClick = (marker: MapMarker) => {
    selectedMaster.value = marker.data as Master
  }
  
  // Обработчик клика по кластеру
  const handleClusterClick = (markers: MapMarker[]) => {
    // Можно показать список мастеров в кластере
    // Debug log removed
  }
  
  // Обработчик изменения границ карты для ленивой загрузки
  const handleBoundsChange = (bounds: any) => {
    // bounds - это массив [[southWest], [northEast]] координат
    if (!bounds || bounds.length !== 2) return
    
    // Сохраняем текущие границы для последующих запросов
    const currentBounds = {
      sw_lat: bounds[0][0],
      sw_lng: bounds[0][1],
      ne_lat: bounds[1][0],
      ne_lng: bounds[1][1]
    }
    
    // Можно добавить фильтрацию существующих маркеров по границам
    // или сделать новый запрос к API с параметрами границ
    
    // Пример: фильтрация уже загруженных маркеров
    const visibleMarkers = masters.value.filter(master => {
      const lat = master.geo?.lat || master.lat
      const lng = master.geo?.lng || master.lng
      
      if (!lat || !lng) return false
      
      return lat >= currentBounds.sw_lat && 
             lat <= currentBounds.ne_lat &&
             lng >= currentBounds.sw_lng && 
             lng <= currentBounds.ne_lng
    })
    
    // Для оптимизации можно отображать только видимые маркеры
    // если их слишком много
    if (masters.value.length > 100) {
      // Эмитим событие с отфильтрованными маркерами
      // Это можно использовать для обновления отображения
      return visibleMarkers
    }
  }
  
  // Обновление локации в фильтрах
  const updateFilterLocation = (lat: number, lng: number, address?: string) => {
    filterStore.setLocation(address || '', lat, lng)
  }
  
  // Следим за изменениями фильтров
  watch(
    () => filterStore.filters,
    () => {
      loadMasters()
    },
    { deep: true }
  )
  
  // Загружаем мастеров при инициализации только если нет начальных данных
  if (!initialMasters || initialMasters.length === 0) {
    loadMasters()
  }
  
  return {
    // Состояние
    masters,
    mapMarkers,
    isLoading,
    error,
    selectedMaster,
    mapCenter,
    mapZoom,
    
    // Методы
    loadMasters,
    handleMarkerClick,
    handleClusterClick,
    handleBoundsChange,
    updateFilterLocation
  }
}