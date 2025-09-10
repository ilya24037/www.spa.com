$ChatFile = '.ai-team\chat.md' 
$Role = 'FRONTEND' 
$host.UI.RawUI.WindowTitle = 'FRONTEND AGENT' 
Clear-Host 
Write-Host '=============================' -ForegroundColor Cyan 
Write-Host '    FRONTEND AGENT ONLINE    ' -ForegroundColor Cyan 
Write-Host '=============================' -ForegroundColor Cyan 
Write-Host '' 
function Send-Msg { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm' 
    Add-Content $ChatFile "[$time] [$Role]: $msg" -Encoding UTF8 
} 
Send-Msg 'Frontend agent connected' 
Write-Host 'Monitoring chat...' -ForegroundColor Yellow 
$lastCount = 0 
while ($true) { 
    $lines = Get-Content $ChatFile -Encoding UTF8 -ErrorAction SilentlyContinue 
    if ($lines.Count -gt $lastCount) { 
        for ($i = $lastCount; $i -lt $lines.Count; $i++) { 
            $line = $lines[$i] 
            if ($line -match '@frontend' -or $line -match '@all') { 
                if ($line -notmatch '\[FRONTEND\]') { 
                    Write-Host "Message: $line" -ForegroundColor Yellow 
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
