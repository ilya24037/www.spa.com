<template>
  <Head title="Оплата прошла успешно" />
  
  <div class="max-w-2xl mx-auto py-12 px-4 text-center">
    <!-- Иконка успеха -->
    <div class="mb-6">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto">
        <svg
          class="w-10 h-10 text-green-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
      </div>
    </div>

    <!-- Заголовок -->
    <h1 class="text-3xl font-bold text-gray-500 mb-4">
      Оплата прошла успешно!
    </h1>
    
    <!-- Информация о платеже -->
    <div class="bg-gray-500 p-6 rounded-lg mb-6 text-left max-w-md mx-auto">
      <div class="space-y-3">
        <div class="flex justify-between">
          <span class="text-gray-500">Номер платежа:</span>
          <span class="font-medium">{{ payment.payment_id }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Сумма:</span>
          <span class="font-medium">{{ payment.formatted_amount }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Дата оплаты:</span>
          <span class="font-medium">{{ payment.paid_at }}</span>
        </div>
      </div>
    </div>

    <!-- Информация об объявлении -->
    <div class="mb-8">
      <p class="text-lg text-gray-500 mb-2">
        Ваше объявление <strong>"{{ ad.title }}"</strong> успешно размещено!
      </p>
      <p class="text-gray-500">
        Объявление будет активно до <strong>{{ ad.expires_at }}</strong>
      </p>
    </div>

    <!-- Кнопки действий -->
    <div class="space-y-3">
      <Link 
        :href="route('my-ads.index')"
        class="inline-flex items-center justify-center w-full max-w-xs mx-auto px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
      >
        Перейти к моим объявлениям
      </Link>
      
      <Link 
        :href="route('home')"
        class="inline-flex items-center justify-center w-full max-w-xs mx-auto px-6 py-3 text-gray-500 hover:text-gray-500 transition-colors"
      >
        На главную
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

defineProps({
    payment: Object,
    ad: Object
})
</script> 