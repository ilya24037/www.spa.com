// Адаптированная логика поиска по городам из dosugbar
// Переписана для Vue 3 Composition API без jQuery

import { ref, computed, watch } from 'vue'

export interface Location {
  id: number
  city: string
  district?: string
  metro?: string
  coordinates?: {
    lat: number
    lon: number
  }
}

export const useLocationSearch = () => {
  // Поисковые запросы
  const searchCity = ref('')
  const searchDistrict = ref('')
  const searchMetro = ref('')
  
  // Все доступные локации (загружаются из API)
  const allLocations = ref<Location[]>([])
  
  // Видимые локации после фильтрации
  const visibleCities = computed(() => {
    if (!searchCity.value) return getAllCities()
    
    const query = capitalizeFirstLetter(searchCity.value.toLowerCase())
    return getAllCities().filter(city => 
      city.toLowerCase().includes(query.toLowerCase())
    )
  })
  
  const visibleDistricts = computed(() => {
    if (!searchDistrict.value) return getAllDistricts()
    
    const query = capitalizeFirstLetter(searchDistrict.value.toLowerCase())
    return getAllDistricts().filter(district => 
      district.toLowerCase().includes(query.toLowerCase())
    )
  })
  
  const visibleMetroStations = computed(() => {
    if (!searchMetro.value) return getAllMetroStations()
    
    const query = capitalizeFirstLetter(searchMetro.value.toLowerCase())
    return getAllMetroStations().filter(metro => 
      metro.toLowerCase().includes(query.toLowerCase())
    )
  })
  
  // Отфильтрованные локации
  const filteredLocations = computed(() => {
    return allLocations.value.filter(location => {
      const cityMatch = !searchCity.value || 
        location.city.toLowerCase().includes(searchCity.value.toLowerCase())
      
      const districtMatch = !searchDistrict.value || 
        (location.district && location.district.toLowerCase().includes(searchDistrict.value.toLowerCase()))
      
      const metroMatch = !searchMetro.value || 
        (location.metro && location.metro.toLowerCase().includes(searchMetro.value.toLowerCase()))
      
      return cityMatch && districtMatch && metroMatch
    })
  })
  
  // Утилиты
  const capitalizeFirstLetter = (str: string): string => {
    return str && str[0].toUpperCase() + str.slice(1)
  }
  
  const getAllCities = (): string[] => {
    const cities = new Set(allLocations.value.map(l => l.city))
    return Array.from(cities).sort()
  }
  
  const getAllDistricts = (): string[] => {
    const districts = new Set(
      allLocations.value
        .filter(l => l.district)
        .map(l => l.district!)
    )
    return Array.from(districts).sort()
  }
  
  const getAllMetroStations = (): string[] => {
    const metros = new Set(
      allLocations.value
        .filter(l => l.metro)
        .map(l => l.metro!)
    )
    return Array.from(metros).sort()
  }
  
  // Сброс фильтров
  const resetFilters = () => {
    searchCity.value = ''
    searchDistrict.value = ''
    searchMetro.value = ''
  }
  
  // Загрузка локаций (мок данные для примера)
  const loadLocations = async () => {
    // В реальном приложении здесь будет API запрос
    allLocations.value = [
      { id: 1, city: 'Москва', district: 'Центральный', metro: 'Арбатская', coordinates: { lat: 55.7558, lon: 37.6173 } },
      { id: 2, city: 'Москва', district: 'Центральный', metro: 'Тверская', coordinates: { lat: 55.7645, lon: 37.6056 } },
      { id: 3, city: 'Москва', district: 'Южный', metro: 'Домодедовская', coordinates: { lat: 55.6102, lon: 37.7189 } },
      { id: 4, city: 'Санкт-Петербург', district: 'Центральный', metro: 'Невский проспект', coordinates: { lat: 59.9343, lon: 30.3351 } },
      { id: 5, city: 'Санкт-Петербург', district: 'Адмиралтейский', metro: 'Адмиралтейская', coordinates: { lat: 59.9359, lon: 30.3146 } },
      { id: 6, city: 'Пермь', district: 'Ленинский', coordinates: { lat: 58.0092, lon: 56.2270 } },
      { id: 7, city: 'Пермь', district: 'Свердловский', coordinates: { lat: 58.0105, lon: 56.2502 } },
      { id: 8, city: 'Екатеринбург', district: 'Октябрьский', metro: 'Площадь 1905 года', coordinates: { lat: 56.8389, lon: 60.6057 } },
      { id: 9, city: 'Новосибирск', district: 'Центральный', metro: 'Площадь Ленина', coordinates: { lat: 54.9833, lon: 82.8964 } },
      { id: 10, city: 'Казань', district: 'Вахитовский', metro: 'Кремлёвская', coordinates: { lat: 55.7894, lon: 49.1221 } }
    ]
  }
  
  // Дебаунс для поиска (вместо setInterval из оригинала)
  const debounce = (fn: Function, delay: number) => {
    let timeoutId: NodeJS.Timeout
    return (...args: any[]) => {
      clearTimeout(timeoutId)
      timeoutId = setTimeout(() => fn(...args), delay)
    }
  }
  
  // Автодополнение при вводе
  const handleCityInput = debounce((value: string) => {
    searchCity.value = value
  }, 200)
  
  const handleDistrictInput = debounce((value: string) => {
    searchDistrict.value = value
  }, 200)
  
  const handleMetroInput = debounce((value: string) => {
    searchMetro.value = value
  }, 200)
  
  // Выбор конкретной локации
  const selectLocation = (location: Location) => {
    searchCity.value = location.city
    searchDistrict.value = location.district || ''
    searchMetro.value = location.metro || ''
  }
  
  // Инициализация
  loadLocations()
  
  return {
    // Поисковые запросы
    searchCity,
    searchDistrict,
    searchMetro,
    
    // Отфильтрованные данные
    visibleCities,
    visibleDistricts,
    visibleMetroStations,
    filteredLocations,
    
    // Все локации
    allLocations,
    
    // Методы
    resetFilters,
    selectLocation,
    handleCityInput,
    handleDistrictInput,
    handleMetroInput,
    
    // Утилиты
    getAllCities,
    getAllDistricts,
    getAllMetroStations
  }
}