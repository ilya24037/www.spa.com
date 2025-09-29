<template>
  <Head title="Создание объявления" />
  
  <MainLayout>
    <div>
      <div class="max-w-4xl mx-auto py-6 lg:py-8">
        <!-- Хлебные крошки в стиле Avito -->
        <nav class="flex items-center mb-6" aria-label="Breadcrumb">
          <button 
            @click="goBack"
            class="flex items-center text-gray-500 hover:text-gray-700 transition-colors mr-4"
          >
            <svg class="w-5 h-5 mr-1"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"></path>
            </svg>
            Назад
          </button>
        
          <ol class="flex items-center space-x-2 text-sm">
            <li>
              <Link 
                href="/profile"
                class="text-gray-500 hover:text-gray-700 transition-colors"
              >
                Личный кабинет
              </Link>
            </li>
            <li class="text-gray-400">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
            </li>
            <li>
              <span class="text-gray-900 font-medium">Новое объявление</span>
            </li>
          </ol>
        </nav>
      
        <!-- Основной контент -->
        <div class="rounded-lg bg-transparent">
          <!-- Заголовок страницы -->
          <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-900">Создание нового объявления</h1>
            <p class="text-sm text-gray-600 mt-1">
              Заполните форму для размещения нового объявления. Все поля с * обязательны для заполнения.
            </p>
          </div>
        
          <!-- Форма создания -->
          <div class="p-6">
            <AdForm 
              :category="'erotic'"
              :categories="[]"
              :ad-id="null"
              :initial-data="{}"
              @success="handleSuccess"
              @cancel="handleCancel"
            />
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import AdForm from '@/src/features/ad-creation/ui/AdForm.vue'
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'

// Очищаем все старые данные из localStorage при монтировании
onMounted(() => {
  // Очищаем общий ключ
  localStorage.removeItem('adFormData')
  
  // Очищаем все ключи черновиков
  const keys = Object.keys(localStorage)
  keys.forEach(key => {
    if (key.startsWith('adFormData_draft_')) {
      localStorage.removeItem(key)
    }
  })
  
  // localStorage очищен для создания нового объявления
})

// Навигация
const goBack = () => {
  router.visit('/profile')
}

const handleSuccess = () => {
  router.visit('/profile/items/draft/all')
}

const handleCancel = () => {
  router.visit('/profile')
}
</script>