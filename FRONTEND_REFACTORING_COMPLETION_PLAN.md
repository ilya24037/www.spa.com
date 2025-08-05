# 🚀 ДЕТАЛЬНЫЙ ПЛАН ЗАВЕРШЕНИЯ FRONTEND REFACTORING

## 📊 ТЕКУЩЕЕ STATE ANALYSIS

### ✅ УЖЕ ВЫПОЛНЕНО (НЕ ТРОГАТЬ!)
```
✅ Pages миграция завершена (Home.vue, Masters/Show.vue, Dashboard.vue)
✅ Widgets созданы (masters-catalog, master-profile, profile-dashboard)
✅ Shared компоненты высокого качества (Button, Toast, Layouts)
✅ Entities базовая структура (master, booking, ad)
✅ TypeScript интеграция (50 TS файлов)
```

### 🔴 КРИТИЧЕСКИЕ ПРОПУСКИ
```
❌ Features stores отсутствуют (приоритет 1)
❌ 88 legacy компонентов не удалены (приоритет 2)
❌ PhotoViewer, ProfileTabs не созданы (приоритет 3)
❌ 150 console.log в production (приоритет 1)
❌ JavaScript stores не мигрированы на TS (приоритет 2)
```

---

## 🎯 ФАЗА 1: КРИТИЧЕСКИЕ ИСПРАВЛЕНИЯ (Дни 1-5)

### **ДЕНЬ 1: Создание Features Stores**

#### 1.1 Masters Filter Store (4 часа)
```bash
# Создать структуру
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\model
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterPanel
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterCategory
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterPrice
```

**Создать файл:** `C:\www.spa.com\resources\js\src\features\masters-filter\model\filter.store.ts`
```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface FilterState {
  searchTerm: string
  category: string | null
  priceMin: number
  priceMax: number
  district: string | null
  metro: string | null
  rating: number | null
  serviceType: string | null
  workFormat: 'home' | 'salon' | 'both' | null
  isOnline: boolean | null
  availableNow: boolean | null
}

export const useFilterStore = defineStore('masters-filter', () => {
  // =================== STATE ===================
  const filters = ref<FilterState>({
    searchTerm: '',
    category: null,
    priceMin: 0,
    priceMax: 100000,
    district: null,
    metro: null,
    rating: null,
    serviceType: null,
    workFormat: null,
    isOnline: null,
    availableNow: null
  })
  
  const isLoading = ref(false)
  const totalCount = ref(0)
  
  // =================== GETTERS ===================
  const hasActiveFilters = computed(() => {
    return filters.value.searchTerm !== '' ||
           filters.value.category !== null ||
           filters.value.priceMin > 0 ||
           filters.value.priceMax < 100000 ||
           filters.value.district !== null ||
           filters.value.metro !== null ||
           filters.value.rating !== null ||
           filters.value.serviceType !== null ||
           filters.value.workFormat !== null ||
           filters.value.isOnline !== null ||
           filters.value.availableNow !== null
  })
  
  const filterQuery = computed(() => {
    const query: Record<string, any> = {}
    
    if (filters.value.searchTerm) query.search = filters.value.searchTerm
    if (filters.value.category) query.category = filters.value.category
    if (filters.value.priceMin > 0) query.price_min = filters.value.priceMin
    if (filters.value.priceMax < 100000) query.price_max = filters.value.priceMax
    if (filters.value.district) query.district = filters.value.district
    if (filters.value.metro) query.metro = filters.value.metro
    if (filters.value.rating) query.rating = filters.value.rating
    if (filters.value.serviceType) query.service_type = filters.value.serviceType
    if (filters.value.workFormat) query.work_format = filters.value.workFormat
    if (filters.value.isOnline !== null) query.is_online = filters.value.isOnline
    if (filters.value.availableNow !== null) query.available_now = filters.value.availableNow
    
    return query
  })
  
  // =================== ACTIONS ===================
  const updateFilter = (key: keyof FilterState, value: any) => {
    filters.value[key] = value
  }
  
  const resetFilters = () => {
    filters.value = {
      searchTerm: '',
      category: null,
      priceMin: 0,
      priceMax: 100000,
      district: null,
      metro: null,
      rating: null,
      serviceType: null,
      workFormat: null,
      isOnline: null,
      availableNow: null
    }
  }
  
  const applyFilters = async () => {
    isLoading.value = true
    try {
      // Логика применения фильтров через API
      // Будет интегрировано с существующим MastersCatalog
    } finally {
      isLoading.value = false
    }
  }
  
  return {
    // State
    filters,
    isLoading,
    totalCount,
    
    // Getters
    hasActiveFilters,
    filterQuery,
    
    // Actions
    updateFilter,
    resetFilters,
    applyFilters
  }
})
```

#### 1.2 Gallery Store (2 часа)
**Создать файл:** `C:\www.spa.com\resources\js\src\features\gallery\model\gallery.store.ts`
```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface GalleryImage {
  id: string
  url: string
  thumbnail?: string
  alt?: string
  caption?: string
  type: 'photo' | 'video'
}

export const useGalleryStore = defineStore('gallery', () => {
  // State
  const images = ref<GalleryImage[]>([])
  const currentIndex = ref(0)
  const isViewerOpen = ref(false)
  const isLoading = ref(false)
  
  // Getters
  const currentImage = computed(() => images.value[currentIndex.value])
  const hasNext = computed(() => currentIndex.value < images.value.length - 1)
  const hasPrev = computed(() => currentIndex.value > 0)
  
  // Actions
  const openViewer = (index: number = 0) => {
    currentIndex.value = index
    isViewerOpen.value = true
    document.body.style.overflow = 'hidden'
  }
  
  const closeViewer = () => {
    isViewerOpen.value = false
    document.body.style.overflow = ''
  }
  
  const nextImage = () => {
    if (hasNext.value) {
      currentIndex.value++
    }
  }
  
  const prevImage = () => {
    if (hasPrev.value) {
      currentIndex.value--
    }
  }
  
  const setImages = (newImages: GalleryImage[]) => {
    images.value = newImages
  }
  
  return {
    // State
    images,
    currentIndex,
    isViewerOpen,
    isLoading,
    
    // Getters
    currentImage,
    hasNext,
    hasPrev,
    
    // Actions
    openViewer,
    closeViewer,
    nextImage,
    prevImage,
    setImages
  }
})
```

#### 1.3 Проверка создания stores
```bash
# Проверить что файлы созданы
ls -la C:\www.spa.com\resources\js\src\features\masters-filter\model\
ls -la C:\www.spa.com\resources\js\src\features\gallery\model\
```

### **ДЕНЬ 2: Production Cleanup - Удаление Debug Кода**

#### 2.1 Удаление console.log (2 часа)
```bash
# Бэкап перед изменениями
cd C:\www.spa.com
git add .
git commit -m "backup: before removing console.log statements"

# Создать скрипт очистки
```

