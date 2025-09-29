<template>
  <div class="verification-section">
    <!-- Заголовок секции -->
    <div 
      class="section-header cursor-pointer select-none"
      @click="toggleSection"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <svg 
            class="w-5 h-5 transition-transform duration-200"
            :class="{ 'rotate-90': isOpen }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"></path>
          </svg>
          
          <h2 class="text-lg font-semibold">
            Подтверждение фотографий
            <span class="text-sm font-normal text-gray-500 ml-2">(рекомендуем)</span>
          </h2>
        </div>
        
        <!-- Badge верификации -->
        <VerificationBadge 
          v-if="verificationData?.badge"
          :badge="verificationData.badge"
        />
      </div>
    </div>
    
    <!-- Содержимое секции -->
    <div v-show="isOpen" class="section-content mt-4">
      <!-- Предупреждение -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-blue-800">
              Проверочные фотографии предназначены только для внутреннего использования.
              Внимательно прочтите условия для получения значка "Фото проверено"!
            </p>
          </div>
        </div>
      </div>
      
      <!-- Табы для выбора способа -->
      <div class="tabs mb-6">
        <div class="tab-list flex gap-2 border-b">
          <button
            @click="activeTab = 'photo'"
            :class="[
              'tab-button px-4 py-2 font-medium transition-colors',
              activeTab === 'photo' 
                ? 'text-blue-600 border-b-2 border-blue-600' 
                : 'text-gray-600 hover:text-gray-800'
            ]"
          >
            📸 Фото с листком
          </button>
          <button
            @click="activeTab = 'video'"
            :class="[
              'tab-button px-4 py-2 font-medium transition-colors',
              activeTab === 'video' 
                ? 'text-blue-600 border-b-2 border-blue-600' 
                : 'text-gray-600 hover:text-gray-800'
            ]"
          >
            🎥 Видео с датой
          </button>
        </div>
        
        <!-- Содержимое табов -->
        <div class="tab-content mt-4">
          <!-- Фото верификация -->
          <div v-show="activeTab === 'photo'">
            <VerificationPhotoUpload
              :ad-id="adId"
              :current-photo="verificationPhoto"
              :status="verificationData?.status"
              @uploaded="handlePhotoUploaded"
              @deleted="handlePhotoDeleted"
            />
          </div>
          
          <!-- Видео верификация -->
          <div v-show="activeTab === 'video'">
            <VerificationVideoUpload
              :ad-id="adId"
              :current-video="verificationVideo"
              :status="verificationData?.status"
              @uploaded="handleVideoUploaded"
              @deleted="handleVideoDeleted"
            />
          </div>
        </div>
      </div>
      
      <!-- Статус верификации -->
      <VerificationStatus
        v-if="verificationData?.status && verificationData.status !== 'none'"
        :status="verificationData.status"
        :comment="verificationData.comment"
        :expires-at="verificationData.expires_at"
        :verified-at="verificationData.verified_at"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { VerificationData } from '../model/types'
import { verificationApi } from '../api/verificationApi'
import VerificationPhotoUpload from './VerificationPhotoUpload.vue'
import VerificationVideoUpload from './VerificationVideoUpload.vue'
import VerificationStatus from './VerificationStatus.vue'
import VerificationBadge from './components/VerificationBadge.vue'

interface Props {
  adId: number
  verificationPhoto?: string | null
  verificationVideo?: string | null
  verificationStatus?: string
  verificationComment?: string | null
  verificationExpiresAt?: string | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:verificationPhoto': [value: string | null]
  'update:verificationVideo': [value: string | null]
  'update:verificationStatus': [value: string]
}>()

// Состояние
const isOpen = ref(false)
const activeTab = ref<'photo' | 'video'>('photo')
const verificationData = ref<VerificationData | null>(null)

// Методы
const toggleSection = () => {
  isOpen.value = !isOpen.value
}

const loadVerificationStatus = async () => {
  try {
    verificationData.value = await verificationApi.getStatus(props.adId)
  } catch (error) {
    console.error('Failed to load verification status:', error)
  }
}

const handlePhotoUploaded = (path: string) => {
  emit('update:verificationPhoto', path)
  emit('update:verificationStatus', 'pending')
  loadVerificationStatus()
}

const handlePhotoDeleted = () => {
  emit('update:verificationPhoto', null)
  if (!props.verificationVideo) {
    emit('update:verificationStatus', 'none')
  }
  loadVerificationStatus()
}

const handleVideoUploaded = (path: string) => {
  emit('update:verificationVideo', path)
  emit('update:verificationStatus', 'pending')
  loadVerificationStatus()
}

const handleVideoDeleted = () => {
  emit('update:verificationVideo', null)
  if (!props.verificationPhoto) {
    emit('update:verificationStatus', 'none')
  }
  loadVerificationStatus()
}

// При монтировании
onMounted(() => {
  if (props.adId) {
    loadVerificationStatus()
  }
  
  // Открываем секцию если есть верификация
  if (props.verificationStatus && props.verificationStatus !== 'none') {
    isOpen.value = true
  }
})
</script>

<style scoped>
.verification-section {
  @apply bg-white rounded-lg border border-gray-200 p-4 mb-4;
}

.section-header {
  @apply py-2;
}

.section-header:hover {
  @apply bg-gray-50 -mx-4 px-4 rounded-lg;
}

.tab-button {
  @apply relative pb-2;
}

.tab-button:focus {
  @apply outline-none;
}
</style>