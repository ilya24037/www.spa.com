<template>
  <div class="section-stub">
    <h2 class="form-group-title">{{ sectionName }}</h2>
    <div class="stub-content">
      <p>🚧 Секция "{{ sectionName }}" в разработке</p>
      <p>Компонент будет создан позже</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  sectionName: { type: String, default: 'Секция' },
  errors: { type: Object, default: () => ({}) }
})
</script>

<style scoped>
.section-stub { 
  background: #fff3cd; 
  border: 1px solid #ffeaa7; 
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
  color: #856404;
  font-style: italic;
}
</style>
