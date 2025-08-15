import { ref, computed, watch } from 'vue'
import { useFilterStore } from '@/src/features/masters-filter/model/filter.store'
import type { MapMarker } from '@/src/shared/ui/molecules/YandexMapPicker/YandexMapPicker.vue'

interface Master {
  id: number | string
  name: string
  rating?: number
  reviews_count?: number
  price?: number
  address?: string
  lat?: number
  lng?: number
  photo?: string
  services?: Array<{ id: number; name: string }>
  is_online?: boolean
  is_available_today?: boolean
}

export function useMapWithMasters() {
  // Store для фильтров
  const filterStore = useFilterStore()
  
  // Состояние
  const masters = ref<Master[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const selectedMaster = ref<Master | null>(null)
  const mapCenter = ref({ lat: 58.0105, lng: 56.2502 }) // Пермь по умолчанию
  const mapZoom = ref(12)
  
  // Преобразование мастеров в маркеры для карты
  const mapMarkers = computed<MapMarker[]>(() => {
    return masters.value.map(master => ({
      id: master.id,
      lat: master.lat || mapCenter.value.lat + (Math.random() - 0.5) * 0.1,
      lng: master.lng || mapCenter.value.lng + (Math.random() - 0.5) * 0.1,
      title: master.name,
      description: formatMasterDescription(master),
      icon: getMarkerIcon(master),
      data: master
    }))
  })
  
  // Форматирование описания мастера для балуна
  const formatMasterDescription = (master: Master): string => {
    const parts = []
    
    if (master.rating) {
      parts.push(`⭐ ${master.rating} (${master.reviews_count || 0} отзывов)`)
    }
    
    if (master.price) {
      parts.push(`💰 от ${master.price} ₽`)
    }
    
    if (master.address) {
      parts.push(`📍 ${master.address}`)
    }
    
    if (master.services && master.services.length > 0) {
      const serviceNames = master.services.slice(0, 3).map(s => s.name).join(', ')
      parts.push(`💆 ${serviceNames}`)
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
  const loadMasters = async () => {
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
      
      // Используем моковые данные для демонстрации
      masters.value = generateMockMasters()
    } finally {
      isLoading.value = false
    }
  }
  
  // Генерация моковых данных для демонстрации
  const generateMockMasters = (): Master[] => {
    const services = [
      { id: 1, name: 'Классический массаж' },
      { id: 2, name: 'Тайский массаж' },
      { id: 3, name: 'Лечебный массаж' },
      { id: 4, name: 'Спортивный массаж' },
      { id: 5, name: 'Антицеллюлитный' },
      { id: 6, name: 'Расслабляющий' }
    ]
    
    const addresses = [
      'ул. Ленина, 45',
      'ул. Комсомольский проспект, 29',
      'ул. Екатерининская, 120',
      'ул. Сибирская, 35',
      'ул. Петропавловская, 55',
      'ул. Куйбышева, 88'
    ]
    
    return Array.from({ length: 20 }, (_, i) => ({
      id: i + 1,
      name: `Мастер ${i + 1}`,
      rating: 3.5 + Math.random() * 1.5,
      reviews_count: Math.floor(Math.random() * 50),
      price: 1500 + Math.floor(Math.random() * 3500),
      address: addresses[Math.floor(Math.random() * addresses.length)],
      lat: mapCenter.value.lat + (Math.random() - 0.5) * 0.1,
      lng: mapCenter.value.lng + (Math.random() - 0.5) * 0.1,
      photo: `/images/masters/master-${(i % 5) + 1}.jpg`,
      services: services.slice(0, Math.floor(Math.random() * 3) + 1),
      is_online: Math.random() > 0.5,
      is_available_today: Math.random() > 0.7
    }))
  }
  
  // Обработчик клика по маркеру
  const handleMarkerClick = (marker: MapMarker) => {
    selectedMaster.value = marker.data as Master
  }
  
  // Обработчик клика по кластеру
  const handleClusterClick = (markers: MapMarker[]) => {
    // Можно показать список мастеров в кластере
    console.log('Кластер содержит мастеров:', markers.length)
  }
  
  // Обработчик изменения границ карты
  const handleBoundsChange = (bounds: any) => {
    // Можно загружать только мастеров в видимой области
    console.log('Границы карты изменились:', bounds)
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
  
  // Загружаем мастеров при инициализации
  loadMasters()
  
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