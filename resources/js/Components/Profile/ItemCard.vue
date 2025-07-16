<template>
  <div class="avito-item-card">
    <div class="item-row">
      <!-- Изображение -->
      <div class="item-image-container">
        <div class="item-image-wrapper">
          <img 
            :src="item.avatar || '/images/default-avatar.svg'" 
            :alt="item.name"
            class="item-image"
            @error="$event.target.src = '/images/default-avatar.svg'"
          >
          <!-- Индикатор количества фото -->
          <div v-if="item.photos_count > 1" class="photo-count-badge">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M2 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4z" fill="currentColor"/>
              <path d="M6 8l1.5 1.5L11 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ item.photos_count }}
          </div>
        </div>
      </div>

      <!-- Основной контент -->
      <div class="item-content">
        <!-- Заголовок -->
        <div class="item-header">
          <h3 class="item-title">
            <a :href="`/ads/${item.id}`" class="item-link">
              {{ item.name }}
            </a>
          </h3>
          
          <!-- Цена -->
          <div class="item-price">
            <span v-if="item.price_from" class="price-value">
              {{ formatPrice(item.price_from) }} ₽
            </span>
            <span v-else class="price-negotiable">Цена договорная</span>
          </div>
        </div>

        <!-- Дополнительная информация -->
        <div class="item-details">
          <div class="item-info">
            <span class="item-category">{{ item.services_list || 'Массаж' }}</span>
            <span class="item-location">{{ item.address || item.city }}</span>
          </div>
          
          <!-- Статистика -->
          <div class="item-stats">
            <div class="stat-item">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M8 6v4M8 10h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <span class="stat-value">{{ item.views_count || 0 }}</span>
            </div>
            
            <div class="stat-item">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 2L2 8l6 6 6-6-6-6z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              <span class="stat-value">{{ item.messages_count || 0 }}</span>
            </div>
            
            <div class="stat-item">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 2L2 8l6 6 6-6-6-6z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              <span class="stat-value">{{ item.bookings_count || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Статус и дата -->
        <div class="item-footer">
          <div class="item-status">
            <span :class="['status-text', getStatusClass(item.status)]">
              {{ getStatusText(item.status) }}
            </span>
            <span class="item-date">{{ formatDate(item.updated_at) }}</span>
          </div>
          
          <!-- Дополнительные бейджи -->
          <div class="item-badges">
            <span v-if="item.status === 'active'" class="badge badge-active">
              Нет новых чатов
            </span>
            <span v-if="item.status === 'draft'" class="badge badge-draft">
              Выберите объявления
            </span>
          </div>
        </div>
      </div>

      <!-- Действия -->
      <div class="item-actions">
        <div class="actions-dropdown" ref="dropdown">
          <button 
            type="button" 
            class="actions-button"
            @click="toggleDropdown"
          >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM10 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM10 16a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" fill="currentColor"/>
            </svg>
          </button>
          
          <div v-if="showDropdown" class="dropdown-menu">
            <a href="#" class="dropdown-item" @click.prevent="editItem">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M11.5 3.5L12.5 4.5L5 12H4V11L11.5 3.5Z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              Редактировать
            </a>
            
            <a href="#" class="dropdown-item" @click.prevent="duplicateItem">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M4 4H12V12H4V4Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M2 2H10V10H2V2Z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              Дублировать
            </a>
            
            <a v-if="item.status === 'active'" href="#" class="dropdown-item" @click.prevent="archiveItem">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M2 4H14V12H2V4Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M6 8H10" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              В архив
            </a>
            
            <a v-if="item.status === 'archived'" href="#" class="dropdown-item" @click.prevent="activateItem">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 2L2 8L8 14L14 8L8 2Z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              Активировать
            </a>
            
            <div class="dropdown-divider"></div>
            
            <a href="#" class="dropdown-item text-red-600" @click.prevent="showDeleteConfirm">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2H10V4H6V2Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M3 4H13L12 14H4L3 4Z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              Удалить
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Удаление объявления"
      message="Вы уверены, что хотите удалить это объявление? Это действие нельзя отменить."
      confirm-text="Удалить"
      cancel-text="Отмена"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import ConfirmModal from '@/Components/UI/ConfirmModal.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['item-deleted'])

const showDropdown = ref(false)
const showDeleteModal = ref(false)
const dropdown = ref(null)

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

const closeDropdown = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short'
  })
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'status-active',
    'draft': 'status-draft', 
    'archived': 'status-archived',
    'paused': 'status-paused'
  }
  return classes[status] || 'status-default'
}

const getStatusText = (status) => {
  const texts = {
    'active': 'Активно',
    'draft': 'Черновик',
    'archived': 'В архиве', 
    'paused': 'Приостановлено'
  }
  return texts[status] || status
}

// Действия
const editItem = () => {
  window.location.href = `/ads/${props.item.id}/edit`
}

