<template>
  <FormField
    label="Предоплата"
    hint="Требуете ли вы предоплату от клиентов"
  >
    <!-- Используем готовые BaseRadio вместо кастомных -->
    <div class="space-y-3">
      <BaseRadio
        v-model="localType"
        value="none"
        label="Без предоплаты"
        description="Оплата после оказания услуги"
      />
      
      <BaseRadio
        v-model="localType"
        value="partial"
        label="Частичная предоплата"
        description="Часть суммы заранее, остальное после"
      />
      
      <BaseRadio
        v-model="localType"
        value="full"
        label="Полная предоплата"
        description="100% оплата до начала сеанса"
      />
    </div>

    <!-- Детали предоплаты -->
    <Card v-if="localType !== 'none'" variant="bordered" class="mt-4 p-4">
      <!-- Размер предоплаты для частичной -->
      <div v-if="localType === 'partial'" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Размер предоплаты:
        </label>
        <div class="flex items-center space-x-2">
          <BaseInput
            v-model="localAmount"
            type="number"
            min="10"
            max="90"
            step="5"
            placeholder="30"
            suffix="%"
            class="w-20"
          />
        </div>
      </div>
      
      <!-- Комментарий к предоплате -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Комментарий к предоплате:
        </label>
        <BaseTextarea
          v-model="localNote"
          rows="2"
          placeholder="Например: предоплата через СБП, возврат при отмене за 24 часа..."
          maxlength="200"
          show-counter
        />
      </div>
    </Card>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseRadio from '@/Components/UI/BaseRadio.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  type: { type: String, default: 'none' },
  amount: { type: String, default: '30' },
  note: { type: String, default: '' }
})

const emit = defineEmits(['update:type', 'update:amount', 'update:note'])

const localType = ref(props.type)
const localAmount = ref(props.amount)
const localNote = ref(props.note)

// Синхронизация с родителем
watch(() => props.type, (newValue) => { localType.value = newValue })
watch(() => props.amount, (newValue) => { localAmount.value = newValue })
watch(() => props.note, (newValue) => { localNote.value = newValue })

watch(localType, (newValue) => emit('update:type', newValue))
watch(localAmount, (newValue) => emit('update:amount', newValue))
watch(localNote, (newValue) => emit('update:note', newValue))
</script>