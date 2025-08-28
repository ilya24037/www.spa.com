/**
 * Скрипт диагностики карты для выполнения в браузере
 * Откройте DevTools > Console и вставьте этот скрипт
 */

console.log('🗺️ ДИАГНОСТИКА КАРТЫ - СКРИПТ ЗАГРУЖЕН');

// 1. Проверяем наличие Yandex Maps API
function checkYandexMapsAPI() {
  console.log('🔍 Проверка Yandex Maps API...');
  
  if (window.ymaps) {
    console.log('✅ window.ymaps загружен:', typeof window.ymaps);
    
    if (window.ymaps.ready) {
      console.log('✅ ymaps.ready доступен');
      window.ymaps.ready(() => {
        console.log('✅ ymaps.ready() выполнен успешно');
      });
    } else {
      console.log('❌ ymaps.ready НЕ доступен');
    }
  } else {
    console.log('❌ window.ymaps НЕ загружен');
    
    // Ищем скрипт Yandex Maps
    const scripts = Array.from(document.querySelectorAll('script')).filter(s => 
      s.src && s.src.includes('api-maps.yandex.ru')
    );
    
    console.log('🔍 Скрипты Yandex Maps на странице:', scripts.length);
    scripts.forEach((script, i) => {
      console.log(`  ${i + 1}. ${script.src}`);
    });
  }
}

// 2. Проверяем наличие контейнеров карт
function checkMapContainers() {
  console.log('🔍 Поиск контейнеров карт...');
  
  // Ищем все элементы с ID содержащим 'map'
  const allElements = document.querySelectorAll('*');
  const mapElements = Array.from(allElements).filter(el => 
    el.id && (el.id.includes('map') || el.id.includes('yandex'))
  );
  
  console.log(`📦 Найдено элементов с ID содержащим 'map': ${mapElements.length}`);
  mapElements.forEach((el, i) => {
    const rect = el.getBoundingClientRect();
    const isVisible = rect.width > 0 && rect.height > 0;
    console.log(`  ${i + 1}. ID: ${el.id}, размер: ${rect.width}x${rect.height}, видим: ${isVisible}`);
  });
  
  // Специально ищем контейнеры карты Yandex
  const yandexContainers = document.querySelectorAll('.yandex-map-base__container, .yandex-map-unified__container');
  console.log(`🗺️ Специальные контейнеры Yandex: ${yandexContainers.length}`);
  yandexContainers.forEach((container, i) => {
    const rect = container.getBoundingClientRect();
    console.log(`  ${i + 1}. Размер: ${rect.width}x${rect.height}, classes: ${container.className}`);
  });
}

// 3. Проверяем секцию География
function checkGeoSection() {
  console.log('🔍 Поиск секции География...');
  
  // Ищем заголовок "География"
  const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6'));
  const geoHeading = headings.find(h => h.textContent && h.textContent.includes('География'));
  
  if (geoHeading) {
    const rect = geoHeading.getBoundingClientRect();
    const isInViewport = rect.top >= 0 && rect.top <= window.innerHeight;
    console.log(`✅ Секция "География" найдена`);
    console.log(`  Позиция: top=${rect.top}, в видимой области: ${isInViewport}`);
    
    if (!isInViewport) {
      console.log(`⚠️ Секция "География" НЕ в видимой области. Прокрутите вниз!`);
      console.log(`  Для прокрутки выполните: geoHeading.scrollIntoView({behavior: 'smooth'})`);
    }
    
    // Делаем элемент доступным глобально
    window.geoHeading = geoHeading;
  } else {
    console.log('❌ Секция "География" НЕ найдена');
  }
}

// 4. Проверяем Vue компоненты
function checkVueComponents() {
  console.log('🔍 Проверка Vue компонентов...');
  
  // Ищем элементы с data-v- атрибутами (Vue scoped CSS)
  const vueElements = document.querySelectorAll('[class*="yandex"], [class*="map"]');
  console.log(`📦 Vue элементы карты: ${vueElements.length}`);
  
  vueElements.forEach((el, i) => {
    if (i < 5) { // Показываем только первые 5
      console.log(`  ${i + 1}. Tag: ${el.tagName}, classes: ${el.className}`);
    }
  });
}

// 5. Главная функция диагностики
function runDiagnosis() {
  console.log('🚀 ЗАПУСК ПОЛНОЙ ДИАГНОСТИКИ КАРТЫ');
  console.log('='.repeat(50));
  
  checkYandexMapsAPI();
  console.log('');
  checkMapContainers();
  console.log('');
  checkGeoSection();
  console.log('');
  checkVueComponents();
  
  console.log('='.repeat(50));
  console.log('✅ ДИАГНОСТИКА ЗАВЕРШЕНА');
  console.log('');
  console.log('💡 РЕКОМЕНДАЦИИ:');
  console.log('1. Если секция "География" не в видимой области - прокрутите к ней');
  console.log('2. Если контейнеры карты есть, но ymaps не загружен - проблема с API');
  console.log('3. Если ничего не найдено - Vue компонент не отрендерился');
}

// Экспортируем функции в глобальную область
window.mapDiagnosis = {
  runFull: runDiagnosis,
  checkAPI: checkYandexMapsAPI,
  checkContainers: checkMapContainers,
  checkGeoSection: checkGeoSection,
  checkVue: checkVueComponents
};

// Автоматически запускаем диагностику
runDiagnosis();

// Полезные команды для консоли
console.log('');
console.log('💻 ДОСТУПНЫЕ КОМАНДЫ:');
console.log('mapDiagnosis.runFull() - полная диагностика');
console.log('mapDiagnosis.checkAPI() - проверка API');
console.log('mapDiagnosis.checkGeoSection() - найти секцию География');
console.log('geoHeading?.scrollIntoView({behavior: "smooth"}) - прокрутить к секции');