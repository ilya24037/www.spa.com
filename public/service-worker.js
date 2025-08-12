/**
 * Service Worker для кеширования и офлайн поддержки
 * Оптимизирован для изображений и статических ресурсов
 */

const CACHE_NAME = 'spa-platform-v1'
const IMAGE_CACHE_NAME = 'spa-images-v1'
const API_CACHE_NAME = 'spa-api-v1'

// Ресурсы для предварительного кеширования
const PRECACHE_URLS = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/images/no-photo.svg',
  '/images/logo.svg'
]

// Стратегии кеширования для разных типов запросов
const CACHE_STRATEGIES = {
  // Cache First - для статических ресурсов
  cacheFirst: async (request, cacheName) => {
    const cache = await caches.open(cacheName)
    const cached = await cache.match(request)
    
    if (cached) {
      // Обновляем кеш в фоне
      fetch(request).then(response => {
        if (response && response.status === 200) {
          cache.put(request, response.clone())
        }
      })
      return cached
    }
    
    const response = await fetch(request)
    if (response && response.status === 200) {
      cache.put(request, response.clone())
    }
    return response
  },
  
  // Network First - для API запросов
  networkFirst: async (request, cacheName) => {
    try {
      const response = await fetch(request)
      if (response && response.status === 200) {
        const cache = await caches.open(cacheName)
        cache.put(request, response.clone())
      }
      return response
    } catch (error) {
      const cache = await caches.open(cacheName)
      const cached = await cache.match(request)
      if (cached) {
        return cached
      }
      throw error
    }
  },
  
  // Stale While Revalidate - для изображений
  staleWhileRevalidate: async (request, cacheName) => {
    const cache = await caches.open(cacheName)
    const cached = await cache.match(request)
    
    const fetchPromise = fetch(request).then(response => {
      if (response && response.status === 200) {
        cache.put(request, response.clone())
      }
      return response
    })
    
    return cached || fetchPromise
  }
}

// Установка Service Worker
self.addEventListener('install', event => {
  // Installing Service Worker
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        // Precaching app shell
        return cache.addAll(PRECACHE_URLS)
      })
      .then(() => self.skipWaiting())
  )
})

// Активация Service Worker
self.addEventListener('activate', event => {
  // Activating Service Worker
  
  event.waitUntil(
    // Очистка старых кешей
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => {
            return name.startsWith('spa-') && 
                   name !== CACHE_NAME && 
                   name !== IMAGE_CACHE_NAME && 
                   name !== API_CACHE_NAME
          })
          .map(name => {
            // Deleting old cache
            return caches.delete(name)
          })
      )
    }).then(() => self.clients.claim())
  )
})

// Обработка fetch запросов
self.addEventListener('fetch', event => {
  const { request } = event
  const url = new URL(request.url)
  
  // Игнорируем не-GET запросы
  if (request.method !== 'GET') return
  
  // Игнорируем внешние запросы
  if (url.origin !== location.origin && !url.hostname.includes('cdn')) return
  
  // Определяем стратегию кеширования
  let responsePromise
  
  if (request.destination === 'image' || url.pathname.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) {
    // Изображения - Stale While Revalidate
    responsePromise = CACHE_STRATEGIES.staleWhileRevalidate(request, IMAGE_CACHE_NAME)
  } else if (url.pathname.startsWith('/api/')) {
    // API запросы - Network First с fallback на кеш
    responsePromise = CACHE_STRATEGIES.networkFirst(request, API_CACHE_NAME)
  } else if (url.pathname.match(/\.(js|css)$/)) {
    // Статические ресурсы - Cache First
    responsePromise = CACHE_STRATEGIES.cacheFirst(request, CACHE_NAME)
  } else {
    // Остальное - Network First
    responsePromise = fetch(request).catch(() => {
      return caches.match(request)
    })
  }
  
  event.respondWith(responsePromise)
})

// Обработка push уведомлений
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Новое уведомление',
    icon: '/images/icon-192.png',
    badge: '/images/badge-72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  }
  
  event.waitUntil(
    self.registration.showNotification('SPA Platform', options)
  )
})

// Обработка клика по уведомлению
self.addEventListener('notificationclick', event => {
  event.notification.close()
  
  event.waitUntil(
    clients.openWindow('/')
  )
})

// Синхронизация в фоне
self.addEventListener('sync', event => {
  if (event.tag === 'sync-images') {
    event.waitUntil(syncImages())
  }
})

// Функция синхронизации изображений
async function syncImages() {
  const cache = await caches.open(IMAGE_CACHE_NAME)
  const requests = await cache.keys()
  
  // Обновляем кешированные изображения
  const updates = requests.map(async request => {
    try {
      const response = await fetch(request)
      if (response && response.status === 200) {
        await cache.put(request, response)
      }
    } catch (error) {
      console.error('[SW] Failed to sync image:', request.url)
    }
  })
  
  return Promise.all(updates)
}

// Обработка сообщений от клиента
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
  
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.keys().then(names => {
      Promise.all(names.map(name => caches.delete(name)))
    }).then(() => {
      event.ports[0].postMessage({ cleared: true })
    })
  }
  
  if (event.data && event.data.type === 'CACHE_IMAGES') {
    const urls = event.data.urls || []
    caches.open(IMAGE_CACHE_NAME).then(cache => {
      return Promise.all(
        urls.map(url => 
          fetch(url).then(response => {
            if (response && response.status === 200) {
              return cache.put(url, response)
            }
          }).catch(() => {})
        )
      )
    }).then(() => {
      event.ports[0].postMessage({ cached: true })
    })
  }
})