const duplicateItem = () => {
  // Логика дублирования
  console.log('Duplicate item:', props.item.id)
}

const archiveItem = async () => {
  try {
    const response = await fetch(`/ads/${props.item.id}/status`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: 'archived' })
    })

    const result = await response.json()
    
    if (result.success) {
      // Обновляем статус элемента
      props.item.status = 'archived'
      console.log('Archive item:', props.item.id)
    } else {
      alert('Ошибка архивирования: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error) {
    console.error('Ошибка при архивировании:', error)
    alert('Ошибка при архивировании объявления')
  }
}

const activateItem = async () => {
  try {
    const response = await fetch(`/ads/${props.item.id}/status`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: 'active' })
    })

    const result = await response.json()
    
    if (result.success) {
      // Обновляем статус элемента
      props.item.status = 'active'
      console.log('Activate item:', props.item.id)
    } else {
      alert('Ошибка активации: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error) {
    console.error('Ошибка при активации:', error)
    alert('Ошибка при активации объявления')
  }
}

// Показать модальное окно подтверждения удаления
const showDeleteConfirm = () => {
  showDropdown.value = false
  showDeleteModal.value = true
}

// Подтвердить удаление
const confirmDelete = async () => {
  showDeleteModal.value = false
  
  try {
    const response = await fetch(`/ads/${props.item.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      }
    })

    const result = await response.json()
    
    if (result.success) {
      // Удаляем элемент из списка
      emit('item-deleted', props.item.id)
      console.log('Delete item:', props.item.id)
    } else {
      alert('Ошибка удаления: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error) {
    console.error('Ошибка при удалении:', error)
    alert('Ошибка при удалении объявления')
  }
}

// Отменить удаление
const cancelDelete = () => {
  showDeleteModal.value = false
}
</script>

<style scoped>
.avito-item-card {
  @apply bg-white border border-gray-200 rounded-lg mb-4 hover:shadow-md transition-shadow;
}

.item-row {
  @apply flex p-4 gap-4;
}

/* Изображение */
.item-image-container {
  @apply flex-shrink-0;
}

.item-image-wrapper {
  @apply relative w-20 h-20 rounded-lg overflow-hidden bg-gray-100;
}

.item-image {
  @apply w-full h-full object-cover;
}

.photo-count-badge {
  @apply absolute bottom-1 right-1 bg-black bg-opacity-70 text-white text-xs px-1.5 py-0.5 rounded flex items-center gap-1;
}

/* Основной контент */
.item-content {
  @apply flex-1 min-w-0;
}

.item-header {
  @apply mb-2;
}

.item-title {
  @apply text-lg font-medium text-gray-900 mb-1;
}

.item-link {
  @apply text-gray-900 hover:text-blue-600 transition-colors;
}

.item-price {
  @apply mb-2;
}

.price-value {
  @apply text-xl font-bold text-gray-900;
}

.price-negotiable {
  @apply text-lg text-gray-600;
}

.item-details {
  @apply mb-3;
}

.item-info {
  @apply flex flex-col gap-1 mb-2;
}

.item-category {
  @apply text-sm text-gray-600;
}

.item-location {
  @apply text-sm text-gray-500;
}

.item-stats {
  @apply flex gap-4;
}

.stat-item {
  @apply flex items-center gap-1 text-sm text-gray-500;
}

.stat-value {
  @apply text-gray-600;
}

.item-footer {
  @apply flex items-center justify-between;
}

.item-status {
  @apply flex items-center gap-2;
}

.status-text {
  @apply text-sm font-medium;
}

.status-active {
  @apply text-green-600;
}

.status-draft {
  @apply text-yellow-600;
}

.status-archived {
  @apply text-gray-500;
}

.status-paused {
  @apply text-orange-600;
}

.item-date {
  @apply text-sm text-gray-500;
}

.item-badges {
  @apply flex gap-2;
}

.badge {
  @apply px-2 py-1 text-xs rounded;
}

.badge-active {
  @apply bg-green-100 text-green-700;
}

.badge-draft {
  @apply bg-yellow-100 text-yellow-700;
}

/* Действия */
.item-actions {
  @apply flex-shrink-0;
}

.actions-dropdown {
  @apply relative;
}

.actions-button {
  @apply p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-lg transition-colors;
}

.dropdown-menu {
  @apply absolute right-0 top-full mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10;
}

.dropdown-item {
  @apply flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors;
}

.dropdown-item:first-child {
  @apply rounded-t-lg;
}

.dropdown-item:last-child {
  @apply rounded-b-lg;
}

.dropdown-divider {
  @apply border-t border-gray-200 my-1;
}

/* Responsive */
@media (max-width: 640px) {
  .item-row {
    @apply flex-col gap-3;
  }
  
  .item-image-wrapper {
    @apply w-full h-48;
  }
  
  .item-stats {
    @apply flex-wrap;
  }
}
</style> 