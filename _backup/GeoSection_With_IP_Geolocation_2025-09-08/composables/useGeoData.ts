/**
 * useGeoData - composable для управления географическими данными
 * 
 * Ответственность:
 * - Централизованное хранение всех geo данных
 * - Координация между компонентами (карта, выезд, зоны, метро, типы)
 * - Загрузка и сохранение данных
 * - Автосохранение при изменениях
 * - Валидация данных
 */

import { ref, reactive, watch, computed } from 'vue'

// Типы
export type OutcallType = 'none' | 'city' | 'zones'

export interface OutcallTypes {
  apartment: boolean
  hotel: boolean
  office: boolean
  sauna: boolean
  house: boolean
}

export interface GeoData {
  address: string
  coordinates: { lat: number; lng: number } | null
  zoom: number
  outcall: OutcallType
  zones: string[]
  metro_stations: string[]
  outcall_apartment: boolean
  outcall_hotel: boolean
  outcall_house: boolean
  outcall_sauna: boolean
  outcall_office: boolean
  taxi_included: boolean
}

// Интерфейсы для эмитов
export interface GeoChangeEvent {
  geoData: GeoData
}

export interface UseGeoDataOptions {
  initialData?: Partial<GeoData>
  onDataChange?: (data: GeoData) => void
  autoSave?: boolean
}

/**
 * Основной composable для работы с geo данными
 */
export function useGeoData(options: UseGeoDataOptions = {}) {
  const { 
    initialData = {}, 
    onDataChange,
    autoSave = true 
  } = options

  // Создание дефолтных данных
  const createDefaultGeoData = (): GeoData => ({
    address: '',
    coordinates: null,
    zoom: 12,
    outcall: 'none',
    zones: [],
    metro_stations: [],
    outcall_apartment: true,  // По умолчанию как в оригинале
    outcall_hotel: false,
    outcall_house: false,
    outcall_sauna: false,
    outcall_office: false,
    taxi_included: false,
    ...initialData
  })

  // Реактивное состояние
  const geoData = reactive<GeoData>(createDefaultGeoData())
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed для удобства работы с типами выезда
  const outcallTypes = computed<OutcallTypes>({
    get: () => ({
      apartment: geoData.outcall_apartment,
      hotel: geoData.outcall_hotel,
      office: geoData.outcall_office,
      sauna: geoData.outcall_sauna,
      house: geoData.outcall_house
    }),
    set: (types: OutcallTypes) => {
      geoData.outcall_apartment = types.apartment
      geoData.outcall_hotel = types.hotel
      geoData.outcall_office = types.office
      geoData.outcall_sauna = types.sauna
      geoData.outcall_house = types.house
    }
  })

  // Методы обновления данных
  
  /**
   * Обновление адреса и координат из карты
   */
  const updateAddress = (address: string) => {
    geoData.address = address
  }

  const updateCoordinates = (coords: { lat: number; lng: number } | null) => {
    geoData.coordinates = coords
  }

  /**
   * Обновление типа выезда
   */
  const updateOutcall = (outcall: OutcallType, shouldClearZones = false) => {
    geoData.outcall = outcall
    
    // Очищаем зоны если нужно (переключение с 'zones' на другой тип)
    if (shouldClearZones || outcall !== 'zones') {
      geoData.zones = []
    }
    
    // Очищаем метро если выбран 'none'
    if (outcall === 'none') {
      geoData.metro_stations = []
      // Сбрасываем типы мест на дефолтные значения
      geoData.outcall_apartment = true
      geoData.outcall_hotel = false
      geoData.outcall_house = false
      geoData.outcall_sauna = false
      geoData.outcall_office = false
      geoData.taxi_included = false
    }
  }

  /**
   * Обновление зон
   */
  const updateZones = (zones: string[]) => {
    geoData.zones = [...zones]
  }

  /**
   * Обновление станций метро
   */
  const updateMetroStations = (stations: string[]) => {
    geoData.metro_stations = [...stations]
  }

  /**
   * Обновление типов мест выезда
   */
  const updateOutcallTypes = (types: OutcallTypes) => {
    outcallTypes.value = types
  }

  /**
   * Обновление настройки такси
   */
  const updateTaxiIncluded = (included: boolean) => {
    geoData.taxi_included = included
  }

  /**
   * Загрузка данных из JSON строки (как в оригинале)
   */
  const loadFromJson = (jsonString: string) => {
    try {
      if (!jsonString) return

      const parsed = JSON.parse(jsonString)
      
      // Обновляем данные безопасно
      Object.assign(geoData, {
        address: parsed.address || '',
        coordinates: parsed.coordinates || null,
        zoom: parsed.zoom || 12,
        outcall: parsed.outcall || 'none',
        zones: parsed.zones || [],
        metro_stations: parsed.metro_stations || [],
        outcall_apartment: parsed.outcall_apartment ?? true,
        outcall_hotel: parsed.outcall_hotel ?? false,
        outcall_house: parsed.outcall_house ?? false,
        outcall_sauna: parsed.outcall_sauna ?? false,
        outcall_office: parsed.outcall_office ?? false,
        taxi_included: parsed.taxi_included ?? false
      })

      error.value = null
    } catch (err) {
      error.value = 'Ошибка загрузки данных: ' + (err as Error).message
      console.error('Ошибка парсинга geo данных:', err)
    }
  }

  /**
   * Конвертация в JSON для сохранения
   */
  const toJson = (): string => {
    return JSON.stringify(geoData)
  }

  /**
   * Получение копии данных
   */
  const getDataCopy = (): GeoData => {
    return { ...geoData }
  }

  /**
   * Сброс данных к дефолтным значениям
   */
  const resetData = () => {
    Object.assign(geoData, createDefaultGeoData())
    error.value = null
  }

  /**
   * Валидация данных
   */
  const validateData = (): { isValid: boolean; errors: string[] } => {
    const errors: string[] = []

    // Проверяем адрес
    if (!geoData.address.trim()) {
      errors.push('Не указан адрес')
    }

    // Проверяем координаты
    if (!geoData.coordinates) {
      errors.push('Не указаны координаты')
    }

    // Проверяем зоны если выбран выезд в зоны
    if (geoData.outcall === 'zones' && geoData.zones.length === 0) {
      errors.push('Не выбраны зоны выезда')
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  // Автосохранение при изменениях (если включено)
  if (autoSave && onDataChange) {
    watch(
      geoData,
      (newData) => {
        onDataChange(newData)
      },
      { deep: true }
    )
  }

  // Возвращаем публичное API
  return {
    // Состояние
    geoData,
    isLoading,
    error,
    outcallTypes,

    // Методы обновления
    updateAddress,
    updateCoordinates,
    updateOutcall,
    updateZones,
    updateMetroStations,
    updateOutcallTypes,
    updateTaxiIncluded,

    // Методы работы с данными
    loadFromJson,
    toJson,
    getDataCopy,
    resetData,
    validateData
  }
}

/**
 * Типы для экспорта
 */
export type { GeoData, OutcallTypes, OutcallType }