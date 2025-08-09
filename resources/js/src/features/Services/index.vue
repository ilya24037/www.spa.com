<template>
  <div class="services-module">
    <h2 class="form-group-title">Услуги</h2>
    <div class="stub-content">
      <p>🚧 Модуль "Услуги" в разработке</p>
      <p>Будет перенесён из старой архитектуры</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  services: { type: Array, default: () => [] },
  servicesAdditionalInfo: { type: String, default: '' },
  allowedCategories: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:services', 'update:servicesAdditionalInfo'])
</script>

<style scoped>
.services-module { 
  background: #e1f5fe; 
  border: 1px solid #0288d1; 
  border-radius: 8px; 
  padding: 20px; 
}
.form-group-title { 
  font-size: 18px; 
  font-weight: 600; 
  color: #333; 
  margin-bottom: 16px; 
}
.stub-content { 
  color: #01579b; 
  font-style: italic; 
}
</style>
