<template>
  <div class="master-detail-card" :class="{ 'compact': compact }">
    <!-- Шапка с фото и основной информацией -->
    <div class="master-header">
      <div class="master-photo">
        <img 
          :src="master.photo || '/images/default-avatar.jpg'" 
          :alt="master.name"
          class="photo-img"
        >
      </div>
      
      <div class="master-info">
        <h3 class="master-name">{{ master.name }}</h3>
        
        <!-- Рейтинг и отзывы -->
        <div v-if="master.rating" class="master-rating">
          <span class="rating-stars">
            <span v-for="i in 5"
                  :key="i"
                  class="star"
                  :class="{ 'filled': i <= Math.floor(master.rating) }">
              ★
            </span>
          </span>
          <span class="rating-value">{{ master.rating }}</span>
          <span class="reviews-count">({{ master.reviews_count || 0 }} отзывов)</span>
        </div>
        
        <!-- Адрес -->
        <div v-if="master.address" class="master-address">
          <svg class="icon"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ master.address }}
        </div>
        
        <!-- Цена -->
        <div v-if="master.price" class="master-price">
          от <span class="price-value">{{ master.price }} ₽</span>
        </div>
      </div>
    </div>
    
    <!-- Услуги -->
    <div v-if="master.services && master.services.length > 0" class="master-services">
      <h4 class="section-title">Услуги</h4>
      <div class="services-list">
        <span 
          v-for="service in master.services" 
          :key="service.id"
          class="service-tag"
        >
          {{ service.name }}
        </span>
      </div>
    </div>
    
    <!-- Описание (если не компактный режим) -->
    <div v-if="!compact && master.description" class="master-description">
      <h4 class="section-title">О мастере</h4>
      <p class="description-text">{{ master.description }}</p>
    </div>
    
    <!-- Действия -->
    <div class="master-actions">
      <PrimaryButton
        @click="$emit('book', master)"
        class="action-button"
      >
        Записаться
      </PrimaryButton>
      
      <SecondaryButton
        v-if="!compact"
        @click="$emit('view-profile', master)"
        class="action-button"
      >
        Подробнее
      </SecondaryButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'

interface Props {
  master: any
  compact?: boolean
}

defineProps<Props>()

defineEmits<{
  'book': [master: any]
  'view-profile': [master: any]
}>()
</script>

<style scoped>
.master-detail-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
}

.master-detail-card.compact {
  padding: 20px;
}

.master-header {
  display: flex;
  gap: 20px;
  margin-bottom: 24px;
}

.master-photo {
  flex-shrink: 0;
  width: 100px;
  height: 100px;
  border-radius: 12px;
  overflow: hidden;
}

.photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.master-info {
  flex: 1;
  min-width: 0;
}

.master-name {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 8px 0;
}

.master-rating {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.rating-stars {
  display: flex;
  gap: 2px;
}

.star {
  color: #d1d5db;
  font-size: 16px;
}

.star.filled {
  color: #fbbf24;
}

.rating-value {
  font-weight: 600;
  color: #111827;
}

.reviews-count {
  color: #6b7280;
  font-size: 14px;
}

.master-address {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 8px;
}

.icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.master-price {
  font-size: 18px;
  color: #111827;
}

.price-value {
  font-weight: 600;
  color: #3b82f6;
}

.master-services {
  margin-bottom: 20px;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #6b7280;
  margin: 0 0 12px 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.services-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.service-tag {
  display: inline-block;
  padding: 6px 12px;
  background: #f3f4f6;
  border-radius: 6px;
  font-size: 13px;
  color: #4b5563;
}

.master-description {
  margin-bottom: 20px;
}

.description-text {
  color: #4b5563;
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}

.master-actions {
  display: flex;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.action-button {
  flex: 1;
}

/* Компактный режим */
.compact .master-header {
  margin-bottom: 16px;
}

.compact .master-photo {
  width: 80px;
  height: 80px;
}

.compact .master-services {
  margin-bottom: 16px;
}

.compact .master-actions {
  padding-top: 16px;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .master-detail-card {
    padding: 16px;
  }
  
  .master-header {
    flex-direction: column;
    text-align: center;
  }
  
  .master-photo {
    margin: 0 auto;
  }
  
  .master-address {
    justify-content: center;
  }
  
  .master-actions {
    flex-direction: column;
  }
}
</style>