<template>
  <PageSection title="Подробности">
    <BaseInput
      v-model="form.title"
      label="Название объявления"
      placeholder="Введите название"
      :error="errors.title"
      hint="Например, «Маникюр, педикюр и наращивание ногтей» или «Ремонт квартир под ключ»"
    />

    <BaseSelect
      v-model="form.specialty"
      label="Специальность или сфера"
      placeholder="Выберите специальность"
      :options="specialtyOptions"
      :error="errors.specialty"
    />

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Клиенты</label>
        <div class="space-y-2">
          <BaseCheckbox
            v-for="option in clientOptions"
            :key="option.value"
            v-model="form.clients"
            :value="option.value"
            :label="option.label"
          />
        </div>
        <div v-if="errors.clients" class="mt-1 text-sm text-red-600">{{ errors.clients }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Место оказания услуги</label>
        <div class="space-y-2">
          <BaseCheckbox
            v-for="option in serviceLocationOptions"
            :key="option.value"
            v-model="form.service_location"
            :value="option.value"
            :label="option.label"
          />
        </div>
        <div v-if="errors.service_location" class="mt-1 text-sm text-red-600">{{ errors.service_location }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Формат работы</label>
        <div class="space-y-2">
          <BaseRadio
            v-for="option in workFormatOptions"
            :key="option.value"
            v-model="form.work_format"
            :value="option.value"
            :label="option.label"
          />
        </div>
        <div v-if="errors.work_format" class="mt-1 text-sm text-red-600">{{ errors.work_format }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Кто оказывает услугу</label>
        <div class="space-y-2">
          <BaseCheckbox
            v-for="option in serviceProviderOptions"
            :key="option.value"
            v-model="form.service_provider"
            :value="option.value"
            :label="option.label"
          />
        </div>
        <div v-if="errors.service_provider" class="mt-1 text-sm text-red-600">{{ errors.service_provider }}</div>
      </div>
    </div>

    <BaseSelect
      v-model="form.experience"
      label="Опыт работы"
      placeholder="Выберите опыт"
      :options="experienceOptions"
      :error="errors.experience"
    />
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import BaseRadio from '@/Components/UI/BaseRadio.vue'
import {
  specialtyOptions,
  clientOptions,
  serviceLocationOptions,
  workFormatOptions,
  serviceProviderOptions,
  experienceOptions
} from '@/utils/formOptions'

export default {
  name: 'DetailsSection',
  components: {
    PageSection,
    BaseInput,
    BaseSelect,
    BaseCheckbox,
    BaseRadio
  },
  props: {
    form: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      specialtyOptions,
      clientOptions,
      serviceLocationOptions,
      workFormatOptions,
      serviceProviderOptions,
      experienceOptions
    }
  }
}
</script> 