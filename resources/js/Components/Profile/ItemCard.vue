<!--
  Компонент карточки услуги в стиле Avito для раздела "Мои объявления"
  
  Ожидаемая структура данных item:
  {
    id: 1,
    name: "Клапан вентиляции топливного бака Chevrolet Captiv",
    price_from: 1500,
    avatar: "/images/masters/demo-1.jpg",
    main_image: "/images/masters/demo-1.jpg",
    photos_count: 4,
    company_name: "METACO",
    address: "Пермский край, Пермь, ул. Адмирала Ушакова, 21",
    district: "р-н Кировский",
    home_service: true,
    status: "active",
    views_count: 7,
    subscribers_count: 0,
    favorites_count: 0,
    new_messages_count: 0,
    expires_at: "2024-02-15T12:00:00Z"
  }
-->
<template>
  <div class="avito-item-snippet">
    <div class="item-snippet-content">
      <!-- Изображение слева (160x120px) -->
      <div class="item-image-section">
        <a :href="`/ads/${item.id}`" class="item-image-link">
          <div class="item-image-wrapper">
            <img 
              :src="getImageUrl(item.avatar || item.main_image)"
              :alt="item.name"
              class="item-image"
              @error="handleImageError"
            >
            <!-- Индикаторы слайдера (белые точки) -->
            <div v-if="item.photos_count > 1" class="slider-indicators">
              <div 
                v-for="n in Math.min(item.photos_count, 4)" 
                :key="n"
                class="slider-dot"
                :class="{ 'active': n === 1 }"
              ></div>
            </div>
          </div>
        </a>
      </div>

      <!-- Основной контент (центральная часть) -->
      <div class="item-content-section">
        <!-- Заголовок -->
        <h4 class="item-title">
          <a :href="`/ads/${item.id}`" class="item-title-link">
            {{ item.name }}
          </a>
        </h4>

        <!-- Описание -->
        <div v-if="item.description" class="item-description">
          <p class="description-text">{{ item.description }}</p>
        </div>

        <!-- Цена -->
        <div class="item-price-section">
          <p class="item-price">
            <span v-if="item.price_from" class="price-value">
              {{ formatPrice(item.price_from) }}
            </span>
            <span v-else class="price-negotiable">Договорная</span>
            <span class="price-currency">₽</span>
          </p>
        </div>

        <!-- Доступность -->
        <div class="item-stock">
          <p class="stock-text">Доступен для записи</p>
        </div>

        <!-- Доставка/Выезд -->
        <div v-if="item.home_service" class="item-delivery">
          <div class="delivery-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </div>
          <p class="delivery-text">Выезд к клиенту</p>
        </div>

        <!-- Название компании/мастера -->
        <div class="item-company">
          <p class="company-name">{{ item.company_name || 'Массажный салон' }}</p>
        </div>

        <!-- Адрес -->
        <div class="item-location">
          <p class="location-address">{{ item.address || item.city }}</p>
          <p class="location-district">{{ item.district || 'Центральный район' }}</p>
        </div>
      </div>

      <!-- Информация и действия (правая колонка) -->
      <div class="item-info-section">
        <!-- Счетчики -->
        <div class="item-counters">
          <!-- Просмотры (глаз) -->
          <div class="counter-item">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M8 3C4.5 3 1.73 5.11 1 8c.73 2.89 3.5 5 7 5s6.27-2.11 7-5c-.73-2.89-3.5-5-7-5zm0 8.5c-1.93 0-3.5-1.57-3.5-3.5S6.07 4.5 8 4.5s3.5 1.57 3.5 3.5S9.93 11.5 8 11.5zm0-5.5c-.83 0-1.5.67-1.5 1.5S7.17 9.5 8 9.5s1.5-.67 1.5-1.5S8.83 6 8 6z" fill="currentColor"/>
            </svg>
            <span class="counter-value">{{ item.views_count || 0 }}</span>
          </div>
          <!-- Подписчики (человек) -->
          <div class="counter-item">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M8 8c1.66 0 3-1.34 3-3S9.66 2 8 2 5 3.34 5 5s1.34 3 3 3zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
            </svg>
            <span class="counter-value">{{ item.subscribers_count || 0 }}</span>
          </div>
          <!-- Избранное (сердце) -->
          <div class="counter-item">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M8 14s6-4.5 6-8.5C14 3.5 12 2 10 2c-1 0-2 .5-2 1.5C8 2.5 7 2 6 2 4 2 2 3.5 2 5.5 2 9.5 8 14 8 14z" fill="currentColor"/>
            </svg>
            <span class="counter-value">{{ item.favorites_count || 0 }}</span>
          </div>
        </div>

        <!-- Время до окончания -->
        <div class="item-lifetime">
          <span v-if="item.status === 'waiting_payment'" class="lifetime-text text-orange-600">
            Не оплачено
          </span>
          <span v-else class="lifetime-text" :class="{ 'lifetime-warning': getDaysLeft() < 7 }">
            Осталось {{ getDaysLeft() }} дней
          </span>
        </div>

        <!-- Чаты -->
        <div class="item-chats">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="chat-icon">
            <!-- Простая иконка чата как в Avito -->
            <path d="M2 3h12c.6 0 1 .4 1 1v6c0 .6-.4 1-1 1h-3l-3 2v-2H2c-.6 0-1-.4-1-1V4c0-.6.4-1 1-1z" 
                  stroke="currentColor" 
                  stroke-width="1" 
                  fill="none"/>
          </svg>
          <span class="chat-text">{{ item.new_messages_count > 0 ? `${item.new_messages_count} новых чатов` : 'Нет новых чатов' }}</span>
        </div>

        <!-- Кнопки действий -->
        <div class="item-actions">
          <!-- Для объявлений ждущих оплаты показываем кнопку "Оплатить размещение" -->
          <button v-if="item.status === 'waiting_payment'" 
                  @click="payItem"
                  class="action-button primary-button">
            Оплатить размещение
          </button>
          
          <!-- Для активных объявлений показываем "Поднять просмотры" -->
          <button v-else-if="item.status === 'active'" 
                  class="action-button primary-button">
            Поднять просмотры
          </button>
          
          <!-- Для черновиков показываем "Опубликовать" -->
          <button v-else-if="item.status === 'draft'" 
                  @click="publishItem"
                  class="action-button primary-button">
            Опубликовать
          </button>
          
          <div class="action-row">
            <button v-if="item.status !== 'waiting_payment'" 
                    class="action-button secondary-button">
              Рассылка
            </button>
            
            <div class="dropdown-container" ref="dropdown">
              <button 
                type="button" 
                class="dropdown-button"
                @click="toggleDropdown"
              >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M10 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM10 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM10 16a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" fill="currentColor"/>
                </svg>
              </button>
              
              <div v-if="showDropdown" class="dropdown-menu">
                <!-- Меню для объявлений, ждущих действий -->
                <template v-if="item.status === 'waiting_payment'">
                  <a href="#" class="dropdown-item" @click.prevent="payItem">
                    Оплатить размещение
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="deactivateItem">
                    Уже не актуально
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </template>
                
                <!-- Меню для активных объявлений -->
                <template v-else-if="item.status === 'active'">
                  <a href="#" class="dropdown-item" @click.prevent="promoteItem">
                    Поднять просмотры
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="deactivateItem">
                    Снять с публикации
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </template>
                
                <!-- Меню для черновиков -->
                <template v-else-if="item.status === 'draft'">
                  <a href="#" class="dropdown-item" @click.prevent="publishItem">
                    Опубликовать
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </template>
                
                <!-- Меню для архивных объявлений -->
                <template v-else-if="item.status === 'archived'">
                  <a href="#" class="dropdown-item" @click.prevent="restoreItem">
                    Восстановить
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </template>
                
                <!-- Меню по умолчанию -->
                <template v-else>
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </template>
              </div>
            </div>
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
import { router } from '@inertiajs/vue3'
import ConfirmModal from '../UI/ConfirmModal.vue'

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

