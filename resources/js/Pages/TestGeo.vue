<template>
  <div class="test-geo-page">
    <div class="container">
      <h1 class="title">Тест секции География</h1>
      <p class="description">Тестирование компонента GeoSection с Яндекс.Картами</p>
      
      <!-- Компонент GeoSection -->
      <GeoSection 
        :geo="geoData"
        :errors="errors"
        @update:geo="handleGeoUpdate"
      />
      
      <!-- Отображение данных -->
      <div class="data-display">
        <h3>Текущие данные (JSON):</h3>
        <pre>{{ geoDataFormatted }}</pre>
      </div>
      
      <!-- Статус -->
      <div class="status-display">
        <h3>Статус:</h3>
        <p>{{ status }}</p>
      </div>
      
      <!-- Кнопки действий -->
      <div class="actions">
        <button @click="loadTestData" class="btn btn-primary">
          Загрузить тестовые данные
        </button>
        <button @click="testAutoDetection" class="btn btn-info">
          Автоопределение местоположения
        </button>
        <button @click="clearData" class="btn btn-secondary">
          Очистить данные
        </button>
        <button @click="saveData" class="btn btn-success">
          Сохранить (имитация)
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import GeoSection from '@/src/features/AdSections/GeoSection/ui/GeoSection.vue'

// Данные
const geoData = ref('')
const errors = ref({})
const status = ref('Готов к тестированию')

// Форматированные данные для отображения
const geoDataFormatted = computed(() => {
  if (!geoData.value) return 'Нет данных'
  
  try {
    const parsed = JSON.parse(geoData.value)
    return JSON.stringify(parsed, null, 2)
  } catch (e) {
    return geoData.value
  }
})

// Обработчик обновления
const handleGeoUpdate = (value: string) => {
  geoData.value = value
  status.value = `Данные обновлены: ${new Date().toLocaleTimeString()}`
}

// Загрузка тестовых данных
const loadTestData = () => {
  geoData.value = JSON.stringify({
    address: 'Пермь, ул. Ленина, 1',
    coordinates: { lat: 58.0105, lng: 56.2502 },
    outcall: 'city',
    zones: []
  })
  status.value = 'Тестовые данные загружены'
}

// Тест автоопределения
const testAutoDetection = () => {
  status.value = 'Определение местоположения...'
  
  if (!navigator.geolocation) {
    status.value = 'Геолокация не поддерживается браузером'
    return
  }
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const coords = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      }
      
      geoData.value = JSON.stringify({
        address: `Автоопределено: ${coords.lat}, ${coords.lng}`,
        coordinates: coords,
        outcall: 'city',
        zones: []
      })
      
      status.value = `Местоположение определено: ${coords.lat}, ${coords.lng}`
    },
    (error) => {
      status.value = `Ошибка геолокации: ${error.message}`
      
      // Fallback на IP
      fetch('https://ipapi.co/json/')
        .then(res => res.json())
        .then(data => {
          if (data.latitude && data.longitude) {
            geoData.value = JSON.stringify({
              address: `${data.city}, ${data.country_name}`,
              coordinates: { lat: data.latitude, lng: data.longitude },
              outcall: 'city',
              zones: []
            })
            status.value = `Определено по IP: ${data.city}, ${data.country_name}`
          }
        })
        .catch(() => {
          status.value = 'Не удалось определить местоположение'
        })
    }
  )
}

// Очистка данных
const clearData = () => {
  geoData.value = ''
  errors.value = {}
  status.value = 'Данные очищены'
}

// Имитация сохранения
const saveData = () => {
  if (!geoData.value) {
    errors.value = {
      geo: ['Заполните адрес']
    }
    status.value = 'Ошибка: нет данных для сохранения'
    return
  }
  
  errors.value = {}
  status.value = 'Данные сохранены успешно (имитация)'
}
</script>

<style scoped>
.test-geo-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 20px;
}

.container {
  max-width: 900px;
  margin: 0 auto;
}

.title {
  font-size: 28px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 10px 0;
}

.description {
  font-size: 16px;
  color: #666;
  margin: 0 0 30px 0;
}

.data-display {
  margin-top: 30px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.data-display h3 {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 15px 0;
  color: #1a1a1a;
}

.data-display pre {
  background: #f5f5f5;
  padding: 15px;
  border-radius: 4px;
  font-size: 13px;
  overflow-x: auto;
  margin: 0;
  color: #333;
  font-family: 'Courier New', monospace;
}

.status-display {
  margin-top: 20px;
  padding: 15px;
  background: #e3f2fd;
  border-radius: 8px;
  border-left: 4px solid #2196f3;
}

.status-display h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 10px 0;
  color: #1565c0;
}

.status-display p {
  margin: 0;
  color: #0d47a1;
  font-size: 14px;
}

.actions {
  margin-top: 30px;
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
  background: #0066ff;
  color: white;
}

.btn-primary:hover {
  background: #0052cc;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
}

.btn-info {
  background: #17a2b8;
  color: white;
}

.btn-info:hover {
  background: #138496;
}

/* Адаптивность */
@media (max-width: 768px) {
  .container {
    padding: 0;
  }
  
  .title {
    font-size: 24px;
  }
  
  .actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>