<template>
  <div class="location-info-widget" :class="{ 'is-loading': isLoading }">
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <span class="loading-text">Загрузка информации о локации...</span>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
      </div>
      <div class="error-content">
        <h3 class="error-title">Ошибка загрузки</h3>
        <p class="error-message">{{ error }}</p>
        <button class="retry-button" @click="loadLocationInfo">
          Попробовать снова
        </button>
      </div>
    </div>
    
    <!-- Content -->
    <div v-else-if="locationInfo" class="location-content">
      <!-- Header -->
      <div class="location-header">
        <div class="location-icon">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 0C4.48 0 0 4.48 0 10c0 7 10 10 10 10s10-3 10-10c0-5.52-4.48-10-10-10z"/>
            <circle cx="10" cy="10" r="3"/>
          </svg>
        </div>
        <div class="location-title-container">
          <h3 class="location-title">{{ locationInfo.name }}</h3>
          <p v-if="locationInfo.type" class="location-type">
            {{ getLocationTypeLabel(locationInfo.type) }}
          </p>
        </div>
        <button 
          v-if="showFavoriteButton"
          class="favorite-button"
          :class="{ 'is-favorite': isFavorite }"
          @click="toggleFavorite"
          :title="isFavorite ? 'Удалить из избранного' : 'Добавить в избранное'"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 12.5l-4.5 2.8 1.2-5.1L0 6.4l5.2-.4L8 1l2.8 5 5.2.4-4.7 3.8 1.2 5.1L8 12.5z"/>
          </svg>
        </button>
      </div>
      
      <!-- Address -->
      <div v-if="locationInfo.address" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M7 0a7 7 0 0 1 7 7c0 5.25-7 7-7 7s-7-1.75-7-7a7 7 0 0 1 7-7z"/>
          </svg>
          <span>Адрес</span>
        </div>
        <p class="section-content">{{ locationInfo.address }}</p>
      </div>
      
      <!-- Coordinates -->
      <div v-if="showCoordinates" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M7 1a6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 1-6-6 6 6 0 0 1 6-6m0 2a4 4 0 0 0-4 4 4 4 0 0 0 4 4 4 4 0 0 0 4-4 4 4 0 0 0-4-4"/>
          </svg>
          <span>Координаты</span>
        </div>
        <p class="section-content coordinates">
          {{ formatCoordinates(coordinates) }}
          <button 
            class="copy-button"
            @click="copyCoordinates"
            title="Скопировать координаты"
          >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
              <path d="M8 0v1h3v10H4V8H3v4h9V0H8zM0 0v8h7V0H0zm1 1h5v6H1V1z"/>
            </svg>
          </button>
        </p>
      </div>
      
      <!-- Distance -->
      <div v-if="distance && showDistance" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M1 3h12v2H1V3zm0 4h8v2H1V7zm0 4h10v2H1v-2z"/>
          </svg>
          <span>Расстояние</span>
        </div>
        <p class="section-content">{{ formatDistance(distance) }}</p>
      </div>
      
      <!-- Rating -->
      <div v-if="locationInfo.rating && showRating" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M7 0l1.5 4.5h4.5l-3.5 2.5 1.5 4.5L7 9l-3.5 2.5 1.5-4.5L1 4.5h4.5L7 0z"/>
          </svg>
          <span>Рейтинг</span>
        </div>
        <div class="section-content rating-content">
          <div class="stars">
            <span 
              v-for="i in 5" 
              :key="i"
              class="star"
              :class="{ 'filled': i <= Math.floor(locationInfo.rating!) }"
            >
              ★
            </span>
          </div>
          <span class="rating-value">{{ locationInfo.rating.toFixed(1) }}</span>
          <span v-if="locationInfo.reviewCount" class="review-count">
            ({{ locationInfo.reviewCount }} отзывов)
          </span>
        </div>
      </div>
      
      <!-- Description -->
      <div v-if="locationInfo.description" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M1 2h12v2H1V2zm0 3h12v2H1V5zm0 3h8v2H1V8z"/>
          </svg>
          <span>Описание</span>
        </div>
        <p class="section-content description">{{ locationInfo.description }}</p>
      </div>
      
      <!-- Opening Hours -->
      <div v-if="locationInfo.openingHours && showOpeningHours" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <circle cx="7" cy="7" r="6" fill="none" stroke="currentColor" stroke-width="1"/>
            <path d="M7 3v4l3 2"/>
          </svg>
          <span>Режим работы</span>
        </div>
        <div class="section-content">
          <div 
            v-for="(hours, day) in locationInfo.openingHours"
            :key="day"
            class="opening-hours-item"
          >
            <span class="day">{{ getDayLabel(day) }}</span>
            <span class="hours">{{ hours }}</span>
          </div>
        </div>
      </div>
      
      <!-- Contact Info -->
      <div v-if="hasContactInfo" class="location-section">
        <div class="section-header">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M1 2h12v10H1V2zm1 1v8h10V3H2z"/>
          </svg>
          <span>Контакты</span>
        </div>
        <div class="section-content contact-info">
          <div v-if="locationInfo.phone" class="contact-item">
            <a :href="`tel:${locationInfo.phone}`" class="contact-link">
              <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                <path d="M2 0C1 0 0 1 0 2v8c0 1 1 2 2 2h8c1 0 2-1 2-2V2c0-1-1-2-2-2H2z"/>
              </svg>
              {{ locationInfo.phone }}
            </a>
          </div>
          <div v-if="locationInfo.website" class="contact-item">
            <a :href="locationInfo.website" target="_blank" class="contact-link">
              <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                <path d="M6 0a6 6 0 1 1 0 12A6 6 0 0 1 6 0zM2 6a4 4 0 1 0 8 0 4 4 0 0 0-8 0z"/>
              </svg>
              Сайт
            </a>
          </div>
        </div>
      </div>
      
      <!-- Custom Sections -->
      <div v-for="section in customSections" :key="section.id" class="location-section">
        <div class="section-header">
          <div v-if="section.icon" v-html="section.icon"></div>
          <span>{{ section.title }}</span>
        </div>
        <div class="section-content" v-html="section.content"></div>
      </div>
      
      <!-- Actions -->
      <div v-if="showActions" class="location-actions">
        <button 
          v-if="showDirectionsButton"
          class="action-button primary"
          @click="getDirections"
        >
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M1 4h3v1H1V4zm0 2h5v1H1V6zm0 2h4v1H1V8z"/>
          </svg>
          Построить маршрут
        </button>
        
        <button 
          v-if="showShareButton"
          class="action-button secondary"
          @click="shareLocation"
        >
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
            <path d="M10 8a2 2 0 1 1 .5 1.3L6.7 11a2 2 0 1 1-.5-1.3L9.5 8.3A2 2 0 0 1 10 8z"/>
          </svg>
          Поделиться
        </button>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="empty-container">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
          <path d="M24 4C14.05 4 6 12.05 6 22c0 15 18 22 18 22s18-7 18-22c0-9.95-8.05-18-18-18z"/>
        </svg>
      </div>
      <p class="empty-text">Выберите место на карте для просмотра информации</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