const getImageUrl = (path) => {
  if (!path) return '/images/no-photo.jpg'
  
  // Если это уже полный URL
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }
  
  // Если путь начинается с /storage/
  if (path.startsWith('/storage/')) {
    return path
  }
  
  // Если путь начинается с /
  if (path.startsWith('/')) {
    return path
  }
  
  // Иначе добавляем /storage/
  return `/storage/${path}`
}

const handleImageError = (event) => {
  event.target.src = '/images/no-photo.jpg'
  event.target.onerror = null // Предотвращаем бесконечный цикл
}

const getDaysLeft = () => {
  if (!props.item.expires_at) return 30
  const now = new Date()
  const expires = new Date(props.item.expires_at)
  const diffTime = expires - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return Math.max(0, diffDays)
}

// Действия
const promoteItem = () => {
  console.log('Promote item:', props.item.id)
  showDropdown.value = false
}

const payItem = async () => {
  showDropdown.value = false
  try {
    // Используем Inertia для POST запроса
    router.post(`/my-ads/${props.item.id}/pay`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        // Обновление произойдет автоматически через Inertia
      },
      onError: (errors) => {
        console.error('Ошибка оплаты:', errors)
        alert('Ошибка при оплате объявления')
      }
    })
  } catch (error) {
    console.error('Ошибка при оплате:', error)
  }
}

