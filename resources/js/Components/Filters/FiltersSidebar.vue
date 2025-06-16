<!-- resources/js/Components/Filters/FiltersSidebar.vue -->
<template>
  <SidebarWrapper :sticky-top="110">
    <h3 class="font-semibold mb-4">Фильтры</h3>
    <slot />
    <button 
      v-if="hasActiveFilters"
      @click="$emit('reset')"
      class="w-full mt-4 text-sm text-blue-600"
    >
      Сбросить фильтры
    </button>
  </SidebarWrapper>
</template>

<!-- resources/js/Components/Profile/ProfileSidebar.vue -->
<template>
  <SidebarWrapper :sticky-top="80">
    <div class="text-center mb-6">
      <img :src="user.avatar" class="w-20 h-20 rounded-full mx-auto mb-3">
      <h3 class="font-semibold">{{ user.name }}</h3>
      <div class="text-sm text-gray-500">{{ user.rating }} ⭐</div>
    </div>
    <slot />
  </SidebarWrapper>
</template>