# AI Team Chat Server - UTF-8 Fixed Version
param([int]$Port = 8080)

# Полная настройка UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::InputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$chatPath = "C:\www.spa.com\.ai-team\chat.md"
$htmlPath = "C:\www.spa.com\ai-team-chat.html"

Write-Host ""
Write-Host "🚀 AI Team Chat Server (UTF-8 Fixed)" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Проверка файлов
if (!(Test-Path $chatPath)) {
    Write-Host "❌ Файл chat.md не найден!" -ForegroundColor Red
    exit
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$Port/")
$listener.Start()

Write-Host "✅ Сервер запущен!" -ForegroundColor Green
Write-Host "📡 http://localhost:$Port/" -ForegroundColor Cyan
Write-Host "🔤 UTF-8 кодировка активна" -ForegroundColor Yellow
Write-Host ""
Write-Host "Нажмите Ctrl+C для остановки" -ForegroundColor Yellow
Write-Host ""

while ($listener.IsListening) {
    $context = $listener.GetContext()
    $request = $context.Request
    $response = $context.Response
    
    # CORS headers
    $response.Headers.Add("Access-Control-Allow-Origin", "*")
    $response.Headers.Add("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
    $response.Headers.Add("Access-Control-Allow-Headers", "Content-Type")
    $response.Headers.Add("Cache-Control", "no-cache")
    
    $url = $request.Url.LocalPath
    $method = $request.HttpMethod
    
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] $method $url" -ForegroundColor DarkGray
    
    if ($url -eq "/.ai-team/chat.md") {
        # Читаем файл с правильной кодировкой
        $content = [System.IO.File]::ReadAllText($chatPath, [System.Text.Encoding]::UTF8)
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
        
        $response.ContentType = "text/plain; charset=utf-8"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  ✓ Отправлен chat.md" -ForegroundColor Green
    }
    elseif ($url -eq "/send-message" -and $method -eq "POST") {
        # Читаем тело запроса с UTF-8
        $reader = New-Object System.IO.StreamReader($request.InputStream, [System.Text.Encoding]::UTF8)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        try {
            # Парсим JSON
            $data = $body | ConvertFrom-Json
            $message = $data.message
            
            # Добавляем сообщение в файл
            [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
            
            # Отправляем успешный ответ
            $response.StatusCode = 200
            $responseJson = '{"status":"ok","message":"Сообщение сохранено"}'
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
            $response.ContentType = "application/json; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            
            Write-Host "  ✓ Сообщение сохранено" -ForegroundColor Green
        }
        catch {
            Write-Host "  ✗ Ошибка: $_" -ForegroundColor Red
            $response.StatusCode = 400
            $errorJson = '{"status":"error","message":"Ошибка обработки"}'
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($errorJson)
            $response.ContentType = "application/json; charset=utf-8"
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
        }
    }
    elseif ($url -eq "/api/status") {
        $statusJson = @{
            backend = "online"
            frontend = "online"
            devops = "online"
            server = "running"
            encoding = "UTF-8"
            time = Get-Date -Format "HH:mm:ss"
        } | ConvertTo-Json
        
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($statusJson)
        $response.ContentType = "application/json; charset=utf-8"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  ✓ Статус отправлен" -ForegroundColor Green
    }
    elseif ($url -eq "/api/command" -and $method -eq "POST") {
        $reader = New-Object System.IO.StreamReader($request.InputStream, [System.Text.Encoding]::UTF8)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        $time = Get-Date -Format 'HH:mm'
        $data = $body | ConvertFrom-Json
        $command = $data.command
        
        $message = switch ($command) {
            "/sync" { "[$time] [PM]: @all СИНХРОНИЗАЦИЯ - Обновите статус вашей работы" }
            "/status" { "[$time] [PM]: @all СТАТУС - Сообщите о текущих задачах" }
            "/clear" { 
                $header = [System.IO.File]::ReadAllLines($chatPath, [System.Text.Encoding]::UTF8) | Select-Object -First 30
                [System.IO.File]::WriteAllLines($chatPath, $header, [System.Text.Encoding]::UTF8)
                "[$time] [SYSTEM]: Чат очищен"
            }
            default { "[$time] [PM]: $command" }
        }
        
        [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
        
        $response.StatusCode = 200
        $responseJson = '{"status":"ok"}'
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
        $response.ContentType = "application/json; charset=utf-8"
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  ✓ Команда выполнена: $command" -ForegroundColor Green
    }
    elseif ($url -eq "/" -or $url -eq "/index.html") {
        if (Test-Path $htmlPath) {
            $content = [System.IO.File]::ReadAllText($htmlPath, [System.Text.Encoding]::UTF8)
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  ✓ Интерфейс чата отправлен" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
        }
    }
    elseif ($url -eq "/favicon.ico") {
        $response.StatusCode = 404
    }
    else {
        $response.StatusCode = 404
    }
    
    $response.Close()
}

$listener.Stop()
Write-Host "Сервер остановлен" -ForegroundColor Yellow