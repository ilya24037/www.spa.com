# Create Virtual Office Structure
Write-Host "Creating Virtual Office structure..." -ForegroundColor Cyan

$basePath = "C:\www.spa.com\.ai-team"

# Directory structure
$directories = @(
    "virtual-office\inbox\teamlead",
    "virtual-office\inbox\backend",
    "virtual-office\inbox\frontend",
    "virtual-office\inbox\devops",
    "virtual-office\inbox\qa",
    "virtual-office\outbox\teamlead",
    "virtual-office\outbox\backend",
    "virtual-office\outbox\frontend",
    "virtual-office\outbox\devops",
    "virtual-office\outbox\qa",
    "virtual-office\tasks",
    "virtual-office\reports",
    "virtual-office\shared\docs",
    "virtual-office\shared\specs",
    "virtual-office\shared\code",
    "virtual-office\channels\general",
    "virtual-office\channels\standup",
    "virtual-office\channels\help",
    "system",
    "qa"
)

# Create directories
foreach ($dir in $directories) {
    $fullPath = Join-Path $basePath $dir
    if (!(Test-Path $fullPath)) {
        New-Item -ItemType Directory -Path $fullPath -Force | Out-Null
        Write-Host "  Created: $dir" -ForegroundColor Green
    } else {
        Write-Host "  Exists: $dir" -ForegroundColor Yellow
    }
}

Write-Host "`nVirtual Office structure created successfully!" -ForegroundColor Green