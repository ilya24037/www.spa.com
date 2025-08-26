$ChatFile = '.ai-team\chat.md' 
$Role = 'DEVOPS' 
$host.UI.RawUI.WindowTitle = 'DEVOPS AGENT' 
Clear-Host 
Write-Host '=============================' -ForegroundColor Yellow 
Write-Host '     DEVOPS AGENT ONLINE     ' -ForegroundColor Yellow 
Write-Host '=============================' -ForegroundColor Yellow 
Write-Host '' 
function Send-Msg { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm' 
    Add-Content $ChatFile "[$time] [$Role]: $msg" -Encoding UTF8 
} 
Send-Msg 'DevOps agent connected' 
Write-Host 'Monitoring chat...' -ForegroundColor Cyan 
$lastCount = 0 
while ($true) { 
    $lines = Get-Content $ChatFile -Encoding UTF8 -ErrorAction SilentlyContinue 
    if ($lines.Count -gt $lastCount) { 
        for ($i = $lastCount; $i -lt $lines.Count; $i++) { 
            $line = $lines[$i] 
            if ($line -match '@devops' -or $line -match '@all') { 
                if ($line -notmatch '\[DEVOPS\]') { 
                    Write-Host "Message: $line" -ForegroundColor Green 
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
