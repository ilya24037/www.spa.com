// Простой тест Brave Search API
import https from 'https';
import zlib from 'zlib';

const API_KEY = 'BSASbzOkt6aRXgHa-zncvcBnB0ooLZv';
const query = 'test';

const options = {
  hostname: 'api.search.brave.com',
  path: `/res/v1/web/search?q=${encodeURIComponent(query)}`,
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'X-Subscription-Token': API_KEY
  }
};

console.log('🔍 Тестируем Brave Search API...\n');

const req = https.request(options, (res) => {
  const chunks = [];

  res.on('data', (chunk) => {
    chunks.push(chunk);
  });

  res.on('end', () => {
    const buffer = Buffer.concat(chunks);
    
    // Попробуем декодировать как gzip
    if (res.headers['content-encoding'] === 'gzip') {
      zlib.gunzip(buffer, (err, decoded) => {
        if (err) {
          console.error('❌ Ошибка декодирования:', err);
          return;
        }
        handleResponse(decoded.toString(), res.statusCode);
      });
    } else {
      handleResponse(buffer.toString(), res.statusCode);
    }
  });
});

function handleResponse(data, statusCode) {
  try {
    const result = JSON.parse(data);
    
    if (statusCode === 200) {
      console.log('✅ API работает успешно!\n');
      console.log(`API ключ: ${API_KEY.substring(0, 10)}...`);
      console.log(`Статус: ${statusCode}`);
      console.log(`Найдено результатов: ${result.web?.results?.length || 0}`);
      
      if (result.web?.results?.length > 0) {
        console.log('\nПервый результат:');
        const first = result.web.results[0];
        console.log(`- ${first.title}`);
        console.log(`- ${first.url}`);
      }
    } else {
      console.log(`❌ Ошибка API: ${statusCode}`);
      console.log(data);
    }
  } catch (error) {
    console.log('❌ Ошибка обработки:', error.message);
  }
}

req.on('error', (error) => {
  console.error('❌ Ошибка запроса:', error.message);
});

req.end();