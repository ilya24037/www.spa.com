<template>
  <Head title="Редактирование объявления" />
  
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-6 lg:py-8">
      <!-- Хлебные крошки в стиле Avito -->
      <nav class="flex items-center mb-6" aria-label="Breadcrumb">
        <button 
          @click="goBack"
          class="flex items-center text-gray-500 hover:text-gray-700 transition-colors mr-4"
        >
          <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Назад
        </button>
        
        <ol class="flex items-center space-x-2 text-sm">
          <li>
            <Link 
              :href="getBackUrl()"
              class="text-gray-500 hover:text-gray-700 transition-colors"
            >
              Мои объявления
            </Link>
          </li>
          <li class="text-gray-400">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
          </li>
          <li>
            <span class="text-gray-900 font-medium">Редактирование объявления</span>
          </li>
        </ol>
      </nav>
      
      <!-- Основной контент -->
      <div class="bg-white rounded-lg shadow-sm">
        <!-- Заголовок страницы -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-bold text-gray-900">Редактирование объявления</h1>
          <p class="text-sm text-gray-600 mt-1">
            Внесите изменения в ваше объявление. Все поля с * обязательны для заполнения.
          </p>
        </div>
        
        <!-- Форма редактирования -->
        <div class="p-6">
          <AdForm 
            :category="actualAd.category || 'erotic'"
            :categories="[]"
            :ad-id="actualAd.id"
            :initial-data="actualAd"
            @success="handleSuccess"
            @cancel="handleCancel"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AdForm from '@/src/features/ad-creation/ui/AdForm.vue'

interface Ad {
  id: number | string
  title?: string
  name?: string
  description?: string
  category?: string
  status?: string
  price?: number
  location?: string
  photos?: any[]
  services?: any[]
  [key: string]: any
}

interface EditAdProps {
  ad: Ad
}

const props = defineProps<EditAdProps>()

// Props успешно принимаются и обрабатываются
// ВАЖНО: Данные могут быть вложены в ключ 'data' если используется AdResource
const actualAd = props.ad?.data || props.ad

// ВРЕМЕННОЕ ЛОГИРОВАНИЕ для диагностики
console.log('🔍 Edit.vue: props.ad структура:', {
  hasData: !!props.ad?.data,
  directId: props.ad?.id,
  dataId: props.ad?.data?.id,
  actualAdId: actualAd?.id,
  actualAdStatus: actualAd?.status,
  fullProp: props.ad
})

// Навигация
const getBackUrl = () => {
  const status = actualAd.status || 'draft'
  return status === 'draft' ? '/profile/items/draft/all' : '/profile/items/active/all'
}

const goBack = () => {
  router.visit(getBackUrl())
}

const handleSuccess = () => {
  router.visit(getBackUrl())
}

const handleCancel = () => {
  router.visit(getBackUrl())
}
</script>