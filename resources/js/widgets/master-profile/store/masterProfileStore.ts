/**
 * Изолированное состояние виджета MasterProfile
 * Использует Pinia но изолировано от глобальных stores
 */

import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { 
  MasterProfileWidgetState, 
  MasterProfileFilters,
  MasterService
} from '../types/masterProfile.types'
import { MasterProfileWidgetApi } from '../api/masterProfileApi'
import { logger } from '@/src/shared/utils/logger'

export const useMasterProfileWidgetStore = defineStore('master-profile-widget', () => {
  // === СОСТОЯНИЕ ===
  const state = ref<MasterProfileWidgetState>({
    master: null,
    isLoading: false,
    error: null,
    selectedService: null
  })

  // API инстанс (изолированный)
  const api = new MasterProfileWidgetApi()

  // === COMPUTED ===
  const isLoaded = computed(() => !state.value.isLoading && state.value.master !== null)
  const hasError = computed(() => state.value.error !== null)
  const hasServices = computed(() => state.value.master?.services && state.value.master.services.length > 0)
  const hasPhotos = computed(() => state.value.master?.photos && state.value.master.photos.length > 0)
  
  const mainPhoto = computed(() => {
    if (!state.value.master?.photos) return null
    return state.value.master.photos.find(photo => photo.isMain) || state.value.master.photos[0]
  })

  const galleryPhotos = computed(() => {
    if (!state.value.master?.photos) return []
    return state.value.master.photos.filter(photo => !photo.isMain)
  })

  // === ACTIONS ===

  /**
   * Загрузить профиль мастера
   */
  async function loadMasterProfile(masterId: number, filters: MasterProfileFilters = {}) {
    if (state.value.isLoading) return

    try {
      state.value.isLoading = true
      state.value.error = null
      
      const master = await api.getMasterProfile(masterId, filters)
      state.value.master = master
      
      // Аналитика виджета (изолированная)
      trackWidgetEvent('master_profile_loaded', { masterId, filters })
      
    } catch (error) {
      state.value.error = error instanceof Error ? error.message : 'Ошибка загрузки'
      logger.error('[MasterProfileWidget] Load error:', error)
      
      // Изолированная обработка ошибок
      trackWidgetEvent('master_profile_error', { masterId, error: state.value.error })
    } finally {
      state.value.isLoading = false
    }
  }

  /**
   * Выбрать услугу
   */
  function selectService(service: MasterService | null) {
    state.value.selectedService = service
    
    if (service) {
      trackWidgetEvent('service_selected', { 
        serviceId: service.id, 
        masterId: state.value.master?.id 
      })
    }
  }

  /**
   * Очистить ошибку
   */
  function clearError() {
    state.value.error = null
  }

  /**
   * Перезагрузить данные
   */
  async function reload() {
    if (state.value.master) {
      await loadMasterProfile(state.value.master.id, {
        includeServices: hasServices.value,
        includePhotos: hasPhotos.value
      })
    }
  }

  /**
   * Очистить состояние виджета
   */
  function reset() {
    state.value = {
      master: null,
      isLoading: false,
      error: null,
      selectedService: null
    }
  }

  /**
   * Отслеживание событий виджета (изолированное)
   */
  function trackWidgetEvent(event: string, data: any) {
    if (typeof window !== 'undefined') {
      // Отправляем изолированные события виджета
      window.dispatchEvent(new CustomEvent('widget-analytics', {
        detail: {
          widget: 'master-profile',
          event,
          data,
          timestamp: Date.now()
        }
      }))
    }
  }

  // === BUSINESS LOGIC ===

  /**
   * Может ли пользователь забронировать услугу
   */
  function canBookService(service?: MasterService): boolean {
    if (!service || !state.value.master) return false
    
    // Проверяем доступность мастера
    if (!state.value.master.isOnline) return false
    
    // Дополнительная логика бронирования
    return true
  }

  /**
   * Форматирование цены услуги
   */
  function formatServicePrice(service: MasterService): string {
    return `${service.price.toLocaleString('ru-RU')} ₽`
  }

  /**
   * Получить следующее доступное время
   */
  function getNextAvailableTime(): string | null {
    // Логика расчета следующего доступного времени
    // В реальном приложении это было бы более сложно
    return '14:00'
  }

  // Возвращаем API виджета
  return {
    // State
    state: readonly(state),
    
    // Computed
    isLoaded,
    hasError,
    hasServices,
    hasPhotos,
    mainPhoto,
    galleryPhotos,
    
    // Actions
    loadMasterProfile,
    selectService,
    clearError,
    reload,
    reset,
    
    // Business Logic
    canBookService,
    formatServicePrice,
    getNextAvailableTime,
    
    // Events
    trackWidgetEvent
  }
})

// === ТИПЫ ДЛЯ ИСПОЛЬЗОВАНИЯ ===
export type MasterProfileWidgetStore = ReturnType<typeof useMasterProfileWidgetStore>