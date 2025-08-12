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
        class="action-btn btn-edit-primary"
      >
        Редактировать
      </button>
      
      <!-- Кнопка меню с тремя точками -->
      <div class="action-menu">
        <button 
          @click="toggleMenu"
          class="action-btn btn-menu"
          ref="menuButton"
        >
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="4" r="1.5" fill="currentColor"/>
            <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
            <circle cx="10" cy="16" r="1.5" fill="currentColor"/>
          </svg>
        </button>
        
        <!-- Выпадающее меню -->
        <div 
          v-if="showMenu" 
          class="dropdown-menu"
          ref="dropdownMenu"
        >
          <button 
            @click="handleEdit"
            class="menu-item"
          >
            Редактировать
          </button>
          <button 
            @click="handleDelete"
            class="menu-item"
          >
            Удалить
          </button>
        </div>
      </div>
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
import { ref, onMounted, onUnmounted } from 'vue'
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
}

defineProps<Props>()

const emit = defineEmits<{
  pay: []
  promote: []
  edit: []
  deactivate: []
  delete: []
}>()

// Состояние меню
const showMenu = ref(false)
const menuButton = ref<HTMLElement | null>(null)
const dropdownMenu = ref<HTMLElement | null>(null)

// Функция переключения меню
const toggleMenu = () => {
  showMenu.value = !showMenu.value
}

// Обработчики действий
const handleEdit = () => {
  showMenu.value = false
  emit('edit')
}

const handleDelete = () => {
  showMenu.value = false
  emit('delete')
}

// Закрытие меню при клике вне его
const handleClickOutside = (event: MouseEvent) => {
  if (menuButton.value && dropdownMenu.value) {
    const target = event.target as Node
    if (!menuButton.value.contains(target) && !dropdownMenu.value.contains(target)) {
      showMenu.value = false
    }
  }
}

// Добавляем и удаляем слушатель событий
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
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

/* Стили для кнопки Редактировать черновика */
.btn-edit-primary {
  background: #f5f5f5;
  color: #333;
  border-color: #d5d5d5;
  padding: 8px 16px;
  font-size: 14px;
}

.btn-edit-primary:hover {
  background: #e6e6e6;
}

/* Стили для меню */
.action-menu {
  position: relative;
}

.btn-menu {
  background: white;
  color: #666;
  border-color: #d5d5d5;
  padding: 4px 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
}

.btn-menu:hover {
  background: #f5f5f5;
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  min-width: 160px;
  z-index: 1000;
  overflow: hidden;
}

.menu-item {
  display: block;
  width: 100%;
  padding: 10px 16px;
  text-align: left;
  font-size: 14px;
  color: #333;
  background: none;
  border: none;
  cursor: pointer;
  transition: background 0.2s ease;
}

.menu-item:hover {
  background: #f5f5f5;
}

.menu-item:not(:last-child) {
  border-bottom: 1px solid #f0f0f0;
}
</style>