<template>
  <!-- ProfileLayout: Layout для личного кабинета -->
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">
      <div class="flex gap-6">
        <!-- Боковая панель профиля -->
        <div class="w-80 flex-shrink-0">
          <ProfileSidebar 
            :user="user"
            :counts="counts"
            :active-tab="activeTab"
            @tab-change="handleTabChange"
          />
        </div>

        <!-- Основной контент -->
        <div class="flex-1 min-w-0">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import ProfileSidebar from './ProfileSidebar.vue'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  counts: {
    type: Object,
    default: () => ({
      ads: 0,
      bookings: 0,
      reviews: 0,
      favorites: 0
    })
  },
  activeTab: {
    type: String,
    default: 'ads'
  }
})

// Events
const emit = defineEmits(['tab-change'])

// Methods
const handleTabChange = (tab) => {
  emit('tab-change', tab)
}
</script>

<style scoped>
/* ProfileLayout стили */
</style>


