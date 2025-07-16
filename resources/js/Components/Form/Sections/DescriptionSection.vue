<template>
  <PageSection title="Описание">
    <FormTextarea
      id="description"
      name="description"
      label="Описание"
      placeholder="Расскажите о своих услугах подробнее"
      v-model="form.description"
      :error="errors.description"
      :minlength="50"
      hint="Минимум 50 символов"
    />
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import FormTextarea from '@/Components/Form/FormTextarea.vue'

export default {
  name: 'DescriptionSection',
  components: {
    PageSection,
    FormTextarea
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
  }
}
</script> 