const deactivateItem = async () => {
  showDropdown.value = false
  try {
    router.post(`/my-ads/${props.item.id}/deactivate`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        // Обновление произойдет автоматически через Inertia
      },
      onError: (errors) => {
        console.error('Ошибка деактивации:', errors)
        alert('Ошибка при деактивации объявления')
      }
    })
  } catch (error) {
    console.error('Ошибка при деактивации:', error)
  }
}

const publishItem = async () => {
  showDropdown.value = false
  try {
    router.post(`/my-ads/${props.item.id}/publish`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        // Обновление произойдет автоматически через Inertia
      },
      onError: (errors) => {
        console.error('Ошибка публикации:', errors)
        alert('Ошибка при публикации объявления')
      }
    })
  } catch (error) {
    console.error('Ошибка при публикации:', error)
  }
}

const restoreItem = async () => {
  showDropdown.value = false
  try {
    router.post(`/my-ads/${props.item.id}/restore`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        // Обновление произойдет автоматически через Inertia
      },
      onError: (errors) => {
        console.error('Ошибка восстановления:', errors)
        alert('Ошибка при восстановлении объявления')
      }
    })
  } catch (error) {
    console.error('Ошибка при восстановлении:', error)
  }
}

const editItem = () => {
  // Используем Inertia для навигации
  router.visit(`/ads/${props.item.id}/edit`)
  showDropdown.value = false
}

const reserveItem = () => {
  console.log('Reserve item:', props.item.id)
  showDropdown.value = false
}

