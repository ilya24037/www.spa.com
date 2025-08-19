<template>
  <Head title="Создать анкету мастера" />
    
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-500 mb-8">
        Создать анкету мастера
      </h1>
            
      <form class="space-y-6" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-2">
            Имя
          </label>
          <input 
            v-model="form.name"
            type="text" 
            class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
            required
          >
        </div>
                
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-2">
            Специализация
          </label>
          <input 
            v-model="form.specialization"
            type="text" 
            class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
            required
          >
        </div>
                
        <div class="flex gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-500 mb-2">
              Возраст
            </label>
            <input 
              v-model="form.age"
              type="number" 
              class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
              required
            >
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-500 mb-2">
              Рост (см)
            </label>
            <input 
              v-model="form.height"
              type="number" 
              class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
              required
            >
          </div>
        </div>
                
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-2">
            Цена за час
          </label>
          <input 
            v-model="form.pricePerHour"
            type="number" 
            class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
            required
          >
        </div>
                
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-2">
            Телефон
          </label>
          <input 
            v-model="form.phone"
            v-maska="'+7 (###) ###-##-##'"
            type="tel" 
            class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
            placeholder="+7 (999) 999-99-99"
            required
          >
        </div>
                
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-2">
            Описание
          </label>
          <textarea 
            v-model="form.description"
            rows="4"
            class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
            required
          />
        </div>
                
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-2">
            Услуги (через запятую)
          </label>
          <input 
            v-model="form.services"
            type="text" 
            class="w-full px-3 py-2 border border-gray-500 rounded-md focus:ring-blue-500 focus:border-blue-500"
            placeholder="Классический массаж, Релакс массаж"
            required
          >
        </div>
                
        <div class="flex justify-end gap-4">
          <Link 
            href="/"
            class="px-4 py-2 bg-gray-500 text-gray-500 rounded-md hover:bg-gray-500 transition"
          >
            Отмена
          </Link>
          <button 
            type="submit"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Создание...' : 'Создать' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { route } from 'ziggy-js'

import { Head, Link, useForm } from '@inertiajs/vue3'
import { vMaska } from 'maska/vue'

const form = useForm({
    name: '',
    specialization: '',
    age: '',
    height: '',
    pricePerHour: '',
    phone: '',
    description: '',
    services: ''
})

const submit = () => {
    form.post(route('masters.store'))
}
</script>