<!-- resources/js/Pages/AddItem.vue - FSD Refactored -->
<template>
  <Head title="Новое объявление — Объявления на сайте Massagist" />
  
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      
      <!-- Навигация назад -->
      <BackButton 
        href="/" 
        text="Назад"
        class="mb-6"
      />

      <!-- Заголовок -->
      <div class="mb-8">
        <h1 class="text-2xl font-normal text-gray-900 mb-2">Новое объявление</h1>
        
        <!-- Хлебные крошки -->
        <Breadcrumbs
          :items="[
            { title: 'Предложение услуг' },
            { title: 'Красота' },
            { title: 'Эротический массаж' }
          ]"
          separator="›"
          class="text-sm"
        />
      </div>

      <!-- FSD Entity: Форма объявления -->
      <div class="bg-white rounded-lg shadow-sm">
        <AdForm 
          category="erotic"
          :categories="categories"
          :initial-data="initialData"
          @success="handleSuccess"
          @draft-saved="handleDraftSaved"
        />
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'

// 🎯 FSD Импорты
import Breadcrumbs from '@/src/shared/ui/molecules/Breadcrumbs/Breadcrumbs.vue'
import BackButton from '@/src/shared/ui/atoms/BackButton/BackButton.vue'
import AdForm from '@/src/entities/ad/ui/AdForm/AdForm.vue'

// Категории (только эротический массаж)
const categories = [
  { id: 'erotic', name: 'Эротический массаж' }
]

// Начальные данные для формы
const initialData = {
  category: 'erotic',
  specialty: 'erotic_massage'
}

// Обработчики событий
const handleSuccess = (response) => {
  
  // Перенаправляем на страницу созданного объявления
  if (response.ad && response.ad.id) {
    router.visit(`/ads/${response.ad.id}`)
  } else {
    router.visit('/my-ads')
  }
}

const handleDraftSaved = (draftData) => {
  // Можно показать уведомление
}
</script> 