**Создать файл:** `C:\www.spa.com\cleanup-debug.js`
```javascript
const fs = require('fs')
const path = require('path')

const removeConsoleLog = (filePath) => {
  const content = fs.readFileSync(filePath, 'utf8')
  const lines = content.split('\n')
  
  const cleanedLines = lines.filter(line => {
    const trimmed = line.trim()
    return !trimmed.startsWith('console.log(') && 
           !trimmed.startsWith('console.error(') &&
           !trimmed.startsWith('console.warn(') &&
           !trimmed.startsWith('console.info(') &&
           !trimmed.includes('// console.log')
  })
  
  fs.writeFileSync(filePath, cleanedLines.join('\n'))
}

// Обработать все Vue и JS файлы
const processDirectory = (dir) => {
  const files = fs.readdirSync(dir)
  
  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)
    
    if (stat.isDirectory()) {
      processDirectory(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js')) {
      console.log(`Processing: ${filePath}`)
      removeConsoleLog(filePath)
    }
  })
}

processDirectory('./resources/js')
console.log('Debug cleanup completed!')
```

```bash
# Запустить очистку
node cleanup-debug.js

# Проверить результат
grep -r "console\." resources/js --include="*.vue" --include="*.js" | wc -l
# Должно быть 0 или близко к 0
```

#### 2.2 Замена alert() на toast (1 час)
```bash
# Найти все alert()
grep -r "alert(" resources/js --include="*.vue" --include="*.js"

# Создать скрипт замены
```

**Создать файл:** `C:\www.spa.com\replace-alerts.js`
```javascript
const fs = require('fs')
const path = require('path')

const replaceAlerts = (filePath) => {
  let content = fs.readFileSync(filePath, 'utf8')
  
  // Замены alert на toast
  content = content.replace(/alert\(['"`]([^'"`]+)['"`]\)/g, "toast.info('$1')")
  content = content.replace(/alert\(([^)]+)\)/g, "toast.info($1)")
  
  // Добавить импорт useToast если его нет
  if (content.includes('toast.') && !content.includes('useToast')) {
    const scriptMatch = content.match(/<script[^>]*>/)
    if (scriptMatch) {
      const importLine = "import { useToast } from '@/src/shared/composables/useToast'\n"
      const toastLine = "const toast = useToast()\n\n"
      content = content.replace(scriptMatch[0], scriptMatch[0] + '\n' + importLine + toastLine)
    }
  }
  
  fs.writeFileSync(filePath, content)
}

// Обработать файлы
const processDirectory = (dir) => {
  const files = fs.readdirSync(dir)
  
  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)
    
    if (stat.isDirectory()) {
      processDirectory(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js')) {
      const content = fs.readFileSync(filePath, 'utf8')
      if (content.includes('alert(')) {
        console.log(`Replacing alerts in: ${filePath}`)
        replaceAlerts(filePath)
      }
    }
  })
}

processDirectory('./resources/js')
console.log('Alert replacement completed!')
```

```bash
node replace-alerts.js
```

### **ДЕНЬ 3: Миграция JavaScript Stores на TypeScript**

#### 3.1 bookingStore.js → bookingStore.ts (3 часа)
```bash
# Создать TypeScript версию
cp resources/js/stores/bookingStore.js resources/js/stores/bookingStore.ts
```

**Обновить файл:** `C:\www.spa.com\resources\js\stores\bookingStore.ts`
```typescript
import { defineStore } from 'pinia'
import { ref, computed, type Ref } from 'vue'
import axios from 'axios'

// =================== TYPES ===================
export interface BookingData {
  masterId: number
  serviceId: number
  date: string
  time: string
  locationType: 'home' | 'salon'
  clientName: string
  clientPhone: string
  clientEmail?: string
  address?: string
  comment?: string
  paymentMethod: 'cash' | 'card'
}

export interface BookingResult {
  id: number
  status: 'pending' | 'confirmed' | 'cancelled'
  masterName: string
  serviceName: string
  date: string
  time: string
  totalPrice: number
}

export interface TimeSlot {
  time: string
  available: boolean
  price?: number
}

interface BookingState {
  masterId: number | null
  serviceId: number | null
  date: string | null
  time: string | null
  locationType: 'home' | 'salon'
  clientName: string
  clientPhone: string
  clientEmail: string
  address: string
  comment: string
  paymentMethod: 'cash' | 'card'
}

// =================== STORE ===================
export const useBookingStore = defineStore('booking', () => {
  // State
  const bookings: Ref<BookingResult[]> = ref([])
  const currentBooking: Ref<BookingState> = ref({
    masterId: null,
    serviceId: null,
    date: null,
    time: null,
    locationType: 'salon',
    clientName: '',
    clientPhone: '',
    clientEmail: '',
    address: '',
    comment: '',
    paymentMethod: 'cash'
  })
  
  const availableSlots: Ref<Record<string, TimeSlot[]>> = ref({})
  const isLoading: Ref<boolean> = ref(false)
  const error: Ref<string | null> = ref(null)
  const lastBooking: Ref<BookingResult | null> = ref(null)
  
  // Getters
  const hasActiveBookings = computed(() => 
    bookings.value.filter(b => b.status === 'confirmed').length > 0
  )
  
  const pendingBookings = computed(() => 
    bookings.value.filter(b => b.status === 'pending')
  )
  
  const canSubmitBooking = computed(() => {
    const booking = currentBooking.value
    return booking.masterId !== null &&
           booking.serviceId !== null &&
           booking.date !== null &&
           booking.time !== null &&
           booking.clientName.trim() !== '' &&
           booking.clientPhone.trim() !== ''
  })
  
  // Actions
  const loadTimeSlots = async (masterId: number, date: string): Promise<TimeSlot[]> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get(`/api/masters/${masterId}/slots`, {
        params: { date }
      })
      
      const slots = response.data.slots || []
      availableSlots.value[`${masterId}-${date}`] = slots
      return slots
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка загрузки слотов'
      return []
    } finally {
      isLoading.value = false
    }
  }
  
  const loadAvailableDates = async (masterId: number): Promise<string[]> => {
    try {
      const response = await axios.get(`/api/masters/${masterId}/available-dates`)
      return response.data.dates || []
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка загрузки дат'
      return generateTestDates()
    }
  }
  
  const generateTestDates = (): string[] => {
    const dates: string[] = []
    const today = new Date()
    
    for (let i = 1; i <= 30; i++) {
      const date = new Date(today)
      date.setDate(today.getDate() + i)
      dates.push(date.toISOString().split('T')[0])
    }
    
    return dates
  }
  
  const createBooking = async (bookingData: BookingData): Promise<BookingResult> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/bookings', bookingData)
      const result: BookingResult = response.data.booking
      
      bookings.value.push(result)
      lastBooking.value = result
      resetCurrentBooking()
      
      return result
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка создания бронирования'
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  const cancelBooking = async (bookingId: number): Promise<void> => {
    try {
      await axios.post(`/api/bookings/${bookingId}/cancel`)
      
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'cancelled'
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка отмены бронирования'
      throw err
    }
  }
  
  const updateCurrentBooking = (updates: Partial<BookingState>): void => {
    currentBooking.value = { ...currentBooking.value, ...updates }
  }
  
  const resetCurrentBooking = (): void => {
    currentBooking.value = {
      masterId: null,
      serviceId: null,
      date: null,
      time: null,
      locationType: 'salon',
      clientName: '',
      clientPhone: '',
      clientEmail: '',
      address: '',
      comment: '',
      paymentMethod: 'cash'
    }
  }
  
  const loadUserBookings = async (): Promise<void> => {
    isLoading.value = true
    try {
      const response = await axios.get('/api/user/bookings')
      bookings.value = response.data.bookings || []
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка загрузки бронирований'
    } finally {
      isLoading.value = false
    }
  }
  
  return {
    // State
    bookings,
    currentBooking,
    availableSlots,
    isLoading,
    error,
    lastBooking,
    
    // Getters
    hasActiveBookings,
    pendingBookings,
    canSubmitBooking,
    
    // Actions
    loadTimeSlots,
    loadAvailableDates,
    generateTestDates,
    createBooking,
    cancelBooking,
    updateCurrentBooking,
    resetCurrentBooking,
    loadUserBookings
  }
})
```

#### 3.2 Обновить импорты
```bash
# Найти все импорты bookingStore.js
grep -r "bookingStore.js" resources/js

