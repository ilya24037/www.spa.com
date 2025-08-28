/**
 * Загрузчик API Яндекс.Карт
 * Обеспечивает однократную загрузку скрипта
 */

// Простая рабочая версия загрузчика (как в старой версии)
export function loadYandexMaps(apiKey: string): Promise<void> {
  console.log('🗺️ loadYandexMaps: Starting (SIMPLE VERSION)...', { apiKey })
  
  return new Promise((resolve, reject) => {
    // Если уже загружено и готово
    if (window.ymaps && window.ymaps.ready) {
      console.log('🗺️ loadYandexMaps: Already loaded, calling ready')
      window.ymaps.ready(() => {
        console.log('🗺️ loadYandexMaps: Ready callback completed')
        resolve()
      })
      return
    }

    console.log('🗺️ loadYandexMaps: Creating new script tag')
    const script = document.createElement('script')
    script.src = `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=${apiKey}`
    
    script.onload = () => {
      console.log('🗺️ loadYandexMaps: Script loaded, waiting for ymaps.ready')
      window.ymaps.ready(() => {
        console.log('🗺️ loadYandexMaps: ymaps.ready completed successfully')
        resolve()
      })
    }
    
    script.onerror = () => {
      console.error('🗺️ loadYandexMaps: Script loading failed')
      reject(new Error('Не удалось загрузить Яндекс.Карты'))
    }
    
    document.head.appendChild(script)
    console.log('🗺️ loadYandexMaps: Script tag added to head')
  })
}

// Типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}