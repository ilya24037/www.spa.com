/**
 * ImageCacheService - Сервис для кеширования и оптимизации изображений
 * Использует IndexedDB для долговременного хранения и Memory Cache для быстрого доступа
 */

interface CachedImage {
  url: string
  blob: Blob
  timestamp: number
  size: number
  type: string
}

interface ImageCacheOptions {
  maxAge?: number // Максимальный возраст кеша в миллисекундах
  maxSize?: number // Максимальный размер кеша в байтах
  memoryLimit?: number // Лимит памяти для in-memory кеша
}

class ImageCacheService {
  private static instance: ImageCacheService
  private memoryCache: Map<string, string> = new Map()
  private pendingRequests: Map<string, Promise<string>> = new Map()
  private db: IDBDatabase | null = null
  private readonly DB_NAME = 'ImageCache'
  private readonly DB_VERSION = 1
  private readonly STORE_NAME = 'images'
  
  private options: Required<ImageCacheOptions> = {
    maxAge: 7 * 24 * 60 * 60 * 1000, // 7 дней
    maxSize: 50 * 1024 * 1024, // 50MB
    memoryLimit: 20 * 1024 * 1024 // 20MB в памяти
  }
  
  private currentMemorySize = 0
  
  private constructor(options?: ImageCacheOptions) {
    if (options) {
      this.options = { ...this.options, ...options }
    }
    this.initDB()
  }
  
  static getInstance(options?: ImageCacheOptions): ImageCacheService {
    if (!ImageCacheService.instance) {
      ImageCacheService.instance = new ImageCacheService(options)
    }
    return ImageCacheService.instance
  }
  
  /**
   * Инициализация IndexedDB
   */
  private async initDB(): Promise<void> {
    if (!window.indexedDB) {
      console.warn('IndexedDB not supported')
      return
    }
    
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.DB_NAME, this.DB_VERSION)
      
      request.onerror = () => {
        console.error('Failed to open IndexedDB')
        reject(request.error)
      }
      
      request.onsuccess = () => {
        this.db = request.result
        this.cleanupOldCache()
        resolve()
      }
      
      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result
        
