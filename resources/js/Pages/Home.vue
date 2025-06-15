<template>
  <div>
    <Head title="Массажисты в Москве - SPA.COM" />
    
    <div class="flex">
      <!-- Фильтры с защитой -->
      <ErrorBoundary 
        error-title="Ошибка загрузки фильтров"
        error-message="Фильтры временно недоступны"
        class="w-64 flex-shrink-0 border-r bg-white"
      >
        <aside class="w-full">
          <div class="p-4">
            <h2 class="text-lg font-semibold mb-4">Фильтры</h2>
            <Filters 
              :filters="filters" 
              :cities="cities"
              :categories="categories"
              :priceRange="priceRange"
              @update="updateFilters"
            />
          </div>
        </aside>
      </ErrorBoundary>
      
      <!-- Контент справа -->
      <main class="flex-1 min-w-0">
        <!-- Заголовок -->
        <div class="px-4 py-3 border-b">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-semibold">Массажисты в Москве</h1>
              <p class="text-sm text-gray-600 mt-0.5">
                {{ totalCount }} {{ pluralize(totalCount, ['объявление', 'объявления', 'объявлений']) }}
              </p>
            </div>
            
            <div class="flex items-center gap-3">
              <!-- Управление видом -->
              <ViewSwitcher v-model="showMap" />
              
              <!-- Сортировка -->
              <SortSelector v-model="sort" @change="updateFilters({ sort })" />
            </div>
          </div>
        </div>
        
        <!-- Карта с защитой -->
        <ErrorBoundary 
          v-if="showMap"
          error-title="Ошибка загрузки карты"
          error-message="Карта временно недоступна. Попробуйте переключиться на вид сеткой."
          class="h-[500px] border-b"
        >
          <Map :cards="masters?.data || cards" />
        </ErrorBoundary>
        
        <!-- Карточки с защитой -->
        <ErrorBoundary 
          error-title="Ошибка загрузки списка"
          error-message="Не удалось загрузить список мастеров"
        >
          <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <!-- Каждая карточка в своей защите -->
              <ErrorBoundary 
                v-for="master in masters?.data || cards" 
                :key="master.id"
                error-title=""
                error-message="Не удалось загрузить"
                :show-reload="false"
              >
                <MasterCard :master="master" />
              </ErrorBoundary>
            </div>
            
            <!-- Пагинация -->
            <ErrorBoundary 
              v-if="masters?.links && masters.last_page > 1"
              error-title="Ошибка пагинации"
              :show-reload="false"
            >
              <div class="mt-8">
                <Pagination :links="masters.links" />
              </div>
            </ErrorBoundary>
          </div>
        </ErrorBoundary>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ErrorBoundary from '@/Components/Common/ErrorBoundary.vue'
import MasterCard from '@/Components/Masters/MasterCard.vue'
import Map from '@/Components/Map/Map.vue' 
import Filters from '@/Components/Filters.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import ViewSwitcher from '@/Components/Common/ViewSwitcher.vue'
import SortSelector from '@/Components/Common/SortSelector.vue'

// ... остальной код
</script>