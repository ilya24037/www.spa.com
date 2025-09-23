<!-- ItemCard - карточка объявления в стиле Avito -->
<template>
  <div class="avito-item-snippet hover:shadow-lg transition-shadow">
    <div class="item-snippet-content">
      <!-- Изображение в стиле Ozon (кликабельное) -->
      <Link :href="itemUrl" class="item-image-container relative cursor-pointer">
        <ItemImage 
          :item="item"
          :item-url="itemUrl"
        />
      </Link>

      <!-- Основной контент (кликабельный) -->
      <Link :href="itemUrl" class="item-content-link cursor-pointer">
        <ItemContent 
          :item="item"
          :item-url="itemUrl"
        />
      </Link>

      <!-- Статистика и действия (НЕ кликабельные) -->
      <div class="item-info-section">
        <!-- Индикатор жалоб для админов -->
        <div v-if="($page.props.adminMode || $page.props.complaintsMode) && item.complaints_count"
             class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-red-600">⚠️</span>
              <span class="text-sm font-medium text-red-900">
                Жалоб: {{ item.complaints_count }}
              </span>
              <span v-if="item.has_unresolved_complaints"
                    class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">
                Неразрешенные
              </span>
            </div>
            <button
              @click="viewComplaints"
              class="px-3 py-1 text-sm bg-white border border-red-300 text-red-700 rounded hover:bg-red-50 transition-colors"
            >
              👁️ Посмотреть
            </button>
          </div>
        </div>

        <div class="item-info-top">
          <ItemStats :item="item" />
        </div>
        
        <!-- Действия на уровне низа фото -->
        <div class="item-actions-bottom">
          <ItemActions
            :item="item"
            @pay="payItem"
            @promote="promoteItem"
            @edit="editItem"
            @deactivate="deactivateItem"
            @delete="showDeleteModal = true"
            @mark-irrelevant="markIrrelevant"
            @book="bookItem"
            @restore="restoreItem"
            @publish="publishItem"
          />
        </div>

        <!-- Кнопки модерации для админов -->
        <div v-if="$page.props.moderationMode" class="flex gap-2 mt-4 pt-4 border-t">
          <button
            @click="approveItem"
            data-action="approve"
            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            ✅ Одобрить
          </button>
          <button
            @click="showRejectDialog = true"
            data-action="reject"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            ❌ Отклонить
          </button>
        </div>

        <!-- Кнопки управления пользователями -->
        <div v-if="$page.props.userManagementMode" class="flex gap-2 mt-4 pt-4 border-t">
          <button
            @click="toggleUserBlock"
            :class="[
              'flex-1 px-4 py-2 rounded-lg transition-colors',
              item.status === 'blocked'
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-yellow-600 text-white hover:bg-yellow-700'
            ]"
          >
            {{ item.status === 'blocked' ? '🔓 Разблокировать' : '🔒 Заблокировать' }}
          </button>
        </div>

        <!-- Кнопки обработки жалоб -->
        <div v-if="$page.props.complaintsMode" class="flex gap-2 mt-4 pt-4 border-t">
          <button
            @click="resolveComplaint('accept')"
            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            ✅ Оправдать
          </button>
          <button
            @click="resolveComplaint('block')"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            🚫 Заблокировать
          </button>
        </div>

        <!-- Кнопки управления мастерами -->
        <div v-if="$page.props.mastersMode" class="flex gap-2 mt-4 pt-4 border-t">
          <button
            @click="toggleMasterVerification"
            :class="[
              'flex-1 px-4 py-2 rounded-lg transition-colors',
              item.is_verified
                ? 'bg-yellow-600 text-white hover:bg-yellow-700'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            ]"
          >
            {{ item.is_verified ? '❌ Снять верификацию' : '✅ Верифицировать' }}
          </button>
        </div>

        <!-- Кнопки модерации отзывов -->
        <div v-if="$page.props.reviewsMode" class="flex gap-2 mt-4 pt-4 border-t">
          <button
            @click="approveReview"
            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            ✅ Одобрить отзыв
          </button>
          <button
            @click="deleteReview"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            🗑️ Удалить отзыв
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно подтверждения удаления -->
  <ConfirmModal
    v-model="showDeleteModal"
    title="Удалить объявление?"
    message="Это действие нельзя отменить. Объявление будет удалено навсегда."
    confirm-text="Удалить"
    cancel-text="Отмена"
    variant="danger"
    @confirm="deleteItem"
  />

  <!-- Диалог отклонения для модерации -->
  <div v-if="showRejectDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
      <h3 class="text-lg font-semibold mb-4">Причина отклонения</h3>
      <textarea
        v-model="rejectReason"
        class="w-full p-2 border rounded"
        rows="3"
        placeholder="Укажите причину..."
      ></textarea>
      <div class="flex gap-2 mt-4">
        <button @click="rejectItem" class="flex-1 px-4 py-2 bg-red-600 text-white rounded">
          Отклонить
        </button>
        <button @click="showRejectDialog = false" class="flex-1 px-4 py-2 bg-gray-200 rounded">
          Отмена
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import ItemImage from '@/src/shared/ui/molecules/ItemImage.vue'
import ItemContent from '@/src/shared/ui/molecules/ItemContent.vue'
import ItemStats from './components/ItemStats.vue'
import ItemActions from './components/ItemActions.vue'
import ConfirmModal from '@/src/shared/ui/molecules/Modal/ConfirmModal.vue'
import type { AdItem, ItemCardEmits } from './ItemCard.types'

