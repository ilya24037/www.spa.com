<template>
  <div class="fibonacci-test-page">
    <!-- Заголовок -->
    <div class="header-section">
      <h1 class="main-title">Принципы Фибоначчи в дизайне интерфейса</h1>
      <p class="subtitle">Последовательность: 0, 1, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 144, 233...</p>
    </div>

    <!-- Шкала размеров -->
    <section class="size-scale">
      <h2 class="section-title">Шкала размеров (px)</h2>
      <div class="fibonacci-boxes">
        <div class="fib-box" style="width: 8px; height: 8px;">8</div>
        <div class="fib-box" style="width: 13px; height: 13px;">13</div>
        <div class="fib-box" style="width: 21px; height: 21px;">21</div>
        <div class="fib-box" style="width: 34px; height: 34px;">34</div>
        <div class="fib-box" style="width: 55px; height: 55px;">55</div>
        <div class="fib-box" style="width: 89px; height: 89px;">89</div>
        <div class="fib-box" style="width: 144px; height: 144px;">144</div>
        <div class="fib-box" style="width: 233px; height: 233px;">233</div>
      </div>
    </section>

    <!-- Типографика -->
    <section class="typography-section">
      <h2 class="section-title">Типографика по Фибоначчи</h2>
      <div class="typography-examples">
        <p style="font-size: 8px; line-height: 13px;">Размер 8px - мелкий шрифт для подписей</p>
        <p style="font-size: 13px; line-height: 21px;">Размер 13px - дополнительный текст, подсказки</p>
        <p style="font-size: 21px; line-height: 34px;">Размер 21px - основной текст контента</p>
        <p style="font-size: 34px; line-height: 55px;">Размер 34px - заголовки разделов</p>
        <p style="font-size: 55px; line-height: 89px;">Размер 55px - главный заголовок</p>
      </div>
    </section>

    <!-- Отступы -->
    <section class="spacing-section">
      <h2 class="section-title">Система отступов</h2>
      <div class="spacing-grid">
        <div class="spacing-demo">
          <div class="spacing-box" style="padding: 8px;">
            <div class="spacing-content">Padding: 8px</div>
          </div>
        </div>
        <div class="spacing-demo">
          <div class="spacing-box" style="padding: 13px;">
            <div class="spacing-content">Padding: 13px</div>
          </div>
        </div>
        <div class="spacing-demo">
          <div class="spacing-box" style="padding: 21px;">
            <div class="spacing-content">Padding: 21px</div>
          </div>
        </div>
        <div class="spacing-demo">
          <div class="spacing-box" style="padding: 34px;">
            <div class="spacing-content">Padding: 34px</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Сетка колонок -->
    <section class="grid-section">
      <h2 class="section-title">Сетка на основе Фибоначчи</h2>
      
      <!-- 4 колонки по 144px -->
      <div class="grid-demo">
        <h3>4 колонки × 144px (gap: 13px)</h3>
        <div class="fibonacci-grid-4">
          <div class="grid-item">144px</div>
          <div class="grid-item">144px</div>
          <div class="grid-item">144px</div>
          <div class="grid-item">144px</div>
        </div>
      </div>

      <!-- 3 колонки: 233-144-233 -->
      <div class="grid-demo">
        <h3>3 колонки: 233px - 144px - 233px</h3>
        <div class="fibonacci-grid-3">
          <div class="grid-item wide">233px</div>
          <div class="grid-item narrow">144px</div>
          <div class="grid-item wide">233px</div>
        </div>
      </div>

      <!-- Золотое сечение: 377-233 -->
      <div class="grid-demo">
        <h3>Золотое сечение: 377px - 233px (1.618:1)</h3>
        <div class="fibonacci-grid-golden">
          <div class="grid-item golden-large">377px (62%)</div>
          <div class="grid-item golden-small">233px (38%)</div>
        </div>
      </div>
    </section>

    <!-- Компоненты UI -->
    <section class="components-section">
      <h2 class="section-title">UI компоненты с размерами Фибоначчи</h2>
      
      <div class="components-grid">
        <!-- Кнопки -->
        <div class="component-group">
          <h3>Кнопки</h3>
          <div class="buttons-demo">
            <button class="fib-btn small">Малая (h: 34px)</button>
            <button class="fib-btn medium">Средняя (h: 55px)</button>
            <button class="fib-btn large">Большая (h: 89px)</button>
          </div>
        </div>

        <!-- Поля ввода -->
        <div class="component-group">
          <h3>Поля ввода</h3>
          <div class="inputs-demo">
            <input type="text" class="fib-input narrow" placeholder="Ширина 144px" />
            <input type="text" class="fib-input medium" placeholder="Ширина 233px" />
            <input type="text" class="fib-input wide" placeholder="Ширина 377px" />
          </div>
        </div>

        <!-- Карточки -->
        <div class="component-group">
          <h3>Карточки</h3>
          <div class="cards-demo">
            <div class="fib-card small">
              <h4>Карточка 144×144</h4>
              <p>Квадратная карточка</p>
            </div>
            <div class="fib-card medium">
              <h4>Карточка 233×144</h4>
              <p>Прямоугольная карточка</p>
            </div>
            <div class="fib-card large">
              <h4>Карточка 377×233</h4>
              <p>Золотое сечение</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Сравнение -->
    <section class="comparison-section">
      <h2 class="section-title">Сравнение: обычная сетка vs Фибоначчи</h2>
      
      <div class="comparison-grid">
        <!-- Обычная сетка -->
        <div class="comparison-side">
          <h3>Обычная сетка (кратно 10)</h3>
          <div class="regular-grid">
            <div style="padding: 10px; margin: 10px; background: #f3f4f6;">10px</div>
            <div style="padding: 20px; margin: 10px; background: #f3f4f6;">20px</div>
            <div style="padding: 30px; margin: 10px; background: #f3f4f6;">30px</div>
            <div style="padding: 40px; margin: 10px; background: #f3f4f6;">40px</div>
          </div>
        </div>

        <!-- Фибоначчи сетка -->
        <div class="comparison-side">
          <h3>Сетка Фибоначчи</h3>
          <div class="fibonacci-grid">
            <div style="padding: 8px; margin: 8px; background: #fef3c7;">8px</div>
            <div style="padding: 13px; margin: 8px; background: #fde68a;">13px</div>
            <div style="padding: 21px; margin: 8px; background: #fcd34d;">21px</div>
            <div style="padding: 34px; margin: 8px; background: #fbbf24;">34px</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Практический пример -->
    <section class="practical-section">
      <h2 class="section-title">Практический пример: форма цен</h2>
      
      <div class="practical-example">
        <div class="price-form-fibonacci">
          <h3 style="font-size: 21px; margin-bottom: 13px;">Цены на услуги</h3>
          
          <div class="price-grid-fib">
            <div class="price-field-fib">
              <label>Экспресс</label>
              <input type="number" placeholder="₽" />
            </div>
            <div class="price-field-fib">
              <label>1 час</label>
              <input type="number" placeholder="₽" />
            </div>
            <div class="price-field-fib">
              <label>2 часа</label>
              <input type="number" placeholder="₽" />
            </div>
            <div class="price-field-fib">
              <label>Ночь</label>
              <input type="number" placeholder="₽" />
            </div>
          </div>

          <div class="form-actions-fib">
            <button class="btn-fib secondary">Отмена</button>
            <button class="btn-fib primary">Сохранить</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Спираль Фибоначчи -->
    <section class="spiral-section">
      <h2 class="section-title">Золотая спираль</h2>
      <div class="spiral-container">
        <svg viewBox="0 0 610 377" class="fibonacci-spiral">
          <!-- Прямоугольники -->
          <rect x="0"
                y="0"
                width="233"
                height="233"
                fill="none"
                stroke="#fbbf24"
                stroke-width="2"/>
          <rect x="233"
                y="0"
                width="144"
                height="144"
                fill="none"
                stroke="#fcd34d"
                stroke-width="2"/>
          <rect x="233"
                y="144"
                width="89"
                height="89"
                fill="none"
                stroke="#fde68a"
                stroke-width="2"/>
          <rect x="322"
                y="144"
                width="55"
                height="55"
                fill="none"
                stroke="#fef3c7"
                stroke-width="2"/>
          <rect x="322"
                y="199"
                width="34"
                height="34"
                fill="none"
                stroke="#fef3c7"
                stroke-width="2"/>
          
          <!-- Спираль -->
          <path d="M 233,233 Q 0,233 0,0 Q 0,0 377,0 Q 377,0 377,144 Q 377,144 322,144 Q 322,144 322,199 Q 322,199 356,199" 
                fill="none" 
                stroke="#f59e0b" 
                stroke-width="3"
                stroke-linecap="round"/>
        </svg>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  console.log('Fibonacci Design Test Page loaded')
})
</script>

