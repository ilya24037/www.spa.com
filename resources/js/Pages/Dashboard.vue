<!-- resources/js/Pages/Dashboard.vue - FSD Refactored согласно плану 5.3 -->
<template>
  <ProfileLayout>
    <ProfileDashboard 
      :ads="ads"
      :counts="counts"
      :stats="userStats"
    />
  </ProfileLayout>
</template>

<script setup>
import { ProfileLayout } from '@/src/shared'
import { ProfileDashboard } from '@/src/widgets/profile-dashboard'

// Props из Inertia
defineProps({
  ads: {
    type: Array,
    default: () => []
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
  userStats: {
    type: Object,
    default: () => ({
      views: 0,
      calls: 0,
      bookings: 0,
      revenue: 0
    })
  }
})
</script>