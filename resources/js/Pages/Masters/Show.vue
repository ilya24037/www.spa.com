<!-- resources/js/Pages/Masters/Show.vue - FSD Refactored -->
<template>
  <MainLayout>
    <MasterProfile :master="master" />
  </MainLayout>
</template>

<script setup>
import { MainLayout } from '@/shared/layouts'
import { MasterProfile } from '@/widgets/master-profile'

defineProps({
  master: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
/* Стили страницы мастера */
</style>