/**
 * РћСЃРЅРѕРІРЅРѕР№ store РґР»СЏ СѓРїСЂР°РІР»РµРЅРёСЏ РјР°СЃС‚РµСЂР°РјРё
 * Pinia store РґР»СЏ entities/master (FSD Р°СЂС…РёС‚РµРєС‚СѓСЂР°)
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed, type Ref, type ComputedRef } from 'vue'
import { masterApi } from '../api/masterApi'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Master {
  id: number
  name: string
  display_name?: string
  avatar?: string
  specialty?: string
  description?: string
  rating?: number
  reviews_count?: number
  services?: any[]
  photos?: any[]
  location?: string
  price_from?: number
  price_to?: number
  is_favorite?: boolean
  is_verified?: boolean
  is_premium?: boolean
  is_online?: boolean
  [key: string]: any
}

interface MasterFilters {
  city: string | null
  district: string | null
  category: string | null
  priceFrom: number | null
  priceTo: number | null
  rating: number | null
  experience: string | null
  age: string | null
  availability: string | null
  verified: boolean
  premium: boolean
  online: boolean
  sortBy: string
  sortOrder: 'asc' | 'desc'
}

export const useMasterStore = defineStore('master', () => {
  // === РЎРћРЎРўРћРЇРќРР• ===
  
  // РЎРїРёСЃРѕРє РјР°СЃС‚РµСЂРѕРІ
  const masters = ref<Ref<Master[]>>([])
  
  // РўРµРєСѓС‰РёР№ РјР°СЃС‚РµСЂ
  const currentMaster = ref<Ref<Master | null>>(null)
  
  // РР·Р±СЂР°РЅРЅС‹Рµ РјР°СЃС‚РµСЂР°
  const favoriteMasters = ref<Ref<Master[]>>([])
  
  // РџРѕС…РѕР¶РёРµ РјР°СЃС‚РµСЂР°
  const similarMasters = ref<Ref<Master[]>>([])
  
  // Р¤РёР»СЊС‚СЂС‹
  const filters = reactive<MasterFilters>({
    city: null,
    district: null,
    category: null,
    priceFrom: null,
    priceTo: null,
    rating: null,
    experience: null,
    age: null,
    availability: null,
    verified: false,
    premium: false,
    online: false,
    sortBy: 'rating',
    sortOrder: 'desc'
  })
  
  // РџРѕРёСЃРєРѕРІС‹Р№ Р·Р°РїСЂРѕСЃ
  const searchQuery = ref<Ref<string>>('')
  
  // РЎРѕСЃС‚РѕСЏРЅРёРµ Р·Р°РіСЂСѓР·РєРё
  const loading = ref<Ref<boolean>>(false)
  const loadingReviews = ref<Ref<boolean>>(false)
  
  // РџР°РіРёРЅР°С†РёСЏ
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0
  })
  
  // РћС‚Р·С‹РІС‹ С‚РµРєСѓС‰РµРіРѕ РјР°СЃС‚РµСЂР°
  const currentMasterReviews = ref([])
  
  // === Р’Р«Р§РРЎР›РЇР•РњР«Р• РЎР’РћР™РЎРўР’Рђ ===
  
  /**
   * РћС‚С„РёР»СЊС‚СЂРѕРІР°РЅРЅС‹Рµ РјР°СЃС‚РµСЂР°
   */
  const filteredMasters = computed(() => {
    let result = masters?.value
    
    // Р¤РёР»СЊС‚СЂ РїРѕ РіРѕСЂРѕРґСѓ
    if (filters?.city) {
      result = result?.filter(master => master?.city === filters?.city)
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ СЂР°Р№РѕРЅСѓ
    if (filters?.district) {
      result = result?.filter(master => master?.district === filters?.district)
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ РєР°С‚РµРіРѕСЂРёРё
    if (filters?.category) {
      result = result?.filter(master => master?.category === filters?.category)
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ С†РµРЅРµ
    if ((filters?.priceFrom ?? 0)) {
      result = result?.filter(master => (master?.price_from || 0) >= (filters?.priceFrom ?? 0))
    }
    
    if ((filters?.priceTo ?? Infinity)) {
      result = result?.filter(master => (master?.price_from || 0) <= (filters?.priceTo ?? Infinity))
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ СЂРµР№С‚РёРЅРіСѓ
    if (filters?.rating) {
      result = result?.filter(master => (master?.rating || 0) >= filters?.rating)
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ РІРµСЂРёС„РёРєР°С†РёРё
    if (filters?.verified) {
      result = result?.filter(master => master?.is_verified)
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ РїСЂРµРјРёСѓРј
    if (filters?.premium) {
      result = result?.filter(master => master?.is_premium)
    }
    
    // Р¤РёР»СЊС‚СЂ РїРѕ РѕРЅР»Р°Р№РЅ СЃС‚Р°С‚СѓСЃСѓ
    if (filters?.online) {
      result = result?.filter(master => master?.is_online || master?.is_available_now)
    }
    
    // РџРѕРёСЃРє РїРѕ С‚РµРєСЃС‚Сѓ
    if (searchQuery?.value) {
      const query = searchQuery?.value.toLowerCase()
      result = result?.filter(master => 
        (master?.name && master?.name.toLowerCase().includes(query)) ||
        (master?.display_name && master?.display_name.toLowerCase().includes(query)) ||
        (master?.specialty && master?.specialty.toLowerCase().includes(query)) ||
        (master?.bio && master?.bio.toLowerCase().includes(query))
      )
    }
    
    return result
  })
  
  /**
   * РџРѕРїСѓР»СЏСЂРЅС‹Рµ РјР°СЃС‚РµСЂР°
   */
  const popularMasters = computed(() => 
    masters?.value
      .filter(master => master?.rating >= 4.5)
      .sort((a, b) => (b?.reviews_count || 0) - (a?.reviews_count || 0))
  )
  
  /**
   * РџСЂРµРјРёСѓРј РјР°СЃС‚РµСЂР°
   */
  const premiumMasters = computed(() => 
    masters?.value.filter(master => master?.is_premium)
  )
  
  /**
   * РџСЂРѕРІРµСЂРµРЅРЅС‹Рµ РјР°СЃС‚РµСЂР°
   */
  const verifiedMasters = computed(() => 
    masters?.value.filter(master => master?.is_verified)
  )
  
  /**
   * РћРЅР»Р°Р№РЅ РјР°СЃС‚РµСЂР°
   */
  const onlineMasters = computed(() => 
    masters?.value.filter(master => master?.is_online || master?.is_available_now)
  )
  
  /**
   * Р•СЃС‚СЊ Р»Рё РµС‰Рµ СЃС‚СЂР°РЅРёС†С‹ РґР»СЏ Р·Р°РіСЂСѓР·РєРё
   */
  const hasMorePages = computed(() => 
    pagination?.current_page < pagination?.last_page
  )
  
  // === Р”Р•Р™РЎРўР’РРЇ ===
  
  /**
   * Р—Р°РіСЂСѓР·РёС‚СЊ РјР°СЃС‚РµСЂРѕРІ
   */
  const fetchMasters = async (params: any = {}) => {
    loading?.value = true
    
    try {
      const queryParams = {
        page: pagination?.current_page,
        per_page: pagination?.per_page,
        ...filters,
        ...params
      }
      
      if (searchQuery?.value) {
        queryParams?.search = searchQuery?.value
      }
      
      const response = await masterApi?.getMasters(queryParams)
      
      // РћР±РЅРѕРІР»СЏРµРј СЃРїРёСЃРѕРє РјР°СЃС‚РµСЂРѕРІ
      if (params?.append) {
        masters?.value.push(...response?.data)
      } else {
        masters?.value = response?.data
      }
      
      // РћР±РЅРѕРІР»СЏРµРј РїР°РіРёРЅР°С†РёСЋ
      Object?.assign(pagination, {
        current_page: response?.current_page,
        last_page: response?.last_page,
        per_page: response?.per_page,
        total: response?.total
      })
      
      return response
    } catch (error: any) {
      throw error
    } finally {
      loading?.value = false
    }
  }
  
  /**
   * Р—Р°РіСЂСѓР·РёС‚СЊ РјР°СЃС‚РµСЂР° РїРѕ ID
   */
  const fetchMaster = async (id: number | string) => {
    loading?.value = true
    
    try {
      const response = await masterApi?.getMaster(id)
      
      currentMaster?.value = response
      
      // Р”РѕР±Р°РІР»СЏРµРј РІ СЃРїРёСЃРѕРє РµСЃР»Рё РµРіРѕ С‚Р°Рј РЅРµС‚
      const existingIndex = masters?.value.findIndex(master => master?.id === id)
      if (existingIndex >= 0) {
        masters?.value[existingIndex] = response
      } else {
        masters?.value.unshift(response)
      }
      
      return response
    } catch (error: any) {
      throw error
    } finally {
      loading?.value = false
    }
  }
  
  /**
   * РџРѕРёСЃРє РјР°СЃС‚РµСЂРѕРІ
   */
  const searchMasters = async (query: string, additionalFilters: any = {}) => {
    loading?.value = true
    
    try {
      const response = await masterApi?.searchMasters(query, {
        ...filters,
        ...additionalFilters
      })
      
      masters?.value = response?.data
      
      // РћР±РЅРѕРІР»СЏРµРј РїР°РіРёРЅР°С†РёСЋ
      Object?.assign(pagination, {
        current_page: response?.current_page || 1,
        last_page: response?.last_page || 1,
        per_page: response?.per_page || 12,
        total: response?.total || response?.data.length
      })
      
      return response
    } catch (error: any) {
      throw error
    } finally {
      loading?.value = false
    }
  }
  
  /**
   * Р—Р°РіСЂСѓР·РёС‚СЊ РѕС‚Р·С‹РІС‹ РјР°СЃС‚РµСЂР°
   */
  const fetchMasterReviews = async (masterId: number, params: any = {}) => {
    loadingReviews?.value = true
    
    try {
      const response = await masterApi?.getMasterReviews(masterId, params)
      
      if (params?.append) {
        currentMasterReviews?.value.push(...response?.data)
      } else {
        currentMasterReviews?.value = response?.data
      }
      
      return response
    } catch (error: any) {
      throw error
    } finally {
      loadingReviews?.value = false
    }
  }
  
  /**
   * Р—Р°РіСЂСѓР·РёС‚СЊ РїРѕС…РѕР¶РёС… РјР°СЃС‚РµСЂРѕРІ
   */
  const fetchSimilarMasters = async (masterId: number, params: any = {}) => {
    try {
      const response = await masterApi?.getSimilarMasters(masterId, params)
      similarMasters?.value = response?.data
      return response
    } catch (error: any) {
      throw error
    }
  }
  
  /**
   * Р”РѕР±Р°РІРёС‚СЊ/СѓРґР°Р»РёС‚СЊ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ
   */
  const toggleFavorite = async (masterId: number) => {
    try {
      const master = masters?.value.find(m => m?.id === masterId) || currentMaster?.value
      if (!master) return
      
      if (master?.is_favorite) {
        await masterApi?.removeFromFavorites(masterId)
        master?.is_favorite = false
        
        // РЈРґР°Р»СЏРµРј РёР· РёР·Р±СЂР°РЅРЅС‹С…
        const favIndex = favoriteMasters?.value.findIndex(fav => fav?.id === masterId)
        if (favIndex >= 0) {
          favoriteMasters?.value.splice(favIndex, 1)
        }
      } else {
        await masterApi?.addToFavorites(masterId)
        master?.is_favorite = true
        
        // Р”РѕР±Р°РІР»СЏРµРј РІ РёР·Р±СЂР°РЅРЅС‹Рµ
        favoriteMasters?.value.unshift(master)
      }
      
      return master?.is_favorite
    } catch (error: any) {
      throw error
    }
  }
  
  /**
   * Р—Р°РіСЂСѓР·РёС‚СЊ РёР·Р±СЂР°РЅРЅС‹С… РјР°СЃС‚РµСЂРѕРІ
   */
  const fetchFavorites = async () => {
    loading?.value = true
    
    try {
      const response = await masterApi?.getFavorites()
      favoriteMasters?.value = response?.data
      return response
    } catch (error: any) {
      throw error
    } finally {
      loading?.value = false
    }
  }
  
  /**
   * РЈРІРµР»РёС‡РёС‚СЊ РїСЂРѕСЃРјРѕС‚СЂС‹
   */
  const incrementViews = async (masterId: number) => {
    try {
      await masterApi?.incrementViews(masterId)
      
      // РћР±РЅРѕРІР»СЏРµРј Р»РѕРєР°Р»СЊРЅРѕ
      const master = masters?.value.find(m => m?.id === masterId) || currentMaster?.value
      if (master && master?.views_count) {
        master?.views_count++
      }
    } catch (error: any) {
      // РќРµ РїРѕРєР°Р·С‹РІР°РµРј РѕС€РёР±РєСѓ РїРѕР»СЊР·РѕРІР°С‚РµР»СЋ РґР»СЏ РїСЂРѕСЃРјРѕС‚СЂРѕРІ
    }
  }
  
  /**
   * РЈСЃС‚Р°РЅРѕРІРёС‚СЊ С„РёР»СЊС‚СЂС‹
   */
  const setFilters = (newFilters: any) => {
    Object?.assign(filters, newFilters)
    pagination?.current_page = 1 // РЎР±СЂР°СЃС‹РІР°РµРј РЅР° РїРµСЂРІСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ
  }
  
  /**
   * РћС‡РёСЃС‚РёС‚СЊ С„РёР»СЊС‚СЂС‹
   */
  const clearFilters = () => {
    Object?.keys(filters).forEach(key => {
      if (typeof filters[key] === 'boolean') {
        filters[key] = false
      } else {
        filters[key] = null
      }
    })
    filters?.sortBy = 'rating'
    filters?.sortOrder = 'desc'
    pagination?.current_page = 1
  }
  
  /**
   * РЈСЃС‚Р°РЅРѕРІРёС‚СЊ РїРѕРёСЃРєРѕРІС‹Р№ Р·Р°РїСЂРѕСЃ
   */
  const setSearchQuery = (query: any) => {
    searchQuery?.value = query
    pagination?.current_page = 1 // РЎР±СЂР°СЃС‹РІР°РµРј РЅР° РїРµСЂРІСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ
  }
  
  /**
   * Р—Р°РіСЂСѓР·РёС‚СЊ СЃР»РµРґСѓСЋС‰СѓСЋ СЃС‚СЂР°РЅРёС†Сѓ
   */
  const loadMoreMasters = async () => {
    if (!hasMorePages?.value || loading?.value) return
    
    pagination?.current_page++
    return await fetchMasters({ append: true })
  }
  
  /**
   * РЎР±СЂРѕСЃ СЃРѕСЃС‚РѕСЏРЅРёСЏ
   */
  const reset = () => {
    masters?.value = []
    currentMaster?.value = null
    favoriteMasters?.value = []
    similarMasters?.value = []
    currentMasterReviews?.value = []
    searchQuery?.value = ''
    loading?.value = false
    loadingReviews?.value = false
    
    clearFilters()
    
    Object?.assign(pagination, {
      current_page: 1,
      last_page: 1,
      per_page: 12,
      total: 0
    })
  }
  
  // Р’РѕР·РІСЂР°С‰Р°РµРј РїСѓР±Р»РёС‡РЅС‹Р№ API
  return {
    // РЎРѕСЃС‚РѕСЏРЅРёРµ
    masters,
    currentMaster,
    favoriteMasters,
    similarMasters,
    currentMasterReviews,
    filters,
    searchQuery,
    loading,
    loadingReviews,
    pagination,
    
    // Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
    filteredMasters,
    popularMasters,
    premiumMasters,
    verifiedMasters,
    onlineMasters,
    hasMorePages,
    
    // Р”РµР№СЃС‚РІРёСЏ
    fetchMasters,
    fetchMaster,
    searchMasters,
    fetchMasterReviews,
    fetchSimilarMasters,
    toggleFavorite,
    fetchFavorites,
    incrementViews,
    setFilters,
    clearFilters,
    setSearchQuery,
    loadMoreMasters,
    reset
  }
})