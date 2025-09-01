/**
 * Простая функция загрузки Yandex Maps API по образцу архивной карты
 * Принцип KISS: убираем сложный Singleton, используем прямое Promise решение
 * Размер: 25 строк
 */

// Простая функция загрузки как в архиве с подробным логированием
export const loadYandexMaps = (apiKey: string): Promise<typeof ymaps> => {
  return new Promise((resolve, reject) => {
    console.log('[MapLoader] 🚀 Начинаем загрузку Yandex Maps API')
    console.log('[MapLoader] 🔑 API Key:', apiKey)
    
    // Если уже загружено, возвращаем сразу
    if (window.ymaps && window.ymaps.ready) {
      console.log('[MapLoader] ✅ API уже загружен, используем существующий')
      window.ymaps.ready(() => {
        console.log('[MapLoader] ✅ ymaps.ready() выполнен')
        resolve(window.ymaps)
      })
      return
    }

    // Проверяем API ключ
    if (!apiKey || apiKey.trim() === '') {
      console.warn('[MapLoader] ⚠️ API ключ пустой, загружаем без ключа')
    }

    // Создаем script tag 
    const script = document.createElement('script')
    // Используем стандартную загрузку API 2.1 (geocode включен по умолчанию)
    const url = `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=${apiKey}`
    script.src = url
    
    console.log('[MapLoader] 📡 Загружаем API с URL:', url)
    
    script.onload = () => {
      console.log('[MapLoader] 📦 Script загружен, ждём ymaps.ready()')
      
      if (window.ymaps && window.ymaps.ready) {
        window.ymaps.ready(() => {
          console.log('[MapLoader] ✅ Yandex Maps API готов к использованию')
          
          // Проверяем доступность модулей
          console.log('[MapLoader] 📦 Проверка загруженных модулей:')
          console.log('[MapLoader]   - geocode:', typeof window.ymaps.geocode === 'function' ? '✅' : '❌')
          console.log('[MapLoader]   - Map:', typeof window.ymaps.Map === 'function' ? '✅' : '❌')
          console.log('[MapLoader]   - Placemark:', typeof window.ymaps.Placemark === 'function' ? '✅' : '❌')
          console.log('[MapLoader]   - Clusterer:', typeof window.ymaps.Clusterer === 'function' ? '✅' : '❌')
          
          resolve(window.ymaps)
        })
      } else {
        console.error('[MapLoader] ❌ window.ymaps недоступен после загрузки script')
        reject(new Error('window.ymaps is not available after script load'))
      }
    }
    
    script.onerror = (error) => {
      console.error('[MapLoader] ❌ Ошибка загрузки script:', error)
      console.error('[MapLoader] 📡 URL который не удалось загрузить:', url)
      reject(new Error(`Failed to load Yandex Maps API from ${url}`))
    }
    
    console.log('[MapLoader] 🔄 Добавляем script в head')
    document.head.appendChild(script)
  })
}

// Экспортируем для обратной совместимости
export const mapLoader = {
  load: loadYandexMaps
}