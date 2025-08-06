<!-- resources/js/Components/Filters/Filters.vue -->
<template>
  <div class="space-y-6">
    <!-- Р¦РµРЅР° -->
    <div>
      <h4 class="font-medium mb-3">РЎС‚РѕРёРјРѕСЃС‚СЊ</h4>
      <div class="space-y-3">
        <div>
          <label class="text-sm text-gray-600">РћС‚</label>
          <input 
            v-model.number="localFilters.price_from"
            type="number"
            placeholder="0"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="updateFilter('price_from', localFilters.price_from)"
          >
        </div>
        <div>
          <label class="text-sm text-gray-600">Р”Рѕ</label>
          <input 
            v-model.number="localFilters.price_to"
            type="number"
            placeholder="10000"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="updateFilter('price_to', localFilters.price_to)"
          >
        </div>
      </div>
    </div>

    <!-- РљР°С‚РµРіРѕСЂРёРё СѓСЃР»СѓРі -->
    <div>
      <h4 class="font-medium mb-3">Р’РёРґ РјР°СЃСЃР°Р¶Р°</h4>
      <div class="space-y-2">
        <label 
          v-for="category in categories"
          :key="category.id"
          class="flex items-center cursor-pointer hover:text-blue-600"
        >
          <input 
            type="checkbox"
            :value="category.id"
            v-model="localFilters.categories"
            @change="updateFilter('categories', localFilters.categories)"
            class="mr-2 rounded text-blue-600 focus:ring-blue-500"
          >
          <span class="text-sm">{{ category.name }}</span>
          <span class="ml-auto text-xs text-gray-500">{{ category.masters_count }}</span>
        </label>
      </div>
    </div>

    <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РїР°СЂР°РјРµС‚СЂС‹ -->
    <div>
      <h4 class="font-medium mb-3">Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕ</h4>
      <div class="space-y-2">
        <label class="flex items-center cursor-pointer">
          <input 
            type="checkbox"
            v-model="localFilters.home_service"
            @change="updateFilter('home_service', localFilters.home_service)"
            class="mr-2 rounded text-blue-600 focus:ring-blue-500"
          >
          <span class="text-sm">Р’С‹РµР·Рґ РЅР° РґРѕРј</span>
        </label>
        
        <label class="flex items-center cursor-pointer">
          <input 
            type="checkbox"
            v-model="localFilters.online_booking"
            @change="updateFilter('online_booking', localFilters.online_booking)"
            class="mr-2 rounded text-blue-600 focus:ring-blue-500"
          >
          <span class="text-sm">РћРЅР»Р°Р№РЅ-Р·Р°РїРёСЃСЊ</span>
        </label>
        
        <label class="flex items-center cursor-pointer">
          <input 
            type="checkbox"
            v-model="localFilters.certificates"
            @change="updateFilter('certificates', localFilters.certificates)"
            class="mr-2 rounded text-blue-600 focus:ring-blue-500"
          >
          <span class="text-sm">Р•СЃС‚СЊ СЃРµСЂС‚РёС„РёРєР°С‚С‹</span>
        </label>
      </div>
    </div>

    <!-- Р РµР№С‚РёРЅРі -->
    <div>
      <h4 class="font-medium mb-3">Р РµР№С‚РёРЅРі</h4>
      <div class="space-y-2">
        <label 
          v-for="rating in [4, 3, 2]"
          :key="rating"
          class="flex items-center cursor-pointer"
        >
          <input 
            type="radio"
            :value="rating"
            v-model="localFilters.min_rating"
            @change="updateFilter('min_rating', localFilters.min_rating)"
            class="mr-2"
          >
          <div class="flex items-center">
            <svg 
              v-for="i in 5"
              :key="i"
              class="w-4 h-4"
              :class="i <= rating ? 'text-yellow-400' : 'text-gray-300'"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span class="ml-1 text-sm">Рё РІС‹С€Рµ</span>
          </div>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  filters: Object,
  categories: Array,
  priceRange: Object
})

const emit = defineEmits(['update'])

// Р›РѕРєР°Р»СЊРЅР°СЏ РєРѕРїРёСЏ С„РёР»СЊС‚СЂРѕРІ
const localFilters = ref({
  price_from: props.filters?.price_from || null,
  price_to: props.filters?.price_to || null,
  categories: props.filters?.categories || [],
  home_service: props.filters?.home_service || false,
  online_booking: props.filters?.online_booking || false,
  certificates: props.filters?.certificates || false,
  min_rating: props.filters?.min_rating || null
})

// РћР±РЅРѕРІР»РµРЅРёРµ С„РёР»СЊС‚СЂР°
const updateFilter = (key, value) => {
  emit('update', { [key]: value })
}

// РЎРёРЅС…СЂРѕРЅРёР·Р°С†РёСЏ СЃ РїСЂРѕРїСЃР°РјРё
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...localFilters.value, ...newFilters }
}, { deep: true })
</script>

