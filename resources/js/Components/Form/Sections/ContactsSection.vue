<template>
  <PageSection title="Контакты">
    <FormInput
      id="address"
      name="address"
      label="Адрес"
      placeholder="Введите адрес"
      v-model="form.address"
      :error="errors.address"
    />

    <FormInput
      id="travel_area"
      name="travel_area"
      label="Район выезда"
      placeholder="Укажите район"
      v-model="form.travel_area"
      :error="errors.travel_area"
    />

    <FormInput
      id="phone"
      name="phone"
      label="Телефон"
      placeholder="+7 (___) ___-__-__"
      v-model="form.phone"
      :error="errors.phone"
    />

    <FormRadio
      label="Способ связи"
      name="contact_method"
      :options="contactMethodOptions"
      v-model="form.contact_method"
      :error="errors.contact_method"
    />
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import FormInput from '@/Components/Form/FormInput.vue'
import FormRadio from '@/Components/Form/FormRadio.vue'
import { contactMethodOptions } from '@/utils/formOptions'

export default {
  name: 'ContactsSection',
  components: {
    PageSection,
    FormInput,
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
      contactMethodOptions
    }
  }
}
</script> 