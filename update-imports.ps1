# PowerShell скрипт для обновления импортов моделей
Write-Host "Starting model imports update..." -ForegroundColor Green

$replacements = @{
    "use App\Models\User" = "use App\Domain\User\Models\User"
    "use App\Models\Ad" = "use App\Domain\Ad\Models\Ad"
    "use App\Models\AdContent" = "use App\Domain\Ad\Models\AdContent"
    "use App\Models\AdMedia" = "use App\Domain\Ad\Models\AdMedia"
    "use App\Models\AdPricing" = "use App\Domain\Ad\Models\AdPricing"
    "use App\Models\AdSchedule" = "use App\Domain\Ad\Models\AdSchedule"
    "use App\Models\Service" = "use App\Domain\Service\Models\Service"
    "use App\Models\Booking" = "use App\Domain\Booking\Models\Booking"
    "use App\Models\BookingService" = "use App\Domain\Booking\Models\BookingService"
    "use App\Models\BookingSlot" = "use App\Domain\Booking\Models\BookingSlot"
    "use App\Models\Review" = "use App\Domain\Review\Models\Review"
    "use App\Models\ReviewReply" = "use App\Domain\Review\Models\ReviewReply"
    "use App\Models\MasterProfile" = "use App\Domain\Master\Models\MasterProfile"
    "use App\Models\MasterPhoto" = "use App\Domain\Media\Models\Photo"
    "use App\Models\MasterVideo" = "use App\Domain\Media\Models\Video"
    "use App\Models\Payment" = "use App\Domain\Payment\Models\Payment"
    "use App\Models\Media" = "use App\Domain\Media\Models\Media"
    "use App\Models\Notification" = "use App\Domain\Notification\Models\Notification"
    "use App\Models\NotificationDelivery" = "use App\Domain\Notification\Models\NotificationDelivery"
    "use App\Models\UserBalance" = "use App\Domain\User\Models\UserBalance"
    "use App\Models\Schedule" = "use App\Domain\Master\Models\Schedule"
}

$files = Get-ChildItem -Path "C:\www.spa.com\app" -Filter "*.php" -Recurse | 
         Where-Object { $_.FullName -notlike "*\app\Models\*" }

$updatedCount = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    
    foreach ($old in $replacements.Keys) {
        $new = $replacements[$old]
        $content = $content -replace [regex]::Escape($old), $new
        
        # Also replace in code with backslashes
        $oldClass = $old -replace "use ", "" -replace "\\", "\\\\"
        $newClass = $new -replace "use ", "" -replace "\\", "\\\\"
        $content = $content -replace [regex]::Escape($oldClass), $newClass
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        $updatedCount++
        Write-Host "Updated: $($file.FullName)" -ForegroundColor Yellow
    }
}

Write-Host "`nUpdated $updatedCount files" -ForegroundColor Green

# Check remaining
Write-Host "`nChecking for remaining old imports..." -ForegroundColor Cyan
$remaining = Get-ChildItem -Path "C:\www.spa.com\app" -Filter "*.php" -Recurse | 
             Where-Object { $_.FullName -notlike "*\app\Models\*" } |
             Select-String -Pattern "use App\\Models\\" -List

if ($remaining.Count -gt 0) {
    Write-Host "`nFiles still using old imports:" -ForegroundColor Red
    $remaining | ForEach-Object { Write-Host "  - $($_.Path)" }
} else {
    Write-Host "`nNo files using old imports found!" -ForegroundColor Green
}