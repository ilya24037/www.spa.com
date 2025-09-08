/**
 * Пример интеграции компонентов Yandex Maps в существующий проект
 * Демонстрирует различные способы использования модулей
 */

// ============================================================================
// ВАРИАНТ 1: Использование в чистом JavaScript проекте
// ============================================================================

import YMapsCore from '../core/YMapsCore.js'
import MapBehaviors from '../behaviors/MapBehaviors.js'
import Placemark from '../modules/Placemark/Placemark.js'
import Balloon from '../modules/Balloon/Balloon.js'
import Clusterer from '../modules/Clusterer/Clusterer.js'

class MapService {
  constructor(config = {}) {
    this.config = {
      apiKey: config.apiKey || '',
      containerId: config.containerId || 'map',
      center: config.center || [55.753994, 37.622093],
      zoom: config.zoom || 10,
      ...config
    }
    
    this.map = null
    this.mapsCore = null
    this.behaviors = null
    this.clusterer = null
    this.markers = new Map()
    this.balloon = null
  }
  
  /**
   * Инициализация карты
   */
  async init() {
    try {
      // Создаем ядро
      this.mapsCore = new YMapsCore({
        apiKey: this.config.apiKey,
        lang: 'ru_RU',
        coordorder: 'latlong'
      })
      
      // Загружаем API
      await this.mapsCore.loadAPI()
      
      // Создаем карту
      this.map = await this.mapsCore.createMap(this.config.containerId, {
        center: this.config.center,
        zoom: this.config.zoom,
        controls: ['zoomControl', 'fullscreenControl', 'geolocationControl']
      })
      
      // Инициализируем компоненты
      await this._initComponents()
      
      return this.map
      
    } catch (error) {
      console.error('Ошибка инициализации карты:', error)
      throw error
    }
  }
  
  /**
   * Инициализация компонентов
   * @private
   */
  async _initComponents() {
    // Менеджер поведений
    this.behaviors = new MapBehaviors(this.map, {
      drag: true,
      scrollZoom: true,
      dblClickZoom: true
    })
    
    // Balloon для всплывающих окон
    this.balloon = new Balloon(this.map, {
      closeButton: true,
      autoPan: true
    })
    
    // Кластеризатор (опционально)
    if (this.config.useClustering) {
      this.clusterer = new Clusterer(this.map, {
        preset: 'islands#blueClusterIcons'
      })
    }
  }
  
  /**
   * Добавление метки на карту
   */
  async addMarker(id, position, options = {}) {
    const marker = new Placemark(
      position,
      {
        balloonContent: options.balloonContent || '',
        hintContent: options.hintContent || ''
      },
      {
        preset: options.preset || 'islands#blueIcon',
        draggable: options.draggable || false,
        ...options
      }
    )
    
    // Добавляем на карту или в кластеризатор
    if (this.clusterer && this.config.useClustering) {
      await this.clusterer.add(marker)
    } else {
      await marker.addToMap(this.map)
    }
    
    // Сохраняем в коллекцию
    this.markers.set(id, marker)
    
    return marker
  }
  
  /**
   * Удаление метки
   */
  async removeMarker(id) {
    const marker = this.markers.get(id)
    if (!marker) return
    
    if (this.clusterer && this.config.useClustering) {
      await this.clusterer.remove(marker)
    } else {
      await marker.removeFromMap()
    }
    
    this.markers.delete(id)
  }
  
  /**
   * Показать balloon
   */
  async showBalloon(position, content, options = {}) {
    await this.balloon.open(position, content, options)
  }
  
  /**
   * Центрировать карту
   */
  async centerMap(center, zoom) {
    await this.map.setCenter(center || this.config.center, zoom || this.config.zoom, {
      duration: 500
    })
  }
  
  /**
   * Уничтожение карты
   */
  destroy() {
    if (this.balloon) this.balloon.destroy()
    if (this.clusterer) this.clusterer.destroy()
    if (this.behaviors) this.behaviors.destroy()
    if (this.mapsCore) this.mapsCore.destroy()
    
    this.markers.clear()
  }
}

