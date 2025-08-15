<template>
  <div class="tooltip-example">
    <h2>Примеры использования Tooltip</h2>
    
    <div class="example-section">
      <h3>1. Базовое использование (как на скриншоте)</h3>
      <div class="example-item">
        <div class="flex items-center gap-4">
          <Tooltip 
            text="Товары за 1₽"
            content="Можно купить за 1₽ и звёзды"
            placement="bottom"
          />
          
          <!-- Toggle switch (как на скриншоте) -->
          <label class="switch">
            <input type="checkbox" id="promo-toggle" name="promo-toggle" v-model="promoEnabled">
            <span class="slider"></span>
          </label>
        </div>
      </div>
    </div>

    <div class="example-section">
      <h3>2. Разные позиции</h3>
      <div class="example-grid">
        <Tooltip 
          text="Сверху"
          content="Подсказка появляется сверху"
          placement="top"
        />
        
        <Tooltip 
          text="Снизу"
          content="Подсказка появляется снизу"
          placement="bottom"
        />
        
        <Tooltip 
          text="Слева"
          content="Подсказка появляется слева"
          placement="left"
        />
        
        <Tooltip 
          text="Справа"
          content="Подсказка появляется справа"
          placement="right"
        />
      </div>
    </div>

    <div class="example-section">
      <h3>3. Кастомный контент</h3>
      <Tooltip placement="bottom">
        <template #trigger>
          <button class="custom-button">
            <InfoIcon :size="20" />
            Помощь
          </button>
        </template>
        <template #content>
          <div>
            <strong>Заголовок подсказки</strong>
            <p>Здесь может быть любой HTML контент, включая списки, ссылки и т.д.</p>
          </div>
        </template>
      </Tooltip>
    </div>

    <div class="example-section">
      <h3>4. Click trigger</h3>
      <Tooltip 
        text="Нажмите для подсказки"
        content="Эта подсказка появляется по клику"
        placement="auto"
        trigger="click"
      />
    </div>

    <div class="example-section">
      <h3>5. В контексте формы</h3>
      <div class="form-group">
        <label class="form-label">
          Цена услуги
          <Tooltip 
            content="Укажите стоимость за час работы. Можно указать диапазон цен."
            placement="right"
          >
            <template #trigger>
              <InfoIcon :size="16" color="#999" />
            </template>
          </Tooltip>
        </label>
        <input type="text" id="price-field" name="price" class="form-input" placeholder="от 2000 ₽">
      </div>
    </div>

    <div class="example-section">
      <h3>6. Без иконки</h3>
      <Tooltip 
        text="Текст без иконки"
        content="Подсказка для простого текста"
        :show-icon="false"
        placement="bottom"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Tooltip from './Tooltip.vue'
import InfoIcon from '@/src/shared/ui/atoms/InfoIcon/InfoIcon.vue'

const promoEnabled = ref(false)
</script>

<style scoped>
.tooltip-example {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 30px;
  color: #001a34;
}

h3 {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 15px;
  color: #333;
}

.example-section {
  margin-bottom: 40px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.example-item {
  display: flex;
  align-items: center;
  gap: 20px;
}

.example-grid {
  display: flex;
  gap: 30px;
  flex-wrap: wrap;
}

/* Toggle switch стили (как на Ozon) */
.switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:checked + .slider:before {
  transform: translateX(20px);
}

/* Кастомная кнопка */
.custom-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.custom-button:hover {
  background: #f8f9fa;
  border-color: #999;
}

/* Форма */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #2196F3;
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.gap-4 {
  gap: 16px;
}
</style>