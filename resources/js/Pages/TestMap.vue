<template>
    <Head title="Тест карты" />
    
    <div class="py-6 lg:py-8">
        <div class="mx-auto max-w-4xl px-4">
            <h1 class="text-2xl font-bold mb-6">Быстрый тест реальной карты</h1>
            
            <!-- Простая карта -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4">Простая карта (RealMap)</h2>
                <RealMap
                    :height="300"
                    :center="[58.0105, 56.2502]"
                    marker-text="SPA Platform - Пермь"
                />
            </div>
            
            <!-- Карта с мастерами -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4">Карта с мастерами (LeafletMap)</h2>
                <LeafletMap
                    :height="400"
                    :center="{ lat: 58.0105, lng: 56.2502 }"
                    :markers="masters"
                    @marker-click="handleMarkerClick"
                />
            </div>
            
            <!-- Результат клика -->
            <div v-if="selectedMaster" class="mt-4 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-blue-900">Выбран мастер:</h3>
                <p class="text-blue-800">{{ selectedMaster.title }}</p>
                <p class="text-blue-600 text-sm">{{ selectedMaster.description }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import RealMap from '@/Components/Map/RealMap.vue'
import LeafletMap from '@/src/features/map/ui/MapLegacy/LeafletMap.vue'

const selectedMaster = ref(null)

const masters = [
    {
        lat: 58.0105,
        lng: 56.2502,
        title: 'Анна Иванова',
        description: 'Классический массаж, 5 лет опыта',
        popup: '<b>Анна Иванова</b><br>Классический массаж<br>от 2000 ₽/час'
    },
    {
        lat: 58.0085,
        lng: 56.2580,
        title: 'Елена Петрова',
        description: 'Релаксирующий массаж, 3 года опыта',
        popup: '<b>Елена Петрова</b><br>Релаксирующий массаж<br>от 1800 ₽/час'
    },
    {
        lat: 58.0125,
        lng: 56.2420,
        title: 'Мария Сидорова',
        description: 'Лечебный массаж, 7 лет опыта',
        popup: '<b>Мария Сидорова</b><br>Лечебный массаж<br>от 2500 ₽/час'
    }
]

const handleMarkerClick = (marker) => {
    selectedMaster.value = marker
}
</script>