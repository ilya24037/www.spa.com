<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Место оказания услуг
    </label>
    <div class="space-y-3">
      <label class="flex items-center">
        <input type="radio" name="service_location" value="master_place" class="mr-2" />
        <span>У мастера</span>
      </label>
      <label class="flex items-center">
        <input type="radio" name="service_location" value="client_place" class="mr-2" />
        <span>У клиента</span>
      </label>
      <label class="flex items-center">
        <input type="radio" name="service_location" value="both" class="mr-2" />
        <span>Любое место</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ 
  errors?: any 
}>()
</script>