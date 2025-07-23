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
  <div class="avito-item-snippet cursor-pointer hover:shadow-lg transition-shadow" @click="handleCardClick">
    <div class="item-snippet-content">
      <!-- Изображение слева (160x120px) -->
      <div class="item-image-section">
        <a :href="itemUrl" class="item-image-link">
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
          <a :href="itemUrl" class="item-title-link">
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
        <div v-if="item.status !== 'waiting_payment'" class="item-counters">
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
          <span v-if="item.status === 'waiting_payment'" class="lifetime-text text-gray-900">
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
          <!-- Для объявлений ждущих оплаты показываем две кнопки в ряд -->
          <div v-if="item.status === 'waiting_payment'" class="waiting-payment-actions">
            <button @click="payItem" class="action-button secondary-button-flex">
              <span class="button-wrapper">
                <span class="button-text">Оплатить размещение</span>
              </span>
            </button>
            
            <div class="dropdown-container" ref="dropdown">
              <button 
                type="button" 
                class="dropdown-button-inline"
                @click="toggleDropdown"
                aria-haspopup="true"
                :aria-expanded="showDropdown"
              >
                <span class="dropdown-button-wrapper">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" class="dropdown-icon">
                    <circle cx="4" cy="10" r="1.5" fill="currentColor"/>
                    <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
                    <circle cx="16" cy="10" r="1.5" fill="currentColor"/>
                  </svg>
                </span>
              </button>
              
              <div v-if="showDropdown" class="dropdown-menu">
                <a href="#" class="dropdown-item" @click.prevent="payItem">
                  Оплатить размещение
                </a>
                <a href="#" class="dropdown-item" @click.prevent="deactivateItem">
                  Уже не актуально
                </a>
                <a href="#" class="dropdown-item" @click.prevent="editItem">
                  Редактировать
                </a>
                <a href="#" class="dropdown-item danger-item" @click.stop.prevent="showDeleteConfirm">
                  Удалить
                </a>
              </div>
            </div>
          </div>

          <!-- Для активных объявлений -->
          <template v-else-if="item.status === 'active'">
            <div class="waiting-payment-actions">
              <button class="action-button secondary-button-flex">
                <span class="button-wrapper">
                  <span class="button-text">Поднять просмотры</span>
                </span>
              </button>
              
              <div class="dropdown-container" ref="dropdown">
                <button 
                  type="button" 
                  class="dropdown-button-inline"
                  @click="toggleDropdown"
                  aria-haspopup="true"
                  :aria-expanded="showDropdown"
                >
                  <span class="dropdown-button-wrapper">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" class="dropdown-icon">
                      <circle cx="4" cy="10" r="1.5" fill="currentColor"/>
                      <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
                      <circle cx="16" cy="10" r="1.5" fill="currentColor"/>
                    </svg>
                  </span>
                </button>
                
                <div v-if="showDropdown" class="dropdown-menu">
                  <a href="#" class="dropdown-item" @click.prevent="promoteItem">
                    Поднять просмотры
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item" @click.prevent="deactivateItem">
                    Снять с публикации
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.stop.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </div>
              </div>
            </div>
          </template>
          
          <!-- Для черновиков -->
          <template v-else-if="item.status === 'draft'">
            <div class="waiting-payment-actions">
              <button @click="editItem" class="action-button secondary-button-flex">
                <span class="button-wrapper">
                  <span class="button-text">Редактировать</span>
                </span>
              </button>
              
              <div class="dropdown-container" ref="dropdown">
                <button 
                  type="button" 
                  class="dropdown-button-inline"
                  @click="toggleDropdown"
                  aria-haspopup="true"
                  :aria-expanded="showDropdown"
                >
                  <span class="dropdown-button-wrapper">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" class="dropdown-icon">
                      <circle cx="4" cy="10" r="1.5" fill="currentColor"/>
                      <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
                      <circle cx="16" cy="10" r="1.5" fill="currentColor"/>
                    </svg>
                  </span>
                </button>
                
                <div v-if="showDropdown" class="dropdown-menu">
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.stop.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </div>
              </div>
            </div>
          </template>

          <!-- Для архивных объявлений -->
          <template v-else-if="item.status === 'archived'">
            <div class="waiting-payment-actions">
              <button @click="restoreItem" class="action-button secondary-button-flex">
                <span class="button-wrapper">
                  <span class="button-text">Восстановить</span>
                </span>
              </button>
              
              <div class="dropdown-container" ref="dropdown">
                <button 
                  type="button" 
                  class="dropdown-button-inline"
                  @click="toggleDropdown"
                  aria-haspopup="true"
                  :aria-expanded="showDropdown"
                >
                  <span class="dropdown-button-wrapper">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" class="dropdown-icon">
                      <circle cx="4" cy="10" r="1.5" fill="currentColor"/>
                      <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
                      <circle cx="16" cy="10" r="1.5" fill="currentColor"/>
                    </svg>
                  </span>
                </button>
                
                <div v-if="showDropdown" class="dropdown-menu">
                  <a href="#" class="dropdown-item" @click.prevent="editItem">
                    Редактировать
                  </a>
                  <a href="#" class="dropdown-item danger-item" @click.stop.prevent="showDeleteConfirm">
                    Удалить
                  </a>
                </div>
              </div>
            </div>
          </template>
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import ConfirmModal from '../UI/ConfirmModal.vue'

