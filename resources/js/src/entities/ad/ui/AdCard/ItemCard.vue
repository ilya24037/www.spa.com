<!--
  Рефакторированный компонент карточки услуги в стиле Avito
  Разбит на переиспользуемые подкомпоненты для лучшей maintainability
  С изображением в стиле Ozon
-->
<template>
  <div 
    class="avito-item-snippet hover:shadow-lg transition-shadow" 
    @click="handleContainerClick"
    role="article"
    :aria-label="`Объявление: ${props.item.title || props.item.name || props.item.display_name}`"
    data-testid="item-card"
  >
    <div class="item-snippet-content">
      <!-- Изображение в стиле Ozon -->
      <Link 
        :href="itemUrl" 
        class="item-image-container relative cursor-pointer"
        :aria-label="`Посмотреть объявление ${props.item.title || props.item.name}`"
        data-testid="item-image-link"
      >
        <ItemImage 
          :item="item"
          :itemUrl="itemUrl"
        />
      </Link>

      <!-- Основной контент -->
      <Link 
        :href="itemUrl" 
        class="item-content-link cursor-pointer"
        :aria-label="`Открыть объявление ${props.item.title || props.item.name}`"
        data-testid="item-content-link"
      >
        <ItemContent 
          :item="item"
          :itemUrl="itemUrl"
        />
      </Link>

      <!-- Статистика и действия (НЕ кликабельные) -->
      <div class="item-info-section">
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
            @delete="handleDeleteClick"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно подтверждения удаления -->
  <ConfirmModal
      :show="showDeleteModal"
      title="Удалить объявление?"
      message="Это действие нельзя отменить. Объявление будет удалено навсегда."
      confirmText="Удалить"
      cancelText="Отмена"
      @confirm="deleteItem"
      @cancel="showDeleteModal = false"
      data-testid="delete-modal"
    />
</template>

<script setup lang="ts">
import { ref, computed, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ItemImage from '../Cards/ItemImage.vue'
import ItemContent from '../Cards/ItemContent.vue'
import ItemStats from '../Cards/ItemStats.vue'
import ItemActions from '../Cards/ItemActions.vue'
import ConfirmModal from '../UI/ConfirmModal.vue'
import { Link } from '@inertiajs/vue3'
import { useToast } from '@/src/shared/composables/useToast'
import type { 
  ItemCardProps, 
  ItemCardEmits, 
  ItemCardState,
  ClickEvent,
  ItemActionResponse,
  ApiError 
} from './ItemCard.types'

// Toast для замены alert()
const toast = useToast()

// Props
const props = defineProps<ItemCardProps>()

// Emits  
const emit = defineEmits<ItemCardEmits>()

// Состояние компонента
const showDeleteModal: Ref<boolean> = ref(false)

// Вычисляемые свойства
const itemUrl = computed((): string => {
  if (props.item.status === 'draft') {
    return `/draft/${props.item.id}`
  }
  return `/ads/${props.item.id}`
})

// Методы действий
const payItem = (): void => {
  try {
    router.visit(`/payment/select-plan?item_id=${props.item.id}`)
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка оплаты: ' + errorMessage)
  }
}

const promoteItem = (): void => {
  try {
    router.visit(`/payment/promotion?item_id=${props.item.id}`)
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка продвижения: ' + errorMessage)
  }
}

const editItem = (): void => {
  try {
    
    // Если модальное окно открыто, НЕ редактируем
    if (showDeleteModal.value) {
      return
    }
    
    // Для всех объявлений (включая черновики) используем один роут
    router.visit(`/ads/${props.item.id}/edit`)
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка редактирования: ' + errorMessage)
  }
}

const deactivateItem = async (): Promise<void> => {
  try {
    // Используем правильный роут через router
    await router.post(`/my-ads/${props.item.id}/deactivate`, {}, {
      preserveState: true,
      onSuccess: () => {
        const updatedItem = { ...props.item, status: 'archived' as const }
        emit('item-updated', updatedItem)
        toast.success('Объявление деактивировано')
      },
      onError: (errors) => {
        const errorMessage = typeof errors === 'string' ? errors : 'Ошибка деактивации'
        toast.error(errorMessage)
      }
    })
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка деактивации: ' + errorMessage)
  }
}

const restoreItem = async (): Promise<void> => {
  try {
    // Используем правильный роут через router
    await router.post(`/my-ads/${props.item.id}/restore`, {}, {
      preserveState: true,
      onSuccess: () => {
        const updatedItem = { ...props.item, status: 'active' as const }
        emit('item-updated', updatedItem)
        toast.success('Объявление восстановлено')
      },
      onError: (errors) => {
        const errorMessage = typeof errors === 'string' ? errors : 'Ошибка восстановления'
        toast.error(errorMessage)
      }
    })
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка восстановления: ' + errorMessage)
  }
}

const handleContainerClick = (event: ClickEvent): void => {
}

const handleDeleteClick = (event: ClickEvent): void => {
  
  // Безопасно останавливаем всплытие события чтобы не сработал Link
  if (event && typeof event.stopPropagation === 'function') {
    event.stopPropagation()
  }
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault()
  }
  
  showDeleteModal.value = true
}

