<template>
  <Card variant="bordered" class="bg-emerald-50 border-emerald-200">
    <div class="flex space-x-3">
      <div class="text-xl flex-shrink-0">💡</div>
      <div class="flex-1">
        <p class="font-semibold text-emerald-800 mb-2">
          Советы по выбору места работы
        </p>
        <ul class="text-sm text-emerald-700 space-y-1">
          <li>• Выезд к клиенту увеличивает количество заказов на 40%</li>
          <li>• Работа у себя позволяет создать комфортную атмосферу</li>
          <li>• Салон дает дополнительную безопасность и престиж</li>
          <li>• Несколько вариантов расширяют клиентскую базу</li>
          <li>• Отели популярны у деловых клиентов</li>
        </ul>
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Cards/Card.vue'
</script>