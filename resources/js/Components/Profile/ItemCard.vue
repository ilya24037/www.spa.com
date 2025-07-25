<!--
  Рефакторированный компонент карточки услуги в стиле Avito
  Разбит на переиспользуемые подкомпоненты для лучшей maintainability
  С изображением в стиле Ozon
-->
<template>
  <Link :href="itemUrl" class="block">
    <div class="avito-item-snippet cursor-pointer hover:shadow-lg transition-shadow">
      <div class="item-snippet-content">
        <!-- Изображение в стиле Ozon -->
        <div class="item-image-container relative">
          <ItemImage 
            :item="item"
            :itemUrl="itemUrl"
          />
        </div>

        <!-- Основной контент -->
        <ItemContent 
          :item="item"
          :itemUrl="itemUrl"
        />

        <!-- Статистика и действия -->
        <div class="item-info-section">
          <div class="item-info-top">
            <ItemStats :item="item" />
          </div>
          
          <!-- Действия на уровне низа фото -->
          <div class="item-actions-bottom">
            <ItemActions 
              :item="item"
              @pay="(e) => { e.preventDefault(); payItem() }"
              @promote="(e) => { e.preventDefault(); promoteItem() }"
              @edit="(e) => { e.preventDefault(); editItem() }"
              @deactivate="(e) => { e.preventDefault(); deactivateItem() }"
              @delete="(e) => { e.preventDefault(); showDeleteModal = true }"
              @click.stop
            />
          </div>
        </div>
      </div>
    </div>
  </Link>

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
  // Для всех объявлений (включая черновики) используем один роут
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

const deleteItem = async () => {
  try {
    // Используем правильный роут через router для удаления
    router.delete(`/my-ads/${props.item.id}`, {
      preserveScroll: false,
      preserveState: false,
      onSuccess: () => {
        emit('item-deleted', props.item.id)
        showDeleteModal.value = false
      },
      onError: (errors) => {
        console.error('Ошибка при удалении:', errors)
        showDeleteModal.value = false
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
  @apply bg-white border border-gray-200 mb-4;
  border-radius: 16px; /* Как на Ozon */
  padding: 0; /* Убираем padding как на Ozon */
  height: fit-content; /* Строго по контенту */
  overflow: hidden; /* Как на Ozon */
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
}

.item-info-section {
  @apply flex flex-col justify-between flex-shrink-0;
  width: 200px; /* Оптимальная ширина правой колонки */
  height: 232px; /* Высота контента с отступами 12px */
}

/* Кнопки с большим отступом снизу */
.item-actions-bottom {
  margin-bottom: 30px; /* Еще больший отступ снизу */
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