// ============================================================================
// ВАРИАНТ 2: Использование в Vue 3 проекте (Composition API)
// ============================================================================

import { ref, onMounted, onUnmounted } from 'vue'

export function useYandexMap(config = {}) {
  const map = ref(null)
  const isLoading = ref(true)
  const error = ref(null)
  const markers = ref(new Map())
  
  let mapService = null
  
  const initMap = async (containerId) => {
    try {
      isLoading.value = true
      error.value = null
      
      mapService = new MapService({
        containerId,
        ...config
      })
      
      map.value = await mapService.init()
      isLoading.value = false
      
    } catch (err) {
      error.value = err.message
      isLoading.value = false
    }
  }
  
  const addMarker = async (id, position, options) => {
    if (!mapService) return
    
    const marker = await mapService.addMarker(id, position, options)
    markers.value.set(id, marker)
    return marker
  }
  
  const removeMarker = async (id) => {
    if (!mapService) return
    
    await mapService.removeMarker(id)
    markers.value.delete(id)
  }
  
  const centerMap = async (center, zoom) => {
    if (!mapService) return
    await mapService.centerMap(center, zoom)
  }
  
  onUnmounted(() => {
    if (mapService) {
      mapService.destroy()
    }
  })
  
  return {
    map,
    isLoading,
    error,
    markers,
    initMap,
    addMarker,
    removeMarker,
    centerMap
  }
}

// ============================================================================
// ВАРИАНТ 3: Использование в React проекте
// ============================================================================

import React, { useEffect, useRef, useState } from 'react'

export function useYandexMapReact(config = {}) {
  const [map, setMap] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)
  const mapServiceRef = useRef(null)
  const markersRef = useRef(new Map())
  
  useEffect(() => {
    const initMap = async () => {
      try {
        setIsLoading(true)
        setError(null)
        
        mapServiceRef.current = new MapService(config)
        const mapInstance = await mapServiceRef.current.init()
        
        setMap(mapInstance)
        setIsLoading(false)
        
      } catch (err) {
        setError(err.message)
        setIsLoading(false)
      }
    }
    
    initMap()
    
    // Cleanup
    return () => {
      if (mapServiceRef.current) {
        mapServiceRef.current.destroy()
      }
    }
  }, [])
  
  const addMarker = async (id, position, options) => {
    if (!mapServiceRef.current) return
    
    const marker = await mapServiceRef.current.addMarker(id, position, options)
    markersRef.current.set(id, marker)
    return marker
  }
  
  const removeMarker = async (id) => {
    if (!mapServiceRef.current) return
    
    await mapServiceRef.current.removeMarker(id)
    markersRef.current.delete(id)
  }
  
  return {
    map,
    isLoading,
    error,
    addMarker,
    removeMarker
  }
}

// ============================================================================
// ВАРИАНТ 4: Класс для управления картой мастеров (пример бизнес-логики)
// ============================================================================

export class MastersMapManager {
  constructor(containerId, options = {}) {
    this.mapService = new MapService({
      containerId,
      useClustering: true,
      ...options
    })
    
    this.masters = new Map()
    this.activeFilters = {
      categories: [],
      districts: [],
      rating: 0
    }
  }
  
  async init() {
    await this.mapService.init()
    this.setupEventListeners()
  }
  
  setupEventListeners() {
    // Слушаем изменения границ карты
    this.mapService.map.events.add('boundschange', () => {
      this.onBoundsChange()
    })
  }
  
  async loadMasters(masters) {
    // Очищаем старые метки
    for (const [id, data] of this.masters) {
      await this.mapService.removeMarker(id)
    }
    this.masters.clear()
    
    // Добавляем новые
    for (const master of masters) {
      await this.addMaster(master)
    }
  }
  
