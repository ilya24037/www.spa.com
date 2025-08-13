<template>
  <div class="characteristic-tooltip-example">
    <h2>Примеры использования CharacteristicTooltip</h2>
    
    <!-- Пример 1: Информация о мастере -->
    <div class="example-section">
      <h3>1. Характеристики мастера</h3>
      <div class="characteristics-card">
        <h4>Основная информация</h4>
        
        <CharacteristicTooltip
          name="Опыт работы"
          value="7 лет"
          title="Профессиональный стаж"
          description="Включает обучение в медицинском колледже, стажировку и самостоятельную практику"
          :items="[
            'Медицинское образование - 3 года',
            'Стажировка в клинике - 1 год', 
            'Частная практика - 3 года'
          ]"
          note="Постоянное повышение квалификации"
        />
        
        <CharacteristicTooltip
          name="Специализация"
          value="Лечебный массаж"
          title="Основное направление"
          description="Медицинский массаж для лечения и профилактики заболеваний опорно-двигательного аппарата"
          :examples="['Остеохондроз', 'Сколиоз', 'Радикулит', 'Артроз']"
        />
        
        <CharacteristicTooltip
          name="Выезд к клиенту"
          :value="true"
          value-format="boolean"
          title="Возможность выезда"
          description="Мастер может приехать к вам домой или в офис"
          :items="[
            'Привозит массажный стол',
            'Все необходимые материалы',
            'Работает в удобное время'
          ]"
          note="Стоимость выезда от 500₽"
        />
      </div>
    </div>

    <!-- Пример 2: Услуги с ценами -->
    <div class="example-section">
      <h3>2. Услуги и цены</h3>
      <div class="characteristics-card">
        <h4>Классический массаж</h4>
        
        <CharacteristicTooltip
          name="Длительность"
          :value="60"
          value-format="duration"
          title="Продолжительность сеанса"
          description="Полный сеанс классического массажа всего тела"
          :items="[
            'Консультация - 5 мин',
            'Основная процедура - 50 мин',
            'Рекомендации - 5 мин'
          ]"
        />
        
        <CharacteristicTooltip
          name="Стоимость"
          :value="2500"
          value-format="price"
          title="Цена за сеанс"
          description="Стоимость одного сеанса классического массажа"
          note="При покупке абонемента на 10 сеансов скидка 15%"
        />
        
        <CharacteristicTooltip
          name="Интенсивность"
          value="Средняя"
          title="Сила воздействия"
          description="Оптимальный уровень давления для терапевтического эффекта"
          :items="[
            'Проработка глубоких слоев мышц',
            'Комфортные ощущения',
            'Без болевых ощущений'
          ]"
        />
      </div>
    </div>

    <!-- Пример 3: С использованием конфигурации -->
    <div class="example-section">
      <h3>3. Загрузка из конфигурации</h3>
      <div class="characteristics-card">
        <h4>Дополнительные опции</h4>
        
        <CharacteristicTooltip
          v-for="char in configCharacteristics"
          :key="char.key"
          :name="char.name"
          :value="getCharValue(char.key)"
          :title="char.title"
          :description="char.description"
          :items="char.items"
          :examples="char.examples"
          :note="char.note"
          :value-format="char.valueFormat"
        />
      </div>
    </div>

    <!-- Пример 4: В контексте формы -->
    <div class="example-section">
      <h3>4. В форме редактирования</h3>
      <div class="form-example">
        <div class="form-group">
          <label class="form-label">
            <CharacteristicTooltip
              name="Ароматерапия"
              :value="formData.aromatherapy"
              value-format="boolean"
              title="Использование эфирных масел"
              description="Натуральные ароматические масла для усиления эффекта"
              :items="[
                'Лаванда - расслабление',
                'Эвкалипт - тонизирование',
                'Апельсин - настроение'
              ]"
              note="Проверяем на аллергию перед применением"
              tooltip-placement="right"
            />
          </label>
          <input 
            type="checkbox" 
            v-model="formData.aromatherapy"
            class="form-checkbox"
          >
        </div>

        <div class="form-group">
          <label class="form-label">
            <CharacteristicTooltip
              name="Горячие камни"
              :value="formData.hotStones"
              value-format="boolean"
              title="Стоун-терапия"
              description="Массаж с использованием нагретых вулканических камней"
              :items="[
                'Глубокое прогревание мышц',
                'Улучшение кровообращения',
                'Максимальное расслабление'
              ]"
              tooltip-placement="right"
            />
          </label>
          <input 
            type="checkbox" 
            v-model="formData.hotStones"
            class="form-checkbox"
          >
        </div>

        <div class="form-group">
          <label class="form-label">
            <CharacteristicTooltip
              name="Музыкальное сопровождение"
              :value="formData.music"
              value-format="boolean"
              title="Релаксирующая музыка"
              description="Специально подобранная музыка для максимального расслабления"
              :examples="['Звуки природы', 'Медитативная музыка', 'Лаунж']"
              tooltip-placement="right"
            />
          </label>
          <input 
            type="checkbox" 
            v-model="formData.music"
            class="form-checkbox"
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import CharacteristicTooltip from './CharacteristicTooltip.vue'
import { getCharacteristicInfo } from '@/src/shared/config/characteristicsInfo'

// Пример загрузки характеристик из конфигурации
const configCharacteristics = [
  getCharacteristicInfo('aromatherapy'),
  getCharacteristicInfo('hot_stones'),
  getCharacteristicInfo('professional_table'),
  getCharacteristicInfo('music_therapy')
].filter(Boolean)

// Значения для характеристик из конфигурации
const getCharValue = (key: string) => {
  const values: Record<string, any> = {
    aromatherapy: true,
    hot_stones: false,
    professional_table: true,
    music_therapy: true
  }
  return values[key]
}

// Данные формы
const formData = reactive({
  aromatherapy: true,
  hotStones: false,
  music: true
})
</script>

<style scoped>
.characteristic-tooltip-example {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 30px;
  color: #001a34;
}

h3 {
  font-size: 18px;
  font-weight: 500;
  margin-bottom: 20px;
  color: #333;
}

h4 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #001a34;
}

.example-section {
  margin-bottom: 40px;
}

.characteristics-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Форма */
.form-example {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
}

.form-group {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1px solid #f0f2f5;
}

.form-group:last-child {
  border-bottom: none;
}

.form-label {
  flex: 1;
}

.form-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
  margin-left: 16px;
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .characteristic-tooltip-example {
    padding: 16px;
  }
  
  .characteristics-card,
  .form-example {
    padding: 16px;
  }
  
  h2 {
    font-size: 20px;
    margin-bottom: 20px;
  }
  
  h3 {
    font-size: 16px;
    margin-bottom: 16px;
  }
  
  .form-group {
    padding: 12px 0;
  }
}
</style>