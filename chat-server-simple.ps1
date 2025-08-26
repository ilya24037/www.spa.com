# AI Team Chat Server - Simplified Version
param([int]$Port = 8080)

# UTF-8 encoding
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$chatPath = "C:\www.spa.com\.ai-team\chat.md"
$htmlPath = "C:\www.spa.com\ai-team-chat.html"

Write-Host ""
Write-Host "🚀 AI Team Chat Server (Simple)" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Starting on port $Port..." -ForegroundColor Yellow
Write-Host ""

# Create HTTP listener
$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$Port/")
$listener.Start()

Write-Host "✅ Server started!" -ForegroundColor Green
Write-Host "📡 http://localhost:$Port/" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

# Main loop
while ($listener.IsListening) {
    $context = $listener.GetContext()
    $request = $context.Request
    $response = $context.Response
    
    # CORS headers
    $response.Headers.Add("Access-Control-Allow-Origin", "*")
    $response.Headers.Add("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
    $response.Headers.Add("Access-Control-Allow-Headers", "Content-Type")
    
    $url = $request.Url.LocalPath
    $method = $request.HttpMethod
    
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] $method $url" -ForegroundColor DarkGray
    
    # Route: Serve chat.md
    if ($url -eq "/.ai-team/chat.md") {
        if (Test-Path $chatPath) {
            $content = Get-Content $chatPath -Raw -Encoding UTF8
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/plain; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  ✓ Sent chat.md" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
            Write-Host "  ✗ chat.md not found" -ForegroundColor Red
        }
    }
    # Route: Send message
    elseif ($url -eq "/send-message" -and $method -eq "POST") {
        $reader = New-Object System.IO.StreamReader($request.InputStream, $request.ContentEncoding)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        # Simple JSON parsing
        if ($body -match '"message"\s*:\s*"([^"]+)"') {
            $message = $matches[1]
            $message = $message -replace '\\n', "`n"
            Add-Content -Path $chatPath -Value $message -Encoding UTF8
            
            $response.StatusCode = 200
            $responseJson = '{"status":"ok"}'
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
            $response.ContentType = "application/json"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  ✓ Message saved" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 400
            Write-Host "  ✗ Invalid request" -ForegroundColor Red
        }
    }
    # Route: API Status
    elseif ($url -eq "/api/status") {
        $statusJson = '{"backend":"online","frontend":"online","devops":"online","server":"running"}'
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($statusJson)
        $response.ContentType = "application/json"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  ✓ Status sent" -ForegroundColor Green
    }
    # Route: Commands
    elseif ($url -eq "/api/command" -and $method -eq "POST") {
        $reader = New-Object System.IO.StreamReader($request.InputStream, $request.ContentEncoding)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        $time = Get-Date -Format 'HH:mm'
        
        if ($body -match '"/sync"') {
            $msg = "[$time] [PM]: @all SYNC - Please update your current work status"
            Add-Content -Path $chatPath -Value $msg -Encoding UTF8
            Write-Host "  ✓ Sync command" -ForegroundColor Green
        }
        elseif ($body -match '"/status"') {
            $msg = "[$time] [PM]: @all STATUS - Report your current task status"
            Add-Content -Path $chatPath -Value $msg -Encoding UTF8
            Write-Host "  ✓ Status command" -ForegroundColor Green
        }
        elseif ($body -match '"/clear"') {
            $header = Get-Content $chatPath -Encoding UTF8 | Select-Object -First 30
            $header | Out-File $chatPath -Encoding UTF8
            "[$time] [SYSTEM]: Chat cleared" | Add-Content $chatPath -Encoding UTF8
            Write-Host "  ✓ Clear command" -ForegroundColor Green
        }
        
        $response.StatusCode = 200
        $responseJson = '{"status":"ok"}'
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
        $response.ContentType = "application/json"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
    }
    # Route: Favicon (ignore)
    elseif ($url -eq "/favicon.ico") {
        $response.StatusCode = 404
    }
    # Route: Main page
    elseif ($url -eq "/" -or $url -eq "/index.html") {
        if (Test-Path $htmlPath) {
            $content = Get-Content $htmlPath -Raw -Encoding UTF8
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  ✓ Served chat UI" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
            Write-Host "  ✗ Chat UI not found" -ForegroundColor Red
        }
    }
    # Route: Not found
    else {
        $response.StatusCode = 404
        Write-Host "  ✗ Not found: $url" -ForegroundColor Red
    }
    
    $response.Close()
}

$listener.Stop()
Write-Host "Server stopped" -ForegroundColor Yellow