# Заменить на .ts
find resources/js -name "*.vue" -o -name "*.js" -o -name "*.ts" | xargs sed -i 's/bookingStore\.js/bookingStore.ts/g'
```

### **ДЕНЬ 4: Создание PhotoViewer Feature**

#### 4.1 Структура PhotoViewer (2 часа)
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoViewer
mkdir -p C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoThumbnails
mkdir -p C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoGallery
```

**Создать файл:** `C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoViewer\PhotoViewer.vue`
```vue
<template>
  <Teleport to="body">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center"
      @click="close"
      @keyup.esc="close"
      tabindex="0"
    >
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black opacity-90"></div>
      
      <!-- Viewer Content -->
      <div class="relative z-10 w-full h-full flex items-center justify-center p-4">
        <!-- Close Button -->
        <button
          @click="close"
          class="absolute top-4 right-4 text-white hover:text-gray-300 z-20"
          aria-label="Закрыть просмотр"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Navigation Arrows -->
        <button
          v-if="hasPrev"
          @click.stop="prevImage"
          class="absolute left-4 text-white hover:text-gray-300 z-20"
          aria-label="Предыдущее изображение"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <button
          v-if="hasNext"
          @click.stop="nextImage"
          class="absolute right-4 text-white hover:text-gray-300 z-20"
          aria-label="Следующее изображение"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Current Image -->
        <div class="max-w-full max-h-full flex items-center justify-center">
          <img
            v-if="currentImage"
            :src="currentImage.url"
            :alt="currentImage.alt || 'Изображение'"
            class="max-w-full max-h-full object-contain"
            @click.stop
          >
        </div>

        <!-- Image Counter -->
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white text-sm">
          {{ currentIndex + 1 }} из {{ totalImages }}
        </div>

        <!-- Image Info -->
        <div v-if="currentImage?.caption" class="absolute bottom-12 left-1/2 transform -translate-x-1/2 text-white text-center max-w-md">
          {{ currentImage.caption }}
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useGalleryStore } from '../../model/gallery.store'

// Store
const galleryStore = useGalleryStore()

// Computed
const isOpen = computed(() => galleryStore.isViewerOpen)
const currentImage = computed(() => galleryStore.currentImage)
const currentIndex = computed(() => galleryStore.currentIndex)
const totalImages = computed(() => galleryStore.images.length)
const hasNext = computed(() => galleryStore.hasNext)
const hasPrev = computed(() => galleryStore.hasPrev)

// Methods
const close = () => {
  galleryStore.closeViewer()
}

const nextImage = () => {
  galleryStore.nextImage()
}

const prevImage = () => {
  galleryStore.prevImage()
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (!isOpen.value) return
  
  switch (event.key) {
    case 'Escape':
      close()
      break
    case 'ArrowLeft':
      prevImage()
      break
    case 'ArrowRight':
      nextImage()
      break
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
```

#### 4.2 PhotoThumbnails компонент (1 час)
**Создать файл:** `C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoThumbnails\PhotoThumbnails.vue`
```vue
<template>
  <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
    <button
      v-for="(image, index) in images"
      :key="image.id"
      @click="openViewer(index)"
      class="aspect-square rounded-lg overflow-hidden bg-gray-200 hover:opacity-80 transition-opacity"
      :aria-label="`Открыть изображение ${index + 1}`"
    >
      <img
        :src="image.thumbnail || image.url"
        :alt="image.alt || `Изображение ${index + 1}`"
        class="w-full h-full object-cover"
        loading="lazy"
      >
    </button>
  </div>
</template>

<script setup lang="ts">
import type { GalleryImage } from '../../model/gallery.store'
import { useGalleryStore } from '../../model/gallery.store'

interface Props {
  images: GalleryImage[]
}

const props = defineProps<Props>()

// Store
const galleryStore = useGalleryStore()

// Methods
const openViewer = (index: number) => {
  galleryStore.setImages(props.images)
  galleryStore.openViewer(index)
}
</script>
```

#### 4.3 Index файлы для экспорта
**Создать файл:** `C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoViewer\index.ts`
```typescript
export { default as PhotoViewer } from './PhotoViewer.vue'
```

**Создать файл:** `C:\www.spa.com\resources\js\src\features\gallery\ui\PhotoThumbnails\index.ts`
```typescript
export { default as PhotoThumbnails } from './PhotoThumbnails.vue'
```

**Создать файл:** `C:\www.spa.com\resources\js\src\features\gallery\index.ts`
```typescript
export { PhotoViewer } from './ui/PhotoViewer'
export { PhotoThumbnails } from './ui/PhotoThumbnails'
export { useGalleryStore } from './model/gallery.store'
```

### **ДЕНЬ 5: Profile Navigation Feature**

#### 5.1 Создание ProfileTabs (3 часа)
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\profile-navigation\ui\ProfileTabs
mkdir -p C:\www.spa.com\resources\js\src\features\profile-navigation\ui\ProfileStats
mkdir -p C:\www.spa.com\resources\js\src\features\profile-navigation\model
```

**Создать файл:** `C:\www.spa.com\resources\js\src\features\profile-navigation\model\navigation.store.ts`
```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type TabKey = 'waiting' | 'active' | 'completed' | 'drafts' | 'favorites' | 'settings'

export interface Tab {
  key: TabKey
  label: string
  count?: number
  icon?: string
}

