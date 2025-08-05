// analyze-duplicates.js - Анализ дублирования компонентов
const fs = require('fs');
const path = require('path');

// Конфигурация
const config = {
  legacyDir: 'resources/js/Components',
  fsdDir: 'resources/js/src',
  outputFile: 'duplication-report.json',
  ignorePatterns: [
    'node_modules',
    '.git',
    'dist',
    'build',
    '.test.',
    '.spec.',
    '.stories.'
  ]
};

// Утилиты
function getFiles(dir, fileList = []) {
  if (!fs.existsSync(dir)) {
    return fileList;
  }

  const files = fs.readdirSync(dir);

  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);

    if (stat.isDirectory()) {
      // Пропускаем игнорируемые паттерны
      if (!config.ignorePatterns.some(pattern => filePath.includes(pattern))) {
        getFiles(filePath, fileList);
      }
    } else if (file.endsWith('.vue')) {
      fileList.push(filePath);
    }
  });

  return fileList;
}

function extractComponentName(filePath) {
  const fileName = path.basename(filePath, '.vue');
  return fileName;
}

function analyzeComponent(filePath) {
  const content = fs.readFileSync(filePath, 'utf-8');
  const lines = content.split('\n').length;

  // Анализ импортов
  const imports = [];
  const importMatches = content.matchAll(/import\s+(?:{[^}]+}|\w+)\s+from\s+['"]([^'"]+)['"]/g);
  for (const match of importMatches) {
    imports.push(match[1]);
  }

  // Анализ props
  const propsMatch = content.match(/defineProps<{([^}]+)}>/s) || 
                     content.match(/props:\s*{([^}]+)}/s);
  const hasProps = !!propsMatch;

  // Анализ emits
  const hasEmits = content.includes('defineEmits') || content.includes('$emit');

  // Анализ composables
  const composables = [];
  const composableMatches = content.matchAll(/use[A-Z]\w+/g);
  for (const match of composableMatches) {
    if (!composables.includes(match[0])) {
      composables.push(match[0]);
    }
  }

  // Анализ стилей
  const hasStyles = content.includes('<style');
  const hasScoped = content.includes('<style scoped');

  return {
    name: extractComponentName(filePath),
    path: filePath,
    lines,
    imports: imports.length,
    hasProps,
    hasEmits,
    composables,
    hasStyles,
    hasScoped,
    complexity: calculateComplexity(content)
  };
}

function calculateComplexity(content) {
  // Простая метрика сложности
  let complexity = 1;

  // Условия
  complexity += (content.match(/\bif\s*\(/g) || []).length;
  complexity += (content.match(/\belse\s+if\s*\(/g) || []).length;
  complexity += (content.match(/\bfor\s*\(/g) || []).length;
  complexity += (content.match(/\bwhile\s*\(/g) || []).length;
  complexity += (content.match(/\?.*:/g) || []).length; // тернарные операторы
  complexity += (content.match(/v-if=/g) || []).length;
  complexity += (content.match(/v-for=/g) || []).length;

  return complexity;
}

function findDuplicates(components) {
  const duplicates = {};
  const nameMap = {};

  // Группируем по имени
  components.forEach(comp => {
    const baseName = comp.name.replace(/^(Legacy|Old|New|V2|Copy)/, '');

    if (!nameMap[baseName]) {
      nameMap[baseName] = [];
    }
    nameMap[baseName].push(comp);
  });

  // Находим потенциальные дубликаты
  Object.entries(nameMap).forEach(([name, comps]) => {
    if (comps.length > 1) {
      duplicates[name] = {
        count: comps.length,
        components: comps.map(c => ({
          path: c.path,
          lines: c.lines,
          complexity: c.complexity,
          location: c.path.includes('Components') ? 'legacy' : 'fsd'
        }))
      };
    }
  });

  return duplicates;
}

function findSimilar(components) {
  const similar = [];

  for (let i = 0; i < components.length; i++) {
    for (let j = i + 1; j < components.length; j++) {
      const comp1 = components[i];
      const comp2 = components[j];

      // Пропускаем если имена уже очень разные
      if (!areSimilarNames(comp1.name, comp2.name)) continue;

      // Сравниваем характеристики
      const similarity = calculateSimilarity(comp1, comp2);

      if (similarity > 0.7) { // 70% схожести
        similar.push({
          component1: {
            name: comp1.name,
            path: comp1.path
          },
          component2: {
            name: comp2.name,
            path: comp2.path
          },
          similarity: Math.round(similarity * 100)
        });
      }
    }
  }

  return similar;
}

function areSimilarNames(name1, name2) {
  // Убираем общие префиксы/суффиксы
  const clean1 = name1.replace(/(Modal|Form|Card|List|Item|Component)$/, '');
  const clean2 = name2.replace(/(Modal|Form|Card|List|Item|Component)$/, '');

  // Проверяем схожесть
  return clean1.includes(clean2) || clean2.includes(clean1) ||
         levenshteinDistance(clean1, clean2) < 4;
}

function calculateSimilarity(comp1, comp2) {
  let score = 0;
  let factors = 0;

  // Схожесть по размеру
  const sizeDiff = Math.abs(comp1.lines - comp2.lines);
  if (sizeDiff < 20) {
    score += 1 - (sizeDiff / 100);
    factors++;
  }

  // Схожесть по сложности
  const complexityDiff = Math.abs(comp1.complexity - comp2.complexity);
  if (complexityDiff < 5) {
    score += 1 - (complexityDiff / 20);
    factors++;
  }

  // Схожесть по характеристикам
  if (comp1.hasProps === comp2.hasProps) {
    score += 0.5;
    factors += 0.5;
  }

  if (comp1.hasEmits === comp2.hasEmits) {
    score += 0.5;
    factors += 0.5;
  }

  if (comp1.hasStyles === comp2.hasStyles) {
    score += 0.3;
    factors += 0.3;
  }

  return factors > 0 ? score / factors : 0;
}

function levenshteinDistance(str1, str2) {
  const matrix = [];

  for (let i = 0; i <= str2.length; i++) {
    matrix[i] = [i];
  }

  for (let j = 0; j <= str1.length; j++) {
    matrix[0][j] = j;
  }

  for (let i = 1; i <= str2.length; i++) {
    for (let j = 1; j <= str1.length; j++) {
      if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1];
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j] + 1
        );
      }
    }
  }

  return matrix[str2.length][str1.length];
}

