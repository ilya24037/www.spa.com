# AI Team Chat Server
# Simple HTTP server for chat messaging

param(
    [int]$Port = 8080
)

# UTF-8 encoding
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$chatPath = "C:\www.spa.com\.ai-team\chat.md"

Write-Host ""
Write-Host "🚀 AI Team Chat Server" -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Server starting on port $Port..." -ForegroundColor Yellow
Write-Host "Chat file: $chatPath" -ForegroundColor Gray
Write-Host ""

# Create HTTP listener
$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$Port/")

try {
    $listener.Start()
    Write-Host "✅ Server started successfully!" -ForegroundColor Green
    Write-Host "📡 Listening on http://localhost:$Port/" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Waiting for requests..." -ForegroundColor Gray
    Write-Host ""

    while ($listener.IsListening) {
        $context = $listener.GetContext()
        $request = $context.Request
        $response = $context.Response

        # Enable CORS
        $response.Headers.Add("Access-Control-Allow-Origin", "*")
        $response.Headers.Add("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        $response.Headers.Add("Access-Control-Allow-Headers", "Content-Type")

        $url = $request.Url.LocalPath
        $method = $request.HttpMethod

        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] $method $url" -ForegroundColor DarkGray

        if ($url -eq "/.ai-team/chat.md") {
            # Serve chat.md file
            if (Test-Path $chatPath) {
                $content = Get-Content $chatPath -Raw -Encoding UTF8
                $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
                $response.ContentType = "text/plain; charset=utf-8"
                $response.ContentLength64 = $buffer.Length
                $response.OutputStream.Write($buffer, 0, $buffer.Length)
                Write-Host "  ✓ Sent chat.md ($($buffer.Length) bytes)" -ForegroundColor Green
            } 
            else {
                $response.StatusCode = 404
                Write-Host "  ✗ chat.md not found" -ForegroundColor Red
            }
        }
        elseif ($url -eq "/send-message" -and $method -eq "POST") {
            # Handle message sending
            $reader = New-Object System.IO.StreamReader($request.InputStream, $request.ContentEncoding)
            $body = $reader.ReadToEnd()
            $reader.Close()

            $success = $false
            try {
                $data = $body | ConvertFrom-Json
                $message = $data.message
                
                # Append to chat.md
                Add-Content -Path $chatPath -Value $message -Encoding UTF8
                
                $response.StatusCode = 200
                $responseText = '{"status":"ok"}'
                $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseText)
                $response.ContentType = "application/json"
                $response.ContentLength64 = $buffer.Length
                $response.OutputStream.Write($buffer, 0, $buffer.Length)
                
                Write-Host "  ✓ Message saved" -ForegroundColor Green
                $success = $true
            } 
            catch {
                Write-Host "  ✗ Invalid JSON: $_" -ForegroundColor Red
            }
            
            if (-not $success) {
                $response.StatusCode = 400
            }
        }
        elseif ($url -eq "/api/status") {
            # Return status of AI agents
            $status = @{
                backend = "online"
                frontend = "online"
                devops = "online"
                server = "running"
                timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            }
            
            $json = $status | ConvertTo-Json
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($json)
            $response.ContentType = "application/json"
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
            Write-Host "  ✓ Status sent" -ForegroundColor Green
        }
        elseif ($url -eq "/api/command" -and $method -eq "POST") {
            # Handle commands
            $reader = New-Object System.IO.StreamReader($request.InputStream, $request.ContentEncoding)
            $body = $reader.ReadToEnd()
            $reader.Close()

            $success = $false
            try {
                $data = $body | ConvertFrom-Json
                $command = $data.command
                $time = Get-Date -Format 'HH:mm'
                
                if ($command -eq "/sync") {
                    $message = "[$time] [PM]: @all SYNC - Please update your current work status"
                    Add-Content -Path $chatPath -Value $message -Encoding UTF8
                    Write-Host "  ✓ Sync command executed" -ForegroundColor Green
                }
                elseif ($command -eq "/status") {
                    $message = "[$time] [PM]: @all STATUS - Report your current task status"
                    Add-Content -Path $chatPath -Value $message -Encoding UTF8
                    Write-Host "  ✓ Status command executed" -ForegroundColor Green
                }
                elseif ($command -eq "/clear") {
                    # Keep header, clear messages
                    $header = Get-Content $chatPath -Encoding UTF8 | Select-Object -First 30
                    $header | Out-File $chatPath -Encoding UTF8
                    "[$time] [SYSTEM]: Chat cleared" | Add-Content $chatPath -Encoding UTF8
                    Write-Host "  ✓ Chat cleared" -ForegroundColor Green
                }
                else {
                    Write-Host "  ⚠ Unknown command: $command" -ForegroundColor Yellow
                }
                
                $response.StatusCode = 200
                $responseText = '{"status":"ok"}'
                $buffer = [System.Text.Encoding]::UTF8.GetBytes($responseText)
                $response.ContentType = "application/json"
                $response.ContentLength64 = $buffer.Length
                $response.OutputStream.Write($buffer, 0, $buffer.Length)
                $success = $true
            } 
            catch {
                Write-Host "  ✗ Command error: $_" -ForegroundColor Red
            }
            
            if (-not $success) {
                $response.StatusCode = 400
            }
        }
        elseif ($url -eq "/favicon.ico") {
            $response.StatusCode = 404
        }
        elseif ($url -eq "/" -or $url -eq "/index.html") {
            # Serve ai-team-chat.html for root
            $htmlPath = "C:\www.spa.com\ai-team-chat.html"
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
        else {
            $response.StatusCode = 404
            Write-Host "  ✗ Not found: $url" -ForegroundColor Red
        }

        $response.Close()
    }
} 
catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
} 
finally {
    if ($listener.IsListening) {
        $listener.Stop()
    }
    Write-Host ""
    Write-Host "Server stopped" -ForegroundColor Yellow
}