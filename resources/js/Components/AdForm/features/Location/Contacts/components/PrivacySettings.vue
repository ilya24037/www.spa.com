<template>
  <FormField
    label="Настройки конфиденциальности"
    hint="Как показывать ваши контакты в объявлении"
  >
    <!-- Используем готовые BaseCheckbox вместо кастомных -->
    <div class="space-y-4">
      <BaseCheckbox
        v-model="localHidePhone"
        label="Скрыть номер телефона"
        description="Номер будет показан только после запроса через сайт"
        @update:modelValue="updateHidePhone"
      />
      
      <BaseCheckbox
        v-model="localShowOnline"
        label="Показывать статус 'онлайн'"
        description="Клиенты увидят, когда вы активны на сайте"
        @update:modelValue="updateShowOnline"
      />
    </div>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  hidePhone: { type: Boolean, default: false },
  showOnline: { type: Boolean, default: true }
})

const emit = defineEmits(['update:hidePhone', 'update:showOnline'])

const localHidePhone = ref(props.hidePhone)
const localShowOnline = ref(props.showOnline)

// Отслеживание изменений пропсов
watch(() => props.hidePhone, (newValue) => {
  localHidePhone.value = newValue
})

watch(() => props.showOnline, (newValue) => {
  localShowOnline.value = newValue
})

// Методы обновления
const updateHidePhone = (value) => {
  emit('update:hidePhone', value)
}

const updateShowOnline = (value) => {
  emit('update:showOnline', value)
}
</script>