export interface LocationInfo {
  id?: string
  name: string
  type?: 'address' | 'poi' | 'city' | 'business' | 'park' | 'restaurant' | 'shop'
  address?: string
  description?: string
  rating?: number
  reviewCount?: number
  phone?: string
  website?: string
  openingHours?: Record<string, string>
  photos?: string[]
  features?: string[]
}

export interface CustomSection {
  id: string
  title: string
  content: string
  icon?: string
}

export interface LocationInfoProps {
  /** Координаты локации */
  coordinates?: [number, number]
  /** Информация о локации */
  locationInfo?: LocationInfo | null
  /** Состояние загрузки */
  isLoading?: boolean
  /** Ошибка загрузки */
  error?: string | null
  /** Расстояние до локации */
  distance?: number
  /** Показать координаты */
  showCoordinates?: boolean
  /** Показать расстояние */
  showDistance?: boolean
  /** Показать рейтинг */
  showRating?: boolean
  /** Показать режим работы */
  showOpeningHours?: boolean
  /** Показать кнопки действий */
  showActions?: boolean
  /** Показать кнопку маршрута */
  showDirectionsButton?: boolean
  /** Показать кнопку поделиться */
  showShareButton?: boolean
  /** Показать кнопку избранное */
  showFavoriteButton?: boolean
  /** В избранном */
  isFavorite?: boolean
  /** Дополнительные секции */
  customSections?: CustomSection[]
}

