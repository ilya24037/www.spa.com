<template>
  <Card variant="bordered" class="bg-emerald-50 border-emerald-200">
    <div class="flex space-x-3">
      <div class="text-xl flex-shrink-0">💡</div>
      <div class="flex-1">
        <p class="font-semibold text-emerald-800 mb-2">
          Советы по составлению расписания
        </p>
        <ul class="text-sm text-emerald-700 space-y-1">
          <li>• Указание четкого графика увеличивает доверие клиентов на 45%</li>
          <li>• Вечерние часы (18:00-22:00) популярны у работающих клиентов</li>
          <li>• Выходные дни приносят до 30% больше заказов</li>
          <li>• Укажите перерывы в дополнительной информации</li>
          <li>• Обновляйте график в праздничные дни</li>
        </ul>
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Cards/Card.vue'
</script>