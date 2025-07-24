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
  if (props.item.status === 'draft') {
    router.visit(`/draft/${props.item.id}/edit`)
  } else {
    router.visit(`/ads/${props.item.id}/edit`)
  }
}

const deactivateItem = async () => {
  try {
    const response = await fetch(`/api/ads/${props.item.id}/deactivate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      emit('item-updated', { ...props.item, status: 'archived' })
    }
  } catch (error) {
    console.error('Ошибка при деактивации:', error)
  }
}

const restoreItem = async () => {
  try {
    const response = await fetch(`/api/ads/${props.item.id}/restore`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      emit('item-updated', { ...props.item, status: 'active' })
    }
  } catch (error) {
    console.error('Ошибка при восстановлении:', error)
  }
}

const showDeleteConfirm = () => {
  showDeleteModal.value = true
}

const deleteItem = async () => {
  try {
    const response = await fetch(`/api/ads/${props.item.id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      emit('item-deleted', props.item.id)
    }
  } catch (error) {
    console.error('Ошибка при удалении:', error)
  } finally {
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