// Импортируем route из window.route (Ziggy)
const { route } = window

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

// Computed property для правильного URL в зависимости от статуса
const itemUrl = computed(() => {
  if (props.item.status === 'draft') {
    return `/draft/${props.item.id}`
  }
  return `/ads/${props.item.id}`
})

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

// Обработчик клика по карточке (как на Авито)
const handleCardClick = (event) => {
  // Игнорируем клик если это по кнопкам действий
  if (event.target.closest('.dropdown-container') || 
      event.target.closest('.action-button') ||
      event.target.closest('button')) {
    return
  }
  
  // Используем router.visit для навигации
  console.log('Card clicked, navigating to:', itemUrl.value)
  router.visit(itemUrl.value)
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
  if (!path || path === 'undefined') return '/images/masters/demo-1.jpg'
  
  // Если это base64 изображение
  if (path.startsWith('data:image/')) {
    return path
  }
  
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
  event.target.src = '/images/masters/demo-1.jpg'
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
  // Перенаправляем на страницу выбора тарифа как в Авито
  window.location.href = `/payment/ad/${props.item.id}/select-plan`
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
    // Используем один роут для удаления всех объявлений (как у Avito)
    const deleteUrl = `/my-ads/${props.item.id}`
    
    router.delete(deleteUrl, {
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
  @apply flex p-4;
  gap: 12px; /* уменьшаем отступ для экономии места */
}

/* Блок изображения (160x120px) */
.item-image-section {
  @apply flex-shrink-0;
  width: 160px; /* фиксированная ширина */
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
  flex: 1; /* занимает максимум доступного места */
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
  @apply flex-shrink-0 flex flex-col gap-3;
  width: 220px; /* увеличено для помещения полного текста */
  max-width: 220px;
  overflow: visible; /* разрешаем переполнение для dropdown меню */
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
  @apply text-gray-900;
}

.lifetime-warning {
  @apply text-red-600 font-medium;
}

.item-chats {
  @apply flex items-center gap-2 text-sm text-gray-900;
}

.chat-icon {
  @apply text-gray-900;
  flex-shrink: 0;
}

.chat-text {
  @apply text-gray-900;
}

.item-actions {
  @apply flex flex-col gap-2;
  width: 100%;
  max-width: 100%;
}

.action-button {
  @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors;
}

.primary-button {
  @apply bg-blue-600 text-white hover:bg-blue-700 w-full;
  max-width: 100%;
}

.primary-button-flex {
  @apply bg-blue-600 text-white hover:bg-blue-700;
  flex: 1;
  width: auto;
  min-width: 0; /* Позволяет элементу сжиматься */
}

.button-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 6px 12px; /* как в реальном Авито */
}

.button-text {
  white-space: nowrap;
  font-size: 14px; /* как в реальном Авито */
  line-height: 1.2;
}

.secondary-button {
  @apply bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200 rounded;
  height: 32px;
  padding: 6px 12px;
  font-size: 14px;
  font-weight: 400;
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

.action-row {
  @apply flex gap-2;
  width: 100%;
  max-width: 100%;
}

.dropdown-container {
  @apply relative;
  overflow: visible;
  z-index: 1; /* гарантируем что контейнер создает новый stacking context */
}

.dropdown-button {
  @apply p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors;
}

.dropdown-button-avito {
  @apply p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  flex-shrink: 0;
}

.gray-button {
  @apply bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200;
}

.dropdown-button-inline {
  @apply bg-gray-100 text-gray-900 hover:bg-gray-200 border border-gray-200 rounded transition-colors;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px; /* как в реальном Авито */
  height: 32px; /* как в реальном Авито */
  flex-shrink: 0;
}

.dropdown-button-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.dropdown-icon {
  width: 20px;
  height: 20px;
}

.dropdown-menu {
  @apply absolute right-0 top-full mt-1 w-48 bg-white border border-gray-200 rounded-lg;
  z-index: 9999; /* максимальный z-index для отображения поверх всех элементов */
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); /* усиленная тень */
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

.waiting-payment-actions {
  @apply flex flex-row;
  gap: 4px; /* отступ как в реальном Авито */
  width: 100%;
  min-width: 200px; /* обеспечиваем минимальное место для кнопок */
}

.secondary-button-flex {
  @apply bg-gray-100 text-gray-900 hover:bg-gray-200 border border-gray-200 rounded;
  height: 32px; /* как в реальном Авито */
  padding: 0;
  font-size: 14px; /* увеличиваем шрифт */
  font-weight: 400;
  flex: 1;
  min-width: 150px; /* минимальная ширина для полного текста */
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
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