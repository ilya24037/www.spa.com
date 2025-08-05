const fs = require('fs')
const path = require('path')

const searchForAlerts = (dir) => {
  const results = []
  
  const processDirectory = (currentDir) => {
    const files = fs.readdirSync(currentDir)
    
    files.forEach(file => {
      const filePath = path.join(currentDir, file)
      const stat = fs.statSync(filePath)
      
      if (stat.isDirectory() && !file.includes('node_modules')) {
        processDirectory(filePath)
      } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
        const content = fs.readFileSync(filePath, 'utf8')
        const lines = content.split('\n')
        
        lines.forEach((line, index) => {
          // Ищем alert( но не в комментариях
          if (line.includes('alert(') && 
              !line.trim().startsWith('//') && 
              !line.trim().startsWith('*') &&
              !line.includes('// Toast для замены alert()')) {
            results.push({
              file: filePath.replace('C:\\www.spa.com\\', ''),
              line: index + 1,
              content: line.trim()
            })
          }
        })
      }
    })
  }
  
  processDirectory(dir)
  return results
}

console.log('🔍 Поиск фактических alert() в коде...')
const alerts = searchForAlerts('./resources/js')

if (alerts.length === 0) {
  console.log('✅ Фактических вызовов alert() не найдено!')
  console.log('📝 Все найденные вхождения - это комментарии или уже замененные toast')
} else {
  console.log(`❌ Найдено ${alerts.length} вызовов alert():`)
  alerts.forEach(alert => {
    console.log(`   ${alert.file}:${alert.line} - ${alert.content}`)
  })
}