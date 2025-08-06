import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// TypeScript интерфейсы
export interface CatalogCategory {
  id: string
  name: string
  slug: string
  icon?: string
  items: CatalogItem[]
  description?: string
  featured?: boolean
}

export interface CatalogItem {
  id: string
  name: string
  slug: string
  href: string
  description?: string
  popular?: boolean
  new?: boolean
}

export interface PopularTag {
  id: string
  name: string
  slug: string
  count?: number
  trending?: boolean
}

export const useCatalogStore = defineStore('catalog', () => {
  // State
  const categories = ref<CatalogCategory[]>([])
  const popularTags = ref<PopularTag[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const lastFetchTime = ref<Date | null>(null)
  const isOpen = ref(false)
  
  // Getters
  const featuredCategories = computed(() => 
    categories.value.filter(cat => cat.featured)
  )
  
  const trendingTags = computed(() => 
    popularTags.value.filter(tag => tag.trending)
  )
  
  const totalItemsCount = computed(() => 
    categories.value.reduce((total, cat) => total + cat.items.length, 0)
  )
  
  const categoriesByColumns = computed(() => {
    const columns = 4
    const itemsPerColumn = Math.ceil(categories.value.length / columns)
    const result: CatalogCategory[][] = []
    
    for (let i = 0; i < columns; i++) {
      const start = i * itemsPerColumn
      const end = start + itemsPerColumn
      result.push(categories.value.slice(start, end))
    }
    
    return result
  })
  
  // Actions
  const loadCatalog = async (force = false): Promise<void> => {
    // Проверяем кеш (5 минут)
    if (!force && lastFetchTime.value && 
        Date.now() - lastFetchTime.value.getTime() < 5 * 60 * 1000) {
      return
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      const [categoriesResponse, tagsResponse] = await Promise.all([
        axios.get('/api/catalog/categories'),
        axios.get('/api/catalog/popular-tags')
      ])
      
      categories.value = categoriesResponse.data?.categories || getDefaultCategories()
      popularTags.value = tagsResponse.data?.tags || getDefaultTags()
      lastFetchTime.value = new Date()
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка загрузки каталога'
      console.error('Failed to load catalog:', err)
      
      // Fallback на дефолтные данные
      categories.value = getDefaultCategories()
      popularTags.value = getDefaultTags()
    } finally {
      isLoading.value = false
    }
  }
  
  const openCatalog = (): void => {
    isOpen.value = true
    // Подгружаем данные при открытии
    if (categories.value.length === 0) {
      loadCatalog()
    }
  }
  
  const closeCatalog = (): void => {
    isOpen.value = false
  }
  
  const toggleCatalog = (): void => {
    if (isOpen.value) {
      closeCatalog()
    } else {
      openCatalog()
    }
  }
  
  const trackCategoryClick = async (category: CatalogCategory, item?: CatalogItem): Promise<void> => {
    try {
      await axios.post('/api/analytics/catalog-click', {
        category_id: category.id,
        item_id: item?.id,
        timestamp: new Date().toISOString()
      })
    } catch (err) {
      console.warn('Failed to track category click:', err)
    }
  }
  
  const trackTagClick = async (tag: PopularTag): Promise<void> => {
    try {
      await axios.post('/api/analytics/tag-click', {
        tag_id: tag.id,
        timestamp: new Date().toISOString()
      })
    } catch (err) {
      console.warn('Failed to track tag click:', err)
    }
  }
  
  const searchInCatalog = (query: string): CatalogItem[] => {
    if (!query.trim()) return []
    
    const searchTerm = query.toLowerCase()
    const results: CatalogItem[] = []
    
    categories.value.forEach(category => {
      category.items.forEach(item => {
        if (
          item.name.toLowerCase().includes(searchTerm) ||
          item.description?.toLowerCase().includes(searchTerm) ||
          category.name.toLowerCase().includes(searchTerm)
        ) {
          results.push({ ...item, category: category.name } as any)
        }
      })
    })
    
    return results
  }
  
  const getCategoryBySlug = (slug: string): CatalogCategory | null => {
    return categories.value.find(cat => cat.slug === slug) || null
  }
  
  const getItemBySlug = (categorySlug: string, itemSlug: string): CatalogItem | null => {
    const category = getCategoryBySlug(categorySlug)
    if (!category) return null
    
    return category.items.find(item => item.slug === itemSlug) || null
  }
  
  const clearError = (): void => {
    error.value = null
  }
  
  const refreshCatalog = async (): Promise<void> => {
    await loadCatalog(true)
  }
  
  // Default data
  const getDefaultCategories = (): CatalogCategory[] => [
    {
      id: '1',
      name: 'Массаж',
      slug: 'massage',
      icon: '💆‍♀️',
      featured: true,
      items: [
        { id: '1', name: 'Классический массаж', slug: 'classic-massage', href: '/category/classic-massage', popular: true },
        { id: '2', name: 'Тайский массаж', slug: 'thai-massage', href: '/category/thai-massage', popular: true },
        { id: '3', name: 'Спортивный массаж', slug: 'sport-massage', href: '/category/sport-massage' },
        { id: '4', name: 'Лечебный массаж', slug: 'medical-massage', href: '/category/medical-massage' },
        { id: '5', name: 'Антицеллюлитный', slug: 'anti-cellulite', href: '/category/anti-cellulite' },
        { id: '6', name: 'Релакс массаж', slug: 'relax-massage', href: '/category/relax-massage', popular: true }
      ]
    },
    {
      id: '2', 
      name: 'СПА процедуры',
      slug: 'spa',
      icon: '🧖‍♀️',
      featured: true,
      items: [
        { id: '7', name: 'Обёртывания', slug: 'body-wrap', href: '/category/body-wrap', popular: true },
        { id: '8', name: 'Скрабирование', slug: 'scrub', href: '/category/scrub' },
        { id: '9', name: 'Стоун-терапия', slug: 'stone-therapy', href: '/category/stone-therapy' },
        { id: '10', name: 'Ароматерапия', slug: 'aromatherapy', href: '/category/aromatherapy' },
        { id: '11', name: 'Банные процедуры', slug: 'bath-procedures', href: '/category/bath-procedures' }
      ]
    },
    {
      id: '3',
      name: 'Косметология', 
      slug: 'cosmetology',
      icon: '💅',
      featured: true,
      items: [
        { id: '12', name: 'Уход за лицом', slug: 'facial', href: '/category/facial', popular: true },
        { id: '13', name: 'Пилинги', slug: 'peeling', href: '/category/peeling' },
        { id: '14', name: 'Маски для лица', slug: 'masks', href: '/category/masks' },
        { id: '15', name: 'Anti-age процедуры', slug: 'anti-age', href: '/category/anti-age', new: true },
        { id: '16', name: 'Чистка лица', slug: 'cleaning', href: '/category/cleaning', popular: true }
      ]
    },
    {
      id: '4',
      name: 'Дополнительно',
      slug: 'additional', 
      icon: '✨',
      items: [
        { id: '17', name: 'Выезд на дом', slug: 'at-home', href: '/masters/at-home', popular: true },
        { id: '18', name: 'Салоны и студии', slug: 'salons', href: '/salons' },
        { id: '19', name: 'Подарочные сертификаты', slug: 'gift-certificates', href: '/gift-certificates' },
        { id: '20', name: 'Акции и скидки', slug: 'promotions', href: '/promotions' },
        { id: '21', name: 'Блог о здоровье', slug: 'blog', href: '/blog' }
      ]
    }
  ]
  
  const getDefaultTags = (): PopularTag[] => [
    { id: '1', name: 'Массаж спины', slug: 'massage-back', count: 156, trending: true },
    { id: '2', name: 'Расслабляющий массаж', slug: 'relax-massage', count: 134, trending: true },
    { id: '3', name: 'Массаж в 4 руки', slug: 'four-hands-massage', count: 89, trending: false },
    { id: '4', name: 'Лимфодренаж', slug: 'lymphatic-drainage', count: 76, trending: false },
    { id: '5', name: 'Массаж для беременных', slug: 'pregnancy-massage', count: 45, trending: false },
    { id: '6', name: 'Детский массаж', slug: 'child-massage', count: 34, trending: false },
    { id: '7', name: 'Массаж лица', slug: 'face-massage', count: 67, trending: true },
    { id: '8', name: 'Горячие камни', slug: 'hot-stone', count: 89, trending: false }
  ]
  
  // Инициализация
  loadCatalog()
  
  return {
    // State
    categories,
    popularTags,
    isLoading,
    error,
    isOpen,
    lastFetchTime,
    
    // Getters
    featuredCategories,
    trendingTags,
    totalItemsCount,
    categoriesByColumns,
    
    // Actions
    loadCatalog,
    openCatalog,
    closeCatalog,
    toggleCatalog,
    trackCategoryClick,
    trackTagClick,
    searchInCatalog,
    getCategoryBySlug,
    getItemBySlug,
    clearError,
    refreshCatalog
  }
})