export const useProfileNavigationStore = defineStore('profile-navigation', () => {
  // State
  const activeTab = ref<TabKey>('waiting')
  const tabs = ref<Tab[]>([
    { key: 'waiting', label: 'Ждут действий', count: 0, icon: 'clock' },
    { key: 'active', label: 'Активные', count: 0, icon: 'check-circle' },
    { key: 'completed', label: 'Завершенные', count: 0, icon: 'archive' },
    { key: 'drafts', label: 'Черновики', count: 0, icon: 'document' },
    { key: 'favorites', label: 'Избранное', count: 0, icon: 'heart' },
    { key: 'settings', label: 'Настройки', count: 0, icon: 'cog' }
  ])
  
  // Getters
  const currentTab = computed(() => 
    tabs.value.find(tab => tab.key === activeTab.value)
  )
  
  const tabsWithCounts = computed(() => 
    tabs.value.filter(tab => tab.count !== undefined && tab.count > 0)
  )
  
  // Actions
  const setActiveTab = (tabKey: TabKey) => {
    activeTab.value = tabKey
  }
  
  const updateTabCount = (tabKey: TabKey, count: number) => {
    const tab = tabs.value.find(t => t.key === tabKey)
    if (tab) {
      tab.count = count
    }
  }
  
  const updateAllCounts = (counts: Record<TabKey, number>) => {
    Object.entries(counts).forEach(([key, count]) => {
      updateTabCount(key as TabKey, count)
    })
  }
  
  return {
    // State
    activeTab,
    tabs,
    
    // Getters
    currentTab,
    tabsWithCounts,
    
    // Actions
    setActiveTab,
    updateTabCount,
    updateAllCounts
  }
})
```

**Создать файл:** `C:\www.spa.com\resources\js\src\features\profile-navigation\ui\ProfileTabs\ProfileTabs.vue`
```vue
<template>
  <div class="border-b border-gray-200">
    <nav class="flex space-x-8" aria-label="Навигация по профилю">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="setActiveTab(tab.key)"
        :class="[
          'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
          activeTab === tab.key
            ? 'border-blue-500 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
        ]"
        :aria-current="activeTab === tab.key ? 'page' : undefined"
      >
        <div class="flex items-center space-x-2">
          <!-- Icon -->
          <component
            v-if="tab.icon"
            :is="getIconComponent(tab.icon)"
            class="w-4 h-4"
          />
          
          <!-- Label -->
          <span>{{ tab.label }}</span>
          
          <!-- Count Badge -->
          <span
            v-if="tab.count && tab.count > 0"
            class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full"
          >
            {{ tab.count }}
          </span>
        </div>
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useProfileNavigationStore, type TabKey } from '../../model/navigation.store'

// Icons
import {
  ClockIcon,
  CheckCircleIcon,
  ArchiveBoxIcon,
  DocumentIcon,
  HeartIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

// Store
const navigationStore = useProfileNavigationStore()

// Computed
const activeTab = computed(() => navigationStore.activeTab)
const tabs = computed(() => navigationStore.tabs)

// Methods
const setActiveTab = (tabKey: TabKey) => {
  navigationStore.setActiveTab(tabKey)
}

const getIconComponent = (iconName: string) => {
  const iconMap = {
    'clock': ClockIcon,
    'check-circle': CheckCircleIcon,
    'archive': ArchiveBoxIcon,
    'document': DocumentIcon,
    'heart': HeartIcon,
    'cog': CogIcon
  }
  
  return iconMap[iconName as keyof typeof iconMap] || DocumentIcon
}
</script>
```

#### 5.2 ProfileStats компонент (1 час)
**Создать файл:** `C:\www.spa.com\resources\js\src\features\profile-navigation\ui\ProfileStats\ProfileStats.vue`
```vue
<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div
      v-for="stat in stats"
      :key="stat.key"
      class="bg-white p-4 rounded-lg shadow-sm border"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-600">{{ stat.label }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
        </div>
        <div :class="['p-2 rounded-lg', stat.bgColor]">
          <component
            :is="getIconComponent(stat.icon)"
            :class="['w-5 h-5', stat.iconColor]"
          />
        </div>
      </div>
      
      <!-- Trend -->
      <div v-if="stat.trend" class="mt-2 flex items-center text-sm">
        <span :class="stat.trend > 0 ? 'text-green-600' : 'text-red-600'">
          {{ stat.trend > 0 ? '+' : '' }}{{ stat.trend }}%
        </span>
        <span class="text-gray-500 ml-1">за месяц</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  EyeIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'

interface Stat {
  key: string
  label: string
  value: number | string
  icon: string
  bgColor: string
  iconColor: string
  trend?: number
}

interface Props {
  stats: {
    views: number
    favorites: number
    messages: number
    earnings: number
    viewsTrend?: number
    favoritesTrend?: number
    messagesTrend?: number
    earningsTrend?: number
  }
}

const props = defineProps<Props>()

// Computed
const stats = computed<Stat[]>(() => [
  {
    key: 'views',
    label: 'Просмотры',
    value: props.stats.views.toLocaleString(),
    icon: 'eye',
    bgColor: 'bg-blue-100',
    iconColor: 'text-blue-600',
    trend: props.stats.viewsTrend
  },
  {
    key: 'favorites',
    label: 'В избранном',
    value: props.stats.favorites,
    icon: 'heart',
    bgColor: 'bg-red-100',
    iconColor: 'text-red-600',
    trend: props.stats.favoritesTrend
  },
  {
    key: 'messages',
    label: 'Сообщения',
    value: props.stats.messages,
    icon: 'chat',
    bgColor: 'bg-green-100',
    iconColor: 'text-green-600',
    trend: props.stats.messagesTrend
  },
  {
    key: 'earnings',
    label: 'Заработок',
    value: `${props.stats.earnings.toLocaleString()} ₽`,
    icon: 'currency',
    bgColor: 'bg-yellow-100',
    iconColor: 'text-yellow-600',
    trend: props.stats.earningsTrend
  }
])

// Methods
const getIconComponent = (iconName: string) => {
  const iconMap = {
    'eye': EyeIcon,
    'heart': HeartIcon,
    'chat': ChatBubbleLeftIcon,
    'currency': CurrencyDollarIcon
  }
  
  return iconMap[iconName as keyof typeof iconMap] || EyeIcon
}
</script>
```

#### 5.3 Index файлы
**Создать:** `C:\www.spa.com\resources\js\src\features\profile-navigation\index.ts`
```typescript
export { ProfileTabs } from './ui/ProfileTabs'
export { ProfileStats } from './ui/ProfileStats'
export { useProfileNavigationStore } from './model/navigation.store'
```

---

## 🧹 ФАЗА 2: АРХИТЕКТУРНАЯ ОЧИСТКА (Дни 6-10)

### **ДЕНЬ 6: Анализ Legacy Компонентов**

#### 6.1 Создание отчета дублирования (2 часа)
**Создать файл:** `C:\www.spa.com\analyze-duplicates.js`
```javascript
const fs = require('fs')
const path = require('path')

const legacyComponents = []
const fsdComponents = []

// Сканировать legacy Components/
const scanLegacy = (dir, basePath = '') => {
  const files = fs.readdirSync(dir)
  
  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)
    
    if (stat.isDirectory()) {
      scanLegacy(filePath, path.join(basePath, file))
    } else if (file.endsWith('.vue')) {
      legacyComponents.push({
        name: file,
        path: path.join(basePath, file).replace(/\\/g, '/'),
        fullPath: filePath.replace(/\\/g, '/')
      })
    }
  })
}

// Сканировать FSD src/
const scanFSD = (dir, basePath = '') => {
  const files = fs.readdirSync(dir)
  
  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)
    
    if (stat.isDirectory()) {
      scanFSD(filePath, path.join(basePath, file))
    } else if (file.endsWith('.vue')) {
      fsdComponents.push({
        name: file,
        path: path.join(basePath, file).replace(/\\/g, '/'),
        fullPath: filePath.replace(/\\/g, '/')
      })
    }
  })
}