interface Props {
  item: AdItem
}

const props = defineProps<Props>()
const emit = defineEmits<ItemCardEmits>()

// Состояние компонента
const showDeleteModal = ref(false)
const isArchiving = ref(false)
const showRejectDialog = ref(false)
const rejectReason = ref('')

// Вычисляемые свойства
const itemUrl = computed(() => {
  if (props.item.status === 'draft') {
    return `/draft/${props.item.id}`
  }
  return `/ads/${props.item.id}`
})

// Методы действий
const payItem = () => {
  router.visit(`/payment/select-plan?item_id=${props.item.id}`)
  emit('pay', props.item.id)
}

const promoteItem = () => {
  router.visit(`/payment/promotion?item_id=${props.item.id}`)
  emit('promote', props.item.id)
}

const editItem = () => {
  // Если админ редактирует чужое объявление
  if ($page.props.adminMode) {
    router.visit(`/profile/admin/ads/${props.item.id}/edit`)
  } else {
    // Для обычных пользователей - стандартный роут
    router.visit(`/ads/${props.item.id}/edit`)
  }
  emit('edit', props.item.id)
}

/**
 * Публикация объявления (повторная отправка на модерацию)
 * Для отклоненных и истекших объявлений
 */
const publishItem = () => {
  // Frontend валидация входных данных
  if (!props.item.id || typeof props.item.id !== 'number') {
    console.error('Некорректный ID объявления:', props.item.id)
    return
  }

  // Для rejected/expired - повторная отправка на модерацию
  if (['rejected', 'expired'].includes(props.item.status)) {
    router.post(`/ads/${props.item.id}/resubmit`, {}, {
      preserveState: false,
      preserveScroll: true,
      onSuccess: () => {
        emit('item-updated', props.item.id, { status: 'pending_moderation' })
        emit('publish', props.item.id)
      },
      onError: (errors) => {
        console.error('Ошибка при отправке на модерацию:', errors)
        emit('item-error', props.item.id, 'Не удалось отправить на модерацию')
      }
    })
  }
  // Для pending_moderation - уже на модерации
  else if (props.item.status === 'pending_moderation') {
    emit('item-error', props.item.id, 'Объявление уже находится на модерации')
  }
  // Для draft - публикация
  else if (props.item.status === 'draft') {
    router.post(`/draft/${props.item.id}/publish`, {}, {
      preserveState: false,
      preserveScroll: true,
      onSuccess: () => {
        emit('item-updated', props.item.id, { status: 'active' })
        emit('publish', props.item.id)
      },
      onError: (errors) => {
        console.error('Ошибка при публикации:', errors)
        emit('item-error', props.item.id, 'Не удалось опубликовать объявление')
      }
    })
  }
}

/**
 * Восстановление объявления из архива
 * Использует существующий backend endpoint и паттерн из deactivateItem
 * Принцип KISS: минимальные изменения, максимальная надежность
 */
const restoreItem = () => {
  // Frontend валидация входных данных (security by default из CLAUDE.md)
  if (!props.item.id || typeof props.item.id !== 'number') {
    console.error('Некорректный ID объявления:', props.item.id)
    return
  }
  
  // Проверяем что объявление действительно в архиве
  if (props.item.status !== 'archived') {
    console.warn(`Нельзя восстановить объявление со статусом: ${props.item.status}`)
    return
  }
  
  // API запрос с complete error handling (паттерн из deactivateItem)
  router.post(`/ads/${props.item.id}/restore`, {}, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      emit('item-updated', props.item.id, { status: 'active' })
      emit('restore', props.item.id)
    },
    onError: (errors) => {
      console.error('Ошибка API при восстановлении:', errors)
      emit('item-error', props.item.id, 'Не удалось восстановить объявление')
    }
  })
}

/**
 * Просмотр жалоб на объявление
 * Для админов и модераторов
 */
const viewComplaints = () => {
  // Переход на страницу жалоб для конкретного объявления
  router.visit(`/profile/complaints/ad/${props.item.id}`)
}

/**
 * Архивация объявления через API с полной валидацией
 * Реализует принцип KISS: простая логика с comprehensive error handling
 * Следует цепочке данных: Component → API → Backend → Database → UI Update
 */
