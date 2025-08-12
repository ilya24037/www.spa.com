// Тест Brave Search API
import https from 'https';

const API_KEY = 'BSASbzOkt6aRXgHa-zncvcBnB0ooLZv';
const query = 'Laravel Vue.js tutorial';

const options = {
  hostname: 'api.search.brave.com',
  path: `/res/v1/web/search?q=${encodeURIComponent(query)}`,
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Accept-Encoding': 'gzip',
    'X-Subscription-Token': API_KEY
  }
};

console.log('🔍 Тестируем Brave Search API...\n');
console.log(`Запрос: "${query}"\n`);

const req = https.request(options, (res) => {
  let data = '';

  res.on('data', (chunk) => {
    data += chunk;
  });

  res.on('end', () => {
    try {
      const result = JSON.parse(data);
      
      if (res.statusCode === 200) {
        console.log('✅ API работает успешно!\n');
        console.log(`Найдено результатов: ${result.web?.results?.length || 0}\n`);
        
        if (result.web?.results?.length > 0) {
          console.log('Первые 3 результата:');
          result.web.results.slice(0, 3).forEach((item, index) => {
            console.log(`\n${index + 1}. ${item.title}`);
            console.log(`   URL: ${item.url}`);
            console.log(`   ${item.description?.substring(0, 100)}...`);
          });
        }
      } else {
        console.log(`❌ Ошибка API: ${res.statusCode}`);
        console.log(data);
      }
    } catch (error) {
      console.log('❌ Ошибка парсинга ответа:', error.message);
      console.log('Ответ:', data);
    }
  });
});

req.on('error', (error) => {
  console.error('❌ Ошибка запроса:', error.message);
});

req.end();