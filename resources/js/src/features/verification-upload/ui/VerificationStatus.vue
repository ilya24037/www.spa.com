<template>
  <div class="verification-status mt-4">
    <!-- Статус: На проверке -->
    <div v-if="status === 'pending'" class="status-card bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
          <svg class="w-6 h-6 text-yellow-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-yellow-800 mb-1">На модерации</h3>
          <p class="text-sm text-yellow-700">
            Ваши файлы отправлены на проверку. Обычно это занимает от 30 минут до 2 часов.
          </p>
          <p class="text-xs text-yellow-600 mt-2">
            Вы получите уведомление о результате проверки
          </p>
        </div>
      </div>
    </div>
    
    <!-- Статус: Подтверждено -->
    <div v-else-if="status === 'verified'" class="status-card bg-green-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
          <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-green-800 mb-1">Фото проверено</h3>
          <p class="text-sm text-green-700">
            Ваши фотографии успешно прошли проверку
          </p>
          
          <!-- Информация о сроке действия -->
          <div v-if="expiresAt" class="mt-3 p-2 bg-white rounded border border-green-200">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Действует до:</span>
              <span class="text-sm font-medium text-gray-800">{{ formatDate(expiresAt) }}</span>
            </div>
            <div v-if="daysLeft !== null" class="mt-1">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Осталось дней:</span>
                <span 
                  class="text-sm font-medium"
                  :class="getDaysLeftClass(daysLeft)"
                >
                  {{ daysLeft }}
                </span>
              </div>
              
              <!-- Прогресс бар -->
              <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-300"
                  :class="getProgressBarClass(daysLeft)"
                  :style="{ width: getProgressWidth(daysLeft) + '%' }"
                ></div>
              </div>
            </div>
          </div>
          
          <!-- Дата верификации -->
          <div v-if="verifiedAt" class="mt-2 text-xs text-green-600">
            Проверено: {{ formatDate(verifiedAt) }}
          </div>
        </div>
      </div>
      
      <!-- Предупреждение об истечении срока -->
      <div v-if="daysLeft !== null && daysLeft <= 30" 
           class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
        <div class="flex items-start gap-2">
          <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="text-sm font-medium text-yellow-800">
              Срок верификации истекает
            </p>
            <p class="text-xs text-yellow-700 mt-1">
              Рекомендуем обновить проверочные фото заранее, чтобы не потерять статус верификации
            </p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Статус: Отклонено -->
    <div v-else-if="status === 'rejected'" class="status-card bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
          <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-red-800 mb-1">Проверка не пройдена</h3>
          
          <!-- Причина отклонения -->
          <div v-if="comment" class="mt-2 p-3 bg-white rounded border border-red-200">
            <p class="text-sm font-medium text-gray-700 mb-1">Причина отклонения:</p>
            <p class="text-sm text-red-600">{{ comment }}</p>
          </div>
          
          <!-- Рекомендации -->
          <div class="mt-3">
            <p class="text-sm font-medium text-gray-700 mb-2">Что нужно исправить:</p>
            <ul class="text-sm text-gray-600 space-y-1">
              <li v-for="tip in getRejectionTips(comment)" :key="tip" class="flex items-start gap-1">
                <span class="text-red-500">•</span>
                <span>{{ tip }}</span>
              </li>
            </ul>
          </div>
          
          <p class="text-sm text-gray-600 mt-3">
            Загрузите новые файлы, соответствующие требованиям
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  status: string
  comment?: string | null
  expiresAt?: string | null
  verifiedAt?: string | null
}

const props = defineProps<Props>()

// Вычисляемые свойства
const daysLeft = computed(() => {
  if (!props.expiresAt) return null
  
  const expires = new Date(props.expiresAt)
  const now = new Date()
  const diff = expires.getTime() - now.getTime()
  const days = Math.ceil(diff / (1000 * 60 * 60 * 24))
  
  return days > 0 ? days : 0
})

// Методы
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const getDaysLeftClass = (days: number): string => {
  if (days <= 7) return 'text-red-600'
  if (days <= 30) return 'text-yellow-600'
  return 'text-green-600'
}

const getProgressBarClass = (days: number): string => {
  if (days <= 7) return 'bg-red-500'
  if (days <= 30) return 'bg-yellow-500'
  return 'bg-green-500'
}

const getProgressWidth = (days: number): number => {
  // Максимум 120 дней (4 месяца)
  const maxDays = 120
  return Math.min((days / maxDays) * 100, 100)
}

const getRejectionTips = (comment: string | null | undefined): string[] => {
  if (!comment) {
    return [
      'Убедитесь, что лицо хорошо видно',
      'Проверьте качество освещения',
      'Дата должна быть написана четко и разборчиво',
      'Используйте актуальную дату'
    ]
  }
  
  const tips: string[] = []
  const lowerComment = comment.toLowerCase()
  
  if (lowerComment.includes('лицо') || lowerComment.includes('face')) {
    tips.push('Лицо должно быть полностью видно и не закрыто')
  }
  
  if (lowerComment.includes('дата') || lowerComment.includes('date')) {
    tips.push('Напишите сегодняшнюю дату четко и разборчиво')
    tips.push('Формат даты: число месяц год')
  }
  
  if (lowerComment.includes('качество') || lowerComment.includes('quality')) {
    tips.push('Улучшите качество фото/видео')
    tips.push('Обеспечьте хорошее освещение')
  }
  
  if (lowerComment.includes('листок') || lowerComment.includes('paper')) {
    tips.push('Листок с датой должен быть хорошо виден')
    tips.push('Текст на листке должен быть написан от руки')
  }
  
  if (lowerComment.includes('старое') || lowerComment.includes('old')) {
    tips.push('Используйте актуальные фото, сделанные сегодня')
  }
  
  if (tips.length === 0) {
    // Если не удалось определить конкретные проблемы
    tips.push('Внимательно прочитайте инструкции')
    tips.push('Следуйте всем требованиям к верификации')
    tips.push('При необходимости обратитесь в поддержку')
  }
  
  return tips
}
</script>

<style scoped>
.status-card {
  @apply transition-all duration-300;
}

.status-card:hover {
  @apply shadow-md;
}
</style>