const deactivateItem = () => {
  // Frontend валидация входных данных (security by default)
  if (!props.item.id || typeof props.item.id !== 'number') {
    console.error('Некорректный ID объявления:', props.item.id)
    return
  }
  
  // Проверяем бизнес-правила архивации (edge cases handling)
  if (!['active', 'draft'].includes(props.item.status)) {
    console.warn(`Нельзя архивировать объявление со статусом: ${props.item.status}`)
    return
  }
  
  // Предотвращаем повторные запросы (debouncing)
  if (isArchiving.value) {
    console.warn('Архивация уже выполняется, игнорируем повторный запрос')
    return
  }
  
  isArchiving.value = true
  
  // API запрос с complete error handling
  router.post(`/ads/${props.item.id}/archive`, {}, {
    preserveState: false,  // Обновляем состояние приложения
    preserveScroll: true,  // UX: сохраняем позицию скролла
    onSuccess: () => {
      isArchiving.value = false
      // Optimistic UI update - сразу обновляем интерфейс
      emit('item-updated', props.item.id, { status: 'archived' })
      emit('deactivate', props.item.id)
    },
    onError: (errors) => {
      isArchiving.value = false
      console.error('Ошибка API при архивации:', errors)
      // Production-ready error handling без debug alert
      emit('item-error', props.item.id, 'Не удалось переместить в архив')
    }
  })
}

const markIrrelevant = () => {
  router.post(`/ads/${props.item.id}/mark-irrelevant`, {}, {
    onSuccess: () => {
      emit('item-updated', props.item.id, { status: 'archived' })
      emit('mark-irrelevant', props.item.id)
    },
    onError: (errors) => {
      console.error('Ошибка при пометке как неактуальное:', errors)
      alert('Ошибка при обновлении статуса объявления')
    }
  })
}

const bookItem = () => {
  router.visit(`/ads/${props.item.id}?booking=true`)
  emit('book', props.item.id)
}

const deleteItem = () => {
  // Определяем правильный маршрут в зависимости от контекста
  const deleteUrl = props.item.status === 'draft' 
    ? `/profile/items/draft/${props.item.id}`
    : `/my-ads/${props.item.id}`
    
  router.delete(deleteUrl, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      emit('item-deleted', props.item.id)
      emit('delete', props.item.id)
    },
    onError: (errors) => {
      console.error('Ошибка при удалении:', errors)
      alert('Ошибка при удалении объявления')
    }
  })
}

// Методы модерации
const approveItem = () => {
  router.post(`/profile/moderation/${props.item.id}/approve`, {}, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      // Объявление исчезнет после перезагрузки страницы
      emit('item-approved', props.item.id)
    }
  })
}

const rejectItem = () => {
  router.post(`/profile/moderation/${props.item.id}/reject`, {
    reason: rejectReason.value
  }, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      showRejectDialog.value = false
      rejectReason.value = ''
      emit('item-rejected', props.item.id)
    }
  })
}

// Методы управления пользователями
const toggleUserBlock = () => {
  router.post(`/profile/users/${props.item.id}/toggle`, {}, {
    preserveState: false,
    preserveScroll: true
  })
}

// Методы обработки жалоб
const resolveComplaint = (action: string) => {
  router.post(`/profile/complaints/${props.item.id}/resolve`, { action }, {
    preserveState: false,
    preserveScroll: true
  })
}

// Методы управления мастерами
const toggleMasterVerification = () => {
  router.post(`/profile/masters/${props.item.id}/verify`, {}, {
    preserveState: false,
    preserveScroll: true
  })
}

// Методы модерации отзывов
const approveReview = () => {
  router.post(`/profile/reviews/${props.item.id}/moderate`, { action: 'approve' }, {
    preserveState: false,
    preserveScroll: true
  })
}

const deleteReview = () => {
  router.post(`/profile/reviews/${props.item.id}/moderate`, { action: 'delete' }, {
    preserveState: false,
    preserveScroll: true
  })
}
</script>

<style scoped>
/* Карточка в стиле Avito */
.avito-item-snippet {
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  padding: 16px;
  transition: all 0.2s ease;
}

.avito-item-snippet:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.item-snippet-content {
  display: grid;
  grid-template-columns: 200px 1fr auto;
  gap: 16px;
  align-items: start;
}

.item-image-container {
  display: block;
}

.item-content-link {
  display: block;
  text-decoration: none;
  color: inherit;
}

.item-content-link:hover {
  text-decoration: none;
}

.item-info-section {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 100%;
}

.item-info-top {
  margin-bottom: auto;
}

.item-actions-bottom {
  margin-top: auto;
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .item-snippet-content {
    grid-template-columns: 120px 1fr;
    gap: 12px;
  }
  
  .item-info-section {
    grid-column: 1 / -1;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
  }
  
  .item-info-top {
    margin-bottom: 0;
  }
  
  .item-actions-bottom {
    margin-top: 0;
  }
}
</style>