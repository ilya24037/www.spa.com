const fs = require('fs')
const path = require('path')

const replaceAlerts = (filePath) => {
  let content = fs.readFileSync(filePath, 'utf8')
  let modified = false

  // Замены alert на toast (избегаем комментарии)
  const alertPattern = /(?<!\/\/.*?)alert\(['"`]([^'"`]+)['"`]\)/g
  const alertVariablePattern = /(?<!\/\/.*?)alert\(([^)]+)\)/g

  if (content.match(alertPattern)) {
    content = content.replace(alertPattern, "toast.info('$1')")
    modified = true
  }

  if (content.match(alertVariablePattern) && !content.includes('toast.info')) {
    content = content.replace(alertVariablePattern, "toast.info($1)")
    modified = true
  }

  // Добавить импорт useToast если его нет и есть toast.
  if (content.includes('toast.') && !content.includes('useToast')) {
    const scriptMatch = content.match(/<script[^>]*>/i)
    if (scriptMatch) {
      const importLine = "import { useToast } from '@/src/shared/composables/useToast'\n"
      const toastLine = "const toast = useToast()\n\n"
      content = content.replace(scriptMatch[0], scriptMatch[0] + '\n' + importLine + toastLine)
      modified = true
    }
  }

  if (modified) {
    fs.writeFileSync(filePath, content)
    return true
  }
  return false
}

// Обработать файлы
const processDirectory = (dir) => {
  const files = fs.readdirSync(dir)
  let processedCount = 0

  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)

    if (stat.isDirectory() && !file.includes('node_modules')) {
      processedCount += processDirectory(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js')) {
      const content = fs.readFileSync(filePath, 'utf8')
      // Ищем alert( но не в комментариях
      if (content.includes('alert(') && !content.match(/^\s*\/\/.*alert\(/m)) {
        if (replaceAlerts(filePath)) {
          processedCount++
        }
      }
    }
  })

  return processedCount
}

на toast...')
const count = processDirectory('./resources/js')
