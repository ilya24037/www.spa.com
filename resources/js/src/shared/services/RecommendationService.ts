/**
 * RecommendationService - Сервис персонализированных рекомендаций
 * Анализирует поведение пользователя и предлагает релевантный контент
 */

import type { Master } from '@/src/entities/master/model/types'

interface UserBehavior {
  viewedMasters: Set<number>
  favoriteMasters: Set<number>
  bookedMasters: Set<number>
  searchQueries: string[]
  viewedCategories: Map<string, number>
  priceRange: { min: number; max: number }
  preferredDistricts: Map<string, number>
  lastActivityTime: number
}

interface RecommendationScore {
  masterId: number
  score: number
  reasons: string[]
}

interface RecommendationConfig {
  maxRecommendations?: number
  minScore?: number
  includeReasons?: boolean
  diversityFactor?: number // Фактор разнообразия (0-1)
}

class RecommendationService {
  private static instance: RecommendationService
  private userBehavior: UserBehavior
  private readonly STORAGE_KEY = 'user_behavior'
  private readonly BEHAVIOR_EXPIRY = 30 * 24 * 60 * 60 * 1000 // 30 дней
  
  private readonly WEIGHTS = {
    category: 0.3,
    price: 0.2,
    location: 0.15,
    rating: 0.15,
    popularity: 0.1,
    newness: 0.1
  }
  
  private constructor() {
    this.userBehavior = this.loadBehavior()
    this.startSessionTracking()
  }
  
  static getInstance(): RecommendationService {
    if (!RecommendationService.instance) {
      RecommendationService.instance = new RecommendationService()
    }
    return RecommendationService.instance
  }
  
  /**
   * Загрузка сохраненного поведения пользователя
   */
  private loadBehavior(): UserBehavior {
    try {
      const stored = localStorage.getItem(this.STORAGE_KEY)
      if (stored) {
        const parsed = JSON.parse(stored)
        return {
          viewedMasters: new Set(parsed.viewedMasters || []),
          favoriteMasters: new Set(parsed.favoriteMasters || []),
          bookedMasters: new Set(parsed.bookedMasters || []),
          searchQueries: parsed.searchQueries || [],
          viewedCategories: new Map(parsed.viewedCategories || []),
          priceRange: parsed.priceRange || { min: 0, max: 999999 },
          preferredDistricts: new Map(parsed.preferredDistricts || []),
          lastActivityTime: parsed.lastActivityTime || Date.now()
        }
      }
    } catch (error) {
      console.error('Failed to load user behavior:', error)
    }
    
    return this.createEmptyBehavior()
  }
  
  /**
   * Создание пустого объекта поведения
   */
  private createEmptyBehavior(): UserBehavior {
    return {
      viewedMasters: new Set(),
      favoriteMasters: new Set(),
      bookedMasters: new Set(),
      searchQueries: [],
      viewedCategories: new Map(),
      priceRange: { min: 0, max: 999999 },
      preferredDistricts: new Map(),
      lastActivityTime: Date.now()
    }
  }
  
