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
    confirm-text="Удалить"
    cancel-text="Отмена"
    @confirm="deleteItem"
    @cancel="showDeleteModal = false"
  />
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
  // Для всех объявлений (включая черновики) используем один роут
  router.visit(`/ads/${props.item.id}/edit`)
  emit('edit', props.item.id)
}

const deactivateItem = () => {
  router.post(`/ads/${props.item.id}/deactivate`, {}, {
    onSuccess: () => {
      emit('item-updated', props.item.id, { status: 'inactive' })
      emit('deactivate', props.item.id)
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