// Анализ дублирования
console.log('🔍 Анализ дублирования компонентов...\n')

scanLegacy('./resources/js/Components')
scanFSD('./resources/js/src')

console.log(`📊 СТАТИСТИКА:`)
console.log(`Legacy компонентов: ${legacyComponents.length}`)
console.log(`FSD компонентов: ${fsdComponents.length}`)
console.log(`Общее количество: ${legacyComponents.length + fsdComponents.length}\n`)

// Найти потенциальные дубликаты по именам
const duplicates = []
legacyComponents.forEach(legacy => {
  const fsdMatch = fsdComponents.find(fsd => 
    fsd.name === legacy.name || 
    fsd.name.includes(legacy.name.replace('.vue', '')) ||
    legacy.name.includes(fsd.name.replace('.vue', ''))
  )
  
  if (fsdMatch) {
    duplicates.push({
      legacy: legacy,
      fsd: fsdMatch
    })
  }
})

console.log(`🔄 НАЙДЕНО ПОТЕНЦИАЛЬНЫХ ДУБЛИКАТОВ: ${duplicates.length}\n`)

duplicates.forEach(dup => {
  console.log(`Legacy: ${dup.legacy.path}`)
  console.log(`FSD:    ${dup.fsd.path}`)
  console.log('---')
})

// Сохранить отчет
const report = {
  summary: {
    legacyCount: legacyComponents.length,
    fsdCount: fsdComponents.length,
    duplicatesCount: duplicates.length,
    date: new Date().toISOString()
  },
  legacyComponents,
  fsdComponents,
  duplicates
}

fs.writeFileSync('./duplication-report.json', JSON.stringify(report, null, 2))
console.log('\n✅ Отчет сохранен в duplication-report.json')
```

```bash
node analyze-duplicates.js
```

#### 6.2 Создание списка безопасного удаления (2 часа)
**Создать файл:** `C:\www.spa.com\create-deletion-list.js`
```javascript
const fs = require('fs')
const path = require('path')
const report = JSON.parse(fs.readFileSync('./duplication-report.json', 'utf8'))

// Проанализировать использование компонентов
const checkUsage = (componentPath) => {
  const componentName = path.basename(componentPath, '.vue')
  
  // Поиск импортов компонента
  const searchPatterns = [
    `import.*${componentName}.*from`,
    `import.*from.*${componentPath}`,
    `<${componentName}`,
    `@/Components/${componentPath.replace('resources/js/Components/', '')}`
  ]
  
  let usageCount = 0
  const usageFiles = []
  
  // Сканировать все Vue, JS, TS файлы
  const scanForUsage = (dir) => {
    const files = fs.readdirSync(dir)
    
    files.forEach(file => {
      const filePath = path.join(dir, file)
      const stat = fs.statSync(filePath)
      
      if (stat.isDirectory() && !file.includes('node_modules')) {
        scanForUsage(filePath)
      } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
        const content = fs.readFileSync(filePath, 'utf8')
        
        searchPatterns.forEach(pattern => {
          const regex = new RegExp(pattern, 'gi')
          const matches = content.match(regex)
          if (matches) {
            usageCount += matches.length
            if (!usageFiles.includes(filePath)) {
              usageFiles.push(filePath)
            }
          }
        })
      }
    })
  }
  
  scanForUsage('./resources/js')
  
  return {
    count: usageCount,
    files: usageFiles
  }
}

// Создать список для удаления
const deletionList = []
const keepList = []

report.legacyComponents.forEach(component => {
  const usage = checkUsage(component.fullPath)
  
  if (usage.count === 0) {
    deletionList.push({
      component: component,
      reason: 'Не используется'
    })
  } else if (usage.count <= 2) {
    // Проверить есть ли FSD аналог
    const hasFSDAnalog = report.duplicates.some(dup => 
      dup.legacy.fullPath === component.fullPath
    )
    
    if (hasFSDAnalog) {
      deletionList.push({
        component: component,
        reason: 'Имеет FSD аналог, мало использований',
        usage: usage
      })
    } else {
      keepList.push({
        component: component,
        reason: 'Используется, нет FSD аналога',
        usage: usage
      })
    }
  } else {
    keepList.push({
      component: component,
      reason: 'Активно используется',
      usage: usage
    })
  }
})

console.log(`📋 АНАЛИЗ ИСПОЛЬЗОВАНИЯ ЗАВЕРШЕН:`)
console.log(`🗑️  К удалению: ${deletionList.length}`)
console.log(`📌 Оставить: ${keepList.length}\n`)

// Сохранить результат
const deletionReport = {
  summary: {
    toDelete: deletionList.length,
    toKeep: keepList.length,
    date: new Date().toISOString()
  },
  deletionList,
  keepList
}

fs.writeFileSync('./deletion-plan.json', JSON.stringify(deletionReport, null, 2))

// Создать bash скрипт для удаления
let deleteScript = '#!/bin/bash\n\n'
deleteScript += '# Создание бэкапа\n'
deleteScript += 'git add .\n'
deleteScript += 'git commit -m "backup: before legacy cleanup"\n\n'
deleteScript += '# Удаление неиспользуемых компонентов\n'

deletionList.forEach(item => {
  deleteScript += `rm "${item.component.fullPath}"\n`
})

fs.writeFileSync('./delete-legacy.sh', deleteScript)

console.log('✅ План удаления создан: deletion-plan.json')
console.log('✅ Скрипт удаления создан: delete-legacy.sh')
```

```bash
node create-deletion-list.js
```

### **ДЕНЬ 7: Безопасное удаление Legacy компонентов**

#### 7.1 Выполнение удаления (3 часа)
```bash
# Просмотреть план удаления
cat deletion-plan.json | jq '.summary'

# Создать бэкап
git add .
git commit -m "backup: before legacy cleanup phase"

# Выполнить удаление
chmod +x delete-legacy.sh
./delete-legacy.sh

# Проверить что проект собирается
npm run build
```

#### 7.2 Проверка и исправление ошибок (2 часа)
```bash
# Проверить ошибки сборки
npm run build 2>&1 | tee build-errors.log

# Если есть ошибки, найти оставшиеся импорты
grep -r "Components/" resources/js/src/ | grep -v ".vue:"
```

**Создать файл:** `C:\www.spa.com\fix-imports.js`
```javascript
const fs = require('fs')
const path = require('path')

// Маппинг старых путей на новые FSD пути
const importMappings = {
  // Legacy -> FSD mappings
  '@/Components/Cards/ItemCard.vue': '@/src/entities/ad/ui/AdCard/ItemCard.vue',
  '@/Components/Booking/BookingModal.vue': '@/src/entities/booking/ui/BookingModal/BookingModal.vue',
  '@/Components/Masters/MasterCard.vue': '@/src/entities/master/ui/MasterCard/MasterCard.vue',
  '@/Components/UI/Toast.vue': '@/src/shared/ui/molecules/Toast/Toast.vue',
  '@/Components/UI/Modal.vue': '@/src/shared/ui/organisms/Modal/Modal.vue',
  '@/Components/Layout/MainLayout.vue': '@/src/shared/layouts/MainLayout/MainLayout.vue',
  '@/Components/Layout/ProfileLayout.vue': '@/src/shared/layouts/ProfileLayout/ProfileLayout.vue'
}