const togglePublication = async () => {
  const newStatus = props.item.status === 'active' ? 'paused' : 'active'
  
  try {
    const response = await fetch(`/ads/${props.item.id}/status`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })

    const result = await response.json()
    
    if (result.success) {
      props.item.status = newStatus
      console.log('Toggle publication:', props.item.id, newStatus)
    } else {
      alert('Ошибка изменения статуса: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error) {
    console.error('Ошибка при изменении статуса:', error)
    alert('Ошибка при изменении статуса объявления')
  }
  
  showDropdown.value = false
}

const showDeleteConfirm = () => {
  showDropdown.value = false
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  showDeleteModal.value = false
  
  try {
    router.delete(`/my-ads/${props.item.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Обновление произойдет автоматически через Inertia
      },
      onError: (errors) => {
        console.error('Ошибка удаления:', errors)
        alert('Ошибка при удалении объявления')
      }
    })
  } catch (error) {
    console.error('Ошибка при удалении:', error)
  }
}

const cancelDelete = () => {
  showDeleteModal.value = false
}
</script>

<style scoped>
/* Главный контейнер - точная копия Avito */
.avito-item-snippet {
  @apply bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow duration-200;
  overflow: visible;
}

.item-snippet-content {
  @apply flex p-4 gap-4;
}

/* Блок изображения (160x120px) */
.item-image-section {
  @apply flex-shrink-0;
}

.item-image-link {
  @apply block;
}

  .item-image-wrapper {
    @apply relative w-40 h-32 rounded-lg overflow-hidden bg-gray-100;
  width: 160px;
  height: 120px;
  border-radius: 10px;
}

.item-image {
  @apply w-full h-full object-cover;
}

.slider-indicators {
  @apply absolute bottom-2 left-1/2 transform -translate-x-1/2 flex gap-1;
}

.slider-dot {
  @apply w-1.5 h-1.5 rounded-full bg-white bg-opacity-80 transition-all duration-200;
}

.slider-dot.active {
  @apply w-4 bg-white;
}

/* Блок контента (центральная часть) */
.item-content-section {
  @apply flex-1 min-w-0;
}

.item-title {
  @apply text-xl font-medium text-gray-900 mb-2 leading-tight;
}

.item-title-link {
  @apply text-gray-900 hover:text-blue-600 transition-colors;
}

.item-description {
  @apply mb-3;
}

.description-text {
  @apply text-sm text-gray-600 leading-relaxed;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.item-price-section {
  @apply mb-2;
}

.item-price {
  @apply text-2xl font-bold text-gray-900;
}

.price-value {
  @apply mr-1;
}

.price-currency {
  @apply text-gray-900;
}

.price-negotiable {
  @apply text-gray-600;
}

.item-stock {
  @apply mb-2;
}

.stock-text {
  @apply text-sm text-gray-600;
}

.item-delivery {
  @apply flex items-center gap-2 mb-2;
}

.delivery-icon {
  @apply text-gray-400;
}

.delivery-text {
  @apply text-sm text-gray-600;
}

.item-company {
  @apply mb-1;
}

.company-name {
  @apply text-sm text-gray-600;
}

.item-location {
  @apply text-sm;
  color: rgb(117, 117, 117);
}

.location-address {
  @apply mb-1;
}

.location-district {
  /* Пустой класс для consistency */
}

/* Блок информации и действий (правая колонка) */
.item-info-section {
  @apply flex-shrink-0 flex flex-col gap-3 w-48;
  overflow: visible;
}

.item-counters {
  @apply flex gap-4;
}

.counter-item {
  @apply flex items-center gap-1 text-sm text-gray-600;
}

.counter-value {
  @apply text-gray-900;
}

.item-lifetime {
  @apply text-sm;
}

.lifetime-text {
  @apply text-gray-600;
}

.lifetime-warning {
  @apply text-red-600 font-medium;
}

.item-chats {
  @apply flex items-center gap-2 text-sm text-gray-600;
}

.chat-icon {
  @apply text-gray-600;
  flex-shrink: 0;
}

.chat-text {
  /* Базовый стиль */
}

.item-actions {
  @apply flex flex-col gap-2;
}

.action-button {
  @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors;
}

.primary-button {
  @apply bg-blue-600 text-white hover:bg-blue-700 w-full;
}

.secondary-button {
  @apply bg-gray-100 text-gray-700 hover:bg-gray-200 flex-1;
}

.action-row {
  @apply flex gap-2;
}

.dropdown-container {
  @apply relative;
  overflow: visible;
}

.dropdown-button {
  @apply p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors;
}

.dropdown-menu {
  @apply absolute right-0 top-full mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50;
}

.dropdown-item {
  @apply block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors;
}

.dropdown-item:first-child {
  @apply rounded-t-lg;
}

.dropdown-item:last-child {
  @apply rounded-b-lg;
}

.danger-item {
  @apply text-red-600 hover:bg-red-50;
}

.dropdown-divider {
  @apply border-t border-gray-200 my-1;
}

/* Responsive */
@media (max-width: 768px) {
  .item-snippet-content {
    @apply flex-col gap-3;
  }
  
  .item-image-wrapper {
    @apply w-full h-48;
    width: 100%;
    height: 192px;
  }
  
  .item-info-section {
    @apply w-full;
  }
  
  .item-counters {
    @apply justify-center;
  }
  
  .action-row {
    @apply flex-col gap-2;
  }
  
  .secondary-button {
    @apply w-full;
  }
}
</style> 