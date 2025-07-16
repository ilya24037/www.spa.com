<template>
  <PageSection title="Цена">
    <div class="price-group">
      <FormInput
        id="price"
        name="price"
        label="Цена"
        type="number"
        placeholder="0"
        v-model="form.price"
        :error="errors.price"
        postfix="₽"
      />
      
      <FormSelect
        id="price_unit"
        name="price_unit"
        label="За что"
        v-model="form.price_unit"
        :options="priceUnitOptions"
      />
    </div>

    <FormCheckbox
      label="Цена от"
      name="is_starting_price"
      :options="[{value: '1', label: 'Цена от'}]"
      v-model="form.is_starting_price"
    />

    <FormInput
      id="discount"
      name="discount"
      label="Скидка"
      type="number"
      placeholder="0"
      v-model="form.discount"
      :error="errors.discount"
      postfix="%"
      hint="Необязательно"
    />

    <FormInput
      id="gift"
      name="gift"
      label="Подарок"
      placeholder="Например, консультация в подарок"
      v-model="form.gift"
      hint="Необязательно"
    />
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import FormInput from '@/Components/Form/FormInput.vue'
import FormSelect from '@/Components/Form/FormSelect.vue'
import FormCheckbox from '@/Components/Form/FormCheckbox.vue'
import { priceUnitOptions } from '@/utils/formOptions'

export default {
  name: 'PriceSection',
  components: {
    PageSection,
    FormInput,
    FormSelect,
    FormCheckbox
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
      priceUnitOptions
    }
  }
}
</script>

<style scoped>
.price-group {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 16px;
  align-items: end;
}

@media (max-width: 768px) {
  .price-group {
    grid-template-columns: 1fr;
  }
}
</style> 