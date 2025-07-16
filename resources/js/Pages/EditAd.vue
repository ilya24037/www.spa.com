<template>
  <AppLayout>
    <Head title="Редактировать объявление" />
    
    <div class="py-6 lg:py-8">
      <div class="max-w-4xl mx-auto">
        <!-- Хлебные крошки -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
          <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
              <Link 
                href="/profile/items/active/all"
                class="text-gray-500 hover:text-gray-700 transition-colors"
              >
                Мои объявления
              </Link>
              <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
            </li>
            <li>
              <span class="text-gray-900 font-medium">Редактировать объявление</span>
            </li>
          </ol>
        </nav>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Редактировать объявление</h1>
          
          <!-- Используем общий компонент AdForm -->
          <AdForm 
            :category="ad.category || 'massage'"
            :categories="[]"
            :ad-id="ad.id"
            @success="handleSuccess"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdForm from '@/Components/Form/AdForm.vue'

const props = defineProps({
  ad: {
    type: Object,
    required: true
  }
})

const handleSuccess = () => {
  router.visit('/profile/items/active/all')
}
</script> 