        if (!db.objectStoreNames.contains(this.STORE_NAME)) {
          const store = db.createObjectStore(this.STORE_NAME, { keyPath: 'url' })
          store.createIndex('timestamp', 'timestamp', { unique: false })
          store.createIndex('size', 'size', { unique: false })
        }
      }
    })
  }
  
  /**
   * Получить изображение из кеша или загрузить
   */
  async getImage(url: string): Promise<string> {
    // 1. Проверяем memory cache
    if (this.memoryCache.has(url)) {
      return this.memoryCache.get(url)!
    }
    
    // 2. Проверяем, не загружается ли уже
    if (this.pendingRequests.has(url)) {
      return this.pendingRequests.get(url)!
    }
    
    // 3. Создаем промис загрузки
    const loadPromise = this.loadImage(url)
    this.pendingRequests.set(url, loadPromise)
    
    try {
      const result = await loadPromise
      this.pendingRequests.delete(url)
      return result
    } catch (error) {
      this.pendingRequests.delete(url)
      throw error
    }
  }
  
  /**
   * Загрузка изображения
   */
  private async loadImage(url: string): Promise<string> {
    // Пробуем получить из IndexedDB
    const cached = await this.getFromDB(url)
    
    if (cached && Date.now() - cached.timestamp < this.options.maxAge) {
      const objectUrl = URL.createObjectURL(cached.blob)
      this.addToMemoryCache(url, objectUrl, cached.size)
      return objectUrl
    }
    
    // Загружаем из сети
    try {
      const response = await fetch(url)
      if (!response.ok) throw new Error(`Failed to fetch ${url}`)
      
      const blob = await response.blob()
      const objectUrl = URL.createObjectURL(blob)
      
      // Сохраняем в кеши
      this.addToMemoryCache(url, objectUrl, blob.size)
      await this.saveToDB(url, blob)
      
      return objectUrl
    } catch (error) {
      console.error(`Failed to load image ${url}:`, error)
      return url // Возвращаем оригинальный URL как fallback
    }
  }
  
  /**
   * Добавление в memory cache с учетом лимита
   */
  private addToMemoryCache(url: string, objectUrl: string, size: number): void {
    // Очищаем старые элементы если превышен лимит
    while (this.currentMemorySize + size > this.options.memoryLimit && this.memoryCache.size > 0) {
      const firstKey = this.memoryCache.keys().next().value
      if (firstKey) {
        const oldUrl = this.memoryCache.get(firstKey)
        if (oldUrl && oldUrl.startsWith('blob:')) {
          URL.revokeObjectURL(oldUrl)
        }
        this.memoryCache.delete(firstKey)
        this.currentMemorySize -= size // Примерное вычисление
      }
    }
    
    this.memoryCache.set(url, objectUrl)
    this.currentMemorySize += size
  }
  
  /**
   * Получение из IndexedDB
   */
  private async getFromDB(url: string): Promise<CachedImage | null> {
    if (!this.db) return null
    
    return new Promise((resolve) => {
      const transaction = this.db!.transaction([this.STORE_NAME], 'readonly')
      const store = transaction.objectStore(this.STORE_NAME)
      const request = store.get(url)
      
      request.onsuccess = () => {
        resolve(request.result || null)
      }
      
      request.onerror = () => {
        console.error('Failed to get from IndexedDB')
        resolve(null)
      }
    })
  }
  
  /**
   * Сохранение в IndexedDB
   */
  private async saveToDB(url: string, blob: Blob): Promise<void> {
    if (!this.db) return
    
    const cachedImage: CachedImage = {
      url,
      blob,
      timestamp: Date.now(),
      size: blob.size,
      type: blob.type
    }
    
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction([this.STORE_NAME], 'readwrite')
      const store = transaction.objectStore(this.STORE_NAME)
      const request = store.put(cachedImage)
      
      request.onsuccess = () => resolve()
      request.onerror = () => {
        console.error('Failed to save to IndexedDB')
        reject(request.error)
      }
    })
  }
  
  /**
   * Предзагрузка массива изображений
   */
  async preloadImages(urls: string[]): Promise<void> {
    const promises = urls.map(url => this.getImage(url).catch(() => {}))
    await Promise.all(promises)
  }
  
  /**
   * Очистка старого кеша
   */
  private async cleanupOldCache(): Promise<void> {
    if (!this.db) return
    
    const transaction = this.db.transaction([this.STORE_NAME], 'readwrite')
    const store = transaction.objectStore(this.STORE_NAME)
    const index = store.index('timestamp')
    
    const cutoffTime = Date.now() - this.options.maxAge
    const range = IDBKeyRange.upperBound(cutoffTime)
    
    const request = index.openCursor(range)
    
    request.onsuccess = (event) => {
      const cursor = (event.target as IDBRequest).result
      if (cursor) {
        store.delete(cursor.primaryKey)
        cursor.continue()
      }
    }
  }
  
  /**
   * Очистка всего кеша
   */
  async clearCache(): Promise<void> {
    // Очищаем memory cache
    this.memoryCache.forEach(url => {
      if (url.startsWith('blob:')) {
        URL.revokeObjectURL(url)
      }
    })
    this.memoryCache.clear()
    this.currentMemorySize = 0
    
    // Очищаем IndexedDB
    if (this.db) {
      const transaction = this.db.transaction([this.STORE_NAME], 'readwrite')
      const store = transaction.objectStore(this.STORE_NAME)
      store.clear()
    }
  }
  
  /**
   * Получение статистики кеша
   */
  async getCacheStats(): Promise<{
    memorySize: number
    dbSize: number
    totalImages: number
    memoryImages: number
  }> {
    let dbSize = 0
    let totalImages = 0
    
    if (this.db) {
      const transaction = this.db.transaction([this.STORE_NAME], 'readonly')
      const store = transaction.objectStore(this.STORE_NAME)
      const countRequest = store.count()
      
      await new Promise(resolve => {
        countRequest.onsuccess = () => {
          totalImages = countRequest.result
          resolve(null)
        }
      })
      
      // Подсчет размера
      const getAllRequest = store.getAll()
      await new Promise(resolve => {
        getAllRequest.onsuccess = () => {
          const results = getAllRequest.result as CachedImage[]
          dbSize = results.reduce((sum, item) => sum + item.size, 0)
          resolve(null)
        }
      })
    }
    
    return {
      memorySize: this.currentMemorySize,
      dbSize,
      totalImages,
      memoryImages: this.memoryCache.size
    }
  }
}

export default ImageCacheService.getInstance()
export { ImageCacheService, type CachedImage, type ImageCacheOptions }