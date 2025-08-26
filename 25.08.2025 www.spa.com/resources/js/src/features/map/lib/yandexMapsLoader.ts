/**
 * Загрузчик API Яндекс.Карт
 * Обеспечивает однократную загрузку скрипта
 */

let loadPromise: Promise<void> | null = null

export function loadYandexMaps(apiKey: string): Promise<void> {
  // Если уже загружено
  if (window.ymaps && window.ymaps.ready) {
    return new Promise((resolve) => {
      window.ymaps.ready(resolve)
    })
  }

  // Если уже загружается, возвращаем существующий промис
  if (loadPromise) {
    return loadPromise
  }

  // Создаем новый промис загрузки
  loadPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=${apiKey}`
    script.async = true
    
    script.onload = () => {
      window.ymaps.ready(() => {
        resolve()
      })
    }
    
    script.onerror = () => {
      loadPromise = null
      reject(new Error('Не удалось загрузить Яндекс.Карты'))
    }
    
    document.head.appendChild(script)
  })

  return loadPromise
}

// Типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}