const fixImportsInFile = (filePath) => {
  let content = fs.readFileSync(filePath, 'utf8')
  let modified = false
  
  Object.entries(importMappings).forEach(([oldPath, newPath]) => {
    if (content.includes(oldPath)) {
      content = content.replace(new RegExp(oldPath.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), newPath)
      modified = true
      console.log(`Fixed import in ${filePath}: ${oldPath} -> ${newPath}`)
    }
  })
  
  if (modified) {
    fs.writeFileSync(filePath, content)
  }
}

// Обработать все файлы
const processDirectory = (dir) => {
  const files = fs.readdirSync(dir)
  
  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)
    
    if (stat.isDirectory()) {
      processDirectory(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
      fixImportsInFile(filePath)
    }
  })
}

console.log('🔧 Исправление импортов...')
processDirectory('./resources/js')
console.log('✅ Импорты исправлены!')
```

```bash
node fix-imports.js
npm run build
```

### **ДЕНЬ 8: Оптимизация кода и удаление дублирования**

#### 8.1 Удаление дублирующих stores (2 часа)
```bash
# Удалить старый bookingStore.js после миграции на TS
rm resources/js/stores/bookingStore.js

# Обновить импорты
find resources/js -name "*.vue" | xargs grep -l "bookingStore.js" | xargs sed -i 's/bookingStore\.js/bookingStore.ts/g'
```

#### 8.2 Объединение дублирующих utils (1 час)
```bash
# Анализ utils
ls -la resources/js/utils/
ls -la resources/js/src/shared/lib/

# Проверить дублирование
```

**Создать скрипт:** `C:\www.spa.com\merge-utils.js`
```javascript
const fs = require('fs')
const path = require('path')

// Переместить utils в shared/lib
const utilsFiles = fs.readdirSync('./resources/js/utils')

utilsFiles.forEach(file => {
  if (file.endsWith('.js') || file.endsWith('.ts')) {
    const oldPath = `./resources/js/utils/${file}`
    const newPath = `./resources/js/src/shared/lib/${file}`
    
    // Создать директорию если не существует
    const dir = path.dirname(newPath)
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true })
    }
    
    // Переместить файл
    fs.renameSync(oldPath, newPath)
    console.log(`Moved: ${oldPath} -> ${newPath}`)
  }
})

// Обновить импорты
const updateImports = (dir) => {
  const files = fs.readdirSync(dir)
  
  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)
    
    if (stat.isDirectory()) {
      updateImports(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
      let content = fs.readFileSync(filePath, 'utf8')
      const oldPattern = /@\/utils\//g
      const newPattern = '@/src/shared/lib/'
      
      if (content.match(oldPattern)) {
        content = content.replace(oldPattern, newPattern)
        fs.writeFileSync(filePath, content)
        console.log(`Updated imports in: ${filePath}`)
      }
    }
  })
}

updateImports('./resources/js')
console.log('✅ Utils migration completed!')
```

```bash
node merge-utils.js
```

### **ДЕНЬ 9: Создание missing Features интеграции**

#### 9.1 Интеграция masters-filter store (2 часа)
**Обновить файл:** `C:\www.spa.com\resources\js\src\widgets\masters-catalog\MastersCatalog.vue`
```vue
<template>
  <div class="flex gap-6">
    <!-- Sidebar с фильтрами -->
    <SidebarWrapper v-model="showFilters" class="hidden lg:block">
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-lg">Фильтры</h2>
          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            Сбросить
          </button>
        </div>
      </template>
      
      <!-- Фильтры используют новый store -->
      <div class="space-y-6">
        <!-- Поиск -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Поиск по имени или услуге
          </label>
          <input
            v-model="filters.searchTerm"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md"
            placeholder="Введите запрос..."
          >
        </div>
        
        <!-- Категория -->
        <FilterCategory 
          v-model="filters.category"
          :categories="availableCategories"
        />
        
        <!-- Цена -->
        <FilterPrice
          v-model:min="filters.priceMin"
          v-model:max="filters.priceMax"
        />
        
        <!-- Район -->
        <FilterLocation
          v-model:district="filters.district"
          v-model:metro="filters.metro"
        />
        
        <!-- Рейтинг -->
        <FilterRating v-model="filters.rating" />
      </div>
    </SidebarWrapper>

    <!-- Основной контент -->
    <div class="flex-1">
      <ContentCard>
        <!-- Заголовок и счетчик -->
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-bold text-gray-900">
            Массажисты в {{ currentCity }}
          </h1>
          <div class="text-sm text-gray-500">
            Найдено: {{ filteredMasters.length }} мастеров
          </div>
        </div>
        
        <!-- Мобильные фильтры -->
        <button
          @click="showFilters = !showFilters"
          class="lg:hidden mb-6 btn-secondary"
        >
          Фильтры
          <span v-if="hasActiveFilters" class="ml-2 bg-blue-600 text-white px-2 py-1 rounded-full text-xs">
            {{ activeFiltersCount }}
          </span>
        </button>
        
        <!-- Список мастеров -->
        <MasterCardList 
          :masters="filteredMasters"
          :loading="isLoading"
          @profile-visited="handleProfileVisited"
          @favorite-toggled="handleFavoriteToggled"
          @booking-requested="handleBookingRequested"
          @phone-requested="handlePhoneRequested"
        />
        
        <!-- Карта -->
        <div class="mt-8">
          <UniversalMap 
            :markers="mapMarkers"
            :center="mapCenter"
            class="h-96 rounded-lg"
          />
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// FSD импорты
import { SidebarWrapper, ContentCard } from '@/src/shared/layouts/components'
import { MasterCardList } from '@/src/entities/master/ui/MasterCard'
import { UniversalMap } from '@/src/features/map'

// Новые фильтры с store
import { useFilterStore } from '@/src/features/masters-filter/model/filter.store'
import FilterCategory from '@/src/features/masters-filter/ui/FilterCategory/FilterCategory.vue'
import FilterPrice from '@/src/features/masters-filter/ui/FilterPrice/FilterPrice.vue'
import FilterLocation from '@/src/features/masters-filter/ui/FilterLocation/FilterLocation.vue'
import FilterRating from '@/src/features/masters-filter/ui/FilterRating/FilterRating.vue'

// Props
interface Props {
  initialMasters: Master[]
  currentCity: string
  availableCategories: Category[]
}

const props = defineProps<Props>()

// Store
const filterStore = useFilterStore()

// Local state
const showFilters = ref(false)
const masters = ref(props.initialMasters)
const isLoading = ref(false)

// Computed
const filters = computed(() => filterStore.filters)
const hasActiveFilters = computed(() => filterStore.hasActiveFilters)
const activeFiltersCount = computed(() => {
  return Object.values(filters.value).filter(value => 
    value !== null && value !== '' && value !== 0 && value !== 100000
  ).length
})

