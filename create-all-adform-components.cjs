// create-all-adform-components.cjs
const fs = require('fs');
const path = require('path');

const baseDir = 'resources/js/src/entities/ad/ui/AdForm/components';

const components = [
  'AdFormDescription',
  'AdFormAge',
  'AdFormHeight',
  'AdFormWeight',
  'AdFormBust',
  'AdFormHair',
  'AdFormPricing',
  'AdFormDuration',
  'AdFormAddress',
  'AdFormMetro',
  'AdFormPhone',
  'AdFormWhatsApp',
  'AdFormTelegram',
  'AdFormWorkDays',
  'AdFormWorkHours',
  'AdFormVideoUpload',
  'AdFormTitle'
];

components.forEach(name => {
  const filePath = path.join(baseDir, `${name}.vue`);
  
  if (!fs.existsSync(filePath)) {
    const content = `<template>
  <div class="${name.toLowerCase()}">
    <label class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>
    <input
      type="text"
      v-model="localValue"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      :placeholder="placeholder"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  modelValue?: string | number
  label?: string
  placeholder?: string
}>(), {
  label: '${name.replace('AdForm', '')}',
  placeholder: 'Введите значение'
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const localValue = computed({
  get: () => props.modelValue || '',
  set: (value) => emit('update:modelValue', value)
})
</script>`;
    
    fs.writeFileSync(filePath, content);
    console.log(`✨ Created: ${name}`);
  }
});

console.log('\n✅ All AdForm components created!');