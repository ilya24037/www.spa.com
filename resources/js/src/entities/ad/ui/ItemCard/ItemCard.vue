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
          />
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно подтверждения удаления -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
          <!-- Backdrop -->
          <div 
            class="fixed inset-0 bg-black bg-opacity-50"
            @click="showDeleteModal = false"
          />
          
          <!-- Modal -->
          <div class="relative bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-2">
              Удалить объявление?
            </h3>
            <p class="text-gray-600 mb-6">
              Это действие нельзя отменить. Объявление будет удалено навсегда.
            </p>
            
            <div class="flex justify-end gap-3">
              <button
                @click="showDeleteModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Отмена
              </button>
              <button
                @click="deleteItem"
                class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
              >
                Удалить
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import ItemImage from './components/ItemImage.vue'
import ItemContent from './components/ItemContent.vue'
import ItemStats from './components/ItemStats.vue'
import ItemActions from './components/ItemActions.vue'
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

const deleteItem = () => {
  router.delete(`/ads/${props.item.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      emit('item-deleted', props.item.id)
      emit('delete', props.item.id)
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