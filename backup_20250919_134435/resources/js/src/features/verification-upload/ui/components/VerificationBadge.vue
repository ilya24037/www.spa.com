<template>
  <div class="verification-badge">
    <!-- Активный бейдж -->
    <div 
      v-if="badge.status === 'verified'"
      class="badge verified"
      :title="badgeTooltip"
    >
      <svg class="badge-icon" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
      <span class="badge-text">{{ badge.text }}</span>
      <span v-if="badge.days_left !== null && badge.days_left <= 30" class="badge-days">
        {{ badge.days_left }}д
      </span>
    </div>
    
    <!-- Истекающий бейдж -->
    <div 
      v-else-if="badge.status === 'expiring'"
      class="badge expiring"
      :title="badgeTooltip"
    >
      <svg class="badge-icon animate-pulse" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
      </svg>
      <span class="badge-text">Истекает</span>
      <span class="badge-days">{{ badge.days_left }}д</span>
    </div>
    
    <!-- На проверке -->
    <div 
      v-else-if="badge.status === 'pending'"
      class="badge pending"
      :title="'На модерации'"
    >
      <svg class="badge-icon animate-spin" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
      </svg>
      <span class="badge-text">На проверке</span>
    </div>
    
    <!-- Требует обновления -->
    <div 
      v-else-if="badge.needs_update"
      class="badge needs-update"
      :title="'Требуется обновление верификации'"
    >
      <svg class="badge-icon" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      <span class="badge-text">Обновить</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { VerificationBadge } from '../../model/types'

interface Props {
  badge: VerificationBadge
}

const props = defineProps<Props>()

const badgeTooltip = computed(() => {
  if (props.badge.status === 'verified') {
    if (props.badge.expires_at) {
      const expiresDate = new Date(props.badge.expires_at).toLocaleDateString('ru-RU')
      if (props.badge.days_left !== null && props.badge.days_left <= 30) {
        return `Фото проверено. Истекает ${expiresDate} (осталось ${props.badge.days_left} дней)`
      }
      return `Фото проверено до ${expiresDate}`
    }
    return 'Фото проверено'
  }
  
  if (props.badge.status === 'expiring') {
    return `Срок верификации истекает через ${props.badge.days_left} дней`
  }
  
  return ''
})
</script>

<style scoped>
.verification-badge {
  @apply inline-flex items-center;
}

.badge {
  @apply inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium;
  @apply transition-all duration-200 cursor-help;
}

.badge:hover {
  @apply transform scale-105;
}

.badge-icon {
  @apply w-4 h-4 flex-shrink-0;
}

.badge-text {
  @apply whitespace-nowrap;
}

.badge-days {
  @apply px-1.5 py-0.5 rounded-full bg-white bg-opacity-30 font-bold;
}

/* Верифицирован */
.badge.verified {
  @apply bg-green-100 text-green-800 border border-green-200;
}

.badge.verified .badge-icon {
  @apply text-green-600;
}

/* Истекает */
.badge.expiring {
  @apply bg-yellow-100 text-yellow-800 border border-yellow-200;
}

.badge.expiring .badge-icon {
  @apply text-yellow-600;
}

/* На проверке */
.badge.pending {
  @apply bg-blue-100 text-blue-800 border border-blue-200;
}

.badge.pending .badge-icon {
  @apply text-blue-600;
}

/* Требует обновления */
.badge.needs-update {
  @apply bg-red-100 text-red-800 border border-red-200;
}

.badge.needs-update .badge-icon {
  @apply text-red-600;
}

/* Анимация для иконки на проверке */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 2s linear infinite;
}
</style>