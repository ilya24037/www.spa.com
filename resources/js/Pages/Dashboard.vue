<!-- resources/js/Pages/Dashboard.vue - FSD Refactored -->
<template>
  <AppLayout>
    <Head title="Личный кабинет" />
    
    <!-- FSD Widget: ProfileDashboard -->
    <ProfileDashboard 
      :user="user"
      :initial-tab="activeTab"
      :counts="counts"
    />
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

// Layout (используем существующий AppLayout вместо ProfileLayout)
import AppLayout from '@/Layouts/AppLayout.vue'

// 🎯 FSD Импорты согласно плану
import { ProfileDashboard } from '@/src/widgets/profile-dashboard'

// Props из Inertia
defineProps({
  user: {
    type: Object,
    required: true,
    default: () => ({
      id: null,
      name: 'Пользователь',
      email: '',
      is_master: false,
      avatar: null
    })
  },
  activeTab: {
    type: String,
    default: 'ads'
  },
  counts: {
    type: Object,
    default: () => ({
      ads: 0,
      bookings: 0,
      reviews: 0,
      favorites: 0,
      waiting: 0,
      active: 0,
      drafts: 0,
      archived: 0
    })
  },
  // Сохраняем для обратной совместимости
  profiles: {
    type: Array,
    default: () => []
  },
  userStats: {
    type: Object,
    default: () => ({})
  }
})
</script>