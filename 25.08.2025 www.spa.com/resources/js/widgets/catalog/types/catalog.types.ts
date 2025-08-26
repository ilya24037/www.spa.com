/**
 * Типы для изолированного виджета Catalog
 */

export interface CatalogItem {
  id: number
  title: string
  description?: string
  price: number
  image?: string
  category: string
  masterId: number
  masterName: string
  rating?: number
  location?: string
  isPromoted?: boolean
}

export interface CatalogFilter {
  category?: string
  priceMin?: number
  priceMax?: number
  location?: string
  rating?: number
  sortBy?: 'price' | 'rating' | 'date' | 'popular'
  sortOrder?: 'asc' | 'desc'
}

export interface CatalogWidgetProps {
  category?: string
  limit?: number
  showFilters?: boolean
  showPagination?: boolean
  layout?: 'grid' | 'list'
  compact?: boolean
}

export interface CatalogWidgetEmits {
  'item-selected': (item: CatalogItem) => void
  'filter-changed': (filters: CatalogFilter) => void
  'master-clicked': (masterId: number) => void
  'load-more': () => void
}

export interface CatalogWidgetState {
  items: CatalogItem[]
  isLoading: boolean
  error: string | null
  filters: CatalogFilter
  totalCount: number
  currentPage: number
  hasMore: boolean
}