<style scoped>
/* Стили для Fibonacci теста */
.fibonacci-test-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 34px 21px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Заголовки */
.header-section {
  text-align: center;
  margin-bottom: 55px;
}

.main-title {
  font-size: 55px;
  line-height: 89px;
  color: #111827;
  margin: 0 0 13px 0;
}

.subtitle {
  font-size: 21px;
  color: #6b7280;
  margin: 0;
}

.section-title {
  font-size: 34px;
  line-height: 55px;
  color: #1f2937;
  margin: 55px 0 21px 0;
}

/* Секции */
section {
  margin-bottom: 89px;
}

/* Шкала размеров */
.fibonacci-boxes {
  display: flex;
  align-items: flex-end;
  gap: 13px;
  padding: 21px;
  background: #f9fafb;
  border-radius: 8px;
  overflow-x: auto;
}

.fib-box {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 13px;
  font-weight: bold;
  border-radius: 3px;
  flex-shrink: 0;
}

/* Типографика */
.typography-examples {
  background: #f9fafb;
  padding: 34px;
  border-radius: 8px;
}

.typography-examples p {
  margin: 21px 0;
  color: #374151;
}

/* Отступы */
.spacing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(144px, 1fr));
  gap: 21px;
}

.spacing-box {
  background: #fef3c7;
  border: 2px solid #fbbf24;
  border-radius: 8px;
}

