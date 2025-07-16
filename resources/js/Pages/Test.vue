<template>

    <Head title="Тест навигации" />
    
    <div class="py-8">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">
          Тестовая страница
        </h1>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 mb-4">
            Эта страница используется для тестирования навигации.
          </p>
          
          <div class="space-y-4">
            <div class="p-4 bg-blue-50 rounded-lg">
              <h3 class="font-semibold text-blue-900">Проверьте:</h3>
              <ul class="mt-2 text-blue-800 space-y-1">
                <li>• Навигация отображается только один раз</li>
                <li>• Нет дублирования шапки</li>
                <li>• Правильные отступы</li>
              </ul>
            </div>
            
            <div class="p-4 bg-green-50 rounded-lg">
              <h3 class="font-semibold text-green-900">Ссылки для тестирования:</h3>
              <div class="mt-2 space-x-4">
                <a href="/login" class="text-green-700 hover:text-green-900 underline">Страница логина</a>
                <a href="/register" class="text-green-700 hover:text-green-900 underline">Страница регистрации</a>
                <a href="/" class="text-green-700 hover:text-green-900 underline">Главная страница</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

</template>

<script setup>
import { Head } from '@inertiajs/vue3'

</script> 