const deleteItem = async (): Promise<void> => {
  try {
    
    // Выбираем правильный роут в зависимости от типа объявления
    const deleteUrl = props.item.status === 'draft' 
      ? `/draft/${props.item.id}` 
      : `/my-ads/${props.item.id}`
    
    
    // Используем правильный роут через router для удаления
    await router.delete(deleteUrl, {
      preserveScroll: false,
      preserveState: false,
      onStart: () => {
      },
      onSuccess: (page) => {
        
        // Эмитим событие для обновления списка
        emit('item-deleted', props.item.id)
        showDeleteModal.value = false
        toast.success('Объявление удалено')
      },
      onError: (errors) => {
        
        const errorMessage = typeof errors === 'object' && errors !== null && 'message' in errors
          ? String(errors.message)
          : 'Ошибка удаления объявления'
        
        toast.error(errorMessage)
        showDeleteModal.value = false
      },
      onFinish: () => {
      }
    })
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка удаления: ' + errorMessage)
    showDeleteModal.value = false
  }
}
</script>

<style scoped>
.avito-item-snippet {
  @apply bg-white border border-gray-200 mb-4 relative;
  border-radius: 16px; /* Как на Ozon */
  padding: 0; /* Убираем padding как на Ozon */
  height: fit-content; /* Строго по контенту */
  overflow: visible; /* Изменяем на visible для dropdown */
}

.item-snippet-content {
  @apply flex;
  align-items: flex-start;
  height: 256px; /* Фото + равномерные отступы 12px */
  padding: 12px; /* Равномерные отступы 12px со всех сторон */
  gap: 12px; /* Уменьшаем gap */
}

.item-image-container {
  @apply relative;
  @apply overflow-hidden; /* Как на Ozon - обрезаем */
  width: 160px; /* Ширина как на Ozon */
  height: 232px; /* Точно по размеру без лишних отступов */
  border-radius: 12px; /* Скругление фото */
  flex-shrink: 0;
  display: block; /* Для корректного отображения Link */
}

.item-content-link {
  display: block; /* Для корректного отображения Link */
  flex: 1; /* Занимает оставшееся место */
}

.item-info-section {
  @apply flex flex-col justify-between flex-shrink-0;
  width: 200px; /* Оптимальная ширина правой колонки */
  height: 232px; /* Высота контента с отступами 12px */
}

/* Кнопки с большим отступом снизу */
.item-actions-bottom {
  margin-bottom: 30px; /* Еще больший отступ снизу */
  position: relative; /* Для корректного позиционирования dropdown */
}

/* Responsive */
@media (max-width: 768px) {
  .item-snippet-content {
    @apply flex-col gap-3;
  }
  
  .item-info-section {
    @apply w-full;
  }
}
</style>