  /**
   * Сохранение поведения пользователя
   */
  private saveBehavior(): void {
    try {
      const toStore = {
        viewedMasters: Array.from(this.userBehavior.viewedMasters),
        favoriteMasters: Array.from(this.userBehavior.favoriteMasters),
        bookedMasters: Array.from(this.userBehavior.bookedMasters),
        searchQueries: this.userBehavior.searchQueries.slice(-50), // Последние 50 запросов
        viewedCategories: Array.from(this.userBehavior.viewedCategories.entries()),
        priceRange: this.userBehavior.priceRange,
        preferredDistricts: Array.from(this.userBehavior.preferredDistricts.entries()),
        lastActivityTime: Date.now()
      }
      
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(toStore))
    } catch (error) {
      console.error('Failed to save user behavior:', error)
    }
  }
  
  /**
   * Отслеживание сессии пользователя
   */
  private startSessionTracking(): void {
    // Очистка старых данных при новой сессии
    if (Date.now() - this.userBehavior.lastActivityTime > this.BEHAVIOR_EXPIRY) {
      this.userBehavior = this.createEmptyBehavior()
      this.saveBehavior()
    }
    
    // Сохранение при закрытии страницы
    window.addEventListener('beforeunload', () => {
      this.saveBehavior()
    })
    
    // Периодическое сохранение
    setInterval(() => {
      this.saveBehavior()
    }, 60000) // Каждую минуту
  }
  
  /**
   * Отслеживание просмотра мастера
   */
  trackMasterView(master: Master): void {
    this.userBehavior.viewedMasters.add(master.id)
    
    // Обновление категорий
    if (master.services) {
      master.services.forEach(service => {
        const count = this.userBehavior.viewedCategories.get(service.name) || 0
        this.userBehavior.viewedCategories.set(service.name, count + 1)
      })
    }
    
    // Обновление диапазона цен
    if (master.price_from) {
      this.userBehavior.priceRange.min = Math.min(
        this.userBehavior.priceRange.min,
        master.price_from
      )
      this.userBehavior.priceRange.max = Math.max(
        this.userBehavior.priceRange.max,
        master.price_from * 1.5
      )
    }
    
    // Обновление районов
    if (master.district) {
      const count = this.userBehavior.preferredDistricts.get(master.district) || 0
      this.userBehavior.preferredDistricts.set(master.district, count + 1)
    }
    
    this.saveBehavior()
  }
  
  /**
   * Отслеживание добавления в избранное
   */
  trackFavorite(masterId: number, added: boolean): void {
    if (added) {
      this.userBehavior.favoriteMasters.add(masterId)
    } else {
      this.userBehavior.favoriteMasters.delete(masterId)
    }
    this.saveBehavior()
  }
  
  /**
   * Отслеживание бронирования
   */
  trackBooking(masterId: number): void {
    this.userBehavior.bookedMasters.add(masterId)
    this.saveBehavior()
  }
  
  /**
   * Отслеживание поискового запроса
   */
  trackSearch(query: string): void {
    this.userBehavior.searchQueries.push(query.toLowerCase())
    this.saveBehavior()
  }
  
  /**
   * Расчет оценки рекомендации для мастера
   */
  private calculateScore(master: Master): RecommendationScore {
    let score = 0
    const reasons: string[] = []
    
    // 1. Оценка по категориям (30%)
    if (master.services) {
      let categoryScore = 0
      master.services.forEach(service => {
        const viewCount = this.userBehavior.viewedCategories.get(service.name) || 0
        categoryScore += viewCount * 0.1
      })
      score += Math.min(categoryScore * this.WEIGHTS.category, this.WEIGHTS.category)
      
      if (categoryScore > 0) {
        reasons.push('Похожие услуги')
      }
    }
    
    // 2. Оценка по цене (20%)
    if (master.price_from) {
      const { min, max } = this.userBehavior.priceRange
      if (master.price_from >= min && master.price_from <= max) {
        score += this.WEIGHTS.price
        reasons.push('Подходящая цена')
      } else {
        const distance = master.price_from < min 
          ? (min - master.price_from) / min
          : (master.price_from - max) / max
        score += this.WEIGHTS.price * Math.max(0, 1 - distance)
      }
    }
    
    // 3. Оценка по локации (15%)
    if (master.district) {
      const districtCount = this.userBehavior.preferredDistricts.get(master.district) || 0
      if (districtCount > 0) {
        score += this.WEIGHTS.location * Math.min(1, districtCount / 5)
        reasons.push('Удобный район')
      }
    }
    
    // 4. Оценка по рейтингу (15%)
    if (master.rating) {
      score += this.WEIGHTS.rating * (master.rating / 5)
      if (master.rating >= 4.5) {
        reasons.push('Высокий рейтинг')
      }
    }
    
    // 5. Оценка по популярности (10%)
    if (master.reviews_count) {
      const popularityScore = Math.min(1, master.reviews_count / 100)
      score += this.WEIGHTS.popularity * popularityScore
      if (master.reviews_count > 50) {
        reasons.push('Популярный мастер')
      }
    }
    
    // 6. Бонус за новизну (10%)
    if (!this.userBehavior.viewedMasters.has(master.id)) {
      score += this.WEIGHTS.newness
      reasons.push('Новый для вас')
    }
    
    // Штраф за уже просмотренное
    if (this.userBehavior.viewedMasters.has(master.id)) {
      score *= 0.5
    }
    
    // Бонус за премиум
    if (master.is_premium) {
      score *= 1.2
      reasons.push('Premium')
    }
    
    return {
      masterId: master.id,
      score,
      reasons
    }
  }
  
  /**
   * Получение персонализированных рекомендаций
   */
  getRecommendations(
    masters: Master[],
    config: RecommendationConfig = {}
  ): Master[] {
    const {
      maxRecommendations = 12,
      minScore = 0.3,
      includeReasons = false,
      diversityFactor = 0.3
    } = config
    
    // Расчет оценок для всех мастеров
    const scores = masters
      .filter(m => !this.userBehavior.bookedMasters.has(m.id)) // Исключаем уже забронированных
      .map(master => ({
        master,
        ...this.calculateScore(master)
      }))
      .filter(item => item.score >= minScore)
      .sort((a, b) => b.score - a.score)
    
    // Применение фактора разнообразия
    const diverse = this.applyDiversity(scores, diversityFactor)
    
    // Возврат топ рекомендаций
    const recommendations = diverse
      .slice(0, maxRecommendations)
      .map(item => {
        if (includeReasons) {
          return {
            ...item.master,
            _recommendationReasons: item.reasons
          }
        }
        return item.master
      })
    
    return recommendations
  }
  
  /**
   * Применение разнообразия к рекомендациям
   */
  private applyDiversity(
    scores: Array<{ master: Master; score: number; reasons: string[] }>,
    factor: number
  ): Array<{ master: Master; score: number; reasons: string[] }> {
    if (factor === 0) return scores
    
    const result: typeof scores = []
    const usedCategories = new Set<string>()
    const usedDistricts = new Set<string>()
    
    for (const item of scores) {
      let diversityPenalty = 0
      
      // Штраф за повторение категорий
      if (item.master.services) {
        const hasUsedCategory = item.master.services.some(s => 
          usedCategories.has(s.name)
        )
        if (hasUsedCategory) {
          diversityPenalty += factor * 0.5
        }
      }
      
      // Штраф за повторение районов
      if (item.master.district && usedDistricts.has(item.master.district)) {
        diversityPenalty += factor * 0.3
      }
      
      // Применение штрафа
      item.score *= (1 - diversityPenalty)
      
      result.push(item)
      
      // Обновление использованных
      if (item.master.services) {
        item.master.services.forEach(s => usedCategories.add(s.name))
      }
      if (item.master.district) {
        usedDistricts.add(item.master.district)
      }
    }
    
    // Пересортировка с учетом разнообразия
    return result.sort((a, b) => b.score - a.score)
  }
  
  /**
   * Получение похожих мастеров
   */
  getSimilarMasters(
    currentMaster: Master,
    allMasters: Master[],
    limit: number = 6
  ): Master[] {
    const scores = allMasters
      .filter(m => m.id !== currentMaster.id)
      .map(master => {
        let similarity = 0
        
        // Схожесть по услугам
        if (currentMaster.services && master.services) {
          const currentServices = new Set(currentMaster.services.map(s => s.name))
          const matchingServices = master.services.filter(s => 
            currentServices.has(s.name)
          ).length
          similarity += matchingServices * 0.3
        }
        
        // Схожесть по цене
        if (currentMaster.price_from && master.price_from) {
          const priceDiff = Math.abs(currentMaster.price_from - master.price_from)
          const priceSimil = Math.max(0, 1 - priceDiff / currentMaster.price_from)
          similarity += priceSimil * 0.2
        }
        
        // Схожесть по району
        if (currentMaster.district === master.district) {
          similarity += 0.2
        }
        
        // Схожесть по рейтингу
        if (currentMaster.rating && master.rating) {
          const ratingDiff = Math.abs(currentMaster.rating - master.rating)
          similarity += (1 - ratingDiff / 5) * 0.15
        }
        
        // Бонус за премиум
        if (currentMaster.is_premium === master.is_premium) {
          similarity += 0.15
        }
        
        return { master, similarity }
      })
      .sort((a, b) => b.similarity - a.similarity)
      .slice(0, limit)
      .map(item => item.master)
    
    return scores
  }
  
  /**
   * Очистка данных пользователя
   */
  clearUserData(): void {
    this.userBehavior = this.createEmptyBehavior()
    localStorage.removeItem(this.STORAGE_KEY)
  }
  
  /**
   * Получение статистики поведения
   */
  getBehaviorStats(): {
    viewedCount: number
    favoriteCount: number
    bookedCount: number
    topCategories: Array<{ name: string; count: number }>
    topDistricts: Array<{ name: string; count: number }>
  } {
    return {
      viewedCount: this.userBehavior.viewedMasters.size,
      favoriteCount: this.userBehavior.favoriteMasters.size,
      bookedCount: this.userBehavior.bookedMasters.size,
      topCategories: Array.from(this.userBehavior.viewedCategories.entries())
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5)
        .map(([name, count]) => ({ name, count })),
      topDistricts: Array.from(this.userBehavior.preferredDistricts.entries())
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3)
        .map(([name, count]) => ({ name, count }))
    }
  }
}

export default RecommendationService.getInstance()
export { RecommendationService, type UserBehavior, type RecommendationScore, type RecommendationConfig }