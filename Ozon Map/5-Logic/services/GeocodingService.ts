/**
 * Сервис для работы с геокодингом
 */

export interface GeocodingResult {
  id: string
  name: string
  displayName: string
  coordinates: [number, number]
  bounds?: [[number, number], [number, number]]
  type: 'address' | 'poi' | 'city' | 'street' | 'building'
  relevance: number
  country?: string
  region?: string
  city?: string
  district?: string
  street?: string
  houseNumber?: string
  postalCode?: string
}

export interface ReverseGeocodingResult {
  coordinates: [number, number]
  address: {
    country?: string
    region?: string
    city?: string
    district?: string
    street?: string
    houseNumber?: string
    postalCode?: string
    formattedAddress: string
  }
  poi?: {
    name: string
    category: string
    tags: Record<string, string>
  }
}

export interface GeocodingOptions {
  /** Максимальное количество результатов */
  limit?: number
  /** Фильтр по стране */
  countryCode?: string
  /** Фильтр по типу */
  types?: Array<'address' | 'poi' | 'city' | 'street' | 'building'>
  /** Приоритет по координатам */
  proximity?: [number, number]
  /** Ограничение по области */
  bbox?: [[number, number], [number, number]]
  /** Язык результатов */
  language?: string
}

export interface GeocodingProvider {
  name: string
  search(query: string, options?: GeocodingOptions): Promise<GeocodingResult[]>
  reverse(coordinates: [number, number], options?: Partial<GeocodingOptions>): Promise<ReverseGeocodingResult>
}

/**
 * Провайдер для OpenStreetMap Nominatim
 */
export class NominatimProvider implements GeocodingProvider {
  name = 'nominatim'
  private baseUrl = 'https://nominatim.openstreetmap.org'
  private userAgent = 'OZON Map Widget'

  async search(query: string, options: GeocodingOptions = {}): Promise<GeocodingResult[]> {
    const {
      limit = 10,
      countryCode,
      types,
      bbox,
      language = 'ru'
    } = options

    const params = new URLSearchParams({
      q: query,
      format: 'json',
      addressdetails: '1',
      limit: limit.toString(),
      'accept-language': language
    })

    if (countryCode) {
      params.append('countrycodes', countryCode)
    }

    if (bbox) {
      const [[minLng, minLat], [maxLng, maxLat]] = bbox
      params.append('viewbox', `${minLng},${maxLat},${maxLng},${minLat}`)
      params.append('bounded', '1')
    }

    try {
      const response = await fetch(`${this.baseUrl}/search?${params}`, {
        headers: {
          'User-Agent': this.userAgent
        }
      })

      if (!response.ok) {
        throw new Error(`Nominatim API error: ${response.status}`)
      }

      const data = await response.json()

      return data.map((item: any) => this.transformNominatimResult(item))
        .filter((result: GeocodingResult) => {
          if (!types || types.length === 0) return true
          return types.includes(result.type)
        })
    } catch (error) {
      console.error('Nominatim search error:', error)
      throw new Error('Ошибка поиска адреса')
    }
  }

