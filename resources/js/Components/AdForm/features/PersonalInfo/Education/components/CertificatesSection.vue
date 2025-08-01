<template>
  <div class="space-y-4">
    <h4 class="text-lg font-medium text-gray-900">Сертификаты</h4>
    
    <BaseCheckbox
      :model-value="hasCertificates"
      @update:model-value="$emit('update:has-certificates', $event)"
      label="У меня есть сертификаты"
      description="Загрузите фотографии сертификатов для подтверждения квалификации"
    />

    <div v-if="hasCertificates" class="mt-4">
      <FormField
        label="Фотографии сертификатов"
        hint="Загрузите до 5 фотографий ваших сертификатов"
        :error="errors.certificate_photos"
      >
        <PhotoUploader
          :model-value="certificatePhotos"
          @update:model-value="$emit('update:certificate-photos', $event)"
          :max-files="5"
          :max-file-size="5242880"
          accept="image/*"
          multiple
        />
      </FormField>
    </div>
  </div>
</template>

<script setup>
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import PhotoUploader from '@/Components/Form/Upload/PhotoUploader.vue'

const props = defineProps({
  hasCertificates: {
    type: Boolean,
    default: false
  },
  certificatePhotos: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

defineEmits(['update:has-certificates', 'update:certificate-photos'])
</script>