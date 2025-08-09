<template>
  <Head title="Новое объявление — Объявления на сайте Massagist" />
  
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Кнопка назад -->
      <Link href="/" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span class="text-sm">Назад</span>
      </Link>

      <!-- Заголовок как у Avito -->
      <h1 class="text-2xl font-normal text-gray-900 mb-2">Новое объявление</h1>
      
      <!-- Подзаголовок -->
      <div class="text-sm text-gray-500 mb-8">
        <span>Предложение услуг</span>
        <span class="mx-1">›</span>
        <span>Красота</span>
        <span class="mx-1">›</span>
        <span>Эротический массаж</span>
      </div>

      <!-- Модульная форма -->
      <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
          <AdForm 
            category="erotic"
            :categories="categories"
            :initial-data="initialData"
            @success="handleSuccess"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AdForm from '@/Components/Form/AdForm.vue'

// Категории (только эротический массаж)
const categories = [
  { id: 'erotic', name: 'Эротический массаж' }
]

// Начальные данные для формы
const initialData = {
  category: 'erotic',
  specialty: 'erotic_massage'
}

// Обработчик успешного создания
const handleSuccess = (response) => {
  // Перенаправляем на страницу созданного объявления
  if (response.ad && response.ad.id) {
    router.visit(`/ads/${response.ad.id}`)
  } else {
    router.visit('/my-ads')
  }
}
</script> 
