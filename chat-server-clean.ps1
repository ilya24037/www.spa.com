# AI Team Chat Server - Clean UTF-8 Version
param([int]$Port = 8080)

# UTF-8 Setup
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::InputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$chatPath = "C:\www.spa.com\.ai-team\chat.md"
$htmlPath = "C:\www.spa.com\ai-team-chat.html"

Write-Host ""
Write-Host "AI Team Chat Server (UTF-8 Clean)" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Check files
if (!(Test-Path $chatPath)) {
    Write-Host "ERROR: chat.md not found!" -ForegroundColor Red
    exit
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$Port/")
$listener.Start()

Write-Host "OK: Server started!" -ForegroundColor Green
Write-Host "URL: http://localhost:$Port/" -ForegroundColor Cyan
Write-Host "UTF-8 encoding active" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
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
    
    Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] $method $url" -ForegroundColor DarkGray
    
    if ($url -eq "/.ai-team/chat.md") {
        # Read file with UTF-8
        $content = [System.IO.File]::ReadAllText($chatPath, [System.Text.Encoding]::UTF8)
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
        
        $response.ContentType = "text/plain; charset=utf-8"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  OK: Sent chat.md" -ForegroundColor Green
    }
    elseif ($url -eq "/send-message" -and $method -eq "POST") {
        # Read request body with UTF-8
        $reader = New-Object System.IO.StreamReader($request.InputStream, [System.Text.Encoding]::UTF8)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        try {
            # Parse JSON
            $data = $body | ConvertFrom-Json
            $message = $data.message
            
            # Append message to file
            [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
            
            # Send success response
            $response.StatusCode = 200
            $responseJson = '{"status":"ok","message":"Message saved"}'
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
            $response.ContentType = "application/json; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            
            Write-Host "  OK: Message saved" -ForegroundColor Green
        }
        catch {
            Write-Host "  ERROR: $_" -ForegroundColor Red
            $response.StatusCode = 400
            $errorJson = '{"status":"error","message":"Processing error"}'
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($errorJson)
            $response.ContentType = "application/json; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
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
            time = (Get-Date).ToString("HH:mm:ss")
        } | ConvertTo-Json
        
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($statusJson)
        $response.ContentType = "application/json; charset=utf-8"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  OK: Status sent" -ForegroundColor Green
    }
    elseif ($url -eq "/api/command" -and $method -eq "POST") {
        $reader = New-Object System.IO.StreamReader($request.InputStream, [System.Text.Encoding]::UTF8)
        $body = $reader.ReadToEnd()
        $reader.Close()
        
        $time = (Get-Date).ToString('HH:mm')
        $data = $body | ConvertFrom-Json
        $command = $data.command
        
        $message = switch ($command) {
            "/sync" { "[$time] [PM]: @all SYNC - Update your work status" }
            "/status" { "[$time] [PM]: @all STATUS - Report current tasks" }
            "/clear" { 
                $header = [System.IO.File]::ReadAllLines($chatPath, [System.Text.Encoding]::UTF8) | Select-Object -First 30
                [System.IO.File]::WriteAllLines($chatPath, $header, [System.Text.Encoding]::UTF8)
                "[$time] [SYSTEM]: Chat cleared"
            }
            "/start-backend" {
                & powershell.exe -File "ai-agent-manager.ps1" -Action "start-backend"
                "[$time] [SYSTEM]: Starting Backend agent..."
            }
            "/start-frontend" {
                & powershell.exe -File "ai-agent-manager.ps1" -Action "start-frontend"
                "[$time] [SYSTEM]: Starting Frontend agent..."
            }
            "/start-devops" {
                & powershell.exe -File "ai-agent-manager.ps1" -Action "start-devops"
                "[$time] [SYSTEM]: Starting DevOps agent..."
            }
            "/start-all" {
                # Use new launcher for better reliability
                Start-Process powershell -ArgumentList "-ExecutionPolicy", "Bypass", "-File", "ai-agent-launcher.ps1", "-Role", "TeamLead" -WindowStyle Minimized
                Start-Sleep -Seconds 2
                Start-Process powershell -ArgumentList "-ExecutionPolicy", "Bypass", "-File", "ai-agent-launcher.ps1", "-Role", "Backend" -WindowStyle Minimized
                Start-Process powershell -ArgumentList "-ExecutionPolicy", "Bypass", "-File", "ai-agent-launcher.ps1", "-Role", "Frontend" -WindowStyle Minimized
                Start-Process powershell -ArgumentList "-ExecutionPolicy", "Bypass", "-File", "ai-agent-launcher.ps1", "-Role", "DevOps" -WindowStyle Minimized
                "[$time] [SYSTEM]: Starting Team Lead and all AI agents with new launcher..."
            }
            "/start-teamlead" {
                & powershell.exe -File "ai-agent-manager.ps1" -Action "start-teamlead"
                "[$time] [SYSTEM]: Starting Team Lead coordinator..."
            }
            "/stop-all" {
                & powershell.exe -File "ai-agent-manager.ps1" -Action "stop-all"
                "[$time] [SYSTEM]: Stopping all AI agents..."
            }
            default { "[$time] [PM]: $command" }
        }
        
        [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
        
        $response.StatusCode = 200
        $responseJson = '{"status":"ok"}'
        $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseJson)
        $response.ContentType = "application/json; charset=utf-8"
        $response.ContentLength64 = $buffer.Length
        $response.OutputStream.Write($buffer, 0, $buffer.Length)
        Write-Host "  OK: Command executed: $command" -ForegroundColor Green
    }
    elseif ($url -eq "/" -or $url -eq "/index.html") {
        # Serve dashboard by default
        $dashboardPath = "C:\www.spa.com\ai-team-dashboard.html"
        if (Test-Path $dashboardPath) {
            $content = [System.IO.File]::ReadAllText($dashboardPath, [System.Text.Encoding]::UTF8)
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  OK: Dashboard served" -ForegroundColor Green
        }
        elseif (Test-Path $htmlPath) {
            $content = [System.IO.File]::ReadAllText($htmlPath, [System.Text.Encoding]::UTF8)
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  OK: Chat UI served" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
            Write-Host "  ERROR: UI not found" -ForegroundColor Red
        }
    }
    elseif ($url -eq "/ai-team-dashboard.html") {
        $dashboardPath = "C:\www.spa.com\ai-team-dashboard.html"
        if (Test-Path $dashboardPath) {
            $content = [System.IO.File]::ReadAllText($dashboardPath, [System.Text.Encoding]::UTF8)
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  OK: Dashboard served" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
        }
    }
    elseif ($url -eq "/ai-team-dashboard-channels.html") {
        $channelsPath = "C:\www.spa.com\ai-team-dashboard-channels.html"
        if (Test-Path $channelsPath) {
            $content = [System.IO.File]::ReadAllText($channelsPath, [System.Text.Encoding]::UTF8)
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentType = "text/html; charset=utf-8"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  OK: Dashboard with channels served" -ForegroundColor Green
        }
        else {
            $response.StatusCode = 404
            Write-Host "  ERROR: Dashboard channels not found" -ForegroundColor Red
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