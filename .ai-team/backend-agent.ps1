$ChatFile = '.ai-team\chat.md' 
$Role = 'BACKEND' 
$host.UI.RawUI.WindowTitle = 'BACKEND AGENT' 
Clear-Host 
Write-Host '=============================' -ForegroundColor Green 
Write-Host '     BACKEND AGENT ONLINE    ' -ForegroundColor Green 
Write-Host '=============================' -ForegroundColor Green 
Write-Host '' 
function Send-Msg { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm' 
    Add-Content $ChatFile "[$time] [$Role]: $msg" -Encoding UTF8 
} 
Send-Msg 'Backend agent connected' 
Write-Host 'Monitoring chat...' -ForegroundColor Yellow 
$lastCount = 0 
while ($true) { 
    $lines = Get-Content $ChatFile -Encoding UTF8 -ErrorAction SilentlyContinue 
    if ($lines.Count -gt $lastCount) { 
        for ($i = $lastCount; $i -lt $lines.Count; $i++) { 
            $line = $lines[$i] 
            if ($line -match '@backend' -or $line -match '@all') { 
                if ($line -notmatch '\[BACKEND\]') { 
                    Write-Host "Message: $line" -ForegroundColor Cyan 
                    if ($line -match 'status') { 
                        Send-Msg 'Ready and monitoring' 
                    } 
                } 
            } 
        } 
        $lastCount = $lines.Count 
    } 
    Start-Sleep -Seconds 1 
} 