export interface LocationInfoEmits {
  (e: 'directions-click', coordinates: [number, number]): void
  (e: 'share-click', info: LocationInfo): void
  (e: 'favorite-toggle', info: LocationInfo, isFavorite: boolean): void
  (e: 'reload-click'): void
}

const props = withDefaults(defineProps<LocationInfoProps>(), {
  isLoading: false,
  error: null,
  showCoordinates: true,
  showDistance: true,
  showRating: true,
  showOpeningHours: true,
  showActions: true,
  showDirectionsButton: true,
  showShareButton: true,
  showFavoriteButton: false,
  isFavorite: false,
  customSections: () => []
})

const emit = defineEmits<LocationInfoEmits>()

// Computed
const hasContactInfo = computed(() => {
  return props.locationInfo?.phone || props.locationInfo?.website
})

// Methods
const loadLocationInfo = () => {
  emit('reload-click')
}

const getDirections = () => {
  if (props.coordinates) {
    emit('directions-click', props.coordinates)
  }
}

const shareLocation = () => {
  if (props.locationInfo) {
    emit('share-click', props.locationInfo)
  }
}

const toggleFavorite = () => {
  if (props.locationInfo) {
    emit('favorite-toggle', props.locationInfo, !props.isFavorite)
  }
}

const copyCoordinates = async () => {
  if (!props.coordinates) return
  
  const coordsText = `${props.coordinates[1]}, ${props.coordinates[0]}`
  
  try {
    await navigator.clipboard.writeText(coordsText)
    // Could emit success event or show toast
  } catch (err) {
    console.error('Failed to copy coordinates:', err)
  }
}

const formatCoordinates = (coords: [number, number]) => {
  const [lng, lat] = coords
  return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
}

const formatDistance = (distance: number): string => {
  if (distance < 1000) {
    return `${Math.round(distance)} м`
  }
  return `${(distance / 1000).toFixed(1)} км`
}

const getLocationTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    'address': 'Адрес',
    'poi': 'Достопримечательность',
    'city': 'Город',
    'business': 'Организация',
    'park': 'Парк',
    'restaurant': 'Ресторан',
    'shop': 'Магазин'
  }
  return labels[type] || type
}

const getDayLabel = (day: string): string => {
  const labels: Record<string, string> = {
    'monday': 'Пн',
    'tuesday': 'Вт',
    'wednesday': 'Ср',
    'thursday': 'Чт',
    'friday': 'Пт',
    'saturday': 'Сб',
    'sunday': 'Вс'
  }
  return labels[day] || day
}
</script>

