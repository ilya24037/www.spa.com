const fs = require('fs')
const path = require('path')

const removeConsoleLog = (filePath) => {
  const content = fs.readFileSync(filePath, 'utf8')
  const lines = content.split('\n')

  const cleanedLines = lines.filter(line => {
    const trimmed = line.trim()
    return !trimmed.startsWith('&& 
           !trimmed.startsWith('console.error(') &&
           !trimmed.startsWith('console.warn(') &&
           !trimmed.startsWith('console.info(') &&
           !trimmed.includes('// console.log')
  })

  fs.writeFileSync(filePath, cleanedLines.join('\n'))
}

// Обработать все Vue и JS файлы
const processDirectory = (dir) => {
  const files = fs.readdirSync(dir)

  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)

    if (stat.isDirectory()) {
      processDirectory(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js')) {
      removeConsoleLog(filePath)
    }
  })
}

processDirectory('./resources/js')
