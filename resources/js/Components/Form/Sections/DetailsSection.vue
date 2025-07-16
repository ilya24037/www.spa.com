<template>
  <PageSection title="Подробности">
    <FormInput
      id="title"
      name="title"
      label="Название объявления"
      placeholder="Введите название"
      v-model="form.title"
      :error="errors.title"
      hint="Например, «Маникюр, педикюр и наращивание ногтей» или «Ремонт квартир под ключ»"
    />

    <FormSelect
      id="specialty"
      name="specialty"
      label="Специальность или сфера"
      placeholder="Выберите специальность"
      v-model="form.specialty"
      :options="specialtyOptions"
      :error="errors.specialty"
    />

    <FormCheckbox
      label="Клиенты"
      name="clients"
      :options="clientOptions"
      v-model="form.clients"
      :error="errors.clients"
    />

    <FormCheckbox
      label="Место оказания услуги"
      name="service_location"
      :options="serviceLocationOptions"
      v-model="form.service_location"
      :error="errors.service_location"
    />

    <FormRadio
      label="Формат работы"
      name="work_format"
      :options="workFormatOptions"
      v-model="form.work_format"
      :error="errors.work_format"
    />

    <FormCheckbox
      label="Кто оказывает услугу"
      name="service_provider"
      :options="serviceProviderOptions"
      v-model="form.service_provider"
      :error="errors.service_provider"
    />

    <FormSelect
      id="experience"
      name="experience"
      label="Опыт работы"
      placeholder="Выберите опыт"
      v-model="form.experience"
      :options="experienceOptions"
      :error="errors.experience"
    />
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import FormInput from '@/Components/Form/FormInput.vue'
import FormSelect from '@/Components/Form/FormSelect.vue'
import FormCheckbox from '@/Components/Form/FormCheckbox.vue'
import FormRadio from '@/Components/Form/FormRadio.vue'
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
    FormInput,
    FormSelect,
    FormCheckbox,
    FormRadio
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