  async reverse(coordinates: [number, number], options: Partial<GeocodingOptions> = {}): Promise<ReverseGeocodingResult> {
    const { language = 'ru' } = options
    const [lng, lat] = coordinates

    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lng.toString(),
      format: 'json',
      addressdetails: '1',
      'accept-language': language
    })

    try {
      const response = await fetch(`${this.baseUrl}/reverse?${params}`, {
        headers: {
          'User-Agent': this.userAgent
        }
      })

      if (!response.ok) {
        throw new Error(`Nominatim API error: ${response.status}`)
      }

      const data = await response.json()

      return this.transformNominatimReverseResult(data, coordinates)
    } catch (error) {
      console.error('Nominatim reverse error:', error)
      throw new Error('Ошибка обратного геокодинга')
    }
  }

  private transformNominatimResult(item: any): GeocodingResult {
    const address = item.address || {}
    
    return {
      id: item.place_id.toString(),
      name: item.display_name.split(',')[0].trim(),
      displayName: item.display_name,
      coordinates: [parseFloat(item.lon), parseFloat(item.lat)],
      bounds: item.boundingbox ? [
        [parseFloat(item.boundingbox[2]), parseFloat(item.boundingbox[0])],
        [parseFloat(item.boundingbox[3]), parseFloat(item.boundingbox[1])]
      ] : undefined,
      type: this.mapNominatimType(item.type, item.class),
      relevance: parseFloat(item.importance) || 0,
      country: address.country,
      region: address.state || address.region,
      city: address.city || address.town || address.village,
      district: address.suburb || address.district,
      street: address.road,
      houseNumber: address.house_number,
      postalCode: address.postcode
    }
  }

  private transformNominatimReverseResult(data: any, coordinates: [number, number]): ReverseGeocodingResult {
    const address = data.address || {}
    
    return {
      coordinates,
      address: {
        country: address.country,
        region: address.state || address.region,
        city: address.city || address.town || address.village,
        district: address.suburb || address.district,
        street: address.road,
        houseNumber: address.house_number,
        postalCode: address.postcode,
        formattedAddress: data.display_name || 'Неизвестный адрес'
      },
      poi: data.class === 'amenity' || data.class === 'shop' ? {
        name: data.namedetails?.name || data.name,
        category: data.type,
        tags: data.extratags || {}
      } : undefined
    }
  }

  private mapNominatimType(type: string, osmClass: string): GeocodingResult['type'] {
    if (['house', 'building'].includes(type)) return 'building'
    if (['residential', 'highway', 'road'].includes(type)) return 'address'
    if (['city', 'town', 'village'].includes(type)) return 'city'
    if (['primary', 'secondary', 'tertiary'].includes(type)) return 'street'
    if (['amenity', 'shop', 'tourism'].includes(osmClass)) return 'poi'
    return 'address'
  }
}

/**
 * Провайдер для Яндекс.Карт
 */
export class YandexProvider implements GeocodingProvider {
  name = 'yandex'
  private baseUrl = 'https://geocode-maps.yandex.ru/1.x/'
  private apiKey: string

  constructor(apiKey: string) {
    this.apiKey = apiKey
  }

  async search(query: string, options: GeocodingOptions = {}): Promise<GeocodingResult[]> {
    const {
      limit = 10,
      bbox,
      language = 'ru'
    } = options

    const params = new URLSearchParams({
      apikey: this.apiKey,
      geocode: query,
      format: 'json',
      results: limit.toString(),
      lang: language
    })

    if (bbox) {
      const [[minLng, minLat], [maxLng, maxLat]] = bbox
      params.append('bbox', `${minLng},${minLat}~${maxLng},${maxLat}`)
      params.append('rspn', '1')
    }

    try {
      const response = await fetch(`${this.baseUrl}?${params}`)

      if (!response.ok) {
        throw new Error(`Yandex API error: ${response.status}`)
      }

      const data = await response.json()
      const geoObjects = data.response?.GeoObjectCollection?.featureMember || []

      return geoObjects.map((item: any) => this.transformYandexResult(item.GeoObject))
    } catch (error) {
      console.error('Yandex search error:', error)
      throw new Error('Ошибка поиска адреса')
    }
  }

  async reverse(coordinates: [number, number], options: Partial<GeocodingOptions> = {}): Promise<ReverseGeocodingResult> {
    const { language = 'ru' } = options
    const [lng, lat] = coordinates

    const params = new URLSearchParams({
      apikey: this.apiKey,
      geocode: `${lng},${lat}`,
      format: 'json',
      results: '1',
      lang: language
    })

    try {
      const response = await fetch(`${this.baseUrl}?${params}`)

      if (!response.ok) {
        throw new Error(`Yandex API error: ${response.status}`)
      }

      const data = await response.json()
      const geoObject = data.response?.GeoObjectCollection?.featureMember?.[0]?.GeoObject

      if (!geoObject) {
        throw new Error('Адрес не найден')
      }

      return this.transformYandexReverseResult(geoObject, coordinates)
    } catch (error) {
      console.error('Yandex reverse error:', error)
      throw new Error('Ошибка обратного геокодинга')
    }
  }

