<!--
  Рефакторированный компонент карточки услуги в стиле Avito
  Разбит на переиспользуемые подкомпоненты для лучшей maintainability
-->
<template>
  <div class="avito-item-snippet cursor-pointer hover:shadow-lg transition-shadow" @click="handleCardClick">
    <div class="item-snippet-content">
      <!-- Изображение -->
      <ItemImage 
        :item="item"
        :itemUrl="itemUrl"
      />

      <!-- Основной контент -->
      <ItemContent 
        :item="item"
        :itemUrl="itemUrl"
      />

      <!-- Статистика и действия -->
      <div class="item-info-section">
        <ItemStats :item="item" />
        
        <ItemActions 
          :item="item"
          @pay="payItem"
          @promote="promoteItem"
          @edit="editItem"
          @deactivate="deactivateItem"
          @restore="restoreItem"
          @delete="showDeleteConfirm"
        />
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ItemImage from '../Cards/ItemImage.vue'
import ItemContent from '../Cards/ItemContent.vue'
import ItemStats from '../Cards/ItemStats.vue'
import ItemActions from '../Cards/ItemActions.vue'
import ConfirmModal from '../UI/ConfirmModal.vue'

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
const handleCardClick = (event) => {
  // Не переходим по ссылке при клике на кнопки
  if (event.target.closest('button') || event.target.closest('.dropdown-container')) {
    return
  }
  
  window.open(itemUrl.value, '_blank')
}

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

const showDeleteConfirm = () => {
  showDeleteModal.value = true
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
  @apply bg-white rounded-lg border border-gray-200 p-4 mb-4;
}

.item-snippet-content {
  @apply flex gap-4;
}

.item-info-section {
  @apply flex flex-col justify-between w-48 flex-shrink-0;
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