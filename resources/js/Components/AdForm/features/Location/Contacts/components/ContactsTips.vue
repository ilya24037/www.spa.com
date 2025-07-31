<template>
  <Card variant="bordered" class="bg-emerald-50 border-emerald-200">
    <div class="flex space-x-3">
      <div class="text-xl flex-shrink-0">💡</div>
      <div class="flex-1">
        <p class="font-semibold text-emerald-800 mb-2">
          Советы по контактам
        </p>
        <ul class="text-sm text-emerald-700 space-y-1">
          <li>• Указание нескольких способов связи увеличивает отклик на 25%</li>
          <li>• WhatsApp и Telegram удобны для быстрого общения</li>
          <li>• Скрытие номера повышает безопасность и фильтрует серьёзных клиентов</li>
          <li>• Статус "онлайн" показывает вашу активность</li>
        </ul>
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Cards/Card.vue'
</script>