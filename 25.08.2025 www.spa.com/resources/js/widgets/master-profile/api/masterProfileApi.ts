/**
 * Изолированный API для виджета MasterProfile
 * Не зависит от глобальных API - собственная инкапсуляция
 */

import { logger } from '@/src/shared/utils/logger'
import type { 
  MasterProfile, 
  MasterProfileFilters 
} from '../types/masterProfile.types'

export class MasterProfileWidgetApi {
  private baseUrl: string

  constructor() {
    this.baseUrl = '/api/masters'
  }

  /**
   * Получить профиль мастера
   */
  async getMasterProfile(
    masterId: number, 
    filters: MasterProfileFilters = {}
  ): Promise<MasterProfile> {
    try {
      const params = new URLSearchParams()
      
      if (filters.includeServices) params.append('include', 'services')
      if (filters.includePhotos) params.append('include', 'photos')  
      if (filters.includeReviews) params.append('include', 'reviews')

      const response = await fetch(
        `${this.baseUrl}/${masterId}?${params.toString()}`,
        {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          }
        }
      )

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()
      return this.transformMasterData(data)
    } catch (error) {
      logger.error('[MasterProfileWidget] API Error:', error)
      throw new Error(
        error instanceof Error 
          ? error.message 
          : 'Не удалось загрузить профиль мастера'
      )
    }
  }

  /**
   * Получить услуги мастера
   */
  async getMasterServices(masterId: number) {
    try {
      const response = await fetch(`${this.baseUrl}/${masterId}/services`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })

      if (!response.ok) {
        throw new Error(`Ошибка загрузки услуг: ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      logger.error('[MasterProfileWidget] Services API Error:', error)
      throw error
    }
  }

  /**
   * Получить фотографии мастера
   */
  async getMasterPhotos(masterId: number) {
    try {
      const response = await fetch(`${this.baseUrl}/${masterId}/photos`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })

      if (!response.ok) {
        throw new Error(`Ошибка загрузки фотографий: ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      logger.error('[MasterProfileWidget] Photos API Error:', error)
      throw error
    }
  }

  /**
   * Трансформация данных с бэкенда в формат виджета
   */
  private transformMasterData(data: any): MasterProfile {
    return {
      id: data.id,
      name: data.name || 'Без имени',
      description: data.description || data.bio,
      avatar: data.avatar_url || data.photo_url,
      rating: data.rating || 0,
      reviewsCount: data.reviews_count || 0,
      isOnline: data.is_online || false,
      
      services: data.services?.map((service: any) => ({
        id: service.id,
        name: service.name,
        description: service.description,
        price: service.price || 0,
        duration: service.duration || 60,
        category: service.category
      })) || [],
      
      photos: data.photos?.map((photo: any) => ({
        id: photo.id,
        url: photo.url || photo.image_url,
        alt: photo.alt || `Фото ${data.name}`,
        isMain: photo.is_main || false
      })) || [],
      
      location: data.location ? {
        address: data.location.address,
        city: data.location.city,
        coordinates: data.location.coordinates
      } : undefined,
      
      workingHours: data.working_hours || {},
      
      contacts: data.contacts ? {
        phone: data.contacts.phone,
        whatsapp: data.contacts.whatsapp,
        telegram: data.contacts.telegram,
        instagram: data.contacts.instagram
      } : undefined
    }
  }

  /**
   * Кеширование данных виджета (зарезервировано для будущей оптимизации)
   */
  // private cache = new Map<string, { data: any, timestamp: number }>()
  // private readonly CACHE_TTL = 5 * 60 * 1000 // 5 минут

  // Методы кеширования (зарезервированы для будущей оптимизации)
  // private getCacheKey(masterId: number, filters: MasterProfileFilters): string {
  //   return `master-${masterId}-${JSON.stringify(filters)}`
  // }

  // private isValidCache(timestamp: number): boolean {
  //   return Date.now() - timestamp < this.CACHE_TTL
  // }

  // private setCache(key: string, data: any): void {
  //   this.cache.set(key, { data, timestamp: Date.now() })
  // }

  // private getCache(key: string): any | null {
  //   const cached = this.cache.get(key)
  //   if (cached && this.isValidCache(cached.timestamp)) {
  //     return cached.data
  //   }
  //   return null
  // }
}