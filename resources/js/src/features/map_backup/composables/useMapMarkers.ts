import { ref, computed } from 'vue'
import type { MapMarker } from '../types'
import { MARKER_PRESETS } from '../lib/mapConstants'

export function useMapMarkers() {
  const markers = ref<MapMarker[]>([])
  const selectedMarker = ref<MapMarker | null>(null)
  const hoveredMarker = ref<MapMarker | null>(null)

  /**
   * Получение иконки маркера на основе данных
   */
  const getMarkerIcon = (marker: MapMarker): string => {
    // Если иконка уже задана
    if (marker.icon) return marker.icon

    // Логика выбора иконки на основе данных
    const data = marker.data
    if (!data) return MARKER_PRESETS.gray

    // Например, для мастеров с рейтингом
    if (data.rating !== undefined) {
      if (data.rating >= 4.5) return MARKER_PRESETS.gold
      if (data.rating >= 4) return MARKER_PRESETS.green
      if (data.rating >= 3) return MARKER_PRESETS.blue
    }

    // Для доступных сегодня
    if (data.is_available_today) return MARKER_PRESETS.blue

    return MARKER_PRESETS.gray
  }

  /**
   * Форматирование описания маркера
   */
  const formatMarkerDescription = (marker: MapMarker): string => {
    if (marker.description) return marker.description

    const data = marker.data
    if (!data) return ''

    const parts = []

    // Рейтинг
    if (data.rating) {
      parts.push(`⭐ ${data.rating} (${data.reviews_count || 0} отзывов)`)
    }

    // Цена
    const price = data.price_from || data.price
    if (price) {
      parts.push(`💰 от ${price} ₽`)
    }

    // Адрес
    const address = data.geo?.address || data.address
    if (address) {
      parts.push(`📍 ${address}`)
    }

    // Район
    const district = data.geo?.district || data.district
    if (district) {
      parts.push(`🏘️ ${district}`)
    }

    // Услуги
    if (data.services && data.services.length > 0) {
      const serviceNames = data.services
        .filter((s: any) => s !== null && s !== undefined && s.name)
        .slice(0, 3)
        .map((s: any) => s.name)
        .join(', ')
      if (serviceNames) {
        parts.push(`💆 ${serviceNames}`)
      }
    }

    return parts.join('<br>')
  }

  /**
   * Добавление маркера
   */
  const addMarker = (marker: MapMarker) => {
    // Обогащаем маркер дополнительными данными
    const enrichedMarker = {
      ...marker,
      icon: getMarkerIcon(marker),
      description: formatMarkerDescription(marker)
    }
    markers.value.push(enrichedMarker)
    return enrichedMarker
  }

  /**
   * Добавление нескольких маркеров
   */
  const addMarkers = (newMarkers: MapMarker[]) => {
    const enrichedMarkers = newMarkers.map(marker => ({
      ...marker,
      icon: getMarkerIcon(marker),
      description: formatMarkerDescription(marker)
    }))
    markers.value.push(...enrichedMarkers)
    return enrichedMarkers
  }

  /**
   * Удаление маркера
   */
  const removeMarker = (markerId: string | number) => {
    const index = markers.value.findIndex(m => m.id === markerId)
    if (index !== -1) {
      markers.value.splice(index, 1)
      if (selectedMarker.value?.id === markerId) {
        selectedMarker.value = null
      }
      if (hoveredMarker.value?.id === markerId) {
        hoveredMarker.value = null
      }
    }
  }

  /**
   * Очистка всех маркеров
   */
  const clearMarkers = () => {
    markers.value = []
    selectedMarker.value = null
    hoveredMarker.value = null
  }

  /**
   * Обновление маркера
   */
  const updateMarker = (markerId: string | number, updates: Partial<MapMarker>) => {
    const marker = markers.value.find(m => m.id === markerId)
    if (marker) {
      Object.assign(marker, updates)
      // Обновляем иконку и описание
      marker.icon = getMarkerIcon(marker)
      marker.description = formatMarkerDescription(marker)
    }
  }

  /**
   * Выбор маркера
   */
  const selectMarker = (marker: MapMarker | null) => {
    selectedMarker.value = marker
  }

  /**
   * Наведение на маркер
   */
  const hoverMarker = (marker: MapMarker | null) => {
    hoveredMarker.value = marker
  }

  /**
   * Фильтрация маркеров по границам карты
   */
  const filterMarkersByBounds = (bounds: number[][]): MapMarker[] => {
    if (!bounds || bounds.length !== 2) return markers.value

    const [[swLat, swLng], [neLat, neLng]] = bounds

    return markers.value.filter(marker => {
      return marker.lat >= swLat &&
        marker.lat <= neLat &&
        marker.lng >= swLng &&
        marker.lng <= neLng
    })
  }

  /**
   * Получение ближайших маркеров
   */
  const getNearbyMarkers = (center: { lat: number; lng: number }, radius: number): MapMarker[] => {
    return markers.value.filter(marker => {
      const distance = calculateDistance(center, marker)
      return distance <= radius
    })
  }

  /**
   * Вычисление расстояния между точками (в километрах)
   */
  const calculateDistance = (point1: { lat: number; lng: number }, point2: { lat: number; lng: number }): number => {
    const R = 6371 // Радиус Земли в км
    const dLat = toRad(point2.lat - point1.lat)
    const dLng = toRad(point2.lng - point1.lng)
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(point1.lat)) * Math.cos(toRad(point2.lat)) *
      Math.sin(dLng / 2) * Math.sin(dLng / 2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
    return R * c
  }

  const toRad = (value: number): number => {
    return value * Math.PI / 180
  }

  // Вычисляемые свойства
  const markersCount = computed(() => markers.value.length)
  const hasMarkers = computed(() => markers.value.length > 0)
  const hasSelectedMarker = computed(() => selectedMarker.value !== null)

  return {
    markers,
    selectedMarker,
    hoveredMarker,
    markersCount,
    hasMarkers,
    hasSelectedMarker,
    addMarker,
    addMarkers,
    removeMarker,
    clearMarkers,
    updateMarker,
    selectMarker,
    hoverMarker,
    filterMarkersByBounds,
    getNearbyMarkers,
    getMarkerIcon,
    formatMarkerDescription
  }
}