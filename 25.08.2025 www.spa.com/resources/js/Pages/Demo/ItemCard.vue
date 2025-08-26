<template>
  <div class="min-h-screen bg-gray-500 py-8">
    <div class="max-w-6xl mx-auto px-4">
      <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h1 class="text-3xl font-bold text-gray-500 mb-4">
            Демонстрация компонента ItemCard
          </h1>
          <p class="text-gray-500 mb-6">
            Компонент карточки услуги в стиле Avito для раздела "Мои объявления"
          </p>
          
          <div class="flex gap-4">
            <a 
              href="/dashboard" 
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Перейти к Dashboard
            </a>
            <button 
              class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
              @click="addRandomItem"
            >
              Добавить случайную карточку
            </button>
            <button 
              class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
              @click="clearItems"
            >
              Очистить все
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <AdCard 
            v-for="item in items" 
            :key="item.id"
            :ad="item"
            @item-deleted="handleItemDeleted"
          />
        
          <!-- Пустое состояние -->
          <div v-if="items.length === 0" class="text-center py-12 bg-white rounded-lg">
            <div class="text-gray-500 mb-4">
              <svg
                class="w-16 h-16 mx-auto"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-500 mb-2">
              Нет карточек для демонстрации
            </h3>
            <p class="text-gray-500 mb-4">
              Добавьте карточки, чтобы увидеть, как они выглядят
            </p>
            <button 
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
              @click="loadSampleItems"
            >
              Загрузить примеры
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
// 🎯 FSD Импорты
import AdCard from '@/src/entities/ad/ui/AdCard/AdCard.vue'

const items = ref([])

const sampleItems = [
    {
        id: 1,
        name: "Расслабляющий массаж всего тела",
        description: "Профессиональный расслабляющий массаж с использованием натуральных масел. Поможет снять стресс и напряжение после тяжелого дня.",
        price_from: 2500,
        avatar: "/images/masters/demo-1.jpg",
        photos_count: 4,
        company_name: "Массажный салон 'Релакс'",
        address: "Москва, ул. Тверская, 12",
        district: "Центральный район",
        home_service: true,
        status: "active",
        views_count: 47,
        subscribers_count: 3,
        favorites_count: 12,
        new_messages_count: 2,
        expires_at: "2024-02-15T12:00:00Z"
    },
    {
        id: 2,
        name: "Антицеллюлитный массаж",
        description: "Эффективный антицеллюлитный массаж для коррекции фигуры. Используются специальные техники и косметические средства.",
        price_from: 3000,
        avatar: "/images/masters/demo-2.jpg",
        photos_count: 6,
        company_name: "Студия красоты 'Афродита'",
        address: "Санкт-Петербург, Невский пр., 45",
        district: "Центральный район",
        home_service: false,
        status: "active",
        views_count: 89,
        subscribers_count: 7,
        favorites_count: 23,
        new_messages_count: 0,
        expires_at: "2024-01-20T12:00:00Z"
    },
    {
        id: 3,
        name: "Лечебный массаж спины",
        description: "Лечебный массаж для устранения болей в спине и улучшения осанки. Проводится квалифицированным специалистом.",
        price_from: 1800,
        avatar: "/images/masters/demo-3.jpg",
        photos_count: 2,
        company_name: "Медицинский центр 'Здоровье'",
        address: "Екатеринбург, ул. Ленина, 78",
        district: "Ленинский район",
        home_service: true,
        status: "paused",
        views_count: 156,
        subscribers_count: 15,
        favorites_count: 34,
        new_messages_count: 5,
        expires_at: "2024-01-10T12:00:00Z"
    },
    {
        id: 4,
        name: "Спортивный массаж для спортсменов",
        description: "Профессиональный спортивный массаж для восстановления после тренировок и соревнований. Подходит для всех видов спорта.",
        price_from: 4000,
        avatar: "/images/masters/demo-4.jpg",
        photos_count: 8,
        company_name: "Спортивный клуб 'Олимп'",
        address: "Новосибирск, пр. Ленина, 156",
        district: "Центральный район",
        home_service: true,
        status: "active",
        views_count: 234,
        subscribers_count: 28,
        favorites_count: 67,
        new_messages_count: 8,
        expires_at: "2024-03-01T12:00:00Z"
    },
    {
        id: 5,
        name: "Детский массаж",
        price_from: 1200,
        avatar: "/images/masters/elena1.jpg",
        photos_count: 3,
        company_name: "Детский центр 'Малыш'",
        address: "Казань, ул. Баумана, 34",
        district: "Вахитовский район",
        home_service: true,
        status: "active",
        views_count: 78,
        subscribers_count: 5,
        favorites_count: 18,
        new_messages_count: 1,
        expires_at: "2024-02-28T12:00:00Z"
    },
    {
        id: 6,
        name: "Массаж для беременных",
        price_from: 2200,
        avatar: "/images/masters/elena2.jpg",
        photos_count: 5,
        company_name: "Центр материнства 'Мама и малыш'",
        address: "Краснодар, ул. Красная, 67",
        district: "Центральный район",
        home_service: true,
        status: "active",
        views_count: 123,
        subscribers_count: 12,
        favorites_count: 45,
        new_messages_count: 3,
        expires_at: "2024-02-20T12:00:00Z"
    }
]

const loadSampleItems = () => {
    items.value = [...sampleItems]
}

const addRandomItem = () => {
    const randomItem = sampleItems[Math.floor(Math.random() * sampleItems.length)]
    const newItem = {
        ...randomItem,
        id: Date.now() + Math.random(),
        views_count: Math.floor(Math.random() * 300) + 10,
        subscribers_count: Math.floor(Math.random() * 50),
        favorites_count: Math.floor(Math.random() * 100),
        new_messages_count: Math.floor(Math.random() * 10)
    }
    items.value.push(newItem)
}

const clearItems = () => {
    items.value = []
}

const handleItemDeleted = (itemId) => {
    items.value = items.value.filter(item => item.id !== itemId)
}

// Загружаем примеры при монтировании
loadSampleItems()
</script> 