<template>
  <PageSection title="Акции">
    <p class="section-description">
      Клиенты увидят информацию о скидках и подарках в объявлении.
    </p>
    
    <div class="promo-fields">
      <PriceInput
        id="discount"
        name="discount"
        label="Скидка новым клиентам"
        placeholder="0"
        v-model="form.discount"
        :error="errors.discount"
        :show-unit="false"
        postfix="%"
        hint="Укажите процент скидки для привлечения новых клиентов"
      />

      <FormInput
        id="gift"
        name="gift"
        label="Подарок"
        placeholder="Что и на каких условиях дарите"
        v-model="form.gift"
        :error="errors.gift"
        hint="Можно не заполнять, если у вас нет такой акции"
      />

      <FormInput
        id="promo_code"
        name="promo_code"
        label="Промокод"
        placeholder="Введите промокод"
        v-model="form.promo_code"
        :error="errors.promo_code"
        hint="Специальный код для скидки (опционально)"
      />

      <FormCheckbox
        name="has_special_offers"
        v-model="form.has_special_offers"
        :options="[{ value: '1', label: 'У меня есть специальные предложения' }]"
      />

      <div v-if="form.has_special_offers.includes('1')" class="special-offers">
        <FormTextarea
          id="special_offers_description"
          name="special_offers_description"
          label="Описание специальных предложений"
          placeholder="Опишите ваши специальные предложения..."
          v-model="form.special_offers_description"
          :error="errors.special_offers_description"
          hint="Например: «При заказе от 3 сеансов - скидка 15%», «День рождения - скидка 20%»"
        />
      </div>
    </div>
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import PriceInput from '@/Components/Form/Controls/PriceInput.vue'
import FormInput from '@/Components/Form/FormInput.vue'
import FormCheckbox from '@/Components/Form/FormCheckbox.vue'
import FormTextarea from '@/Components/Form/FormTextarea.vue'

export default {
  name: 'PromoSection',
  components: {
    PageSection,
    PriceInput,
    FormInput,
    FormCheckbox,
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

<style scoped>
.section-description {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
  line-height: 1.5;
}

.promo-fields {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.special-offers {
  margin-top: 8px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}
</style> 