<style scoped>
.location-info-widget {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  max-width: 400px;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  text-align: center;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f0f0f0;
  border-top: 3px solid var(--ozon-primary, #005bff);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: #666;
  font-size: 14px;
}

/* Error State */
.error-container {
  display: flex;
  align-items: center;
  padding: 16px;
  gap: 12px;
}

.error-icon {
  color: #e74c3c;
  flex-shrink: 0;
}

.error-content {
  flex: 1;
}

.error-title {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
  color: #e74c3c;
}

.error-message {
  margin: 0 0 8px 0;
  font-size: 13px;
  color: #666;
}

.retry-button {
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 4px 8px;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background: #c0392b;
}

/* Content */
.location-content {
  padding: 16px;
}

.location-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.location-icon {
  color: var(--ozon-primary, #005bff);
  flex-shrink: 0;
  margin-top: 2px;
}

.location-title-container {
  flex: 1;
  min-width: 0;
}

.location-title {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  line-height: 1.3;
}

.location-type {
  margin: 0;
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.favorite-button {
  background: none;
  border: none;
  color: #ccc;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: color 0.2s;
  flex-shrink: 0;
}

.favorite-button:hover {
  color: #ffc107;
}

.favorite-button.is-favorite {
  color: #ffc107;
}

/* Sections */
.location-section {
  margin-bottom: 16px;
}

.location-section:last-child {
  margin-bottom: 0;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-content {
  margin: 0;
  font-size: 14px;
  color: #333;
  line-height: 1.4;
}

.coordinates {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: monospace;
  font-size: 13px;
}

.copy-button {
  background: #f5f5f5;
  border: none;
  border-radius: 3px;
  padding: 4px;
  cursor: pointer;
  color: #666;
  transition: all 0.2s;
}

.copy-button:hover {
  background: #e0e0e0;
  color: #333;
}

.description {
  max-height: 120px;
  overflow-y: auto;
}

/* Rating */
.rating-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stars {
  display: flex;
  gap: 2px;
}

.star {
  color: #ddd;
  font-size: 14px;
}

.star.filled {
  color: #ffc107;
}

.rating-value {
  font-weight: 600;
  color: #333;
}

.review-count {
  color: #666;
  font-size: 12px;
}

/* Opening Hours */
.opening-hours-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2px 0;
  font-size: 13px;
}

.day {
  font-weight: 500;
  color: #666;
}

.hours {
  color: #333;
}

/* Contact Info */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.contact-item {
  display: flex;
  align-items: center;
}

.contact-link {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--ozon-primary, #005bff);
  text-decoration: none;
  font-size: 13px;
  transition: color 0.2s;
}

.contact-link:hover {
  color: var(--ozon-primary-dark, #0050e0);
}

/* Actions */
.location-actions {
  display: flex;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

.action-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  flex: 1;
  padding: 10px 12px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.action-button.primary {
  background: var(--ozon-primary, #005bff);
  color: white;
}

.action-button.primary:hover {
  background: var(--ozon-primary-dark, #0050e0);
}

.action-button.secondary {
  background: #f5f5f5;
  color: #666;
}

.action-button.secondary:hover {
  background: #e0e0e0;
  color: #333;
}

/* Empty State */
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
}

.empty-icon {
  margin-bottom: 16px;
  color: #ccc;
}

.empty-text {
  margin: 0;
  color: #999;
  font-size: 14px;
  line-height: 1.4;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .location-content {
    padding: 12px;
  }
  
  .location-header {
    gap: 10px;
    margin-bottom: 12px;
  }
  
  .location-title {
    font-size: 15px;
  }
  
  .location-section {
    margin-bottom: 12px;
  }
  
  .action-button {
    padding: 12px;
    font-size: 14px;
  }
}

/* Dark Theme */
@media (prefers-color-scheme: dark) {
  .location-info-widget {
    background: #2a2a2a;
  }
  
  .location-title,
  .section-content {
    color: white;
  }
  
  .location-type,
  .section-header {
    color: #ccc;
  }
  
  .loading-text {
    color: #ccc;
  }
  
  .copy-button {
    background: #444;
    color: #ccc;
  }
  
  .copy-button:hover {
    background: #555;
    color: white;
  }
  
  .action-button.secondary {
    background: #444;
    color: #ccc;
  }
  
  .action-button.secondary:hover {
    background: #555;
    color: white;
  }
  
  .location-actions {
    border-top-color: #444;
  }
  
  .empty-text {
    color: #999;
  }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    animation: none;
  }
  
  .action-button,
  .copy-button,
  .contact-link {
    transition: none;
  }
}
</style>