.spacing-content {
  background: white;
  padding: 8px;
  border-radius: 5px;
  text-align: center;
  font-size: 13px;
}

/* Сетки */
.grid-demo {
  margin-bottom: 34px;
}

.grid-demo h3 {
  font-size: 21px;
  margin-bottom: 13px;
  color: #4b5563;
}

.fibonacci-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 144px);
  gap: 13px;
}

.fibonacci-grid-3 {
  display: grid;
  grid-template-columns: 233px 144px 233px;
  gap: 13px;
}

.fibonacci-grid-golden {
  display: grid;
  grid-template-columns: 377px 233px;
  gap: 13px;
}

.grid-item {
  background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
  padding: 21px;
  border-radius: 8px;
  text-align: center;
  font-weight: bold;
  color: #4338ca;
}

.grid-item.golden-large {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  color: #92400e;
}

.grid-item.golden-small {
  background: linear-gradient(135deg, #fde68a, #fcd34d);
  color: #78350f;
}

/* UI Компоненты */
.components-grid {
  display: grid;
  gap: 34px;
}

.component-group h3 {
  font-size: 21px;
  margin-bottom: 13px;
  color: #4b5563;
}

.buttons-demo {
  display: flex;
  gap: 13px;
  align-items: flex-end;
}

.fib-btn {
  padding: 0 21px;
  border: none;
  border-radius: 5px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s;
}

.fib-btn.small {
  height: 34px;
  background: #ddd6fe;
  color: #5b21b6;
}

.fib-btn.medium {
  height: 55px;
  font-size: 21px;
  background: #a78bfa;
  color: white;
}

.fib-btn.large {
  height: 89px;
  font-size: 34px;
  background: #7c3aed;
  color: white;
}

.fib-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 13px rgba(0,0,0,0.2);
}

.inputs-demo {
  display: flex;
  flex-direction: column;
  gap: 13px;
}

.fib-input {
  height: 55px;
  padding: 0 21px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 21px;
  transition: all 0.3s;
}

.fib-input.narrow {
  width: 144px;
}

.fib-input.medium {
  width: 233px;
}

.fib-input.wide {
  width: 377px;
}

.fib-input:focus {
  outline: none;
  border-color: #fbbf24;
  background: #fffbeb;
}

.cards-demo {
  display: flex;
  gap: 21px;
  flex-wrap: wrap;
}

.fib-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 13px;
  padding: 21px;
  transition: all 0.3s;
}

.fib-card.small {
  width: 144px;
  height: 144px;
}

.fib-card.medium {
  width: 233px;
  height: 144px;
}

.fib-card.large {
  width: 377px;
  height: 233px;
}

.fib-card:hover {
  border-color: #fbbf24;
  transform: translateY(-3px);
  box-shadow: 0 8px 21px rgba(251, 191, 36, 0.3);
}

.fib-card h4 {
  font-size: 21px;
  margin: 0 0 8px 0;
  color: #1f2937;
}

.fib-card p {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

/* Сравнение */
.comparison-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 34px;
}

.comparison-side h3 {
  font-size: 21px;
  margin-bottom: 13px;
  color: #4b5563;
}

/* Практический пример */
.price-form-fibonacci {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 13px;
  padding: 34px;
  max-width: 610px;
}

.price-grid-fib {
  display: grid;
  grid-template-columns: repeat(4, 144px);
  gap: 13px;
  margin-bottom: 34px;
}

.price-field-fib label {
  display: block;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 5px;
}

.price-field-fib input {
  width: 100%;
  height: 34px;
  padding: 0 13px;
  border: 2px solid #e5e7eb;
  border-radius: 5px;
  font-size: 21px;
}

.form-actions-fib {
  display: flex;
  gap: 13px;
  justify-content: flex-end;
}

.btn-fib {
  height: 55px;
  padding: 0 34px;
  border: none;
  border-radius: 8px;
  font-size: 21px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-fib.primary {
  background: #fbbf24;
  color: #78350f;
}

.btn-fib.secondary {
  background: #f3f4f6;
  color: #4b5563;
}

.btn-fib:hover {
  transform: translateY(-2px);
}

/* Золотая спираль */
.spiral-container {
  max-width: 610px;
  margin: 0 auto;
  padding: 34px;
  background: #fffbeb;
  border-radius: 13px;
}

.fibonacci-spiral {
  width: 100%;
  height: auto;
}

/* Адаптивность */
@media (max-width: 768px) {
  .main-title {
    font-size: 34px;
    line-height: 55px;
  }
  
  .section-title {
    font-size: 21px;
    line-height: 34px;
  }
  
  .comparison-grid {
    grid-template-columns: 1fr;
  }
  
  .price-grid-fib {
    grid-template-columns: repeat(2, 144px);
  }
  
  .fibonacci-grid-4,
  .fibonacci-grid-3,
  .fibonacci-grid-golden {
    grid-template-columns: 1fr;
  }
}
</style>