  private transformYandexResult(geoObject: any): GeocodingResult {
    const coordinates = geoObject.Point.pos.split(' ').map(Number).reverse() as [number, number]
    const metaData = geoObject.metaDataProperty?.GeocoderMetaData
    const addressDetails = metaData?.Address?.Components || []
    
    const getComponent = (kind: string) => {
      return addressDetails.find((comp: any) => comp.kind === kind)?.name
    }

    return {
      id: metaData?.id || `${coordinates[0]}_${coordinates[1]}`,
      name: geoObject.name,
      displayName: geoObject.description || geoObject.name,
      coordinates,
      type: this.mapYandexType(metaData?.kind),
      relevance: 1,
      country: getComponent('country'),
      region: getComponent('province'),
      city: getComponent('locality'),
      district: getComponent('district'),
      street: getComponent('street'),
      houseNumber: getComponent('house'),
      postalCode: metaData?.Address?.postal_code
    }
  }

  private transformYandexReverseResult(geoObject: any, coordinates: [number, number]): ReverseGeocodingResult {
    const metaData = geoObject.metaDataProperty?.GeocoderMetaData
    const addressDetails = metaData?.Address?.Components || []
    
    const getComponent = (kind: string) => {
      return addressDetails.find((comp: any) => comp.kind === kind)?.name
    }

    return {
      coordinates,
      address: {
        country: getComponent('country'),
        region: getComponent('province'),
        city: getComponent('locality'),
        district: getComponent('district'),
        street: getComponent('street'),
        houseNumber: getComponent('house'),
        postalCode: metaData?.Address?.postal_code,
        formattedAddress: geoObject.description || geoObject.name
      }
    }
  }

  private mapYandexType(kind: string): GeocodingResult['type'] {
    switch (kind) {
      case 'house':
        return 'building'
      case 'street':
        return 'street'
      case 'locality':
        return 'city'
      default:
        return 'address'
    }
  }
}

/**
 * Основной сервис геокодинга
 */
export class GeocodingService {
  private providers: Map<string, GeocodingProvider> = new Map()
  private defaultProvider: string

  constructor(defaultProvider: string = 'nominatim') {
    this.defaultProvider = defaultProvider
    
    // Добавляем Nominatim по умолчанию
    this.addProvider(new NominatimProvider())
  }

  addProvider(provider: GeocodingProvider) {
    this.providers.set(provider.name, provider)
  }

  setDefaultProvider(name: string) {
    if (!this.providers.has(name)) {
      throw new Error(`Provider ${name} not found`)
    }
    this.defaultProvider = name
  }

  async search(
    query: string, 
    options: GeocodingOptions = {}, 
    providerName?: string
  ): Promise<GeocodingResult[]> {
    const provider = this.providers.get(providerName || this.defaultProvider)
    if (!provider) {
      throw new Error(`Provider ${providerName || this.defaultProvider} not found`)
    }

    return provider.search(query, options)
  }

  async reverse(
    coordinates: [number, number], 
    options: Partial<GeocodingOptions> = {}, 
    providerName?: string
  ): Promise<ReverseGeocodingResult> {
    const provider = this.providers.get(providerName || this.defaultProvider)
    if (!provider) {
      throw new Error(`Provider ${providerName || this.defaultProvider} not found`)
    }

    return provider.reverse(coordinates, options)
  }

  getAvailableProviders(): string[] {
    return Array.from(this.providers.keys())
  }
}

// Создаем экземпляр сервиса по умолчанию
export const geocodingService = new GeocodingService()

// Экспортируем типы и классы
export type { GeocodingOptions, GeocodingResult, ReverseGeocodingResult }