param([int]$Port = 8080)

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$chatPath = "C:\www.spa.com\.ai-team\chat.md"
$htmlPath = "C:\www.spa.com\ai-team-chat.html"

Write-Host ""
Write-Host "AI Team Chat Server v2" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan
Write-Host ""

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$Port/")
$listener.Start()

Write-Host "Server running on http://localhost:$Port/" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

while ($listener.IsListening) {
    $context = $listener.GetContext()
    $request = $context.Request
    $response = $context.Response
    
    $response.Headers.Add("Access-Control-Allow-Origin", "*")
    $response.Headers.Add("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
    $response.Headers.Add("Access-Control-Allow-Headers", "Content-Type")
    
    $url = $request.Url.LocalPath
    $method = $request.HttpMethod
    
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] $method $url" -ForegroundColor DarkGray
    
    if ($url -eq "/.ai-team/chat.md") {
        if (Test-Path $chatPath) {
            $content = Get-Content $chatPath -Raw -Encoding UTF8
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/plain; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  Sent chat.md" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
        }
    }
    elseif ($url -eq "/send-message" -and $method -eq "POST") {
        $reader = New-Object System.IO.StreamReader($request.InputStream, [System.Text.Encoding]::UTF8)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        if ($body -match '"message"\s*:\s*"([^"]+)"') {
            $message = $matches[1]
            $message = $message -replace '\\n', "`n"
            # Декодируем Unicode escape sequences
            $message = [System.Text.RegularExpressions.Regex]::Unescape($message)
            [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
            
            $response.StatusCode = 200
            $responseJson = '{"status":"ok"}'
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
            $response.ContentType = "application/json"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  Message saved" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 400
        }
    }
    elseif ($url -eq "/api/status") {
        $statusJson = '{"backend":"online","frontend":"online","devops":"online"}'
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($statusJson)
        $response.ContentType = "application/json"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  Status sent" -ForegroundColor Green
    }
    elseif ($url -eq "/api/command" -and $method -eq "POST") {
        $reader = New-Object System.IO.StreamReader($request.InputStream, $request.ContentEncoding)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        $time = Get-Date -Format 'HH:mm'
        
        if ($body -match '"/sync"') {
            $msg = "[$time] [PM]: @all SYNC - Please update your current work status"
            Add-Content -Path $chatPath -Value $msg -Encoding UTF8
            Write-Host "  Sync command" -ForegroundColor Green
        }
        elseif ($body -match '"/status"') {
            $msg = "[$time] [PM]: @all STATUS - Report your current task status"
            Add-Content -Path $chatPath -Value $msg -Encoding UTF8
            Write-Host "  Status command" -ForegroundColor Green
        }
        
        $response.StatusCode = 200
        $responseJson = '{"status":"ok"}'
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
        $response.ContentType = "application/json"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
    }
    elseif ($url -eq "/" -or $url -eq "/index.html") {
        if (Test-Path $htmlPath) {
            $content = Get-Content $htmlPath -Raw -Encoding UTF8
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  Served chat UI" -ForegroundColor Green
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
Write-Host "Server stopped" -ForegroundColor Yellow