function generateReport(legacyComponents, fsdComponents, duplicates, similar) {
  const report = {
    timestamp: new Date().toISOString(),
    summary: {
      totalComponents: legacyComponents.length + fsdComponents.length,
      legacyComponents: legacyComponents.length,
      fsdComponents: fsdComponents.length,
      duplicatesFound: Object.keys(duplicates).length,
      similarFound: similar.length
    },

    statistics: {
      legacy: {
        count: legacyComponents.length,
        totalLines: legacyComponents.reduce((sum, c) => sum + c.lines, 0),
        averageLines: Math.round(legacyComponents.reduce((sum, c) => sum + c.lines, 0) / legacyComponents.length),
        averageComplexity: Math.round(legacyComponents.reduce((sum, c) => sum + c.complexity, 0) / legacyComponents.length)
      },
      fsd: {
        count: fsdComponents.length,
        totalLines: fsdComponents.reduce((sum, c) => sum + c.lines, 0),
        averageLines: Math.round(fsdComponents.reduce((sum, c) => sum + c.lines, 0) / fsdComponents.length) || 0,
        averageComplexity: Math.round(fsdComponents.reduce((sum, c) => sum + c.complexity, 0) / fsdComponents.length) || 0
      }
    },

    duplicates: Object.entries(duplicates).map(([name, data]) => ({
      name,
      count: data.count,
      locations: data.components.map(c => ({
        path: c.path,
        lines: c.lines,
        type: c.location
      }))
    })),

    similar: similar.slice(0, 20), // Топ-20 похожих

    migrationProgress: {
      completed: [],
      inProgress: [],
      pending: []
    },

    recommendations: generateRecommendations(duplicates, similar, legacyComponents, fsdComponents)
  };

  // Определяем статус миграции
  legacyComponents.forEach(legacy => {
    const fsdEquivalent = fsdComponents.find(fsd => 
      areSimilarNames(legacy.name, fsd.name)
    );

    if (fsdEquivalent) {
      report.migrationProgress.completed.push({
        legacy: legacy.path,
        fsd: fsdEquivalent.path
      });
    } else {
      report.migrationProgress.pending.push({
        component: legacy.name,
        path: legacy.path,
        complexity: legacy.complexity
      });
    }
  });

  return report;
}

function generateRecommendations(duplicates, similar, legacy, fsd) {
  const recommendations = [];

  // Рекомендации по дубликатам
  Object.entries(duplicates).forEach(([name, data]) => {
    if (data.count > 2) {
      recommendations.push({
        type: 'critical',
        message: `Компонент "${name}" имеет ${data.count} копий. Требуется объединение.`,
        action: 'merge'
      });
    }
  });

  // Рекомендации по миграции
  const unmigrated = legacy.filter(l => 
    !fsd.some(f => areSimilarNames(l.name, f.name))
  );

  if (unmigrated.length > 0) {
    recommendations.push({
      type: 'migration',
      message: `${unmigrated.length} компонентов ожидают миграции на FSD`,
      components: unmigrated.slice(0, 10).map(c => c.name)
    });
  }

  // Рекомендации по сложности
  const complexComponents = [...legacy, ...fsd].filter(c => c.complexity > 15);
  if (complexComponents.length > 0) {
    recommendations.push({
      type: 'refactoring',
      message: `${complexComponents.length} компонентов имеют высокую сложность и требуют рефакторинга`,
      components: complexComponents.slice(0, 5).map(c => ({
        name: c.name,
        complexity: c.complexity
      }))
    });
  }

  return recommendations;
}

// Главная функция
function main() {

  // Получаем все компоненты

  const legacyFiles = getFiles(config.legacyDir);
  const legacyComponents = legacyFiles.map(analyzeComponent);

  const fsdFiles = getFiles(config.fsdDir);
  const fsdComponents = fsdFiles.map(analyzeComponent);

  // Анализируем дубликаты

  const allComponents = [...legacyComponents, ...fsdComponents];
  const duplicates = findDuplicates(allComponents);

  // Анализируем похожие компоненты

  const similar = findSimilar(allComponents);

  // Генерируем отчет

  const report = generateReport(legacyComponents, fsdComponents, duplicates, similar);

  // Сохраняем отчет
  fs.writeFileSync(config.outputFile, JSON.stringify(report, null, 2));

  // Выводим краткую сводку

  if (report.recommendations.length > 0) {

    report.recommendations.slice(0, 3).forEach(rec => {

    });
  }

}

// Запуск
main();