<template>
  <div class="promo-card">
    <div class="card-badge-wrapper">
      <div class="card-content">
        <a :href="card.link" class="card-link">
          <img :src="card.icon" :alt="card.title" class="card-icon">
          <div class="card-content-wrapper">
            <div class="card-main-content">
              <h5 class="card-title">{{ card.title }}</h5>
              <p class="card-description">{{ card.description }}</p>
            </div>
          </div>
        </a>
        <div v-if="card.badge" class="card-badge">
          <div class="badge-new">{{ card.badge }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  card: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.promo-card {
  @apply relative;
}

.card-badge-wrapper {
  @apply relative;
}

.card-content {
  @apply relative;
}

.card-link {
  @apply flex items-center gap-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 hover:shadow-md transition-all;
}

.card-icon {
  @apply w-16 h-16 flex-shrink-0 rounded-lg;
}

.card-content-wrapper {
  @apply flex-1;
}

.card-main-content {
  @apply space-y-1;
}

.card-title {
  @apply text-base font-semibold text-gray-900;
}

.card-description {
  @apply text-sm text-gray-600;
}

.card-badge {
  @apply absolute -top-2 -right-2;
}

.badge-new {
  @apply bg-red-500 text-white text-xs font-medium px-2 py-1 rounded-full shadow-sm;
}
</style> 