<template>
  <div class="py-6 lg:py-8">
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="content"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="4"
    />
    
    <!-- Основной контент -->
    <div v-else>
      <!-- Заголовок страницы -->
      <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-500 mb-2">
          Мои записи
        </h1>
        <p class="text-gray-500">
          Управление вашими записями к мастерам
        </p>
      </div>

      <!-- Фильтры и статистика -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Статистика -->
        <div class="bg-white rounded-lg p-4 shadow-sm border">
          <div class="text-sm text-gray-500 mb-1">
            Всего записей
          </div>
          <div class="text-2xl font-bold text-gray-500">
            {{ totalBookings }}
          </div>
        </div>
      
        <div class="bg-white rounded-lg p-4 shadow-sm border">
          <div class="text-sm text-gray-500 mb-1">
            Предстоящие
          </div>
          <div class="text-2xl font-bold text-blue-600">
            {{ upcomingBookings }}
          </div>
        </div>
      
        <div class="bg-white rounded-lg p-4 shadow-sm border">
          <div class="text-sm text-gray-500 mb-1">
            Завершенные
          </div>
          <div class="text-2xl font-bold text-green-600">
            {{ completedBookings }}
          </div>
        </div>
      
        <div class="bg-white rounded-lg p-4 shadow-sm border">
          <div class="text-sm text-gray-500 mb-1">
            Отмененные
          </div>
          <div class="text-2xl font-bold text-red-600">
            {{ cancelledBookings }}
          </div>
        </div>
      </div>

      <!-- Вкладки фильтров -->
      <div class="mb-6">
        <div class="border-b border-gray-500">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                activeTab === tab.key
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-500 hover:border-gray-500'
              ]"
              @click="activeTab = tab.key"
            >
              {{ tab.label }}
              <span
                v-if="tab.count !== undefined"
                :class="[
                  'ml-2 px-2 py-0.5 rounded-full text-xs',
                  activeTab === tab.key ? 'bg-blue-100 text-blue-600' : 'bg-gray-500 text-gray-500'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Список бронирований -->
      <div class="space-y-4">
        <!-- Состояние загрузки -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="filteredBookings.length === 0" class="text-center py-12">
          <svg
            class="mx-auto h-12 w-12 text-gray-500 mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <h3 class="text-lg font-medium text-gray-500 mb-2">
            Нет записей
          </h3>
          <p class="text-gray-500 mb-4">
            У вас пока нет записей в этой категории
          </p>
          <router-link
            to="/masters"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Найти мастера
          </router-link>
        </div>

        <!-- Карточки бронирований -->
        <div v-else>
          <BookingStatus
            v-for="booking in filteredBookings"
            :key="booking.id"
            :booking="booking"
            :show-quick-actions="true"
            :user-role="userRole"
            class="mb-4"
            @cancel="handleCancelBooking"
            @reschedule="handleRescheduleBooking"
            @complete="handleCompleteBooking"
          />

          <!-- Пагинация -->
          <div v-if="pagination && pagination.total > pagination.per_page" class="mt-8">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <button
                  :disabled="!pagination.prev_page_url"
                  class="relative inline-flex items-center px-4 py-2 border border-gray-500 text-sm font-medium rounded-md text-gray-500 bg-white hover:bg-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="loadPage(pagination.current_page - 1)"
                >
                  Предыдущая
                </button>
                <button
                  :disabled="!pagination.next_page_url"
                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-500 text-sm font-medium rounded-md text-gray-500 bg-white hover:bg-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="loadPage(pagination.current_page + 1)"
                >
                  Следующая
                </button>
              </div>
            
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-500">
                    Показано <span class="font-medium">{{ pagination.from }}</span> до 
                    <span class="font-medium">{{ pagination.to }}</span> из 
                    <span class="font-medium">{{ pagination.total }}</span> записей
                  </p>
                </div>
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <!-- Пагинация для больших экранов -->
                    <button
                      v-for="page in visiblePages"
                      :key="page"
                      :class="[
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors',
                        page === pagination.current_page
                          ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                          : 'bg-white border-gray-500 text-gray-500 hover:bg-gray-500'
                      ]"
                      @click="loadPage(page)"
                    >
                      {{ page }}
                    </button>
                  </nav>
                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно переноса записи -->
    <div v-if="showRescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
          <h2 class="text-xl font-bold text-gray-500">
            Перенести запись
          </h2>
          <button class="text-gray-500 hover:text-gray-500" @click="showRescheduleModal = false">
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        
        <div class="p-6">
          <BookingCalendar
            v-if="rescheduleBooking"
            :master-id="rescheduleBooking.masterId"
            :selected-service="rescheduleBooking.service"
            @selection-change="handleNewTimeSelection"
          />
          
          <div v-if="newDateTime" class="mt-6 flex gap-3">
            <button
              class="flex-1 bg-gray-500 text-gray-500 py-2 px-4 rounded-lg font-medium hover:bg-gray-500 transition-colors"
              @click="showRescheduleModal = false"
            >
              Отменить
            </button>
            <button
              :disabled="rescheduling"
              class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
              @click="confirmReschedule"
            >
              {{ rescheduling ? 'Переношу...' : 'Перенести запись' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { route } from 'ziggy-js'

import { logger } from '@/src/shared/lib/logger'
import { computed, onMounted, watch, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import BookingStatus from '@/src/entities/booking/ui/BookingStatus/BookingStatus.vue'
import BookingCalendar from '@/src/entities/booking/ui/BookingCalendar/BookingCalendar.vue'
import { useBookingStore } from '@/src/entities/booking/model/bookingStore'
import { useToast } from '@/src/shared/composables/useToast'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация данных
interface BookingData {
  id: number | string
  status: string
  masterId?: number
  service?: any
  startTime?: string
  cancellationReason?: string
  [key: string]: any
}

interface BookingPagination {
  data: BookingData[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  prev_page_url?: string
  next_page_url?: string
  meta?: any
}

interface BookingsIndexProps {
  bookings: BookingPagination
  isMaster?: boolean
}

// Toast для замены (window as any).// Removed // Removed // Removed alert() - use toast notifications instead - use toast notifications instead - use toast notifications instead
const toast = useToast()

// Props от Inertia с типизацией
const _props: any = withDefaults(defineProps<BookingsIndexProps>(), {
    isMaster: false
})

// Управление загрузкой страницы
const pageLoader = usePageLoading({
    type: 'content',
    autoStart: true,
    timeout: 8000,
    onStart: () => {
    // Bookings page loading started
    },
    onComplete: () => {
    // Bookings page loading completed
    },
    onError: (error) => {
        logger.error('Bookings page loading error:', error)
    }
})

// Store
const bookingStore = useBookingStore()

// Состояние компонента
const activeTab = ref<string>('all')
const loading = ref<boolean>(false)
const showRescheduleModal = ref<boolean>(false)
const rescheduleBooking = ref<BookingData | null>(null)
const newDateTime = ref<string | null>(null)
const rescheduling = ref<boolean>(false)

// Данные
const allBookings = ref<BookingData[]>(_props.bookings.data || [])
const pagination = ref<BookingPagination | any>(_props.bookings.meta || _props.bookings)

// Вычисляемые свойства
const userRole = computed(() => _props.isMaster ? 'master' : 'client')

const totalBookings = computed(() => allBookings.value.length)

const upcomingBookings = computed(() => 
    allBookings.value.filter(b => ['pending', 'confirmed'].includes(b.status)).length
)

const completedBookings = computed(() => 
    allBookings.value.filter(b => b.status === 'completed').length
)

const cancelledBookings = computed(() => 
    allBookings.value.filter(b => b.status === 'cancelled').length
)

const tabs = computed(() => [
    { key: 'all', label: 'Все записи', count: totalBookings.value },
    { key: 'upcoming', label: 'Предстоящие', count: upcomingBookings.value },
    { key: 'completed', label: 'Завершенные', count: completedBookings.value },
    { key: 'cancelled', label: 'Отмененные', count: cancelledBookings.value }
])

const filteredBookings = computed(() => {
    switch (activeTab.value) {
    case 'upcoming':
        return allBookings.value.filter(b => ['pending', 'confirmed', 'in_progress'].includes(b.status))
    case 'completed':
        return allBookings.value.filter(b => b.status === 'completed')
    case 'cancelled':
        return allBookings.value.filter(b => b.status === 'cancelled')
    default:
        return allBookings.value
    }
})

const visiblePages = computed(() => {
    if (!pagination.value) return []
  
    const current = pagination.value.current_page
    const total = pagination.value.last_page
    const delta = 2
  
    const range = []
    const start = Math.max(1, current - delta)
    const end = Math.min(total, current + delta)
  
    for (let i = start; i <= end; i++) {
        range.push(i)
    }
  
    return range
})

// Методы
const loadPage = async (page: any) => {
    if (loading.value || page === pagination.value.current_page) return
  
    loading.value = true
  
    try {
        router.get(route('bookings.index'), { page }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                allBookings.value = (page.props as any).bookings.data
                pagination.value = (page.props as any).bookings.meta || (page.props as any).bookings
            }
        })
    } finally {
        loading.value = false
    }
}

const handleCancelBooking = async ({ bookingId, reason }: { bookingId: number, reason: string }) => {
    try {
        const result = await bookingStore.cancelBooking(bookingId, reason)
    
        if (result) {
            // Обновляем локальные данные
            const bookingIndex = allBookings.value.findIndex(b => b.id === bookingId)
            if (bookingIndex !== -1) {
        allBookings.value[bookingIndex]!.status = 'cancelled'
        allBookings.value[bookingIndex]!.cancellationReason = reason
            }
      
            // Показываем уведомление
            toast.success('Запись успешно отменена')
        }
    } catch (error: any) {
        toast.error('Ошибка при отмене записи: ' + (error as any).message)
    }
}

const handleRescheduleBooking = (bookingId: any) => {
    const booking = allBookings.value.find(b => b.id === bookingId)
    if (booking) {
        rescheduleBooking.value = booking
        showRescheduleModal.value = true
        newDateTime.value = null
    }
}

const handleCompleteBooking = async (bookingId: any) => {
    if (!(window as any).confirm('Отметить запись как завершенную?')) return
  
    try {
    // API вызов для завершения записи
        const bookingIndex = allBookings.value.findIndex(b => b.id === bookingId)
        if (bookingIndex !== -1) {
      allBookings.value[bookingIndex]!.status = 'completed'
        }
    
        toast.success('Запись отмечена как завершенная')
    } catch (error: any) {
        toast.error('Ошибка при завершении записи: ' + (error as any).message)
    }
}

const handleNewTimeSelection = (selection: any) => {
    newDateTime.value = selection.datetime
}

const confirmReschedule = async () => {
    if (!newDateTime.value || !rescheduleBooking.value) return
  
    rescheduling.value = true
  
    try {
    // API вызов для переноса записи
        const bookingIndex = allBookings.value.findIndex(b => b.id === rescheduleBooking.value!.id)
        if (bookingIndex !== -1) {
      allBookings.value[bookingIndex]!.startTime = newDateTime.value
      allBookings.value[bookingIndex]!.status = 'rescheduled'
        }
    
        showRescheduleModal.value = false
        rescheduleBooking.value = null
        newDateTime.value = null
    
        toast.success('Запись успешно перенесена')
    } catch (error: any) {
        toast.error('Ошибка при переносе записи: ' + (error as any).message)
    } finally {
        rescheduling.value = false
    }
}

// Инициализация
onMounted(() => {
    // Проверяем наличие данных
    if (!_props.bookings?.data) {
        const noDataError = {
            type: 'client' as const,
            message: 'Данные записей не найдены',
            code: 404
        }
        pageLoader.errorLoading(noDataError)
        return
    }

    // Поэтапная загрузка данных
    setTimeout(() => {
        pageLoader.setProgress(30, 'Загружаем записи...')
    }, 200)

    setTimeout(() => {
        pageLoader.setProgress(60, 'Обрабатываем статистику...')
    }, 600)

    setTimeout(() => {
        pageLoader.setProgress(85, 'Подготавливаем интерфейс...')
    }, 1000)

    setTimeout(() => {
        pageLoader.completeLoading()
    }, 1400)

    // Устанавливаем активную вкладку из URL параметра если есть
    const urlParams = new URLSearchParams((window as any).location.search)
    const tabParam = urlParams.get('tab')
    if (tabParam && tabs.value.some(t => t.key === tabParam)) {
        activeTab.value = tabParam
    }
})

// Наблюдатели
watch(activeTab, (newTab) => {
    // Обновляем URL при смене вкладки
    const url = new URL((window as any).location.href);
    url.searchParams.set('tab', newTab);
    (window as any).history.replaceState({}, '', url)
})
</script>

<style scoped>
/* Анимации */
.booking-status {
  transition: all 0.2s ease-in-out;
}

/* Стилизация загрузки */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
