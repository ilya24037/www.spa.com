/**
 * Singleton загрузчик Yandex Maps API
 * Паттерн: Singleton + Promise caching
 * Размер: 50 строк
 */
export class MapLoader {
  private static instance: MapLoader | null = null
  private loadPromise: Promise<typeof ymaps> | null = null
  private isLoaded = false

  private constructor() {}

  static getInstance(): MapLoader {
    if (!MapLoader.instance) {
      MapLoader.instance = new MapLoader()
    }
    return MapLoader.instance
  }

  async load(apiKey: string): Promise<typeof ymaps> {
    // Если уже загружено, возвращаем сразу
    if (this.isLoaded && window.ymaps) {
      return window.ymaps
    }

    // Если загрузка в процессе, возвращаем существующий промис
    if (this.loadPromise) {
      return this.loadPromise
    }

    // Начинаем загрузку
    this.loadPromise = this.loadScript(apiKey)
    const ymaps = await this.loadPromise
    this.isLoaded = true
    return ymaps
  }

  private loadScript(apiKey: string): Promise<typeof ymaps> {
    return new Promise((resolve, reject) => {
      // Проверяем, может уже загружено
      if (window.ymaps?.ready) {
        window.ymaps.ready(() => resolve(window.ymaps))
        return
      }

      // Создаем script tag
      const script = document.createElement('script')
      script.src = `https://api-maps.yandex.ru/2.1/?apikey=${apiKey}&lang=ru_RU`
      script.async = true

      script.onload = () => {
        window.ymaps.ready(() => {
            resolve(window.ymaps)
        })
      }

      script.onerror = () => {
        this.loadPromise = null
        reject(new Error('Failed to load Yandex Maps API'))
      }

      document.head.appendChild(script)
    })
  }

  // Для тестов
  reset(): void {
    this.loadPromise = null
    this.isLoaded = false
  }
}

// Экспортируем готовый инстанс
export const mapLoader = MapLoader.getInstance()