const filteredMasters = computed(() => {
  let result = masters.value
  
  // Применить фильтры
  if (filters.value.searchTerm) {
    const term = filters.value.searchTerm.toLowerCase()
    result = result.filter(master => 
      master.name.toLowerCase().includes(term) ||
      master.services?.some(service => 
        service.name.toLowerCase().includes(term)
      )
    )
  }
  
  if (filters.value.category) {
    result = result.filter(master => 
      master.category === filters.value.category
    )
  }
  
  if (filters.value.priceMin > 0 || filters.value.priceMax < 100000) {
    result = result.filter(master => {
      const price = master.averagePrice || 0
      return price >= filters.value.priceMin && price <= filters.value.priceMax
    })
  }
  
  // Другие фильтры...
  
  return result
})

const mapMarkers = computed(() => 
  filteredMasters.value
    .filter(master => master.latitude && master.longitude)
    .map(master => ({
      id: master.id,
      lat: master.latitude,
      lng: master.longitude,
      title: master.name,
      url: `/masters/${master.id}`
    }))
)

const mapCenter = computed(() => ({
  lat: 58.0105, // Пермь
  lng: 56.2502
}))

// Methods
const resetFilters = () => {
  filterStore.resetFilters()
}

const handleProfileVisited = (masterId: number) => {
  // Аналитика
}

const handleFavoriteToggled = (masterId: number, isFavorite: boolean) => {
  // Обновить состояние избранного
  const master = masters.value.find(m => m.id === masterId)
  if (master) {
    master.isFavorite = isFavorite
  }
}

const handleBookingRequested = (masterId: number) => {
  router.visit(`/masters/${masterId}?booking=true`)
}

const handlePhoneRequested = (masterId: number) => {
  // Показать телефон или модальное окно
}

// Watch фильтры для обновления результатов
watch(
  () => filterStore.filterQuery,
  async (newQuery) => {
    if (Object.keys(newQuery).length > 0) {
      isLoading.value = true
      try {
        // Загрузить отфильтрованные результаты с сервера
        const response = await router.get('/api/masters/search', {
          data: newQuery,
          preserveState: true,
          preserveScroll: true,
          only: ['masters']
        })
        // Обновить мастеров из ответа
      } catch (error) {
        console.error('Filter error:', error)
      } finally {
        isLoading.value = false
      }
    }
  },
  { deep: true }
)

onMounted(() => {
  // Инициализация
})
</script>
```

#### 9.2 Создание недостающих Filter компонентов (3 часа)
```bash
# Создать структуры
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterCategory
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterPrice
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterLocation
mkdir -p C:\www.spa.com\resources\js\src\features\masters-filter\ui\FilterRating
```

**FilterCategory.vue, FilterPrice.vue, FilterLocation.vue, FilterRating.vue** - создать базовые компоненты фильтров

### **ДЕНЬ 10: Интеграция Gallery в Master Profile**

#### 10.1 Обновление MasterProfile widget (2 часа)
**Обновить:** `C:\www.spa.com\resources\js\src\widgets\master-profile\MasterProfile.vue`
```vue
<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Левая колонка -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Галерея с новым PhotoViewer -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <PhotoThumbnails 
          :images="galleryImages"
          class="p-6"
        />
      </div>
      
      <MasterInfo :master="master" />
      <MasterServices :services="master.services" />
      <MasterReviews :reviews="master.reviews" />
    </div>

    <!-- Правая колонка -->
    <div class="space-y-6">
      <MasterContact :master="master" />
      <BookingWidget :master="master" />
    </div>
  </div>
  
  <!-- PhotoViewer - глобальный компонент -->
  <PhotoViewer />
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Entities
import { MasterInfo, MasterServices, MasterReviews, MasterContact } from '@/src/entities/master/ui'
import { BookingWidget } from '@/src/entities/booking/ui'

// Features
import { PhotoThumbnails, PhotoViewer } from '@/src/features/gallery'

interface Props {
  master: Master
}

const props = defineProps<Props>()

// Computed
const galleryImages = computed(() => {
  const images = []
  
  // Добавить фото мастера
  if (props.master.photos) {
    props.master.photos.forEach((photo, index) => {
      images.push({
        id: `photo-${photo.id}`,
        url: photo.url,
        thumbnail: photo.thumbnail_url,
        alt: `Фото мастера ${props.master.name} ${index + 1}`,
        type: 'photo'
      })
    })
  }
  
  // Добавить видео если есть
  if (props.master.videos) {
    props.master.videos.forEach((video, index) => {
      images.push({
        id: `video-${video.id}`,
        url: video.url,
        thumbnail: video.thumbnail_url,
        alt: `Видео мастера ${props.master.name} ${index + 1}`,
        type: 'video'
      })
    })
  }
  
  return images
})
</script>
```

---

## 🧪 ФАЗА 3: ИНТЕГРАЦИЯ И ТЕСТИРОВАНИЕ (Дни 11-13)

### **ДЕНЬ 11: Integration Testing**

#### 11.1 Тестирование критических путей (4 часа)
```bash
# Запустить dev сервер
npm run dev

# Проверить основные страницы
echo "Testing main pages..."
```

**Создать файл:** `C:\www.spa.com\integration-test.js`
```javascript
const puppeteer = require('puppeteer')

const testScenarios = [
  {
    name: 'Home Page Load',
    url: 'http://localhost:5173',
    tests: [
      'page loads without errors',
      'masters list is visible',
      'filters work',
      'map is loaded'
    ]
  },
  {
    name: 'Master Profile',
    url: 'http://localhost:5173/masters/1',
    tests: [
      'master info loads',
      'gallery is functional',
      'booking widget works',
      'contact info visible'
    ]
  },
  {
    name: 'Profile Dashboard',
    url: 'http://localhost:5173/dashboard',
    tests: [
      'tabs navigation works',
      'stats are displayed',
      'ads list loads'
    ]
  }
]

const runTest = async (scenario) => {
  const browser = await puppeteer.launch({ headless: false })
  const page = await browser.newPage()
  
  console.log(`\n🧪 Testing: ${scenario.name}`)
  
  try {
    await page.goto(scenario.url, { waitUntil: 'networkidle0' })
    
    // Проверить отсутствие ошибок консоли
    const errors = []
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text())
      }
    })
    
    // Ждать загрузки компонентов
    await page.waitForTimeout(2000)
    
    // Проверить наличие ключевых элементов
    const title = await page.title()
    console.log(`✅ Page title: ${title}`)
    
    if (errors.length > 0) {
      console.log(`❌ Console errors found: ${errors.length}`)
      errors.forEach(error => console.log(`   - ${error}`))
    } else {
      console.log(`✅ No console errors`)
    }
    
    // Скриншот для визуальной проверки
    await page.screenshot({ 
      path: `./test-screenshots/${scenario.name.replace(/\s+/g, '-').toLowerCase()}.png`,
      fullPage: true 
    })
    
    console.log(`✅ Screenshot saved`)
    
  } catch (error) {
    console.log(`❌ Test failed: ${error.message}`)
  }
  
  await browser.close()
}

// Создать директорию для скриншотов
const fs = require('fs')
if (!fs.existsSync('./test-screenshots')) {
  fs.mkdirSync('./test-screenshots')
}

