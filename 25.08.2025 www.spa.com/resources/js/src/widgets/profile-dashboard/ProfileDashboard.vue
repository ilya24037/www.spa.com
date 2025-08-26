<template>
  <div class="profile-dashboard">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Stats -->
      <div class="lg:col-span-3">
        <ProfileStats :stats="stats" />
      </div>
      
      <!-- Tabs -->
      <div class="lg:col-span-3">
        <ProfileTabs />
      </div>
      
      <!-- Content -->
      <div class="lg:col-span-3">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import ProfileStats from '@/src/features/profile-navigation/ui/ProfileStats/ProfileStats.vue'
import ProfileTabs from '@/src/features/profile-navigation/ui/ProfileTabs/ProfileTabs.vue'

defineProps<{
  ads?: any[]
  counts?: any
  stats?: any
}>()

defineEmits<{
  'stats-loading': []
  'data-loaded': []
}>()
</script>