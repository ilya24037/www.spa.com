/**
 * SearchPlugin - Плагин поиска по адресу и обратного геокодинга
 * Преобразует адреса в координаты и наоборот
 */

import type { MapPlugin, MapStore, Coordinates } from '../types'

export class SearchPlugin implements MapPlugin {
  name = 'search'
  private geocoder: any = null
  private searchControl: any = null
  private map: any = null
  private store: MapStore | null = null

  constructor(private options: any = {}) {
    this.options = {
      showSearchControl: false,
      reverseGeocode: true,
      ...options
    }
  }

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    console.log('[SearchPlugin] Установлен')

    // Добавляем контрол поиска (если нужен)
    if (this.options.showSearchControl && window.ymaps) {
      this.searchControl = new window.ymaps.control.SearchControl({
        options: {
          float: 'left',
          floatIndex: 100,
          noPlacemark: true,
          placeholderContent: 'Поиск места...'
        }
      })

      map.controls.add(this.searchControl)

      // Обработчик результата поиска
      this.searchControl.events.add('resultselect', (e: any) => {
        const index = e.get('index')
        this.searchControl.getResult(index).then((res: any) => {
          const coords = res.geometry.getCoordinates()
          const address = res.properties.get('text')
          
          const coordinates: Coordinates = {
            lat: coords[0],
            lng: coords[1]
          }
          
          store.setCoordinates(coordinates)
          store.setAddress(address)
          store.emit('search-result', { address, coordinates })
          
          console.log(`[SearchPlugin] Найдено: ${address}`)
        })
      })
    }

    // Обратное геокодирование при клике
    if (this.options.reverseGeocode) {
      map.events.add('click', (e: any) => {
        const coords = e.get('coords')
        this.reverseGeocode(coords, store)
      })
    }

    // Слушаем запросы поиска из store
    store.on('search-address', this.searchAddress.bind(this))
  }

  /**
   * Поиск адреса по координатам (обратное геокодирование)
   */
  async reverseGeocode(coords: number[], store: MapStore) {
    if (!window.ymaps) return

    try {
      const geocodeResult = await window.ymaps.geocode(coords, {
        results: 1,
        kind: 'house'
      })
      
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const address = firstGeoObject.getAddressLine()
        const precision = firstGeoObject.properties.get('metaDataProperty.GeocoderMetaData.precision')
        
        store.setAddress(address)
        store.emit('address-found', {
          address,
          coordinates: {
            lat: coords[0],
            lng: coords[1]
          },
          precision
        })
        
        console.log(`[SearchPlugin] Адрес найден: ${address}`)
      }
    } catch (error) {
      console.error('[SearchPlugin] Ошибка геокодирования:', error)
      store.emit('search-error', 'Не удалось определить адрес')
    }
  }

  /**
   * Поиск координат по адресу (прямое геокодирование)
   */
  async searchAddress(address: string): Promise<Coordinates | null> {
    if (!window.ymaps || !address) return null

    try {
      const geocodeResult = await window.ymaps.geocode(address, {
        results: 1,
        boundedBy: this.map?.getBounds(),
        strictBounds: false
      })
      
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const coords = firstGeoObject.geometry.getCoordinates()
        const foundAddress = firstGeoObject.getAddressLine()
        
        const coordinates: Coordinates = {
          lat: coords[0],
          lng: coords[1]
        }
        
        // Обновляем карту
        if (this.map) {
          this.map.setCenter(coords, 16)
        }
        
        // Обновляем store
        if (this.store) {
          this.store.setCoordinates(coordinates)
          this.store.setAddress(foundAddress)
          this.store.emit('address-found', {
            address: foundAddress,
            coordinates
          })
        }
        
        console.log(`[SearchPlugin] Найден адрес: ${foundAddress}`)
        return coordinates
      }
    } catch (error) {
      console.error('[SearchPlugin] Ошибка поиска адреса:', error)
      this.store?.emit('search-error', 'Адрес не найден')
    }
    
    return null
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    if (this.searchControl && this.map) {
      this.map.controls.remove(this.searchControl)
      this.searchControl = null
    }
    this.map = null
    this.store = null
  }
}