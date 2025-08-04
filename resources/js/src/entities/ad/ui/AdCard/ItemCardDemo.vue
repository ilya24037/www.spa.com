<template>
  <div class="p-8 bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Карточки услуг в стиле Avito</h1>
      
      <div class="space-y-4">
        <ItemCard 
          v-for="item in sampleItems" 
          :key="item.id"
          :item="item"
          @item-deleted="handleItemDeleted"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ItemCard from './ItemCard.vue'

const sampleItems = ref([
  {
    id: 1,
    name: "Расслабляющий массаж всего тела",
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
  }
])

const handleItemDeleted = (itemId) => {
  sampleItems.value = sampleItems.value.filter(item => item.id !== itemId)
}
</script> 