  async addMaster(master) {
    const marker = await this.mapService.addMarker(master.id, master.coordinates, {
      preset: this.getMarkerPreset(master),
      balloonContent: this.createBalloonContent(master),
      hintContent: master.name,
      draggable: false
    })
    
    // Добавляем обработчик клика
    marker.on('click', () => {
      this.onMasterClick(master)
    })
    
    this.masters.set(master.id, { master, marker })
  }
  
  getMarkerPreset(master) {
    // Определяем цвет метки по рейтингу
    if (master.rating >= 4.5) return 'islands#greenIcon'
    if (master.rating >= 4.0) return 'islands#blueIcon'
    if (master.rating >= 3.5) return 'islands#orangeIcon'
    return 'islands#grayIcon'
  }
  
  createBalloonContent(master) {
    return `
      <div class="master-balloon">
        <h3>${master.name}</h3>
        <p class="rating">⭐ ${master.rating} (${master.reviewsCount} отзывов)</p>
        <p class="services">${master.services.join(', ')}</p>
        <p class="price">От ${master.minPrice} ₽</p>
        <button onclick="window.bookMaster('${master.id}')">
          Записаться
        </button>
      </div>
    `
  }
  
  async applyFilters(filters) {
    this.activeFilters = { ...this.activeFilters, ...filters }
    
    for (const [id, data] of this.masters) {
      const isVisible = this.checkFilters(data.master)
      
      if (isVisible) {
        data.marker.show()
      } else {
        data.marker.hide()
      }
    }
  }
  
  checkFilters(master) {
    // Проверяем категории
    if (this.activeFilters.categories.length > 0) {
      const hasCategory = master.categories.some(cat => 
        this.activeFilters.categories.includes(cat)
      )
      if (!hasCategory) return false
    }
    
    // Проверяем районы
    if (this.activeFilters.districts.length > 0) {
      if (!this.activeFilters.districts.includes(master.district)) {
        return false
      }
    }
    
    // Проверяем рейтинг
    if (master.rating < this.activeFilters.rating) {
      return false
    }
    
    return true
  }
  
  async showMasterDetails(masterId) {
    const data = this.masters.get(masterId)
    if (!data) return
    
    // Центрируем карту на мастере
    await this.mapService.centerMap(data.master.coordinates, 15)
    
    // Показываем balloon с детальной информацией
    await this.mapService.showBalloon(
      data.master.coordinates,
      this.createDetailedBalloonContent(data.master)
    )
  }
  
  createDetailedBalloonContent(master) {
    // Более детальное содержимое для balloon
    return `
      <div class="master-details">
        <img src="${master.photo}" alt="${master.name}" />
        <h2>${master.name}</h2>
        <div class="rating">
          ${'⭐'.repeat(Math.floor(master.rating))} ${master.rating}
        </div>
        <div class="info">
          <p><strong>Опыт:</strong> ${master.experience} лет</p>
          <p><strong>Услуги:</strong></p>
          <ul>
            ${master.services.map(s => `<li>${s}</li>`).join('')}
          </ul>
          <p><strong>График:</strong> ${master.schedule}</p>
          <p><strong>Адрес:</strong> ${master.address}</p>
        </div>
        <div class="actions">
          <button onclick="window.bookMaster('${master.id}')">
            Записаться
          </button>
          <button onclick="window.callMaster('${master.id}')">
            Позвонить
          </button>
        </div>
      </div>
    `
  }
  
  onMasterClick(master) {
    // Событие клика по мастеру
    window.dispatchEvent(new CustomEvent('masterSelected', {
      detail: master
    }))
  }
  
  onBoundsChange() {
    // Можно загружать мастеров для видимой области
    const bounds = this.mapService.map.getBounds()
    window.dispatchEvent(new CustomEvent('mapBoundsChanged', {
      detail: bounds
    }))
  }
  
  destroy() {
    this.masters.clear()
    this.mapService.destroy()
  }
}

// ============================================================================
// ЭКСПОРТ
// ============================================================================

export {
  MapService,
  YMapsCore,
  MapBehaviors,
  Placemark,
  Balloon,
  Clusterer
}

export default MapService