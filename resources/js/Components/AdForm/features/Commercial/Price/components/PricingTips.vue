<template>
  <Card variant="bordered" class="bg-amber-50 border-amber-200">
    <div class="flex space-x-3">
      <div class="text-xl flex-shrink-0">💡</div>
      <div class="flex-1">
        <p class="font-semibold text-amber-800 mb-3">
          Советы по эффективному ценообразованию
        </p>
        
        <div class="space-y-3 text-sm text-amber-700">
          <!-- Статистика цен -->
          <div class="bg-white/60 rounded-lg p-3">
            <p class="font-medium mb-2">📊 Средние цены в Москве:</p>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div>• Эконом: 2000-3000 ₽</div>
              <div>• Стандарт: 3000-4500 ₽</div>
              <div>• Комфорт: 4500-6000 ₽</div>
              <div>• Премиум: 6000+ ₽</div>
            </div>
          </div>

          <!-- Рекомендации -->
          <ul class="space-y-1">
            <li>• Конкурентная цена увеличивает количество заказов на 40%</li>
            <li>• Новым мастерам рекомендуется начать с цен ниже среднего</li>
            <li>• Цена влияет на позицию в поиске — учитывайте баланс</li>
            <li>• Указывайте цену за выезд отдельно для прозрачности</li>
          </ul>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Cards/Card.vue'
</script>