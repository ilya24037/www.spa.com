import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// TypeScript интерфейсы
export interface City {
  id: number
  name: string
  region: string
  population?: number
  coordinates?: {
    lat: number
    lng: number
  }
  timezone?: string
  isPopular?: boolean
}

export interface CityRegion {
  name: string
  cities: City[]
}

export const useCityStore = defineStore('city-selector', () => {
  // State
  const currentCity = ref<City | null>(null)
  const availableCities = ref<City[]>([])
  const regions = ref<CityRegion[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const modalOpen = ref(false)
  const searchQuery = ref('')
  
  // Популярные города по умолчанию
  const defaultPopularCities: City[] = [
    { id: 1, name: 'Москва', region: 'Московская область', isPopular: true },
    { id: 2, name: 'Санкт-Петербург', region: 'Ленинградская область', isPopular: true },
    { id: 3, name: 'Новосибирск', region: 'Новосибирская область', isPopular: true },
    { id: 4, name: 'Екатеринбург', region: 'Свердловская область', isPopular: true },
    { id: 5, name: 'Нижний Новгород', region: 'Нижегородская область', isPopular: true },
    { id: 6, name: 'Казань', region: 'Республика Татарстан', isPopular: true },
    { id: 7, name: 'Челябинск', region: 'Челябинская область', isPopular: true },
    { id: 8, name: 'Омск', region: 'Омская область', isPopular: true },
    { id: 9, name: 'Самара', region: 'Самарская область', isPopular: true }
  ]
  
  const defaultAllCities: City[] = [
    ...defaultPopularCities,
    { id: 10, name: 'Ростов-на-Дону', region: 'Ростовская область' },
    { id: 11, name: 'Уфа', region: 'Республика Башкортостан' },
    { id: 12, name: 'Красноярск', region: 'Красноярский край' },
    { id: 13, name: 'Воронеж', region: 'Воронежская область' },
    { id: 14, name: 'Пермь', region: 'Пермский край' },
    { id: 15, name: 'Волгоград', region: 'Волгоградская область' },
    { id: 16, name: 'Краснодар', region: 'Краснодарский край' },
    { id: 17, name: 'Саратов', region: 'Саратовская область' },
    { id: 18, name: 'Тюмень', region: 'Тюменская область' },
    { id: 19, name: 'Тольятти', region: 'Самарская область' },
    { id: 20, name: 'Ижевск', region: 'Удмуртская Республика' },
    { id: 21, name: 'Барнаул', region: 'Алтайский край' },
    { id: 22, name: 'Ульяновск', region: 'Ульяновская область' },
    { id: 23, name: 'Иркутск', region: 'Иркутская область' },
    { id: 24, name: 'Хабаровск', region: 'Хабаровский край' },
    { id: 25, name: 'Ярославль', region: 'Ярославская область' },
    { id: 26, name: 'Владивосток', region: 'Приморский край' },
    { id: 27, name: 'Махачкала', region: 'Республика Дагестан' },
    { id: 28, name: 'Томск', region: 'Томская область' },
    { id: 29, name: 'Оренбург', region: 'Оренбургская область' },
    { id: 30, name: 'Кемерово', region: 'Кемеровская область' }
  ]
  
  // Getters
  const currentCityName = computed(() => currentCity.value?.name || 'Москва')
  const popularCities = computed(() => 
    availableCities.value.filter(city => city.isPopular) || defaultPopularCities
  )
  
  const filteredCities = computed(() => {
    if (!searchQuery.value) return availableCities.value || defaultAllCities
    
    const query = searchQuery.value.toLowerCase()
    return (availableCities.value || defaultAllCities).filter(city =>
      city.name.toLowerCase().includes(query) ||
      city.region.toLowerCase().includes(query)
    )
  })
  
  const groupedCities = computed(() => {
    const groups: Record<string, City[]> = {}
    
    filteredCities.value.forEach(city => {
      const region = city.region
      if (!groups[region]) {
        groups[region] = []
      }
      groups[region].push(city)
    })
    
    return Object.keys(groups)
      .sort()
      .map(region => ({
        name: region,
        cities: groups[region]?.sort((a, b) => a.name.localeCompare(b.name)) || []
      }))
  })
  
  const hasResults = computed(() => filteredCities.value.length > 0)
  
  // Actions
  const loadCities = async (): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/cities')
      
      if (response.data?.cities) {
        availableCities.value = response.data.cities
      } else {
        // Fallback на локальные данные
        availableCities.value = defaultAllCities
      }
      
      // Загрузить регионы если есть
      if (response.data?.regions) {
        regions.value = response.data.regions
      }
    } catch (err) {
      console.warn('Failed to load cities from API, using fallback:', err)
      availableCities.value = defaultAllCities
      error.value = null // Не показываем ошибку пользователю, используем fallback
    } finally {
      isLoading.value = false
    }
  }
  
  const selectCity = async (city: City): Promise<void> => {
    currentCity.value = city
    modalOpen.value = false
    searchQuery.value = ''
    
    // Сохранить в localStorage
    try {
      localStorage.setItem('selectedCity', JSON.stringify(city))
    } catch (err) {
      console.warn('Failed to save city to localStorage:', err)
    }
    
    // Отправить аналитику
    try {
      await axios.post('/api/analytics/city-changed', {
        city_id: city.id,
        city_name: city.name
      })
    } catch (err) {
      // Не критично, просто логируем
      console.warn('Failed to send city change analytics:', err)
    }
  }
  
  const detectCurrentCity = async (): Promise<void> => {
    // 1. Попробовать загрузить из localStorage
    try {
      const saved = localStorage.getItem('selectedCity')
      if (saved) {
        const savedCity = JSON.parse(saved)
        currentCity.value = savedCity
        return
      }
    } catch (err) {
      console.warn('Failed to load city from localStorage:', err)
    }
    
    // 2. Попробовать определить по IP
    try {
      const response = await axios.get('/api/detect-city')
      if (response.data?.city) {
        currentCity.value = response.data.city
        return
      }
    } catch (err) {
      console.warn('Failed to detect city by IP:', err)
    }
    
    // 3. Fallback на Москву
    currentCity.value = defaultPopularCities[0] || null
  }
  
  const openModal = (): void => {
    modalOpen.value = true
    searchQuery.value = ''
    
    // Загрузить города если не загружены
    if (availableCities.value.length === 0) {
      loadCities()
    }
  }
  
  const closeModal = (): void => {
    modalOpen.value = false
    searchQuery.value = ''
    error.value = null
  }
  
  const updateSearchQuery = (query: string): void => {
    searchQuery.value = query
  }
  
  const clearSearch = (): void => {
    searchQuery.value = ''
  }
  
  const getCurrentWeather = async (cityId?: number): Promise<any> => {
    const id = cityId || currentCity.value?.id
    if (!id) return null
    
    try {
      const response = await axios.get(`/api/cities/${id}/weather`)
      return response.data?.weather || null
    } catch (err) {
      console.warn('Failed to load weather:', err)
      return null
    }
  }
  
  const getCityServices = async (cityId?: number): Promise<any[]> => {
    const id = cityId || currentCity.value?.id
    if (!id) return []
    
    try {
      const response = await axios.get(`/api/cities/${id}/services`)
      return response.data?.services || []
    } catch (err) {
      console.warn('Failed to load city services:', err)
      return []
    }
  }
  
  const reset = (): void => {
    currentCity.value = null
    availableCities.value = []
    regions.value = []
    modalOpen.value = false
    searchQuery.value = ''
    error.value = null
    isLoading.value = false
  }
  
  // Инициализация при создании store
  detectCurrentCity()
  
  return {
    // State
    currentCity,
    availableCities,
    regions,
    isLoading,
    error,
    modalOpen,
    searchQuery,
    
    // Getters
    currentCityName,
    popularCities,
    filteredCities,
    groupedCities,
    hasResults,
    
    // Actions
    loadCities,
    selectCity,
    detectCurrentCity,
    openModal,
    closeModal,
    updateSearchQuery,
    clearSearch,
    getCurrentWeather,
    getCityServices,
    reset
  }
})