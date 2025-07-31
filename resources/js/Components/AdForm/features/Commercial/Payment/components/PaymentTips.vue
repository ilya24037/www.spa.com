<template>
  <Card variant="bordered" class="bg-emerald-50 border-emerald-200">
    <div class="flex space-x-3">
      <div class="text-xl flex-shrink-0">💡</div>
      <div class="flex-1">
        <p class="font-semibold text-emerald-800 mb-2">
          Советы по способам оплаты
        </p>
        <ul class="text-sm text-emerald-700 space-y-1">
          <li>• Наличные остаются самым популярным способом (60% клиентов)</li>
          <li>• СБП и карты удобны для молодой аудитории</li>
          <li>• Предоплата снижает количество отмен на 40%</li>
          <li>• Несколько способов оплаты увеличивают конверсию</li>
        </ul>
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Cards/Card.vue'
</script>