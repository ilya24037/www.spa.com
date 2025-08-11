<!-- ItemActions - действия с объявлением -->
<template>
  <div class="item-actions">
    <!-- Кнопка "Оплатить" для ожидающих оплаты -->
    <button 
      v-if="item.waiting_payment"
      @click="$emit('pay')"
      class="action-btn btn-pay"
    >
      Оплатить
    </button>
    
    <!-- Кнопки для активных объявлений -->
    <template v-else-if="item.status === 'active'">
      <button 
        @click="$emit('promote')"
        class="action-btn btn-promote"
      >
        Продвинуть
      </button>
      <button 
        @click="$emit('edit')"
        class="action-btn btn-edit"
      >
        Редактировать
      </button>
      <button 
        @click="$emit('deactivate')"
        class="action-btn btn-deactivate"
      >
        Снять
      </button>
    </template>
    
    <!-- Кнопки для черновиков -->
    <template v-else-if="item.status === 'draft'">
      <button 
        @click="$emit('edit')"
        class="action-btn btn-edit"
      >
        Продолжить
      </button>
      <button 
        @click="$emit('delete')"
        class="action-btn btn-delete"
      >
        Удалить
      </button>
    </template>
    
    <!-- Кнопки для неактивных -->
    <template v-else>
      <button 
        @click="$emit('edit')"
        class="action-btn btn-edit"
      >
        Редактировать
      </button>
      <button 
        @click="$emit('delete')"
        class="action-btn btn-delete"
      >
        Удалить
      </button>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
}

defineProps<Props>()

defineEmits<{
  pay: []
  promote: []
  edit: []
  deactivate: []
  delete: []
}>()
</script>

<style scoped>
.item-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.action-btn {
  padding: 6px 12px;
  font-size: 13px;
  font-weight: 500;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-pay {
  background: #00a55b;
  color: white;
  border-color: #00a55b;
}

.btn-pay:hover {
  background: #008548;
}

.btn-promote {
  background: #0066ff;
  color: white;
  border-color: #0066ff;
}

.btn-promote:hover {
  background: #0052cc;
}

.btn-edit {
  background: white;
  color: #333;
  border-color: #d5d5d5;
}

.btn-edit:hover {
  background: #f5f5f5;
}

.btn-deactivate {
  background: white;
  color: #666;
  border-color: #d5d5d5;
}

.btn-deactivate:hover {
  background: #f5f5f5;
}

.btn-delete {
  background: white;
  color: #d32f2f;
  border-color: #d5d5d5;
}

.btn-delete:hover {
  background: #ffebee;
  border-color: #d32f2f;
}
</style>