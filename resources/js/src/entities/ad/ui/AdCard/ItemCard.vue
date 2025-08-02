<!--
  Рефакторированный компонент карточки услуги в стиле Avito
  Разбит на переиспользуемые подкомпоненты для лучшей maintainability
  С изображением в стиле Ozon
-->
<template>
  <div class="avito-item-snippet hover:shadow-lg transition-shadow" @click="handleContainerClick">
    <div class="item-snippet-content">
      <!-- Изображение в стиле Ozon -->
      <Link 
        :href="itemUrl" 
        class="item-image-container relative cursor-pointer"
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
    />
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ItemImage from '../Cards/ItemImage.vue'
import ItemContent from '../Cards/ItemContent.vue'
import ItemStats from '../Cards/ItemStats.vue'
import ItemActions from '../Cards/ItemActions.vue'
import ConfirmModal from '../UI/ConfirmModal.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['item-updated', 'item-deleted'])

// Состояние компонента
const showDeleteModal = ref(false)

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
}

const promoteItem = () => {
  router.visit(`/payment/promotion?item_id=${props.item.id}`)
}

const editItem = () => {
  console.log('=== EDIT ITEM CALLED ===')
  console.log('Item ID:', props.item.id)
  console.log('Item status:', props.item.status)
  console.log('Modal open:', showDeleteModal.value)
  
  // Если модальное окно открыто, НЕ редактируем
  if (showDeleteModal.value) {
    console.log('Blocking edit - delete modal is open')
    return
  }
  
  // Для всех объявлений (включая черновики) используем один роут
  console.log('Navigating to edit page...')
  router.visit(`/ads/${props.item.id}/edit`)
}

const deactivateItem = async () => {
  try {
    // Используем правильный роут через router
    router.post(`/my-ads/${props.item.id}/deactivate`, {}, {
      preserveState: true,
      onSuccess: () => {
        emit('item-updated', { ...props.item, status: 'archived' })
      },
      onError: (errors) => {
        console.error('Ошибка при деактивации:', errors)
      }
    })
  } catch (error) {
    console.error('Ошибка при деактивации:', error)
  }
}

const restoreItem = async () => {
  try {
    // Используем правильный роут через router
    router.post(`/my-ads/${props.item.id}/restore`, {}, {
      preserveState: true,
      onSuccess: () => {
        emit('item-updated', { ...props.item, status: 'active' })
      },
      onError: (errors) => {
        console.error('Ошибка при восстановлении:', errors)
      }
    })
  } catch (error) {
    console.error('Ошибка при восстановлении:', error)
  }
}

const handleContainerClick = (event) => {
  console.log('Container clicked')
  console.log('Target:', event.target)
  console.log('Current target:', event.currentTarget)
}

const handleDeleteClick = (event) => {
  console.log('=== DELETE CLICKED IN ITEMCARD ===')
  console.log('Item status:', props.item.status)
  console.log('Item ID:', props.item.id)
  console.log('Current URL:', window.location.href)
  
  // Безопасно останавливаем всплытие события чтобы не сработал Link
  if (event && typeof event.stopPropagation === 'function') {
    event.stopPropagation()
  }
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault()
  }
  
  console.log('Opening delete modal immediately...')
  showDeleteModal.value = true
}

const deleteItem = async () => {
  try {
    console.log('=== DELETING ITEM ===')
    console.log('Item ID:', props.item.id)
    console.log('Item status:', props.item.status)
    
    // Выбираем правильный роут в зависимости от типа объявления
    const deleteUrl = props.item.status === 'draft' 
      ? `/draft/${props.item.id}` 
      : `/my-ads/${props.item.id}`
    
    console.log('Delete URL:', deleteUrl)
    
    // Используем правильный роут через router для удаления
    router.delete(deleteUrl, {
      preserveScroll: false,
      preserveState: false,
      onStart: () => {
        console.log('Delete request started')
      },
      onSuccess: (page) => {
        console.log('=== DELETE SUCCESSFUL ===')
        console.log('Redirected to:', page.url)
        console.log('Page props:', page.props)
        
        // Эмитим событие для обновления списка
        emit('item-deleted', props.item.id)
        showDeleteModal.value = false
      },
      onError: (errors) => {
        console.error('=== DELETE FAILED ===')
        console.error('Delete failed with errors:', errors)
        alert('Ошибка удаления: ' + (errors.message || JSON.stringify(errors)))
        showDeleteModal.value = false
      },
      onFinish: () => {
        console.log('Delete request finished')
      }
    })
  } catch (error) {
    console.error('Ошибка при удалении:', error)
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