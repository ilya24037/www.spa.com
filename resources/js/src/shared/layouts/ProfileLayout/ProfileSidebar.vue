<!-- resources/js/src/shared/layouts/ProfileLayout/ProfileSidebar.vue -->
<template>
    <SidebarWrapper 
        v-model="showSidebar"
        content-class="p-0"
        :show-desktop-header="false"
        :always-visible-desktop="true"
    >
        <!-- Профиль пользователя -->
        <UserProfile :user-stats="userStats" />
        
        <!-- Навигация -->
        <ProfileNavigation :counts="counts" />
    </SidebarWrapper>
</template>

<script setup>
import { ref } from 'vue'
import SidebarWrapper from '../components/SidebarWrapper.vue'
import UserProfile from '@/Components/Profile/UserProfile.vue'
import ProfileNavigation from '@/Components/Profile/ProfileNavigation.vue'

// Props
defineProps({
    counts: {
        type: Object,
        default: () => ({})
    },
    userStats: {
        type: Object,
        default: () => ({})
    }
})

// Состояние для мобильного меню
const showSidebar = ref(false)
</script>