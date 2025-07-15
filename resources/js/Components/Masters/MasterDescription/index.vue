<template>
  <div class="master-description">
    <h2 class="section-title">О мастере</h2>
    
    <div class="description-content">
      <p>{{ master.bio || 'Описание пока не добавлено' }}</p>
    </div>
    
    <!-- Сертификаты -->
    <div v-if="master.certificates && master.certificates.length" class="certificates">
      <h3 class="subsection-title">Сертификаты</h3>
      <div class="certificates-grid">
        <div 
          v-for="cert in master.certificates" 
          :key="cert.id"
          class="certificate-item"
        >
          <img 
            :src="cert.image_url" 
            :alt="cert.name"
            class="certificate-img"
          >
          <span class="certificate-name">{{ cert.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.master-description {
  @apply bg-white rounded-lg p-6 shadow-sm;
}

.section-title {
  @apply text-xl font-bold text-gray-900 mb-4;
}

.description-content {
  @apply text-gray-700 leading-relaxed mb-6;
}

.subsection-title {
  @apply text-lg font-semibold text-gray-900 mb-3;
}

.certificates-grid {
  @apply grid grid-cols-2 md:grid-cols-3 gap-4;
}

.certificate-item {
  @apply text-center;
}

.certificate-img {
  @apply w-full h-32 object-cover rounded-lg mb-2;
}

.certificate-name {
  @apply text-sm text-gray-600;
}
</style> 