// Запустить все тесты
const runAllTests = async () => {
  for (const scenario of testScenarios) {
    await runTest(scenario)
  }
  
  console.log('\n🎉 All tests completed!')
}

runAllTests()
```

#### 11.2 Performance аудит (2 часа)
```bash
# Установить Lighthouse CLI
npm install -g lighthouse

# Запустить аудит
lighthouse http://localhost:5173 --output json --output html --output-path ./lighthouse-report

# Анализ bundle size
npm run build -- --analyze
```

### **ДЕНЬ 12: Bug Fixes и Оптимизация**

#### 12.1 Исправление найденных проблем (4 часа)
Основываясь на результатах тестирования, исправить критические проблемы:

1. **JavaScript ошибки**
2. **CSS проблемы**
3. **Performance bottlenecks**
4. **Accessibility issues**

#### 12.2 Code splitting optimization (2 часа)
**Обновить:** `C:\www.spa.com\vite.config.js`
```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '@/src': resolve(__dirname, 'resources/js/src')
    }
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          // Vendor chunks
          'vendor-vue': ['vue', '@inertiajs/vue3'],
          'vendor-ui': ['@heroicons/vue'],
          
          // Feature chunks
          'feature-gallery': ['./resources/js/src/features/gallery'],
          'feature-map': ['./resources/js/src/features/map'],
          'feature-filters': ['./resources/js/src/features/masters-filter'],
          
          // Entity chunks
          'entity-master': ['./resources/js/src/entities/master'],
          'entity-booking': ['./resources/js/src/entities/booking'],
          'entity-ad': ['./resources/js/src/entities/ad']
        }
      }
    },
    chunkSizeWarningLimit: 1000
  }
})
```

### **ДЕНЬ 13: Final Integration**

#### 13.1 End-to-end тестирование (3 часа)
```bash
# Полный цикл тестирования
npm run build
php artisan serve &
npm run dev &

# Тестирование критических сценариев
```

#### 13.2 Performance финальный check (2 часа)
```bash
# Bundle analysis
npm run build -- --analyze

# Lighthouse final audit
lighthouse http://localhost:8000 --output html --output-path final-lighthouse-report.html

# Size analysis
du -sh public/build/*
```

---

## 🚀 ФАЗА 4: PRODUCTION ГОТОВНОСТЬ (Дни 14-15)

### **ДЕНЬ 14: Production Preparation**

#### 14.1 Environment конфигурация (2 часа)
```bash
# Проверить production сборку
NODE_ENV=production npm run build

# Оптимизация изображения
npm install --save-dev imagemin imagemin-webp
```

**Создать:** `C:\www.spa.com\optimize-images.js`
```javascript
const imagemin = require('imagemin')
const imageminWebP = require('imagemin-webp')

imagemin(['public/images/*.{jpg,png}'], {
  destination: 'public/images/webp',
  plugins: [
    imageminWebP({quality: 75})
  ]
}).then(() => {
  console.log('Images optimized!')
})
```

#### 14.2 Security audit (2 часа)
```bash
# NPM audit
npm audit

# Bundle security check
npm install --save-dev webpack-bundle-analyzer
```

#### 14.3 Final documentation (1 час)
**Создать:** `C:\www.spa.com\FRONTEND_ARCHITECTURE.md`
```markdown
# Frontend Architecture Guide

## FSD Structure
- shared/ - переиспользуемые компоненты
- entities/ - бизнес-сущности  
- features/ - функциональности
- widgets/ - композитные блоки
- pages/ - страницы приложения

## Key Components
- masters-filter/ - фильтрация мастеров
- gallery/ - просмотр изображений
- profile-navigation/ - навигация профиля

## Stores
- masters-filter.store.ts - состояние фильтров
- gallery.store.ts - состояние галереи  
- navigation.store.ts - навигация профиля
- bookingStore.ts - бронирования

## Performance
- Code splitting по features/entities
- Lazy loading изображений
- Bundle размер < 2MB
```

### **ДЕНЬ 15: Final Validation**

#### 15.1 Checklist validation (2 часа)
```bash
# Создать финальный чеклист
```

**Создать:** `C:\www.spa.com\final-checklist.md`
```markdown
# 🎯 FINAL REFACTORING CHECKLIST

## ✅ Architecture
- [x] FSD structure implemented
- [x] Pages migrated to FSD
- [x] Widgets created and integrated
- [x] Features with stores completed  
- [x] Entities properly structured

## ✅ Code Quality
- [x] console.log removed from production
- [x] alert() replaced with toast
- [x] TypeScript coverage 80%+
- [x] Error handling implemented
- [x] Accessibility attributes added

## ✅ Performance
- [x] Code splitting configured
- [x] Lazy loading implemented
- [x] Bundle size < 2MB
- [x] Lighthouse score 85+

## ✅ Integration
- [x] All pages working
- [x] Navigation functional
- [x] Filters working with store
- [x] Gallery integrated
- [x] No console errors

## ✅ Cleanup
- [x] Legacy components removed
- [x] Duplicate stores removed
- [x] Unused imports cleaned
- [x] File structure optimized
```

#### 15.2 Performance final test (2 hours)
```bash
# Final build and test
npm run build
php artisan optimize
php artisan serve

# Lighthouse final audit
lighthouse http://localhost:8000 --output html --view

# Bundle analysis
npm run build -- --analyze
```

---

## 📊 ФИНАЛЬНАЯ ВАЛИДАЦИЯ ПЛАН

### Критерии успеха:
1. **Lighthouse Score**: Performance 85+, Accessibility 95+
2. **Bundle Size**: < 2MB total
3. **Console Errors**: 0 в production
4. **TypeScript Coverage**: 80%+
5. **FSD Compliance**: 100% pages migrated

### Rollback Plan:
```bash
# В случае критических проблем
git checkout HEAD~10  # Откат к последнему стабильному коммиту
npm install
npm run build
```

### Success Metrics:
- ✅ **Архитектурная чистота**: 9.5/10
- ✅ **Code Quality**: 9.0/10  
- ✅ **Performance**: 8.5/10
- ✅ **Maintainability**: 9.5/10

---

## 🎉 ОЖИДАЕМЫЙ РЕЗУЛЬТАТ

По завершению 15-дневного плана:

1. **100% FSD архитектура** - полная миграция завершена
2. **Production-ready код** - без debug statements, с proper error handling
3. **Высокая производительность** - code splitting, lazy loading
4. **Maintainable codebase** - чистая архитектура, типизация
5. **Enterprise качество** - tests, documentation, accessibility

**Финальная оценка проекта: 9.2/10** 🚀

---

## 📞 КОНТАКТНАЯ ИНФОРМАЦИЯ

**Ответственный за выполнение плана:** Frontend Team Lead
**Срок выполнения:** 15 рабочих дней
**Приоритет:** Критический
**Бюджет времени:** 120 часов

**Еженедельные ретроспективы:**
- Конец недели 1: Дни 1-5 завершены
- Конец недели 2: Дни 6-10 завершены  
- Конец недели 3: Дни 11-15 завершены + Финальная валидация
