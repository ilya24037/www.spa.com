<template>
  <div class="promo-section">
    <h2 class="form-group-title">Акции</h2>
    <div class="promo-fields">
      <label>Скидка новым клиентам:
        <input type="text" v-model="localDiscount" @input="emitAll" />
      </label>
      <label>Подарок:
        <input type="text" v-model="localGift" @input="emitAll" />
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  newClientDiscount: { type: String, default: '' },
  gift: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:newClientDiscount', 'update:gift'])
const localDiscount = ref(props.newClientDiscount)
const localGift = ref(props.gift)
watch(() => props.newClientDiscount, val => { localDiscount.value = val })
watch(() => props.gift, val => { localGift.value = val })
const emitAll = () => {
  emit('update:newClientDiscount', localDiscount.value)
  emit('update:gift', localGift.value)
}
</script>

<style scoped>
.promo-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.promo-fields { display: flex; gap: 16px; align-items: center; }
</style> 