<!-- Dashboard - Личный кабинет в стиле Avito с полной FSD архитектурой -->
<template>
  <Head title="Мои объявления" />
  
  <!-- Обертка с правильными отступами как в Home.vue -->
  <div class="py-6 lg:py-8">
    
    <!-- Основной контент с гэпом между блоками -->
    <div class="flex gap-6">
      
      <!-- Боковая панель через SidebarWrapper -->
      <SidebarWrapper 
        v-model="showSidebar"
        content-class="p-0"
        :show-desktop-header="false"
        :always-visible-desktop="true"
      >
        <!-- Профиль пользователя -->
        <div class="p-6 border-b">
          <div class="flex items-center space-x-3">
            <div 
              class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
              :style="{ backgroundColor: avatarColor }"
            >
              {{ userInitial }}
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ userName }}</div>
              <div class="text-sm text-gray-500">★ {{ userStats.rating || 4.2 }} • {{ userStats.reviews_count || 5 }} отзывов</div>
            </div>
          </div>
        </div>
        
        <!-- Меню как на Авито -->
        <nav class="flex-1">
          <div class="py-2">
            <!-- Мои объявления (основная секция) -->
            <div class="px-4">
              <Link 
                href="/profile/items/inactive/all"
                :class="[
                  'flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
                  isAdsRoute ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-50'
                ]"
              >
                <span>Мои объявления</span>
                <span v-if="totalAds > 0" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ totalAds }}</span>
              </Link>
            </div>
            
            <!-- Остальные пункты меню -->
            <div class="px-4 mt-2 space-y-1">
              <Link 
                href="/bookings"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Заказы
              </Link>
              
              <Link 
                href="/profile/reviews"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Мои отзывы
              </Link>
              
              <Link 
                href="/favorites"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Избранное
              </Link>
              
              <Link 
                href="/messages"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Сообщения
              </Link>
              
              <Link 
                href="/notifications"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Уведомления
              </Link>
              
              <Link 
                href="/wallet"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Кошелёк
              </Link>
              
              <Link 
                href="/profile/addresses"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Адреса
              </Link>
              
              <Link 
                href="/profile/edit"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Управление профилем
              </Link>
              
              <Link 
                href="/profile/security"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Защита профиля
              </Link>
              
              <Link 
                href="/settings"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Настройки
              </Link>
              
              <Link
                href="/services"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                Платные услуги
              </Link>
            </div>

            <!-- Админское меню -->
            <div v-if="$page.props.auth?.user?.role === 'admin' || $page.props.auth?.user?.role === 'moderator'"
                 class="px-4 mt-4 pt-4 border-t space-y-1">
              <div class="px-3 mb-2">
                <span class="text-xs font-semibold text-gray-500 uppercase">
                  🛡️ Администрирование
                </span>
              </div>

              <a
                href="/admin/ads"
                class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                title="Модерация объявлений (новая админ-панель Filament)"
              >
                <span>📝 Модерация объявлений</span>
                <span v-if="$page.props.pendingCount" class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">
                  {{ $page.props.pendingCount }}
                </span>
              </a>

              <a
                href="/admin/ads"
                class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                :class="{ 'bg-gray-100': $page.url.includes('/admin/ads') }"
                title="Управление объявлениями (новая админ-панель Filament)"
              >
                <span>📋 Все объявления</span>
                <span v-if="props.stats?.all" class="bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded-full">
                  {{ props.stats.all }}
                </span>
              </a>

              <a
                href="/admin/reviews"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                title="Модерация отзывов (новая админ-панель Filament)"
              >
                <span>⭐ Модерация отзывов</span>
              </a>

              <a
                href="/admin/complaints"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                title="Система жалоб (новая админ-панель Filament)"
              >
                <span>⚠️ Жалобы</span>
              </a>

              <a
                v-if="$page.props.auth?.user?.role === 'admin'"
                href="/admin/users"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                title="Управление пользователями (новая админ-панель Filament)"
              >
                <span>👥 Пользователи</span>
              </a>

              <a
                v-if="$page.props.auth?.user?.role === 'admin'"
                href="/admin/master-profiles"
                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                title="Управление мастерами (новая админ-панель Filament)"
              >
                <span>💆 Мастера</span>
              </a>

              <!-- Кнопка перехода в полную админ-панель -->
              <div class="mt-4 px-3">
                <a
                  href="/admin"
                  target="_blank"
                  class="flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
                  title="Открыть полную админ-панель Filament"
                >
                  <svg class="w-4 h-4 mr-2"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                  </svg>
                  Полная админ-панель
                </a>
              </div>
            </div>
          </div>

          <!-- Статистика модерации (для админов) -->
          <div v-if="$page.props.auth?.user?.role === 'admin' && moderationStats" class="mt-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="text-sm font-semibold text-blue-900 mb-3">📊 Статистика модерации</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-blue-700">На модерации:</span>
                <span class="font-medium text-blue-900">{{ moderationStats.pending || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-blue-700">Обработано сегодня:</span>
                <span class="font-medium text-green-700">{{ moderationStats.processedToday || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-blue-700">Отклонено сегодня:</span>
                <span class="font-medium text-red-700">{{ moderationStats.rejectedToday || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-blue-700">Среднее время:</span>
                <span class="font-medium text-blue-900">{{ moderationStats.avgTime || '~15 мин' }}</span>
              </div>
            </div>

            <!-- Мини-график (опционально) -->
            <div v-if="moderationStats.weekData" class="mt-3 pt-3 border-t border-blue-200">
              <div class="flex items-end justify-between h-12 gap-1">
                <div
                  v-for="(count, day) in moderationStats.weekData"
                  :key="day"
                  class="flex-1 bg-blue-300 rounded-t transition-all hover:bg-blue-400"
                  :style="`height: ${Math.min(100, count * 5)}%`"
                  :title="`${day}: ${count}`"
                ></div>
              </div>
              <div class="text-xs text-blue-600 mt-1">Последние 7 дней</div>
            </div>
          </div>
        </nav>
      </SidebarWrapper>

      <!-- Контент справа -->
      <section class="flex-1 space-y-6">
        
        <!-- Заголовок без фона -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-4">Мои объявления</h1>
        </div>
        
        <!-- Основной контент - белая карточка как на главной -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="p-6">
            <!-- Навигация вкладок как на Авито -->
            <div v-if="!props.adminMode" class="flex items-center space-x-8">
              <Link
                href="/profile/items/inactive/all"
                :class="[
                  'pb-2 text-base font-medium border-b-2 transition-colors',
                  activeTab === 'inactive' 
                    ? 'text-gray-900 border-gray-900' 
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                ]"
              >
                <span class="flex items-center gap-2">
                  Ждут действий
                  <sup v-if="counts.waiting_payment" class="text-sm font-normal">{{ counts.waiting_payment }}</sup>
                </span>
              </Link>
              
              <Link 
                href="/profile/items/active/all"
                :class="[
                  'pb-2 text-base font-medium border-b-2 transition-colors',
                  activeTab === 'active' 
                    ? 'text-gray-900 border-gray-900' 
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                ]"
              >
                <span class="flex items-center gap-2">
                  Активные
                  <sup v-if="counts.active" class="text-sm font-normal">{{ counts.active }}</sup>
                </span>
              </Link>
              
              <Link 
                href="/profile/items/draft/all"
                :class="[
                  'pb-2 text-base font-medium border-b-2 transition-colors',
                  activeTab === 'draft' 
                    ? 'text-gray-900 border-gray-900' 
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                ]"
              >
                <span class="flex items-center gap-2">
                  Черновики
                  <sup v-if="counts.draft" class="text-sm font-normal">{{ counts.draft }}</sup>
                </span>
              </Link>
              
              <Link 
                href="/profile/items/archive/all"
                :class="[
                  'pb-2 text-base font-medium border-b-2 transition-colors',
                  activeTab === 'archive' 
                    ? 'text-gray-900 border-gray-900' 
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                ]"
              >
                <span class="flex items-center gap-2">
                  Архив
                  <sup v-if="counts.archive" class="text-sm font-normal">{{ counts.archive }}</sup>
                </span>
              </Link>
            </div>

            <!-- Админские табы -->
            <div v-if="props.adminMode" class="flex items-center space-x-8 overflow-x-auto">
              <a
                href="/admin/ads"
                class="pb-2 text-base font-medium border-b-2 transition-colors whitespace-nowrap text-gray-500 border-transparent hover:text-gray-700"
              >
                <span class="flex items-center gap-2">
                  Все (в админ-панели)
                  <sup v-if="props.stats?.all" class="text-sm font-normal">{{ props.stats.all }}</sup>
                </span>
              </a>

              <a
                href="/admin/ads"
                class="pb-2 text-base font-medium border-b-2 transition-colors whitespace-nowrap text-gray-500 border-transparent hover:text-gray-700"
              >
                <span class="flex items-center gap-2">
                  Активные (в админ-панели)
                  <sup v-if="props.stats?.active" class="text-sm font-normal">{{ props.stats.active }}</sup>
                </span>
              </a>

              <a
                href="/admin/ads"
                class="pb-2 text-base font-medium border-b-2 transition-colors whitespace-nowrap text-gray-500 border-transparent hover:text-gray-700"
              >
                <span class="flex items-center gap-2">
                  На модерации (в админ-панели)
                  <sup v-if="props.stats?.moderation" class="text-sm font-normal">{{ props.stats.moderation }}</sup>
                </span>
              </a>

              <a
                href="/admin/ads"
                class="pb-2 text-base font-medium border-b-2 transition-colors whitespace-nowrap text-gray-500 border-transparent hover:text-gray-700"
              >
                <span class="flex items-center gap-2">
                  Черновики (в админ-панели)
                  <sup v-if="props.stats?.draft" class="text-sm font-normal">{{ props.stats.draft }}</sup>
                </span>
              </a>

              <a
                href="/admin/ads"
                class="pb-2 text-base font-medium border-b-2 transition-colors whitespace-nowrap text-gray-500 border-transparent hover:text-gray-700"
              >
                <span class="flex items-center gap-2">
                  Отклоненные (в админ-панели)
                  <sup v-if="props.stats?.rejected" class="text-sm font-normal">{{ props.stats.rejected }}</sup>
                </span>
              </a>

              <a
                href="/admin/ads"
                class="pb-2 text-base font-medium border-b-2 transition-colors whitespace-nowrap text-gray-500 border-transparent hover:text-gray-700"
              >
                <span class="flex items-center gap-2">
                  Архив (в админ-панели)
                  <sup v-if="props.stats?.archived" class="text-sm font-normal">{{ props.stats.archived }}</sup>
                </span>
              </a>
            </div>
          </div>

          <!-- Панель массовых действий для админов -->
          <div v-if="props.adminMode && selectedItems.size > 0" class="px-6 py-4 bg-blue-50 border-b border-blue-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-blue-900">
                  Выбрано: {{ selectedItems.size }} {{ getItemsLabel(selectedItems.size) }}
                </span>
                <button
                  @click="clearSelection"
                  class="text-sm text-blue-600 hover:text-blue-700 underline"
                >
                  Снять выделение
                </button>
              </div>

              <div class="flex items-center gap-2">
                <button
                  @click="bulkAction('approve')"
                  :disabled="isBulkActionLoading"
                  class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  ✅ Одобрить
                </button>

                <button
                  @click="bulkAction('reject')"
                  :disabled="isBulkActionLoading"
                  class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  ❌ Отклонить
                </button>

                <button
                  @click="bulkAction('block')"
                  :disabled="isBulkActionLoading"
                  class="px-4 py-2 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  🚫 Заблокировать
                </button>

                <button
                  @click="bulkAction('archive')"
                  :disabled="isBulkActionLoading"
                  class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  📦 В архив
                </button>

                <button
                  @click="bulkAction('delete')"
                  :disabled="isBulkActionLoading"
                  class="px-4 py-2 bg-red-800 text-white text-sm rounded-md hover:bg-red-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  🗑️ Удалить
                </button>
              </div>
            </div>
          </div>

          <!-- Выбрать все для админов -->
          <div v-if="props.adminMode && profiles && profiles.length > 0" class="px-6 pt-4">
            <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600 transition-colors">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              />
              <span class="text-sm font-medium">
                Выбрать все на странице
              </span>
            </label>
          </div>

          <!-- Контент вкладки -->
          <div v-if="profiles && profiles.length > 0" class="space-y-6 p-6">
            <div
              v-for="profile in profiles"
              :key="profile.id"
              class="relative"
            >
              <!-- Чекбокс для админов -->
              <div v-if="props.adminMode" class="absolute top-4 left-4 z-10">
                <input
                  type="checkbox"
                  :checked="selectedItems.has(profile.id)"
                  @change="toggleItemSelection(profile.id)"
                  class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer"
                  :aria-label="`Выбрать объявление #${profile.id}`"
                />
              </div>

              <!-- Карточка пользователя для режима управления -->
              <div v-if="props.userManagementMode" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">{{ profile.title }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ profile.email }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                      <span>Роль: <span class="font-medium">{{ profile.role }}</span></span>
                      <span>Статус: <span class="font-medium" :class="profile.status === 'active' ? 'text-green-600' : 'text-red-600'">{{ profile.status }}</span></span>
                      <span v-if="profile.email_verified_at" class="text-green-600">✓ Email подтверждён</span>
                      <span v-else class="text-orange-600">⚠ Email не подтверждён</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="window.location.href = '/admin/users'"
                      class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
                      :class="profile.status === 'active' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700'"
                    >
                      {{ profile.status === 'active' ? 'Заблокировать' : 'Разблокировать' }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Стандартная карточка объявления -->
              <ItemCard
                v-else
                :item="profile"
                :key="`${profile.id}-${profile.status}`"
                :class="{ 'pl-12': props.adminMode }"
                @item-updated="handleItemUpdate"
                @item-deleted="handleItemDelete"
                @item-error="handleItemError"
                @pay="handleItemPay"
                @promote="handleItemPromote"
                @edit="handleItemEdit"
                @deactivate="handleItemDeactivate"
                @delete="handleItemDelete"
                @mark-irrelevant="handleItemMarkIrrelevant"
                @book="handleItemBook"
                @publish="handleItemPublish"
              />
            </div>
          </div>
          
          <!-- Пустое состояние как на Авито -->
          <div v-else class="text-center py-16">
            <div class="max-w-md mx-auto">
              <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <h3 class="text-xl font-medium text-gray-900 mb-3">{{ getEmptyStateTitle(activeTab) }}</h3>
              <p class="text-gray-600 mb-8 leading-relaxed">{{ getEmptyStateDescription(activeTab) }}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
  
  <!-- Глобальные уведомления -->
  <Toast
    v-for="toast in toasts"
    :key="toast.id"
    :message="toast.message"
    :type="toast.type"
    :duration="toast.duration"
    @close="removeToast(toast.id)"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'

// FSD imports
import { SidebarWrapper } from '@/src/shared/ui/layouts/SidebarWrapper'
import { ItemCard } from '@/src/entities/ad/ui/ItemCard'
import { Toast } from '@/src/shared/ui/molecules/Toast'
import type { AdItem } from '@/src/entities/ad/ui/ItemCard'
import type { ToastType } from '@/src/shared/ui/molecules/Toast'

// TypeScript интерфейсы
interface DashboardPageProps {
  profiles?: AdItem[]
  counts?: {
    active?: number
    draft?: number
    waiting_payment?: number
    old?: number
    archive?: number
    unreadMessages?: number
  }
  userStats?: {
    rating?: number
    reviews_count?: number
  }
  activeTab?: string
  title?: string
  moderationMode?: boolean
  pendingCount?: number
  isAdmin?: boolean
  adminMode?: boolean
  userManagementMode?: boolean
  stats?: Record<string, number>
  pagination?: {
    total?: number
    current?: number
    per_page?: number
  }
  moderationStats?: {
    pending?: number
    processedToday?: number
    rejectedToday?: number
    avgTime?: string
    weekData?: Record<string, number>
  }
}

interface ToastInstance {
  id: number
  message: string
  type: ToastType
  duration: number
}

// Props
const props = withDefaults(defineProps<DashboardPageProps>(), {
  profiles: () => [],
  counts: () => ({}),
  userStats: () => ({}),
  activeTab: 'active', // По умолчанию показываем активные
  title: 'Мои объявления',
  moderationStats: () => ({})
})

// Состояние
const showSidebar = ref(false)
const toasts = ref<ToastInstance[]>([])
const selectedItems = ref<Set<number>>(new Set())
const isBulkActionLoading = ref(false)

// Добавим реактивное свойство для статистики модерации
const moderationStats = computed(() => props.moderationStats || {})

// Пользователь
const page = usePage()
const user = computed(() => (page.props.auth as any)?.user || {})

// Вычисляемые свойства для пользователя
const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const avatarColor = computed(() => {
  const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
  const index = userName.value.charCodeAt(0) % colors.length
  return colors[index]
})

// Проверка текущего роута для объявлений
const isAdsRoute = computed(() => {
  const currentRoute = page.url
  return currentRoute.includes('/profile/items/')
})

// Общее количество объявлений
const totalAds = computed(() => {
  const counts = props.counts || {}
  return (counts.active || 0) + (counts.draft || 0) + (counts.waiting_payment || 0) + (counts.old || 0) + (counts.archive || 0)
})

// Функции для заголовков и описаний

const getEmptyStateTitle = (tab: string) => {
  const titles: Record<string, string> = {
    active: 'Нет активных объявлений',
    draft: 'Нет черновиков',
    inactive: 'Нет объявлений, ожидающих действий',
    old: 'Нет старых объявлений',
    archive: 'Архив пуст'
  }
  return titles[tab] || 'Нет объявлений'
}

const getEmptyStateDescription = (tab: string) => {
  const descriptions: Record<string, string> = {
    active: 'У вас пока нет активных объявлений. Разместите новое объявление, чтобы начать получать заказы.',
    draft: 'У вас нет сохраненных черновиков. Создайте новое объявление или сохраните текущее как черновик.',
    inactive: 'Здесь появятся объявления, которые требуют вашего внимания - например, истекающие или отклоненные.',
    old: 'Здесь будут показаны ваши старые неактивные объявления.',
    archive: 'Архивированные объявления не отображаются в поиске, но сохраняют всю информацию.'
  }
  return descriptions[tab] || 'Пока здесь пусто.'
}

// Обработчики событий
const handleItemUpdate = (itemId: number) => {
  addToast(`Объявление #${itemId} обновлено`, 'success')
  // Обновляем данные страницы для отображения изменений
  router.reload({
    only: ['profiles', 'counts'],
    preserveScroll: true
  } as any)
}

const handleItemDelete = (itemId: number) => {
  addToast(`Объявление #${itemId} удалено`, 'info')
  // Обновляем данные страницы после удаления
  router.reload({
    only: ['profiles', 'counts'],
    preserveScroll: true
  } as any)
}

/**
 * Production-ready обработка ошибок вместо alert()
 * Показывает Toast уведомление пользователю
 */
const handleItemError = (itemId: number, message: string) => {
  addToast(`Объявление #${itemId}: ${message}`, 'error')
}

const handleItemPay = (itemId: number) => {
  addToast(`Переход к оплате объявления #${itemId}`, 'info')
}

const handleItemPromote = (itemId: number) => {
  addToast(`Продвижение объявления #${itemId}`, 'info')
}

const handleItemEdit = (itemId: number) => {
  addToast(`Переход к редактированию объявления #${itemId}`, 'info')
}

const handleItemDeactivate = (itemId: number) => {
  addToast(`Объявление #${itemId} перемещено в архив`, 'success')
  // Обновляем список после деактивации
  router.reload({
    only: ['profiles', 'counts'],
    preserveScroll: true
  } as any)
}

const handleItemMarkIrrelevant = (itemId: number) => {
  addToast(`Объявление #${itemId} помечено как неактуальное`, 'info')
  // Обновляем список после изменения статуса
  router.reload({
    only: ['profiles', 'counts'],
    preserveScroll: true
  } as any)
}

const handleItemBook = (itemId: number) => {
  addToast(`Переход к бронированию объявления #${itemId}`, 'info')
}

const handleItemPublish = (itemId: number) => {
  addToast(`Объявление #${itemId} отправлено на модерацию`, 'success')
  // Обновляем страницу для отображения новых черновиков
  router.reload({
    only: ['profiles', 'counts'],
    preserveScroll: true
  } as any)
}

// Управление Toast уведомлениями
const addToast = (message: string, type: ToastType = 'success', duration = 5000) => {
  const id = Date.now()
  toasts.value.push({ id, message, type, duration })
}

const removeToast = (id: number) => {
  toasts.value = toasts.value.filter(toast => toast.id !== id)
}

// Методы для массовых действий
const toggleItemSelection = (itemId: number) => {
  if (selectedItems.value.has(itemId)) {
    selectedItems.value.delete(itemId)
  } else {
    selectedItems.value.add(itemId)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    clearSelection()
  } else {
    props.profiles?.forEach(profile => {
      selectedItems.value.add(profile.id)
    })
  }
}

const clearSelection = () => {
  selectedItems.value.clear()
}

const isAllSelected = computed(() => {
  if (!props.profiles || props.profiles.length === 0) return false
  return props.profiles.every(profile => selectedItems.value.has(profile.id))
})

const getItemsLabel = (count: number) => {
  if (count === 1) return 'объявление'
  if (count >= 2 && count <= 4) return 'объявления'
  return 'объявлений'
}

const bulkAction = async (action: string) => {
  if (selectedItems.value.size === 0) {
    addToast('Выберите объявления для действия', 'warning')
    return
  }

  const confirmMessages: Record<string, string> = {
    approve: `Одобрить ${selectedItems.value.size} ${getItemsLabel(selectedItems.value.size)}?`,
    reject: `Отклонить ${selectedItems.value.size} ${getItemsLabel(selectedItems.value.size)}?`,
    block: `Заблокировать ${selectedItems.value.size} ${getItemsLabel(selectedItems.value.size)}?`,
    archive: `Переместить в архив ${selectedItems.value.size} ${getItemsLabel(selectedItems.value.size)}?`,
    delete: `Удалить ${selectedItems.value.size} ${getItemsLabel(selectedItems.value.size)}? Это действие необратимо!`
  }

  if (!confirm(confirmMessages[action])) {
    return
  }

  isBulkActionLoading.value = true

  // Перенаправляем на новую админ-панель Filament для массовых действий
  window.location.href = '/admin/ads'
  return;

  /*router.post('/profile/admin/ads/bulk', {
    ids: Array.from(selectedItems.value),
    action: action
  }, {
    preserveScroll: true,
    preserveState: false,
    onStart: () => {
      isBulkActionLoading.value = true
    },
    onSuccess: (page) => {
      const flash = (page.props as any).flash
      addToast(flash?.success || `Действие выполнено успешно`, 'success')
      clearSelection()
      isBulkActionLoading.value = false
    },
    onError: (errors) => {
      console.error('Bulk action error:', errors)
      addToast('Ошибка при выполнении действия', 'error')
      isBulkActionLoading.value = false
    },
    onFinish: () => {
      isBulkActionLoading.value = false
    }
  })*/
}

// Горячие клавиши для модерации (только для админов)
import { onMounted, onUnmounted } from 'vue'

const setupHotkeys = () => {
  // Проверяем, что пользователь админ или модератор
  if (!user.value.role || !['admin', 'moderator'].includes(user.value.role)) {
    return
  }

  const handleKeyPress = (e: KeyboardEvent) => {
    // Игнорируем, если фокус в поле ввода
    if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
      return
    }

    // Проверяем режим модерации
    const isModerationMode = props.moderationMode || page.url.includes('/moderation')
    if (!isModerationMode) {
      return
    }

    // Горячие клавиши
    switch (e.key.toLowerCase()) {
      case 'a': // Approve
        e.preventDefault()
        const approveBtn = document.querySelector('[data-action="approve"]') as HTMLElement
        if (approveBtn) {
          approveBtn.click()
          addToast('✅ Объявление одобрено (клавиша A)', 'success')
        }
        break

      case 'r': // Reject
        e.preventDefault()
        const rejectBtn = document.querySelector('[data-action="reject"]') as HTMLElement
        if (rejectBtn) {
          rejectBtn.click()
          addToast('❌ Объявление отклонено (клавиша R)', 'error')
        }
        break

      case 'n': // Next
        e.preventDefault()
        const nextItem = document.querySelector('[data-action="next"]') as HTMLElement
        if (nextItem) {
          nextItem.click()
        } else {
          // Переход к следующему объявлению
          const currentIndex = props.profiles?.findIndex(p => p.id === (window as any).currentItemId) ?? -1
          if (currentIndex >= 0 && currentIndex < (props.profiles?.length ?? 0) - 1) {
            const nextProfile = props.profiles?.[currentIndex + 1]
            if (nextProfile) {
              ;(window as any).currentItemId = nextProfile.id
              addToast(`➡️ Переход к объявлению #${nextProfile.id} (клавиша N)`, 'info')
            }
          }
        }
        break

      case 'escape': // Закрыть модальное окно
        e.preventDefault()
        const modal = document.querySelector('[data-modal="active"]') as HTMLElement
        if (modal) {
          const closeBtn = modal.querySelector('[data-action="close"]') as HTMLElement
          if (closeBtn) {
            closeBtn.click()
          }
        }
        break

      case '?': // Показать подсказку
        e.preventDefault()
        addToast('⌨️ Горячие клавиши: A - одобрить, R - отклонить, N - следующее, ESC - закрыть', 'info', 8000)
        break
    }
  }

  window.addEventListener('keydown', handleKeyPress)

  // Cleanup при размонтировании компонента
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyPress)
  })
}

// Инициализация горячих клавиш при монтировании
onMounted(() => {
  setupHotkeys()

  // Показываем подсказку админу при первом входе
  if (user.value.role === 'admin' && props.moderationMode) {
    setTimeout(() => {
      addToast('💡 Используйте горячие клавиши для быстрой модерации. Нажмите ? для справки', 'info', 7000)
    }, 2000)
  }
})
</script>

<style scoped>
/* Стили специфичные для дашборда */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

/* Анимации для статистики */
.stat-card {
  transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-2px);
}
</style>