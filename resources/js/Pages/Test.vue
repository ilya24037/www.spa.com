<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">
          Тестовая страница
        </h1>
        <p class="text-gray-600 mb-6">
          Эта страница используется для тестирования базовой функциональности приложения.
        </p>
        
        <div class="space-y-4">
          <div>
            <h2 class="text-lg font-semibold mb-2">Навигация:</h2>
            <div class="flex gap-4">
              <a href="/" class="text-blue-600 hover:text-blue-800">Главная</a>
              <a href="/dashboard" class="text-blue-600 hover:text-blue-800">Dashboard</a>
              <a href="/demo/item-card" class="text-blue-600 hover:text-blue-800">Демо ItemCard</a>
            </div>
          </div>
          
          <div>
            <h2 class="text-lg font-semibold mb-2">Статус:</h2>
            <div class="text-green-600">✓ Vue.js работает</div>
            <div class="text-green-600">✓ Inertia.js работает</div>
            <div class="text-green-600">✓ Tailwind CSS работает</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
</script> 